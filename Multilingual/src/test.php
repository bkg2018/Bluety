<?php

declare(strict_types=1);

require_once 'include/Generator.class.php';
use MultilingualMarkdown\Generator;
mb_internal_encoding('UTF-8');

//xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

$generator = new Generator();
$generator->setNumbering('1::&I:-,2::1:-,3::1');
$generator->addInputFile('testdata/test.mlmd');
$generator->setMainFilename("test.mlmd");
$generator->addInputFile('testdata/subdata/secondary.mlmd');
$generator->addInputFile('testdata/subdata/tertiary.mlmd');
$generator->processAllFiles();

//var_dump(xdebug_get_code_coverage());