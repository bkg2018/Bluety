<?php

/**
 * Multilingual Markdown generator - Storage class
 *
 * The Storage class handles buffers for reading paragraphs from an input file and writing languages parts in output files.
 * The filenames and pathes are not handles by Storage, which uses file handles sent by the caller. The file is neither opened
 * nor closed by Storage but the current file positions are modified by Storage when reading and writing, so callers should not
 * rely on positions nor alter them.
 *
 * See the Filer class for file names handling and files opening.
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
 * @package   mlmd_storage_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

declare(strict_types=1);

namespace MultilingualMarkdown {

    require_once 'OutputModes.class.php';

    class Storage
    {
        // Input file and reading status
        private $buffer = null;                 /// current line content
        private $bufferPosition = 0;            /// current pos in line buffer (utf-8)
        private $bufferLength = 0;              /// current line size in characters (utf-8)
        private $curLine = 0;                   /// current line number from input file
        private $currentChars = [];             /// array of last 3 characters: [0] = current, [1] = previous, [2] = pre-previous

        // Output files and writing status
        private $lastWritten = [];              /// last  character written to file
        private $curOutputs = [];               /// current output buffers for files
        private $outputMode = OutputModes::MD;  /// output html or md style for headings and links in toc

        public function __construct()
        {
            mb_internal_encoding('UTF-8');
        }

        /**
         * Set an input file handle for further reading.
         * This function releases any previous file and resets currents input buffer and status.
         *
         * @param resource $file the file handle for reading. Position is set to the beginning of file.
         *
         * @return bool true if the file and first paragraph is ready.
         */
        public function setInputFile($file): bool
        {
            if (!\is_resource($file)) {
                return false;
            }
            if (isset($this->inFile)) {
                unset($this->inFile);
            }
            $this->inFile = $file;
            \rewind($file);
            if (isset($this->buffer)) {
                unset($this->buffer);
            };
            $this->bufferLength = 0;
            $this->bufferPosition = 0;
            $this->startLine = 0;
            $this->endLine = 0;
            return true;
        }

        /**
<<<<<<< HEAD
         * Return the next UTF-8 paragraph, taken from the input file until an empty line or the end of file.
         * Return false if already at end of file.
         *
         * @return string& a reference to the paragraphh buffer, or null when file and buffer are both finished.
         */
        public function &getNextParagraph(): ?string
        {
            static $nullGuard = null;
            // no: read until empty line (or EOF)
            if (isset($this->buffer)) {
                unset($this->buffer);
            }
            $this->buffer = '';
            do {
                $line = fgets($this->inFile);
                // EOF?
                if (!$line) {
                    // return null now if buffer empty
                    if (empty($this->buffer)) {
                        $this->bufferLength = 0;
                        array_pop($this->currentChars);
                        array_unshift($this->currentChars, null);
                        return $nullGuard;
                    }
                    // end of read, buffer not empty
                    break;
                } else {
                    // delete Windows CR and store
                    $line = \str_replace("\r", '', $line);
                    $this->buffer .= $line;
                    $this->startLine = $this->endLine + 1;
                    $this->endLine += 1;
                }
            // read until empty line
            } while ($line != "\n");
            // init status and characters
            $this->bufferPosition = 0;
            $this->bufferLength = mb_strlen($this->buffer);
            array_pop($this->currentChars);
            array_unshift($this->currentChars, \mb_substr($this->buffer, 0, 1));
            return $this->buffer;
        }

        /**
=======
>>>>>>> f92a1057c7b34f0ccead1d106d443cec207bb72f
         * Read characters from input and append to current buffer until
         * at least $length characters are available or end of file is reached.
         * Content is read line by line until wanted length is reached.
         * Windows CR are deleted from input and ending line number is adjusted.
         * Current char and position do not change.
         *
         * @param int $length number of character to make available in buffer
         */
        public function fetchCharacters(int $length): void
        {
            do {
                if ($this->bufferPosition + $length >= $this->bufferLength) {
                    $line = fgets($this->inFile);
                    if ($line !== false) {
                        $line = rtrim($line, "\n\r") . "\n"; // end of line forced to \n
                        $this->buffer .= $line;
                        $this->bufferLength += mb_strlen($line);
                        if ($this->bufferLength > 4096 && $this->bufferPosition > 1024) {
                            $this->buffer = mb_substr($this->buffer, 1024);
                            $this->bufferPosition -= 1024;
                            $this->bufferLength = mb_strlen($this->buffer);
                        }
                        $this->endLine += 1;
                        if ($this->curLine == 0) {
                            $this->curLine = 1;
                            $this->startLine = 1;
                        }
                    } else {
                        break;
                    }
                }
            } while ($this->bufferLength - $this->bufferPosition < $length);
        }

        /**
         * Get the current paragraph length.
         * Returns the number of UTF-8 characters in the paragraph, including EOLs.
         */
        public function getParagraphLength(): int
        {
            return $this->bufferLength;
        }

        /**
         * Get the starting input line number for current paragraph.
         */
        public function getStartingLineNumber(): int
        {
            return $this->startLine;
        }
        /**
         * Get the ending input line number for current paragraph.
         */
        public function getEndingLineNumber(): int
        {
            return $this->endLine;
        }

        /**
         * Return the current UTF-8 character from current paragraph.
         * Load next paragraph if no paragraph is loaded yet.
         *
         * @return null|string current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function getCurChar(): ?string
        {
            // immediate return if ready
            if ($this->bufferPosition < $this->bufferLength) {
                return $this->currentChars[0];
            }
            // need to append at least 1 char from input
            $this->fetchCharacters(1);
            if ($this->bufferPosition < $this->bufferLength) {
                $this->currentChars[0] = mb_substr($this->buffer, $this->bufferPosition, 1);
                return $this->currentChars[0];
            }
            return null;
        }

        /**
         * Return the previous UTF-8 character .
         *
         * @return null|string previous character ('\n' for EOL).
         */
        public function getPrevChar(): ?string
        {
            return $this->currentChars[1] ?? null;
        }
        /**
         * Return the next UTF-8 character from current buffer, return null if end of file.
         *
         * @return null|string new current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function getNextChar(): ?string
        {
            $this->fetchCharacters(1);
            // EOF?
            if ($this->bufferPosition >= $this->bufferLength - 1) {
                $c = null;;
            } else {
                $this->bufferPosition += 1;
                $c = mb_substr($this->buffer, $this->bufferPosition, 1);
            }
            // adjust previous 3 characters array
            if ($this->currentChars[1] == "\n") {
                $this->curLine += 1;
            }
            array_pop($this->currentChars);         // unset [2]
            array_unshift($this->currentChars, $c); // insert [0]
            return $this->currentChars[0];
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
            $startPosition = max(0, $this->bufferPosition - $charsNumber);
            $length = min($charsNumber, $this->bufferPosition);
            if ($length <= 0) {
                return null;
            }
            return mb_substr($this->buffer, $startPosition, $length);            
        }
        /**
         * Read a number of characters including the current one and return the string.
         * Return null if already at end of file. The current position is set on first
         * character past the string.
         */
        public function getString(int $charsNumber): ?string
        {
            // read char[0]
            $result = $this->currentChar;
            $c = $this->getNextChar();
            // append chars [1..N-1]
            for ($i = 1; ($i < $charsNumber) && ($c != null); $i += 1) {
                $result .= $c;
                $c = $this->getNextChar();
            }
            return $result;
        }
        /**
         * Return next UTF-8 characters from current buffer, return null if end of file.
         * Do not advance reading position, just send back future cahracters to read.
         * If the requested number of characters is not available, return what's left.
         *
         * @param int $charsNumber the number of characters to return
         * @return null|string     the next characters which will be read,  null when file and buffer are finished.
         */
        public function fetchNextChars(int $charsNumber): ?string
        {
            $nextPosition = $this->bufferPosition + 1;
            $this->fetchCharacters($charsNumber);
            if ($nextPosition >= $this->bufferLength) {
                return null;
            }
            return mb_substr($this->buffer, $nextPosition, $charsNumber);
        }

        /**
         * Check if current and next characters match a string in current line buffer.
         * This test fetch necessary characters if the buffer has less than needed
         * left to read.
         *
         * @param string $marker the string to match, starting at current character
         *
         * @return bool true if marker has been found at current place
         */
        public function isMatching(string $marker): bool
        {
            $markerLen = mb_strlen($marker);
            $this->fetchCharacters($markerLen);
            $content = mb_substr($this->buffer, $this->bufferPosition, $markerLen);
            return strcmp($content, $marker) == 0;
        }

        /**
         * Set output mode.
         * If numbering scheme has been set, the output mode will use a numbered format.
         * If not, it will use a non-numbered format.
         * Setting a numbering scheme after setting the output mode will adjust the mode.
         *
         * @param string $name     the output mode name 'md', 'mdpure', 'html' or 'htmlold'
         * param object $numbering the numbering scheme object
         * @param object $logger   the caller object with an error() function
         */
        public function setOutputMode(string $name, object $numbering, ?object $logger = null): void
        {
            $mode = OutputModes::getFromName($name, $numbering);
            if ($mode == OutputModes::INVALID) {
                if ($logger) {
                    $logger->error("invalid output mode name '$name'");
                }
                return;
            }
            $this->outputMode = $mode;
        }
    }
}
