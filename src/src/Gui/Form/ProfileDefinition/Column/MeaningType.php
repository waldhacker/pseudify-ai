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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Meaning\PropertyType;

class MeaningType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => $this->translator->trans('Name'),
                'required' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('property', PropertyType::class, [
                'label' => $this->translator->trans('Settings'),
                'required' => false,
            ])
            ->add('conditions', CollectionType::class, [
                'label' => $this->translator->trans('Conditions'),
                'required' => false,
                'entry_type' => ConditionType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => [
                        'class' => 'w-100',
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'prototype_name' => '__encoding-meaning-condition-index__',
                'attr' => [
                    'data-index' => 0,
                    'data-index-name' => '__encoding-meaning-condition-index__',
                    'data-label' => $this->translator->trans('Condition'),
                    'data-no-heading-label' => true,
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
            ])
        ;

        $builder->get('conditions')->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_array($data)) {
                return;
            }

            $options = $form->getConfig()->getOptions();
            $options['attr']['data-index'] = count($data);
            $form->getParent()->add('conditions', CollectionType::class, $options);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
