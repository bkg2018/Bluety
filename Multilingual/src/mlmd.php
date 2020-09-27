<?php

/* Multilingual Markdown generator - Main script
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

function displayHelp()
{
    echo "
Multilingual Markdown generator - Main script

Parameters:

-h  display this help.

[-i] <filepath.mlmd|filepath.base.md>
    add a file to input files. MLMD will generate one <filepath.xx.md> 
    file for each languages 'xx' declared in the '.languages' directive.
    If -i is not used the script assumes the parameter is an input file path.

-out html|md
    choose HTML or MD for links and anchors for Table Of Contents

-main <mainFilename[.mlmd|.base.md]>
    add a main file (supposedly the one with a global TOC including levels 1-n)
    and indirectly set the root directory for all other files

-numbering <def>[,...]
    declare at least one heading numbering definition, where <def> is: 
        [<level>]:<symbol><separator>
        where :
            <level> is 1 to 9 (default = 1 or next level after level of previous def)
            <symbol> is from 'A'..'Z', 'a'..'z', '1'..'9'
            <separator> is a single character e.g. '.' or '-'

If no '-i' and '-main' parameter is given, MLMD explores current and sub directories
for '*.base.md' and '*.mlmd' template files and generates files for each template found.
By default, main file will be README.mlmd or README.base.md if such a file is found 
in current directory.
 
The '-main' option sets the base file name referenced by the {main} variable (see below) and
sets the root directory for all links in all files. Preferably, all the other template files
should be in this root directory or in subdirectories.

Template files must be named with .base.md or .mlmd extension, other extensions are ignored.

Directives in templates control the languages specifics files generation.

Global directives on one line:
- .languages    declares languages codes (global)
- .numbering    sets the heading numbering schemes (global, also available
                as script argument and .toc parameter)
- .toc          generates a table of contents using headings levels

Directives anywhere in the text and in headings:
- .all((        starts a section for all languages
- .ignore((     starts an ignored section
- .default((    starts a section for languages which don't have a specific section
- .<language>(( starts a section specific to <language>
- .))           ends a section
- .{ .}         encloses escaped text (no variable expansion)

Text can also be escaped between back-ticks '`', double back_ticks '``', 
code fences '```' and double quotes '\"'. Escaped text neutralizes directives and variables.

The following variables are expanded in the generated files except in escaped text:

{file} expands to the current file name, localised for the language (file.xx.md for language xx)
{main} expands to the '-main' file name, localised for the language (main.xx.md for language xx)
{language} expands to the language code as declared in the '.languages' directive. (xx for language xx)
\n";
}

require_once 'include/Generator.class.php';

//MARK: CLI launch

// Create the generator instance
mb_internal_encoding('UTF-8');
$generator = new \MultilingualMarkdown\Generator();

// array of parameters: 'name' => [(public) function to call on the value, 'type' of value]
// if function starts with a ':', call global function, else call Generator member function.
$params = [
    '-i'            => ['addInputFile',     'file'],    // set one input file
    '-main'         => ['setMainFilename',  'file'],    // set a main filename
    '-out'          => ['setOutputMode',    'string'],  // set Markdown output mode
    '-numbering'    => ['setNumbering',     'string'],  // set the headings numbering scheme for headings and TOC
    '-h'            => [':displayHelp',     '-']        // (global function) display help
];
$arg = 1;
while ($arg < $argc) {
    $done = false;
    foreach ($params as $param => $def) {
        $function = $def[0];
        $type = $def[1];
        $ok = (mb_strtolower($argv[$arg]) == $param);
        if ($ok) {
            if ($arg > $argc - 1) {
                echo "WARNING: Missing value for parameter $param\n";
                $value = '';
            } else {
                $arg += 1;
                $value = $argv[$arg];
            }
            switch ($type) {
                case 'file':
                    if (!file_exists($value)) {
                        echo "ERROR: input file [$value] doesn't exist\n";
                        $ok = false;
                    }
                    break;
                case 'string':
                    if (empty($value)) {
                        echo "ERROR: empty value for parameter $param\n";
                        $ok = false;
                    }
                    break;
                case '-':
                    // no value for this parameter
                    break;
                default:// never happens
                    echo "ERROR: unknown parameter type [$type] in script!\n";
                    exit(1);
            }
            if ($ok) {
                // global or Generator function?
                if ($function[0] == ':') {
                    $function = substr($function, 1);
                    $function($value);
                } else {
                    $generator->$function($value);
                }
            }
            $done = true;
            break;
        }
    }
    if (!$done) {
        // unknown parameter: assume an input file
        if (!file_exists($argv[$arg])) {
            echo "ERROR: input file [$argv[$arg]] doesn't exist\n";
        } else {
            $generator->addInputFile($argv[$arg]);
        }
    }
    $arg += 1;
}

// do the job
$generator->processAllFiles();
echo "OK\n";
