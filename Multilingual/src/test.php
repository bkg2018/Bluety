<?php

declare(strict_types=1);

require_once 'include/Generator.class.php';
use MultilingualMarkdown\Generator;

mb_internal_encoding('UTF-8');

$generator = new Generator();
$generator->addInputFile('testdata/test.mlmd');
$generator->setMainFilename("test.mlmd");
$generator->addInputFile('testdata/subdata/secondary.mlmd');
$generator->addInputFile('testdata/subdata/tertiary.mlmd');
$generator->processAllFiles();