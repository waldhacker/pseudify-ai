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
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Column;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Table;

/**
 * @internal
 */
class TableGuesser
{
    public const string PROMT = '
You are part of a expert system that is responsible for recognizing and classifying personally identifiable information (PII) in text data.
You are an expert in classifying software applications based on their database structures.

# Your task:

Your task is to make statements about the typical use and the typical content of an incoming database table.
Then enter a brief description of the typical use and content of the table.
If you cannot find out anything, then return an empty string `null`.
Your output will be used later in an LLM Promt as additional context information.
Formulate your output in such a way that it can be optimally understood by an LLM for further processing.
Be very precise. Double check your results and include all your knowledge and the contextual information provided to you. Do not halucinate. 

# Additional context provided by the user:

You may receive additional context information about the origin of the table.
The context information contains information about the associated software (`Software name` and `Software description`), the table name (`Table name`) and the column names of the table (`Column names` separated by a comma).
This information can help to improve the classification.
Use this information to increase the accuracy of your classification.

# Example:

## Example 1:

User Input:

```
Software name: TYPO3 CMS
Software description: TYPO3 is an open-source content management system (CMS) designed for creating and managing complex websites. It offers extensive flexibility in terms of templates and themes, allowing users to create highly customized web applications.
Table name: tt_content
Column names: uid, pid, CType
```

Your Output:

```
{"table_description": "The `tt_content` table in the TYPO3 Content Management System (CMS) has a central and very specific purpose: it serves as the primary storage medium for all content objects that are managed on TYPO3 websites."}
```

## Example 2:

User Input:

```
Software name: null
Software description: null
Table name: X_XXXXXXXX
Column names: aaa, bbb
```

Your Output:

```
{"table_description": null}
```
    ';

    public function __construct(
        private readonly Chat $chat,
    ) {
    }

    public function guess(Table $profileDefinitionTable, GuesserContext $guesserContext): GuesserContext
    {
        if (!$this->chat->isEnabled()) {
            return $guesserContext;
        }

        $columnNames = array_map(fn (Column $column): string => $column->getIdentifier(), $profileDefinitionTable->getColumns());

        $this->chat->setModelOption('format', [
            'type' => 'object',
            'properties' => [
                'table_description' => [
                    'type' => 'string',
                ],
            ],
            'required' => [
                'table_description',
            ],
        ]);
        $this->chat->setModelOption('options', [
            'temperature' => 0,
            'top_k' => 1,
            'top_p' => 0.1,
        ]);
        $this->chat->setSystemMessage(self::PROMT);

        $response = $this->chat->generateText(implode(PHP_EOL, [
            sprintf('Software name: %s', $guesserContext->applicationName),
            sprintf('Software description: %s', $guesserContext->applicationDescription),
            sprintf('Table name: %s', $guesserContext->tableName),
            sprintf('Column names: %s', implode(', ', $columnNames)),
        ]).PHP_EOL);

        $response = json_decode($response, true);
        if (
            $response
            && !empty($response['table_description'] ?? null)
        ) {
            $guesserContext = $guesserContext->withTableDescription(str_replace(["\r\n", "\r", "\n"], '', $response['table_description']));
        }

        return $guesserContext;
    }
}
