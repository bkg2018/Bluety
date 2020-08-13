<?php
declare(strict_types=1);
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

namespace MultilingualMarkdown {
    /**
     * Heading class, used by $headings array for all headings from all files.
     */
    class Heading
    {
        public $number = 0;     /// unique number over all files and headings
        public $text = '';      /// heading text, including MLMD directives if needed
        public $level = 0;      /// heading level = number of '#'s
        public $line = '';      /// line number in source file
        public $prefix = '';    /// heading prefix in TOC and text, computed
                                /// from 'numbering' directive or toc parameter

        static $curNumber = 0;  /// current value for next $number
        static $prevLevel = 0;  /// minimalistic security check - assumes headings are created following the text order

        /**
         * Build a heading with a source text and a line number in a file.
         * 
         * @param string $text   the source text for heading, including the '#' prefix.
         * @param int    $line   the line number in the source file.
         * @param object $logger the caller object with a logging function called error()
         */
        function __construct(string $text, int $line, object $logger) 
        {
            // sequential number for all headers of all files
            self::$curNumber += 1;
            $this->number = self::$curNumber;
            // count number of '#' = heading level
            $this->level = self::getHeadingLevel($text);
            if ($this->level > self::$prevLevel + 1) {
                $logger->error("level {$this->level} heading skipped one or more heading levels");
            }
            $this->line = $line;
            $this->text = trim(mb_substr($text, $this->level, null, 'UTF-8'));
            self::$prevLevel = $this->level;
        }

        /**
         * Resets the number to 0.
         */
        public static function init() : void 
        {
            Heading::$curNumber = 0;
        }

        /**
         * Compute heading level from the starting '#'s.
         * Static function, call as Heading::getHeadingLevel(string)
         * also on instances like $heading->getHeadingLevel(string)
         *
         * @param string $content the text with '#'s from which to compute heading level.
         *
         * @return int the heading level
         */
        static function getHeadingLevel(string $content) : int 
        {
            $text = trim($content);
            $level = 0;
            $length = mb_strlen($text, 'UTF-8');
            while (mb_substr($text, $level, 1) == '#' && $level < $length) {
                $level += 1;
            }
            return $level;
        }
    }
}
