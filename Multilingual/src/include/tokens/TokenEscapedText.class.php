<?php

/**
 * Multilingual Markdown generator - TokenEscapedText class
 *
 * This class represents a token for escaped text. Escaped text is surrounded by escaper tokens
 * and will be output as-is, without variables or directives interpretation.
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
 * @package   mlmd_token_escaped_text_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'TokenText.class.php';

    use MultilingualMarkdown\TokenText;
    
    /**
     * Class for escaped text.
     * Escaped text do not expand variables nor interpret directives.
     */
    class TokenEscapedText extends TokenText
    {
        public function __construct($content)
        {
            parent::__construct($content);
            $this->type = TokenType::ESCAPED_TEXT;
        }
        public function __toString()
        {
            return '<escaped text> ' .
                (mb_strlen($this->content) < 40 ?
                $this->content :
                mb_substr($this->content, 0, 20) . '...' . mb_substr($this->content, -20));
        }
    }

}
