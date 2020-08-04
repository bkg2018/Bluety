<?php
/**
 * Multilingual Markdown generator - Main script
 *
 * parameters:
 *  -i <filepath.mlmd|filepath.base.md> [...]
 *      generate each <filepath.xx.md> for languages 'xx' declared
 *      in '.languages' directive.
 *  -out=html|md
 *      choose HTML or MD for links and anchors for Table Of Contents
 *  -main=<mainFilename[.mlmd|.base.md]>
 *      chooses the main file (supposedly the one with a global TOC including levels 1-n)
 *      indirectly, sets the root directory for all other files
 *  -numbering=[<def>[,...]] 
 *      where <def> is: 
 *          [<level>]:<symbol><separator>
 *          where :
 *              <level> is 1 to 9 (default = 1 or next level after level of previous def)
 *              <symbol> is from 'A'..'Z', 'a'..'z', '1'..'9'
 *              <separator> is a single character e.g. '.' or '-'
 *
 * If no '-i' parameter is given, MLMD explores current and sub directories
 * for '*.base.md' and '*.mlmd' template files and generates files for each one found.
 * By default, main file will be README.mlmd or README.base.md if such a file is found 
 * in current directory.
 * 
 * The '-main' option sets the file referenced by the {main} variable (see below) and
 * sets the root directory for all links in all files. Preferably, no other template file
 * should be outside of this root directpry and its sub directories.
 *
 * Template files must be named with .base.md or .mlmd extension, other extensions are ignored.
 *
 * Directives in templates control the languages specifics files generation:
 *
 *  - .languages    declares languages codes (global)
 *  - .numbering    sets the heading numbering schemes (global, also available
 *                  as script argument and .toc parameter)
 *  - .toc          generates a table of contents using headings levels
 *  - .all((        starts a section for all languages
 *  - .ignore((     starts an ignored section
 *  - .default((    starts a section for languages which don't have a specific section
 *  - .<language>(( starts a section specific to a language
 *  - .))           ends a section
 *
 * The following variables are expanded in the generated files:
 *
 * {file} expands to the current file name, localised for the language
 * {main} expands to the '-main' file name, localised for the language
 * {language} expands to the language code as declared in the '.languages' directive.
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
 * @package   mlmd_heading_class
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      TODO
 */

require 'generator.class.php';

//MARK: CLI launch

// Create the generator instance
$generator = new \MultilingualMarkdown\Generator();

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
    if (strcasecmp($argv[$arg], '-i') == 0) {
        if ($arg + 1 < $argc) {
            $arg += 1;
            if (!file_exists($argv[$arg])) {
                echo "WARNING: file doesn't exist {$argv[$arg]}\n";
            } else {
                $inFilenames[] = realpath($argv[$arg]);
            }
        }
    } else {
        foreach ($params as $param => $function) {
            $pos = mb_stripos($argv[$arg], $param, null, 'UTF-8');
            if ($pos !== false) {
                $value = mb_substr($argv[$arg], $pos + mb_strlen($param, 'UTF-8'));
                $generator->$function($value);
                break;
            }
        }
    }
    $arg += 1;
}

// no file yet: build file list for current directory
if (count($inFilenames) == 0) {
    if ($generator->getRootDir() === false) {
        $generator->setRootDir(getcwd());
    }
    $inFilenames = \MultilingualMarkdown\exploreDirectory($generator->getRootDir());
}

// do the job
$generator->parseFiles($inFilenames);
