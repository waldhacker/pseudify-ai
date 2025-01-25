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

namespace Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Faker;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Waldhacker\Pseudify\Core\Faker\FormatterInformation;

class FakerFormFactory
{
    public function __construct(
        private readonly FormatterInformation $formatterInformation,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function buildFakerFormatterChoiceList(): array
    {
        $choiceList = [];
        foreach ($this->formatterInformation->buildFormatterInformation() as $formatter) {
            $example = $formatter['example'] ?? '';
            $label = sprintf('%s (%s: "%s")', $formatter['name'], $this->translator->trans('example'), (strlen($example) > 50) ? substr($example, 0, 50).'...' : $example);
            $choiceList[$label] = $formatter['name'];
        }

        return $choiceList;
    }

    /**
     * @return array<string, array{string, array{label: string, help: string, required: bool, html5?: bool, attr: array<string, string|int>, row_attr: array<string, string|int>}}>
     */
    public function buildContextFormTypes(string $formatterName): array
    {
        $formatter = $this->formatterInformation->buildFormatterInformation()[$formatterName] ?? null;
        if (!$formatter) {
            return [];
        }

        $contextFormTypes = [];
        foreach ($formatter['parameters'] as $parameter) {
            $parameterName = $parameter['name'];
            $typeOrder = ['float', 'int', 'string', 'bool'];

            $valueType = null;
            if ($parameter['hasDefaultValue'] && 'null' !== $parameter['defaultValueType']) {
                $valueType = $parameter['defaultValueType'];
            } else {
                foreach ($typeOrder as $type) {
                    if (in_array($type, $parameter['types'])) {
                        $valueType = $type;
                        break;
                    }
                }
            }

            switch ($valueType) {
                case 'float':
                    $contextFormTypes[$parameterName] = $this->buildFloatFormType($parameter);
                    break;
                case 'int':
                    $contextFormTypes[$parameterName] = $this->buildIntFormType($parameter);
                    break;
                case 'string':
                    $contextFormTypes[$parameterName] = $this->buildStringFormType($parameter);
                    break;
                case 'bool':
                    $contextFormTypes[$parameterName] = $this->buildBoolFormType($parameter);
                    break;
            }
        }

        return $contextFormTypes;
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array{string, array{label: string, help: string, required: bool, html5: bool, attr: array<string, string|int>, row_attr: array<string, string|int>}}
     */
    private function buildFloatFormType(array $options): array
    {
        return [
            NumberType::class,
            [
                'label' => $this->translator->trans($options['name']),
                'help' => $this->translator->trans($options['description'] ?? ''),
                'required' => !$options['isOptional'],
                'html5' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array{string, array{label: string, help: string, required: bool, html5: bool, attr: array<string, string|int>, row_attr: array<string, string|int>}}
     */
    private function buildIntFormType(array $options): array
    {
        return [
            NumberType::class,
            [
                'label' => $this->translator->trans($options['name']),
                'help' => $this->translator->trans($options['description'] ?? ''),
                'required' => !$options['isOptional'],
                'html5' => true,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'step' => 1,
                    'min' => 0,
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array{string, array{label: string, help: string, required: bool, attr: array<string, string|int>, row_attr: array<string, string|int>}}
     */
    private function buildStringFormType(array $options): array
    {
        return [
            TextType::class,
            [
                'label' => $this->translator->trans($options['name']),
                'help' => $this->translator->trans($options['description'] ?? ''),
                'required' => !$options['isOptional'],
                'attr' => [
                    'class' => 'form-control form-control-sm',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return array{string, array{label: string, help: string, required: bool, attr: array<string, string|int>, row_attr: array<string, string|int>}}
     */
    private function buildBoolFormType(array $options): array
    {
        return [
            CheckboxType::class,
            [
                'label' => $this->translator->trans($options['name']),
                'help' => $this->translator->trans($options['description'] ?? ''),
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'row_attr' => [
                    'class' => 'mb-3',
                ],
            ],
        ];
    }
}
