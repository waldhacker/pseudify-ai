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

namespace Waldhacker\Pseudify\Encoder;

use Waldhacker\Pseudify\Core\Processor\Encoder\AbstractEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;

class Rot13Encoder extends AbstractEncoder implements EncoderInterface
{
    protected array $defaultContext = [
        self::DATA_PICKER_PATH => null,
    ];

    /**
     * @param string $data
     *
     * @return string|false
     *
     * @api
     */
    #[\Override]
    public function decode(mixed $data, array $context = []): mixed
    {
        return @str_rot13($data);
    }

    /**
     * @param string $data
     *
     * @return string
     *
     * @api
     */
    #[\Override]
    public function encode(mixed $data, array $context = []): mixed
    {
        return @str_rot13($data);
    }

    /**
     * @param string $data
     *
     * @api
     */
    #[\Override]
    public function canDecode(mixed $data, array $context = []): bool
    {
        if (!is_string($data)) {
            return false;
        }

        try {
            $decodedData = $this->decode($data, $context);
            if (is_string($decodedData)) {
                return true;
            }
        } catch (\Throwable $e) {
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
}
