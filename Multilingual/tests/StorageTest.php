<?php

declare(strict_types=1);

namespace MultilingualMarkdown;

use PHPUnit\Framework\TestCase;
use MultilingualMarkdown\Filer;
use MultilingualMarkdown\Storage;

require_once '../src/include/Storage.class.php';

/** Copyright 2020 Francis Piérot
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
 * @package   mlmd_storage_unit_tests
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */
class StorageTest extends TestCase
{
    public function testReadParagraphs()
    {
        $storage = new Storage();
        $file = fopen('test.mlmd', 'rt');
        $this->assertNotFalse($file);
        $storage->setInputFile($file);

        $buffer = '';
        $paragraphCount = 0;
        $bufferLength = 0;
        while ($buffer !== null) {
            $buffer = $storage->getNextParagraph();
            $bufferLength = $storage->getParagraphLength();
            if ($buffer != null) {
                $paragraphCount += 1;
                echo str_repeat('=', 120), "\n";
                echo "[{$storage->getStartingLineNumber()}-{$storage->getEndingLineNumber()}]:", $buffer;
            }
        }
        fclose($file);
        $this->assertEquals(12, $paragraphCount);
        $this->assertEquals(24, $storage->getStartingLineNumber());
        $this->assertEquals(24, $storage->getEndingLineNumber());
        $this->assertEquals(12, $bufferLength); // 'end of file\n'  = 12 characters
    }

    public function testGetChar()
    {
        $storage = new Storage();
        $file = fopen('test.mlmd', 'rt');
        $this->assertNotFalse($file);
        $storage->setInputFile($file);
        $c = $storage->curChar();
        //echo str_repeat('=', 120), "\n";
        $charNumber = 0;
        while ($c !== null) {
            //echo $c;
            $charNumber += 1;
            $c = $storage->nextChar();
        }
        // the test file is 431 bytes but holds 4 times the french 2-bytes UTF-8 character 'ç' so there are 427 characters
        $this->assertEquals(427, $charNumber);
        fclose($file);
    }
}
