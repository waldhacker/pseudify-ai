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

namespace Waldhacker\Pseudify\Core\Processor\Encoder;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContext;
use Waldhacker\Pseudify\Core\Processor\Processing\ExpressionLanguage\ConditionExpressionContextFactory;

class ConditionalEncoder extends AbstractEncoder implements EncoderInterface
{
    final public const string CONDITIONS = 'conditional_conditions';
    final public const string CONDITIONS_CONDITION = 'conditional_conditions_condition';
    final public const string CONDITIONS_ENCODER = 'conditional_conditions_encoder';
    final public const string CONDITIONS_CONTEXT = 'conditional_conditions_context';
    final public const string EXPRESSION_FUNCTION_CONTEXT = 'conditional_expression_function_context';
    final public const string EXPRESSION_FUNCTION_PROVIDERS = 'conditional_expression_function_providers';

    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::CONDITIONS => [],
        self::EXPRESSION_FUNCTION_CONTEXT => null,
        self::EXPRESSION_FUNCTION_PROVIDERS => [],
    ];

    /**
     * @param array<string, mixed> $defaultContext
     *
     * @api
     */
    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = $this->harmonizeContext($defaultContext);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function setContext(array $context): EncoderInterface
    {
        $this->defaultContext = array_merge($this->defaultContext, $this->harmonizeContext($context));

        return $this;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        $context = array_merge($this->defaultContext, $this->harmonizeContext($context));
        $expressionLanguage = new ExpressionLanguage(providers: $context[self::EXPRESSION_FUNCTION_PROVIDERS]);

        foreach ($context[self::CONDITIONS] as $condition) {
            if (!$expressionLanguage->evaluate($condition[self::CONDITIONS_CONDITION], ['context' => $context[self::EXPRESSION_FUNCTION_CONTEXT]])) {
                continue;
            }

            $data = $condition[self::CONDITIONS_ENCODER]->decode($data, $condition[self::CONDITIONS_CONTEXT]);
            break;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        $context = array_merge($this->defaultContext, $this->harmonizeContext($context));
        $expressionLanguage = new ExpressionLanguage(providers: $context[self::EXPRESSION_FUNCTION_PROVIDERS]);

        foreach ($context[self::CONDITIONS] as $condition) {
            if (!$expressionLanguage->evaluate($condition[self::CONDITIONS_CONDITION], ['context' => $context[self::EXPRESSION_FUNCTION_CONTEXT]])) {
                continue;
            }

            $data = $condition[self::CONDITIONS_ENCODER]->encode($data, $condition[self::CONDITIONS_CONTEXT]);
            break;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        return true;
    }

    /**
     * @api
     */
    #[\Override]
    public function decodesToScalarDataOnly(): bool
    {
        return false;
    }

    /**
     * @return array<array-key, array{conditional_conditions_condition: string, conditional_conditions_encoder: EncoderInterface}>
     *
     * @internal
     */
    public function getConditions(): array
    {
        return $this->defaultContext[self::CONDITIONS];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function harmonizeContext(array $context): array
    {
        $newContext = [
            self::EXPRESSION_FUNCTION_CONTEXT => ($context[self::EXPRESSION_FUNCTION_CONTEXT] ?? null) instanceof ConditionExpressionContext
                                                      ? $context[self::EXPRESSION_FUNCTION_CONTEXT]
                                                      : ConditionExpressionContextFactory::empty(),
        ];

        foreach (($context[self::CONDITIONS] ?? []) as $condition) {
            if (empty($condition[self::CONDITIONS_CONDITION])) {
                continue;
            }
            if (!(($condition[self::CONDITIONS_ENCODER] ?? null) instanceof EncoderInterface)) {
                continue;
            }
            if (!isset($condition[self::CONDITIONS_CONTEXT]) || !is_array($condition[self::CONDITIONS_CONTEXT])) {
                $condition[self::CONDITIONS_CONTEXT] = [];
            }
            $newContext[self::CONDITIONS][] = $condition;
        }

        foreach (($context[self::EXPRESSION_FUNCTION_PROVIDERS] ?? []) as $provider) {
            if (!($provider instanceof ExpressionFunctionProviderInterface)) {
                continue;
            }
            $newContext[self::EXPRESSION_FUNCTION_PROVIDERS][] = $provider;
        }

        return $newContext;
    }
}
