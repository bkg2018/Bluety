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
    require_once('tokens/TokenOpenAll.class.php');
    require_once('tokens/TokenOpenDefault.class.php');
    require_once('tokens/TokenOpenIgnore.class.php');
    require_once('tokens/TokenClose.class.php');
    require_once('tokens/TokenEmptyLine.class.php');
    require_once('tokens/TokenEOL.class.php');
    require_once('tokens/TokenSingleBacktick.class.php');
    require_once('tokens/TokenDoubleBacktick.class.php');
    require_once('tokens/TokenFence.class.php');
    require_once('tokens/TokenDoubleQuote.class.php');
    require_once('tokens/TokenSpaceEscape.class.php');
    // on demand directives
    require_once('tokens/TokenText.class.php');             // text outside/between directives
    require_once('tokens/TokenOpenLanguage.class.php');     // .fr((  .en((  etc created when applying .languages directive
    require_once('tokens/TokenHeading.class.php');          // all lines starting with a # become a heading token
    // Headings numbering scheme
    require_once('Numbering.class.php');
    // HTML/MD various output modes (set in Lexer and in Numbering)
    require_once('OutputModes.class.php');

    class Lexer
    {
        // Tokens
        private $knownTokens = [];          /// array of all predefined tokens and languages codes directives tokens added by .languages

        // Defined languages
        private $languageList = null;       /// LanguageList object handling all available languages codes

        // Prepared datas
        private $allHeadingsArrays = [];    /// One HeadingArray for each input file
        private $allNumberings = [];        /// One numbering scheme for each input file

        // MD/HTML output modes for headings anchors and toc links
        private $outputMode = OutputModes::MD;

        // Status and settings
        private $languageSet = false;   /// true when at least one language has been set
        private $waitLanguages = true;  /// true to wait for .languages directive in each input file
        private $languageStack = [];    /// stack of tokens names for languages switching, including .all, .default and .ignore
        private $curLanguage = 'all';   /// name of current language token (index in $knownTokens)
        private $ignoreLevel = 0;       /// number of opened 'ignore', do not output anything when this variable is not 0

        public function __construct()
        {
            // single line directives, derived from TokenBaseSingleLine
            $this->knownTokens['numbering']  = new TokenNumbering();                 ///  .numbering
            $this->knownTokens['languages']  = new TokenLanguages();                 ///  .languages
            $this->knownTokens['toc']        = new TokenTOC();                       ///  .toc

            // streamed language directives
            $this->knownTokens['all']        = new TokenOpenAll();                   ///  .all((
            $this->knownTokens['default']    = new TokenOpenDefault('');             ///  .((
            $this->knownTokens[]             = new TokenOpenDefault('default');      ///  .default((, identical to .((
            $this->knownTokens['ignore']     = new TokenOpenIgnore();                ///  .ignore((
            $this->knownTokens['close']      = new TokenClose();                     ///  .))

            // other streamed directives
            $this->knownTokens[]             = new TokenEmptyLine();                 ///  \n at beginning of line
            $this->knownTokens['eol']        = new TokenEOL();                       ///  \n, must be checked later than TokenEmptyLine

            // escaped text streamed directives, derived from TokenBaseEscaper
            $this->knownTokens[]             = new TokenDoubleQuote();               ///  "
            $this->knownTokens[]             = new TokenFence();                     ///  ```
            $this->knownTokens[]             = new TokenDoubleBacktick();            ///  `` - must be checked later than TokenFence
            $this->knownTokens[]             = new TokenSingleBacktick();            ///  `  - must be checked later than TokenDoubleBacktick
            $this->knownTokens[]             = new TokenSpaceEscape();               ///  4 spaces

            // these tokens can be instanciated more than once:
            //$this->knownTokens[] = new TokenOpenLanguage($language);
            //$this->knownTokens[] = new TokenText($content);

            $this->languageList = new LanguageList();
            $this->allNumberings = [];

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
            foreach ($this->knownTokens as $token) {
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
            // debug trace
            foreach ($allTokens as $token) {
                echo (string)$token . "\n";
            }

            foreach ($allTokens as $token) {
                if (!$token->output($this, $filer)) {
                    return false;
                }
            }
            $key = array_key_last($allTokens);
            while ($key !== null) {
                unset($allTokens[$key]);
                $key = array_key_last($allTokens);
            }
            return true;
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
            if ($this->waitLanguages) {
                $this->languageSet = false;
            }
            while ($c != null) {
                $storeText = false; // store current character in $text temporary buffer
                $resetText = false; // empty $text temporary buffer
                $token = null;
                switch ($c) {
                    case '`':
                    case '"':
                        if ($this->languageSet) {
                            // start of escaped text
                            $token = $this->fetchToken($filer);
                            if ($token === null) {
                                if ($trace) {
                                    $filer->error("unrecognized escape character [$c] in text, should translate into a token", __FILE__, __LINE__);
                                }
                                $storeText = true;
                            }
                        } 
                        break;
                    case '#':
                        // eliminate trivial case (not preceded by EOL)
                        $prevChar = $filer->getPrevChar();
                        if ($prevChar != "\n") {
                            $storeText = true;
                        } else {
                            //TODO: heading
                        }
                        break;
                    case '.':
                        // eliminate trivial case when followed by a space or EOL
                        $nextChar = $filer->fetchNextChars(1);
                        if (($nextChar != ' ') && ($nextChar != "\n") && ($nextChar != "\t")) {
                            $token = $this->fetchToken($filer);
                            if ($token == null) {
                                $storeText = $this->languageSet;
                            } else {
                                // before .languages directive, ignore everything but the directive
                                if (!$this->languageSet && !$token->identifyInBuffer('.languages', 0)) {
                                    $token = null;
                                }
                            }
                        } else {
                            $storeText = $this->languageSet;
                        }
                        break;
                    case '':
                        break;
                    default:
                        $storeText = $this->languageSet;
                        break;
                }
                // handle token if found one
                if ($token) {
                    // first, store current temporary text if any
                    if (!$emptyText) {
                        $allTokens[] = new TokenText($text);
                        $resetText = true;
                    }
                    // let the token process further input if needed
                    $token->processInput($this, $filer, $allTokens);
                    // update output files at token request
                    if ($token->ouputNow($this)) {
                        $this->output($filer, $allTokens);
                    }
                }
                if ($storeText) {
                    $text .= $c;
                    $emptyText = false;
                } else if ($resetText) {
                    $text = '';
                    $emptyText = true;
                }
                $c = $filer->getNextChar();
            }
            // finish with anything left
            if (!$emptyText) {
                $allTokens[] = new TokenText($text);
            }
            $this->output($filer, $allTokens);
            return true;
        }

        /**
         * Pushes current language and set to given name.
         * Name must be an index to $knownTokens: 'all', 'ignore', 'default' and each language code.
         *
         * @param string $name the new language code to set as current
         *
         * @return bool true if name exists and stack has been updated, false if not
         */
        public function pushLanguage(string $name, object &$filer): bool
        {
            // name must exist as an index
            if (!\in_array($name, $this->knownTokens)) {
                array_push($this->languageStack, $this->curLanguage);
                $this->curLanguage = $name;
                // handle 'ignore'
                if ($name == 'ignore') {
                    $this->ignoreLevel += 1;
                    // update Filer status
                    $filer->setIgnoreLevel($this->ignoreLevel);
                }
                // update Filer status (will be ignored if ignore level > 0)
                return $filer->setLanguage($this->languageList, $name);
            }
            return false;
        }

        /**
         * Return the language stack size.
         * Each opening language directive - e.g. '.en((' - pushes one language on stack, and each
         * closing directive - '.))' - pops the current language. When the stack is empty, no close
         * directive has any more effect and the assumed behaviour of Lexer is atht of 'all' language
         * where all input text wil go to each output language file.
         */
        public function getLanguageStackSize(): int
        {
            return count($this->languageStack);
        }

        /**
         * Pop the last language name from stack.
         * Stack is reduced by subtracting its last pushed name. The new current language is set from
         * the new last value on stack.
         * If nothing was in stack, this function returns null and 'all' should be assumed
         * by caller so text will go to all output files when out of languages directives.
         *
         * @return string|null null when stack is empty, else returns the last pushed language name
         */
        public function popLanguage(object $filer): ?string
        {
            // pop a level from stack and get new current language
            $popped = null;
            $count = count($this->languageStack);
            if ($count > 1) {
                $popped = array_pop($this->languageStack);
                $this->curLanguage = $this->languageStack[array_key_last($this->languageStack)];
            } else {
                if ($count == 1) {
                    $popped = array_pop($this->languageStack);
                }
                $this->curLanguage = 'all';
                $this->ignoreLevel = 0;
            }
            // handle when popping 'ignore'
            if ($popped == 'ignore') {
                if ($this->ignoreLevel >= 1) {
                    $this->ignoreLevel -= 1;
                }
                // update Filer status
                $filer->setIgnoreLevel($this->ignoreLevel);
            }
            // update Filer output language
            $filer->setLanguage($this->languageList, $this->curLanguage);
            return $this->curLanguage;
        }

        /**
         * Return the current language.
         */
        public function getCurLanguage(): string
        {
            return $this->curLanguage;
        }

        /**
         * Ready output files given current languages settings.
         */
        public function readyFiler(object $filer): bool
        {
            return $filer->readyOutputs($this->languageList);
        }

        /**
         * Ready all headings by reading them from all input files.
         */
        public function readyHeadings(object $filer): void
        {
            unset($this->allHeadingsArrays);
            $this->allHeadingsArrays = [];
            $languagesToken = $this->knownTokens['languages'];
            Heading::init();// reset global headings numbering to 0
            // Explore each input file ($filer is iterable and returns relative filenames and index)
            foreach ($filer as $index => $relFilename) {
                $filename = $filer->getInputFile($index); // full file path
                if ($filename == null) {
                    continue;
                }
                $file = fopen($filename, 'rb');
                if ($file === false) {
                    $this->error("could not open [$filename]", __FILE__, __LINE__);
                    continue;
                }
                // create an array for headings of this file
                $headingArray = new HeadingArray($relFilename);
                // keep track of the line number for each heading
                $curLineNumber = 0;
                // remember if the .languages directive has been read
                $languageSet = false;
                // loop on each file line@
                do {
                    $text = getNextLineTrimmed($file, $curLineNumber);
                    if (!$text) {
                        break;
                    }
                    // handle .languages directive
                    if ($languagesToken->identifyInBuffer($text, 0)) {
                        $languageSet = trim(mb_substr($text, $languagesToken->getLength()));
                        $this->setLanguagesFrom($languageSet, $filer);
                        continue;
                    }
                    // ignore lines before the .languages directive
                    if ($languageSet === false) {
                        continue;
                    }
                    // skip escaped lines (code fences, double back-ticks)
                    $pos = strpos($text, '```');
                    if ($pos !== false) {
                        // escaped by double backticks+space / space+double backticks?
                        if (($pos <= 2) || (mb_substr($text, $pos - 3, 3) != '`` ') || (mb_substr($text, $pos + 3, 3) != ' ``')) {
                            do {
                                $text = getNextLineTrimmed($file, $curLineNumber);
                                if (!$text) {
                                    break;
                                }
                            } while (strpos($text, '```') === false);
                        }
                    } else {
                        // store headings
                        if (($text[0] ?? '') == '#') {
                            $heading = new Heading($text, $curLineNumber, $this);
                            $headingArray[] = $heading;
                        }
                    }
                } while (!feof($file));
                fclose($file);

                // force a level 1 object if no headings
                if (count($headingArray) == 0) {
                    $heading = new Heading('# ' . $relFilename, 1, $this);
                    $headingArray[] = $heading;
                }
                $this->allHeadingsArrays[$relFilename] = $headingArray;
                unset($headingArray);
            } // next file
        }

        /**
         * Set languages list from a parameter string.
         * This is a relay to LanguagesList::setFrom().
         * Also reprograms output files.
         *
         * @param string $parameters  the parameter string
         * @param object $filer       the Filer object
         *
         * @return bool true if languages have been set correctly and main language was
         *              valid (if 'main=' was in the parameters.)
         */
        public function setLanguagesFrom(string $parameters, object $filer): bool
        {
            $result = $this->languageList->setFrom($parameters);
            if ($result) {
                $filer->readyOutputs($this->languageList);
                foreach ($this->languageList as $index => $language) {
                    if (!\array_key_exists($language['code'], $this->knownTokens)) {
                        $this->knownTokens[$language['code']] = new TokenOpenLanguage($language['code']);    
                    }
                }
                $this->languageSet = isset($index);
            }
            return $result;
        }

        /**
         * Set numbering scheme from a parameter string (for current file.)
         * The scheme parameters can be set from .numbering directive (file local) or from
         * command-line parameters (global).
         */
        public function setNumberingFrom(string $parameters, object $filer): bool
        {
            //$this->numbering = new Numbering($parameters, $this);
            foreach ($filer as $index => $relFilename) {

            }
            return false;
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
