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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\CsvEncoder;

class CsvEncoderType extends AbstractType
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
            ->add(CsvEncoder::DELIMITER_KEY, TextType::class, [
                'label' => $this->translator->trans('Delimiter'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'maxlength' => 1,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(CsvEncoder::ENCLOSURE_KEY, TextType::class, [
                'label' => $this->translator->trans('Enclosure'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'maxlength' => 1,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(CsvEncoder::ESCAPE_CHAR_KEY, TextType::class, [
                'label' => $this->translator->trans('Escape char'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'maxlength' => 1,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(CsvEncoder::ESCAPE_FORMULAS_KEY, CheckboxType::class, [
                'label' => $this->translator->trans('Escape formulas'),
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(CsvEncoder::KEY_SEPARATOR_KEY, TextType::class, [
                'label' => $this->translator->trans('Key separator'),
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'maxlength' => 1,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(CsvEncoder::DATA_PICKER_PATH, TextType::class, [
                'label' => $this->translator->trans('Path'),
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
