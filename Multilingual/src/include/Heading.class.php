<?php

/**
 * Multilingual Markdown generator - Heading class
 *
 * Copyright 2020 Francis Piérot
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files
 * (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
 * OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package   mlmd_heading_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    mb_internal_encoding('UTF-8');

    require_once 'OutputModes.class.php';

    /**
     * Heading class, used by $headings array for all headings from all files.
     */
    class Heading
    {
        private $number = 0;    /// unique number over all files and headings
        private $text = '';     /// heading text, including MLMD directives if needed
        private $level = 0;     /// heading level = number of '#'s
        private $line = 0;     /// line number in source file
        private $index = 0;     /// index in host HeadingArray

        private static $curNumber = 0;  /// current value for next $number
        private static $prevLevel = 0;  /// minimalistic security check - assumes headings are created following the text order

        /**
         * Build a heading with a source text and a line number in a file.
         * Line number is used by caller to check if it's processing the same heading.
         * Level is used by Numbering to check against scheme and compute numbering prefix.
         *
         * @param string $text   the source text for heading, including the '#' prefix.
         * @param int    $line   the line number in the source file.
         * @param object $logger the caller object with a logging function called error()
         */
        public function __construct(string $text, int $line, ?object $logger)
        {
            // sequential number for all headers of all files
            self::$curNumber += 1;
            $this->number = self::$curNumber;
            // count number of '#' = heading level
            $this->level = self::getLevelFromText($text);
            if ($this->level > self::$prevLevel + 1) {
                if ($logger) {
                    $logger->error("level {$this->level} heading skipped one or more heading levels");
                }
            }
            $this->line = $line;
            $this->text = trim(mb_substr($text, $this->level, null));
            self::$prevLevel = $this->level;
        }

        public function __toString()
        {
            return "#[$this->level]:$this->text";
        }

        /**
         * Resets the global (unique) number to 0.
         */
        public static function init(): void
        {
            Heading::$curNumber = 0;
        }

        /**
         * Global number of all headings accessor.
         */
        public function getNumber(): int
        {
            return $this->number;
        }

        /**
         * Text accessor.
         * The heading text doesn't include the '#' prefix.
         */
        public function getText(): string
        {
            return $this->text;
        }

        /**
         * Level accessor.
         */
        public function getLevel(): int
        {
            return $this->level;
        }

        /**
         * Check Level limits.
         */
        public function isLevelWithin(object $numbering): bool
        {
            return ($this->level <= $numbering->getEnd() && $this->level >= $numbering->getStart());
        }
        
        /**
         * Line accessor.
         */
        public function getLine(): int
        {
            return $this->line;
        }

        /**
         * Index in host HeadingArray modifier.
         */
        public function setIndex(int $index): void
        {
            $this->index = $index;
        }

        /**
         * Index in host HeadingArray accessor.
         */
        public function getIndex(): int
        {
            return $this->index;
        }

        /**
         * Compute heading level from the starting '#'s.
         * Static function, call as Heading::getLevelFromText(string)
         * also on instances like $heading->getLevelFromText(string)
         *
         * @param string $content the text with '#'s from which to compute heading level.
         *
         * @return int the heading level
         */
        public static function getLevelFromText(string $content): int
        {
            $text = trim($content);
            $level = 0;
            $length = mb_strlen($text);
            while (mb_substr($text, $level, 1) == '#' && $level < $length) {
                $level += 1;
            }
            return $level;
        }
    }
}
