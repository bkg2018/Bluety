<?php
declare(strict_types=1);
/**
* Multilingual Markdown generator - Numbering class
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
* @package   mlmd_numbering_class
* @author    Francis Piérot <fpierot@free.fr>
* @copyright 2020 Francis Piérot
* @license   https://opensource.org/licenses/mit-license.php MIT License
* @link      TODO
*/
namespace MultilingualMarkdown {

    require_once 'OutputModes.class.php';

    /**
    * Numbering class, used by $allNumberings array for all files.
    */
    class Numbering
    {
        // settings
        private $outputMode = OutputModes::MD;       // output mode
        private $start = 10;            // starting level, do not number levels below
        private $end = 0;               // ending level, do not number levels above
        private $levelsPrefix = [];      // level => prefix for this level alone, should only be used for level 1
        private $levelsNumbering = [];  // level => starting symbol ('a'..'z', 'A'..'Z', '1'..'9, '&I', '&i')
        private $levelsSeparator = [];  // level => separator string after symbol for each level
        private $fullNumeric = true;    // true when no symbol is alphabetic
        private $style = OutputModes::MD;

        // status
        private $curNumbering = [];     // current increment for each level
        private $prevLevel = 0;         // previous level processed by getTOCline()

        // Roman numbers
        private static $romanLimits = [1000 => 'M',900 => 'CM',500 => 'D',400 => 'CD',100 => 'C',90 => 'XC',50 => 'L',40 => 'XL',10 => 'X',9 => 'IX',5 => 'V',4 => 'IV',1 => 'I'];

        /**
         * Return Roman number from decimal.
         * 
         * @param int $number the number to translate
         * 
         * @return string the corresponding Roman number
         */
        private function getRoman(int $number) : string
        {
            $result = '';
            while ($number > 0) {
                foreach (static::$romanLimits as $roman => $int) {
                    if($number >= $int) {
                        $number -= $int;
                        $result .= $roman;
                        break;
                    }
                }            
            }
            return $result;
        }

        /**
         * Set output mode.
         * If numbering scheme has been set, the output mode will use a numbered format.
         * If not, it will use a non-numbered format.
         * Setting a numbering scheme after setting the output mode will adjust the mode.
         * 
         * @param string $name the output mode name 'md', 'mdpure', 'html' or 'htmlold'
         * @param object $logger the caller object with an error() function
         */
        public function setOutputMode(int $name, object $logger = null) : void
        {
            $mode = OutputModes::getFromName($name, $this);
            if ($mode == OutputModes::INVALID) {
                if ($logger) {
                    $logger->error("invalid output mode name '$name'");
                }
                return;
            }
            $this->outputMode = $mode;
        }

        /**
         * Check if there is an ative scheme.
         * 
         * @return bool true if a Numbering scheme is active, false if it is empty.
         */
        public function isActive() : bool
        {
            return count($this->levelsNumbering) > 0;
        }

        /**
         * Restrict a level between 1 and 9.
         * 
         * @param int $level the proposed level
         * 
         * @return int the level adjusted to fit between 1 and 9
         */
        public function checkLevel(int $level) : int
        {
            return ($level < 1) ? 1 : ($level > 9) ? 9 : $level;
        }

        /**
         * Build a numbering scheme from parameter string.
         * 
         * Syntax for string: <level:>[<prefix>]:<symbol>:<separator>[,...]
         * 
         * Level: 1 to 9
         * Prefix: any string except ':' character
         * Symbol:
         * - `A` to `Z`: uppercase starting letter
         * - `a` to `z`: lowercase starting letter
         * - `&I`: uppercase Roman numbers (I, II, III etc), always start at 'I'
         * - `&i`: lowercase Roman numbers (i, ii, iii etc), always start at 'i'
         * - `1` to `9`: starting number
         * Separator: any string except ':' character
         *
         * @param string $scheme  the scheme string
         * @param object $logger  the caller object with an error() function
         * @see Logger interface
         */
        function __construct(string $scheme, object $logger = null) 
        {
            $defs = explode(',', $scheme);
            $this->levelsPrefix = []; // only allowed for level 1
            $this->levelsNumbering = [];
            $this->levelsSeparator = [];
            $this->curNumbering = [];
            $this->fullNumeric = true;
            // interpret each scheme level definition
            foreach ($defs as $def) {
                $parts = explode(':', $def);
                if (count($parts) != 4) {
                    if ($logger) {
                        $logger->error("invalid .numbering scheme '$def': ignored");
                    }
                    continue;
                }
                $level = $parts[0];
                $prefix = $parts[1];
                $symbol = $parts[2];
                $separator = $part[3];
                if ($level < '1' || $level > '9') {
                    if ($logger) {
                        $logger->error("invalid .numbering scheme '$def': level must be between 1 and 9");
                    }
                    continue;
                }
                /// store level parameters
                if ($level > 1 && !empty($prefix)) {
                    if ($logger) {
                        $logger->warning("prefix '$prefix' in '$def' will be ignored (level > 1)");
                    }
                    $this->levelsPrefix[$level] = '';
                } else {
                    $this->levelsPrefix[$level] = "$prefix ";
                }
                if (
                    ($symbol < '1' || $symbol > '9') 
                    && ($symbol < 'a' || $symbol > 'z') 
                    && ($symbol < 'A' || $symbol > 'Z')
                    && ($symbol != '&i') && ($symbol != '&I')
                ) {
                    if ($logger) {
                        $logger->error("invalid numbering symbol in .numbering '$def': values are 1 to 9, 'a' to 'z', 'A' to 'Z', '&i' or '&I'");
                    }
                } else {
                    $this->levelsNumbering[$level] = $symbol;
                    if (!is_numeric($symbol)) {
                        $this->fullNumeric = false;
                    }
                }
                $this->levelsSeparator[$level] = $separator;
                $this->curNumbering[$level] = 0;
            }
            // sort all by level so foreach() can track levels in growing order
            ksort($this->levelsPrefix, SORT_NUMERIC);
            ksort($this->levelsNumbering, SORT_NUMERIC);
            ksort($this->levelsSeparator, SORT_NUMERIC);
            ksort($this->curNumbering, SORT_NUMERIC);
        }

        /**
         * Set the first and last level to number.
         * Zeroing both limits (calling wioth no parameters) disables Numbering.
         * Numbering scheme is not required to define all levels
         * between start and end.
         * 
         * @param int $start first elvel to number between 1 and 9, 0 to disable
         * @param int $end   last level to number between start and 9, 0 to disable
         */
        public function setLevelLimits(int $start = 0, int $end = 0) : void
        {
            $this->start = $start;
            $this->end = $end;
        }

        /**
         * Resets all numbers on all levels.
         */
        public function resetNumbering() : void
        {
            foreach( $this->curNumbering as &$curNumbering) {
                $curNumbering = 0;
            }
        }

        /**
         * Get the numbering sequence for a level and update numbers.
         * The number depends on current numbers at each level and
         * getting the numbering will update the number for the relevant levels:
         * 
         * - if previous level was above (level > previous), new level starts at number 0.
         * - else the number for the level is incremented
         * 
         * Then the string using all levels symbols and numbers from level start to requested
         * level is computed.
         * 
         * @param int $level the level
         * 
         * @return string the nmbering string to use on headings and TOC lines
         */
        public function getNumbering(int $level) : string
        {
            if ($this->start == 0 && $this->end == 0) {
                return '';
            }
            $sequence = '';
            // level 1 can be prefixed
            if ($level == 1 && isset($this->levelsPrefix[1])) {
                $sequence = $this->levelsPrefix[1] . ' ';
            }
            // adjust number for this level
            if ($level <= $this->prevLevel) {
                $this->curNumbering[$level] += 1;
            } else {
                $this->curNumbering[$level] = 0;
            }
            // build <symbol><separator>... string
            for ($i = $this->start ; $i <= $level ; $i += 1) {
                // set <symbol><separator>
                $numbering = $this->levelsNumbering[$i] ?? '1';  
                $isNumeric = is_numeric($numbering);        
                if ($isNumeric) {
                    $sequence .= $numbering + $this->curNumbering[$level];
                } else {
                    if (in_array($numbering,['&i', '&I'])) {
                        $sequence .= $this->getRoman($curNumbering[$level]);
                    } else {
                        $sequence .= chr(ord($numbering) + $curNumbering[$level]);
                    }
                }
                // add separator if not last level, else ')' or ' )'
                if ($i < $level) {
                    $sequence .= $this->levelsSeparator[$level] ?? '';
                } else {
                    if ($isNumeric) {
                        $sequence .= ' ';
                    }
                    $sequence .= ') '; 
                }
            }
            return $sequence;
        }
    } // class
}  // namespace
