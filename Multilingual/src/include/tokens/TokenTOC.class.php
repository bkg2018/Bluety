<?php

/**
 * Multilingual Markdown generator - TokenTOC class
 *
 * This class represents a token for the .toc directive.
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
 * @package   mlmd_token_toc_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenBaseSingleLine.class.php';

    use MultilingualMarkdown\TokenBaseSingleLine;
    
    /**
     * .TOC directive token.
     * 
     * The token stores the parameters following it as a string, and
     * interpret them in output() to send the decorated headings.
     */
    class TokenTOC extends TokenBaseSingleLine
    {
        public function __construct()
        {
            parent::__construct(TokenType::SINGLE_LINE_DIRECTIVE, '.toc', true);
        }
        public function __toString()
        {
            return '<directive> .toc';
        }

        /**
         * Tell if the token is empty of significant text content.
         */
        public function isEmpty(): bool
        {
            return false;
        }

        /**
         * TOC directive input processing.
         *
         * Processing input is only a matter of reading until end of line
         * and storing content. Actual output is done in output().
         */
        public function processInput(Lexer $lexer, object $input, Filer &$filer = null): void
        {
            // skip the directive (no need to store)
            $this->skipSelf($input);
            // store the parameters until end of line
            $this->content = $input->getLine();
            $this->length = mb_strlen($this->content);
            $lexer->appendToken($this, $filer);
        }

        
        /**
         * Do the actual TOC output into filer.
         * The output will be done in tyhe current output context so care
         * should be taken not to put .TOC directive in a language specific
         * text part, unless the expected effect is to restrain a toc to
         * a specific language.
         */
        public function output(Lexer $lexer, Filer $filer): bool
        {
            $lexer->debugEcho("<TOC - TODO: output toc content>\n");
            $filer->output($lexer, "<TOC (todo)>", false, $this->type);
            return true;
        }

    }
}
