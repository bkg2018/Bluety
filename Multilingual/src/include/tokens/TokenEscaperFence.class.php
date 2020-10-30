<?php

/**
 * Multilingual Markdown generator - TokenEscaperFence class
 *
 * This class represents a token for code fence start or end at the beginning of a line (or after spaces only) present
 * before and after escaped text lines.
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
 * @package   mlmd_token_fence_class
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
     * Class for the code fence.
     * The code fence opening starts with a triple back-tick possibly followed
     * by a language name. The token will skip over the reste of current line and
     * send everything to outputs while watching for ending fence.
     */
    class TokenEscaperFence extends TokenBaseEscaper
    {
        public function __construct()
        {
            parent::__construct('```');
        }
        /**
         * Identify self against an UTF-8 buffer and position.
         * Make sure code fence starts a new line.
         */
        public function identifyInBuffer(string $buffer, int $pos): bool
        {
            if ($pos <= 0) {
                return true;
            }
            $lf = mb_substr($buffer, $pos - 1, 1);
            if ($lf != "\n") {
                return false;
            }
            return parent::identifyInBuffer($buffer, $pos);
        }
        /**
         * Let the token self-identify against an input handler Filer object.
         *
         * @param object $filer the Filer object
         *
         * @return bool true if theh current token can be found at current Filer position and buffer content.
         */
        public function identifyInFiler(object $filer): bool
        {
            if ($filer->getPrevChar() != "\n") {
                return false;
            }
            return parent::identifyInFiler($filer);
        }
        public function ouputNow(object $lexer): bool
        {
            return ($lexer->getLanguageStackSize() <= 1);
        }
        public function output(object $lexer, object $filer): bool
        {
            $lexer->debugEcho('<CODE FENCE ' . $this->debugText() . ">\n");
            $filer->outputRawCurrent($lexer, $this->content);
            return true;
        }
    }

}
