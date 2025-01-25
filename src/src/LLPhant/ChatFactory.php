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

namespace Waldhacker\Pseudify\Core\LLPhant;

use LLPhant\Chat\OllamaChat;
use LLPhant\OllamaConfig;

/**
 * @internal
 */
class ChatFactory
{
    public function __invoke(
        ?string $apiUrl,
        ?string $model,
        ?int $modelContextLength,
    ): OllamaChat {
        $config = new OllamaConfig();
        $config->model = $model ?? 'llama3.1';
        $config->url = $apiUrl ?? '';
        $config->modelOptions = [
            'options' => [
                'num_ctx' => $modelContextLength ?? 32768,
            ],
        ];

        return new OllamaChat($config);
    }
}
