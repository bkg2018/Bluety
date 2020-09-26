<?php

/**
 * Multilingual Markdown generator - TokenNumbering class
 *
 * This class represents a token for the .numbering directive.
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
 * @package   mlmd_token_numbering_class
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
     * .NUMBERING directive token.
     */
    class TokenNumbering extends TokenBaseSingleLine
    {
        public function __construct()
        {
            parent::__construct('.numbering', true);
        }
        public function __toString()
        {
            return '<directive> .numbering((';
        }
       /**
         * Process .numbering directive.
         * Register the numbering scheme.
         *
         * @param object $lexer  the Lexer object
         * @param object $filer  the Filer object ready for input, positionned on the directive
         * @param array  $tokens [IGNORED] ignored by this directive
         */
        public function processInput(object $lexer, object $filer, array &$tokens): bool
        {
            // skip the directive (no need to store)
            $this->skipSelf($filer);
            // get the parameters until end of line
            $params = '';
            do {
                $curChar = $filer->getNextChar();
                if (($curChar == "\n") || ($curChar == null)) {
                    break;
                }
                $params .= $curChar;
            } while ($curChar !== null);
            // set the numbering scheme in Lexer
            return $lexer->setNumberingFrom($params, $filer);
        }    
    }
}
