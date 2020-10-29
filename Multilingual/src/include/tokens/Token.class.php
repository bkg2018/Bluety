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
 * into the buffer if it has to process some content or parameters. These two functions are available on all Tokens
 * and should at least return false or true for identification and adjust position for processing.
 * Processing should not be done without a prealable positive self identification: the token will not check
 * for this. Some tokens do no process a buffer but rather simply store an information: the function processInput() 
 * will then do nothing and will not advance the position more than right after the token.
 *
 * The Token also has an output() function which is called to possibly output some content to output files.
 * The outputs are done through the Filer class instance which is given to output(). Tokens whichh have nothing
 * to output will simply do nothing in the function.
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
     * Represents a directive or text part.
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
        public function __toString()
        {
            return '- FORBIDDEN: base Token class, check Lexer code -';
        }

        /**
         * Let the token self-identify against an input handler Filer object.
         *
         * @param object $filer the Filer input handling object
         *
         * @return bool true if theh current token can be found at current Filer position and buffer content.
         */
        public function identifyInFiler(object $filer): bool
        {
            return false;
        }

         /**
         * Let the token self-identify against an UTF-8 buffer and position.
         *
         * @param string $buffer a buffer holding UTF-8 content
         * @param int    $pos    the position in $buffer where to start identification
         */
        public function identifyInBuffer(string $buffer, int $pos): bool
        {
            return false;
        }

        /**
         * Skip over the token itself in the Filer object.
         * This doesn't store anything and is mainly for use by the directives
         * tokens themselves.
         */
        protected function skipSelf(object $filer): ?string
        {
            return null;
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
         * Type accessor.
         */
        public function getType(): int
        {
            return $this->type;
        }

        /**
         * Return the length of the token identifier.
         */
        public function getLength(): int
        {
            return 0;
        }

        /**
         * Process the input starting at the current position, assuming the token starts
         * at this position which should be right after the token identifier.
         *
         * If the current Token has to handle part of the following buffer content,
         * it must process it and update the buffer position to right after any character
         * it takes care of. The corresponding buffer part will not be available
         * to further tokens so the current token must store the content if needed, or
         * withdraw it if it only has informational purposes.
         *
         * The function may update an array of tokens by including the token itself
         * but also more tokens if it has to create them for its content and work.
         *
         * Calling the process function with a wrong position will lead to wrong
         * results: it must be called only after a positive self-identification.
         *
         * Default behaviour is to store itself in the tokens array and do nothing more.
         *
         * @param object $lexer  the Lexer object, used e.g. by languages directive to add tokens.
         * @param object $filer  the Filer input handling object, positionned on current character.
         *
         * @return int|null|array an error code > 0, or null to keep the token alone, or an array
         *                        of tokens starting with the token itself.
         */
        public function processInput(object $lexer, object $filer): bool
        {
           $lexer->storeToken($this);
           $lexer->setCurrentChar($filer->getNextChar());
           return true;
        }
 
         /**
         * Return a text where ascii control codes are replaced by [n]. 
         */
        protected function debugTextPart(string $text): string
        {
            $result = '';
            for ($pos =  0 ; $pos < mb_strlen($text) ; $pos += 1) {
                $c = mb_substr($text, $pos, 1);
                $result .= $c < ' ' ? '['.ord($c).']' : $c;
            }
            return $result;
        }

        /**
         * Return a summary of the text token content with neutralized control codes and max length of 60 characters.
         */
        protected function debugText(): string
        {
            $result = '';
            if (isset($this->length) && isset($this->content)) {
                $maxlen = 60;
                if ($this->length < $maxlen) {
                    return $this->debugTextPart($this->content);
                }
                $start = mb_substr($this->content, 0, $maxlen / 2);
                $end = mb_substr($this->content, -$maxlen / 2);
                $result = $this->debugTextPart($start) . '...' . $this->debugTextPart($end);
            }
            return $result;
        }

       /**
         * Output content to the Filer object or change its settings.
         * The token must handle whatever it has to do with the output files: send text content,
         * change current language, send raw text, etc.
         *
         * @param object $filer the Filer instance object which receives outputs and settings
         */
        public function output(object $lexer, object $filer): bool
        {
            $class = get_class($this);
            $backslash = strrpos($class, '\\');
            $lexer->debugEcho('<no output() for class ' . substr($class, $backslash + 1) . ">\n");
            return true;
        }

        /**
         * Tell if a token must process output immediately after being stored.
         * This is mainly for one-line directives and the .)) ending directive.
         */
        public function ouputNow(object $lexer): bool
        {
            return false;
        }
    }

}
