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

abstract class AbstractEncoder implements EncoderInterface
{
    final public const string DATA_PICKER_PATH = 'data_picker_path';

    /** @var array<string, mixed> */
    protected array $defaultContext = [];

    /**
     * @param array<string, mixed> $defaultContext
     *
     * @api
     */
    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function setContext(array $context): EncoderInterface
    {
        $this->defaultContext = array_merge($this->defaultContext, $context);

        return $this;
    }

    /**
     * @return array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function getContext(): array
    {
        return $this->defaultContext;
    }

    /**
     * @api
     */
    #[\Override]
    public function getContextFormTypeClassName(): ?string
    {
        return null;
    }
}
