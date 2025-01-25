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

namespace Waldhacker\Pseudify\Core\Gui\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Waldhacker\Pseudify\Core\Gui\VarDumper\HtmlDumper;

/**
 * @internal
 */
final class PseudifyExtension extends AbstractExtension
{
    public function __construct(private readonly HtmlDumper $dumper)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('header_data_for_dump', $this->dumpHeader(...)),
        ];
    }

    public function dumpHeader(): string
    {
        return $this->dumper->buildHeaderData();
    }
}
