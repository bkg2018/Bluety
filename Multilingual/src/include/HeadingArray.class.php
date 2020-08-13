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
        private $outputMode = OutputModes::MD;
        private $file = '';         // path of file relative to root dir for these headings

        /**
         * Build the array, register the file base path (no extension)
         */
        function __construct(string $file) {
            $this->file = $file;
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

        /**
         * Check if an index is valid.
         * 
         * @param int $index -1 for current index or a heading index
         * @param object $logger the caller object with an error() function, can be null to ignore errors.
         * 
         * @return int|null same index if valid, current index if -1, null if invalid
         */
        private function checkIndex(int $index = 1, object $logger = null) : ?int
        {
            if ($index < -1 || $index >= count($this->allHeadings)) {
                if ($logger) {
                    $logger->error("invalid heading index $index");
                }                
                return null;
            }
            if ($index == -1) {
                return $this->curIndex;
            }
            return $index;
        }

        /**
         * Get the spacing prefix for a heading and current output mode.
         * The spacing is used in TOC lines before each heading.
         * Depending on the output mode, the spacing can be:
         *
         * MDPURE: 3 spaces for each level above 1
         * MD/MDNUM : 2 spaces for each level above 1
         * HTML all variants: 4 '&nbsp;' for each level above 1
         *
         * @param int $index index of the heading, -1 to use current exploration index.
         * @see Logger interface
         *
         * @return string the spacing prefix for current output mode, or null if error.
         */
        public function getSpacing(int $index = -1, object $logger = null) : string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $heading = &$this->allHeadings[$index];
            $repeat = 2;
            switch ($this->outputMode) {
                case OutputModes::MDPURE:
                    $repeat += 1;
                    // intentionnal fall-through
                case OutputModes::MD:
                case OutputModes::MDNUM:
                    return \str_repeat(' ', $repeat * ($heading->level - 1));
                default:
                    // all html modes
                    return \str_repeat('&nbsp;', 4 * ($heading->level - 1));
            }
            // impossible case
            if ($logger) {
                $logger->error("impossible case in " . __function__);
            }
            return null;
        }

        /**
         * Get the anchor for a heading and current output mode.
         * The anchor is targetted by TOC links in TOC lines.
         * Depending on the output mode, for a unique identifier a{id}, the anchor can be:
         *
         * MDPURE : a Markdown anchor {#a{id}}
         * MD/MDNUM/HTML/HTMLNUM : an id HTML anchor <A id="a{id}">
         * HTMLOLD/HTMLOLDNUM : an name HTML anchor <A name="a{id}">
         *
         * @param int    $index  valid index of the heading (not checked here)
         * @param object $logger the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the anchor, or null if error.
         */
        private function getAnchor(int $index, object $logger = null) : string
        {
            $id = $this->allHeadings[$index]->number;
            switch ($this->outputMode) {
                case OutputModes::MDPURE:
                    return "\{#a{$id}\}";
                case OutputModes::HTMLOLD:
                case OutputModes::HTMLOLDNUM:
                    return "<A name=\"{$id}\"></A>";
                default:
                    return "<A id=\"{$id}\"></A>";
            }
            if ($logger) {
                $logger->error("invalid output mode {$this->outputMode}");
            }
            return null;
       }

        /**
         * Get TOC link for current or given heading in a given file.
         * The returned string includes the heading text as legend for the link.
         * The file path must be the output language file relative path where the anchor lies for this link.
         * The caller is responsible for giving the relevant language file name.
         * 
         * HTML all variants: <A href="file#id">text</A>
         * MD all variants: [text](file#id)
         * 
         * @param string $path   the file path where lies the anchor, must be relative to root dir
         * @param int    $index  the index of the heading, -1 to use current exploration index.
         * @param object $logger the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the TOC link, or null if error.

         */
        public function getTOCLink(string $path, int $index = -1, object $logger = null) : string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $id = $this->allHeadings[$index]->number;
            $text = $this->allHeadings[$index]->text;
            switch ($this->outputMode) {
                case OutputModes::MDPURE:
                case OutputModes::MD:
                case OutputModes::MDNUM:
                    return "[{$text}]({$path}#a{$id})";
                default:
                    return "<A href=\"{$path}#a{$id}\">{$text}</A>";
            }
            if ($logger) {
                $logger->error("invalid output mode {$this->outputMode}");
            }

        }

        /**
         * Get Numbering for current or given heading.
         * The caller provide a Numbering object setup for current file.
         * 
         * HTMLNUM/HTMLOLDNUM:  `<numbering>)`
         * MDNUM:               `- <numbering>)`
         * MDPURE with NUM:     `1.`
         * all other variants:  `-`
         * 
         * @param int    $index     the index of the heading, -1 to use current exploration index.
         * @param object $numbering the Numbering object in charge of current file numbering scheme.
         * @param object $logger    the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the numbering string, or null if error.
        */
        public function getNumbering(int $index = -1, object &$numbering, object $logger = null) : string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            if ($numbering == null) {
                return null;
            }
            $heading = $this->allHeadings[$index];
            return $numbering->getNumbering($heading->level);
        }

        /**
         * Get full line for current  or givenheading.
         * Components for heading line :
         * 
         * HTML all variants:  <anchor>\n<numbering> <text>\n\n
         * MD all variants:    <numbering> <text><anchor>\n\n
         * 
         * @param int    $index     index of the heading, -1 to use current exploration index.
         * @param object $numbering the Numbering object in charge of current file numbering scheme.
         * @param object $logger    the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the full heading line, or null if error.
         */
        public function getHeadingLine(int $index = -1, object &$numbering, object $logger = null) : string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $anchor = $this->getAnchor($index, $logger);
            $numbering = $this->getNumbering($index, $numbering, $logger);
        }

        /**
         * Get TOC full line for current or given heading.
         */
        public function getTOCLine(int $index = -1, object $logger = null) : string
        {

        }
    }
}
