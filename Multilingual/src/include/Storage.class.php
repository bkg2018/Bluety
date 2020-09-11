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
        private $currentChar = '';                  /// current character in input
        private $previousChar = '';                 /// previous value of $currentChar
        private $prePreviousChar = '';              /// previous previous value of $currentChar

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
                        $this->currentChar = null;
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
            $this->prePreviousChar = $this->previousChar ?? '';
            $this->previousChar = $this->currentChar ?? '';
            $this->currentChar = \mb_substr($this->buffer, 0, 1);
            return $this->buffer;
        }

        /**
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
                        $line = \str_replace("\r", '', $line);// delete Windows CR
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
                return $this->currentChar;
            }
            // need to read at least 1 char
            $this->fetchCharacters(1);
            if ($this->bufferPosition < $this->bufferLength) {
                $this->currentChar = mb_substr($this->buffer, $this->bufferPosition, 1);
                return $this->currentChar;
            }
            return null;
        }

        /**
         * Return the previous UTF-8 character .
         *
         * @return null|string previous character ('\n' for EOL).
         */
        public function prevChar(): ?string
        {
            return $this->previousChar;
        }
        /**
         * Return the previous previous UTF-8 character .
         *
         * @return null|string previous character ('\n' for EOL).
         */
        public function prePrevChar(): ?string
        {
            return $this->prePreviousChar;
        }
        /**
         * Return the next UTF-8 character from current buffer, return null if end of file.
         *
         * @return null|string new current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function getNextChar(): ?string
        {
            $this->prePreviousChar = $this->previousChar;
            $this->previousChar = $this->currentChar;
            if ($this->previousChar == "\n") {
                $this->curLine += 1;
            }
            // end of file?
            $this->fetchCharacters(1);
            if ($this->bufferPosition >= $this->bufferLength - 1) {
                $this->currentChar = null;
            } else {
                // get next utf-8 char
                $this->bufferPosition += 1;
                $this->currentChar = mb_substr($this->buffer, $this->bufferPosition, 1);
            }
            return $this->currentChar;
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
    }
}
