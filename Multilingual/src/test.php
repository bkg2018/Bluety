<?php

declare(strict_types=1);

//echo getenv('PATH');
//echo phpinfo();

require_once 'include/Generator.class.php';
use MultilingualMarkdown\Generator;

mb_internal_encoding('UTF-8');

$generator = new Generator();
$generator->setNumbering('1::&I:-,2::1:-,3::1');
$generator->addInputFile('testdata/test.mlmd');
$generator->setMainFilename("test.mlmd");
$generator->addInputFile('testdata/subdata/secondary.mlmd');
$generator->addInputFile('testdata/subdata/tertiary.mlmd');
$generator->processAllFiles();