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

use Waldhacker\Pseudify\Core\Gui\Form\ProfileDefinition\Column\Encoder\HexEncoderType;

class HexEncoder extends AbstractEncoder implements EncoderInterface
{
    /** @var array<string, mixed> */
    protected array $defaultContext = [
        self::DATA_PICKER_PATH => null,
    ];

    /**
     * @param array<string, mixed> $context
     *
     * @return string|false
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        if (!is_string($data)) {
            return false;
        }

        return @hex2bin($data);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return string|null
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        if (!is_string($data)) {
            return null;
        }

        return @bin2hex($data);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        try {
            $decodedData = $this->decode($data, $context);
            if (is_string($decodedData)) {
                return true;
            }
        } catch (\Throwable) {
        }

        return false;
    }

    /**
     * @api
     */
    #[\Override]
    public function decodesToScalarDataOnly(): bool
    {
        return true;
    }

    /**
     * @api
     */
    #[\Override]
    public function getContextFormTypeClassName(): ?string
    {
        return HexEncoderType::class;
    }
}
