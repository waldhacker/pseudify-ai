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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzCompressEncoder;

class GzCompressEncoderType extends AbstractType
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
            ->add(GzCompressEncoder::DECODE_MAX_LENGTH, NumberType::class, [
                'label' => $this->translator->trans('Max length (decode)'),
                'required' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-check-input',
                    'step' => 1,
                    'min' => 0,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(GzCompressEncoder::ENCODE_ENCODING, ChoiceType::class, [
                'choices' => [
                    $this->translator->trans('DEFLATE algorithm as per RFC 1951') => \ZLIB_ENCODING_RAW,
                    $this->translator->trans('ZLIB compression algorithm as per RFC 1950') => \ZLIB_ENCODING_DEFLATE,
                    $this->translator->trans('GZIP algorithm as per RFC 1952') => \ZLIB_ENCODING_GZIP,
                ],
                'label' => $this->translator->trans('Encoding (encode)'),
                'required' => false,
                'multiple' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'size' => 10,
                ],
            ])
            ->add(GzCompressEncoder::ENCODE_LEVEL, NumberType::class, [
                'label' => $this->translator->trans('Compression level (encode)'),
                'required' => false,
                'html5' => true,
                'attr' => [
                    'class' => 'form-check-input',
                    'step' => 1,
                    'min' => -1,
                    'max' => 9,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ])
            ->add(GzCompressEncoder::DATA_PICKER_PATH, TextType::class, [
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
