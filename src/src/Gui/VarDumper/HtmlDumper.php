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

namespace Waldhacker\Pseudify\Core\Gui\VarDumper;

/**
 * @internal
 */
class HtmlDumper extends \Symfony\Component\VarDumper\Dumper\HtmlDumper
{
    /**
     * @return string
     */
    #[\Override]
    protected function getDumpHeader()
    {
        $this->headerIsDumped = true;
        $this->dumpHeader = '';

        return '';
    }

    public function buildHeaderData(): string
    {
        $this->headerIsDumped = false;
        $this->dumpHeader = null;

        $headerData = parent::getDumpHeader();

        $this->headerIsDumped = true;
        $this->dumpHeader = '';

        return $headerData;
    }
}
