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
use Symfony\Component\Yaml\Yaml;
use Waldhacker\Pseudify\Core\Processor\Encoder\AdvancedEncoderCollection;
use Waldhacker\Pseudify\Core\Processor\Encoder\Base64Encoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\CsvEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\EncoderInterface;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzCompressEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzDeflateEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\GzEncodeEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\HexEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\JsonEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\SerializedEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\TYPO3\FlexformEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\XmlEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\YamlEncoder;
use Waldhacker\Pseudify\Core\Processor\Encoder\ZlibEncodeEncoder;

/**
 * @internal
 */
class EncodingsGuesser
{
    public const array BUILTIN_ENCODER_CLASSNAMES = [
        Base64Encoder::class,
        CsvEncoder::class,
        GzCompressEncoder::class,
        GzDeflateEncoder::class,
        GzEncodeEncoder::class,
        HexEncoder::class,
        JsonEncoder::class,
        SerializedEncoder::class,
        XmlEncoder::class,
        YamlEncoder::class,
        ZlibEncodeEncoder::class,
        FlexformEncoder::class,
    ];

    public const array FORMAT_UNIQUENESS = [
        JsonEncoder::class => 60,
        SerializedEncoder::class => 60,
        FlexformEncoder::class => 60,

        YamlEncoder::class => 50,
        XmlEncoder::class => 50,

        CsvEncoder::class => 40,

        HexEncoder::class => 30,

        Base64Encoder::class => 20,

        GzCompressEncoder::class => 10,
        GzDeflateEncoder::class => 10,
        GzEncodeEncoder::class => 10,
        ZlibEncodeEncoder::class => 10,
    ];

    /** @var array<string, EncoderInterface> */
    private array $encoders = [];

    public function __construct(
        AdvancedEncoderCollection $encoderCollection,
    ) {
        $this->encoders = array_filter($encoderCollection->getEncoders(), fn (EncoderInterface $encoder): bool => in_array($encoder::class, self::BUILTIN_ENCODER_CLASSNAMES));
    }

    /**
     * @param array<array-key, mixed> $dataSet
     *
     * @return array<string, array{encoder: EncoderInterface, context: array<string, mixed>, successRate: float}>
     */
    public function guess(
        array $dataSet,
        GuesserContext $guesserContext,
        int $minimumDataSetCount = 1,
    ): array {
        $dataSet = array_filter($dataSet, fn (mixed $data): bool => is_string($data) && !empty($data));

        $dataSetCount = count($dataSet);
        if ($dataSetCount < $minimumDataSetCount) {
            return [];
        }

        $possibleEncodersByDecodeSuccessRate = $this->guessByDecodeSuccessRate($dataSet);
        $possibleEncodersByLookAlike = $this->guessByLookAlike($dataSet);

        $possibleEncoders = [];
        foreach ($possibleEncodersByDecodeSuccessRate as $encoderClassName => $encoderData) {
            if (in_array($encoderClassName, $possibleEncodersByLookAlike)) {
                $possibleEncoders[$encoderClassName] = $encoderData;
            }
        }

        return $possibleEncoders;
    }

    /**
     * @param array<array-key, mixed> $dataSet
     *
     * @return string[]
     */
    private function guessByLookAlike(array $dataSet): array
    {
        $dataSetCount = count($dataSet);
        $possibleEncoderClassNames = [
            Base64Encoder::class => 0,
            CsvEncoder::class => 0,
            GzCompressEncoder::class => 0,
            GzDeflateEncoder::class => 0,
            GzEncodeEncoder::class => 0,
            HexEncoder::class => 0,
            JsonEncoder::class => 0,
            SerializedEncoder::class => 0,
            XmlEncoder::class => 0,
            YamlEncoder::class => 0,
            ZlibEncodeEncoder::class => 0,
            FlexformEncoder::class => 0,
        ];

        foreach ($dataSet as $data) {
            if ($this->looksLikeNonScalarJson($data)) {
                ++$possibleEncoderClassNames[JsonEncoder::class];
            }
            if ($this->looksLikeSerialized($data)) {
                ++$possibleEncoderClassNames[SerializedEncoder::class];
            }
            if ($this->looksLikeFlexform($data)) {
                ++$possibleEncoderClassNames[FlexformEncoder::class];
            }

            if ($this->looksLikeYaml($data)) {
                ++$possibleEncoderClassNames[YamlEncoder::class];
            }
            if ($this->looksLikeXml($data)) {
                ++$possibleEncoderClassNames[XmlEncoder::class];
            }

            if ($this->looksLikeCsv($data)) {
                ++$possibleEncoderClassNames[CsvEncoder::class];
            }

            if ($this->looksLikeHex($data)) {
                ++$possibleEncoderClassNames[HexEncoder::class];
            }

            if ($this->looksLikeBase64($data)) {
                ++$possibleEncoderClassNames[Base64Encoder::class];
            }

            if ($this->looksLikeBinary($data)) {
                ++$possibleEncoderClassNames[GzCompressEncoder::class];
                ++$possibleEncoderClassNames[GzDeflateEncoder::class];
                ++$possibleEncoderClassNames[GzEncodeEncoder::class];
                ++$possibleEncoderClassNames[ZlibEncodeEncoder::class];
            }
        }

        foreach ($possibleEncoderClassNames as $encoderClassName => $numberOfLookAlikeItems) {
            $successRate = 100 / $dataSetCount * $numberOfLookAlikeItems;
            if ($successRate < 25.0) {
                unset($possibleEncoderClassNames[$encoderClassName]);
            }
        }

        uksort($possibleEncoderClassNames, fn (string $encoderClassNameA, string $encoderClassNameB): int => (self::FORMAT_UNIQUENESS[$encoderClassNameB] ?? 0) <=> (self::FORMAT_UNIQUENESS[$encoderClassNameA] ?? 0));

        return array_keys($possibleEncoderClassNames);
    }

    /**
     * @param array<array-key, mixed> $dataSet
     *
     * @return array<string, array{encoder: EncoderInterface, context: array<string, mixed>, successRate: float}>
     */
    private function guessByDecodeSuccessRate(array $dataSet): array
    {
        $dataSetCount = count($dataSet);
        $possibleEncoders = [];
        foreach ($this->encoders as $encoder) {
            $contextPermutations = $this->getContextPermutations($encoder);
            foreach ($contextPermutations as $permutationIdentifier => $context) {
                $numberOfDecodedItems = 0;
                foreach ($dataSet as $data) {
                    if ($encoder->canDecode($data, $context)) {
                        ++$numberOfDecodedItems;
                    }
                }

                $successRate = 100 / $dataSetCount * $numberOfDecodedItems;
                if ($successRate >= 75.0) {
                    $possibleEncoders[$encoder::class] = [
                        'encoder' => $encoder,
                        'context' => $context,
                        'successRate' => $successRate,
                    ];

                    break;
                }
            }
        }

        uksort($possibleEncoders, fn (string $encoderClassNameA, string $encoderClassNameB): int => (self::FORMAT_UNIQUENESS[$encoderClassNameB] ?? 0) <=> (self::FORMAT_UNIQUENESS[$encoderClassNameA] ?? 0));

        return $possibleEncoders;
    }

    private function looksLikeNonScalarJson(string $data): bool
    {
        preg_match('/
            (?(DEFINE)
                (?<ws>      [\t\n\r ]* )
                (?<number>  -? (?: 0|[1-9]\d*) (?: \.\d+)? (?: [Ee] [+-]? \d++)? )    
                (?<boolean> true | false | null )
                (?<string>  " (?: [^\\\\"\x00-\x1f] | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9A-Fa-f]{4} )* " )
                (?<pair>    (?&ws) (?&string) (?&ws) : (?&value) )
                (?<array>   \[ (?: (?&value) (?: , (?&value) )* )? (?&ws) \] )
                (?<object>  \{ (?: (?&pair) (?: , (?&pair) )* )? (?&ws) \} )
                (?<value>   (?&ws) (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) (?&ws) )
            )
            \A (?&value) \Z
            /sx',
            $data,
            $matches
        );

        return !empty($matches)
                && (
                    (str_starts_with($data, '{') && str_ends_with($data, '}'))
                    || (str_starts_with($data, '[') && str_ends_with($data, ']'))
                )
        ;
    }

    private function looksLikeSerialized(string $data): bool
    {
        preg_match('/^(N|b:(\d+)|i:(\d+)|d:(\d+)|s:(\d+)|a:(\d+)|O:(\d+)|C:(\d+)|r:(\d+)|R:(\d+))(.*)(;|})$/s', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeFlexform(string $data): bool
    {
        preg_match('/<T3FlexForms>/s', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeXml(string $data): bool
    {
        preg_match('/^<\?xml /s', $data, $matches);

        return !empty($matches);
    }

    private function looksLikeYaml(string $data): bool
    {
        return !$this->looksLikeSerialized($data)
                && !$this->looksLikeFlexform($data)
                && !$this->looksLikeXml($data)
                && !$this->looksLikeHex($data)
                && !$this->looksLikeBase64($data)
                && !$this->looksLikeBinary($data)
        ;
    }

    private function looksLikeCsv(string $data): bool
    {
        return !$this->looksLikeBinary($data) && (
            count(explode(',', $data)) > 1
                   || count(explode(';', $data)) > 1
                   || count(explode("\t", $data)) > 1
        )
        ;
    }

    private function looksLikeHex(string $data): bool
    {
        preg_match('/^[a-f0-9]+$/is', $data, $matches);

        return !empty($matches)
               // maybe md5
               && 32 !== strlen($data)
               // maybe sha1
               && 40 !== strlen($data)
               // maybe sha256
               && 64 !== strlen($data)
        ;
    }

    private function looksLikeBase64(string $data): bool
    {
        preg_match('#^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$#', str_replace([' ', "\n", "\r", "\n\r"], '', $data), $matches);

        return !empty($matches)
               // maybe md5
               && 32 !== strlen($data)
               // maybe sha1
               && 40 !== strlen($data)
               // maybe sha256
               && 64 !== strlen($data)
        ;
    }

    private function looksLikeBinary(string $data): bool
    {
        return false === mb_detect_encoding($data, null, true);
    }

    /**
     * @return array<array-key, array<array-key, mixed>>
     */
    private function getContextPermutations(EncoderInterface $encoder): array
    {
        // @todo: make it possible for user defined encoders to provide context permutations
        return match ($encoder::class) {
            Base64Encoder::class => $this->buildContextCombinations([
                Base64Encoder::DECODE_STRICT => [true],
            ]),

            CsvEncoder::class => $this->buildContextCombinations([
                CsvEncoder::DELIMITER_KEY => [',', ';', "\t"],
                CsvEncoder::ENCLOSURE_KEY => ['"', "'"],
            ]),

            FlexformEncoder::class => $this->buildContextCombinations([
                FlexformEncoder::LOAD_OPTIONS => $this->buildBitmaskCombinations([\LIBXML_NONET | \LIBXML_NOBLANKS, \LIBXML_NOERROR, \LIBXML_NOWARNING]),
            ]),

            GzCompressEncoder::class => $this->buildContextCombinations([
                GzCompressEncoder::ENCODE_ENCODING => [\ZLIB_ENCODING_RAW, \ZLIB_ENCODING_DEFLATE, \ZLIB_ENCODING_GZIP],
                GzCompressEncoder::ENCODE_LEVEL => [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            ]),

            GzDeflateEncoder::class => $this->buildContextCombinations([
                GzDeflateEncoder::ENCODE_ENCODING => [\ZLIB_ENCODING_RAW, \ZLIB_ENCODING_DEFLATE, \ZLIB_ENCODING_GZIP],
                GzDeflateEncoder::ENCODE_LEVEL => [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            ]),

            GzEncodeEncoder::class => $this->buildContextCombinations([
                GzEncodeEncoder::ENCODE_ENCODING => [\FORCE_GZIP, \FORCE_DEFLATE],
                GzEncodeEncoder::ENCODE_LEVEL => [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            ]),

            HexEncoder::class => [[]],

            JsonEncoder::class => $this->buildContextCombinations([
                JsonEncoder::DECODE_OPTIONS => $this->buildBitmaskCombinations([0, \JSON_INVALID_UTF8_IGNORE, \JSON_INVALID_UTF8_SUBSTITUTE]),
            ]),

            SerializedEncoder::class => [[]],

            XmlEncoder::class => $this->buildContextCombinations([
                XmlEncoder::LOAD_OPTIONS => $this->buildBitmaskCombinations([\LIBXML_NONET | \LIBXML_NOBLANKS, \LIBXML_NOERROR, \LIBXML_NOWARNING]),
            ]),

            YamlEncoder::class => $this->buildContextCombinations([
                YamlEncoder::YAML_FLAGS => $this->buildBitmaskCombinations([0, Yaml::PARSE_CUSTOM_TAGS, Yaml::PARSE_OBJECT_FOR_MAP]),
            ]),

            ZlibEncodeEncoder::class => $this->buildContextCombinations([
                ZlibEncodeEncoder::ENCODE_ENCODING => [\ZLIB_ENCODING_RAW, \ZLIB_ENCODING_DEFLATE, \ZLIB_ENCODING_GZIP],
                ZlibEncodeEncoder::ENCODE_LEVEL => [-1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            ]),

            default => [],
        };
    }

    /**
     * @param array<array-key, array<int, mixed>> $contextValues
     *
     * @return array<array-key, array<array-key, mixed>>
     */
    private function buildContextCombinations(array $contextValues): array
    {
        $result = [[]];
        foreach ($contextValues as $property => $values) {
            $tempResult = [];
            foreach ($result as $item) {
                foreach ($values as $value) {
                    $tempResult[] = array_merge($item, [$property => $value]);
                }
            }

            $result = $tempResult;
        }

        $uniqueResult = array_intersect_key($result, array_unique(array_map('serialize', $result)));

        $contextCombinations = [];
        foreach ($uniqueResult as $item) {
            $contextCombinations[(string) Uuid::v4()] = $item;
        }

        return $contextCombinations;
    }

    /**
     * @param array<array-key, int> $bitmaskOptions
     *
     * @return int[]
     */
    private function buildBitmaskCombinations(array $bitmaskOptions): array
    {
        $optionsCount = count($bitmaskOptions);
        $combinationCount = 2 ** $optionsCount;

        $optionsCombinations = [];
        for ($i = 0; $i < $combinationCount; ++$i) {
            $computedOption = 0;
            for ($j = 0; $j < $optionsCount; ++$j) {
                if (2 ** $j & $i) {
                    $computedOption |= $bitmaskOptions[$j];
                }
            }

            $optionsCombinations[] = $computedOption;
        }

        return $optionsCombinations;
    }
}
