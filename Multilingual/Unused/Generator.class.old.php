<?php

/**
 * Multilingual Markdown generator - Generator class
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
 * @package   mlmd_main_generator_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    mb_internal_encoding('UTF-8');

    require_once 'Logger.interface.php';
    require_once 'Heading.class.php';
    require_once 'HeadingArray.class.php';
    require_once 'Numbering.class.php';
    require_once 'FileUtilities.php';
    require_once 'Lexer.class.php';


    //MARK: Generator class

    /**
     * Generator class.
     * Accept input parameters and files, process all input files and generate output files.
     */
    class GeneratorOLD implements Logger
    {
        // Directives and handling function names
        private static $directives = [
            '.all(('        => 'all',           /// push current section and open all languages section
            '.ignore(('     => 'ignore',        /// push current section and open ignored section
            '.default(('    => 'default',       /// push current section and open default text section
            '.(('           => 'default',       /// same as .default((
            '.))'           => 'end',           /// close current section and pop previous section
            '.languages'    => 'languages',     /// declare languages and start files generation
            '.numbering'    => 'numbering',     /// sets the numbering schemes for headings levels
            '.toc'          => 'toc',           /// insert a table of contents
            '.{'            => 'escape'         /// start escaped text
        ];
        private static $openDirectives = [
            '.all(('        => 'all',           /// push current section and open all languages section
            '.ignore(('     => 'ignore',        /// push current section and open ignored section
            '.default(('    => 'default',       /// push current section and open default text section
            '.(('           => 'default',       /// same as .default((
        ];

        private $prevChar = '';                 /// used to detect line and heading start
        private $curChar = '';                  /// current char
        private $curLine = 1;                   /// current line number in current input file

        // Directives status
        private $curLanguage = 'ignore';        // current opened directive (all / <code> / ignore / default)
        private $directiveStack = [];           // stack of previous directives
        private $languagesSet = false;          // ignore any input until '.languages' directive has been processed
        private $emptyOutput = true;            // ignore EOL until something else has been written to files
        private $spaceOnly = true;              // Nothing else but space / tabs since beginning of current line

        // All files headings and numbering for tables of contents (TOC)
        private $allHeadingArrays = [];         // HeadingArray for each relative filename
        private $allNumberings = [];            // Numbering for each relative filename

        // Initial settings
        //private $outputModeName = '';           // from -out command line argument
        //private $numberingScheme = '';          // from -numbering command line argument

        /**
         * Set the main file name.
         * Relay to Filer function.
         *
        public function setMainFilename(string $name = 'README.mlmd'): bool
        {
            return true;
        }*/

        /**
         * Add an input filename.
         *
        public function addInputFile(string $path): bool
        {
            return true;
        }*/

        /**
         * Set the output mode.
         * Default is MD style links.
         *
         * @param string $mode 'htmlold' to set HTML mode (<A name> links and anchors),
         *                     'html' to set HTML mode (<A id> links and anchors),
         *                     'md for MD mode ([]() links and {:# } anchors)
         *
         * @return nothing
         *
        public function setOutputMode(string $mode): void
        {
            if (!OutputModes::isValid($mode)) {
                $this->error("invalid output mode $mode, using \'md\'");
                $mode = 'md';
            }
            $this->outputModeName = $mode;
        }
        */

        /**
         * Set the numbering schemes.
         *
         * @param string $scheme a string containing numbering scheme
         *
         * @return nothing
         *
        public function setNumbering(string $scheme): void
        {
            $this->numberingScheme = $scheme;
        }
        */

        /**
         * Expand variables in a text for a language.
         * - {file} replaced by relative path of generated file ('example.en.md' for 'example.mlmd')
         * - {main} replaced by relative path of main file as declared by '-main='
         * - {language} is replaced by the current language
         *
         * Notices:
         * - {main} won't be replaced if no '-main=' parameter was given to the script.
         * - no expansion in escaped text
         *
         * @param string $text     the original text
         * @param string $basename the base name for current input file ('example' for 'example.mlmd')
         * @param string $language the current language for generation (like 'en')
         *
         * @return string the text with expanded variables
         */
        public function expandVariables(string $text, ?string $basename, string $language): string
        {
            if (empty($text)) {
                return '';
            }
            $extension = (($language == $this->mainLanguage) ? '.md' : ".{$language}.md");
            // parse text, skip escaped parts and expand variables

            // $textParts is an array of strings, $expand  an array of bools.
            // For each escaped/non escaped part of text, $textParts[] holds the text
            // and $expand is true if the text must expand variables.
            // At the end of parsing, the $textParts array is imploded intop a string.
            $textParts = [];
            $expand = [];
            $nbParts = 0;
            $spaceOnly = true;
            $out = '';      // current part
            $maxPos = mb_strlen($text);
            for ($pos = 0; $pos < $maxPos; $pos += 1) {
                $curChar = mb_substr($text, $pos, 1);
                switch ($curChar) {
                    case "\r":
                        // ignore
                        break;
                    case "\n":
                        // inside text, has no special effect simply copy to output
                        $out .= $curChar;
                        $spaceOnly = true;
                        break;
                    case '.':
                        // dot check mlmd escaping  .{ .}
                        if ($this->isMatchingContent('.{', $text, $pos)) {
                            $nbParts += 1;
                            $expand[$nbParts] = true;
                            $textParts[$nbParts] = $out;
                            $out = '';
                            $curChar = ''; // do not store '.{'
                            $pos += 1;
                            do {
                                $out .= $curChar;
                                $pos += 1;
                                if ($pos == $maxPos) {
                                    $curChar = '';
                                    break;
                                } else {
                                    $curChar = mb_substr($text, $pos, 1);
                                }
                            } while (!$this->isMatchingContent('.}', $text, $pos));
                            $nbParts += 1;
                            $expand[$nbParts] = false;
                            $textParts[$nbParts] = $out; // do not store '.}'
                            $pos += 1; // skip '.}'
                            $out = '';
                        } else {
                            // normal dot
                            $out .= $curChar;
                        }
                        $spaceOnly = false;
                        break;
                    case '`':
                        // back-tick start escaped text, check if it's a code fence
                        if ($this->isMatchingContent('```', $text, $pos)) {
                            if (!$spaceOnly) {
                                $this->warning("code fence start is not at the begining of line");
                            }
                            // this is a code fence: save current output as expanded text and reset output
                            $nbParts += 1;
                            $expand[$nbParts] = true;
                            $textParts[$nbParts] = $out;
                            $out = '```';
                            $pos += 3;
                            $curChar = mb_substr($text, $pos, 1);
                            $prev2 = $prev1 = '';
                            // copy output until closing fence ```<eol>
                            do {
                                $out .= $curChar;
                                $prev3 = $prev2;
                                $prev2 = $prev1;
                                $prev1 = $curChar;
                                $pos += 1;
                                if ($pos == $maxPos) {
                                    $curChar = '';
                                    break;
                                } else {
                                    $curChar = mb_substr($text, $pos, 1);
                                }
                            } while ($prev3 != '`' || $prev2 != '`' || $prev1 != '`' || $curChar != "\n");
                            /// record as non-expanded part and reset output
                            $nbParts += 1;
                            $expand[$nbParts] = false;
                            $textParts[$nbParts] = $out . $curChar;
                            $out = '';
                        } elseif ($this->isMatchingContent('``', $text, $pos)) {
                            // double back-tick: skip escaped text until closing double back-tick``
                            $nbParts += 1;
                            $expand[$nbParts] = true;
                            $textParts[$nbParts] = $out;
                            $out = '``';
                            $pos += 2;
                            $curChar = mb_substr($text, $pos, 1);
                            do {
                                $out .= $curChar;
                                $prev1 = $curChar;
                                $pos += 1;
                                if ($pos == $maxPos) {
                                    $curChar = '';
                                    break;
                                } else {
                                    $curChar = mb_substr($text, $pos, 1);
                                }
                            } while ($prev1 != '`' || $curChar != '`');
                            $nbParts += 1;
                            $expand[$nbParts] = false;
                            $textParts[$nbParts] = $out . $curChar;
                            $out = '';
                        } else {
                            // ` : skip escaped text
                            $nbParts += 1;
                            $expand[$nbParts] = true;
                            $textParts[$nbParts] = $out;
                            $out = '';
                            do {
                                $out .= $curChar;
                                $pos += 1;
                                if ($pos == $maxPos) {
                                    $curChar = '';
                                    break;
                                } else {
                                    $curChar = mb_substr($text, $pos, 1);
                                }
                            } while ($curChar != '`');
                            $nbParts += 1;
                            $expand[$nbParts] = false;
                            $textParts[$nbParts] = $out . $curChar;
                            $out = '';
                        }
                        break;
                    default:
                        $out .= $curChar;
                        if ($curChar != ' ' && $curChar != '\t') {
                            $spaceOnly = false;
                        }
                        break;
                }
            }
            if (!empty($out)) {
                $nbParts += 1;
                $expand[$nbParts] = true;
                $textParts[$nbParts] = $out;
            }

            // expand variables in non escaped text
            for ($part = 1; $part <= $nbParts; $part += 1) {
                if ($expand[$part]) {
                    $text = str_replace('{file}', $basename . $extension, $textParts[$part]);
                    if ($this->mainFilename !== null) {
                        $text = str_replace('{main}', $this->mainFilename . $extension, $text);
                    }
                    $textParts[$part] = str_replace('{language}', $language, $text);
                }
            }

            // join all parts
            $text = implode('', $textParts);
            return $text;
        }

        /**
         * Read an UTF-8 character from input file, return null if end of file.
         * Characters are read in buffer until an end of paragraph (empty line) is found.
         *
         * @return null|string new current character ('\n' for EOL),  null when file and buffer are finished.
         */
        private function getChar(): ?string
        {
            // any  character left in current buffer?
            if ($this->lineBufPos < $this->lineBufLength - 1) {
                $this->lineBufPos += 1;
            } else {
                // no: read until empty line (or EOF)
                $this->lineBuf = '';
                do {
                    $line = fgets($this->inFile);
                    // EOF?
                    if (!$line) {
                        // return null now if buffer empty
                        if (empty($this->lineBuf)) {
                            $this->curChar = null;
                            return null;
                        }
                        // else stop read loop
                        break;
                    } else {
                        // delete Windows CR and store
                        $line = \str_replace("\r", '', $line);
                        $this->lineBuf .= $line;
                    }
                // read until empty line
                } while ($line != "\n");
                // init buffer scan
                $this->lineBufPos = 0;
                $this->lineBufLength = mb_strlen($this->lineBuf);
            }
            // adjust status
            $this->prevChar = $this->curChar;
            if ($this->prevChar == "\n") {
                $this->curLine += 1;
            }
            // get next utf-8 char
            $this->curChar = mb_substr($this->lineBuf, $this->lineBufPos, 1);
            //$this->debugEcho();
            return $this->curChar;
        }

        /**
         * Parser Tool: get UTF-8 characters from current file until one given character is found.
         * Optionally stops on EOL. Returns all content before the end marker, and the marker
         * is the new current character.
         *
         * @param string $marker the ending character (one UTF-8 character allowed)
         * @param bool   $eol    true to stop if end of line is found
         *
         * @return string the characters read until the marker. The marker or EOL itself is not returned
         *                but is available as $this->curChar. Return empty string if already on marker.
         *                If $eol is set to true, the search stops if an end-of-line is found even
         *                if the marker has not been found.
         */
        private function getCharUntil(string $marker, bool $eol): string
        {
            $content = '';
            $this->getChar();/// read first title character, null if end of file
            while (($this->curChar !== null) && ($this->curChar !== $marker)) {
                if ($eol && ($this->curChar == "\n")) {
                    break;
                }
                $content .= $this->curChar ?? '';
                $this->getChar(); // next char
            }
            return $content;
        }

        /**
         * Parser Tool: check if current and next characters match a string in current line buffer.
         *
         * @param string $marker the string to match, starting at current character
         *
         * @return bool true if marker has been found at current place
         */
        public function isMatching(string $marker): bool
        {
            $markerLen = mb_strlen($marker);
            $content = mb_substr($this->lineBuf, $this->lineBufPos, $markerLen);
            return strcmp($content, $marker) == 0;
        }

        /**
         * Parser Tool: check if current and next characters match a string in a content.
         *
         * @param string $marker  the string to match starting with current character
         * @param string $content the content to search in
         * @param int    $pos     the current position in $content
         *
         * @return bool true if marker has been found at current place
         */
        private function isMatchingContent(string $marker, string $content, int $pos): bool
        {
            $markerLen = mb_strlen($marker);
            $subcontent = mb_substr($content, $pos, $markerLen);
            return strcmp($subcontent, $marker) == 0;
        }

        /**
         * Parser Tool: read content until an ending marker is found, including EOLs.
         * The returned string includes the end marker and reading is ready for next character.
         * Carriage return (\r) are deleted from content, line-feeds (\n) are sent back in content.
         * The content is returned without ending marker if the end of file is met before.
         *
         * @param string $marker the string to find
         *
         * @return string The content found, including the marker and any end-of-line on the way
         */
        private function getContentUntil(string $marker): string
        {
            $markerLen = mb_strlen($marker);
            $content = $this->curWord . ($this->curChar ?? '');
            $this->resetParsing();
            do {
                $this->getChar();
                if ($this->curChar != "\r") {
                    $content .= $this->curChar;
                }
            } while (
                ($this->curChar !== null) // beware character '0' would be interpreted as false
                && (mb_substr($content, -$markerLen, $markerLen) != $marker)
            );
            return $content;
        }
        /**
         * Parser Tool: skip a number of characters in stream.
         *
         * @param int $number the number of characters to skip
         *
         * @return nothing
         */
        private function skipChar(int $number): void
        {
            do {
                $this->getChar();
                $number -= 1;
            } while ($number > 0);
        }

        /**
         * Debugging echo of current character and line info.
         * To activate this echo, set the "debug" environment variable to "1".
         *
         * @return nothing
         */
        private function debugEcho(): void
        {
            if (getenv("debug") == "1") {
                if (($this->curChar !== null) && ($this->prevChar == "\n")) {
                    echo "[{$this->curLine}]:";
                }
                echo $this->curChar;
            }
        }

        //MARK: Directives
        // These functions are called when the corresponding directive is found, and
        // receive a string parameter when appropriate.

        /**
         * Directive .all(( handling. Start to send to all languages files.
         * This directive doesn't need the given parameter.
         *
         * @param string $dummy unused
         *
         * @return nothing
         */
        private function all(?string $dummy): void
        {
            array_push($this->directiveStack, $this->curLanguage);
            $this->curLanguage = 'all';
            $this->resetParsing();
        }

        /**
         * Directive .ignore(( handling. Start to ignore text.
         *
         * @param any $dummy unused
         *
         * @return nothing
         */
        private function ignore(?string $dummy): void
        {
            array_push($this->directiveStack, $this->curLanguage);
            $this->curLanguage = 'ignore';
            $this->resetParsing();
        }

        /**
         * Directive .(( or .default(( - start default text.
         *
         * @param any $dummy unused
         *
         * @return nothing
         */
        private function default(?string $dummy): void
        {
            array_push($this->directiveStack, $this->curLanguage);
            $this->curLanguage = 'default';
            $this->resetParsing();
        }

        /**
         * Directive .)) . Return to previous directive.
         * if the directive is at the end of current line, the closing EOL is eaten
         * so it won't be interpreted and won't appear in output files. This allows
         * separated open/close directives paragraphs.
         *
         * @param any $dummy unused
         *
         * @return nothing
         */
        private function end(?string $dummy): void
        {
            if (count($this->directiveStack) > 0) {
                $this->curLanguage = array_pop($this->directiveStack);
            } else {
                $this->curLanguage = 'all';
            }
            $this->resetParsing();
            // check if end of line
            $eol = true;
            $c = '';
            static $spaces = ["\n", "\r", "\t",' '];
            for ($i = $this->lineBufPos + 1; ($c != "\n") && ($i < $this->lineBufLength); $i += 1) {
                $c = mb_substr($this->lineBuf, $i, 1);
                if (!in_array($c, $spaces)) {
                    $eol = false;
                    break;
                }
            }
            if ($eol) {
                $eol = $i;
                for ($i = $this->lineBufPos + 1; $i < $eol; $i += 1) {
                    $this->getChar();
                }
                $this->curChar = ')';// act as if there was no EOL at all
            }
        }

        /**
         * Directive .<language>(( - start to send to one declared language.
         *
         * @param string $directive the code for language directive.
         *
         * @return nothing
         */
        private function change(?string $directive): void
        {
            $code = mb_strtolower(mb_substr($directive, 1, -2)); // '.en(('  -> 'en'
            if (array_key_exists($code, $this->languages)) {
                array_push($this->directiveStack, $this->curLanguage);
                $this->curLanguage = $code;
                $this->resetParsing();
            }
        }

        /**
         * Directive .languages - declare authorized languages.
         * This directive must be first text on its line, or only be preceeded by spaces or tabs.
         *
         * @param any $dummy unused
         *
         * @return nothing
         */
        private function languages(?string $dummy)
        {
            if (!$this->spaceOnly) {
                // .languages directive not first on line
                $this->outputToFiles($this->curChar);
                $this->resetParsing();
                return;
            }
            $curWord = '';
            $stopNow = false;
            do {
                $this->getChar();
                if ($this->curChar !== null) {
                    switch ($this->curChar) {
                        case "\r":
                            // do not store CR (normalize to unix EOL)
                            break;
                        case "\n":
                            // line feed: stop current directive
                            $stopNow = true; // finish with current word (then, fall-through)
                        case ' ':
                        case ',':
                            // if we have a word, set it as additionnal language
                            if (!empty($curWord)) {
                                $this->addLanguage($curWord);
                                $curWord = '';
                            }
                            break;

                            // store current keyword
                        default:
                            $curWord .= $this->curChar;
                            break;
                    }
                }
            } while (($this->curChar !== null) && !$stopNow);

            // finish languages registration, open files, set initial status
            if ($this->outFilenameTemplate == null) {
                return false;
            }
            foreach ($this->languages as $language => $bool) {
                if ($this->mainLanguage == $language) {
                    $this->outFilenames[$language] = "{$this->outFilenameTemplate}.md";
                } else {
                    $this->outFilenames[$language] = "{$this->outFilenameTemplate}.{$language}.md";
                }
                // open the output file
                $this->outFiles[$language] = fopen($this->outFilenames[$language], 'wb');
                // and record 'change' action for '.<code>((' directive
                $this->directives[".{$language}(("] = 'change';
                $this->openDirectives[".{$language}(("] = 'change';
            }

            // default numbering if needed
            $relFilename = $this->relFilenames[$this->inFilename];
            if (!\in_array($relFilename, $this->allNumberings)) {
                $this->allNumberings[$relFilename] = new Numbering('', $this);
                $this->allNumberings[$relFilename]->setOutputMode($this->outputModeName, $this);
            }

            // set initial status
            $this->resetParsing();
            $this->prevChar = "\n";
            $this->curLanguage = 'all';
            $this->languagesSet = true;
            array_push($this->directiveStack, $this->curLanguage);
        }

        /**
         * .numbering directive handling.
         * Sets the numbering schemes for headings and TOC in current file.
         * The directive must be placed between the .languages directive and the level 1
         * heading or it will miss numbering the headigns already processed.
         *
         *      .numbering m:<prefix>:<symbol><sep>,...
         *
         *      - m        is the heading level to define
         *      - <prefix> is a prefix string for level 1 heading only (e.g. Chapter )
         *      - <symbol> is a number (e.g: `1`) or a letter (e.g: `a`) for this level,
         *                 case (`a` or `A`) is preserved and numbering starts with the given value
         *      - <sep>    is the symbol to use after this level numbering before next level, e.g `.` or `-`
         *                 it is nopt used for last level which is always followed by ')'
         *
         * The .numbering directive has no effect if a global numbering scheme has been set. (Using the
         * -numbering script argument.)
         *
         * @param string $dummy unused
         *
         * @return nothing
         */
        private function numbering(?string $dummy): void
        {
            if (!$this->spaceOnly) {
                // .numbering directive not first on line
                $this->outputToFiles($this->curChar);
                $this->resetParsing();
                return;
            }

            // skip initial space
            $this->curWord = trim($this->getChar());
            // get definition until next space / stop if EOL
            $scheme = $this->getCharUntil(' ', true);

            // Any global scheme?
            if (!empty($this->numberingScheme)) {
                return;
            }
            // set to current file
            $relFilename = $this->relFilenames[$this->inFilename];
            $this->allNumberings[$relFilename] = new Numbering($scheme, $this);
            $this->allNumberings[$relFilename]->setOutputMode($this->outputModeName, $this);
            $this->resetParsing();
        }

        /**
         * Generate a TOC in current output files using the directive parameters and the headings array.
         *
         * The generator has an outputMODE field to format links and anchors in two possibles modes.
         *
         * OLD HTML mode (outputMODE=OutputModes::HTMLOLD):
         *      Headings all have an named anchor prior to the title, with a heading number:
         *          <a name="h12"></a>
         *          ### Heading text
         *      The TOC must link to these anchors using the following model:
         *          [1.2 Heading text](<file>#h12)
         *
         * HTML mode (outputMODE=OutputModes::HTML):
         *      Headings all have an anchor with an id prior to the title, with a heading number:
         *          <a id="h12"></a>
         *          ### Heading text
         *      The TOC must link to these anchors using the following model:
         *          [1.2 Heading text](<file>#h12)
         *
         * MD mode (outputMODE=OutputModes::MD):
         *      Headings have an automatic MD anchor with their cleaned text, and they can define one using {: }:
         *          ### Heading text {: #h12-heading-text}
         *      The TOC must link to these headings using the cleaned text:
         *          [1.2 Heading text](<file>#heading-text)
         *      WARNING: MD method requires that all headings from a given file are unique in their file.
         *      If this is not true then HTML mode is more appropriate and will always work
         *
         * .TOC [level=m-n] [title=m,"<title text>"] [numbering=m:<symbol><sep>[,...]] [format=html|md]
         *
         * level=m-n                   : use headings from level m to n. m defaults to 1, n defaults to 9
         *
         * title=m,"<title text>"      : text for the TOC title with heading level m, language directives can be used
         *
         * Example to number level 1 with uppercase letters, followed by a dash '-', level 2 with a number
         * starting at 1 and followed by a dot, and level 3 as level 2, and with a title heading level
         * of 2 (prefixed with '##'):
         *
         * .TOC level=1-3 title=2,.fr((Table des matières.)).en((Table Of Contents))
         *
         * @param any $dummy unused
         *
         * @return nothing
         */
        private function toc(?string $dummy): void
        {
            if (!$this->spaceOnly) {
                // .toc directive not first on line
                $this->outputToFiles($this->curChar);
                $this->resetParsing();
                return;
            }
            // default parameters
            $title = "Table Of Contents";               // <text>    in title=m,"<text>"
            $titleLevel = 2;                            // m         in title=m,"<text>"
            $startLevel = 2;                            // m         in level=m[-n]
            $endLevel = 4;                              // n         in level=[m]-n
            // skip initial space
            $this->curWord = trim($this->getChar());
            do {
                $this->getChar();

                // add to current word and check keywords
                $this->curWord .= $this->curChar ?? '';
                switch (strtolower($this->curWord)) {
                    case 'title=': // title=m,"<text>"
                        // get level (used later)
                        $titleLevel = (int)$this->getCharUntil(',', true);
                        // parse and set toc title (used later)
                        $title = $this->getChar(); // read "
                        if ($this->curChar != '"') {
                            $this->error("no '\"' around title text, check .toc directive");
                            $this->getCharUntil(' ', true);
                        } else {
                            $title = $this->getCharUntil('"', true);
                        }
                        $this->resetParsing();
                        break;
                    case 'level=':
                        // get definition until next space
                        $def = $this->getCharUntil(' ', true);
                        // find start and end levels (used later in toc generation)
                        $dashpos = mb_stripos($def, '-', 0);
                        if ($dashpos === false) {
                            $startLevel = $endLevel = (int)$def;
                        } else {
                            $startLevel = (int)mb_substr($def, 0, $dashpos);
                            $endLevel   = (int)mb_substr($def, $dashpos + 1, null);
                            if (empty($startLevel)) {
                                $startLevel = 1;
                            }
                            if (empty($endLevel)) {
                                $endLevel = 9;
                            }
                        }
                        $this->resetParsing();
                        break;
                    case ' ':/// separator
                        $this->resetParsing();
                        //fall-through
                    default:
                        break;
                }
            } while ($this->curChar !== null && $this->curChar != "\n");

            // generate TOC title with forced 'toc' anchor
            $this->storeHeading($titleLevel, $title, 'toc');

            // update start/end levels
            foreach ($this->allNumberings as $numbering) {
                $numbering->setLevelLimits($startLevel, $endLevel);
            }

            // generate toc lines for each file, only if start level is 1
            if ($startLevel == 1) {
                foreach ($this->allHeadingArrays as $basename => $headingArray) {
                    $this->storeTOC($headingArray, $startLevel, $endLevel);
                    $this->storeContent("\n", null, false, false, true);// keep EOLs
                }
            } else {
                $this->storeTOC($this->allHeadingArrays[$this->relFilenames[$this->inFilename]], $startLevel, $endLevel);
                $this->storeContent("\n", null, false, false, true);// keep EOLs
            }

            $this->resetParsing();
        }

        /**
         * Escape text directive
         */
        private function escape($dummy): void
        {
            $content = $this->getContentUntil('.}');
            if ($this->languagesSet) {
                $this->outputToFiles($content);
            }
        }

        /**
         * Normalize to UNIX EOL and delete triple EOLs and wrong characters.
         *
         * @param string $text the input text to clean.
         *
         * @return string the cleaned text.
         */
        private function getCleanText(string $text): string
        {
            // normalize to unix eol
            $text = str_replace("\r\n", "\n", $text);
            //reduce triple EOL to double, and trim ending spaces & tabs
            $text = str_replace(
                ["\n\n\n"," \n","\t\n"],
                ["\n\n",  "\n", "\n"],
                $text
            );
            $text = trim($text, "\0\x0B");
            return $text;
        }

        /**
         * Write to an output file, protect against doubled line feeds.
         *
         * @param string $language the language code
         *
         * @return nothing
         */
        private function writeToFile(string $language): void
        {
            // delete superfluous EOLs
            if (array_key_exists($language, $this->lastWritten)) {
                while (substr($this->curOutputs[$language], 0, 1) == "\n" && $this->lastWritten[$language] == "\n\n") {
                    $this->curOutputs[$language] = substr($this->curOutputs[$language], 1);
                }
            }
            // normalize to unix eol
            $this->curOutputs[$language] = $this->getCleanText($this->curOutputs[$language]);
            // send to file and clear the buffer
            if (!empty($this->curOutputs[$language] && isset($this->outFiles[$language]))) {
                if (fwrite($this->outFiles[$language], $this->curOutputs[$language]) === false) {
                    $this->error("cannot write '{$this->curOutputs[$language]}' to file $this->outFilenames[$language]", __FILE__, __LINE__);
                };
                // retain previous last 2 character written
                $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . $this->curOutputs[$language], -2, 2);
                $this->curOutputs[$language] = "";
                $this->emptyOutput = false;
            }
        }

        /**
         * Output a content to current output files.
         * Lines are buffered before being sent, and beginning : ending spaces are trimed.
         * Variables are expanded for language ({file}, {main}, {language}) in each generated file
         *
         * @param string $content  the content to send to outputs buffers, and to files if EOL found
         * @param bool   $flush    true to force sending to files (used at end of file and for escaped text)
         * @param string $basename the base filename to use for {file} replacement
         * @param bool   $expand   true to expand variables in content, false, to keep content as is
         *
         * @return nothing
         */
        private function outputToFiles(string $content, bool $flush = false, ?string $basename = null, bool $expand = true): void
        {
            // check if there is anything else than EOLs in content
            if ($this->emptyOutput) {
                $len = mb_strlen($content);
                for ($i = 0; $i < $len; $i += 1) {
                    $c = mb_substr($content, $i, 1);
                    if ($c != "\n" && $c != "\t" && $c != ' ') {
                        $this->emptyOutput = false;
                        break;
                    }
                }
            }
            /// output to array(s) for current language(s)
            switch ($this->curLanguage) {
                case 'all': // output to all files
                    foreach ($this->outFiles as $language => $outFile) {
                        //TODO: improve this, because it doesn't work if default is not declared first
                        if (!array_key_exists($language, $this->curOutputs)) {
                            $this->curOutputs[$language] = '';
                        }
                        if (empty($this->curOutputs[$language]) && !empty($this->curOutputs['default'])) {
                            $this->curOutputs[$language] = $this->curOutputs['default'] . $content;
                        } else {
                            $this->curOutputs[$language] .= $content;
                        }
                        // send to file if EOL or EOF
                        if ($flush || substr($this->curOutputs[$language], -1, 1) == "\n") {
                            if ($expand) {
                                $basename = $this->getBasename($this->inFilename);
                                $this->curOutputs[$language] = $this->expandVariables(
                                    $this->curOutputs[$language],
                                    $basename,
                                    $language
                                );
                            }
                            $this->writeToFile($language);
                        }
                    }
                    $this->curOutputs['default'] = '';
                    break;
                case 'ignore': // no output
                    break;
                case '': // .))
                    $this->curLanguage = 'all';
                    break;
                default:
                    // output to current language
                    if (!array_key_exists($this->curLanguage, $this->curOutputs)) {
                        $this->curOutputs[$this->curLanguage] = '';
                    }
                    //
                    if (!empty($content)) {
                        $this->emptyOutput = false;
                        $this->curOutputs[$this->curLanguage] .= $content;
                    }
                    if ($flush || substr($this->curOutputs[$this->curLanguage], -1, 1) == "\n") {
                        if ($expand) {
                            $basename = $this->getBasename($this->inFilename);
                            $this->curOutputs[$this->curLanguage] = $this->expandVariables(
                                $this->curOutputs[$this->curLanguage],
                                $basename,
                                $this->curLanguage
                            );
                        }
                        $this->writeToFile($this->curLanguage);
                    }
                    break;
            }
        }
        
        /**
         * Output into an array of outputs depending on a language.
         * 'all' send to all languages, 'ignore' send to none, else content is sent
         * to the given $out language (can be 'default').
         * Content is emptied before return.
         *
         * @param string   $content the content to output
         * @param string[] $outputs [IN/OUT] the array of outputs
         * @param string   $out     [IN/OUT] language code/'all'/'default'/'ignore'
         * @return nothing
         */
        private function storeInArray(string &$content, array &$outputs, string &$out): void
        {
            if (!empty($content)) {
                switch ($out) {
                    case 'ignore':
                        break;
                    default:
                        if (array_key_exists($out, $outputs)) {
                            $outputs[$out] .= $content;
                        } else {
                            $outputs[$out] = $content;
                        }
                        break;
                }
                $content = '';
            }
        }

        /**
         * Write a TOC for one heading array and start/end levels
         */
        private function storeTOC(object $headingArray, int $startLevel, int $endLevel)
        {
            $index = $headingArray->findIndex($startLevel);
            $lastIndex = $headingArray->getLastIndex();
            $relFilename = $this->relFilenames[$this->inFilename];
            $numbering = $this->allNumberings[$relFilename];
            while ($index >= 0 && $index <= $lastIndex) {
                if ($headingArray->isHeadingBetween($index, $startLevel, $endLevel)) {
                    $TOCline = $headingArray->getTOCLine($index, $numbering, $this);
                    $this->storeContent($TOCline, pathinfo($relFilename, PATHINFO_FILENAME), true, true);
                }
                $index += 1;
            }
        }

        /**
         * Store content as a header.
         *
         * Interpret any directive in the content and write the result to corresponding files.
         * Allowed directives: .all .ignore .default .<code>
         * Also writes an anchor for the TOC links if there is a recorded heading and no anchor has been forced.
         * The default anchor can be an HTML <A name=""> tag or an MD {: } shortcut,
         * depending on the Generator output mode.
         * This function is also called for the TOC title itself.
         *
         * @param int    $level   the heading level (number of '#')
         * @param string $content the text line for the heading, starting after '#' and ending right before '\n'.
         * @param string $anchor  [OPTIONAL] anchor to use, null to use the default anchor
         *
         * @return boolean false if any writing error occurs, true if header stored correctly in files
         */
        private function storeHeading(int $level, string $content, string $anchor = null): bool
        {
            // compute anchor name if needed
            if ($anchor == null) {
                $headingArray = $this->allHeadingArrays[$this->relFilenames[$this->inFilename]] ?? false;
                $index = $headingArray->findIndex($level, $this->curLine - 1); // line number is already on next line
                $headerObject = $headingArray->getAt($index);
                if ($headerObject) {
                    //TODO: '{:' syntax not known in MD viewers
                    // despite https://developers.google.com/style/headings-targets
                    //$anchor = ($this->outputMODE != OutputModes::MD) ? "h{$headerObject->number}" : "{: #h{$headerObject->number}}";
                    $anchor = "a{$headerObject->getNumber()}";
                }
            } else {
                //TODO: '{:' syntax not known in MD viewers
                //  despite  https://developers.google.com/style/headings-targets
                //if ($this->outputMODE == OutputModes::MD) {
                //   $anchor = "{: #{$anchor}}";
                //}
            }
            // write prefix
            $prefix = str_repeat('#', $level);
            foreach ($this->languages as $language => $bool) {
                // HTML/MD: '#### '
                if (fwrite($this->outFiles[$language], $prefix . ' ') === false) {
                    $this->error("unable to write heading prefix to {$this->outFilenames[$language]}", __FILE__, __LINE__);
                    return false;
                }
                // write HTML anchor
                $attr = ($this->outputModeName == 'htmlold') ? 'name' : 'id';
                if (fwrite($this->outFiles[$language], "<a $attr=\"{$anchor}\"></a>") === false) {
                    $this->error("unable to write anchor to {$this->outFilenames[$language]}", __FILE__, __LINE__);
                    return false;
                }
            }
            $this->emptyOutput = false;
            // write heading content, interpret variables, no EOL
            // HTML: '#### <a name="anchor"></a>content'
            // MD  : '#### content'
            $heading = trim($content);
            $this->storeContent($heading, pathinfo($this->relFilenames[$this->inFilename], PATHINFO_FILENAME), false);

            /*TODO: '{:' anchors not known in MD viewers
            // write MD anchors
            if ($this->outputMODE == OutputModes::MD) {
                // MD  : '#### content{: #anchor}'
                foreach ($this->languages as $language => $bool) {
                    if (fwrite($this->outFiles[$language], $anchor)===false) {
                        $this->error("unable to write to {$this->outFilenames[$language]}");
                        return false;
                    }
                }
            }
            */
            // Finish heading with 2 x EOL
            $this->storeContent("\n\n", null, false, false, true);
            return true;
        }

                /**
         * Store content. Interpret any directive in the content and write the result to corresponding files.
         * Allowed directives: .all .ignore .default .<code>
         * 'all' is assumed at entry.
         * Expand variables for each language.
         *
         * @param string $content   the text to store, ending right before the '\n'.
         * @param string $basename  the base filename to use for {file} replacement in content
         * @param bool   $keepSTART true to keep the starting spaces (useful for TOC)
         * @param bool   $endCR     [OPTIONAL] true to put a \n at the end [false]
         * @param bool   $keepCR    [OPTIONAL] false to delete \n endings, true to keep them [false]
         *
         * @return boolean false if any writing error occurs, true if header stored correctly in files
         */
        private function storeContent(string $content, ?string $basename, bool $keepSTART, bool $endCR = false, bool $keepCR = false): bool
        {
            $pos = 0;
            $tryDirective = false;
            $curword = '';
            $length = mb_strlen($content);
            $curOutput = 'all';
            $outputStack = [];
            $outputs = [];
            $trim = $keepSTART ? "rtrim" : "trim";
            while ($pos < $length) {
                $c = mb_substr($content, $pos, 1);
                // escape back-tick and double quotes
                if (($c == '`') || ($c == '"')) {
                    $match = $c;
                    $curword .= $c;
                    do {
                        $pos += 1;
                        $c = mb_substr($content, $pos, 1);
                        $curword .= $c;
                    } while ($match !== $c);
                    $this->storeInArray($curword, $outputs, $curOutput);
                } else {
                    // does curword+c (still) might start a directive?
                    $tryDirective = false;
                    $testWord = mb_strtolower($curword . $c);
                    if ($testWord[0] == '.') {
                        $tryDirective = array_key_exists($testWord, $this->directives);
                        if (!$tryDirective) {
                            foreach ($this->directives as $directive => $directiveName) {
                                if (\strncasecmp($testWord, $directive, strlen($testWord)) == 0) {
                                    $tryDirective = true;
                                    break;
                                }
                            }
                        }
                    }
                    if ($tryDirective) {
                        // is it an existing directive?
                        // can be '.all((', '.default((', '.ignore((', '.<code>((', '.((' or '.))'
                        if (array_key_exists($testWord, $this->directives)) {
                            $newOutput = mb_substr($testWord, 1, -2); // 'all', 'default', 'ignore', '<code>', '' / empty=)) or ((
                            // need a flush when switching from 'all' to anything else
                            if ($newOutput != 'all' && $curOutput == 'all' && \array_key_exists('all', $outputs) && !empty($output['all'])) {
                                $this->flushOutputArray($outputs, $basename, $endCR, $keepCR);
                            }
                            $tryDirective = false;
                            $curword = '';
                            if ($testWord == '.))') {
                                $curOutput = array_pop($outputStack);
                            } else {
                                array_push($outputStack, $curOutput);
                                $curOutput = empty($newOutput) ? 'default' : $newOutput;
                            }
                        } else {
                            $curword .= $c;
                        }
                    } else {
                        // need a flush when storing in 'all' for the first time
                        if ($curOutput == 'all' && (!\array_key_exists('all', $outputs) || empty($outputs['all']))) {
                            $this->flushOutputArray($outputs, $basename, false, $keepCR);
                        }
                        // or need a flush when storing into anything else when 'all' is not empty
                        if ($curOutput != 'all' && \array_key_exists('all', $outputs) && !empty($outputs['all'])) {
                            $this->flushOutputArray($outputs, $basename, false, $keepCR);
                        }
                        if (empty($curword)) {
                            $this->storeInArray($c, $outputs, $curOutput);
                        } else {
                            $curword .= $c;
                            $this->storeInArray($curword, $outputs, $curOutput);
                        }
                    }
                }
                $pos += 1;
            }
            if ($curOutput == 'all' && (!\array_key_exists('all', $outputs) || empty($outputs['all']))) {
                $this->flushOutputArray($outputs, $basename, false, $keepCR);
            }
            $this->storeInArray($curword, $outputs, $curOutput);
            $this->flushOutputArray($outputs, $basename, $endCR, $keepCR);
            return true;
        }

        /**
         * Flush languages output array into files.
         * The given arrays have one index for each language with a content, and one possible 'default' index, which
         * is sent to all non indexed languages if it is present.
         * Variables are expanded in text before file writing.
         * Array is emptied before return.
         */
        private function flushOutputArray(array &$outputs, ?string $basename, bool $endCR, bool $keepCR)
        {
            // retain only non empty outputs
            $final = [];
            foreach ($outputs as $output => $content) {
                $finalText = $this->getCleanText($content);
                if (!empty($finalText)) {
                    $final[$output] = $finalText;
                    if (!$keepCR) {
                        $final[$output] = rtrim($final[$output], "\n");
                    }
                }
            }
            $addEndCR = $endCR;
            foreach ($this->languages as $language => $bool) {
                $text = $final[$language] ?? $final['default'] ?? '';
                if (!empty($text)) {
                    $text = $this->expandVariables($text, $basename, $language);
                    $text .= $endCR ? "\n" : '';
                    $addEndCR = false;
                    if (fwrite($this->outFiles[$language], $text) !== false) {
                        $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . $text, -2, 2);
                        $this->emptyOutput = false;
                    }
                }
                $outputs[$language] = '';
            }
            $outputs['default'] = '';
            if (\array_key_exists('all', $final)) {
                $allText = $final['all'];
                if (!empty($allText)) {
                    foreach ($this->languages as $language => $bool) {
                        $text = $this->expandVariables($allText, $basename, $language);
                        $text .= $endCR ? "\n" : '';
                        $addEndCR = false;
                        if (fwrite($this->outFiles[$language], $text) !== false) {
                            $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . $text, -2, 2);
                            $this->emptyOutput = false;
                        }
                    }
                }
            }
            $outputs['all'] = '';
            if ($addEndCR) {
                foreach ($this->languages as $language => $bool) {
                    if (fwrite($this->outFiles[$language], "\n") !== false) {
                        $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . "\n", -2, 2);
                        $this->emptyOutput = false;
                    }
                }
            }
        }

        /**
         * Add a language to the languages set.
         *
         * @param string $code the language code to add.
         *
         * @return nothing
         */
        private function addLanguage(string $code): void
        {
            // main=<code> ?
            if (mb_stripos($code, 'main=', 0) !== false) {
                $this->mainLanguage = mb_strtolower(mb_substr($code, 5, null));
            } else {
                if (!array_key_exists($code, $this->languages)) {
                    $this->languages[$code] = true;
                }
            }
        }

        
         
        /// Buffer for current word starting with a dot
        private $curWord = '';
        /// Flag to know if we're in a word starting with a dot
        private $inDirective = false;
        /// Flag to know if we're in an escape sequence .{ to .}
        private $inEscaping = false;

        /**
         * Reset parsing status to neutral.
         *
         * @return nothing
         */
        private function resetParsing(): void
        {
            $this->inDirective = false;
            $this->curWord = '';
        }

        /**
         * Parse a heading starting with at least current character which must be '#'.
         *
         * @return nothing
         */
        private function parseHeading(): void
        {
            // get heading level and title
            $level = Heading::getLevelFromText($this->lineBuf);
            do {
                $this->getChar();
            } while ($this->curChar == '#' || $this->curChar == ' ' || $this->curChar == "\t");
            $content = ($this->curChar ?? '') . trim($this->getCharUntil("\n", true));
            $this->storeHeading($level, $content);
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
            // Check valid input
            if (!$this->openInputFile($filename)) {
                return false;
            }
            if (feof($this->inFile)) {
                return false;
            }

            // Get first paragraph in buffer
            $this->getChar();
            
            /*
            // test lexer
            $lexer = new Lexer();
            $pos = 0;
            $line = 1;
            $tokens = $lexer->transform($this->lineBuf, $line, $this);
            */

            if (getenv("debug") == '1') {
                echo "\n[1]:";//write first line number
            }
            // flags to ignore things until .languages is done and something has been written in files
            $this->languagesSet = false;    // .languages has been processed
            $this->emptyOutput = true;      // something usefull has been written to output
            $this->spaceOnly = true;        // only space/tabs/cr/lf read since the beginning of current line
            // main loop on current opened input stream from $filename
            do {
                // read one UTF-8 char and update status
                $this->getChar();
                // process this character
                switch ($this->curChar) {
                    case false:
                        // end of file
                        break;
                    case '`':
                        // back-tick: is it the start of a code fence?
                        if ($this->spaceOnly && $this->isMatching('```')) {
                            $content = $this->getContentUntil("```\n");
                        } else {
                            // double back-tick escaping?
                            if ($this->isMatching('``')) {
                                $content = $this->getContentUntil('``');
                            } else {
                                // single back-tick escaping
                                $content = $this->getContentUntil('`');
                            }
                        }
                        // output content, except when before .languages directive
                        if ($this->languagesSet) {
                            $this->outputToFiles($content);
                        }
                        $this->spaceOnly = false;
                        $this->resetParsing();
                        break;
                    case '"':
                        // double quoted text
                        $content = $this->getContentUntil($this->curChar);
                        if ($this->languagesSet) {
                            $this->outputToFiles($content);
                        }
                        $this->resetParsing();
                        $this->spaceOnly = false;
                        break;
                    case '.':
                        // if end of line, flush current output to files
                        if ($this->prevChar == "\n") {
                            $this->outputToFiles($this->curWord, true);// forces flushing to files
                            $this->resetParsing();
                        } elseif ($this->inDirective) {
                            // store current content, and reset directive detection
                            $this->outputToFiles($this->curWord);
                            $this->resetParsing();
                        }
                        // start directive detection?
                        if (empty($this->curWord) || !$this->inDirective) {
                            /*$$
                            // escaping directive?
                            if ($this->isMatching('.{')) {
                                $content = $this->getContentUntil('.}');
                                if ($this->languagesSet) {
                                    $this->outputToFiles($content);
                                }
                                $this->resetParsing();
                            } else {
                            */
                                $this->inDirective = true;
                                $this->curWord = $this->curChar;
                            /*$$
                            }
                            */
                            break;
                        }
                        // currently in a directive?
                        if ($this->inDirective) {
                            // possible end of directive, try to interpret current word
                            $tryWord = mb_strtolower($this->curWord);
                            if (array_key_exists($tryWord, $this->directives)) {
                                // start effect and restart character capture
                                $functionName = $this->directives[$tryWord];
                                $this->$functionName($tryWord);
                                $this->resetParsing();//TODO: check if useless
                            } else {
                                // no: keep storing (in current word)
                                $this->curWord .= $this->curChar;
                                break;
                            }
                            // start a possible new directive with this '.'
                            $this->inDirective = true;
                            $this->curWord = $this->curChar;
                        } else {
                            // continue to store possible directive
                            $this->curWord .= $this->curChar;
                        }
                        break;
                    case "\n":
                        if (!$this->emptyOutput && $this->languagesSet) {
                            $this->outputToFiles($this->curWord . $this->curChar);
                        }
                        $this->spaceOnly = true;
                        $this->resetParsing();
                        break;
                    case '#':
                        if (!$this->languagesSet) {
                            $this->getContentUntil("\n");
                            $this->resetParsing();
                            break;
                        }
                        // start or continue a heading
                        if ($this->prevChar == "\n") {
                            $this->parseHeading();
                            break;
                        }
                        // not a heading: fall through to default processing
                    default:
                        if (!$this->inDirective && $this->curChar != ' ' && $this->curChar != "\t") {
                            // not looking for a directive, and not space/tabs
                            $this->spaceOnly = false;
                        }
                        // not '.', '\n' or heading starting '#'
                        if ($this->inDirective) {
                            // try to identify a directive with current store and this character
                            $tryWord = mb_strtolower($this->curWord . $this->curChar);
                            if (array_key_exists($tryWord, $this->directives)) {
                                // apply directive effect and reset current store
                                $functionName = $this->directives[$tryWord];
                                $this->$functionName($tryWord);
                                break;
                            }
                            // not a directive, add to current word store
                            $this->curWord .= $this->curChar;
                        } else {
                            if ($this->languagesSet) {
                                $this->outputToFiles($this->curChar);
                            }
                        }
                        break;
                } // switch $this->curChar
            } while ($this->curChar !== false);
            // flush anything left in all output buffers
            $this->curLanguage = 'all';
            $this->outputToFiles($this->curWord, true);
            // MD047: single \n file ending if needed
            foreach ($this->languages as $language => $bool) {
                if (mb_substr($this->lastWritten[$language], -1, 1) != "\n") {
                    if (fwrite($this->outFiles[$language], "\n") === false) {
                        $this->error("cannot write EOL to {$this->outFilenames[$language]}", __FILE__, __LINE__);
                    }
                }
                fclose($this->outFiles[$language]);
                $this->outFiles[$language] = null;
            }
            return true;
        }

    }
}
