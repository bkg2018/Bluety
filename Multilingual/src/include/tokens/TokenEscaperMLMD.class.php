<?php

/**
 * Multilingual Markdown generator - TokenEscaperMLMD class
 *
 * This class represents a token for MLMD escaped text between '.{' and '.}'. This syntax allows MLMD content to use any special
 * characters without bothering about vaationsriable exxpansion or directives interpreting. MLMD escaped text may contain normal
 * MD escaping notations as well as MLMD directives or variables between accolades. This is used in MLMD documentation itself to avoid
 * interpretation of directives when the desired effect is to have them written into the final output files.
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
 * @package   mlmd_token_escaper_mlmd_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenBaseEscaper.class.php';

    use MultilingualMarkdown\TokenBaseEscaper;
    
    /**
     * Class for the MLMD escaper token.
     * Starts with '.{' and runs until '.}' if ound. Start and end symbols are not put into the content.
     */
    class TokenEscaperMLMD extends TokenBaseEscaper
    {
        public function __construct()
        {
            parent::__construct('.{');
        }
        public function output(object $lexer, object $filer): bool
        {
            $lexer->debugEcho('<MLMD ESCAPE ' . $this->debugText() . ">\n");
            return true;
        }
        public function processInput(object $lexer, object $filer): bool
        {
            $this->content = '';    
            $this->skipSelf($filer);
            $currentChar = $filer->getCurrentChar();
            do {
                if ($filer->isMatching('.}')) {
                    $filer->getNextChar();// skip end marker
                    break;
                }
                $this->content .= $currentChar;
                $currentChar = $filer->getNextChar();
            } while ($currentChar != null);
            $this->length = mb_strlen($this->content);
            $lexer->storeToken($this);
            $lexer->setCurrentChar($filer->getNextChar());
            return true;
        }
    }

}
