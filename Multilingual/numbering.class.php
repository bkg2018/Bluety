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
   /**
    * Numbering class, used by $allNumberings array for all files.
    */
    class Numbering
    {
        const MD = 0;
        const MDSCHEME = 1;
        const HTML = 2;
        const HTMLSCHEME = 3;
        private $STYLES = [MDPURE, MD, MDSCHEME, HTML, HTMLSCHEME];

        // settings
        private $start = 10;            // starting level
        private $end = 0;               // ending level
        private $levelsNumbering = [];  // level => starting symbol ('a'..'w', 'A'..'W', '1'..'9, 'X', 'x')
        private $levelsSeparator = [];  // level => separator string after symbol for each level
        private $fullNumeric = true;    // true when no symbol is alphabetic
        private $style = self::MD;

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
         * Build a numbering scheme from parameter string.
         * 
         * Syntax for string:
         * 
         * Level 1 def:        `[<prefix>]:<symbol>:<separator>`
         * Level 2-9 def:      `:<symbol>:<separator>`
         * Numbering syntax:   `<level:><def>[,...]`
         * 
         * Symbols can be:
         * 
         * - `A` to `W`: uppercase starting letter
         * - `a` to `w`: lowercase starting letter
         * - `X`: Roman numbers (I, II, III etc), always start at 'I'
         * - `1` to `n`: starting number
         *
         * @param string $scheme  the scheme string
         * @param object $logger  the caller object with an error() function
         */
        function __construct(string $scheme, object $logger) 
        {

            //TODO: $$ a revoir pour chiffres romains et préfixe level 1

            $defs = explode(',', $scheme);
            $this->levelsPrefix = []; // only allowed for level 1
            $this->levelsNumbering = [];
            $this->levelsSeparator = [];
            $this->curNumbering = [];
            $this->fullNumeric = true;
            foreach ($defs as $def) {
                $parts = explode(':', $def);
                $level = $parts[0];
                if ($level < '1' || $level > '9') {
                    $logger->error("bad argument in numbering scheme '$def': level before ':' must be between '1' and '9'");
                } else {
                    if ($level < $this->start) {
                        $this->start = $level;
                    } elseif ($level > $this->end) {
                        $this->end = $level;
                    }
                    $this->levelsNumbering[$level] = '';
                    $this->levelsSeparator[$level] = '';
                    $this->curNumbering[$level] = 0;
                    if (count($parts) > 1) {
                        $num = mb_substr($parts[1], 0, 1, 'UTF-8');
                        if (($num < '1' || $num > '9') && ($num < 'a' || $num > 'z') && ($num < 'A' || $num > 'Z')) {
                            $logger->error(
                                "bad argument in -numbering '$def': numbering after ':' " .
                                "must be between '1' and '9', 'a' and 'z', or 'A' and 'Z'"
                            );
                        } else {
                            $this->levelsNumbering[$level] = $num;
                            $this->levelsSeparator[$level] = mb_substr($parts[1], 1, 1, 'UTF-8');
                            $this->curNumbering[$level] = 0;
                            if (!is_numeric($num)) {
                                $this->fullNumeric = false;
                            }
                        }
                    } else {
                        $this->levelsNumbering[$level] = '';
                        $this->levelsSeparator[$level] = '';
                        $this->curNumbering[$level] = 0;
                    }
                }
            }
            ksort($this->levelsNumbering, SORT_NUMERIC);
            ksort($this->levelsSeparator, SORT_NUMERIC);
            ksort($this->curNumbering, SORT_NUMERIC);
        }

        /**
         * Set the style for prefixing.
         * TOC lines can be written in different styles.
         * 
         * MDPURE)
         * 
         * All numbering is done using the generic '1.' header and indenting 3 spaces for a sub level
         * 
         *    1. Heading level N
         *       1. Heading level N+1
         *          - Heading level not numbered
         *    1. Heading level N

         * MD)
         * 
         * no numbering.
         * 
         *    - Heading level N
         *      - Heading level N+1
         *        - Heading level not numbered
         *    - Heading level N
         * 
         *  MDSCHEME)
         * 
         *    - A) Heading level N
         *      - A.1) Heading level N+1
         *          - Heading level not numbered
         *    - 2) Heading level N
         * 
         *  HTML)
         * 
         *    Heading level N<br>
         *    &nbsp;&nbsp;&nbsp;&nbsp;Heading level N+1<br>
         *    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Heading level not numbered<br>
         *    Heading level N
         * 
         *  HTMLSCHEME)
         * 
         *    A) Heading level N<br>
         *    &nbsp;&nbsp;&nbsp;&nbsp;A-1) Heading level N+1<br>
         *    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Heading level not numbered<br>
         *    B) Heading level N
         * 
         * @param int $style MD, MDSCHEME or HTMLSCHEME
         * @param object $logger calling object with an error() function
         * @see Logger interface.
         */
        public function setStyle(int $style, object $logger) : void
        {
            if (in_array($style,$this->STYLES)) {
                $this->style = $style;
            } else {
                $this->error("unknown numbering style $style, setting MD style");
                $style = self::MD;
            }
        }

        /**
         * Get the levels numbering <symbol><separator>... sequence for a level.
         * 
         * @param int $level the level
         * 
         * @return string the sequence string for the heading and this numbering scheme
         */
        private function getSchemeSequence(int $level) : string
        {
            $sequence = '';
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
                    $sequence .= chr(ord($numbering) + $curNumbering[$level]);
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

#if 0
        /**
         * Build a TOC line for a heading.
         * If the heading level is inferior to the starting level of numbering scheme
         * the function returns an empty prefix.
         * 
         * @param object $heading The Heading object
         * 
         * @return the string for this heading line in TOC.
         */
        public function getTOCLine(Heading $heading) : string
        {
            if ($heading->level < $this->start) {
                return '' ;
            }
            switch ($this->style) {

                case self::HTML:
                    /* Schemed numbering HTML:

                    A) Heading level N<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;A-1) Heading level N+1<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Heading level > max<br>
                    B) Heading level N

                    Implementation:

                    - `&nbsp;` \* 4 \* level, '- ', heading text if level > max
                    - `&nbsp;` \* 4 \* level, sequence for level, ') ', heading text
                    - ending <br> for all lines except last one
                    - sequence = <symbol><separator>... using all levels <= level N
                    - number of `&nbsp;` can be anything
                    */
                    $line = \str_repeat(str_repeat('&nbsp;', 4), $level);
                    if ($level > $this->end) {
                        $line .= '- ';
                    } else {
                        $line = $this->getSchemeSequence($level) . $heading->text;
                    }
                    if (!$heading->isLast($this->start,$this->end)) {
                        $line .= '<br>';
                    }
                    break;

                case self::MDSCHEME:
                    /* Schemed numbering MD:

                    - A) Heading level N
                      - A.1) Heading level N+1
                          - Heading level > max
                    - 2) Heading level N

                    Implementation:

                    - level > max :   space \* 2 \* max, space \* 4 \* (level - max), '- ', heading text
                    - level <= max : space \* 2 \* level, '- ', sequence for level, ' ) ', heading text
                    - space before ')' if last symbol is numeric
                    - sequence = <symbol><separator>... using all levels <= level N
                    */
                    if ($level > $this->end) {
                        $line = \str_repeat('  ', $this->end); //  2 spaces x level
                        $line .= \str_repeat('    ', $level - $this->end); //  4 spaces x (level-max)
                        $line .= '- ';
                    } else {
                        $line = \str_repeat('  ', $level); //  2 spaces x level
                        $line .= '- ';
                        $line = $this->getSchemeSequence($level) . $heading->text;
                    }
                    break;

                default:
                case self::MD:
                    /* Pure MD TOC:

                    1. Heading level N
                       1. Heading level N+1
                          - Heading level > max
                    2. Heading level N

                    Implementation:

                    - space \* 3 * level, '1. ', heading text
                    - space \* 3 * level, '- ', heading text if level > max
                    */
                    $line = \str_repeat('   ', $level); // 3 spaces x level
                    if ($level > $this->end) {
                        $line .= '- ';
                    } else {
                        $line .= '1. ';
                    }
                    $line .= $heading->text;
                    break;
            }
            $this->prevLevel = $level;
            return $line;
        } // getTOCline
    } // class
}  // namespace
