<?php

require 'generator.php';

//MARK: CLI launch

// Create the generator instance
$generator = new \MultilingualMarkdown\Generator;
$generator->setRootDir(getcwd());

// Arguments parsing
$inFilenames = [];
$arg = 1;
// array of parameters => (public) function name to call on the value which follows the parameter
$params = [
    'main=' => 'setMainFilename',   // set the main filename using the string after '='
    '-out=' => 'setOutputHTML',     // set Markdown output mode if 'md' follows the '=', else it set HTML mode
    '-numbering=' => 'setNumbering' // set the headings numbering scheme for headings and TOC
];
while ($arg < $argc) {
    if (strcasecmp($argv[$arg], '-i')==0) {
        if ($arg+1 < $argc) {
            $arg += 1;
            if (!file_exists($argv[$arg])) {
                echo "WARNING: file doesn't exist {$argv[$arg]}\n";
            } else {
                $inFilenames[] = $argv[$arg];
            }
        }
    } else {
        foreach ( $params as $param => $function ) {
            $pos = mb_stripos($argv[$arg], $param, null, 'UTF-8');
            if ($pos !== false) {
                $value = mb_substr($argv[$arg], $pos+mb_strlen($param, 'UTF-8'));
                $generator->$function($value);
                break;
            }
        }
    }
    $arg += 1;
}

// no file: build file list for current directory
if (count($inFilenames)==0) {
    $inFilenames = \MultilingualMarkdown\exploreDirectory(getcwd());
}

// do the job
$generator->parseFiles($inFilenames);

?>