<?php

/**
 * Multilingual Markdown generator - TokenBaseInline class
 *
 * This class represents a token for an opening directive of the .xxxx(( kind.
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
 * @package   mlmd_token_stream_directive_class
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
     * Streaming text directive token.
     *
     * The derived tokens are used for directives lying in the text flow, like the open or close
     * language directives .<code>(( and .)).
     *
     * This class is not instanciated by itself but is base for actual directives tokens.
     */
    class TokenBaseInline extends TokenBaseKeyworded
    {
        public function __construct(int $type, string $keyword, bool $ignoreCase)
        {
            parent::__construct($type, $keyword, $ignoreCase);
        }
        public function __toString()
        {
            return '- FORBIDDEN: base TokenBaseInline class, check Lexer code -';
        }
        /**
         * Processing input: store in token list, skip over directive and go next character. 
         */
        public function processInput(Lexer $lexer, object $input, Filer &$filer = null): void
        {
            $this->skipSelf($input);
            $lexer->appendToken($this);
            $lexer->setCurrentChar($input->getCurrentChar());
        }
    }
}
