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

    // directives and static tokens
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
    require_once('tokens/TokenText.class.php');
    require_once('tokens/TokenEscapedText.class.php');
    // on demand directives
    require_once('tokens/TokenLanguageDirective.class.php');

    class Lexer
    {
        private $tokens = [];           // array of all predefined tokens and languages codes directives tokens added by .languages

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
         * The function doesn't advance position, it just checks if there
         * is a known token at the starting position. It may read enough characters from input
         * for checking depending on what's left in buffer.
         *
         * @param object $filer the input file handling object.
         *
         * @return null|object   the recognized token or null if none, which means
         *                       caller Lexer will have to decide what to do with content
         *                       (e.g. creating text tokens)
         */
        public function fetchToken(object $filer): ?Token
        {
            foreach ($this->tokens as $token) {
                if ($token->identifyInFiler($filer)) {
                    return $token;
                }
            }
            return null;
        }
        /**
         * Look at next character from input.
         * This call doesn't advance input position but rather just send back the next character
         * from input file, or null at end of input file.
         *
         * @param object $filer   the input file handling object.
         * @param int $charsNumber the number of characters to fetch
         *
         * @return null|string   the next character which will be read from input,
         *                       null if already at end of file.
         */
        public function fetchNextChars(object $filer, int $charsNumber): ?string
        {
            return $filer->fetchNextChars($charsNumber);
        }
        /**
         * Execute the effects of a sequence of tokens on outputs.
         *
         * @param object $filer     the Filer object which will receive outputs and settings
         * @param array  $allTokens [IN/OUT] the sequence of tokens, will be emptied on output if no error
         *
         * @return bool true if all OK and token sequence is emptied, else an error occured
         */
        public function output(object &$filer, array &$allTokens)
        {
            foreach ($allTokens as $token) {
                if (!$token->output($filer)) {
                    return false;
                }
            }
            for ($index = count($allTokens) - 1; $index >= 0; $index -= 1) {
                unset($allTokens[$index]);
            }
            return true;
        }

        /**
         * Check if current position in a buffer matches a registered token and return the token.
         * The function doesn't advance position or change buffer, uit just checks if there
         * is a known token at the starting position.
         *
         * @param string $buffer the UTF-8 content buffer where to search in
         * @param int    $pos    the starting position for token search
         *
         * @return null|object   the recognized token or null if none, which means
         *                       caller LExer will have to decide what to do with content
         *                       (e.g. creating text tokens)
         *
        public function getToken(string $buffer, int $pos): ?Token
        {
            foreach ($this->tokens as $token) {
                if ($token->identifyInBuffer($buffer, $pos)) {
                    return $token;
                }
            }
            return null;
        }*/

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
         * Process an opened filer, input and output files ready.
         * Builds sequences of tokens while reading input character by character,
         * and periodically updates outputs when meeting some directives.
         */
        public function process(object $filer): bool
        {
            $c = $filer->getCurChar();  /// current character (between tokens)
            $text = '';                 /// current text out of tokens
            $emptyText = true;
            $allTokens = [];            /// current token sequence to execute
            $languageSet = false;       /// ignore input until .languages has been found
            while ($c != null) {
                switch ($c) {
                    case '`':
                    case '"':
                        if (!$languageSet) {
                            break;
                        }
                         // start of escaped text
                        $token = $this->fetchToken($filer);
                        if ($token) {
                            // first, store current text if any
                            if (!$emptyText) {
                                $allTokens[] = new TokenText($text);
                                $text = '';
                                $emptyText = true;
                            }
                            // now store the escape sequence: escaper, text, escaper
                            $token->processInput($this, $filer, $allTokens);
                            if ($token->ouputNow()) {
                                $this->output($filer, $allTokens);
                            }
                            break;
                        }
                        if ($trace) {
                            $filer->error("unrecognized escape character [$c] in text, should translate into a token", __FILE__, __LINE__);
                        }
                        $text .= $c;
                        $emptyText = false;
                        break;
                    case '.':
                        // eliminate trivial case when followed by a space or EOL
                        $nextChar = $filer->fetchNextChars(1);
                        if ($nextChar == ' ' || $nextChar == "\n" || $nextChar == "\t") {
                            $token = null;
                        } else {
                            $token = $this->fetchToken($filer);
                        }
                        if ($token == null) {
                            if (!$languageSet) {
                                break;
                            }
                            // no directive: keep storing text
                            $text .= $c;
                            $emptyText = false;
                        } else {
                            // before .languages directive, ignore everything but the directive
                            if (!$languageSet && !$token->identifyInBuffer('.languages', 0)) {
                                break;
                            }
                            // valid token: first store current text
                            if (!$emptyText) {
                                $allTokens[] = new TokenText($text);
                                $text = '';
                                $emptyText = true;
                            }
                            $token->processInput($this, $filer, $allTokens);
                            if ($token->ouputNow()) {
                                $this->output($filer, $allTokens);
                            }
                        }
                        break;
                    case '':
                        break;
                    default:
                        if ($languageSet) {
                            $text .= $c;
                            $emptyText = false;
                        }
                        break;
                }
                $c = $filer->getNextChar();
            }
            // finish with anything left
            if (!$emptyText) {
                $allTokens[] = new TokenText($text);
            }
            $this->output($filer, $allTokens);
        }

        /**
         * Add a language to directive tokens.
         *
         * @param string $code language code as written in .languages directive
         *
         * @return bool true if the language is already known by Lexer
         */
        public function addLanguage(string $code): void
        {
            if (!\array_key_exists($code, $this->tokens)) {
                $this->tokens[$code] = new TokenLanguageDirective($code);    
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
         * @return array a Token array, can be empty
         *
        public function getTokens(string &$buffer, int &$line, ?Logger $logger): array
        {
            $pos = 0;
            $maxPos = mb_strlen($buffer) - 1;   // last authorized position in buffer
            $tokens = [];                       // array of tokens
            $continue = true;                   // stop on an empty line, else continue
            $text = '';                         // current text outside of tokens
            $prevToken = null;                  // previous token copy, for any needed test
            $this->debugEcho("\n[$line]: ");
            do {
                $token = $this->getToken($buffer, $pos);
                if ($token) {
                    // if there is awaiting text, store it in a text or escaped text token
                    if (!empty($text)) {
                        if (($prevToken != null) && $prevToken->isType(TokenType::ESCAPER)) {
                            // is new token same escaper ?
                            if ($token->isType($prevToken->getType())) {
                                $tokens[] = new TokenEscapedText($text);
                                $tokens[] = $token;
                                $prevToken = null;
                                $text = '';
                                $pos += $token->getLength();
                            } else {
                                // not the ending escaper, just keep storing in $text
                                $text .= mb_substr($buffer, $pos, 1);
                                $pos += 1;
                            }
                        } else {
                            // previous token was not a text escaper: store a text token and then the new token
                            $tokens[] = new TokenText($text);
                            $tokens[] = $token;
                            $prevToken = $token;
                            $text = '';
                            $pos += $token->getLength();
                        }
                    }

                    //TODO:  token processing above and below?
                    
                    // now store token after processing from it
                    $pos += $token->getLength();
                    $processed = $token->processInput($buffer, $pos);
                    // processInput may return null, the token itself, or an array of tokens
                    if (\is_array($processed)) {
                        $tokens = array_merge($tokens, $processed);
                    } else {
                        $tokens[] = $token;
                    }
                    $continue = !$token->isType(TokenType::EMPTY_LINE);
                    // adjust line number if needed
                    if ($token->isType([TokenType::EOL,TokenType::EMPTY_LINE])) {
                        $line += 1;
                        $this->debugEcho("\n[$line]: ");
                    }
                } else {
                    // no token found, store in $text to build a text token later.
                    $c = mb_substr($buffer, $pos, 1);
                    $text .= $c;
                    $pos += 1;
                    $this->debugEcho($c);
                }
            } while (($pos <= $maxPos) && $continue);
            return $tokens;
        }*/
    }
}
