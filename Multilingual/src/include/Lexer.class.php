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

    require_once('Constants.php');

    // directives and static tokens
    require_once('tokens/TokenNumbering.class.php');
    require_once('tokens/TokenTopNumber.class.php');
    require_once('tokens/TokenLanguages.class.php');
    require_once('tokens/TokenTOC.class.php');
    require_once('tokens/TokenOpenAll.class.php');
    require_once('tokens/TokenOpenDefault.class.php');
    require_once('tokens/TokenOpenIgnore.class.php');
    require_once('tokens/TokenClose.class.php');
    require_once('tokens/TokenEmptyLine.class.php');
    require_once('tokens/TokenEOL.class.php');
    require_once('tokens/TokenEscaperSingleBacktick.class.php');
    require_once('tokens/TokenEscaperDoubleBacktick.class.php');
    require_once('tokens/TokenEscaperTripleBacktick.class.php');
    require_once('tokens/TokenEscaperFence.class.php');
    require_once('tokens/TokenEscaperDoubleQuote.class.php');
    require_once('tokens/TokenEscaperSpace.class.php');
    require_once('tokens/TokenEscaperMLMD.class.php');
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
        private $mlmdTokens = [];               /// array of all predefined tokens and languages codes directives tokens added by .languages

        // Defined languages
        private $languageList = null;           /// LanguageList object handling all available languages codes

        // Preprocessed datas
        private $allHeadingsArrays = [];        /// One HeadingArray for each input file
        private $allStartingLines = [];         /// Line numbers after languages directive in each file
        private $allNumberingScheme = [];       /// Numbering scheme for each file, default is CLI parameter or main file directive
        private $allNumberings = [];            /// Current numbering for each file
        private $allTopNumbers = [];            /// Starting number for level 1 headings for each file (default to 0 = first number in scheme)

        // MD/HTML output modes for headings anchors and toc links
        private $outputMode = OutputModes::MD;

        // Status and settings
        private $languageSet = false;           /// true when at least one language has been set
        private $waitLanguages = true;          /// true to wait for .languages directive in each input file
        private $languageStack = [];            /// stack of tokens names for languages switching, including .all, .default and .ignore
        private $curLanguage = ALL;             /// name of current language token (index in $mlmdTokens)
        private $ignoreLevel = 0;               /// number of opened 'ignore', do not output anything when this variable is not 0
        private $currentChar = '';              /// current character, can be changed by token input processing
        private $currentText = '';              /// Current text flow, to be stored as a text token before next tokken
        private $curTokens = [];                /// Current tokens file, will be regularly sent to output when languages stack is empty
        private $trace = false;                 /// flag for a few prints or warnings control
        private $defaultNumberingScheme = '';   /// default numbering scheme, set by '-numbering' CLI parameter

        public function __construct()
        {
            // single line directives, derived from TokenBaseSingleLine
            $this->mlmdTokens['numbering']  = new TokenNumbering();                 ///  .numbering
            $this->mlmdTokens['topnumber']  = new TokenTopNumber();                 ///  .topnumber
            $this->mlmdTokens['languages']  = new TokenLanguages();                 ///  .languages
            $this->mlmdTokens['toc']        = new TokenTOC();                       ///  .toc

            // streamed language directives
            $this->mlmdTokens[ALL]          = new TokenOpenAll();                   ///  .all((
            $this->mlmdTokens['']           = new TokenOpenDefault('');             ///  .((
            $this->mlmdTokens[DEFLT]        = new TokenOpenDefault(DEFLT);          ///  .default((, identical to .((
            $this->mlmdTokens[IGNORE]       = new TokenOpenIgnore();                ///  .ignore((
            $this->mlmdTokens['close']      = new TokenClose();                     ///  .))

            // other streamed directives
            $this->mlmdTokens['empty']      = new TokenEmptyLine();                 ///  \n at beginning of line
            $this->mlmdTokens['eol']        = new TokenEOL();                       ///  \n, must be checked later than TokenEmptyLine

            // escaped text streamed directives, derived from TokenBaseEscaper
            $this->mlmdTokens['"']          = new TokenEscaperDoubleQuote();        ///  "   - MD double quote escaping
            $this->mlmdTokens['```c']       = new TokenEscaperFence();              ///  ``` - MD code fence
            $this->mlmdTokens['```']        = new TokenEscaperTripleBacktick();     ///  ``` - MD triple backtick escaping, must be checked later than TokenEscaperFence
            $this->mlmdTokens['``']         = new TokenEscaperDoubleBacktick();     ///  ``  - MD double backtick escaping, must be checked later than TokenEscaperTripleBacktick
            $this->mlmdTokens['`']          = new TokenEscaperSingleBacktick();     ///  `   - MD single backtick escaping, must be checked later than TokenEscaperDoubleBacktick
            $this->mlmdTokens['    ']       = new TokenEscaperSpace();              ///      - MD 4 spaces escaping
            $this->mlmdTokens['{}']         = new TokenEscaperMLMD();               /// .{.} - MLMD escaping

            // NB: TokenOpenLanguage will be instanciated by the .languages directive for each declared language <code>, stored in $this->mlmdTokens['code']
            // NB: TokenText will be instanciated by Lexer for each normal text part, stored in the tokens flow $this->curTokens
        }

        /**
         * Clear all datas from Lexer.
         */
        public function reset(): void
        {
            unset($this->languageList);
            $this->languageList = new LanguageList();
            \unsetArrayContent($this->allHeadingsArrays);
            \unsetArrayContent($this->allStartingLines);
            \unsetArrayContent($this->languageStack);
            \unsetArrayContent($this->allNumberings);
            \unsetArrayContent($this->allNumberingScheme);
            $this->initSet();
        }

        /**
         * Init status for a ready set of files.
         */
        public function initSet(): void
        {
            $this->languageSet = false;
            $this->waitLanguages = true;
            $this->resetCurrentText();            
            $this->ignoreLevel = 0;
        }

        /**
         * Reset current text flow.
         */
        public function resetCurrentText(): void
        {
            $this->currentText = '';
            $this->emptyText = true;
        }

        /**
         * Store current car in current text and go next char.
         */
        public function storeCurrentGoNext(object $input): void
        {
            $this->currentText .= $this->currentChar;
            $this->emptyText = false;
            $this->currentChar = $input->getNextChar();
        }

        /**
         * Add current text as token if not empty, then reset.
         */
        public function appendTextToken(): void
        {
            if (!$this->emptyText) {
                $this->curTokens[] = new TokenText($this->currentText);
                $this->resetCurrentText();
            }
        }

        /**
         * Store a token in current tokens array.
         * This is used by most tokens in their processInput() work to append themselves
         * to the lexer current flow of tokens.
         */
        public function appendToken(object &$token): void
        {
            $this->curTokens[] = $token;
        }

        /**
         * Set current character.
         * This can be used by tokens in their processInput() work.
         */
        public function setCurrentChar(?string $char): void
        {
            $this->currentChar = $char;
        }

        /**
         * Check if current position in a buffer matches a registered token and return the token.
         * The function doesn't advance position, it just checks if there is a known token at the
         * starting position. Notice that this may fetch characters from input if current buffer
         * doesn't hold enough characters.
         *
         * @param object $input  the input Filer or Storage object
         *
         * @return null|object   the recognized token or null if none, which means
         *                       caller Lexer will have to decide what to do with content
         *                       (e.g. creating text tokens)
         */
        public function fetchToken(object $input): ?Token
        {
            foreach ($this->mlmdTokens as $token) {
                if ($token->identify($input)) {
                    return $token;
                }
            }
            return null;
        }

        /**
         * Look at next character from input.
         * This call doesn't advance input position but send back the next character
         * from input file, or null at end of input file.
         *
         * @param object $input       the input Filer or Storage object
         * @param int    $charsNumber the number of characters to fetch
         *
         * @return null|string   the next character which will be read from input,
         *                       null if already at end of file.
         */
        public function fetchNextChars(object $input, int $charsNumber): ?string
        {
            return $input->fetchNextChars($charsNumber);
        }

        /**
         * Execute the effects of current sequence of tokens.
         *
         * @param object $input       the input Filer or Storage object
         * @param object $filer  the Filer object which will receive outputs and settings
         *
         * @return bool true if all OK and token sequence is emptied, else an error occured
         */
        public function output(object &$filer)
        {
            $result = true;
            foreach ($this->curTokens as $token) {
                if (!$token->output($this, $filer)) {
                    $result = false;
                }
            }
            unsetArrayContent($this->curTokens);
            return $result;
        }

        /**
         * Debugging echo of current character and line info.
         * To activate this echo, set the "debug" environment variable to "1" before launching php.
         *
         * @return nothing
         */
        public function debugEcho(string $char): void
        {
            if (getenv("debug") == "1") {
                echo $char;
            }
        }

        /**
         * Append the token sequence corresponding to a text content to current tokens list.
         * Adjust current, future and previous characters lists.
         * Adjust position in a reference Filer when needed (most probably going to next line)
         * 
         * Assumes:
         * - language list has been preprocessed ($languageList ready)
         * - $filer is positionned on the content beginning
         * 
         */
        public function tokenize(string $text, Filer &$filer): void
        {
            $storage = new Storage();
            $storage->setInputBuffer($text);
            $this->currentChar = $storage->getCurrentChar();

            // now interpret current character
            // important functions are :
            // - storeCurrentGoNext() : store current character into current text and go to next character
            // - gotoNextLine() : skip over next characters until end of current line
            // - fetchNextChars() : fetch more characters from input while not changing read position
            // - fetchToken() : try to recognize a token starting at current character
            // 'fetch' means that more characters will be taken from input if needed, but current read position will not change
            while ($this->currentChar != null) {
                // Identify token starting at this character, or store in current text
                $token = null;
                switch ($this->currentChar) {
                    case '.':
                        // ignore when followed by space or EOL
                        $nextChar = $storage->fetchNextChars(1); // pre-read next character
                        if (($nextChar != ' ') && ($nextChar != "\n") && ($nextChar != "\t")) {
                            $token = $this->fetchToken($storage);
                            // special handling for '.languages' if needed
                            if ((!$this->languageSet) && ($token !== null)) {
                                if ($token->identifyInBuffer('.languages', 0)) {
                                    // language are set by preprocessing, simply acknowledge the directive
                                    $this->languageSet = true;
                                    $filer->setLanguage($this->languageList, ALL);
                                    $filer->gotoNextLine();
                                    $storage->gotoNextLine();
                                }
                                // ignore 1) any token before .languages is set 2) .languages directive itself
                                $token = null;
                            } // keep token when after .languages
                        }
                        if ($token == null) {
                            $this->storeCurrentGoNext($storage);
                        }
                        break;
                    case '#':
                        if ($this->languageSet) {
                            // eliminate trivial case (not preceded by EOL)
                            if ($storage->getPrevChar() == "\n") {
                                // find maching heading from preprocessed
                                $headingsArray = $this->allHeadingsArrays[$filer->current()];
                                $heading = $headingsArray->findByLine($filer->getCurrentLineNumber());
                                if ($heading !== null) {
                                    $token = new TokenHeading($heading);
                                    break;
                                }
                            }
                            if ($token == null) {
                                $this->storeCurrentGoNext($storage);
                            }
                        }
                        break;
                    case '`':
                    case '"':
                        if ($this->languageSet) {
                            // start of escaped text?
                            $token = $this->fetchToken($storage);
                            if ($token === null) {
                                if ($this->trace) {
                                    $filer->error("unrecognized escape character [{$this->currentChar}] in text, should translate into a token", __FILE__, __LINE__);
                                }
                                $this->storeCurrentGoNext($storage);
                            }
                        } 
                        break;
                    case ' ':
                    case "\n":
                        if ($this->languageSet) {
                            $token = $this->fetchToken($storage);
                            if ($token == null) {
                                $this->storeCurrentGoNext($storage);
                            }
                        } 
                        break;
                    default:
                        if ($this->languageSet) {
                            $this->storeCurrentGoNext($storage);
                        }
                        break;
                }
                if ($token) {
                    $this->appendTextToken(); // stack a text token when needed
                    $token->processInput($this, $storage, $filer); // let token process any input
                    if ($token->ouputNow($this)) { // and do tokens output now if needed
                        $this->output($filer);
                    }
                    unset($token);
                } 
            }
            unset($storage);
        }

        /**
         * Process an opened filer, input and output files ready.
         * Builds sequences of tokens while reading input character by character,
         * and periodically updates outputs when meeting some directives.
         */
        public function process(Filer $filer): bool
        {
            $relFilename = $filer->current();
            $this->currentChar = '';
            $this->resetCurrentText();
            $this->curTokens = [];

            // skip right after languages directive (only at first time)
            if ($this->waitLanguages && !$this->languageSet) {
                $curLineNumber = $this->allStartingLines[$relFilename];
                do {
                    $this->currentChar = $filer->getNextChar();
                    if ($this->currentChar === null) return false;
                } while ($filer->getCurrentLineNumber() <= $curLineNumber);
                $this->languageSet = true;
            }
            $filer->setLanguage($this->languageList, ALL);

            // read first line and character
            $lineContent = $filer->getLine();
            while ($lineContent !== null) {
                $curLineNumber = $filer->getCurrentLineNumber();
                $this->tokenize($lineContent, $filer);
                // set current character to the next line
                $filer->getNextChar();
                $lineContent = $filer->getLine();
            }
            // process anything left
            $this->appendTextToken();
            $this->output($filer);
            $this->resetCurrentText();
            return true;
        }

        /**
         * Pushes current language and set to given name.
         * Name must be an index to $mlmdTokens: 'all', 'ignore', 'default' and each language code.
         *
         * @param string $name the new language code to set as current
         *
         * @return bool true if name exists and stack has been updated, false if not
         */
        public function pushLanguage(string $name, object &$filer): bool
        {
            // name must exist as an index
            if (empty($name)) {
                $name = DEFLT;
            }
            if (\array_key_exists($name, $this->mlmdTokens)) {
                array_push($this->languageStack, $this->curLanguage);
                $this->curLanguage = $name;
                // handle 'ignore'
                if ($name == IGNORE) {
                    $this->ignoreLevel += 1;
                    // update Filer status
                    $filer->setIgnoreLevel($this->ignoreLevel);
                }
                // update Filer status (will be ignored if ignore level > 0)
                return $filer->setLanguage($this->languageList, $name);
            }
            $filer->error("unknown language '$name'");
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
                $this->curLanguage = ALL;
                $this->ignoreLevel = 0;
            }
            // handle when popping 'ignore'
            if ($popped == IGNORE) {
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
         * Ready output files for current languages settings and opened input file.
         */
        public function readyOutputs(object $filer): bool
        {
            return $filer->readyOutputs($this->languageList);
        }

        /**
         * Ready all headings, numberings and languages by reading
         * only related directives from all input files.
         */
        public function preProcess(object $filer): void
        {
            resetArray($this->allHeadingsArrays);
            resetArray($this->allNumberings);
            $languagesToken = &$this->mlmdTokens['languages']; // shortcut
            $numberingToken = &$this->mlmdTokens['numbering']; // shortcut
            $topnumberToken = &$this->mlmdTokens['topnumber']; // shortcut
            Heading::init();// reset global headings numbering to 0
            $languageSet = false; // remember if the .languages directive has been read
            $defaultNumberingScheme = $this->defaultNumberingScheme; // start with CLI parameter scheme if any
            // explore each input file ($filer is iterable and returns relative filenames and index)
            foreach ($filer as $index => $relFilename) {
                $filename = $filer->getInputFile($index); // full file path
                if ($filename == null) {
                    continue;
                }
                $file = fopen($filename, 'rb');
                if ($file === false) {
                    $filer->error("could not open [$filename]", __FILE__, __LINE__);
                    continue;
                }
                $headingsArray = new HeadingArray($relFilename);
                $curLineNumber = 0;
                $this->allTopNumbers[$relFilename] = 0;
                // loop on each file line@
                do {
                    $text = getNextLineTrimmed($file, $curLineNumber);
                    if (!$text) {
                        break;
                    }
                    // handle .languages directive before anything else
                    if ($languagesToken->identifyInBuffer($text, 0)) {
                        $languageParams = trim(mb_substr($text, $languagesToken->getLength()));
                        $this->setLanguagesFrom($languageParams, $filer);
                        $languageSet = true;
                        // remember line number for languages directive
                        $this->allStartingLines[$relFilename] = $curLineNumber;
                        continue;
                    }
                    // ignore any line before the .languages directive
                    if ($languageSet === false) {
                        continue;
                    }
                    // handle .topnumber directive
                    if ($topnumberToken->identifyInBuffer($text, 0)) {
                        $this->allTopNumbers[$relFilename] = (int)(mb_substr($text, $topnumberToken->getLength()));
                    }
                    // handle .numbering directive
                    if ($numberingToken->identifyInBuffer($text, 0)) {
                        if (!empty($this->allNumberingScheme[$relFilename])) {
                            echo "WARNING: numbering scheme overloading for $relFilename\n";
                        }
                        $this->allNumberingScheme[$relFilename] = trim(mb_substr($text, $numberingToken->getLength()));
                        $this->allNumberings[$relFilename] = new Numbering($this->allNumberingScheme[$relFilename]);
                        if ($defaultNumberingScheme == null) {
                            $defaultNumberingScheme = $this->allNumberingScheme[$relFilename];
                        }
                    }
                    // store headings
                    if (($text[0] ?? '') == '#') {
                        $heading = new Heading($text, $curLineNumber, $this);
                        $headingsArray[] = $heading;
                    }
                } while (!feof($file));
                fclose($file);

                // force fake line number for languages directive if none
                if (!isset($this->allStartingLines[$relFilename])) {
                    $this->allStartingLines[$relFilename] = 0;
                }

                // force a level 1 object if no headings
                if (count($headingsArray) == 0) {
                    $heading = new Heading('# ' . $relFilename, 1, $this);
                    $headingsArray[] = $heading;
                }
                $this->allHeadingsArrays[$relFilename] = $headingsArray;
                unset($headingsArray);
            } // next file

            // check every file gets a numbering if there is a default one
            if ($defaultNumberingScheme != null) {
                foreach ($filer as $relFilename) {
                    if (! \array_key_exists($relFilename, $this->allNumberings)) {
                        $this->allNumberingScheme[$relFilename] = $defaultNumberingScheme;
                        $this->allNumberings[$relFilename] = new Numbering($defaultNumberingScheme, $this);
                        $this->allNumberings[$relFilename]->setLevelNumber(1, $this->allTopNumbers[$relFilename]);
                    }
                }
            }
            // prepare headings index cross reference
            foreach ($filer as $relFilename) {
                $headingsArray = $this->allHeadingsArrays[$relFilename];
                foreach ($headingsArray as $index => $heading) {
                    $heading->setIndex($index);
                }
            }
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
            if ($this->languageList == null) {
                $this->languageList = new LanguageList();
            }
            $result = $this->languageList->setFrom($parameters);
            if ($result) {
                foreach ($this->languageList as $index => $language) {
                    if (!\array_key_exists($language['code'], $this->mlmdTokens)) {
                        $this->mlmdTokens[$language['code']] = new TokenOpenLanguage($language['code']);    
                    }
                }
                $this->languageSet = isset($index);
                if ($filer->hasOpenedFile()) {
                    $filer->readyOutputs($this->languageList);
                }
            }
            return $result;
        }

        /**
         * Set the default numbering scheme before preprocessing.
         *
         * @param string $scheme a string containing numbering scheme.
         *
         * @return nothing
         */
        public function setNumbering(string $scheme): void
        {
            $this->defaultNumberingScheme = $scheme;
        }

        /**
         * Return the text line for a given heading in current file, without the '#' prefixes
         * Handle numbering scheme and current numbering progress.
         * 
         * @see HeadingArray class
         */
        public function getHeadingText(Filer &$filer, Heading &$heading): ?string
        {
            $relFilename = $filer->current();
            return $this->allHeadingsArrays[$relFilename]->getHeadingText($heading->getIndex(), $this->allNumberings[$relFilename], $filer);
        }

    }
}
