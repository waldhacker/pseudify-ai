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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Meaning;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Faker\FakerFormFactory;

class ContextType extends AbstractType
{
    public function __construct(
        private readonly FakerFormFactory $fakerFormFactory,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $formatterName = $form->getParent()->getParent()->get('type')->getData();

            if (empty($formatterName)) {
                return;
            }

            $contextFormTypes = $this->fakerFormFactory->buildContextFormTypes($formatterName);
            if (empty($contextFormTypes)) {
                return;
            }

            foreach ($contextFormTypes as $parameterName => $contextFormType) {
                $form->add($parameterName, $contextFormType[0], $contextFormType[1]);
            }
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
