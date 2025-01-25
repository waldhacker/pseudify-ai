<?php

declare(strict_types=1);

/*
 * This file is part of the pseudify database pseudonymizer project
 * - (c) 2025 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Waldhacker\Pseudify\Core\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileCreateType;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileSelectType;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionFactory;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Writer\ProfileDefinitionWriter;

/**
 * @internal
 */
class ProfileController extends AbstractController
{
    use AppContextTrait;

    public function __construct(
        private ProfileDefinitionCollection $profileDefinitionCollection,
        private ProfileDefinitionFactory $profileDefinitionFactory,
        private ProfileDefinitionWriter $profileDefinitionWriter,
        private readonly ConnectionManager $connectionManager,
        private TranslatorInterface $translator,
        private RequestStack $requestStack,
        protected ParameterBagInterface $params,
    ) {
    }

    #[\Symfony\Component\Routing\Attribute\Route('/profile/load', name: 'app_profile_load')]
    public function load(Request $request): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $profileCreateForm = $this->createForm(ProfileCreateType::class);
        $profileCreateForm->handleRequest($request);
        if ($profileCreateForm->isSubmitted() && $profileCreateForm->isValid()) {
            $data = $profileCreateForm->getData();

            try {
                $profileDefinition = $this->profileDefinitionFactory->create($data['identifier'] ?? time(), $data['description'] ?? '');
                $this->profileDefinitionWriter->write($profileDefinition->getIdentifier(), $profileDefinition);
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans(sprintf('Error while save profile "%s". Message: "%s".', $data['identifier'] ?? '', $e->getMessage())),
                );

                return $this->redirectToRoute('app_profile_load');
            }

            $this->addFlash(
                'success',
                $this->translator->trans(sprintf('Profile "%s" created', $data['identifier'] ?? '')),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        $profileSelectForm = $this->createForm(ProfileSelectType::class);
        $profileSelectForm->handleRequest($request);
        if ($profileSelectForm->isSubmitted() && $profileSelectForm->isValid()) {
            $data = $profileSelectForm->getData();

            try {
                $profileDefinition = $this->profileDefinitionFactory->load($data['identifier'] ?? '', $data['connection'] ?? 'default');
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans(sprintf('Profile "%s" does not exist. Message: "%s".', $data['identifier'] ?? '', $e->getMessage())),
                );

                return $this->redirectToRoute('app_profile_load');
            }

            $session = $this->requestStack->getSession();

            if ($session->get('activeProfileHasUpdates', false)) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('There are unsaved changes to the profile. Please save the changes first or unload the current profile.'),
                );

                return $this->redirectToRoute('app_profile_load');
            }

            $session->set('profileDefinition', $profileDefinition);
            $session->set('activeConnectionName', $data['connection'] ?? 'default');
            $session->set('activeProfileHasUpdates', false);

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('profile/load.html.twig', [
            'context' => $this->getAppContext(),
            'path' => strstr($this->getAppContext()['activeProfile']?->getPath() ?? '', 'src/Profiles/Yaml'),
            'profileCreateForm' => $profileCreateForm,
            'profileSelectForm' => $profileSelectForm,
            'hasProfiles' => !empty($this->profileDefinitionCollection->getProfileDefinitionIdentifiers()),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/profile/unload', name: 'app_profile_unload')]
    public function unload(): Response
    {
        $activeProfile = $this->getAppContext()['activeProfile'];
        $session = $this->requestStack->getSession();
        $session->set('profileDefinition', null);
        $session->set('activeConnectionName', 'default');
        $session->set('activeProfileHasUpdates', false);

        $this->addFlash(
            'success',
            $this->translator->trans(sprintf('Profile "%s" has been deactivated.', $activeProfile?->getIdentifier() ?? '')),
        );

        return $this->redirectToRoute('app_profile_load');
    }

    #[\Symfony\Component\Routing\Attribute\Route('/profile/save', name: 'app_profile_save')]
    public function save(): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        if (!$this->getAppContext()['activeProfile']) {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );

            return $this->redirectToRoute('app_profile_load');
        }

        return $this->render('profile/save.html.twig', [
            'context' => $this->getAppContext(),
            'path' => strstr((string) $this->getAppContext()['activeProfile']->getPath(), 'src/Profiles/Yaml'),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route('/profile/save/perform', name: 'app_profile_save_perform')]
    public function savePerform(): Response
    {
        $this->connectionManager->setConnectionName($this->getAppContext()['activeConnectionName']);

        $activeProfile = $this->getAppContext()['activeProfile'];
        if ($activeProfile) {
            try {
                $this->profileDefinitionWriter->write($activeProfile->getIdentifier(), $activeProfile);

                $session = $this->requestStack->getSession();
                $session->set('activeProfileHasUpdates', false);

                $this->addFlash(
                    'success',
                    $this->translator->trans(sprintf('Profile "%s" saved.', $activeProfile->getIdentifier())),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans(sprintf('Error while save profile "%s". Message: "%s".', $activeProfile->getIdentifier(), $e->getMessage())),
                );
            }
        } else {
            $this->addFlash(
                'warning',
                $this->translator->trans('No profile is activated. Please activate a profile first.'),
            );
        }

        return $this->redirectToRoute('app_profile_save');
    }
}
