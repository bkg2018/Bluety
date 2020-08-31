<?php

/**
 * Multilingual Markdown generator - TokenKeyworded class
 *
 * This class represents a token identified by a sequence of characters.
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
 * @package   mlmd_token_keyworded_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    mb_internal_encoding('UTF-8');

    require_once 'Token.class.php';

    use MultilingualMarkdown\Token;
    
    /**
        * Base class for a token identified by a keyword.
        * The keyworded tokens are all the predefined directives, the language open directives
        * added by .languages directive, and the various forms of characters sequences which
        * surround escaped text.
        *
        * This class is not used by itself by extended into separate classes for each token.
        */
    class TokenKeyworded extends Token
    {
        protected $keyword = '';        /// keyword identifying the token
        protected $keywordLength = 0;   /// number of UTF-8 characters
        protected $ignoreCase = true;   /// ignore case difference for identification

        public function __construct(int $type, string $keyword, bool $ignoreCase)
        {
            parent::__construct($type);
            if ($ignoreCase) {
                $this->keyword = \mb_strtolower($keyword);
            } else {
                $this->keyword = $keyword;
            }
            $this->keywordLength = mb_strlen($keyword);
            $this->ignoreCase = $ignoreCase;
        }

        /**
            * Identify self against an UTF-8 buffer and position.
            *
            * The token knows its UTF-8 identifier / symbols and checks if the
            * given buffer is positionned on an occurence of this identifier.
            *
            * No change is made to the given buffer and position.
            *
            * @param string $buffer        the content where to look for self keyword
            * @param int    $pos           the 0-based character position where to look for
            *
            * @return bool true if the token can be found at the given position in the gvien buffer
            */
        public function identify(string $buffer, int $pos): bool
        {
            $test = mb_substr($buffer, $pos, $this->keywordLength);
            $testLower = \mb_strtolower($test);
            return ($ignorecase ? (\strcmp($testLower, $this->keywordLower) == 0) : (\strcmp($test, $this->keyword) == 0) );
        }

        /**
            * Return the length of the token identifier.
            */
        public function getLength(): int
        {
            return $this->keywordLength;
        }
    }
}
