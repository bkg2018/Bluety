<?php

/**
 * Multilingual Markdown generator - Lexer class.
 *
 * The Lexer is responsible for transforming an UTF-8 content into a sequence of tokens
 * and send them to output through a Filer. It also sentralizes most of the generation
 * parameters like TOC parameters, heading numbering scheme, output mode. It preprocesses
 * the list of files to recognize the languages list, the available headings in each file
 * and other things like the first significant text line of each file or the current
 * language stack.
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

    require_once 'Constants.php';

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
        private $curLanguage = DEFLT;           /// name of current language token (index in $mlmdTokens)
        private $ignoreLevel = 0;               /// number of opened 'ignore', do not output anything when this variable is not 0
        private $currentChar = '';              /// current character, can be changed by token input processing
        private $currentText = '';              /// Current text flow, to be stored as a text token before next tokken
        private $curTokens = [];                /// Current tokens file, will be regularly sent to output when languages stack is empty
        private $trace = false;                 /// flag for a few prints or warnings control
        private $defaultNumberingScheme = '';   /// default numbering scheme, set by '-numbering' CLI parameter
        private $eolCount = 0;                  /// number of previous successives EOL tokens
        private $languageCount = 0;             /// number of unclosed open language tokens
        private $emptyContent = true;           /// false after the first non empty text token

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
            $this->mlmdTokens['fence']      = new TokenEscaperFence();              ///  ``` - MD code fence
            $this->mlmdTokens['```']        = new TokenEscaperTripleBacktick();     ///  ``` - MD triple backtick escaping, must be checked later than TokenEscaperFence
            $this->mlmdTokens['``']         = new TokenEscaperDoubleBacktick();     ///  ``  - MD double backtick escaping, must be checked later than TokenEscaperTripleBacktick
            $this->mlmdTokens['`']          = new TokenEscaperSingleBacktick();     ///  `   - MD single backtick escaping, must be checked later than TokenEscaperDoubleBacktick
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
            if (!$input->adjustNextLine()) {
                $this->currentChar = $input->getNextChar();
            }
        }

        /**
         * Add current text as token if not empty, then reset.
         */
        public function appendTextToken(Filer $filer): void
        {
            if (!$this->emptyText) {
                $text = new TokenText($this->currentText);
                $this->appendToken($text, $filer);
                unset($text);// free this reference
                $this->resetCurrentText();
            }
        }

        /**
         * Delete last token from stack.
         * Type can be checked for security.
         */
        public function deleteLastToken(?string $msg = null, TokenType $type = null): bool
        {
            $count = count($this->curTokens);
            if ($count <= 0) {
                return false;
            }
            $prevToken = &$this->curTokens[$count - 1];
            if ($type !== null) {
                if (!$prevToken->isType($type)) {
                    unset($prevToken);
                    return false;
                }
            }
            if (getenv('debug')) {
                echo '<DELETETOKEN ' . ($msg ?? $prevToken->getTypeName()) . ">\n";
            }
            // update language stack count
            if ($prevToken->isType(TokenType::CLOSE_DIRECTIVE)) {
                $this->openLanguage();// assume a close matches a previous open 
            } elseif ($prevToken->isType(TokenType::OPEN_DIRECTIVE)) {
                $this->closeLanguage();
            }
            array_pop($this->curTokens);
            array_values($this->curTokens);
            $count -= 1;
            if ($prevToken->isType(TokenType::EOL)) {
                $this->eolCount -= 1;
            } elseif ($count >= 2) {
                $this->recalculatePreviousEols();
            }
            unset($prevToken);
            return true;
        }

        /**
         * Make sure there is a single EOL token at the end
         */
        public function ensureEndingEOL($filer): void
        {
            $lastEolIndex = -1;
            for ($index = count($this->curTokens) - 1 ; $index >= 0 ; $index -= 1) {
                if ($this->curTokens[$index]->isType(TokenType::EOL)) {
                    if ($lastEolIndex > $index) {
                        $this->deleteLastToken();
                    }
                    $lastEolIndex = $index;
                } else break;
            }
            if ($lastEolIndex < 0) {
                $this->appendTokenEOL($filer);
            }
        }

        /**
         * Calculate the number of previous successive EOLs tokens.
         */
        public function recalculatePreviousEols(): void
        {
            $this->eolCount = 0;
            // Go backward in tokens to look for successive EOLs
            for ( $tokenIndex = count($this->curTokens) - 1 ; $tokenIndex >= 0 ; $tokenIndex -= 1) {
                $curToken = $this->curTokens[$tokenIndex];
                // count if it's an EOL
                if ($curToken->isType(TokenType::EOL)) {
                    $this->eolCount += 1;
                    continue;
                }
                // else check if it's a close language token
                $languages = [ALL, $this->curLanguage];
                if ($curToken->isType(TokenType::CLOSE_DIRECTIVE)) {
                    // go back to find the matching  open
                    $eolCount = 0;
                    $nonEolFound = false;
                    $countOK = false;
                    for ($index = $tokenIndex - 1 ; $index >= 0 ; $index -= 1) {
                        $subCurToken = $this->curTokens[$index];
                        if ($subCurToken->isType(TokenType::OPEN_DIRECTIVE)) {
                            if (in_array($subCurToken->getLanguage(), $languages)) {
                                $countOK = true;
                            }
                            break;//s toip counting in the open/close
                        }
                        if ($subCurToken->isType(TokenType::EOL) && !$nonEolFound) {
                            $eolCount += 1;
                            continue;// continue the internal for() inside open/close block
                        }
                        $nonEolFound = true;
                    }
                    // found EOLs?
                    if ($eolCount > 0 && $countOK) {
                        $this->eolCount += $eolCount;
                        // stope counting now if a non EOL was in the open/close block
                        if ($nonEolFound) {
                            break; // finished
                        }
                        // skip the open/close all EOL tokens block and continue with previous tokens
                        $tokenIndex = $index - 1; // skip the OPEN/CLOSE
                        continue;
                    }
                }
                if ($curToken->isType(TokenType::OPEN_DIRECTIVE)) {
                    // skip if it's an open all, they're neutral for EOL counting
                    if (in_array($curToken->getLanguage(), $languages)) {
                        continue;
                    }
                }
                // anything else stops the counting
                break;
            }
        }

        /**
         * Count one more open language token.
         */
        public function openLanguage(): void
        {
            $this->languageCount += 1;
        }
        /**
         * Uncount one open language token.
         */
        public function closeLanguage(): void
        {
            $this->languageCount -= 1;
            if ($this->languageCount < 0) {
                $this->languageCount = 0;
            }
        }

        /**
         * Store a token in current tokens array.
         *
         * Cleaning is also done:
         *
         * - do not append more than two successive EOL tokens, ignoring close token
         * - cancel the text token before an EOL if it only hold spacing characters
         * - cancel the EOL before if it follows text or close tokens
         * - close language stack when an EOL is appended after an EOL (empty line)
         *
         * This is used by tokens in their processInput() work to append themselves
         * or other tokens to the lexer current flow of tokens.
         */
        public function appendToken(object &$token, Filer $filer): void
        {
            $nullToken      = new Token(TokenType::UNKNOWN);
            $count          = count($this->curTokens);
            $prevToken      = $count >= 1 ? $this->curTokens[$count - 1] : $nullToken;
            $prevPrevToken  = $count >= 2 ? $this->curTokens[$count - 2] : $nullToken;
            if ($token->isType(TokenType::EOL)) {
                // Immediately following another EOL in root language mode?
                if (($this->languageCount == 0) && $prevToken->isType(TokenType::EOL)) {
                    // replace the two EOLs by open all / eol / eol / close
                    $this->deleteLastToken();
                    $this->appendToken($this->mlmdTokens[ALL], $filer);
                    $this->appendTokenEOL($filer);
                    $this->appendTokenEOL($filer);
                    $this->appendToken($this->mlmdTokens['close'], $filer);
                    return;
                }
                //  <EOL><space only text> ?
                if ($prevToken->isType([TokenType::TEXT, TokenType::ESCAPED_TEXT])) {
                    if ($prevPrevToken->isType(TokenType::EOL) && $prevToken->isSpacing()) {
                        $this->deleteLastToken('SPACE');
                        $this->recalculatePreviousEols();
                        $this->appendTokenEOL($filer);
                        return;
                    }
                }
                // EOL candidate
                $this->recalculatePreviousEols();
                if ($this->eolCount >= 2) {
                    return; // already an empty line
                }
/*                 // <EOL><close> ?
                if ($prevPrevToken->isType(TokenType::EOL) && $prevToken->isType([TokenType::CLOSE_DIRECTIVE])) {
                    $this->deleteLastToken('CLOSE');
                    $this->deleteLastToken('EOL');
                    $this->appendToken($this->mlmdTokens['close'], $filer);
                    $this->eolCount = 0;
                    // fall through
                } */
                $this->eolCount += 1; // will add EOL
            } else {
                $this->eolCount = 0; // will add not an EOL
            }
/*             if ($token->isType(TokenType::OPEN_DIRECTIVE) && ($prevToken !== null) && ($prevToken->isType(TokenType::EOL) && ($prevPrevToken !== null))) {
                // OPEN LANGUAGE candidate
                // <text><eol> or <close><eol> and trying <open language> : delete the <eol>
                $prevPrevToken = $this->curTokens[$count - 2];
                if ($prevPrevToken->isType([TokenType::TEXT, TokenType::ESCAPED_TEXT, TokenType::CLOSE_DIRECTIVE])) {
                    $this->deleteLastToken('EOL');
                    $this->recalculatePreviousEols();
                    $this->appendToken($token, $filer);
                    return;
                }
            } */
            // check if some text has been written
            if (!$token->isEmpty()) {
                $this->emptyContent = false;
            }
            if ($token->isType(TokenType::CLOSE_DIRECTIVE)) {
                $this->closeLanguage();
                $this->recalculatePreviousEols();
            } elseif ($token->isType(TokenType::OPEN_DIRECTIVE)) {
                $this->openLanguage();
                $this->recalculatePreviousEols();
            } /* elseif ($token->isType([TokenType::TEXT, TokenType::ESCAPED_TEXT])) {
                if (($this->languageCount == 0) && ($this->curLanguage !== DEFLT)) {
                    $this->appendToken($this->mlmdTokens[DEFLT], $filer);
                    $this->appendToken($token, $filer);
                    $this->appendToken($this->mlmdTokens['close'], $filer);
                    return;
                }
            } */
            $this->curTokens[] = $token;
            if (getenv('debug')) {
                echo '<TOKEN: ' . \get_class($token) . ">\n";
            }
        }

        /**
         * Store and end-of-line token (EOL).
         */
        public function  appendTokenEOL(Filer $filer): void
        {
            if (!$this->emptyContent) {
                $this->appendToken($this->mlmdTokens['eol'], $filer);
            }
        }

        /**
         * Check if token stack must be simplified before appending an open language token.
         * If an 'open' token immediately follows a single EOL after a 'close' or a text, then the
         * EOL can be deleted :
         *
         * INPUT stack:  <close> <eol> <<future open>>
         * OUTPUT stack: <close> <<future open>>
         * 
         * INPUT stack:  <text> <eol> <<future open>>
         * OUTPUT stack: <text> <<future open>>
         */
        public function adjustCloseOpenSequence(): void
        {
            $count = count($this->curTokens);
            if ($count >= 2) {
                // test if we delete previous spacing text token
                $prevToken = $this->curTokens[$count - 1];
                if ($prevToken->isType([TokenType::EOL])) {
                    $prevToken = $this->curTokens[$count - 2];
                    if ($prevToken->isType([TokenType::CLOSE_DIRECTIVE, TokenType::TEXT, TokenType::ESCAPED_TEXT])) {
-                        $this->deleteLastToken();
                    }
                }
            }
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
                    if ($token->isType(TokenType::ESCAPED_TEXT)) {                        
                        return $token->newInstance();
                    }
                    return $token;
                }
            }
            return null;
        }

        /**
         * Execute the effects of current sequence of tokens.
         *
         * @param object $input  the input Filer or Storage object
         * @param object $filer  the Filer object which will receive outputs and settings
         *
         * @return bool true if all OK and token sequence is emptied, else an error occured
         */
        public function output(Filer &$filer)
        {
            $result = true;
            $eolCount = 0;
            foreach ($this->curTokens as $token) {
                if (!$token->output($this, $filer)) {
                    $result = false;
                }
                $eolCount = ($token->isType(TokenType::EOL) && $filer->outputStarted()) ? $eolCount + 1 : 0;
                if ($eolCount >= 2) {
                    if (count($this->languageStack) <= 1) {
                        //$filer->flushOutput();
                    }
                }
            }
            unsetArrayContent($this->curTokens);
            unset($this->curTokens);
            $this->curTokens = [];
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
         * @param string $text        t he text to tokenize, preferably a single line but not necessary
         * @param Filer  $filer       the Filer for any file reference in variable expansion
         * @param bool   $allowOutput flag to allow output in tokenization,
         *                            should be disabled in recursive tokenization (e.g. TokenHeading::processInput)
         */
        public function tokenize(string $text, Filer &$filer, bool $allowOutput): void
        {
            $storage = new Storage($text);
            $this->currentChar = $storage->getCurrentChar();

            // now interpret current character
            // important functions are :
            // - storeCurrentGoNext() : store current character into current text and go to next character
            // - gotoNextLine() : skip over next characters until end of current line
            // - fetchNextChars() : fetch more characters from input while not changing read position
            // - fetchToken() : try to recognize a token starting at current character
            // 'fetch' means that more characters will be taken from input if needed, but current read position will not change
            do {
                // Identify token starting at this character, or store in current text
                $token = null;
                switch ($this->currentChar) {
                    case null:
                        $token = &$this->mlmdTokens['eol'];
                        break;
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
                                    $filer->setLanguage($this->languageList, DEFLT);
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
                    // save current text in a token, then let new token process input 
                    $this->appendTextToken($filer);
                    $token->processInput($this, $storage, $filer);
                    $this->setCurrentChar($storage->getCurrentChar());
                    // if appropriate, output the tokens stack
                    if ($allowOutput && $token->ouputNow($this)) {
                        $this->output($filer);
                    }
                    unset($token);
                }
            } while ($this->currentChar != null);
            // process anything left
            $this->appendTextToken($filer);
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
                $startLineNumber = $this->allStartingLines[$relFilename];
                do {
                    $lineContent = $filer->getLine(); // read until eol and increment line number
                    if ($this->currentChar === null) return false;
                } while ($filer->getCurrentLineNumber() < $startLineNumber);
                $this->languageSet = true;
            } else {
                $lineContent = $filer->getLine();
            }
            $filer->setLanguage($this->languageList, DEFLT);

            while ($lineContent !== null) {
                $curLineNumber = $filer->getCurrentLineNumber(); // just for debugging checks
                if (getenv("debug")) {
                    echo "[$curLineNumber] $lineContent\n";
                }
                $this->tokenize($lineContent, $filer, true);
                $this->appendTokenEOL($filer);
                // empty line is a good place to send tokens to output
                if ((count($this->languageStack) == 0) && ($this->eolCount == 2)) {
                    //$this->output($filer);
                    //$filer->flushOutput();
                }
                $lineContent = $filer->getLine();
            }
            // process anything left
            $this->appendTextToken($filer);
            $this->ensureEndingEOL($filer);
            $this->output($filer);
            $filer->flushOutput();
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
                $this->curLanguage = $popped;
            } else {
                if ($count == 1) {
                    $popped = array_pop($this->languageStack);
                }
                $this->curLanguage = DEFLT;
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
            $fenceToken     = &$this->mlmdTokens['fence']; // shortcut
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
                $this->allTopNumbers[$relFilename] = 1;
                // loop on each line
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
                        $this->allStartingLines[$relFilename] = $curLineNumber + 1;
                        continue;
                    }
                    // ignore any line before the .languages directive
                    if ($languageSet === false) {
                        continue;
                    }
                    // handle code fences
                    if ($fenceToken->identifyInBuffer($text,0)) {
                        $firstLine = $curLineNumber;
                        do {
                            $text = getNextLineTrimmed($file, $curLineNumber);
                        } while ($text !== null && !$fenceToken->identifyInBuffer($text,0));
                        if ($text === null) {
                            $filer->error("Code fence (```) unable to find closing code fence", $filename, $firstLine);
                        }
                    }
                    // handle .topnumber directive
                    if ($topnumberToken->identifyInBuffer($text, 0)) {
                        $this->allTopNumbers[$relFilename] = (int)(mb_substr($text, $topnumberToken->getLength()));
                    }
                    // handle .numbering directive
                    if ($numberingToken->identifyInBuffer($text, 0)) {
                        if (!empty($this->allNumberingScheme[$relFilename])) {
                            $filer->warning("numbering scheme overloading for $relFilename", $filename, $firstLine);
                        }
                        $this->allNumberingScheme[$relFilename] = trim(mb_substr($text, $numberingToken->getLength()));
                        $this->allNumberings[$relFilename] = new Numbering($this->allNumberingScheme[$relFilename]);
                        if ($defaultNumberingScheme == null) {
                            $defaultNumberingScheme = $this->allNumberingScheme[$relFilename];
                        }
                    }
                    // store headings
                    if (($text[0] ?? '') == '#') {
                        $heading = new Heading($text, $curLineNumber, $filer);
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
                    $heading = new Heading('# ' . $relFilename, 1, $filer);
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
                        $this->allNumberings[$relFilename] = new Numbering($defaultNumberingScheme, $filer);
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
            return $this->allHeadingsArrays[$relFilename]->getHeadingText($heading->getIndex(), $this->allNumberings[$relFilename] ?? null, $filer);
        }

        /**
         * Return all headings arrays.
         */
        public function getAllHeadingsArrays(): array
        {
            return $this->allHeadingsArrays;
        }

        /**
         * Return headings array for a given file.
         * The index is the relative file name vs main file directory.
         */
        public function getCurrentHeadingsArray(string $filename): ?array
        {
            if (\array_key_exists($filename, $this->allHeadingsArrays)) {
                return $this->allHeadingsArrays[$filename];
            }
            return null;
        }

        /**
         * return numbering object for a given file.
         * The index is the relative file name vs main file directory.
         */
        public function getNumbering(string $filename): ?object
        {
            if (\array_key_exists($filename, $this->allNumberings)) {
                return $this->allNumberings[$filename];
            }
            return null;
        }

    }
}
