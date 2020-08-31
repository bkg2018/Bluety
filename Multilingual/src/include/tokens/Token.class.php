<?php

/**
 * Multilingual Markdown generator - Token class
 *
 * The Lexer class cuts an UTF-8 text buffer into an array of successive parts of different types. The Token class
 * represents each of these possible parts. Some token types are only distinguished when a previous is interpreted. For
 * example, escaped text is only known when the opening escaper token is found. Most tokens can not happen inside
 * escaped text.
 *
 * Construction parameters depend on the Token: not all tokens need a keyword or a content.
 *
 * A Token is responsible for self-identification against a given buffer content and position, and for advancing
 * into the buffer if it has to process some content. These two functions are available on all Tokens and should at
 * least return false for identification or unchahged position for processing. Processing should not be
 * done without a positive self identification: the token will not check for this. Some tokens do no process
 * a bufffer but rather simply store a content: the function process() will then do nothing.
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
 * @package   mlmd_token_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    mb_internal_encoding('UTF-8');

    require_once 'TokenTypes.class.php';

    /**
     * Token base class.
     * Represents a type of template text part.
     */
    class Token
    {
        protected $type;       /// int value from TokenType enum consts.

        public function __construct(int $type)
        {
            if ($type < TokenType::FIRST || $type > TokenType::LAST) {
                $this->type = TokenType::UNKNOWN;
            } else {
                $this->type = $type;
            }
        }

        /**
         * Self-Identify against an UTF-8 buffer and position.
         */
        public function identify(string $buffer, int $pos): bool
        {
            return false;
        }

        /**
         * Process the given buffer starting at the given position, which should be right
         * after the token identifier.
         *
         * Calling the process function with a wrong position can lead to wrong
         * results so it should be called only after a positive self-identification.
         *
         * @param string $buffer the UTF-8 content to process
         * @param string $pos    [IN/OUT] the character position in $buffer where to start processing,
         *                       must be positionned just after the token keyword or symbols. This is
         *                       not checked by the process() function.
         *
         * @return int the error code, 0 if there was no error. Error codes are token specific.
         */
        public function process(string $buffer, int &$pos): bool
        {
            return true;
        }

        /**
         * Check if the token is of a given type.
         *
         * @param array|int $type the token type to test against, or an array of types
         *
         * @return true if the token is of the given type
         */
        public function isType($type): bool
        {
            if (is_array($type)) {
                return \in_array($this->type, $type);
            }
            return ($this->type == $type);
        }

        /**
         * Return the length of the token identifier.
         */
        public function getLength(): int
        {
            return 0;
        }
    }

}
