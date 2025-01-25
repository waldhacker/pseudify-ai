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

namespace Waldhacker\Pseudify\Core\Gui\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Database\ConnectionManager;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\ProfileDefinitionCollection;

class ProfileSelectType extends AbstractType
{
    public function __construct(
        private readonly ProfileDefinitionCollection $profileDefinitionCollection,
        private readonly ConnectionManager $connectionManager,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $session = $this->requestStack->getSession();

        $profileDefinitions = [];
        foreach (array_map(fn (string $profileIdentifier): ProfileDefinition => $this->profileDefinitionCollection->getProfileDefinition($profileIdentifier), $this->profileDefinitionCollection->getProfileDefinitionIdentifiers()) as $profileDefinition) {
            $profileDefinitions[sprintf('%s (%s)', $profileDefinition->getIdentifier(), strstr((string) $profileDefinition->getPath(), 'src/Profiles/Yaml'))] = $profileDefinition->getIdentifier();
        }

        $connectionNames = [];
        foreach (array_keys($this->connectionManager->getConnections()) as $connectionName) {
            $connectionNames[$connectionName] = $connectionName;
        }

        $builder
            ->add('identifier', ChoiceType::class, [
                'choices' => $profileDefinitions,
                'label' => $this->translator->trans('Identifier'),
                'required' => true,
                'placeholder' => $this->translator->trans('Choose a profile'),
                'data' => $session->get('profileDefinition', null)?->getIdentifier(),
            ])
            ->add('connection', ChoiceType::class, [
                'choices' => $connectionNames,
                'label' => $this->translator->trans('Connection'),
                'required' => true,
                'placeholder' => $this->translator->trans('Choose a database connection'),
                'data' => $session->get('activeConnectionName', 'default'),
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->translator->trans('Load profile'),
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
