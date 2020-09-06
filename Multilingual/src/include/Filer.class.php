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

    mb_internal_encoding('UTF-8');

    require_once 'FileUtilities.php';
    require_once 'Logger.interface.php';
    require_once 'OutputModes.class.php';
    require_once 'File.class.php';
    require_once 'LanguageList.class.php';
    require_once 'Storage.class.php';

    use MultilingualMarkdown\Logger;

    // MB string functions depending on OS
    $posFunction = 'mb_strpos';
    $cmpFunction = 'strcmp';
        
    class Filer implements Logger, \Iterator
    {
        //TODO: Array for all files
        //private $allFiles = [];                 /// Array of File instances, one for each valid input file

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
        private $languages = null;              /// all the languages codes declared in .languages directives

        /**
         * Initialize string function names.
         */
        public function __construct()
        {
            $this->languages = new LanguageList();
            if (\isWindows()) {
                global $posFunction, $cmpFunction;
                $posFunction = 'mb_stripos' ;
                $cmpFunction = 'strcasecmp';
            }
            $this->storage = new Storage();
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
                    error_log("$source($line): MLMD {$type} in {$this->inFilename}({$this->curLine}): $msg");
                } else {
                    error_log("{$this->inFilename}({$this->curLine}): MLMD {$type}: $msg");
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
                $absoluteRoot = realpath($rootDir);
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
                $mainPath = \realpath($name);
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

            // check file extension
            $extension = isMLMDfile($path);
            if ($extension === null) {
                return $this->error("invalid file extension ($filename)", __FILE__, __LINE__);
            }
            // check if it is relative or absolute
            $absolutePath = realpath($path);
            if ($absolutePath === false) {
                return $this->error("file $path doesn't exist", __FILE__, __LINE__);
            }
            $filePos = $posFunction($absolutePath, $path, 0);
            if ($filePos !== 0) {
                // relative path: check against root dir or set it
                $baseDir = mb_substr($absolutePath, 0, $filePos - 1);
                if (empty($this->rootDir ?? '')) {
                    $this->setRootDir($baseDir);
                } else {
                    $rootPos = $posFunction($absolutePath, $this->rootDir, 0);
                    if ($rootPos === false) {
                        return $this->error("file path ($filename) is not relative to root dir ({$this->rootDir}", __FILE__, __LINE__);
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
                $baseDir = mb_substr(realpath($path), 0, $rootLen);
                if ($cmpFunction($baseDir, $this->rootDir) != 0) {
                    $this->error("wrong root dir for file [$path], should be [{$this->rootDir}]", __FILE__, __LINE__);
                    return null;
                }
                $extension = isMLMDfile($path) ?? '';
                $path = mb_substr(realpath($path), $rootLen + 1, null);
                return mb_substr($path, 0, -mb_strlen($extension));
            } else {
                if ($this->mainFilename !== null) {
                    // get root dir from main file path
                    $this->rootDir = dirname(realpath($this->mainFilename));
                    return $this->getBasename($path);
                }
            }
            $extension = isMLMDfile(basename($path)) ?? '';
            return basename($path, $extension);
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
                return $this->error("invalid indexx $index for file", __FILE__, __LINE__);
            }
            // open or exit
            $this->closeInput();
            $filename = $this->allInFilePathes[$index];
            $this->inFile = fopen($filename, "rb");
            if ($this->inFile === false) {
                return $this->error("cannot open file $filename", __FILE__, __LINE__);
            }

            // prapare storage object
            $this->storage->setInputFile($this->inFile);

            // retain base name with full path but no extension as template and reset line number
            $extension = \isMLMDfile($filename);
            $this->outFilenameTemplate = mb_substr($filename, 0, -mb_strlen($extension));
            $this->inFilename = $filename;
            $this->curLine = 1;
            $this->curLanguage = 'ignore';
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
        private function closeInput(): bool
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
        private function closeOutput(): bool
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
         * Set languages list from a parameter string.
         * This is just a relay to LanguagesList::setFrom().
         *
         * @param string $string  the parameter string
         *
         * @return bool true if languages have been set correctly and main language was
         *              valid (if 'main=' was in the parameters.)
         */
        public function setLanguagesFrom(string $param): bool
        {
            return $this->languages->setFrom($param);
        }

        /**
         * Prepare output filenames from the languages set and output template filename.
         * This call must be done after all input files have been set and readyInputs() has
         * been called.
         */
        public function readyOutputs(): bool
        {
            foreach ($this->languages as $index => $array) {
                $code = $array['code'] ?? null;
                $this->outFiles[$code] = null;
                if ($this->outFilenameTemplate != null) {
                    if ($this->languages->isMain($code)) {
                        $this->outFilenames[$code] = "{$this->outFilenameTemplate}.md";
                    } else {
                        $this->outFilenames[$code] = "{$this->outFilenameTemplate}.{$code}.md";
                    }
                }
            }
            return true;
        }

        /**
         * Set the main language code.
         * The main language will have output files only suffixed '.md' instead of '.code.md'.
         *
         * @param string $code the language code to set as main language
         */
        public function setMainLanguage(string $code): bool
        {
            return $this->languages->setMain($code);
        }

        //MARK: Relays to storage

        /**
         * Get the current paragraph length.
         * Returns the number of UTF-8 characters in the paragraph, including EOLs.
         */
        public function getParagraphLength(): int
        {
            return $this->storage->getParagraphLength();
        }
        /**
         * Get the starting input line number for current paragraph.
         */
        public function getStartingLineNumber(): int
        {
            return $this->storage->getStartingLineNumber();
        }
        /**
         * Get the ending input line number for current paragraph.
         */
        public function getEndingLineNumber(): int
        {
            return $this->storage->getEndingLineNumber();
        }
        /**
         * Return the current UTF-8 character from current paragraph.
         * Load next paragraph if no paragraph is loaded yet.
         *
         * @return null|string current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function curChar(): ?string
        {
            return $this->storage->curChar();
        }
        /**
         * Return the next UTF-8 character from current buffer, return null if end of file.
         *
         * @return null|string new current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function nextChar(): ?string
        {
            return $this->storage->nextChar();
        }
    }
}
