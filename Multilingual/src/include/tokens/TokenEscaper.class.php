<?php

/**
 * Multilingual Markdown generator - TokenEscaper class
 *
 * This class represents a token for sequence of characters surrounding escaped text.
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
 * @package   mlmd_token_escaper_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenKeyworded.class.php';

    use MultilingualMarkdown\TokenKeyworded;
    
    /**
     * Class for an escaper starting/ending.
     * This class will store escaped text tokens by parsing until its closing marker.
     * This is why there are only opening escaper tokens and no closing ones.
     */
    class TokenEscaper extends TokenKeyworded
    {
        protected $text; /// the escaped text, including opening and closing escapers
        
        public function __construct(string $marker)
        {
            parent::__construct(TokenType::ESCAPER, $marker, true);
        }
        public function __toString()
        {
            return '<escaped text> ' .
                (mb_strlen($this->text) < 40 ?
                $this->text :
                mb_substr($this->text, 0, 20) . '...' . mb_substr($this->text, -20));
        }
        public function isType($type): bool
        {
            if ((\is_array($type) && \in_array(TokenType::ESCAPER, $type)) || ($type == 'TokenType::ESCAPER')) {
                return true;
            }
            return parent::isType($type);
        }
        /**
         * Process input: get text until we find another double backtick. Update tokens array.
         * Used by derived classes.
         * Stores both escape markers as well as the escaped text.
         */
        public function processInput(object $lexer, object $filer, array &$allTokens): bool
        {
            $this->text = $this->skipSelf();
            do {
                $curChar = $filer->getNextChar();
                if ($curChar == null) {
                    break;
                }
                $this->text .= $curChar;
                $prevChars = $filer->fetchPrevChars($this->keywordLength);
            } while ($prevChars != $this->keyword);
            // self store in token array
            $allTokens[] = $this;
            return true;
        }
    }
}
