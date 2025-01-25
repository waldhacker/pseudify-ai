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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition;

use Doctrine\DBAL\Types\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Gui\Validator\UniqueProfileDefinition;

class BasicConfigurationType extends AbstractType
{
    public function __construct(
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
        $activeProfile = $session->get('profileDefinition', null);

        $doctrineTypeNames = array_map(static fn (Type $type): string => $type->getName(), Type::getTypeRegistry()->getMap());
        ksort($doctrineTypeNames);

        $tableNames = $activeProfile?->getTableNames() ?? [];
        $tableNameChoices = array_combine($tableNames, $tableNames);
        ksort($tableNameChoices);

        $builder
            ->add('identifier', TextType::class, [
                'label' => $this->translator->trans('Identifier'),
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new UniqueProfileDefinition($activeProfile?->getIdentifier()),
                ],
                'data' => $activeProfile?->getIdentifier(),
            ])
            ->add('description', TextType::class, [
                'label' => $this->translator->trans('Profile description'),
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'data' => $activeProfile?->getDescription(),
            ])
            ->add('applicationName', TextType::class, [
                'label' => $this->translator->trans('Application name'),
                'required' => false,
                'constraints' => [],
                'data' => $activeProfile?->getApplicationName(),
                'empty_data' => '',
            ])
            ->add('applicationDescription', TextType::class, [
                'label' => $this->translator->trans('Application description'),
                'required' => false,
                'constraints' => [],
                'data' => $activeProfile?->getApplicationDescription(),
                'empty_data' => '',
            ])
            ->add('targetDataFrameCuttingLength', NumberType::class, [
                'label' => $this->translator->trans('Data frame cutting length (commandline analyze)'),
                'required' => false,
                'html5' => true,
                'attr' => ['step' => 1, 'min' => 0],
                'data' => $activeProfile?->getTargetDataFrameCuttingLength(),
            ])
            ->add('sourceStrings', CollectionType::class, [
                'label' => false,
                'required' => false,
                'entry_type' => TextType::class,
                'allow_add' => true,
                'allow_delete' => true,
                // 'delete_empty' => true,
                'prototype' => true,
                'data' => $activeProfile?->getSourceStrings() ?? [],
            ])
            ->add('excludedTargetColumnTypes', ChoiceType::class, [
                'choices' => $doctrineTypeNames,
                'label' => $this->translator->trans('Global excluded column types'),
                'required' => false,
                'multiple' => true,
                'attr' => ['size' => 10],
                'data' => $activeProfile?->getExcludedTargetColumnTypes() ?? [],
            ])
            ->add('excludedTargetTables', ChoiceType::class, [
                'choices' => $tableNameChoices,
                'label' => $this->translator->trans('Exclude tables'),
                'required' => false,
                'multiple' => true,
                'attr' => ['size' => 10],
                'data' => $activeProfile?->getExcludedTargetTables() ?? [],
            ])
            ->add('save', SubmitType::class, [
                'label' => $this->translator->trans('Update profile'),
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
