<?php

declare(strict_types=1);
require_once 'include/Generator.class.php';
use MultilingualMarkdown\Generator;
mb_internal_encoding('UTF-8');

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

$generator = new Generator();
$generator->setOutputMode('html');
$generator->setNumbering('1::&I:-,2::1:-,3::1');
$generator->addInputFile('testdata/test.mlmd');
$generator->setMainFilename("test.mlmd");
$generator->addInputFile('testdata/subdata/secondary.mlmd');
$generator->addInputFile('testdata/subdata/tertiary.mlmd');
$generator->setOutputDirectory(realpath('.') . '/testdata/out');
$generator->processAllFiles();

$allCoverage = xdebug_get_code_coverage();
echo "Coveraging...\n";
foreach ($allCoverage as $inFilepath => $coverage) {
    $outFilepath = $inFilepath . '.log';
    $inFile = fopen($inFilepath, "r");
    $outFile = fopen($outFilepath, "w");
    $inLine = fgets($inFile);
    $lineNumber = 1;
    while ($inLine != null) {
        $prefix = '   ';
        if (array_key_exists($lineNumber, $coverage)) {
            switch ($coverage[$lineNumber]) {
                case 1: $prefix = '[*]'; break;
                case -1:$prefix = '[ ]'; break;
                case -2:$prefix = ' - '; break;
                default: break;
            }
        }
        fputs($outFile, $prefix . ' ' . $inLine);
        $inLine = fgets($inFile);
        $lineNumber += 1;
    }
    fclose($inFile);
    fclose($outFile);
}