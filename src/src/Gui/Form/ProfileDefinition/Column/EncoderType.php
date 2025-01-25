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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;

class EncoderType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AdvancedEncoderCollection $encoderCollection,
    ) {
    }

    /**
     * @param array<array-key, mixed> $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $encoders = [];
        foreach ($this->encoderCollection->getEncoderIdentifiers() as $encoderIdentifier) {
            $encoder = $this->encoderCollection->getEncoder($encoderIdentifier);
            $encoders[$this->encoderCollection->getEncoderShortName($encoder)] = $encoderIdentifier;
        }
        ksort($encoders);

        $builder
            ->add('encoder', ChoiceType::class, [
                'choices' => $encoders,
                'label' => $this->translator->trans('Encoder'),
                'required' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm selectpicker',
                    'data-live-search' => 'true',
                    'size' => 10,
                ],
            ])
        ;

        $builder->get('encoder')->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (empty($data)) {
                return;
            }

            $contextFormTypeClassName = $this->encoderCollection->getEncoder($data)?->getContextFormTypeClassName();
            if (!$contextFormTypeClassName) {
                return;
            }

            $form->getParent()->add('context', CollectionType::class, [
                'label' => $this->translator->trans('Encoder settings'),
                'required' => false,
                'entry_type' => $contextFormTypeClassName,
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
