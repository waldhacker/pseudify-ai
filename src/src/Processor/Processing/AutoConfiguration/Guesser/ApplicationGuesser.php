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

namespace Waldhacker\Pseudify\Core\Processor\Processing\AutoConfiguration\Guesser;

use Waldhacker\Pseudify\Core\LLPhant\Chat;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition;

/**
 * @internal
 */
class ApplicationGuesser
{
    public const string PROMT = '
You are part of a expert system that is responsible for recognizing and classifying personally identifiable information (PII) in text data.
You are an expert in classifying software applications based on their database structures.

# Your task:

Your task is to classify incoming database tables and make a statement about which software system they belong to.
You then name the software system and give a brief description of the software.
If several software systems could apply, select the most likely system.
If you cannot find out anything, then return `null`.
Your output will be used later in an LLM Promt as additional context information.
Formulate your output in such a way that it can be optimally understood by an LLM for further processing.
Be very precise. Double check your results and include all your knowledge and the contextual information provided to you. Do not halucinate. 

# Example:

## Example 1:

User Input:

```
1. sales_channel_type
2. order_transaction_capture_refund
3. swag_paypal_pos_sales_channel_run
```

Your Output:

```
{"application": "Shopware", "description": "Shopware is an open source commerce platform that enables companies to create and manage online stores."}
```

## Example 2:

User Input:

```
1. a_aaaaaaaaaa
2. b_bbbbbbbbbb
3. c_cccccccccc
```

Your Output:

```
{"application": null, "description": null}
```
    ';

    public function __construct(
        private readonly Chat $chat,
    ) {
    }

    public function guess(
        ProfileDefinition $profileDefinition,
        GuesserContext $guesserContext,
    ): GuesserContext {
        if (!$this->chat->isEnabled()) {
            return $guesserContext;
        }

        $reducedTableNames = $profileDefinition->getTableNames();
        if (count($reducedTableNames) > 300) {
            $reducedTableNames = array_rand(array_flip($reducedTableNames), 300);
        }

        $tableNames = [];
        $i = 1;
        foreach ($reducedTableNames as $tableName) {
            $tableNames[] = sprintf('%s. %s', $i, $tableName);
            ++$i;
        }

        $this->chat->setModelOption('format', [
            'type' => 'object',
            'properties' => [
                'application' => [
                    'type' => 'string',
                ],
                'description' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'application',
                'description',
            ],
        ]);
        $this->chat->setModelOption('options', [
            'temperature' => 0,
            'top_k' => 1,
            'top_p' => 0.1,
        ]);
        $this->chat->setSystemMessage(self::PROMT);

        $response = $this->chat->generateText(implode(PHP_EOL, $tableNames).PHP_EOL);
        $response = json_decode($response, true);
        if (
            $response
            && !empty($response['application'] ?? null)
            && !empty($response['description'] ?? null)
        ) {
            $guesserContext = $guesserContext->withApplicationName(str_replace(["\r\n", "\r", "\n"], '', $response['application']));
            $guesserContext = $guesserContext->withApplicationDescription(str_replace(["\r\n", "\r", "\n"], '', $response['description']));
        }

        return $guesserContext;
    }
}
