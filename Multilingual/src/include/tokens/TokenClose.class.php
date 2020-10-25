<?php

/**
 * Multilingual Markdown generator - TokenClose class
 *
 * This class represents a token for the .)) ending directive which closes the streamed .xxxx(( directives.
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
 * @package   mlmd_token_end_directive_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenBaseInline.class.php';

    use MultilingualMarkdown\TokenBaseInline;
    
    /**
     * .)) directive token.
     */
    class TokenClose extends TokenBaseInline
    {
        public function __construct()
        {
            parent::__construct(TokenType::CLOSE_DIRECTIVE, '.))', true);
        }
        public function __toString()
        {
            return "<close {$lexer->getCurLanguage()}>";
        }

        // Closing directive will have Lexer processing all stored tokens if it empties the language stack.
        public function ouputNow(object $lexer): bool
        {
            return ($lexer->getLanguageStackSize() <= 1);
        }
        // Output: have Lexer updating the current output language
        public function output(object $lexer, object $filer): bool
        {
            $lexer->debugEcho("<CLOSE {$lexer->getCurLanguage()}>\n");
            $lexer->popLanguage($filer);
            return true;
        }
    }
}
