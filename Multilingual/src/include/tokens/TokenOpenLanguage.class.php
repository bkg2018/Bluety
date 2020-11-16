<?php

/**
 * Multilingual Markdown generator - TokenOpenLanguage class
 *
 * This class represents a token for an opening language code .<code>(( directive. The language code
 * must have been declared in the .languages directive.
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
 * @package   mlmd_token_language_directive_class
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
     * .<code>(( directive token.
     * This kind of token is created by the .languages directive.
     */
    class TokenOpenLanguage extends TokenBaseInline
    {
        private $language = ''; // language code from .languages directives

        public function __construct(string $language)
        {
            $this->language = $language;
            parent::__construct(TokenType::OPEN_DIRECTIVE, ".$language((", true);
        }

        public function processInput(Lexer $lexer, object $input, Filer &$filer = null): void
        {
            // check if previous token is EOL, and pre-previous token is close.
            // EOL between close and open must be ignored and deleted from token stack
            $lexer->adjustCloseOpenSequence();
            parent::processInput($lexer, $input, $filer);
        }
        public function output(Lexer &$lexer, Filer &$filer): bool
        {
            $lexer->debugEcho("<OPEN {$this->language}>\n");
            $lexer->pushLanguage($this->language, $filer);
            return true;
        }
    }
}
