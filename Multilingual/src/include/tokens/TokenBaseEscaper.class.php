<?php

/**
 * Multilingual Markdown generator - TokenBaseEscaper class
 *
 * This class represents a token for sequence of characters surrounding escaped text.
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
 * @package   mlmd_token_escaper_class
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
     * Class for an escaper starting/ending.
     * This class will store escaped text tokens by parsing until its closing marker.
     * This is why there are only opening escaper tokens and no closing ones.
     */
    class TokenBaseEscaper extends TokenBaseKeyworded
    {
        protected $content = '';/// the escaped text, including opening and closing escapers
        protected $length = 0;  /// character length of content
        
        public function __construct(string $marker)
        {
            parent::__construct(TokenType::ESCAPER, $marker, true);
        }
        public function __toString()
        {
            return $this->debugText();
        }
        public function isType($type): bool
        {
            if ((\is_array($type) && \in_array(TokenType::ESCAPER, $type)) || ($type == 'TokenType::ESCAPER')) {
                return true;
            }
            return parent::isType($type);
        }
        /**
         * Process input: get text until we find another escape marker. 
         * Update tokens array with the token itself. The escaped text is stored
         * by the token.
         */
        public function processInput(object $lexer, object $filer, array &$allTokens): bool
        {
            $this->content = $this->keyword;
            $this->skipSelf($filer);
            $currentChar = $filer->getCurrentChar();
            if ($currentChar != null) {
                do {
                    $this->content .= $currentChar;
                    $currentChar = $filer->getNextChar();
                    $prevChars = $filer->fetchPreviousChars($this->keywordLength);
                } while (($prevChars != $this->keyword) && ($currentChar != null));
            }
            $this->length = mb_strlen($this->content);
            // replace current character by next one and tell Lexer to store it as new current text start
            $lexer->setStoreText(true);
            $lexer->setCurrentChar($currentChar);
            // self store in token array
            $allTokens[] = $this;
            return true;
        }
    }
}
