<?php
declare(strict_types=1);
require_once __DIR__ .'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use MultilingualMarkdown\Heading;
use MultilingualMarkdown\HeadingArray;
use MultilingualMarkdown\OutputModes;
use MultilingualMarkdown\Numbering;

require_once 'src/include/HeadingArray.class.php';

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
 * @package   mlmd_headingarray_unit_tests
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */
class HeadingArrayTest extends TestCase
{
    // build a test case
    private function getTestData()
    {
        $a = new HeadingArray('main.mlmd');
        $a->add(new Heading('# heading 1', 1, null));               // 0
        $a->add(new Heading('## heading 1.1', 3, null));            // 1
        $a->add(new Heading('# heading 2', 5, null));               // 2
        $a->add(new Heading('## heading 2.1', 7, null));            // 3
        $a->add(new Heading('## heading 2.2', 9, null));            // 4
        $a->add(new Heading('### heading 2.2.1', 11, null));        // 5
        $a->add(new Heading('### heading 2.2.2', 13, null));        // 6
        $a->add(new Heading('### heading 2.2.3', 15, null));        // 7
        $a->add(new Heading('## heading 2.3', 17, null));           // 8
        $a->add(new Heading('### heading 2.3.1', 19, null));        // 9
        $a->add(new Heading('#### heading 2.3.1.1', 21, null));     // 10
        return $a;
    }

    // heading lines data for scheme with roman numbers
    private function getHeadingDataRoman()
    {
        return ['.((Chapter.)).fr((Chapitre.)) I) heading 1<A id="a1"></A>',
        'I-1) heading 1.1<A id="a2"></A>',
        '.((Chapter.)).fr((Chapitre.)) II) heading 2<A id="a3"></A>',
        'II-1) heading 2.1<A id="a4"></A>',
        'II-2) heading 2.2<A id="a5"></A>',
        'II-2.a) heading 2.2.1<A id="a6"></A>',
        'II-2.b) heading 2.2.2<A id="a7"></A>',
        'II-2.c) heading 2.2.3<A id="a8"></A>',
        'II-3) heading 2.3<A id="a9"></A>',
        'II-3.a) heading 2.3.1<A id="a10"></A>',
        'II-3.a.1) heading 2.3.1.1<A id="a11"></A>'];
    }

    // TOC lines data for scheme with roman numbers
    private function getTOCDataRoman()
    {
        return ['- .((Chapter.)).fr((Chapitre.)) I) heading 1<A id="a1"></A>',
        '- I-1) heading 1.1<A id="a2"></A>',
        '- .((Chapter.)).fr((Chapitre.)) II) heading 2<A id="a3"></A>',
        '- II-1) heading 2.1<A id="a4"></A>',
        '- II-2) heading 2.2<A id="a5"></A>',
        '- II-2.a) heading 2.2.1<A id="a6"></A>',
        '- II-2.b) heading 2.2.2<A id="a7"></A>',
        '- II-2.c) heading 2.2.3<A id="a8"></A>',
        '- II-3) heading 2.3<A id="a9"></A>',
        '- II-3.a) heading 2.3.1<A id="a10"></A>',
        '- II-3.a.1) heading 2.3.1.1<A id="a11"></A>'];
    }

    public function testMisc()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();

        $index = $a->findIndex(2,6);
        $this->assertEquals(3, $index);

        $a->resetCurrent();
        $h = $a->getNext();
        $this->assertEquals(2, $h->getLevel());
        $h = $a->getNext();
        $h = $a->getNext();
        $h = $a->getNext();
        $h = $a->getNext();
        $this->assertEquals(11, $h->getLine());

        $this->assertFalse($a->isHeadingLastBetween(-1, 1, 3));
        $h = $a->getNext();
        $this->assertFalse($a->isHeadingLastBetween(-1, 1, 3));
        $h = $a->getNext();
        $this->assertFalse($a->isHeadingLastBetween(-1, 1, 3));
        $h = $a->getNext();
        $this->assertFalse($a->isHeadingLastBetween(-1, 1, 3));
        $h = $a->getNext();
        $this->assertTrue($a->isHeadingLastBetween(-1, 1, 3));
        

    }

    public function testSpacing()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();

        $test = $a->getSpacing(0); // #
        $this->assertEmpty($test);
        $a->setOutputMode('html');
        $test = $a->getSpacing(5); // ###
        $this->assertEquals(str_repeat('&nbsp;', 4 * 2), $test);
        $a->setOutputMode('md');
        $test = $a->getSpacing(5); // ###
        $this->assertEquals(str_repeat(' ', 2 * 2), $test);
        $a->setOutputMode('mdpure');
        $test = $a->getSpacing(5); // ###
        $this->assertEquals(str_repeat(' ', 3 * 2), $test);
    }

    public function testAnchors()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();

        $a->setOutputMode('htmlold');
        $test = $a->getAnchor(0);// #
        $this->assertEquals('<A name="a1"></A>', $test);
        $a->setOutputMode('html');
        $test = $a->getAnchor(5); // ###
        $this->assertEquals('<A id="a6"></A>', $test);
        $a->setOutputMode('md');
        $test = $a->getAnchor(5); // ###
        $this->assertEquals('<A id="a6"></A>', $test);
        $a->setOutputMode('mdpure');
        $test = $a->getAnchor(5); // ###
        $this->assertEquals('{#a6}', $test);
    }

    public function testTOCLink()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();

        $a->setOutputMode('htmlold');
        $test = $a->getTOCLink('dummyfile.md', 0, 1, 3);
        $this->assertEquals('<A href="dummyfile.md#a1">heading 1</A><BR>', $test);
        $test = $a->getTOCLink('dummyfile.md', 6, 1, 3);
        $this->assertEquals('<A href="dummyfile.md#a7">heading 2.2.2</A><BR>', $test);

        $a->setOutputMode('html');
        $test = $a->getTOCLink('dummyfile.md', 0, 1, 3);
        $this->assertEquals('<A href="dummyfile.md#a1">heading 1</A><BR>', $test);
        $test = $a->getTOCLink('dummyfile.md', 6, 1, 3);
        $this->assertEquals('<A href="dummyfile.md#a7">heading 2.2.2</A><BR>', $test);

        $a->setOutputMode('md');
        $test = $a->getTOCLink('dummyfile.md', 0, 1, 3);
        $this->assertEquals('[heading 1](dummyfile.md#a1)', $test);
        $test = $a->getTOCLink('dummyfile.md', 6, 1, 3);
        $this->assertEquals('[heading 2.2.2](dummyfile.md#a7)', $test);

        $a->setOutputMode('mdpure');
        $test = $a->getTOCLink('dummyfile.md', 0, 1, 3);
        $this->assertEquals('[heading 1](dummyfile.md#a1)', $test);
        $test = $a->getTOCLink('dummyfile.md', 6, 1, 3);
        $this->assertEquals('[heading 2.2.2](dummyfile.md#a7)', $test);
    }

    public function testNumbering()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();
        $numbering = New Numbering('1:Chapter:A:-,2::1:.,3::a:.,4::1:');
        $numbering->setLevelLimits(1,3);

        $test = $a->getNumberingText(0, $numbering, true);
        $this->assertEquals('- Chapter A) ', $test);
        $test = $a->getNumberingText(6, $numbering, true);
        $this->assertEquals('- B-2.b) ', $test);

        $numbering = New Numbering('1:.((Chapter.)).fr((Chapitre.)):&I:-,2::1:.,3::a:.,4::1:');
        $numbering->setLevelLimits(1,3);

        $test = $a->getNumberingText(0, $numbering, true);
        $this->assertEquals('- .((Chapter.)).fr((Chapitre.)) I) ', $test);
        $test = $a->getNumberingText(6, $numbering, true);
        $this->assertEquals('- II-2.b) ', $test);

        /*
        $numbering->resetNumbering();
        echo "\n\n";
        for ($i = 0 ; $i <= $a->getLastIndex() ; $i += 1) {
            $test = $a->getNumberingText($i, $numbering, true);
            echo "$i: $test\n";
        }
        */

    }

    public function testHeadingLines()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();
        $h = $this->getHeadingDataRoman();
        $numbering = New Numbering('1:.((Chapter.)).fr((Chapitre.)):&I:-,2::1:.,3::a:.,4::1:');
        $numbering->setLevelLimits(1,3);
        $numbering->resetNumbering();
        echo "\n\n";
        for ($i = 0 ; $i <= $a->getLastIndex() ; $i += 1) {
            $test = $a->getHeadingLine($i, $numbering);
            if ($test) {
                $this->assertEquals($h[$i], $test);
            }
        }

    }    

    public function testTOCLines()
    {
        Heading::init(); //: reset global numbers
        $a = $this->getTestData();
        $h = $this->getTOCDataRoman();
        $numbering = New Numbering('1:.((Chapter.)).fr((Chapitre.)):&I:-,2::1:.,3::a:.,4::1:');
        $numbering->setLevelLimits(1,3);
        echo "\n\nMD:";
        $a->setOutputMode('md', $numbering);
        /*
        for ($i = 0 ; $i <= $a->getLastIndex() ; $i += 1) {
            $test = $a->getTOCLine($i, $numbering);
            if ($test) {
                echo "\n$test";
            }
            //$this->assertEquals($h[$i], $test);
        }
        */
        $this->assertEquals('  - II-2) [heading 2.2](main.mlmd#a5)', $a->getTOCLine(4, $numbering));
        $this->assertEquals('    - II-3.a) [heading 2.3.1](main.mlmd#a10)', $a->getTOCLine(9, $numbering));
        $this->assertNull($a->getTOCLine(10, $numbering));

        echo "\n\nMDPURE:";
        $a->setOutputMode('mdpure', $numbering);
        /*
        for ($i = 0 ; $i <= $a->getLastIndex() ; $i += 1) {
            $test = $a->getTOCLine($i, $numbering);
            if ($test) {
                echo "\n$test";
            }
            //$this->assertEquals($h[$i], $test);
        }
        */
        $this->assertEquals('   2. [heading 2.2](main.mlmd#a5)', $a->getTOCLine(4, $numbering));
        $this->assertEquals('      1. [heading 2.3.1](main.mlmd#a10)', $a->getTOCLine(9, $numbering));
        $this->assertNull($a->getTOCLine(10, $numbering));

        echo "\n\nHTML:";
        $a->setOutputMode('html', $numbering);
        /*
        for ($i = 0 ; $i <= $a->getLastIndex() ; $i += 1) {
            $test = $a->getTOCLine($i, $numbering);
            if ($test) {
                echo "\n$test";
            }
            //$this->assertEquals($h[$i], $test);
        }
        */
        $this->assertEquals('&nbsp;&nbsp;&nbsp;&nbsp;II-2) <A href="main.mlmd#a5">heading 2.2</A><BR>', $a->getTOCLine(4, $numbering));
        $this->assertEquals('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;II-3.a) <A href="main.mlmd#a10">heading 2.3.1</A>', $a->getTOCLine(9, $numbering));
        $this->assertNull($a->getTOCLine(10, $numbering));
    }
}