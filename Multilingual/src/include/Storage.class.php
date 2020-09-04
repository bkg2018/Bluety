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

    require_once '../src/include/OutputModes.class.php';

    class Storage
    {
        // Input file and reading status
        private $buffer = null;                 /// current line content
        private $bufferPosition = 0;            /// current pos in line buffer (utf-8)
        private $bufferLength = 0;              /// current line size in characters (utf-8)
        private $curLine = 0;                   /// current line number from input file

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
                        $this->curChar = null;
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
            $this->prevChar = $this->curChar ?? '';
            $this->curChar = \mb_substr($this->buffer, 0, 1);
            return $this->buffer;
        }

        /**
         * Get the current paragraph length.
         * Returns the number of UTF-8 characters in the paragraph, including EOLs.
         */
        public function getParagraphLength()
        {
            return $this->bufferLength;
        }

        /**
         * Get the starting input line number for current paragraph.
         */
        public function getStartingLineNumber()
        {
            return $this->startLine;
        }
        /**
         * Get the ending input line number for current paragraph.
         */
        public function getEndingLineNumber()
        {
            return $this->endLine;
        }

        /**
         * Return the current UTF-8 character from current paragraph.
         * Load next paragraph if no paragraph is loaded yet.
         *
         * @return null|string current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function curChar(): ?string
        {
            if (($this->bufferLength <= 0) || ($this->bufferPosition >= $this->bufferLength - 1)) {
                $this->getNextParagraph();
            }
            return $this->curChar;
        }

        /**
         * Return the next UTF-8 character from current buffer, return null if end of file.
         *
         * @return null|string new current character ('\n' for EOL),  null when file and buffer are finished.
         */
        public function nextChar(): ?string
        {
            // any  character left in current buffer?
            if ($this->bufferPosition < $this->bufferLength - 1) {
                $this->bufferPosition += 1;
            } else {
                // no: read next paragraph
                $this->getNextParagraph();
                if ($this->bufferLength == 0) {
                    return null;
                }
            }
            // adjust status
            $this->prevChar = $this->curChar;
            if ($this->prevChar == "\n") {
                $this->curLine += 1;
            }
            // get next utf-8 char
            $this->curChar = mb_substr($this->buffer, $this->bufferPosition, 1);
            //$this->debugEcho();
            return $this->curChar;
        }
    }
}
