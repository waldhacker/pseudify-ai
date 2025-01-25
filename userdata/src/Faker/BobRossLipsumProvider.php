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

namespace Waldhacker\Pseudify\Faker;

use Faker\Provider\Base;
use Waldhacker\Pseudify\Core\Faker\FakeDataProviderInterface;

/**
 * Example to show how custom faker data formatters can be implemented.
 * This file is basically a copy of the 'Lorem' formatter (https://github.com/FakerPHP/Faker/blob/v1.20.0/src/Faker/Provider/Lorem.php)
 * with Bob Ross sentences.
 */
final class BobRossLipsumProvider extends Base implements FakeDataProviderInterface
{
    private static array $wordList = [
        'don\'t', 'fiddle', 'with', 'it', 'all', 'day',
        'let\'s', 'have', 'a', 'nice', 'tree', 'right', 'here',
        'anyone', 'can', 'paint.',
        'if', 'there\'s', 'two', 'big', 'trees', 'invariably', 'sooner', 'or', 'later', 'there\'s', 'gonna', 'be', 'a', 'little', 'tree',
        'with', 'practice', 'comes', 'confidence',
        'we', 'wash', 'our', 'brush', 'with', 'odorless', 'thinner',
        'that\'s', 'what', 'painting', 'is', 'all', 'about',
        'it', 'should', 'make', 'you', 'feel', 'good', 'when', 'you', 'paint',
        'just', 'pretend', 'you', 'are', 'a', 'whisper', 'floating', 'across', 'a', 'mountain',
        'tree', 'trunks', 'grow', 'however', 'makes', 'them', 'happy',
        'automatically,', 'all', 'of', 'these', 'beautiful,', 'beautiful', 'things', 'will', 'happen',
        'follow', 'the', 'lay', 'of', 'the', 'land',
        'it\'s', 'most', 'important',
        'don\'t', 'kill', 'all', 'your', 'dark', 'areas', 'you', 'need', 'them', 'to', 'show', 'the', 'light',
        'put', 'light', 'against', 'light', 'you', 'have', 'nothing',
        'put', 'dark', 'against', 'dark', 'you', 'have', 'nothing',
        'it\'s', 'the', 'contrast', 'of', 'light', 'and', 'dark', 'that', 'each', 'give', 'the', 'other', 'one', 'meaning',
        'just', 'relax', 'and', 'let', 'it', 'flow',
        'absolutely', 'no', 'pressure',
        'you', 'are', 'just', 'a', 'whisper', 'floating', 'across', 'a', 'mountain',
        'there\'s', 'nothing', 'wrong', 'with', 'having', 'a', 'tree', 'as', 'a', 'friend.',
        'now', 'then,', 'let\'s', 'play',
        'isn\'t', 'it', 'fantastic', 'that', 'you', 'can', 'change', 'your', 'mind', 'and', 'create', 'all', 'these', 'happy', 'things',
        'just', 'think', 'about', 'these', 'things', 'in', 'your', 'mind', 'and', 'drop', 'em', 'on', 'canvas',
        'you', 'could', 'sit', 'here', 'for', 'weeks', 'with', 'your', 'one', 'hair', 'brush', 'trying', 'to', 'do', 'that', 'or', 'you', 'could', 'do', 'it', 'with', 'one', 'stroke', 'with', 'an', 'almighty', 'brush',
        'you', 'are', 'only', 'limited', 'by', 'your', 'imagination',
        'let\'s', 'make', 'some', 'happy', 'little', 'clouds', 'in', 'our', 'world',
        'let\'s', 'go', 'up', 'in', 'here,', 'and', 'start', 'having', 'some', 'fun',
        'maybe', 'there\'s', 'a', 'happy', 'little', 'waterfall', 'happening', 'over', 'here',
        'now,', 'we\'re', 'going', 'to', 'fluff', 'this', 'cloud',
        'this', 'is', 'a', 'happy', 'place,', 'little', 'squirrels', 'live', 'here', 'and', 'play',
        'here\'s', 'something', 'that\'s', 'fun',
        'i', 'think', 'there\'s', 'an', 'artist', 'hidden', 'in', 'the', 'bottom', 'of', 'every', 'single', 'one', 'of', 'us',
        'we', 'need', 'dark', 'in', 'order', 'to', 'show', 'light',
        'you', 'can', 'get', 'away', 'with', 'a', 'lot',
    ];

    /**
     * @api
     */
    public static function bobRossWord(): string
    {
        return static::randomElement(static::$wordList);
    }

    /**
     * @api
     */
    public static function bobRossWords(int $nb = 3, bool $asText = false): array|string
    {
        $words = [];

        for ($i = 0; $i < $nb; ++$i) {
            $words[] = static::bobRossWord();
        }

        return $asText ? implode(' ', $words) : $words;
    }

    /**
     * @api
     */
    public static function bobRossSentence(int $nbWords = 6, bool $variableNbWords = true): string
    {
        if ($nbWords <= 0) {
            return '';
        }

        if ($variableNbWords) {
            $nbWords = self::randomizeNbElements($nbWords);
        }

        /** @var array $words */
        $words = static::bobRossWords($nbWords);
        $words[0] = ucwords((string) $words[0]);

        return implode(' ', $words).'.';
    }

    /**
     * @api
     */
    public static function bobRossSentences(int $nb = 3, bool $asText = false): array|string
    {
        $sentences = [];

        for ($i = 0; $i < $nb; ++$i) {
            $sentences[] = static::bobRossSentence();
        }

        return $asText ? implode(' ', $sentences) : $sentences;
    }

    /**
     * @api
     */
    public static function bobRossParagraph(int $nbSentences = 3, bool $variableNbSentences = true): string
    {
        if ($nbSentences <= 0) {
            return '';
        }

        if ($variableNbSentences) {
            $nbSentences = self::randomizeNbElements($nbSentences);
        }

        /** @var array $sentences */
        $sentences = static::bobRossSentences($nbSentences);

        return implode(' ', $sentences);
    }

    /**
     * @api
     */
    public static function bobRossParagraphs(int $nb = 3, bool $asText = false): array|string
    {
        $paragraphs = [];

        for ($i = 0; $i < $nb; ++$i) {
            $paragraphs[] = static::bobRossParagraph();
        }

        return $asText ? implode("\n\n", $paragraphs) : $paragraphs;
    }

    /**
     * @api
     */
    public static function bobRossText(int $maxNbChars = 200): string
    {
        if ($maxNbChars < 5) {
            throw new \InvalidArgumentException('text() can only generate text of at least 5 characters');
        }

        $type = ($maxNbChars < 25) ? 'bobRossWord' : (($maxNbChars < 100) ? 'bobRossSentence' : 'bobRossParagraph');

        $text = [];

        while (empty($text)) {
            $size = 0;

            // until $maxNbChars is reached
            while ($size < $maxNbChars) {
                $word = ($size ? ' ' : '').static::$type();
                $text[] = $word;

                $size += strlen($word);
            }

            array_pop($text);
        }

        if ('word' === $type) {
            // capitalize first letter
            $text[0] = ucwords($text[0]);

            // end sentence with full stop
            $text[count($text) - 1] .= '.';
        }

        return implode('', $text);
    }

    private static function randomizeNbElements(int $nbElements): int
    {
        return (int) ($nbElements * self::numberBetween(60, 140) / 100) + 1;
    }
}
