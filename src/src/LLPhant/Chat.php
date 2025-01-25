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

/**
 * @internal
 */
class Chat
{
    /** @var array<string, mixed> */
    private array $modelOptions = [];

    public function __construct(
        private readonly ?string $apiUrl,
        private readonly OllamaChat $chat,
        ?int $modelContextLength,
    ) {
        $this->modelOptions = [
            'options' => [
                'num_ctx' => $modelContextLength ?? 32768,
            ],
        ];
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiUrl);
    }

    public function setModelOption(string $option, mixed $value): void
    {
        if ('options' === $option && is_array($value)) {
            $value = array_replace_recursive($this->modelOptions['options'], $value);
        }

        $this->chat->setModelOption($option, $value);
        $this->modelOptions[$option] = $value;
    }

    /**
     * @param array<array-key, mixed> $arguments
     */
    public function __call(string $functionName, array $arguments = []): mixed
    {
        if (!$this->isEnabled()) {
            throw new ChatUnavailableException('Chat is not enabled', 1736042252);
        }

        return $this->chat->$functionName(...$arguments);
    }
}
