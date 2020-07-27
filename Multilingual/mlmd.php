<?php 
/** Multilingual Markdown generator 
 * parameters: 
 *  -i <filepath.mlmd|filepath.base.md> [...]
 *      generate each <filepath.xx.md> for languages 'xx' declared in '.languages' directive.
 *  -out=html|md
 *      choose HTML or MD for links and anchors for Table Of Contents 
 *  -main=<mainFilename[.mlmd|.base.md]>
 *      choose the main file (generaly the one with global TOC)
 * 
 * If no parameter is given, explore current and sub directories for '*.base.md' and '.mlmd' files
 * and generate files for each file found. By default, main file will be README.mlmd or README.base.md
 * if such a file is found in current directory.
 * 
 * Template files must be named with .base.md or .mlmd
 * 
 * The following variables are expanded in the generated files:
 * 
 * {file} expands to the current file name, localised for the language
 * {main} expands to the '-main' file name, localised for the language
 * {language} expands to the language code as declared in the '.languages' directive.
*/

//MARK: Global Utility functions

/** Check if a filename has an MLMD valid extension.
 *  @param string $filename the file name or path to test.
 *  @return string the file extension (.base.md or .mlmd), or false is not a valid mlmd file name. 
 */
function isMLMDfile($filename) {
    $extension = ".base.md";
    $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
    if ($pos===false) {
        $extension = ".mlmd";
        $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
        if ($pos===false) return false;
    }
    return $extension;
}

/** Recursively explore a directory and its subdirectories and return an array of each '.base.md' and '.mlmd' file found. 
 *  @param string $dirName the directory to test, either relative to current directory or abcolute path
 *  @return array pathes of each file found, relative to $dirName.
*/
function exploreDirectory($dirName) {
    $dir = opendir($dirName);
    $filenames = [];
    if ($dir !== false) {
        while (($file = readdir($dir)) !== false) {
            if ($file=='.') continue;
            if ($file=='..') continue;
            $thisFile = $dirName . '/' . $file;
            if (is_dir($thisFile)) {
                $filenames = array_merge($filenames, exploreDirectory($thisFile));
            } else if (isMLMDfile($thisFile)) {
                $filenames[] = $thisFile;
            }
        }
        closedir($dir);
    }
    return $filenames;
}

//MARK: Heading class

/** heading class, used in generator $headings array of all headings for all files. */
class Heading {
    public $number = 0;     // unique number over all files and headings
    public $text = '';      // heading text, including MLMD directives if needed
    public $level = 0;      // heading level = number of '#'s
    public $line = '';      // line number in source file
}

//MARK: Generator class

/** Generator class. */
class MultilingualMarkdownGenerator {

    // Directives and handling function names
    private $directives = [
        '.all(('        => 'all',           /// push current section and open all languages section
        '.ignore(('     => 'ignore',        /// push current section and open ignored section
        '.default(('    => 'default',       /// push current section and open default text section
        '.(('           => 'default',       /// same as .default((
        '.))'           => 'end',           /// close current section and pop previous section
        '.languages'    => 'languages',     /// declare languages and start files generation
        '.toc'          => 'toc'            /// insert a table of contents
    ];

    // Current input filename, file and reading status
    private $inFilename = null;             /// 'example.base.md'
    private $inFile = null;                 /// input file handle
    private $lineBuf = "";                  /// current line content
    private $lineBufPos = 0;                /// current pos in line buffer (utf-8)
    private $lineBufLength = 0;             /// current line size in characters (utf-8)
    private $prevChar = '';                 /// used to detect line and heading start
    private $curChar = '';                  /// current char
    private $curLine = 1;                   /// current line number in current input file

    // Current output filenames, files and writing status
    private $outFilenameTemplate = null;    /// as 'example'
    private $outFilenames = [];             /// '<language>' => 'example.md' / 'example.<language>.md'
    private $outFiles = [];                 /// '<language>' => file-handle
    private $lastWritten = [];              /// last  character written to file
    private $curOutputs = [];               /// current output buffers for files
    private $outputHTML = true;             /// true=html or false=md, format for headings anchors and toc links
    private $mainFilename = false;          /// -main parameter

    // Directives status
    private $languages = [];                // declared languages
    private $mainLanguage =false;           // optional main language (<code> deleted from MD output filename)
    private $curLanguage = 'ignore';        // current opened directive (all / <code> / ignore / default)
    private $directiveStack = [];           // stack of previous directives
    private $languagesSet = false;          // ignore any input until '.languages' directive has been processed
    private $L1headingSet = false;          // ignore EOL until level 1 heading has been processed

    // All files headings for toc
    private $headings = [];                 // all headers objects for each relative filename
    private $relFilenames = [];             // relative filenames for each filename

    /** Set the output mode. 
     *  @param bool $yes true to set the HTML mode (<A> links and anchors), false for MD mode ([]() links and {:# } anchors)
    */
    public function setOutputHTML($yes) {
        $this->outputHTML = $yes;
    }

    /** Set root directory for relative filenames.
     *  @param string $rootDir the root directory, preferably an absolute path
     */
    public function setRootDir($rootDir) {
        $this->rootDir = $rootDir;
    }

    /** Set the main file name. 
     *  @param string $name the name of the main template file. Default is 'README.mlmd' in the root directory.
    */
    public function setMainFilename($name = 'README.mlmd') {
        $basename = $this->getBasename($name);
        if ($basename !== false) {
            $this->mainFilename = $basename;
        }
    }

    /** Get relative basename (no extension) from a filepath. 
     *  @param string $path the path to the file.
     *  @return string|bool the base name, without extension and using a path relative to rootDir, or false if the path is not under rootDir
    */
    public function getBasename($path) {
        //  build relative path against root dir
        $rootLen = mb_strlen($this->rootDir,'UTF-8');
        $baseDir = mb_substr(realpath($path), 0, $rootLen, 'UTF-8');
        if ($baseDir != $this->rootDir) {
            $this->error("wrong root dir for file [$path], should be [$this->rootDir]");
            return false;
        } 
        $extension = isMLMDfile(basename($path));
        $path = mb_substr(realpath($path), $rootLen + 1, null, 'UTF-8');
        return mb_substr($path, 0, -mb_strlen($extension,'UTF-8'), 'UTF-8');
    }

    /** Expand variables in a text for a language.
     *  {file} is replaced by the relative path of the generated file (like 'example.en.md' for 'example.mlmd' template)
     *  {main} is replaced by the relative path of the main file as declared by the '-main=' script parameter
     *  {language} is replaced by the current language
     *  Notice that {main} won't be replaced if no '-main=' parameter was given to the script.
     *  @param string $text the original text
     *  @param string $basename the base name for current input file (like 'example' for 'example.mlmd' template)
     *  @param string $language the current language for generation (like 'en')
     *  @return string the text with expanded variables
     */
    public function expandVariables($text, $basename, $language) {
        $text = str_replace('{file}', $basename . ($language == $this->mainLanguage ? '.md' : ".{$language}.md"), $text);
        if ($this->mainFilename !== false) {
            $text = str_replace('{main}', $this->mainFilename . ($language == $this->mainLanguage ? '.md' : ".{$language}.md"), $text);
        }
        $text = str_replace('{language}', $language, $text);
        return $text;
    }

    /** Parser Tool: read characters from current file until a given character is found. 
     *  @param string $marker the ending character
     *  @return string the characters read until the marker. The marker itself is not returned but is available as $this->curChar. 
     *                 Returns empty string if already on marker.
     */
    private function getCharUntil($marker) {
        $content = '';
        $this->getChar($this->inFile);/// get first title character
        while (($this->curChar !== false) && ($this->curChar !== $marker) && ($this->curChar !== "\n")) {
            $content .= $this->curChar ?? '';
            $this->getChar($this->inFile); // next char
            if ($this->curChar=="\n") {
                $this->curLine += 1;
            }
        }
        return $content;
    }

    /** Debugging echo. */
    private function debugEcho() {
        if (getenv("debug")=="1") {
            if (($this->curChar !== false) && ($this->prevChar=="\n")) {
                echo "[{$this->curLine}]:";
            }
            echo $this->curChar;
        }
    }

    /** Send an error message to output and php log. */
    private function error($msg) {
        //echo "ERROR: $msg\n";
        error_log("{$this->inFilename}({$this->curLine}): MLMD error: $msg");
    }

    //MARK: Directives

    /** .all(( - start to send to all languages. */
    private function all($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'all';
        $this->resetParsing();
    }
    /** .ignore(( - start to ignore text. */
    private function ignore($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'ignore';
        $this->resetParsing();
    }
    /** .(( or .default(( - start default text. */
    private function default($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'default';
        $this->resetParsing();
    }
    /** .)) - return to previous directive. */
    private function end($dummy) {
        if (count($this->directiveStack) > 0) {
            $this->curLanguage = array_pop($this->directiveStack);
        } else {
            $this->curLanguage = 'all';
        }
        $this->resetParsing();
    }
    /** .<language>(( - start to send to one declared language. */
    private function change($directive) {
        $code = mb_strtolower(mb_substr($directive, 1, -2,'UTF-8'),'UTF-8'); // '.en(('  -> 'en'
        if (array_key_exists($code, $this->languages)) {
            array_push($this->directiveStack,$this->curLanguage);
            $this->curLanguage = $code;
            $this->resetParsing();
        }
    }

    /** .languages - declare authorized languages. */
    private function languages($dummy) {
        $curWord = '';
        $stopNow = false;
        do {
            $this->getChar($this->inFile);
            if ($this->curChar!==false) {
                switch ($this->curChar) {
                    // CR?
                    case "\r":
                        // do not store (normalize to unix EOL)
                        break;
                    // line feed?
                    case "\n":
                        // stop current directive 
                        $stopNow = true;
                        // but finish with current word (fall-through)
                        // end of keyword?
                    case ' ':
                    case ',':
                        // if we have a word, set it as additionnal language
                        if (!empty($curWord)) {
                            $this->addLanguage($curWord);
                            $curWord = '';
                        }
                        break;
                    // store current keyword
                    default:
                        $curWord .= $this->curChar;
                        break;
                }
            }
        } while (($this->curChar !== false) && !$stopNow);

        // finish languages registration, open files, set initial status
        if ($this->outFilenameTemplate==null) return false;
        foreach($this->languages as $language => $bool) {
            if ($this->mainLanguage == $language) {
                $this->outFilenames[$language] = "{$this->outFilenameTemplate}.md";
            } else {
                $this->outFilenames[$language] = "{$this->outFilenameTemplate}.$language.md";
            }
            // open the output file
            $this->outFiles[$language] = fopen($this->outFilenames[$language], 'wb');
            // and record 'change' action for '.<code>((' directive
            $this->directives[".{$language}(("] = 'change';
        }
        // set initial status
        $this->resetParsing();
        $this->prevChar = "\n";
        $this->curLine += 1; 
        $this->curLanguage = 'all';
        $this->languagesSet = true;
        array_push($this->directiveStack,$this->curLanguage);
        return true;
    }

    /** .toc - Generate a TOC in current output files using the directive parameters and the headings array.
     * 
     *  The generator has an outputHTML field to format links and anchors in two possibles modes.
     * 
     *  HTML mode (outputHTML=true):
     *  Headings all have an anchor prior to the title, with a heading number:
     *      <a name="h12"></a>
     *      ### Heading text
     *  The TOC must link to these anchors using the following model:
     *      [1.2 Heading text](<file>#h12)
     * 
     *  MD mode (outputHTML=false):
     *  Headings have an automatic MD anchor with their cleaned text, and they can define one using {: }:
     *      ### Heading text {: #h12-heading-text}
     *  The TOC must link to these headings using the cleaned text:
     *      [1.2 Heading text](<file>#heading-text)
     *  WARNING: MD method requires that all headings from a given file are unique in their file. 
     *  If this is not true then HTML mode is more appropriate and will always work
     * 
     *  Numbering is optional and choosen by the directive parameters:
     * 
     *  .TOC [level=m-n] [title=m,"<title text>"] [number=m:<symbol><sep>[,...]] [format=html|md]
     * 
     *  level=m-n                   : use headings from level m to n. m defaults to 1, n defaults to 9
     * 
     *  title=m,"<title text>"      : text for the TOC title with heading level m, language directives can be used
     * 
     *  number=m:<symbol><sep>,...  : prefix TOC titles with numbering or labelling. Syntax for m:<symbol><sep>
     *                                  - `m` is the heading level
     *                                  - <symbol> is a number (e.g: `1`) or a letter (e.g: `a`) for this level, 
     *                                    case (`a` or `A`) is preserved and numbering starts with the given value
     *                                  - `sep` is the symbol to use after this level numbering, e.g `.` or `-`
     * 
     * Example to number level 1 with uppercase letters, followed by a dash '-', level 2 with a number starting at 1 and followed by a dot,
     * and level 3 as level 2, and with a title heading level of 2 (prefixed with '##'):
     * 
     * .TOC level=1-3 title=2,.fr((Table des matières.)).en((Table Of Contents)) number=1:A-,2:1.,3:1
     * 
    */
    private function toc($dummy) {
        // default parameters
        $title = "Table Of Contents";               // <text>    in title=m,"<text>"
        $titleLevel = 2;                            // m         in title=m,"<text>"
        $startLevel = 2;                            // m         in level=m[-n]
        $endLevel = 4;                              // n         in level=[m]-n
        $levelsNumbering = [2=>'1',3=>'1',4=>'1'];  // m=>symbol in number=m:<symbol><sep>,...
        $levelsSeparator = [2=>'.',3=>'.',4=>''];   // m=>sep    in number=m:<symbol><sep>,...
        $outmode = 'html';                          // format=html by default
        // skip initial space
        $this->curWord = trim($this->getChar($this->inFile)); 
        do {
            $this->getChar($this->inFile);

            // add to current word and check keywords
            $this->curWord .= $this->curChar;
            switch (strtolower($this->curWord)) {
                case 'title=': // title=m,"<text>"
                    // get level
                    $titleLevel = $this->getCharUntil(',');
                    // parse and set toc title
                    $title = $this->getCharUntil('"');// skip to " or \n
                    if ($this->curChar != '"') {
                        $this->error("no '\"' around title text, got this: [$title]");
                    } else {
                        $title = $this->getCharUntil('"');
                    }
                    $this->curWord = '';
                break;
                case 'out=': // out=html|md
                    // get level
                    $outmode = $this->getCharUntil(' ');
                    if (strcasecmp($outmode,'md')!=0) $outmode = 'html';
                    break;
                case 'level=':
                    // get definition until next space
                    $def = $this->getCharUntil(' ');
                    $dashpos=mb_stripos($def, '-', 0, 'UTF-8');
                    if ($dashpos === false) {
                        // only one level
                        $startLevel = $endLevel = (int)$def;
                    } else {
                        $startLevel = (int)mb_substr($def,0,$dashpos,'UTF-8');
                        $endLevel   = (int)mb_substr($def,$dashpos+1,null,'UTF-8');
                        if (empty($startLevel)) $startLevel = 1;
                        if (empty($endLevel)) $endLevel = 9;
                        for($level = $startLevel ; $level <= $endLevel ; $level += 1) {
                            $levelsNumbering[$level] = '1';
                            $levelsSeparator[$level] = '.';
                        }
                        $levelsSeparator[$endLevel] = '';
                    }
                    $this->curWord = '';
                break;
                case 'number=':
                    // get definition until next space
                    $defs = explode(',',$this->getCharUntil(' '));
                    $levelsNumbering = [];
                    $levelsSeparator = [];
                    foreach($defs as $def) {
                        $parts = explode(':',$def);
                        $level = $parts[0];
                        if (count($parts) > 1) {
                            $levelsNumbering[$level]=mb_substr($parts[1],0,1,'UTF-8');
                            $levelsSeparator[$level]=mb_substr($parts[1],1,1,'UTF-8');
                        } else {
                            $levelsNumbering[$level]='';
                            $levelsSeparator[$level]='';
                        }
                    }
                    $this->curWord = '';
                break;
                case ' ':/// separator
                    $this->curWord = '';
                default:
                break;
            }
        } while ($this->curChar !== false && $this->curChar != "\n");
        ksort($levelsNumbering, SORT_NUMERIC);
        ksort($levelsSeparator, SORT_NUMERIC);

        // generate TOC title with forced 'toc' anchor
        $this->storeHeading($titleLevel, $title, 'toc');
        // generate toc lines for each file, only if start level is 1
        if ($startLevel == 1) {
            $numbering = [$startLevel => 0];
            foreach($this->headings as $basename => $headings) {
                // store first level 1 heading for the file
                $index = $this->findHeadingIndex($headings, $startLevel, 0);
                if ($index === false) continue; // should not happen!
                $line = $headings[$index]->line;// remember level 1 line
                $this->storeTOClines($startLevel, $startLevel, $startLevel, $numbering, $index, $headings, $levelsNumbering, $levelsSeparator, $basename);
                // and store the other levels if any
                $index = $this->findHeadingIndex($headings, $startLevel + 1, $line+1);
                if (($endLevel > 1) && ($index !== false)) {
                    $numbering[$startLevel + 1] = 0;
                    $result = $this->storeTOClines($startLevel, $endLevel, $startLevel + 1, $numbering, $index, $headings, $levelsNumbering, $levelsSeparator, $basename);
                }
            }
            /// end toc
            $this->storeContent("\n\n",null,false,false,true);// keep EOLs

        } else {
            $numbering = [ $startLevel => 0];
            $headings = $this->headings[$this->inFilename];
            $index = $this->findHeadingIndex($headings, $startLevel, 0);
            if ($index !== false) {
                $this->storeTOClines($startLevel, $endLevel, $startLevel, $numbering, $index, $headings, $levelsNumbering, $levelsSeparator, $basename);
            } else {
                $this->error("starting level not found for TOC in file {$this->inFilename}");
            }
        }

    }

    /** Store toc lines.
     * 
     *  The lines to print are defined with the following prameters:
     * 
     *  - startLevel -> 2, 3, 4: first heading level, exit when level below this
     *  - endLevel: last heading level, ignore headings with levels above this
     *  - numbering -> A, a, 1: gives the current numbering for each level
     *  - index -> 0, 1, 2... : gives the index of first heading to look
     * 
     *  And the following arrays and data are also given for linking and numbering headings:
     * 
     *  - headings: array of objects, which each object describing a heading from current file 
     *  - levels numbering and separators: array of symbols to define the prefix of headings in the toc
     *  - basename: base filename where to link, replaces '{file}' in content
     * 
     *  The heading objects have the following fields:
     *  - number: a number unique to all headings of all files, used as destination for link
     *  - line: the line number in the file
     *  - level: the heading level (number of '#'s)
     *  - text: the heading text (can include MLMD directives, doesn't include '#' prefix nor numbering)
     *
     *  @param int $startLevel the starting heading level to put in TOC
     *  @param int $endLevel the maximum heading level to put in TOC
     *  @param int $curLevel the current heading level for this line
     *  @param array $curNumbering [IN/OUT] the current numbers for for each level
     *  @param int $curIndex [IN/OUT] the current index in the headings array
     *  @param array $headings the array of all headings, in the file order
     *  @param array $levelsNumbering numbering schemes for each level exx:: [2=>'1',3=>'1',4=>'1'];
     *  @param array $levelsSeparator separators for each level numbering scheme exx: [2=>'.',3=>'.',4=>''];
     *  @param string $basename the base name for the file containing the headings
     *  @return -
    */
    private function storeTOClines($startLevel, $endLevel, $curLevel, &$curNumbering, &$curIndex, &$headings, $levelsNumbering, $levelsSeparator, $basename) {
        // truncate basename extension
        $basename = basename($basename, isMLMDfile($basename));
        $prevLevel = $curLevel;/// keep track for numbering
        // explore all headings starting at curIndex
        while ($curIndex < count($headings)) {
            $object = $headings[$curIndex];
            // exit if level too low
            if ($object->level < $startLevel) {
                return true; // finished
            }
            // ignore if level too high
            if ($object->level > $endLevel) {
                $curIndex += 1;
                continue;// loop on next object
            }
            // go up to previous heading level?
            if ($object->level < $curLevel) {
                $prevLevel = $curLevel;
                $curLevel = $object->level;
                $curNumbering[$curLevel] += 1;
                continue; // loop on same object with new context
            }
            // go down one level?
            $nextLevel = $curLevel + 1;
            if ($object->level == $nextLevel) {
                $curLevel = $nextLevel;
                $curNumbering[$curLevel] = 0;
                $this->storeTOClines($startLevel, $endLevel, $curLevel, $curNumbering, $curIndex, $headings, $levelsNumbering, $levelsSeparator, $basename);
                // back to this level: loop on curIndex object, restore curLevel
                $curLevel = $prevLevel;
                continue;
            }
            // same level as current level? advance number if same as previous heading, else init
            if ($object->level == $curLevel) {
                if ($object->level == $prevLevel) {
                    $curNumbering[$curLevel] += 1;
                } else {
                    $curNumbering[$curLevel] = 1;
                }
                // output: prepare alpha/numeric prefix
                if ($this->outputHTML) {
                    $prefix = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',$curLevel-1);
                    foreach($levelsNumbering as $level => $numbering) {
                        if ($level <= $curLevel) {
                            $prefix .= chr(ord($numbering) + $curNumbering[$level] - 1);
                            if ($level==$curLevel) {
                                $prefix .= ')'; //: end prefix
                                break;
                            } else {
                                $prefix .= $levelsSeparator[$level] ?? '';
                            }
                        }
                    }
                } else {
                    $prefix .= chr(ord($levelsNumbering[$curLevel]) + $curNumbering[$curLevel] - 1) . '. ';
                }
                // output: prepare html anchor
                $anchor = '{file}';
                if ($curIndex !== false) {
                    $text = "{$object->text}";
                    $anchor .= "#h{$object->number}"; /// ex: {file}#h1
                }
                // output: HTML line break prefix for non-numeric prefixes, except for the absolute first line
                if ($this->outputHTML) {
                    if ((($curLevel!=1 || $curNumbering[$curLevel]!=1)  // not level 1, or not numeric
                        && ($curNumbering[$curLevel]) >= 1)) {          // AND number not nul
                            $prefix = "<br />\n{$prefix}";
                    }
                }
                // send parts to files, interpret directives and '{file}' meta      
                $this->storeContent($prefix . ' [',null,true); /// keep starting spaces
                $this->storeContent($text, $basename,false);
                $this->storeContent("]({$anchor})", $basename, false, true);// ending EOL

                // go next heading 
                $curIndex += 1;
                $prevLevel = $curLevel;
            } else {
                // level skipping 
                if ($object->level > $nextLevel) {
                    $this->error("inconsistent heading level (skip from {$curLevel} to {$object->level}) in file {$basename} line {$object->line}");
                    $prevLevel = $curLevel;
                    $curLevel = $nextLevel;
                    $curNumbering[$curLevel] = $levelsNumbering[$curLevel];
                    // loop same object new context
                } else {
                    //WTF
                    $this->error("unknown error in headings level file {$basename} line {$object->line}");
                }
            }
        } // while curIndex ok
        return true;
    }

    /** Find the first heading in the array for a level after a given line number.
     *  @param array $headings array for all headings in a file
     *  @param int $level the heading level to look for
     *  @param int $line the line number where to start search
     *  @return false if no heading found, else return the index of heading object
     */
    private function findHeadingIndex(&$headings, $level = 1, $line = 0) {
        foreach( $headings as $index => $object) {
            if ($object->line < $line) continue;
            if ($object->level == $level) return $index;
        }
        return false;
    }

    /** Generate a TOC line */
    private function generateToc($headings, $start, $end) {
        // generate a link to the file?
    }

    /** Write to an output file, protect against doubled line feeds */
    private function writeToFile($language) {
        // file = $this->outFiles[$language]
        // content = $this->curOutputs[$language]
        if (array_key_exists($language,$this->lastWritten)) {
            while (substr($this->curOutputs[$language],0,1)=="\n" && $this->lastWritten[$language]=="\n\n") {
                $this->curOutputs[$language] = substr($this->curOutputs[$language], 1);
            }
        }
        // normalize to unix eol
        $this->curOutputs[$language] = str_replace("\r\n", "\n", $this->curOutputs[$language]);
        //reduce triple EOL to double, and trim ending spaces & tabs
        $this->curOutputs[$language] = str_replace(["\n\n\n"," \n","\t\n"], ["\n\n", "\n","\n"], $this->curOutputs[$language]);
        $this->curOutputs[$language] = trim($this->curOutputs[$language], " \t\0\x0B");
        
        // send to file and clear the buffer
        if (!empty($this->curOutputs[$language])) {
            fwrite($this->outFiles[$language], $this->curOutputs[$language]);
            // retain previous last 2 character written
            $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . $this->curOutputs[$language],-2,2);
            $this->curOutputs[$language] = "";
        } 
    }

    /** Output a content to current output files. 
     *  Lines are buffered before being sent, and beginning : ending spaces are trimed.
     *  Variables are expanded for language ({file}, {main}, {language}) in each generated file
     *  @param string $content the content to send to outputs buffers, and to files if an end of line is found
     *  @param bool $flush true to force sending to files (used at end of file)
     *  @param string $filename the base filename to use for {file} replacement
    */
    private function outputToFiles($content, $flush=false, $basename=null) {

        switch ($this->curLanguage) {
            case 'all': // output to all files
                foreach ($this->outFiles as $language => $outFile) {
                    // replace filename in content?
                    //$finalContent = str_replace('{file}', $basename . ($language == $this->mainLanguage ? '.md' : ".$language.md"), $content);
                    //TODO: improve this, because it doesn't work if default is not declared first
                    if (!array_key_exists($language, $this->curOutputs)) $this->curOutputs[$language] = '';
                    if (empty($this->curOutputs[$language]) && !empty($this->curOutputs['default'])) {
                        $this->curOutputs[$language] = $this->curOutputs['default'] . $content;
                    } else {
                        $this->curOutputs[$language] .= $content;
                    }
                    // send to file if EOL or EOF
                    if ($flush || substr($this->curOutputs[$language],-1,1)=="\n") {
                        $basename = $this->getBasename($this->inFilename);
                        $this->curOutputs[$language] = $this->expandVariables($this->curOutputs[$language], $basename, $language);
                        $this->writeToFile($language);
                    }
                }
                $this->curOutputs['default'] = '';
                break;
            case 'ignore': // no output
                break;
            case '': // set 
                $this->curLanguage = 'all';
                break;
            default:
                // output to current language
                if (!array_key_exists($this->curLanguage, $this->curOutputs)) $this->curOutputs[$this->curLanguage] = '';
                // replace filename in content?
                ///$finalContent = str_replace('{file}', $basename . ($this->curLanguage == $this->mainLanguage ? '.md' : ".{$this->curLanguage}.md"), $content);
                if (empty($this->curOutputs[$this->curLanguage]) && !empty($this->curOutputs['default'])) {
                    $this->curOutputs[$this->curLanguage] = $this->curOutputs['default'] . $content;
                } else {
                    $this->curOutputs[$this->curLanguage] .= $content;
                }
                if ($flush || substr($this->curOutputs[$this->curLanguage],-1,1)=="\n") {
                    $basename = $this->getBasename($this->inFilename);
                    $this->curOutputs[$this->curLanguage] = $this->expandVariables($this->curOutputs[$this->curLanguage], $basename, $this->curLanguage);
                    $this->writeToFile($this->curLanguage);
                }
                break;
        }
    }
    
    /** Output into an array of outputs depending on a language.
     * @param string $c the content to output
     * @param array $outputs the array of outputs, will be updated
     * @param string $out the language code, or 'all', or 'default', or 'ignore', will be updated
     */
    private function outputToArray($content, &$outputs, &$out) {
        switch ($out) {
            case 'all':
                foreach($this->languages as $language => $bool) {
                    if (!array_key_exists($language, $outputs)) $outputs[$language] = '';
                    $outputs[$language] .= $content;
                }
                break;
            case 'ignore':
            break;
            case '': // after .))
                $out = 'all';
            break;
            default: // any language and 'default'
                if (!array_key_exists($out, $outputs)) $outputs[$out] = '';
                $outputs[$out] .= $content;
            break;
        }
    }

    /** Compute heading level frfom the starting '#'s */
    private function getHeadingLevel($content) {
        $heading = trim($content);
        $level = 0;
        $length = mb_strlen($heading,'UTF-8');
        while ($heading[$level]=='#' && $level <= $length) {
            $level += 1;
        }
        return $level;
    }

    /** Store content as a header. Interpret any directive in the content and write the result to corresponding files.
     *  Allowed directives: .all .ignore .default .<code>
     *  Also writes an anchor for the TOC links if there is a recorded heading and no anchor has been forced.
     *  The default anchor can be an HTML <A name=""> tag or an MD {: } shortcut, depending on the Generator output mode.
     *  This function is also called for the TOC title itself.
     *  
     *  @param int $level the heading level (number of '#')
     *  @param string $content the text line for the heading, starting after the '#' and ending right before the '\n'.
     *  @param string $anchor [optional] anchor to use, null to use the default anchor
     *  @return boolean false if any writing error occurs, true if header stored correctly in files
     */
    private function storeHeading($level, $content, $anchor=null) {
        // compute anchor name if needed
        if ($anchor == null) {
            $headings = $this->headings[$this->relFilenames[$this->inFilename]] ?? false;
            $index = $this->findHeadingIndex($headings, $level, $this->curLine);
            $headerObject = $headings[$index] ?? null;
            if ($headerObject) {
                //TODO: '{:' syntax not known in MD viewers despite https://developers.google.com/style/headings-targets
                //$anchor = $this->outputHTML ? "h{$headerObject->number}" : "{: #h{$headerObject->number}}";
                $anchor = "h{$headerObject->number}";
            }
        } else {
            //TODO: '{:' syntax not known in MD viewers despite  https://developers.google.com/style/headings-targets
            //if (!$this->outputHTML) {
            //   $anchor = "{: #{$anchor}}";
            //}
        }
        // write prefix 
        $prefix = str_repeat('#', $level);
        foreach($this->languages as $language => $bool) {
            // HTML/MD: '#### '
            if (fwrite($this->outFiles[$language], $prefix . ' ')===false) {
                $this->error("unable to write to {$this->outFilenames[$language]}");
                return false;
            }
            // write HTML anchor 
            ///TODO: always use html anchors
            //if ($this->outputHTML) {
                // HTML: '#### <a name="anchor"></a>'
                if (fwrite($this->outFiles[$language], "<a name=\"{$anchor}\"></a>")===false) {
                    $this->error("unable to write to {$this->outFilenames[$language]}");
                    return false;
                }
            ///}
        }
        // write heading content, interpret variables, no EOL
        // HTML: '#### <a name="anchor"></a>content'
        // MD  : '#### content'
        $heading = trim($content);
        $this->storeContent($heading,null,false);

        /*TODO: {: anchors not known in MD viewers
        // write MD anchors
        if (!$this->outputHTML) {
            // MD  : '#### content{: #anchor}'
            foreach($this->languages as $language => $bool) {
                if (fwrite($this->outFiles[$language], $anchor)===false) {
                    $this->error("unable to write to {$this->outFilenames[$language]}");
                    return false;
                }
            }
        }
        */
        // Finish heading with 2 x EOL
        $this->storeContent("\n\n",null,false,false,true);
        // Remember L1 heading so we stop ignoring empty EOLs
        if ($level == 1) $this->L1headingSet = true;
    }

    /** Store content. Interpret any directive in the content and write the result to corresponding files.
     *  Allowed directives: .all .ignore .default .<code>
     *  'all' is assumed at entry.
     *  Expand variables for each language.
     *  @param string $content the text to store, ending right before the '\n'.
     *  @param string $basename the base filename to use for {file} replacement in content
     *  @param bool $keepSTART true to keep the starting spaces (useful for TOC)
     *  @param bool $endCR [OPTIONAL] true to put a \n at the end [false]
     *  @param bool $keepCR [OPTIONAL] false to delete \n endings, true to keep them [false]
     *  @return boolean false if any writing error occurs, true if header stored correctly in files
     */
    private function storeContent($content, $basename, $keepSTART, $endCR = false, $keepCR = false) {
        $pos = 0;
        $inDirective = false;
        $curword = '';
        $length = mb_strlen($content,'UTF-8');
        $curOutput = 'all';
        $outputStack = [];
        $outputs = [];
        $trim = $keepSTART ? "rtrim" : "trim";
        while ($pos < $length) {
            $c = mb_substr($content,$pos,1);
            if ($c=='.') {
                // previous content to be stored?
                if (!empty($curword)) {
                    $this->outputToArray($curword, $outputs, $curOutput);
                    $curword = '';
                }
                $inDirective = true;
            }
            if ($inDirective) {
                $tryWord = mb_strtolower($curword . $c,'UTF-8');
                if (array_key_exists($tryWord, $this->directives)) {
                    $newOutput = mb_substr($tryWord,1,-2); // 'all', 'default', 'ignore', '<code>', ''
                    $inDirective = false;
                    $curword = '';
                    if ($tryWord=='.))') {
                        $curOutput = array_pop($outputStack);
                    } else {
                        array_push($outputStack, $curOutput);
                        $curOutput = empty($newOutput) ? 'default' : $newOutput;
                    }
                } else {
                    $curword .= $c;
                }
            } else {
                // send to outputs
                $this->outputToArray($c, $outputs, $curOutput);
            }
            $pos += 1;
        }
        if (!empty($curword)) {
            $this->outputToArray($curword, $outputs, $curOutput);
            $curword = '';
        }
    // retain only non-empty outputs
        $final = [];
        foreach($outputs as $output => $content) {
            if (!empty($trim($content, " \t\0\x0B"))) {
                $final[$output] = $trim($content, " \t\0\x0B");
                if (!$keepCR) {
                    $final[$output] = rtrim($final[$output],"\n");
                }
            }
        }
        // expand variables and output content to language files
        foreach ($this->languages as $language => $bool) {
            $text = array_key_exists($language, $final) ? $final[$language] : ($final['default'] ?? '');
            /*$text = str_replace('{file}', $basename . ($language == $this->mainLanguage ? '.md' : ".{$language}.md"), $text);
            if ($this->mainFilename !== false) {
                $text = str_replace('{main}', $this->mainFilename . ($language == $this->mainLanguage ? '.md' : ".{$language}.md"), $text);
            }
            $text = str_replace('{language}', $language, $text);*/
            $text = $this->expandVariables($text, $basename, $language);
            $text .= $endCR ? "\n" : '';
            if (fwrite($this->outFiles[$language], $text)===false) return false;
            $this->lastWritten[$language] = mb_substr(($this->lastWritten[$language] ?? "") . $text,-2,2);
        }
        return true;
    }
    
    /** Add a language to the languages set */
    private function addLanguage($code) {
        // main=<code> ?
        if (mb_stripos($code, 'main=', 0, 'UTF-8')!==false) {
            $this->mainLanguage = mb_strtolower(mb_substr($code,5,NULL,'UTF-8'));
        } else {
            if (array_key_exists($code, $this->languages)) return true;
            $this->languages[$code] = true;
        }
    }

    /** Find all headings and sub headings in a set of files.
     *  The files which are not under the given root directory will be ignored.
     *  Files with no headings will receive a heading using their filename
     *  @param array $filenames the pathes of the files to explore for headings
      */
    public function exploreHeadings($filenames) {

        $this->headings = [];
        $number = 1;
        foreach($filenames as $filename) {
            // get relative filename, ignore if not the right root
            $rootLen = mb_strlen($this->rootDir,'UTF-8');
            $baseDir = mb_substr($filename, 0, $rootLen, 'UTF-8');
            if ($baseDir != $this->rootDir) {
                $this->error("wrong root dir for file [$filename], should be [$this->rootDir]");
                continue;
            }
            $relFilename = mb_substr($filename, $rootLen+1, NULL, 'UTF-8');
            $this->relFilenames[$filename] = $relFilename;
            $inFile = fopen($filename,'rb');
            if ($inFile===false) {
                $this->error("could not open [$filename]");
                continue;
            }
            $this->headings[$relFilename] = [];
            $index = 0;
            $curLine = 1;
            do {
                $text = trim(fgets($inFile));
                if (($text[0]??'') == '#') {
                    // prepare an object
                    $object = new Heading();
                    // sequential number for all headers of all files
                    $object->number = $number;
                    $number += 1;
                    // count number of '#' = heading level
                    $object->level = $this->getHeadingLevel($text);
                    // line number in this file
                    $object->line = $curLine;
                    // trimmed text without # prefix
                    $object->text = trim(mb_substr($text, $object->level, NULL,'UTF-8'));
                    // store the object in array for this file
                    $this->headings[$this->relFilenames[$filename]][$index] = $object;
                    $index += 1;
                }
                $curLine += 1;
            } while (!feof($inFile));
            $this->closeInput();
            // force a level 1 object if no headings
            if (count($this->headings[$relFilename])==0) {
                $object = new stdClass();
                $object->number = $number;
                $number += 1;
                $object->level = 1;
                $object->line = 1;
                $object->text = $relFilename;
                $this->headings[$relFilename][] = $object;
            }
        } // next file
    }

    /** Store a character depending on mode. */
    private function storeCharacter($c) {
        $this->curWord .= $c;
    }

    // Buffer for current word starting with a dot
    private $curWord = '';
    // Flag to know if we're in a word starting with a dot
    private $inDirective = false;

    /** Start a new possible directive with a dot. */
    private function startDirectiveWith($c) {
        $this->inDirective = true;
        $this->curWord = $c;
    }

    /** reset parsing status to neutral */
    private function resetParsing() {
        $this->inDirective = false;
        $this->curWord = '';
    }

    /** Read a character from input file, buffered in line.
     * @return bool|string next character, "\n" when end of line is reached, or false when file and buffer are finished.
     */
    private function getChar($file) {
        // any  character left on current line?
        if ($this->lineBufPos < $this->lineBufLength - 1) {
            $this->lineBufPos += 1;
        } else {
            do {
                $this->lineBuf  = fgets($file);
                if (!$this->lineBuf) {
                    $this->curChar = false;
                    return false; // finished
                }
                $this->lineBufPos = 0;
                $this->lineBufLength = mb_strlen($this->lineBuf,'UTF-8');
            } while ($this->lineBufPos >= $this->lineBufLength);
        }
        $this->prevChar = $this->curChar;
        $this->curChar = mb_substr($this->lineBuf, $this->lineBufPos, 1, 'UTF-8');
        $this->debugEcho();
        return $this->curChar;
    }

    /** Parse a heading starting with at least one '#' */
    private function parseHeading($file) {
        $headingContent = $this->curChar; // '#'
        do {
            $c = $this->getChar($file);
            /// end of line or end of file?
            if ($c===false || $c=="\n") {
                $level = $this->getHeadingLevel($headingContent);
                $this->storeHeading($level, trim(mb_substr($headingContent, $level,null,'UTF-8')));
                $this->curLine += 1;
                break;
            }
            // heding line not finished: store and go on
            $headingContent .= $c;
        } while ($c);
        return true;
    }

    /** Close input file. */
    private function closeInput() {
        if ($this->inFile != null) {
            fclose($this->inFile);
            $this->inFile = null;
        }
        return false;
    }

    /** Close output files. */
    private function closeOutput() {
        foreach($this->outFiles as &$outFile) {
            if ($outFile != null) {
                fclose($outFile);
            }
        }
        unset($this->outFiles);
        $this->outFiles = [];
    }

    /** Open the input streaming file and prepare the output filename template. */
    public function setInputFile($filename) {
        // close any previous file
        if ($this->inFile != null) {
            $this->closeInput();
        }
        // open or exit
        $this->inFile = fopen($filename, "rb");
        if ($this->inFile===false) {
            return $this->closeInput();
        }
        // prepare output file template
        $extension = isMLMDfile($filename);
        if ($extension===false) {
            return $this->closeInput();
        }
 
        // retain base name as template and reset line number
        $this->outFilenameTemplate = mb_substr($filename, 0, -mb_strlen($extension,'UTF-8'),'UTF-8');
        $this->inFilename = $filename;
        $this->curLine = 1;
        $this->curLanguage = 'ignore';

        $this->closeOutput();
        return true;
    }
    
    /** Parse input and create files. */
    public function Parse($filename) {
        if (!$this->setInputFile($filename)) {
            return false;
        }
        if (feof($this->inFile)) {
            return false;
        }

        $c = '';
        if (getenv("debug")=="1") {
            echo "\n[1]:";//: first line number
        }
        // ignore until .languages is done and first '#' heading has been processed
        $this->languagesSet = false;
        $this->L1headingSet = false;
        do {
            $this->getChar($this->inFile);
            
            switch ($this->curChar) { 
            case false:
                break;
            case '.':
                // if already in directive detection, flush
                if ($this->prevChar=="\n" || $this->inDirective) {
                    // flush previous content and stop directive detection
                    $this->outputToFiles($this->curWord);
                    $this->resetParsing();
                }
                // start directive detection?
                if (empty($this->curWord) || !$this->inDirective) {
                    $this->startDirectiveWith($this->curChar);
                    break;
                }
                // currently in a directive?
                if ($this->inDirective) {
                    // possible end of directive, try to interpret current word
                    $tryWord = mb_strtolower($this->curWord,'UTF-8');
                    if (array_key_exists($tryWord, $this->directives)) {
                        // start effect and restart character capture
                        $functionName = $this->directives[$tryWord];
                        $this->$functionName($tryWord);
                        $this->resetParsing();
                    } else {
                        // no: keep storing (in current word)
                        $this->storeCharacter($this->curChar);
                        break;
                    }
                    // start a possible new directive with this '.'
                    $this->startDirectiveWith($this->curChar);
                } else {
                    $this->storeCharacter($this->curChar);
                }
                break;
            case "\n":
                $this->curLine += 1;
                if ($this->languagesSet && $this->L1headingSet) {
                    $this->outputToFiles($this->curWord . $this->curChar);
                }
                $this->resetParsing();
                break;
            case '#':
                if (!$this->languagesSet) break;
                // start or continue a heading
                if ($this->prevChar=="\n") {
                    $this->parseHeading($this->inFile);
                    break;
                } 
                // not a heading: fall through to default processing
            default:
                // not '.', '\n' or heading starting '#'
                if ($this->inDirective) {
                    // try to identify a directive with current store and this character
                    $tryWord = mb_strtolower($this->curWord . $this->curChar,'UTF-8');
                    if (array_key_exists($tryWord, $this->directives)) {
                        // apply directive effect and reset current store
                        $functionName = $this->directives[$tryWord];
                        $this->$functionName($tryWord);
                        break;
                    }
                    // not a directive, add to current word store
                    $this->storeCharacter($this->curChar);
                } else {
                    if (!$this->languagesSet) break;
                    $this->outputToFiles($this->curChar);
                }
                break;
            } // switch $this->curChar
        } while ($this->curChar !== false);
        // flush anything left in all output buffers
        $this->curLanguage = 'all';
        $this->outputToFiles($this->curWord, true);
        // MD047: force single \n file ending if needed
        foreach($this->languages as $language => $bool) {
            if (substr($this->lastWritten[$language],-1,1) != "\n") {
                fwrite($this->outFiles[$language], "\n");
            }
            fclose($this->outFiles[$language]);
            $this->outFiles[$language] = null;
        }
    }
}

//MARK: CLI launch

// Create the generator instance
$generator = new MultilingualMarkdownGenerator();
$generator->setRootDir(getcwd());

// Arguments parsing
$inFilenames = [];
$arg = 1;
$params = ["-main=" => 'mainFilename', "-out=" => 'outputHTML'];
foreach ($params as $param => $field) {
    $$field=false;
}
while ($arg < $argc) {
    if (strcasecmp($argv[$arg],"-i")==0) {
        if ($arg+1 < $argc) {
            $arg += 1;
            if (!file_exists($argv[$arg])) {
                echo "WARNING: file doesn't exist {$argv[$arg]}\n";
            } else {
                $inFilenames[] = $argv[$arg];
            }
        }
    } else {
        foreach( $params as $param => $field) {
            $pos = mb_stripos($argv[$arg],$param,null,'UTF-8');
            if ($pos === false) continue;
            $$field = mb_substr($argv[$arg], $pos+mb_strlen($param,'UTF-8'));
        }
    }
    $arg += 1;
}

// check/set values
$generator->setOutputHTML($outputHTML!='md');
if ($mainFilename !== false) {
    if (!file_exists($mainFilename)) {
        $generator->error("WARNING: file doesn't exist {$generator->mainFilename}\n");
    } else {
        $generator->setMainFilename($mainFilename);
    }
}



// no file: explore
if (count($inFilenames)==0) {
    $inFilenames = exploreDirectory(getcwd());

}
// Build the headings list for TOC
$generator->exploreHeadings($inFilenames);

// Parse input files and generate all output files
foreach($inFilenames as $filename) {
    $generator->Parse($filename);
}

?>