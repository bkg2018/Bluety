<?php
declare(strict_types=1);
/**
 * Multilingual Markdown generator - Heading Array class
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
 * @package   mlmd_heading_array_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

namespace MultilingualMarkdown {
    /**
     * Heading array class for all headings from all files.
     */
    class HeadingArray
    {
        private $allHeadings = [];  // all headings from a file
        private $curIndex = 0;      // current exploration index

        function __construct() {

        }

        /**
         * Check if array if empty.
         * 
         * @return bool true if empty.
         */
        public function isEmpty() : bool
        {
            return (count($this->allHeadings) ==  0);
        }

        /**
         * Add a heading to the array
         */
        public function add(Heading& $heading) : void
        {
            $this->allHeadings[] = $heading;
        }

        /**
         * Reset exploration to first heading.
         */
        public function resetCurrent() : void {
            $this->curIndex = 0;
        }

        /**
         * Access to current heading.
         * 
         * @return Heading reference to the current heading.
         */
        public function &getCurrent() : Heading
        {
            return $this->allHeadings[$this->curIndex];
        }

        /**
         * Access to a heading at a given index.
         *
         * @param int $index the index for the heading to get
         *
         * @return Heading reference to the heading, null if invalid index
         */
        public function &getAt(int $index) : ?Heading
        {
            if ($index < 0 || $index >= count($this->allHeadings)) {
                return null;
            }
            return $this->allHeadings[$index];
        }

        /**
         * Go to next heading and get it.
         * 
         * @return Heading reference to the new current heading
         *                 null if no more heading available
         */
        public function &getNext() : ?Heading
        {
            if ($this->curIndex >= count($this->allHeadings) - 1) {
                return null;
            }
            $this->curIndex += 1;
            return $this->allHeadings[$this->curIndex];
        }

        /**
         * Check if current heading is the last available between two level limits.
         * 
         * @param int $start the highest heading level (1 = top)
         * @param int $end   the lowest heading level (> start)
         * 
         * @return bool true if the current heading is the last available between start and $end,
         *              false if there are other headings after it.
         */
        public function isCurrentLastBetween(int $start, int $end) : bool
        {
            for ($i = $this->curIndex + 1 ; $i < count($this->allHeadings) ; $i += 1) {
                if ($this->allHeadings[$i]->level >= $start && $this->allHeadings[$i]->level <= $end) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Find the first heading in the array for a level after a given line number.
         *
         * @param int       $level    the heading level to look for
         * @param int       $line     the line number where to start search
         *
         * @return int -1 if no heading found, else the index of Heading object
         */
        private function findIndex(int $level = 1, int $line = 0) : ?int
        {
            foreach ($this->allHeadings as $index => $object) {
                if ($object->line >= $line) {
                    if ($object->level == $level) {
                        return $index;
                    }
                }
            }
            return -1;
        }
    }
}
