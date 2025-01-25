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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Faker\FakerFormFactory;

class PropertyType extends AbstractType
{
    public function __construct(
        private readonly FakerFormFactory $fakerFormFactory,
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
            ->add('path', TextType::class, [
                'label' => $this->translator->trans('Path'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('scope', TextType::class, [
                'label' => $this->translator->trans('Scope'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('minimumGraphemeLength', NumberType::class, [
                'label' => $this->translator->trans('Min data length'),
                'help' => $this->translator->trans('Only fake data if data is greater than X chars'),
                'required' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'step' => 1,
                    'min' => 0,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => $this->fakerFormFactory->buildFakerFormatterChoiceList(),
                'label' => $this->translator->trans('Faker type'),
                'required' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm selectpicker',
                    'data-live-search' => 'true',
                    'size' => 10,
                ],
            ])
        ;

        $builder->get('type')->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (empty($data)) {
                return;
            }

            $contextFormTypes = $this->fakerFormFactory->buildContextFormTypes($data);
            if (empty($contextFormTypes)) {
                return;
            }

            $form->getParent()->add('context', CollectionType::class, [
                'label' => $this->translator->trans('Faker settings'),
                'required' => false,
                'entry_type' => ContextType::class,
                'entry_options' => [
                    'label' => false,
                    'row_attr' => [
                        'class' => 'w-100',
                    ],
                ],
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
                'row_attr' => [
                    'class' => 'mb-0',
                ],
            ]);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
