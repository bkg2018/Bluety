<?php

/**
 * Multilingual Markdown generator - TokenIgnoreDirective class
 *
 * This class represents a token for the .ignore(( directive.
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
 * @package   mlmd_token_ignore_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenOpenLanguage.class.php';

    use MultilingualMarkdown\TokenOpenLanguage;
    
    /**
     * .ignore(( directive token.
     */
    class TokenOpenIgnore extends TokenOpenLanguage
    {
        public function __construct()
        {
            parent::__construct(IGNORE);
        }
        public function __toString()
        {
            return '<directive> .ignore((';
        }
        public function output(object $lexer, object $filer): bool
        {
            $lexer->debugEcho("<OPEN ignore>\n");
            $lexer->pushLanguage(IGNORE, $filer);
            return true;
        }
    }
}
