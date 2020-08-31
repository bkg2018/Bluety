<?php

/**
 * Multilingual Markdown generator - Lexer class.
 *
 * The Lexer transforms an UTF-8 buffer into a sequence of tokens.
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
 * @package   mlmd_token_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    mb_internal_encoding('UTF-8');

    require_once('tokens/TokenNumbering.class.php');
    require_once('tokens/TokenLanguages.class.php');
    require_once('tokens/TokenTOC.class.php');
    require_once('tokens/TokenAllDirective.class.php');
    require_once('tokens/TokenDefaultDirective.class.php');
    require_once('tokens/TokenIgnoreDirective.class.php');
    require_once('tokens/TokenEndDirective.class.php');
    require_once('tokens/TokenEmptyLine.class.php');
    require_once('tokens/TokenEOL.class.php');
    require_once('tokens/TokenSingleBacktick.class.php');
    require_once('tokens/TokenDoubleBacktick.class.php');
    require_once('tokens/TokenFence.class.php');
    require_once('tokens/TokenDoubleQuote.class.php');
    require_once('tokens/TokenSpaceEscape.class.php');

    class Lexer
    {
        private $tokens = [];   // array of all predefined tokens and languages codes directives tokens added by .languages

        public function __construct()
        {
            $this->tokens[] = new TokenNumbering();                     /// token singleton for .numbering
            $this->tokens[] = new TokenLanguages();                     /// token singleton for .languages
            $this->tokens[] = new TokenTOC();                           /// token singleton for .toc

            $this->tokens[] = new TokenAllDirective();                  /// token singleton for .all((
            $this->tokens[] = new TokenDefaultDirective('');            /// token singleton for .((
            $this->tokens[] = new TokenDefaultDirective('default');     /// token singleton for .default((
            $this->tokens[] = new TokenIgnoreDirective();               /// token singleton for .ignore((
            $this->tokens[] = new TokenEndDirective();                  /// token singleton for .))
            $this->tokens[] = new TokenEmptyLine();                     /// token singleton for \n at beginning of line
            $this->tokens[] = new TokenEOL();                           /// token singleton for \n, must be found after empty line
            $this->tokens[] = new TokenSingleBacktick();                /// token singleton for `
            $this->tokens[] = new TokenDoubleBacktick();                /// token singleton for ``
            $this->tokens[] = new TokenFence();                         /// token singleton for ```
            $this->tokens[] = new TokenDoubleQuote();                   /// token singleton for "
            $this->tokens[] = new TokenSpaceEscape();                   /// token singleton for 4 spaces

            // these tokens can be instanciated more than once:
            //$this->tokens[] = new TokenLanguageDirective($language);
            //$this->tokens[] = new TokenText($content);
            //$this->tokens[] = new TokenEscapedText($content);
        }

        /**
         * Check if current position in a buffer matches a registered token and return the token.
         * Return null if there is no known token at the given position.
         */
        public function getToken(string $buffer, int $pos): ?Token
        {
            foreach ($this->tokens as $token) {
                if ($token->identify($buffer, $pos)) {
                    return $token;
                }
            }
            return null;
        }

        /**
         * Debugging echo of current character and line info.
         * To activate this echo, set the "debug" environment variable to "1".
         *
         * @return nothing
         */
        private function debugEcho(string $c): void
        {
            if (getenv("debug") == "1") {
                echo $c;
            }
        }

        /**
         * Analyze an UTF-8 buffer content and transform it into an array of successive tokens.
         * The last token in a buffer is always EMPTY_LINE and no other empty line can be located in the buffer.
         *
         * @param string $buffer    the UTF-8 content buffer to transform
         * @param int    $line      [IN/OUT] the initial line number for buffer content
         * @param object $logger    optional logger object with error/warning functions
         *
         * @return array a Token array
         */
        public function transform(string $buffer, int &$line, ?Logger $logger): array
        {
            $pos = 0;
            $maxPos = mb_strlen($buffer) - 1;// last authorized position in buffer
            $tokens = [];                           // array of tokens
            $continue = true;                       // stop on an empty line, else continue
            $text = '';                             // current text until a token is found
            $this->debugEcho("\n[$line]: ");
            do {
                $token = $this->getToken($buffer, $pos);
                if ($token) {
                    // store current text in a text token if needed
                    if (!empty($text)) {
                        $tokens[] = new TokenText($text);
                        $text = '';
                    }
                    // now store token after processing from it
                    $pos += $token->getLength();
                    $token->process($buffer, $pos);
                    $tokens[] = $token;
                    $continue = !$token->isType(TokenType::EMPTY_LINE);
                    // adjust line number if needed
                    if ($token->isType([TokenType::EOL,TokenType::EMPTY_LINE])) {
                        $line += 1;
                        $this->debugEcho("\n[$line]: ");
                    }
                } else {
                    $c = mb_substr($buffer, $pos, 1);
                    $text .= $c;
                    $pos += 1;
                    $this->debugEcho($c);
                    // $logger->error('cannot find a valid directive or token');
                    // break;
                }
            } while (($pos <= $maxPos) && $continue);
            return $tokens;
        }

        /**
         * Parse an input file and generate files.
         * This process reads the input file stream, detects and interprets directives and sends output to files.
         * Variables expansions is done in the outputToFiles() function.
         *
         * @param string $filename  The path to the input file. Can be relative or absolute, if relative it is
         *                          relative to rootDir.
         *
         * @return bool true if input file processed correctly, false if any error.
         */
        public function process(string $filename): bool
        {
            return true;
        }
    }
}
