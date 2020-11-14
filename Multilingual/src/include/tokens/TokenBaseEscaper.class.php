<?php

/**
 * Multilingual Markdown generator - TokenBaseEscaper class
 *
 * This class is base for all the tokens holding an escaped text. All escaper tokens
 * have a text content and an identifier which starts and ends the escape sequence.
 * Only the text content will be output to files, with no variables or directive
 * interpretation.
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

    require_once 'TokenBaseKeyworded.class.php';

    use MultilingualMarkdown\TokenBaseKeyworded;
    
    /**
     * Class for an escaper starting/ending.
     * 
     * This class will store escaped text tokens by parsing until its closing marker.
     * This is why there are only opening escaper tokens and no closing ones.
     * The content text will be output as is with no variable expansion and no
     * directive interpretation.
     * 
     * Escaping can be done with multiple backtics as well as unique ones, so the identification
     * must be checked by trying triple first, then double, then single bacticks in this order.
     * This is the only place where identification order is significant.
     */
    class TokenBaseEscaper extends TokenBaseKeyworded
    {
        protected $content = '';/// the escaped text, including opening and closing escapers
        protected $length = 0;  /// character length of content

        public function __construct(string $marker)
        {
            parent::__construct(TokenType::ESCAPER, $marker, true);
        }

        /**
         * Return true when asked for TokenType::ESCAPER.
         * Accepts an array of token types or a single one.
         *
         * @param array|TokenType $type the token type to test, or an array of token types
         * @return true if the token type(s) is ESCAPER.
         */
        public function isType($type): bool
        {
            if ((\is_array($type) && \in_array(TokenType::ESCAPER, $type)) || ($type == 'TokenType::ESCAPER')) {
                return true;
            }
            return parent::isType($type);
        }

        /**
         * Tell if the token is empty of significant text content.
         *
         * @return bool true if the token has *no* text content.
         */
        public function isEmpty(): bool
        {
            return ($this->length <= 0);
        }
        
        /**
         * Process input: get text until we find the closing escape marker. 
         * Update tokens array with the token itself. The escaped text is stored
         * by the token.
         */
        public function processInput(Lexer $lexer, object $input, Filer &$filer = null): void
        {
            $this->content = $this->keyword;    
            $this->skipSelf($input);
            $input->adjustNextLine();
            $currentChar = $input->getCurrentChar();
            $prevChars = '';
            if ($currentChar != null) {
                do {
                    $this->content .= $currentChar;
                    $currentChar = $input->getNextChar();
                    $prevChars = $input->fetchPreviousChars($this->keywordLength);
                } while (($prevChars != $this->keyword) && ($currentChar != null));
            }
            $this->length = mb_strlen($this->content);
            $lexer->appendToken($this, $filer);
            $lexer->setCurrentChar($currentChar);
        }

        /**
         * Output content to the Filer object or change its settings.
         *
         * The content is sent as is, with no variables expansion nor directive interpretation.
         *
         * @param object $filer the Filer instance object which receives outputs and settings
         */
        public function output(Lexer $lexer, Filer $filer): bool
        {
            $lexer->debugEcho("<escaped output>\n");
            $filer->output($lexer, $this->content, false, $this->type);
            return true;
        }
    }
}
