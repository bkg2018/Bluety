<?php

/**
 * Multilingual Markdown generator - Generator class
 *
 * This is the main class for MLMD conversion. Parameters for the process
 * handling classes objects like Filer, Numbering, Lexer, Storage are forwarded by
 * Generator from the command line arguments to the handling classes with
 * minimal interpretation or checking.
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
    require_once 'Filer.class.php';
    require_once 'Lexer.class.php';
    require_once 'Parser.class.php';

    /**
     * Generator class.
     * Accept input parameters and files, process all input files and generate output files.
     */
    class Generator implements Logger
    {
        //------------------------------------------------------------------------------------------------------
        //MARK: Members
        //------------------------------------------------------------------------------------------------------

        // Handling classes instances
        private $filer = null;                  /// Filer instance, input and output files handling
        private $lexer = null;                  /// Lexer instance, transform text into token list
        private $numbering = null;              /// Numbering instance
        private $parser = null;                 /// Parser instance, interpret tokens

        // Settings
        private $outputModeName = '';           /// from -out command line argument
        private $numberingScheme = '';          /// from -numbering command line argument
        
        // Prepared datas
        private $allHeadingsArrays = [];        /// headings array for each input file

        // Initialize handlers and default settings
        public function __construct()
        {
            $this->filer = new Filer();
            $this->lexer = new Lexer();
            $this->numbering = new Numbering('', $this);
            $this->parser = new Parser();
            $this->outputModeName = 'md';       // markdown links and toc style
            $this->numberingScheme = '';        // no numbering
        }

        //------------------------------------------------------------------------------------------------------
        //MARK: Logger interface (relayed to Filer object)
        //------------------------------------------------------------------------------------------------------

        /**
         * Logger interface: Send an error message to output and php log.
         *
         * @param string $msg    the text to display and log.
         * @param string $source optional file name for MLMD script, can be null to ignore
         * @param int    $line   optional line number for MLMD script
         *
         * @return false
         */
        public function error(string $msg, ?string $source = null, $line = false): bool
        {
            return $this->filer->error($msg, $source, $line);
        }
        /**
         * Logger interface: Send a warning message to output and php log.
         *
         * @param string $msg the text to display and log.
         * @param string $source optional file name for MLMD script, can be null to ignore
         * @param int    $line   optional line number for MLMD script
         *
         * @return false
         */
        public function warning(string $msg, ?string $source = null, $line = false): bool
        {
            return $this->filer->warning($msg, $source, $line);
        }

        //------------------------------------------------------------------------------------------------------
        //MARK: Settings
        //------------------------------------------------------------------------------------------------------

        /**
         * Add an input file by path, can bne relative or absolute.
         *
         * The file must have either '.mlmd' or '.base.md' extension or it is rejected.
         *
         * The path can be relative to the current directory (returned by getcwd())
         * which is generally the one where MLMD has been called from, or it can be
         * relative to a previously set root directory in Filer class.
         *
         * If there is no root directory in Filer yet, the base directory of the
         * added input file will be used as root directory for further added files which
         * will hahve to be in or under this root directory.
         *
         * @param string $path a relative or absolute file path ending with .mlmd
         *                     or .base.md extension.
         *
         * @return bool true if the file has been added correctly, false in case of error.
         */
        public function addInputFile(string $path): bool
        {
            return $this->filer->addInputFile($path);
        }

        /**
         * Set the main file name.
         * The root directory is set to the base directory of this main file.
         * All input files must be relative to the root directory.
         *
         * @param string $name the name of the main template file.
         *                     Default is 'README.mlmd' in the root directory.
         *
         * @return bool false if file doesn't exist
         */
        public function setMainFilename(string $name = 'README.mlmd'): bool
        {
            return $this->filer->setMainFilename($name);
        }

        /**
         * Set the output mode.
         * Default is MD style links.
         *
         * @param string $mode 'htmlold' to set HTML mode (<A name> links and anchors),
         *                     'html' to set HTML mode (<A id> links and anchors),
         *                     'md for MD mode ([]() links and {:# } anchors)
         *
         * @return nothing
         */
        public function setOutputMode(string $mode): void
        {
            if (!OutputModes::isValid($mode)) {
                $this->error("invalid output mode $mode, using \'md\'");
                $mode = 'md';
            }
            $this->outputModeName = $mode;
        }

        /**
         * Set the numbering scheme.
         *
         * @param string $scheme a string containing numbering scheme.
         *
         * @return nothing
         */
        public function setNumbering(string $scheme): void
        {
            $this->numberingScheme = $scheme;
        }

        //------------------------------------------------------------------------------------------------------
        //MARK: TOOLS
        //------------------------------------------------------------------------------------------------------

        /**
         * Find the languages directives and all headings and sub headings in the set of input files
         * before processing them.
         *
         * - The languages directives found in all files will preset the list of output files
         *   for each language for each input file.
         *
         * - The headings will be stored for TOC generation and possibly numbering.
         *
         * Files with no headings will receive a level 1 heading using their filename
         * so that TOC can point to them. If no languages directive has been found at all
         * this is an error and the function will return false.
         *
         * This function reads the files without going through Storage class and only looks
         * for languages directives and headings, skipping over code fences and some
         * escape text sequences.
         *
         * @return bool true if pre processing found languages and headings correctly, false
         *              if there is no languages directive in any file.
         */
        public function preProcess(): bool
        {
            $this->filer->readyInputs();
            unset($this->allHeadingsArrays);
            $this->allHeadingsArrays = [];
            Heading::init();// reset global headings numbering to 0
            // Explore each input file ($this->filer is iterable and returns relative filenames and index)
            foreach ($this->filer as $index => $relFilename) {
                $filename = $this->filer->getInputFile($index); // full file path
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
                $curLine = 0;
                // remember if the .languages directive has been read
                $languageSet = false;
                $languagesDirective = '.languages ';
                $languagesDirectiveLength = strlen($languagesDirective);
                // loop on each file line
                do {
                    $text = trim(fgets($file));
                    $curLine += 1;
                    // handle .languages directive
                    if (strncasecmp($text, $languagesDirective, $languagesDirectiveLength) == 0) {
                        $languageSet = mb_substr($text, $languagesDirectiveLength);
                        $this->filer->setLanguagesFrom($languageSet);
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
                                $text = trim(fgets($this->inFile));
                                $curLine += 1;
                            } while (strpos($text, '```') === false);
                        }
                    } else {
                        // store headings
                        if (($text[0] ?? '') == '#') {
                            $heading = new Heading($text, $curLine, $this);
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
            return true;
        }

        //------------------------------------------------------------------------------------------------------
        //MARK: Public main entry
        //------------------------------------------------------------------------------------------------------
        
        /**
         * Process the input files list.
         * Files must be added to the list using addInputFile() function.
         * If no file has been added, process the files found in current directory
         * and sub directories.
         *
         * @return bool true if processing done correctly, false if any error.
         */
        public function processAllFiles(): bool
        {
            $dashes = str_repeat('-', 60);
            $this->preProcess();
            foreach ($this->filer as $index => $relFilename) {
                echo "$dashes\n$relFilename\n$dashes\n";
                if (!$this->process($index)) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Process one of the input files and generate its output files.
         * This process reads the input file stream, detects and interprets directives,
         * expand variables and sends output to files.
         *
         * Note: readyInputs() must have been called before any process() takes place.
         *
         * @param int $index index of the input file in the filer object.
         *
         * @return bool true if input file processed correctly, false if any error.
         */
        public function process(int $index): bool
        {
            if (!$this->filer->openFile($index)) {
                return false;
            }
            $this->filer->readyOutputs();

            /*//echo str_repeat('=', 120), "\n";
            $paragraph = $this->filer->getNextParagraph();
            while ($paragraph !== null) {
                $lineNumber = $this->filer->getStartingLineNumber();
                $tokens = $this->lexer->getTokens($paragraph, $lineNumber, $this);
                foreach ($tokens as $index => $token) {
                    $t = (string)$token;
                    echo "$t\n";
                }
                echo "----- end of paragraph -----\n";
                $paragraph = $this->filer->getNextParagraph();
            }*/
            
            
            $this->filer->closeOutput();
            $this->filer->closeInput();

            return true;
        }
    }
    $generator = new Generator();
    $generator->addInputFile('../../testdata/test.mlmd');
    $generator->setMainFilename("test.mlmd");
    $generator->addInputFile('../../testdata/subdata/secondary.mlmd');
    $generator->addInputFile('../../testdata/subdata/tertiary.mlmd');
    $generator->processAllFiles();
}
