<?php

declare(strict_types=1);

namespace MultilingualMarkdown {

    use PHPUnit\Framework\TestCase;
    use MultilingualMarkdown\Generator;

    require_once '../src/include/Generator.class.php';

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
     * @package   mlmd_generator_unit_tests
     * @author    Francis Piérot <fpierot@free.fr>
     * @copyright 2020 Francis Piérot
     * @license   https://opensource.org/licenses/mit-license.php MIT License
     * @link      TODO
     */
    class GeneratorTest extends TestCase
    {
        public function testInitialization()
        {
            $generator = new Generator();
            $generator->addInputFile('data/test.mlmd');
            $generator->setMainFilename("test.mlmd");
            $generator->addInputFile('data/subdata/secondary.mlmd');
            $generator->addInputFile('data/subdata/tertiary.mlmd');
            $generator->processFiles();

            $this->assertTrue(false);
        }
    }

    /*
    //public function setMainLanguage(string $code): void
    //public function addLanguage(string $code): void
    public function readyInputs(): void
    public function openInputFile(string $filename): bool
    public function getBasename(string $path): ?string
    public function getInputFile(int $index): ?string
    public function getInputFilesMaxIndex(): int
    //public function addInputFile(string $path): bool
    public function setMainFilename(string $name = 'README.mlmd'): bool
    public function getRootDir(): ?string
    public function setRootDir(string $rootDir): bool
    */
}
