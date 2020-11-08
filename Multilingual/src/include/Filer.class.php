<?php

/**
 * Multilingual Markdown generator - Filer class
 *
 * The Filer class handles input file reading through a paragraph buffer and output files writing through temporary storage.
 * It is used by the Generator main class which manages all the file processing for output, and by the Lexer for reading
 * and tokenizing.
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

    require_once('Constants.php');

    require_once 'Utilities.php';
    require_once 'Logger.interface.php';
    require_once 'LanguageList.class.php';
    require_once 'Storage.class.php';
    require_once 'OutputPart.class.php';

    use MultilingualMarkdown\Logger;
    use MultilingualMarkdown\languageList;

    // MB string functions depending on OS
    $posFunction = 'mb_strpos';
    $cmpFunction = 'strcmp';
        
    class Filer implements Logger, \Iterator
    {
        // Input filenames, files and reading status
        private $allInFilePathes = [];          /// Array of all the input files - relative to root dir
        private $relFilenames = [];             /// relative filenames for each filename
        private $inFilename = null;             /// current file name e.g. 'example.mlmd' - relative to root dir
        private $inFile = null;                 /// current input file handle
        private $storage = null;                /// input buffers handling object

        // Output filenames, files and writing status
        private $outFilenameTemplate = null;    /// as 'example'
        private $outFilenames = [];             /// '<language>' => 'example.md' / 'example.<language>.md'
        private $outFiles = [];                 /// '<language>' => file-handle
        private $mainFilename = null;           /// -main parameter
        private $rootDir = null;                /// root directory, or main file directory
        private $rootDirLength = 0;             /// root directory utf-8 length

        // Languages handling (LanguageList class)
        private $languageList = null;           /// list of languages, will be set by Lexer
        private $ignoreLevel = 0;               /// number of 'ignore' to close in language stack
                                                /// don't send any output while this variable is not 0
        private $curLanguage =  IGNORE;         /// current language code, or all, ignore, default
        private $curOutput = [];                /// array of (array of OutputPart), one for each language code
        private $curDefault = [];               /// array of OutputPart for default text
        private $languageFunction = [];         /// language codes will be added by setLanguage

        /**
         * Initialize string function names.
         */
        public function __construct()
        {
            if (\isWindows()) {
                global $posFunction, $cmpFunction;
                $posFunction = 'mb_stripos' ;
                $cmpFunction = 'strcasecmp';
            }
            $this->storage = new Storage();
            $this->curLanguages = ALL;
        }

        /**
         * Logger function: Send an error or warning to output and php log
         *
         * @param string $type   'error' or 'warning'
         * @param string $msg    the text to display and log.
         * @param string $source optional file name for MLMD script, can be null to ignore
         * @param int    $line   optional line number for MLMD script
         *
         * @return false
         */
        private function log(string $type, string $msg, ?string $source = null, $line = false): bool
        {
            if ($this->inFilename) {
                if ($source) {
                    error_log("$source($line): MLMD {$type} in {$this->inFilename}(" . $this->storage->getCurrentLineNumber() . "): $msg");
                } else {
                    error_log("{$this->inFilename}(" . $this->storage->getCurrentLineNumber() . "): MLMD {$type}: $msg");
                }
            } else {
                error_log("arguments: MLMD {$type}: $msg");
            }
            return false;
        }
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
            return $this->log('error', $msg, $source, $line);
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
            return $this->log('warning', $msg, $source, $line);
        }

        /**
         * Iterator interface to relative filenames with foreach()
         */
        private $iteratorIndex = 0;

        public function current()
        {
            return $this->relFilenames[$this->iteratorIndex];
        }
        public function key()
        {
            return $this->iteratorIndex;
        }
        public function next()
        {
            $this->iteratorIndex += 1;
        }
        public function rewind()
        {
            $this->iteratorIndex = 0;
        }
        public function valid()
        {
            return isset($this->relFilenames[$this->iteratorIndex]);
        }

        /**
         * Current filename accessor.
         */
        public function getInFilename(): ?string
        {
            return $this->inFilename;
        }
        /**
         * Get the current line number for current reading position.
         */
        public function getCurrentLineNumber()
        {
            return $this->storage->getCurrentLineNumber();
        }
        /**
         * Set root directory for relative filenames.
         * Resets all registered input files relative to the new root directory.
         *
         * @param string $rootDir the root directory, preferably an absolute path.
         *
         * @return bool false if the directory doesn't exist.
         */
        public function setRootDir(string $rootDir): bool
        {
            if (\file_exists($rootDir) && \is_dir($rootDir)) {
                $absoluteRoot = normalizedPath(realpath($rootDir));
                $this->rootDir = rtrim($absoluteRoot, "/\\");
                $this->rootDirLength = mb_strlen($this->rootDir);
                // reset relative filenames if needed
                $this->readyInputs();
                return true;
            }
            return $this->error("invalid root directory ($rootDir)", __FILE__, __LINE__);
        }

        /**
         * Get the current root directory.
         *
         * @return string null if no root directory yet, else root directory.
         */
        public function getRootDir(): ?string
        {
            return $this->rootDir;
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
            global $posFunction;
            // try to find this file name in registered files
            $mainPath = '';
            foreach ($this->allInFilePathes as $filePath) {
                $posName = $posFunction($filePath, $name, 0);
                if ($posName >= 0) {
                    // found, now set root directory to this file base dir
                    $this->setRootDir(mb_substr($filePath, 0, $posName));
                    $mainPath = $filePath;
                    break;
                }
            }
            if (empty($mainPath)) {
                // file not found: reset root dir
                $mainPath = normalizedPath(\realpath($name));
                if ($mainPath === false) {
                    return $this->error("main file cannot be found ($name)", __FILE__, __LINE__);
                }
                $this->setRootDir(dirname($mainPath));
            }
            // get the base name relative to root dir
            $basename = $this->getBasename($mainPath);
            if ($basename !== false) {
                $this->mainFilename = $basename;
            }
            return true;
        }

        /**
         * Add a file to the input files array.
         * This must be done before any processing.
         * The file is checked for existence. The full path is stored, if it cannot be found
         * the function doesn't record the file and returns false. If no root directory
         * is set yet, the home directory of the file is set as root.
         *
         * @param string $path the relative or absolute path to the input file. If it is relative,
         *                     then the absolute path is computed by the realpath() function.
         *
         * @return bool true if ok, false if the file doesn't exist or can't be accessed or
         *              has a wrong extension (.mlmd and .base.md are accepted, any other is rejected.)
         */
        public function addInputFile(string $path): bool
        {
            global $posFunction;
            $path = normalizedPath($path);
            // check file extension
            $extension = isMLMDfile($path);
            if ($extension === null) {
                return $this->error("invalid file extension ($path)", __FILE__, __LINE__);
            }
            // check if it is relative or absolute
            $absolutePath = normalizedPath(realpath($path));
            if ($absolutePath === false) {
                return $this->error("file $path doesn't exist", __FILE__, __LINE__);
            }
            $filePos = $posFunction($absolutePath, $path, 0);
            if ($filePos !== 0) {
                if ($filePos === false) {
                    // delete anything before '/../' or '/./' in relative path
                    foreach (['/../', '/./'] as $pattern) {
                        do {
                            $curPos = \mb_strrpos($path, $pattern);
                            if ($curPos !== false) {
                                $path = mb_substr($path, $curPos + mb_strlen($pattern));
                            }
                        } while ($curPos !== false);
                    }
                    // delete starting '../' or './'
                    foreach (['../', './'] as $pattern) {
                        do {
                            $curPos = \mb_strpos($path, $pattern);
                            if ($curPos === 0) {
                                $path = mb_substr($path, mb_strlen($pattern));
                            }
                        } while ($curPos !== false);
                    }
                    $filePos = $posFunction($absolutePath, $path, 0);
                    if ($filePos === false) {
                        // shouldn't happen
                        return $this->error("impossible to find root directory from $path", __FILE__, __LINE__);
                    }
                }
                // relative path: check against root dir or set it
                $baseDir = mb_substr($absolutePath, 0, $filePos - 1);
                if (empty($this->rootDir ?? '')) {
                    $this->setRootDir($baseDir);
                } else {
                    $rootPos = $posFunction($absolutePath, $this->rootDir, 0);
                    if ($rootPos === false) {
                        return $this->error("file path ($absolutePath) is not relative to root dir ({$this->rootDir}", __FILE__, __LINE__);
                    }
                }
            }
            // do not store a path twice
            if (!in_array($absolutePath, $this->allInFilePathes)) {
                $this->allInFilePathes[] = $absolutePath;
            }
            return true;
        }

        /**
         * Return the number of input files.
         */
        public function getInputFilesMaxIndex(): int
        {
            return count($this->allInFilePathes) - 1;
        }

        /**
         * Return an input file name.
         * Returns null if the index is invalid.
         *
         * @param int $index an index value between 0 and getInputFilesMaxIndex().
         *
         * @return string|null the file path or null if $index is invalid
         */
        public function getInputFile(int $index): ?string
        {
            if ($index < 0 || $index >= count($this->allInFilePathes)) {
                return null;
            }
            return $this->allInFilePathes[$index];
        }

        /**
         * Return an input file name, relative to root directory.
         * Returns null if the index is invalid.
         *
         * @param int $index an index value between 0 and getInputFilesMaxIndex().
         *
         * @return string|null the rootdir relative file path or null if $index is invalid
         */
        public function getRelativeInputFile(int $index): ?string
        {
            if ($index < 0 || $index >= count($this->relFilenames)) {
                return null;
            }
            return $this->relFilenames[$index];
        }

        /**
         * Get basename (no extension) from a filepath, relative to root directory or
         * to main file directory.
         *
         * @param string $path the path to the file. If the path is relative to
         *
         * @return string|bool the base name, without extension and using a path relative
         *                     to rootDir, null if the path is not under rootDir
         *                     or if there is no rootDir (-i script arguments)
         */
        public function getBasename(string $path): ?string
        {
            global $cmpFunction;

            //  build relative path against root dir
            if ($this->rootDir !== null) {
                $rootLen = mb_strlen($this->rootDir);
                $baseDir = mb_substr(normalizedPath(realpath($path)), 0, $rootLen);
                if ($cmpFunction($baseDir, $this->rootDir) != 0) {
                    $this->error("wrong root dir for file [$path], should be [{$this->rootDir}]", __FILE__, __LINE__);
                    return null;
                }
                $extension = isMLMDfile($path) ?? '';
                $path = mb_substr(normalizedPath(realpath($path)), $rootLen + 1, null);
                return mb_substr($path, 0, -mb_strlen($extension));
            } else {
                if ($this->mainFilename !== null) {
                    // get root dir from main file path
                    $this->rootDir = normalizedPath(dirname(realpath($this->mainFilename)));
                    return $this->getBasename($path);
                }
            }
            $extension = isMLMDfile(basename($path)) ?? '';
            return basename($path, $extension);
        }

        /**
         * Tell if a file is currently opened and output files can be prepared.
         */
        public function hasOpenedFile()
        {
            return ($this->inFile != null);
        }

        /**
         * Open one of the input files and prepare the output filename template.
         * If another file is already opened for input, it is closed.
         *
         * @param int $index index of the input file in the files array.
         *                   must be between 0 and getInputFileMaxIndex() included.
         *
         * @return bool true if input file was opened correctly, false for any error.
         */
        public function openFile(int $index): bool
        {
            if ($index < 0 || $index > $this->getInputFilesMaxIndex()) {
                return $this->error("invalid index $index for file", __FILE__, __LINE__);
            }
            // open or exit
            $this->closeInput();
            $filename = $this->allInFilePathes[$index];
            $this->inFile = fopen($filename, "rb");
            if ($this->inFile === false) {
                return $this->error("cannot open file $filename", __FILE__, __LINE__);
            }

            // prepare storage object
            $this->storage->setInputFile($this->inFile);

            // retain base name with full path but no extension as template and reset line number
            $extension = \isMLMDfile($filename);
            $this->outFilenameTemplate = mb_substr($filename, 0, -mb_strlen($extension));
            $this->inFilename = $filename;
            $this->curLanguage = IGNORE;
            $this->closeOutput();
            // the output files will be opened by the .languages directive for
            // this opened input file based on $this->outFilenameTemplate and languages codes.
            return true;
        }

        /**
         * Close input file.
         *
         * @return false in all cases
         */
        public function closeInput(): bool
        {
            if ($this->inFile != null) {
                fclose($this->inFile);
                unset($this->inFile);
                $this->inFile = null;
            }
            if (isset($this->inFilename)) {
                unset($this->inFilename);
                $this->inFilename = null;
            }
            if (isset($this->outFilenameTemplate)) {
                unset($this->outFilenameTemplate);
                $this->outFilenameTemplate = null;
            }
            return false;
        }

        /**
         * Close output files.
         *
         * @return false in all cases
         */
        public function closeOutput(): bool
        {
            foreach ($this->outFiles as &$outFile) {
                if ($outFile != null) {
                    fclose($outFile);
                }
            }
            unset($this->outFiles);
            $this->outFiles = [];
            return false;
        }

        /**
         * Reset the list of input files to the content of a directory and subdirectories.
         * The directory becomes the root directory.
         *
         * @param string $rootDir the new root directory where to look for input files
         *
         * @return bool true if directory correctly explored, false for any problem
         */
        public function exploreDirectory(string $rootDir): bool
        {
            $this->closeInput();
            $this->closeOutput();
            $this->setRootDir($rootDir);
            $this->allInFilePathes = exploreDirectory($this->rootDir);
            return true;
        }

        /**
         * Get all input files ready for processing.
         * If the input file array or root directory are not set, use default values:
         * - root directory is set to the current working directory using PHP getcwd()
         * - input files are set to all the mlmd or .base.md files recursively found in and under root directory
         */
        public function readyInputs(): void
        {
            if (empty($this->rootDir ?? '')) {
                $this->setRootDir(getcwd());
            }
            unset($this->relFilenames);
            $this->relFilenames = [];
            foreach ($this->allInFilePathes as $index => $filename) {
                // get relative filename, ignore if not the right root
                $rootLen = mb_strlen($this->rootDir);
                $baseDir = mb_substr($filename, 0, $rootLen);
                if ($baseDir != $this->rootDir) {
                    $this->error("wrong base dir for file [$filename], should be [$this->rootDir]", __FILE__, __LINE__);
                    continue;
                }
                // relative filename is the index for the work arrays
                $this->relFilenames[$index] = mb_substr($filename, $rootLen + 1);
            }
        }



        /**
         * Read a number of characters including the current one and return the string.
         * Return null if already at end of file. The final current position is set
         * on the first character past the string.
         */
        public function getString(int $charsNumber): ?string
        {
            return $this->storage->getString($charsNumber);
        }

        /**
         * Skip over any space/tabulation.
         *
         * @return int the number of space and tabulations skipped over.
         */
        public function skipSpaces(): int
        {
            $count = 0;
            $c = $this->getCurrentChar();
            while ($c == ' ' || $c == "\t") {
                $count += 1;
                $c = $this->getNextChar();
            }
            return $count;
        }

        /**
         * Prepare output filenames from the languages set and output template filename.
         * This call must be done after all input files have been set and readyInputs() has
         * been called.
         *
         * @param object $languageList the LanguageList object 
         */
        public function readyOutputs(object $languageList): bool
        {
            if ($this->outFilenameTemplate == null) {
                return $this->error("output file template not set", __FILE__, __LINE__);
            }
            $return = true;
            $this->languageFunction = [ALL=>'outputAll',IGNORE=>'outputIgnore',DEFLT=>'outputDefault'];
            foreach ($languageList as $index => $array) {
                $code = $array['code'] ?? null;
                $this->outFiles[$code] = null;
                if ($languageList->isMain($code)) {
                    $this->outFilenames[$code] = "{$this->outFilenameTemplate}.md";
                } else {
                    $this->outFilenames[$code] = "{$this->outFilenameTemplate}.{$code}.md";
                }
                $this->outFiles[$code] = fopen($this->outFilenames[$code], "wb");
                if ($this->outFiles[$code] == false) {
                    $return &= $this->error("unable to open file {$this->outFilenames[$code]} for writing", __FILE__, __LINE__);
                }
                $this->curOutput[$code] = []; // each [$code] is an array where each [i] is an OutputPart
                $this->languageFunction[$code] = 'outputCurrent';
            }
            $this->curDefault = []; // each [i] is an OuputPart
            $this->languageList = $languageList;
            return $return;
        }

        //MARK: Relays to storage

        /**
         * Return the previous UTF-8 character .
         *
         * @return null|string previous character ('\n' for EOL).
         */
        public function getPrevChar(): ?string
        {
            return $this->storage->getPrevChar();
        }

        /**
         * Return the current UTF-8 character from current paragraph.
         * Load next paragraph if no paragraph is loaded yet.
         *
         * @return null|string current character ('\n' for EOL), null when file and buffer are finished.
         */
        public function getCurrentChar(): ?string
        {
            return $this->storage->getCurrentChar();
        }

        /**
         * Read and return the next UTF-8 character from current buffer, return null at end of file.
         *
         * @return null|string new current character ('\n' for EOL), null at end of file
         */
        public function getNextChar(): ?string
        {
            return $this->storage->getNextChar();
        }

        /**
         * Skip every character starting at next one until next line starts. Do not read the first character on new line,
         * so at exit the current character is the current line EOL.
         * 
         * @return null|string EOL or null at end of file
         */
        public function gotoNextLine(): ?string
        {
            return $this->storage->gotoNextLine();
        }


        /**
         * Read and return the text until the end of line. Do not include
         * the end of line character in the returned text.
         */
        public function getLine(): ?string
        {
            return $this->storage->getLine();
        }

        /**
         * Look at next UTF-8 characters.
         * This call doesn't advance input position but rather just send back the next characters
         * from input file, or null at end of input file.
         *
         * @param int $charsNumber the number of characters to fetch
         *
         * @return null|string     the next characters which will be read from input,
         *                         null if already at end of file.
         */
        public function fetchNextChars(int $charsNumber): ?string
        {
            return $this->storage->fetchNextChars($charsNumber);
        }
        /**
         * Look at previous UTF-8 characters.
         * Cannot read more than further the beginning of file or the beginning
         * of current buffer positions. The buffer at most up to 3072 characters before current
         * position so it is safe to request for a lot of previous characters up to this limit
         * but at the beginning the buffer will only have as much as the 4096 first
         * characters of file.
         *
         * @param int $charsNumber the number of previous characters to fetch
         *
         * @return null|string     the characters before current position.
         */
        public function fetchPreviousChars(int $charsNumber): ?string
        {
            return $this->storage->fetchPreviousChars($charsNumber);
        }

        /**
         * Check if current and next characters match a string in current line buffer.
         * This will fetch more characters from input file if needed but won't advance the
         * current reading position.
         *
         * @param string $marker the string to match, starting at current character
         *
         * @return bool true if marker has been found at current place
         */
        public function isMatching(string $marker): bool
        {
            return $this->storage->isMatching($marker);
        }

        /**
         * Set the 'ignore' level.
         * No output will occur while this level is not 0.
         */
        public function setIgnoreLevel(int $level): void
        {
            $this->ignoreLevel = $level;
        }

        /**
         * Set the current output language, also accepts 'all' or 'default'.
         *
         * @param object $languageList the LanguagesList object
         * @param string $language     the language code to set as current
         */
        public function setLanguage(object $languageList, string $language): bool
        {
            if ($this->ignoreLevel > 0) {
                return false;
            }
            if (($languageList == null) || (get_class($languageList) != 'MultilingualMarkdown\LanguageList')) {
                return false;
            }
            if ($languageList->existLanguage($language)) {
                $this->curLanguage = $language;
                $this->storage->setLanguage($language);
                return true;
            }
            return false;
        }

        /**
         * Set output mode.
         * If numbering scheme has been set, the output mode will use a numbered format.
         * If not, it will use a non-numbered format.
         * Setting a numbering scheme after setting the output mode will adjust the mode.
         *
         * @param string $name      the output mode name 'md', 'mdpure', 'html' or 'htmlold'
         * @param object $numbering the numbering scheme object
         */
        public function setOutputMode(string $name, object $numbering): void
        {
            $this->storage->setOutputMode($name, $numbering, $this);
        }

        /**
         * Output text to current output language and mode.
         *
         * @param Lexer  $lexer     the lexer
         * @param string $text      the text to send
         * @param bool   $expand    true if variables must be expanded (headings and text)
         *                          false if the don't (escaped text)
         */
        public function output(object &$lexer, string $text, bool $expand): bool
        {
            if ($this->ignoreLevel > 0) {
                return false;
            }
            if (\array_key_exists($this->curLanguage, $this->languageFunction)) {
                $functionName = $this->languageFunction[$this->curLanguage];
            } else {
                echo "ERROR: unknown language function for $this->curLanguage\n";
                $functionName = 'outputIgnore';
            }
            return $this->$functionName($lexer, $text, $expand);
        }

        /**
         * Append default parts to empty language outputs.
         */
        private function fillEmptyOutputs(): void
        {
            if (count($this->curDefault) > 0) {
                foreach ($this->languageList as $index => $array) {
                    $code = $array['code'] ?? null;
                    // no output for this code yet?
                    if (count($this->curOutput[$code]) == 0) {
                        // add the default text parts
                        foreach ($this->curDefault as $part) {
                            $this->curOutput[$code][] = $part;
                        }
                    }
                }
            }
        }

        /**
         * Append text to all current languages output buffers.
         * Set status accordingly.
         */
        public function outputAll(object &$lexer, string $text, bool $expand): bool
        {
            // 1) Send default text to empty language buffers
            $this->fillEmptyOutputs();
            // clear the default text now
            unsetArrayContent($this->curDefault);

            // 2) append text part to all languages
            foreach ($this->languageList as $index => $array) {
                $code = $array['code'] ?? null;
                $this->curOutput[$code][] = new OutputPart($text, $expand);
            }
            return true;
        }

        /**
         * Append text part to default output.
         * First flush current outputs if there is any content.
         */
        public function outputDefault(object &$lexer, string $text, bool $expand): bool
        {
            // 1) check for non empty output
            $empty = true;
            foreach ($this->languageList as $index => $array) {
                $code = $array['code'] ?? null;
                if (count($this->curOutput[$code]) > 0) {
                    $empty = false;
                    break;
                }
            }
            if (!$empty) {
                $this->flushOutput();
            }
            // 2) add to default buffer
            $this->curDefault[] = new OutputPart($text, $expand);
            return true;
        }

        /**
         * Ignore text output.
         */
        public function outputIgnore(object &$lexer, string $text, bool $expand): bool
        {
            echo "WARNING: ignored text ($text)\n";
            return true;
        }
        
        /**
         * Append text to current language output.
         */
        public function outputCurrent(object &$lexer, string $text, bool $expand): bool
        {
            $this->curOutput[$this->curLanguage][] = new OutputPart($text, $expand);
            return true;
        }

        /**
         * Send all output to files.
         */
        public function flushOutput(): bool
        {
            $result = true;
            // 1) Send default text to empty language buffers
            $this->fillEmptyOutputs();
            unsetArrayContent($this->curDefault);
            // 2) send to files
            foreach ($this->languageList as $index => $array) {
                $code = $array['code'] ?? null;
                if (!isset($this->outFiles[$code])) {
                    echo "ERROR: unavailable file for code <$code>\n";
                    $result = false;
                    continue;
                }
                foreach ($this->curOutput[$code] as $part) {
                    $text = $part->expand ? $this->expand($part->text, $code) : $part->text;
                    fwrite($this->outFiles[$code], $text);
                }
                unsetArrayContent($this->curOutput[$code]);
                $this->curOutput[$code] = [];
            }
            return $result;
        }

        /**
         * Expand variables in a text.
         */
        public function expand(string $text, string $language): string
        {
            $relFilename = $this->current();
            $basename = pathinfo($relFilename, PATHINFO_FILENAME);
            $extension = $this->languageList->isMain($language) ? '.md' : ".{$language}.md";
            $result = str_replace('{file}', $basename . $extension, $text);
            if ($this->mainFilename !== null) {
                $result = str_replace('{main}', $this->mainFilename . '.md', $result);
            }
            $result = str_replace('{language}', $language, $result);
            return $result;
        }
    }
}
