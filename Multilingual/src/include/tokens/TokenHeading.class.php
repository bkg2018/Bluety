<?php

/**
 * Multilingual Markdown generator - TokenHeading class
 *
 * This class represents a token for a heading in files. A heading is a line starting with at least one '#' character.
 * The token is created by Lexer when meeting such condition. 
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
 * @package   mlmd_token_heading_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'Token.class.php';

    use MultilingualMarkdown\Token;

    /**
     * Heading token.
     */
    class TokenHeading extends Token
    {
        private $heading = null;

        public function __construct(object $heading)
        {
            $this->heading = $heading;
            parent::__construct(TokenType::HEADING);
        }
        public function __toString()
        {
            return "Heading .{$this->heading}((";
        }
        public function identifyInFiler(object $filer): bool
        {
            // must be preceded by an end of line or nothing (possible if first line in file)
            $prevChar = $filer->getPrevChar();
            if (($prevChar != null) && ($prevChar != "\n")) {
                return false;
            }
            return true;
        }
        public function processInput(object $lexer, object $filer, array &$allTokens): bool
        {
            do {
                $c = $filer->getNextChar();
            } while (($c != "\n") && ($c != null));
            $lexer->setStoreText(false);
            $allTokens[] = $this;
            // skip final EOL
            if ($c !== null) {
                $c = $filer->getNextChar();
            }
            return true;
        }
        public function ouputNow(object $lexer): bool
        {
            return ($lexer->getLanguageStackSize() <= 1);
        }
        public function output(object $lexer, object $filer): bool
        {
            $lexer->debugEcho("<HEADING {$this->heading}>\n");
            return true;
        }
    }
}
