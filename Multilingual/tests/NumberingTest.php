<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use MultilingualMarkdown\Numbering;

require_once 'src/include/Numbering.class.php';

class NumberingTest extends TestCase
{
    public function error($msg) : void
    {
        error_log("Error in Numbering: $msg");
    }

    public function testNoNumbering()
    {
        $numbering = new Numbering(0,0,'', $this);
        $numbering->resetNumbering();
        $test = $numbering->getNumbering(1);
        $this->assertEmpty($test);
        $test = $numbering->getNumbering(2);
        $this->assertEmpty($test);
    }
}