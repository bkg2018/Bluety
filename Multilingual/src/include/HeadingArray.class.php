<?php
declare(strict_types=1);
/**
 * Multilingual Markdown generator - Heading Array class
 * The array contains an object for each heading from one file. The object
 * has a level (the number of prefix '#'), a unique number (unique above all 
 * processed files),
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

    use MultilingualMarkdown\Heading;
    use MultilingualMarkdown\Numbering;
    require_once 'Heading.class.php';
    require_once 'Numbering.class.php';

    /**
     * Heading array class for all headings from a file.
     * The file name given at allocation is used in TOC links. It should
     * be relative to the path of the file where the link will be written.
     * It can be ignored when written in the origin file itself. (e.g. in local TOC.)
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
         * Set the output mode.
         * The output mode can be combined with a numbering scheme, in which case
         * the numbering scheme is reset to all 0 offsets and associated for the 
         * given output mode. The scheme may have been set beforehand, but it can also be
         * set afterwards on the Numbering object.
         * 
         * @param string $name      the output mode name, 'md', 'mdpure', 'html' or 'htmlold'
         * @param object $numbering the Numbering associated object if any, can be null or ignored
         */
        public function setOutputMode(string $name, ?object $numbering = null) : void
        {
            $this->outputMode = OutputModes::getFromName($name);
            if ($numbering !== null) {
                $numbering->setOutputMode($name);
                $numbering->resetNumbering();
            }
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
        public function add(Heading $heading) : void
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
         * Get last indexx value for the array.
         * 
         * @return int the last valid index value
         */
        public function getLastIndex()
        {
            return count($this->allHeadings) - 1;
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
         * Check if a heading is the last available between two levels.
         *
         * @param int $index the index of the heading in the array, or -1 for current exploration index
         * @param int $start the highest heading level (1 = top)
         * @param int $end   the lowest heading level (> start)
         * 
         * @return bool true if the current heading is the last available between start and $end,
         *              false if there is at least one relevant heading after it.
         */
        public function isHeadingLastBetween(int $index = -1, int $start = 1, int $end = 9) : bool
        {
            if ($index < 0) {
                $index = $this->curIndex;
            }
            for ($i = $index + 1 ; $i < count($this->allHeadings) ; $i += 1) {
                if ($this->allHeadings[$i]->getLevel() >= $start && $this->allHeadings[$i]->getLevel() <= $end) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Check if a heading is between two levels.
         *
         * @param int $index the index of the heading in the array, or -1 for current exploration index
         * @param int $start the highest heading level (1 = top)
         * @param int $end   the lowest heading level (> start)
         * 
         * @return bool true if the current heading is the last available between start and $end,
         *              false if there is at least one relevant heading after it.
         */
        public function isHeadingBetween(int $index = -1, int $start = 1, int $end = 9) : bool
        {
            if ($index < 0) {
                $index = $this->curIndex;
            }
            if ($this->allHeadings[$index]->getLevel() >= $start && $this->allHeadings[$index]->getLevel() <= $end) {
                return true;
            }
            return false;
        }

        /**
         * Find the first heading in the array for a level after a given line number.
         *
         * @param int       $level    the heading level to look for
         * @param int       $line     the line number where to start search
         *
         * @return int -1 if no heading found, else the index of Heading object
         */
        public function findIndex(int $level = 1, int $line = 0) : ?int
        {
            foreach ($this->allHeadings as $index => $object) {
                if ($object->getLine() >= $line) {
                    if ($object->getLevel() == $level) {
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
        private function checkIndex(int $index = -1, ?object $logger = null) : ?int
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
        public function getSpacing(int $index = -1, ?object $logger = null) : string
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
                    return \str_repeat(' ', $repeat * ($heading->getLevel() - 1));
                default:
                    // all html modes
                    return \str_repeat('&nbsp;', 4 * ($heading->getLevel() - 1));
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
        public function getAnchor(int $index, ?object $logger = null) : string
        {
            $id = $this->allHeadings[$index]->getNumber();
            switch ($this->outputMode) {
                case OutputModes::MDPURE:
                    return "{#a$id}";
                case OutputModes::HTMLOLD:
                case OutputModes::HTMLOLDNUM:
                    return "<A name=\"a$id\"></A>";
                default:
                    return "<A id=\"a$id\"></A>";
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
         * The caller is responsible for giving the relevant language file name and maximum 
         * heading level so the last line can be detected.
         * 
         * HTML all variants: <A href="file#id">text</A>
         * MD all variants: [text](file#id)
         * 
         * @param string $path      the file path where lies the anchor, must be relative to root dir
         * @param int    $index     the index of the heading, -1 to use current exploration index.
         * @param int    $start     the minimum heading level (lowest number of '#'s)
         * @param int    $end       the maximum heading level (biggest number of '#'s)
         * @param object $logger    the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the TOC link, or null if error.

         */
        public function getTOCLink(string $path, int $index, int $start, int $end, ?object $logger = null) : string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $id = $this->allHeadings[$index]->getNumber();
            $text = $this->allHeadings[$index]->getText();
            switch ($this->outputMode) {
                case OutputModes::MDPURE:
                case OutputModes::MD:
                case OutputModes::MDNUM:
                    return "[{$text}]({$path}#a{$id})";
                default:
                    if ($this->isHeadingLastBetween($index, $start, $end)) {
                        return "<A href=\"{$path}#a{$id}\">{$text}</A>";
                    }
                    return "<A href=\"{$path}#a{$id}\">{$text}</A><BR>";
            }
            if ($logger) {
                $logger->error("invalid output mode {$this->outputMode}");
            }
            return '';
        }

        /**
         * Get Numbering for current or given heading.
         * The caller provide a Numbering object setup for current file.
         * A dash may prefix text for some output modes if requested (for TOC lines)
         * 
         * HTMLNUM/HTMLOLDNUM:  `<numbering>)`
         * MDNUM:               `- <numbering>)`
         * MDPURE with NUM:     `1.`
         * all other variants:  `-`
         * 
         * @param int    $index     the index of the heading, -1 to use current exploration index.
         * @param object $numbering the Numbering object in charge of current file numbering scheme.
         * @param bool   $addDash   true to add a dash prefix in MDNUM or non numbered modes
         * @param object $logger    the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the numbering string, or null if error.
        */
        public function getNumberingText(int $index, object &$numbering, bool $addDash, ?object $logger = null) : string
        {
            if ($index >= 0) {
                // jump to the idnex while updating the numbering
                $index = $this->checkIndex($index, $logger);
                if ($index === null) {
                    return null;
                }
                $numbering->resetNumbering();
                for ($i = 0 ; $i < $index ; $i += 1) {
                    $numbering->next($this->allHeadings[$i]->getLevel());
                }
            } else {
                $index = $this->curIndex;
            }
            if ($numbering == null) {
                return null;
            }
            $this->curIndex = $index;
            return $numbering->getText($this->allHeadings[$index]->getLevel(), $addDash);
        }

        /**
         * Get full line for current or given heading.
         * This must be used sequentially on all headings of the array or numbering won't be consistent
         * regarding previous heading level. the whole sequence must be started with a Numbering and 
         * current index reset.
         *
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
        public function getHeadingLine(int $index, object &$numbering, ?object $logger = null) : ?string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $heading = $this->allHeadings[$index];
            if (!$heading->isLevelWithin($numbering)) {
                return null;
            }
            $anchor = $this->getAnchor($index, $logger);
            $numberingText = $this->getNumberingText($index, $numbering, false, $logger);
            $text = $heading->getText();
            if (\in_array($this->outputMode, [OutputModes::MD, OutputModes::MDNUM, OutputModes::MDPURE])) {
                return $numberingText . $text . $anchor;
            }
            return $anchor . $numberingText . $text;
        }

        /**
         * Get TOC full line for current or given heading.
         * This must be used sequentially on all headings of the array or numbering won't be consistent
         * regarding previous heading level. the whole sequence must be started with a Numbering and 
         * current index reset.
         *
         * Components for TOC line :
         * 
         * HTML all variants:  <spacing><numbering> <TOClink>\n\n
         * MD all variants:    <spacing><numbering> <TOClink>\n\n
         * 
         * @param int    $index     index of the heading, -1 to use current exploration index.
         * @param object $numbering the Numbering object in charge of current file numbering scheme.
         * @param object $logger    the caller object with an error() function, can be null to ignore errors.
         * @see Logger interface
         *
         * @return string the full heading line, or null if error or level not within 
         *                numbering scheme limits.
         */
        public function getTOCLine(int $index, object &$numbering, ?object $logger = null) : ?string
        {
            $index = $this->checkIndex($index, $logger);
            if ($index === null) {
                return null;
            }
            $heading = $this->allHeadings[$index];
            if (!$heading->isLevelWithin($numbering)) {
                return null;
            }
            $spacing = $this->getSpacing($index, $logger);
            $numberingText = $this->getNumberingText($index, $numbering, true, $logger);
            $text = $this->getTOCLink('{file}', $index, $numbering->getStart(), $numbering->getEnd(), $logger);
            return $spacing . $numberingText . $text;
        }
    }
}
