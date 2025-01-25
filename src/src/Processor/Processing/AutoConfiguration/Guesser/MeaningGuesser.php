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

use Symfony\Component\Uid\Uuid;
use Waldhacker\Pseudify\Core\Faker\Faker;
use Waldhacker\Pseudify\Core\LLPhant\Chat;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning;
use Waldhacker\Pseudify\Core\Profile\Yaml\ProfileDefinition\Model\ProfileDefinition\Meaning\Property;

/**
 * @internal
 */
class MeaningGuesser
{
    public const string PROMT = '
# Your task:

You are part of a expert system that is responsible for recognizing and classifying personally identifiable information (PII) in text data.
You are an expert in classifying PII in database datasets.
Your task is to categorize incoming datasets using a static list of PII types.
The static list of PII types represents the PII you need to find.

The static list of PII types is:

```
{{ category_list }}
```

To help you classify the data, you will now receive a list describing each PII type.

```
{{ category_description }}
```

# User input:

The datasets are made available to you in CSV format, separated by commas.

Here is an example of a user provided dataset:

```
Dataset: "john_doe123","stokestyler","homenick.alexandre"
```

## Your detection rules:

- Only recognize PII that is defined in the static list of PII types
- You MUST output the most likely PII type from the static list of PII types
- If the user provided dataset does not match any of these PII types you MUST output `null`
- If you find a PII type that is not defined in the static list of PII types you MUST output `null`
- Be very precise. Double check your results. Do not halucinate. 

## Additional context provided by the user:

You receive additional context information about the origin of the dataset at the start of user input.
The context information contains information about the associated software (`Software name` and `Software description`), information about the table (`Table name` and `Table description`), information about the column (`Column name` and `Column description`) and the technical column type (`Column type`).
Use this information to increase the accuracy of your classification.

## Examples:

### Example 1:

User Input:

```
Software name: TYPO3 CMS
Software description: Some application description
Table name: tt_content
Table description: Some table description
Column name: CType
Column description: Some column description
Column type: VARCHAR(255)
Dataset: "john_doe123","stokestyler","homenick.alexandre"
```

Your Output:

```
{"category": "Username"}
```

### Example 2:

User Input:

```
# Additional context:

Software name: null
Software description: null
Table name: a_aaaaaa
Table description: null
Column name: b_bbbbb
Column description: null
Column type: VARCHAR(255)
Dataset: "42","","3.1415"
```

Your Output:

```
{"category": null}
```

### Example 3:

User Input:

```
# Additional context:

Software name: TYPO3 CMS
Software description: Some application description
Table name: be_users
Table description: Some table description
Column name: password
Column description: Some column description
Column type: VARCHAR(255)
Dataset: "$argon2i$v=19$m=8,t=1,p=1$ZUZJUFhCTi93RHVobi5law$UJoLmTdyJMbHSbZ1Ez0YlZ2HIkAgohXObpktAu3t7Js","$argon2i$v=19$m=8,t=1,p=1$b2FBa04xNHhFOUZnM1AyeA$k9Xam5nMDH+hbzcso8a8I0najmmI9/jDIoyayvS6y+A","$argon2i$v=19$m=8,t=1,p=1$MWdmVXhTRURBTHo3elhLbQ$PH2AlI16/Z62rL6+BABW4FqxKLfemJ6NWq+DavJt+Ts"
```

Your Output:

```
{"category": null}
```
    ';

    public const array FORMAT_UNIQUENESS = [
        'ipv4' => 50,
        'ipv6' => 50,

        'argon2iPassword' => 50,
        'argon2idPassword' => 50,
        'bcryptPassword' => 50,

        'md5' => 50,
        'sha1' => 50,
        'sha256' => 50,

        'safeEmail' => 50,
        'macAddress' => 50,
    ];

    public const string NO_PATH_IDENTIFIER = '__internal__nopath__1736007947__internal__';
    public const int MIN_DATA_LENGTH = 4;

    private string $systemPromt = '';
    /** @var array<string, array{description: string, examples: array<int, mixed>, fakerFormatter: string}> */
    private array $categories = [];

    public function __construct(
        private readonly Chat $chat,
        private readonly Faker $faker,
    ) {
        if (!$this->chat->isEnabled()) {
            return;
        }

        $this->categories = [
            'Username' => [
                'description' => 'A user name is a designation or pseudonym chosen by users to identify themselves on computer systems or online platforms.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->userName(), range(0, 2)),
                'fakerFormatter' => 'userName',
            ],
            'First name' => [
                'description' => 'In many languages, a first name is the first part of a person\'s name. It is used to designate and distinguish people.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->firstName(), range(0, 2)),
                'fakerFormatter' => 'firstName',
            ],
            'Surname' => [
                'description' => 'A family name is the name of a family. In many cultures, the family name is used together with the person\'s first name to uniquely identify them.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->lastName(), range(0, 2)),
                'fakerFormatter' => 'lastName',
            ],
            'Full name' => [
                'description' => 'A full name generally refers to a person\'s entire name, which usually consists of a first name and a surname, but may also include additional elements such as middle names, maiden names, married names or titles of nobility, depending on the cultural background.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->name(), range(0, 2)),
                'fakerFormatter' => 'name',
            ],
            'City name' => [
                'description' => 'A city name is the name of a real city. A city name consists of letters, not numbers. Examples are Berlin, Munich, Hamburg or Frankfurt am Main.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->city(), range(0, 2)),
                'fakerFormatter' => 'city',
            ],
            'Country name' => [
                'description' => 'A country name is the name of a real state or nation. A country name consists of letters, not numbers. Examples of country names are Germany, France and Italy.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->country(), range(0, 2)),
                'fakerFormatter' => 'country',
            ],
            'Browser user agent identifier' => [
                'description' => 'A user agent string (abbreviation: UA string) is a character string that is transmitted from a web browser or other software agent to a server in order to identify the client. The UA string contains information about the manufacturer of the browser, the version and the platform (e.g. Windows or macOS).',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->userAgent(), range(0, 2)),
                'fakerFormatter' => 'userAgent',
            ],
            'Email address' => [
                'description' => 'An email address, is a unique identifier assigned to an individual or organization to send, receive, and store electronic messages.',
                'examples' => array_map(fn (int $i): string => $this->faker->withSource(random_bytes(4))->email(), range(0, 2)),
                'fakerFormatter' => 'userName',
            ],
        ];

        $categoryList = [];
        foreach ($this->categories as $categoryName => $categoryData) {
            $categoryList['list'][] = sprintf('- %s', $categoryName);
            $categoryList['description'][] = sprintf('- %s: %s Examples: %s', $categoryName, $categoryData['description'], '`'.implode('`,`', $categoryData['examples']).'`');
        }

        $this->systemPromt = str_replace(
            [
                '{{ category_list }}',
                '{{ category_description }}',
            ],
            [
                implode(PHP_EOL, $categoryList['list']),
                implode(PHP_EOL, $categoryList['description']),
            ],
            self::PROMT
        );
    }

    /**
     * @param array<array-key, array{original: array<string, mixed>, decoded: mixed, paths: array<string, mixed>}> $dataSet
     *
     * @return Meaning[]
     */
    public function guess(
        array $dataSet,
        GuesserContext $guesserContext,
        int $minimumDataSetCount = 1,
    ): array {
        $dataSet = $this->transposeData($dataSet);

        if ($this->chat->isEnabled()) {
            $this->chat->setModelOption('format', [
                'type' => 'object',
                'properties' => [
                    'category' => [
                        'type' => 'string',
                    ],
                ],
                'required' => [
                    'category',
                ],
            ]);
            $this->chat->setModelOption('options', [
                'temperature' => 0,
                'top_k' => 1,
                'top_p' => 0.1,
            ]);
            $this->chat->setSystemMessage($this->systemPromt);
        }

        $meanings = [];
        foreach ($dataSet as $index => $data) {
            $path = $data['path'] ?? '';
            $items = array_unique(array_filter(
                $data['data'] ?? [],
                fn (mixed $data): bool => is_string($data) && !empty($data) && !$this->looksLikeBinary($data)
            ));

            if (count($items) < $minimumDataSetCount) {
                continue;
            }

            $meaning = $this->guessByLookAlike($items, $path);
            if ($meaning) {
                $meanings[] = $meaning;
            } else {
                try {
                    $meaning = $this->guessByLlm($items, $path, $guesserContext);
                } catch (\Throwable) {
                }

                if ($meaning) {
                    $meanings[] = $meaning;
                }
            }
        }

        return $meanings;
    }

    /**
     * @param array<array-key, string> $items
     */
    private function guessByLlm(array $items, string $path, GuesserContext $guesserContext): ?Meaning
    {
        if (!$this->chat->isEnabled()) {
            return null;
        }

        $itemList = [];
        foreach ($items as $item) {
            $item = str_replace(["\r\n", "\r", "\n"], ' ', $item);
            if (mb_strlen($item) > 300) {
                continue;
            }

            $itemList[] = $item;
        }

        if (empty($itemList)) {
            return null;
        }

        $fd = fopen('php://memory', 'rw');
        fputcsv($fd, $itemList);
        $itemList = stream_get_contents($fd, offset: 0);
        fclose($fd);

        $userPromt = implode(PHP_EOL, [
            sprintf('Software name: %s', $guesserContext->applicationName),
            sprintf('Software description: %s', $guesserContext->applicationDescription),
            sprintf('Table name: %s', $guesserContext->tableName),
            sprintf('Table description: %s', $guesserContext->tableDescription),
            sprintf('Column name: %s', $guesserContext->columnName),
            sprintf('Column description: %s', $guesserContext->columnDescription),
            sprintf('Column type: %s', $guesserContext->columnType),
            sprintf('Dataset: %s', $itemList),
        ]).PHP_EOL;

        // @todo shrink or log if the promt does not fit the context length
        // @waitfor https://github.com/ollama/ollama/issues/3582
        $response = $this->chat->generateText($userPromt);

        $response = json_decode($response, true);
        if ($response && in_array($response['category'] ?? null, array_keys($this->categories))) {
            $possibleMeaningType = $this->categories[$response['category']]['fakerFormatter'];

            return new Meaning((string) Uuid::v4(), new Property($path, '', $possibleMeaningType, 3), $response['category']);
        }

        return null;
    }

    /**
     * @param array<array-key, string> $dataSet
     */
    private function guessByLookAlike(array $dataSet, string $path): ?Meaning
    {
        $dataSetCount = count($dataSet);
        $possibleMeaningTypes = [
            'ipv4' => 0,
            'ipv6' => 0,
            'argon2iPassword' => 0,
            'argon2idPassword' => 0,
            'bcryptPassword' => 0,
            // 'md5' => 0,
            // 'sha1' => 0,
            // 'sha256' => 0,
            'safeEmail' => 0,
            'macAddress' => 0,
        ];

        foreach ($dataSet as $data) {
            if ($this->looksLikeIpv4($data)) {
                ++$possibleMeaningTypes['ipv4'];
            }
            if ($this->looksLikeIpv6($data)) {
                ++$possibleMeaningTypes['ipv6'];
            }
            if ($this->looksLikeArgon2iPassword($data)) {
                ++$possibleMeaningTypes['argon2iPassword'];
            }
            if ($this->looksLikeArgon2idPassword($data)) {
                ++$possibleMeaningTypes['argon2idPassword'];
            }
            if ($this->looksLikeBcryptPassword($data)) {
                ++$possibleMeaningTypes['bcryptPassword'];
            }
            // if ($this->looksLikeMd5Hash($data)) {
            //     ++$possibleMeaningTypes['md5'];
            // }
            // if ($this->looksLikeSha1Hash($data)) {
            //     ++$possibleMeaningTypes['sha1'];
            // }
            // if ($this->looksLikeSha256Hash($data)) {
            //     ++$possibleMeaningTypes['sha256'];
            // }
            if ($this->looksLikeEmailAddress($data)) {
                ++$possibleMeaningTypes['safeEmail'];
            }
            if ($this->looksLikeMacAddress($data)) {
                ++$possibleMeaningTypes['macAddress'];
            }
        }

        foreach ($possibleMeaningTypes as $meaningType => $numberOfLookAlikeItems) {
            $successRate = 100 / $dataSetCount * $numberOfLookAlikeItems;
            if ($successRate < 50.0) {
                unset($possibleMeaningTypes[$meaningType]);
            }
        }

        if (empty($possibleMeaningTypes)) {
            return null;
        }

        uksort($possibleMeaningTypes, fn (string $meaningTypeA, string $meaningTypeB): int => (self::FORMAT_UNIQUENESS[$meaningTypeB] ?? 0) <=> (self::FORMAT_UNIQUENESS[$meaningTypeA] ?? 0));

        $possibleMeaningTypes = array_keys($possibleMeaningTypes);
        $possibleMeaningType = array_shift($possibleMeaningTypes);
        if (null === $possibleMeaningType) {
            return null;
        }

        return new Meaning((string) Uuid::v4(), new Property($path, '', $possibleMeaningType, 3));
    }

    private function looksLikeIpv4(string $data): bool
    {
        return false !== filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    private function looksLikeIpv6(string $data): bool
    {
        return false !== filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    private function looksLikeArgon2iPassword(string $data): bool
    {
        return 'argon2i' === password_get_info($data)['algoName'];
    }

    private function looksLikeArgon2idPassword(string $data): bool
    {
        return 'argon2id' === password_get_info($data)['algoName'];
    }

    private function looksLikeBcryptPassword(string $data): bool
    {
        return 'bcrypt' === password_get_info($data)['algoName'];
    }

    private function looksLikeMd5Hash(string $data): bool
    {
        preg_match('/^[0-9a-f]{32}$/is', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeSha1Hash(string $data): bool
    {
        preg_match('/^[0-9a-f]{40}$/is', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeSha256Hash(string $data): bool
    {
        preg_match('/^[0-9a-f]{64}$/is', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeEmailAddress(string $data): bool
    {
        return false !== filter_var($data, FILTER_VALIDATE_EMAIL);
    }

    private function looksLikeMacAddress(string $data): bool
    {
        return false !== filter_var($data, FILTER_VALIDATE_MAC);
    }

    private function looksLikeBinary(string $data): bool
    {
        return false === mb_detect_encoding($data, null, true);
    }

    /**
     * @param array<array-key, array{original: array<string, mixed>, decoded: mixed, paths: array<string, mixed>}> $dataSet
     *
     * @return array<int, array{path: string, data: array<array-key, mixed>, meaning: null}>
     */
    private function transposeData(array $dataSet): array
    {
        $transposedData = [];
        foreach ($dataSet as $data) {
            if (is_scalar($data['decoded'] ?? null)) {
                $data['paths'] = [self::NO_PATH_IDENTIFIER => $data['decoded']];
            }

            foreach ($data['paths'] ?? [] as $path => $pathData) {
                if (!is_string($pathData) || mb_strlen($pathData) < self::MIN_DATA_LENGTH) {
                    continue;
                }

                $transposedData[$path] ??= ['path' => self::NO_PATH_IDENTIFIER === $path ? '' : $path, 'data' => [], 'meaning' => null];
                $transposedData[$path]['data'][] = $pathData;
            }
        }

        return array_values($transposedData);
    }
}
