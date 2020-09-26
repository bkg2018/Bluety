<?php

/**
 * Multilingual Markdown generator - TokenText class
 *
 * This class represents a token for normal text. In normal text output, variables are expanded.
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
 * @package   mlmd_token_text_class
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
     * Token for text.
     */
    class TokenText extends Token
    {
        protected $content; /// text content for this token, including spaces and EOLs
        protected $length;  /// number of UTF-8 characters

        public function __construct($content)
        {
            parent::__construct(TokenType::TEXT);
            $this->content = $content;
            $this->length = mb_strlen($content);
        }
        public function __toString()
        {
            $maxlen = 60;
            return '<text> ' .
                    (mb_strlen($this->content) < $maxlen ?
                        $this->content :
                        mb_substr($this->content, 0, $maxlen / 2) . '...' . mb_substr($this->content, -$maxlen / 2));
        }

        /**
         * Add a character or string to content.
         *
         * @param string $c the character or string to add.
         */
        public function addChar(string $c): void
        {
            $content .= $c;
            $this->length = mb_strlen($this->content);
        }

        /**
         * Return the content.
         */
        public function getText(): Streaming
        {
            return $this->content;
        }

        /**
         * Return the number of UTF-8 characters in content.
         */
        public function getTextLength(): int
        {
            return $this->length;
        }
    }

}
