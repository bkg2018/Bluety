<?php

/**
 * Multilingual Markdown generator
 * parameters:
 *  -i <filepath.mlmd|filepath.base.md> [...]
 *      generate each <filepath.xx.md> for languages 'xx' declared
 *      in '.languages' directive.
 *  -out=html|md
 *      choose HTML or MD for links and anchors for Table Of Contents
 *  -main=<_mainFilename[.mlmd|.base.md]>
 *      choose the main file (generaly the one with global TOC)
 *
 * If no parameter is given, explore current and sub directories
 * for '*.base.md' and '.mlmd' files and generate files for each file found.
 * By default, main file will be README.mlmd or README.base.md
 * if such a file is found in current directory.
 *
 * Template files must be named with .base.md or .mlmd
 *
 * Directives control the languages specifics files generation:
 *  - .languages declares languages
 *  - .toc generates a table of contents
 *  - .numbering sets the heading numbering schemes (also available
 *     as script argument and toc parameter)
 *  - .all(( starts a section for all languages
 *  - .ignore(( starts an ignored section
 *  - .default(( starts a section for default text
 *  - .<language>(( starts a section for a specific language
 *  - .)) ends a section
 *
 * The following variables are expanded in the generated files:
 *
 * {file} expands to the current file name, localised for the language
 * {main} expands to the '-main' file name, localised for the language
 * {language} expands to the language code as declared in the '.languages' directive.
 *
 * @category  TODO
 * @package   TODO
 * @author    Francis Piérot <fpierot@free.fr>
 * @copyright 2020 Francis Piérot
 * @license   www.php.net/license/3_01.txt PHP License 3.01
 * @link      TODO
 */

namespace MultilingualMarkdown {

    require 'heading.php';

    //MARK: Global Utility functions

    /**
     * Check if a filename has an MLMD valid extension.
     *
     * @param string $filename the file name or path to test.
     *
     * @return string the file extension (.base.md or .mlmd), false if invalid
     *                mlmd file name.
     */
    function isMLMDfile($filename)
    {
        $extension = ".base.md";
        $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
        if ($pos === false) {
            $extension = ".mlmd";
            $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
            if ($pos === false) {
                return false;
            }
        }
        return $extension;
    }

    /**
     * Recursively explore a directory and its subdirectories and return an array
     * of each '.base.md' and '.mlmd' file found.
     *
     * @param string $dirName the directory to test, either relative to current
     *                        directory or absolute path.
     *
     * @return string[] pathes of each file found, relative to $dirName.
     */
    function exploreDirectory($dirName)
    {
        $dir = opendir($dirName);
        $filenames = [];
        if ($dir !== false) {
            while (($file = readdir($dir)) !== false) {
                if (($file == '.') || ($file == '..')) {
                    continue;
                }
                $thisFile = $dirName . '/' . $file;
                if (is_dir($thisFile)) {
                    $filenames = array_merge($filenames, exploreDirectory($thisFile));
                } elseif (isMLMDfile($thisFile)) {
                    $filenames[] = $thisFile;
                }
            }
            closedir($dir);
        }
        return $filenames;
    }



    //MARK: Generator class

    /**
     * Generator class.
     */
    class Generator
    {
        // Directives and handling function names
        private $_directives = [
            '.all(('        => '_all',           /// push current section and open all languages section
            '.ignore(('     => '_ignore',        /// push current section and open ignored section
            '.default(('    => '_default',       /// push current section and open default text section
            '.(('           => '_default',       /// same as .default((
            '.))'           => '_end',           /// close current section and pop previous section
            '.languages'    => '_languages',     /// declare languages and start files generation
            '.numbering'    => '_numbering',     /// sets the numbering schemes for headings levels
            '.toc'          => '_toc'            /// insert a table of contents
        ];

        // Current input filename, file and reading status
        private $_inFilename = null;             /// 'example.base.md'
        private $_inFile = null;                 /// input file handle
        private $_lineBuf = "";                  /// current line content
        private $_lineBufPos = 0;                /// current pos in line buffer (utf-8)
        private $_lineBufLength = 0;             /// current line size in characters (utf-8)
        private $_prevChar = '';                 /// used to detect line and heading start
        private $_curChar = '';                  /// current char
        private $_curLine = 1;                   /// current line number in current input file

        // Current output filenames, files and writing status
        private $_outFilenameTemplate = null;    /// as 'example'
        private $_outFilenames = [];             /// '<language>' => 'example.md' / 'example.<language>.md'
        private $_outFiles = [];                 /// '<language>' => file-handle
        private $_lastWritten = [];              /// last  character written to file
        private $_curOutputs = [];               /// current output buffers for files
        private $_outputHTML = true;             /// true=html or false=md, format for headings anchors and toc links
        private $_mainFilename = false;          /// -main parameter

        // Directives status
        private $_languages = [];                // declared languages
        private $_mainLanguage =false;           // optional main language (<code> deleted from MD output filename)
        private $_curLanguage = 'ignore';        // current opened directive (all / <code> / ignore / default)
        private $_directiveStack = [];           // stack of previous directives
        private $_languagesSet = false;          // ignore any input until '.languages' directive has been processed
        private $_L1headingSet = false;          // ignore EOL until level 1 heading has been processed
        private $_levelsNumbering = [];          // numbering for each level scheme: 'A', 'a', '1'
        private $_levelsSeparator = [];          // separator character for each level scheme: '.', '-' etc

        // All files headings for toc
        private $_headings = [];                 // all headers objects for each relative filename
        private $_relFilenames = [];             // relative filenames for each filename

        /** 
         * Set the output mode. Called by script arguments evaluation.
         * 
         * @param string $mode 'html' to set the HTML mode (<A> links and anchors), 
         *                     'md for MD mode ([]() links and {:# } anchors)
         * 
         * @return nothing
         */
        public function setOutputHTML($mode) 
        {
            $this->_outputHTML = ($mode != 'md');
        }

        /** 
         * Set the main file name. Called by script arguments evaluation.
         * 
         * @param string $name the name of the main template file. 
         *                     Default is 'README.mlmd' in the root directory.
         * 
         * @return nothing
         */
        public function setMainFilename($name = 'README.mlmd') 
        {
            $basename = $this->getBasename($name);
            if ($basename !== false) {
                $this->_mainFilename = $basename;
            }
        }

        /** 
         * Set the numbering schemes.
         * 
         * @param string $numbering a string containing numbering scheme
         * 
         * @return nothing
         */
        public function setNumbering($numbering) 
        {
            $defs = explode(',', $numbering);
            $this->_levelsNumbering = [];
            $this->_levelsSeparator = [];
            foreach ($defs as $def) {
                $parts = explode(':', $def);
                $level = $parts[0];
                if ($level < '1' || $level > '9') {
                    $this->_error("bad argument in -numbering '$def': level before ':' must be between '1' and '9'");
                } else {
                    $this->_levelsNumbering[$level]='';
                    $this->_levelsSeparator[$level]='';
                    if (count($parts) > 1) {
                        $num = mb_substr($parts[1], 0, 1, 'UTF-8');
                        if (($num < '1' || $num > '9') && ($num <'a' || $num >'z') && ($num <'A' || $num > 'Z')) {
                            $this->_error("bad argument in -numbering '$def': numbering after ':' must be between '1' and '9', 'a' and 'z', or 'A' and 'Z'");
                        } else {
                            $this->_levelsNumbering[$level] = $num;
                            $this->_levelsSeparator[$level]=mb_substr($parts[1], 1, 1, 'UTF-8');
                        }
                    } else {
                        $this->_levelsNumbering[$level]='';
                        $this->_levelsSeparator[$level]='';
                    }
                }
            }
            ksort($this->_levelsNumbering, SORT_NUMERIC);
            ksort($this->_levelsSeparator, SORT_NUMERIC);
            $this->_resetParsing();

            /// compute prefixes for all headings
            if (empty($this->_headings)) {
                return ;
            }
            foreach ($this->_headings as &$headings) {
                $curNumbering = []; // init all numbers for all levels
                $curLevel = 0; // start above first level
                foreach ($headings as &$heading) {
                    if ($heading->level > $curLevel) {                
                        $curNumbering[$heading->level] = 1; // initialize level below
                    } else {
                        $curNumbering[$heading->level] += 1; // next number in same or above level
                    }
                    $curLevel = $heading->level;                        
                    $prefix = str_repeat(str_repeat('&nbsp;', 4), $curLevel-1);
                    if (isset($this->_levelsNumbering[$curLevel])) {
                        foreach ($this->_levelsNumbering as $level => $numbering) {
                            if (!isset($curNumbering[$level])) {
                                $curNumbering[$level] = 1;
                            }
                            if ($level <= $curLevel) {
                                $prefix .= chr(ord($numbering) + $curNumbering[$level] - 1);
                                if ($level==$curLevel) {
                                    $prefix .= ')'; //: end prefix
                                    break;
                                } else {
                                    $prefix .= $this->_levelsSeparator[$level] ?? '';
                                }
                            }
                        }
                    } else {
                        $prefix .= '- ';
                    }
                }
            }

        }

        /**
         * Set root directory for relative filenames.
         * 
         * @param string $rootDir the root directory, preferably an absolute path
         * 
         * @return nothing
         */
        public function setRootDir($rootDir) 
        {
            $this->rootDir = $rootDir;
        }

        /** 
         * Get relative basename (no extension) from a filepath. 
         * 
         * @param string $path the path to the file.
         * 
         * @return string|bool the base name, without extension and using a path relative
         *                     to rootDir, false if the path is not under rootDir
         */
        public function getBasename($path) 
        {
            //  build relative path against root dir
            $rootLen = mb_strlen($this->rootDir, 'UTF-8');
            $baseDir = mb_substr(realpath($path), 0, $rootLen, 'UTF-8');
            $cmpFunction = (stripos(getenv('SYSTEMROOT'), 'windows') !== false) ? "strcasecmp" : "strcmp";
            if ($cmpFunction($baseDir, $this->rootDir) != 0) {
                $this->_error("wrong root dir for file [$path], should be [{$this->rootDir}]");
                return false;
            }
            $extension = isMLMDfile(basename($path));
            $path = mb_substr(realpath($path), $rootLen + 1, null, 'UTF-8');
            return mb_substr($path, 0, -mb_strlen($extension, 'UTF-8'), 'UTF-8');
        }

        /** 
         * Expand variables in a text for a language.
         * - {file} replaced by relative path of generated file ('example.en.md' for 'example.mlmd')
         * - {main} replaced by relative path of main file as declared by '-main='
         * - {language} is replaced by the current language
         * Notice: {main} won't be replaced if no '-main=' parameter was given to the script.
         * 
         * @param string $text     the original text
         * @param string $basename the base name for current input file ('example' for 'example.mlmd')
         * @param string $language the current language for generation (like 'en')
         * 
         * @return string the text with expanded variables
         */
        public function expandVariables($text, $basename, $language) 
        {
            $text = str_replace('{file}', $basename . ($language == $this->_mainLanguage ? '.md' : ".{$language}.md"), $text);
            if ($this->_mainFilename !== false) {
                $text = str_replace('{main}', $this->_mainFilename . ($language == $this->_mainLanguage ? '.md' : ".{$language}.md"), $text);
            }
            $text = str_replace('{language}', $language, $text);
            return $text;
        }

        /** 
         * Parser Tool: read characters from current file until a given character is found. 
         * 
         * @param string $marker the ending character
         * 
         * @return string the characters read until the marker. The marker itself is not returned 
         *                but is available as $this->_curChar. Return empty string if already on marker.
         */
        private function _getCharUntil($marker) 
        {
            $content = '';
            $this->_getChar($this->_inFile);/// get first title character
            while (($this->_curChar !== false) && ($this->_curChar !== $marker) && ($this->_curChar !== "\n")) {
                $content .= $this->_curChar ?? '';
                $this->_getChar($this->_inFile); // next char
                if ($this->_curChar=="\n") {
                    $this->_curLine += 1;
                }
            }
            return $content;
        }

        /** 
         * Debugging echo. 
         * 
         * @return nothing
         */
        private function _debugEcho() 
        {
            if (getenv("debug")=="1") {
                if (($this->_curChar !== false) && ($this->_prevChar=="\n")) {
                    echo "[{$this->_curLine}]:";
                }
                echo $this->_curChar;
            }
        }

        /**
         * Send an error message to output and php log.
         * 
         * @param string $msg the text to display and log.
         * 
         * @return nothing
         */
        private function _error($msg)
        {
            //echo "ERROR: $msg\n";
            if ($this->_inFilename) {
                error_log("{$this->_inFilename}({$this->_curLine}): MLMD error: $msg");
            } else {
                error_log("arguments: MLMD error: $msg");
            }
        }

        //MARK: Directives

        /** 
         * Directive .all(( handling. Start to send to all languages.
         * 
         * @param any $dummy fake parameter.
         * 
         * @return nothing
         */
        private function _all($dummy) 
        {
            array_push($this->_directiveStack, $this->_curLanguage);
            $this->_curLanguage = 'all';
            $this->_resetParsing();
        }
        /** 
         * Directive .ignore(( handling. Start to ignore text.
         * 
         * @param any $dummy fake parameter.
         * 
         * @return nothing
         */
        private function _ignore($dummy) 
        {
            array_push($this->_directiveStack, $this->_curLanguage);
            $this->_curLanguage = 'ignore';
            $this->_resetParsing();
        }
        /** 
         * Directive .(( or .default(( - start default text. 
         * 
         * @param any $dummy fake parameter.
         * 
         * @return nothing
         */
        private function _default($dummy) 
        {
            array_push($this->_directiveStack, $this->_curLanguage);
            $this->_curLanguage = 'default';
            $this->_resetParsing();
        }
        /** 
         * Directive .)) . Return to previous directive. 
         * 
         * @param any $dummy fake parameter.
         * 
         * @return nothing
         */
        private function _end($dummy) 
        {
            if (count($this->_directiveStack) > 0) {
                $this->_curLanguage = array_pop($this->_directiveStack);
            } else {
                $this->_curLanguage = 'all';
            }
            $this->_resetParsing();
        }
        /**
         * Directive .<language>(( - start to send to one declared language.
         * 
         * @param string $directive the code for language directive.
         * 
         * @return nothing
         */
        private function _change($directive) 
        {
            $code = mb_strtolower(mb_substr($directive, 1, -2, 'UTF-8'), 'UTF-8'); // '.en(('  -> 'en'
            if (array_key_exists($code, $this->_languages)) {
                array_push($this->_directiveStack, $this->_curLanguage);
                $this->_curLanguage = $code;
                $this->_resetParsing();
            }
        }

        /** 
         * Directive .languages - declare authorized languages. 
         * 
         * @param any $dummy fake parameter.
         * 
         * @return nothing
         */
        private function _languages($dummy) 
        {
            $curWord = '';
            $stopNow = false;
            do {
                $this->_getChar($this->_inFile);
                if ($this->_curChar!==false) {
                    switch ($this->_curChar) {
                    case "\r":
                        // do not store CR (normalize to unix EOL)
                        break;
                    case "\n":
                        // line feed: stop current directive 
                        $stopNow = true; // finish with current word (then, fall-through)
                    case ' ':
                    case ',':
                        // if we have a word, set it as additionnal language
                        if (!empty($curWord)) {
                            $this->_addLanguage($curWord);
                            $curWord = '';
                        }
                        break;

                    // store current keyword
                    default:
                        $curWord .= $this->_curChar;
                        break;
                    }
                }
            } while (($this->_curChar !== false) && !$stopNow);

            // finish languages registration, open files, set initial status
            if ($this->_outFilenameTemplate==null) {
                return false;
            }
            foreach ($this->_languages as $language => $bool) {
                if ($this->_mainLanguage == $language) {
                    $this->_outFilenames[$language] = "{$this->_outFilenameTemplate}.md";
                } else {
                    $this->_outFilenames[$language] = "{$this->_outFilenameTemplate}.{$language}.md";
                }
                // open the output file
                $this->_outFiles[$language] = fopen($this->_outFilenames[$language], 'wb');
                // and record 'change' action for '.<code>((' directive
                $this->_directives[".{$language}(("] = '_change';
            }
            // set initial status
            $this->_resetParsing();
            $this->_prevChar = "\n";
            $this->_curLine += 1; 
            $this->_curLanguage = 'all';
            $this->_languagesSet = true;
            array_push($this->_directiveStack, $this->_curLanguage);
        }

        /** 
         * .numbering directive handling. Sets the numbering schemes for headings and TOC. 
         * .numbering m:<symbol><sep>,...  
         * 
         * - `m` is the heading level
         * - <symbol> is a number (e.g: `1`) or a letter (e.g: `a`) for this level, 
         *            case (`a` or `A`) is preserved and numbering starts with the given value
         * - `sep` is the symbol to use after this level numbering, e.g `.` or `-`
         * 
         * @param any $dummy fake parameter
         * 
         * @return nothing
         */
        private function _numbering($dummy) 
        {
            // skip initial space
            $this->_curWord = trim($this->_getChar($this->_inFile)); 
            // get definition until next space / EOL
            $defs = $this->_getCharUntil(' ');
            $this->setNumbering($defs);
        }

        /** 
         * Generate a TOC in current output files using the directive parameters and the headings array.
         * 
         * The generator has an _outputHTML field to format links and anchors in two possibles modes.
         * 
         * HTML mode (_outputHTML=true):
         * Headings all have an anchor prior to the title, with a heading number:
         *      <a name="h12"></a>
         *      ### Heading text
         * The TOC must link to these anchors using the following model:
         *      [1.2 Heading text](<file>#h12)
         * 
         * MD mode (_outputHTML=false):
         * Headings have an automatic MD anchor with their cleaned text, and they can define one using {: }:
         *      ### Heading text {: #h12-heading-text}
         * The TOC must link to these headings using the cleaned text:
         *      [1.2 Heading text](<file>#heading-text)
         * WARNING: MD method requires that all headings from a given file are unique in their file. 
         * If this is not true then HTML mode is more appropriate and will always work
         * 
         * Numbering is optional and choosen by the directive parameters:
         * 
         * .TOC [level=m-n] [title=m,"<title text>"] [numbering=m:<symbol><sep>[,...]] [format=html|md]
         * 
         * level=m-n                   : use headings from level m to n. m defaults to 1, n defaults to 9
         * 
         * title=m,"<title text>"      : text for the TOC title with heading level m, language directives can be used
         * 
         * numbering=m:<symbol><sep>,...  : prefix TOC titles with numbering or labelling. Syntax for m:<symbol><sep>
         *                                  - `m` is the heading level
         *                                  - <symbol> is a number (e.g: `1`) or a letter (e.g: `a`) for this level, 
         *                                    case (`a` or `A`) is preserved and numbering starts with the given value
         *                                  - `sep` is the symbol to use after this level numbering, e.g `.` or `-`
         * 
         * Example to number level 1 with uppercase letters, followed by a dash '-', level 2 with a number 
         * starting at 1 and followed by a dot, and level 3 as level 2, and with a title heading level 
         * of 2 (prefixed with '##'):
         * 
         * .TOC level=1-3 title=2,.fr((Table des matières.)).en((Table Of Contents)) numbering=1:A-,2:1.,3:1
         * 
         * @param any $dummy fake parameter
         * 
         * @return nothing
         */
        private function _toc($dummy) 
        {
            // default parameters
            $title = "Table Of Contents";               // <text>    in title=m,"<text>"
            $titleLevel = 2;                            // m         in title=m,"<text>"
            $startLevel = 2;                            // m         in level=m[-n]
            $endLevel = 4;                              // n         in level=[m]-n
            $outmode = 'html';                          // format=html by default
            // skip initial space
            $this->_curWord = trim($this->_getChar($this->_inFile)); 
            do {
                $this->_getChar($this->_inFile);

                // add to current word and check keywords
                $this->_curWord .= $this->_curChar;
                switch (strtolower($this->_curWord)) {
                case 'title=': // title=m,"<text>"
                    // get level
                    $titleLevel = $this->_getCharUntil(',');
                    // parse and set toc title
                    $title = $this->_getCharUntil('"');// skip to " or \n
                    if ($this->_curChar != '"') {
                        $this->_error("no '\"' around title text, got this: [$title]");
                    } else {
                        $title = $this->_getCharUntil('"');
                    }
                    $this->_curWord = '';
                    break;
                case 'out=': // out=html|md
                    // get level
                    $outmode = $this->_getCharUntil(' ');
                    if (strcasecmp($outmode, 'md')!=0) {
                        $outmode = 'html';
                    }
                    break;
                case 'level=':
                    // get definition until next space
                    $def = $this->_getCharUntil(' ');
                    $dashpos=mb_stripos($def, '-', 0, 'UTF-8');
                    if ($dashpos === false) {
                        // only one level
                        $startLevel = $endLevel = (int)$def;
                    } else {
                        $startLevel = (int)mb_substr($def, 0, $dashpos, 'UTF-8');
                        $endLevel   = (int)mb_substr($def, $dashpos+1, null, 'UTF-8');
                        if (empty($startLevel)) {
                            $startLevel = 1;
                        }
                        if (empty($endLevel)) {
                            $endLevel = 9;
                        }
                    }
                    $this->_curWord = '';
                    break;
                case 'numbering=':
                    $this->numbering(null);
                    break;
                case ' ':/// separator
                    $this->_curWord = '';
                default:
                    break;
                }
            } while ($this->_curChar !== false && $this->_curChar != "\n");

            // generate TOC title with forced 'toc' anchor
            $this->_storeHeading($titleLevel, $title, 'toc');
            // generate toc lines for each file, only if start level is 1
            if ($startLevel == 1) {
                $numbering = [$startLevel => 0];
                foreach ($this->_headings as $basename => $headings) {
                    // store first level 1 heading for the file
                    $index = $this->_findHeadingIndex($headings, $startLevel, 0);
                    if ($index !== false) {
                        $line = $headings[$index]->line;// remember level 1 line
                        $this->_storeTOClines($startLevel, $startLevel, $startLevel, $numbering, $index, $headings, $basename);
                        // and store the other levels if any
                        $index = $this->_findHeadingIndex($headings, $startLevel + 1, $line + 1);
                        if (($endLevel > 1) && ($index !== false)) {
                            $numbering[$startLevel + 1] = 0;
                            $result = $this->_storeTOClines($startLevel, $endLevel, $startLevel + 1, $numbering, $index, $headings, $basename);
                        }
                    }
                }
                /// end toc
                $this->_storeContent("\n\n", null, false, false, true);// keep EOLs
            } else {
                $numbering = [ $startLevel => 0];
                $headings = $this->_headings[$this->_inFilename];
                $index = $this->_findHeadingIndex($headings, $startLevel, 0);
                if ($index !== false) {
                    $this->_storeTOClines($startLevel, $endLevel, $startLevel, $numbering, $index, $headings, $basename);
                } else {
                    $this->_error("starting level not found for TOC in file {$this->_inFilename}");
                }
            }

        }

        /** 
         * Store toc lines.
         * 
         * The lines to print are defined with the following prameters:
         * 
         * - startLevel -> 2, 3, 4: first heading level, exit when level below this
         * - endLevel: last heading level, ignore headings with levels above this
         * - numbering -> A, a, 1: gives the current numbering for each level
         * - index -> 0, 1, 2... : gives the index of first heading to look
         * 
         * And the following arrays and data are also given for linking and numbering headings:
         * 
         * - headings: array of objects, which each object describing a heading from current file 
         * - levels numbering and separators: array of symbols to define the prefix of headings in the toc
         * - basename: base filename where to link, replaces '{file}' in content
         * 
         * The heading objects have the following fields:
         * 
         * - number: a number unique to all headings of all files, used as destination for link
         * - line: the line number in the file
         * - level: the heading level (number of '#'s)
         * - text: the heading text (can include MLMD directives, doesn't include '#' prefix nor numbering)
         *
         * @param int       $startLevel   the starting heading level to put in TOC
         * @param int       $endLevel     the maximum heading level to put in TOC
         * @param int       $curLevel     the current heading level for this line
         * @param int[]     $curNumbering [IN/OUT] the current order for for each level
         * @param int       $curIndex     [IN/OUT] the current index in the headings array
         * @param Heading[] $headings     the array of all headings, in the file order
         * @param string    $basename     the base name for the file containing the headings
         * 
         * @return nothing
         */
        private function _storeTOClines($startLevel, $endLevel, $curLevel, &$curNumbering, &$curIndex, &$headings, $basename)
        {
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
                    $this->_storeTOClines($startLevel, $endLevel, $curLevel, $curNumbering, $curIndex, $headings, $basename);
                    // back to this level: loop on curIndex object, restore curLevel
                    $curLevel = $prevLevel;
                    continue;
                }
                // same level as current level? advance number if same as previous heading, else init
                if ($object->level == $curLevel) {
                    if ($object->level == $prevLevel) {
                        $curNumbering[$curLevel] += 1;
                    } else if ($object->level > $prevLevel) {
                        $curNumbering[$curLevel] = 1;
                    } else {
                        /// object level < prevlevel: nothing to do
                    }
                    // output: prepare alpha/numeric prefix
                    if ($this->_outputHTML) {
                        $prefix = str_repeat(str_repeat('&nbsp;', 4), $curLevel-1);
                        if (isset($this->_levelsNumbering[$curLevel])) {
                            foreach ($this->_levelsNumbering as $level => $numbering) {
                                if ($level <= $curLevel) {
                                    $prefix .= chr(ord($numbering) + $curNumbering[$level] - 1);
                                    if ($level==$curLevel) {
                                        $prefix .= ')'; //: end prefix
                                        break;
                                    } else {
                                        $prefix .= $this->_levelsSeparator[$level] ?? '';
                                    }
                                }
                            }
                        } else {
                            $prefix .= '- ';
                        }
                    } else {
                        $prefix .= chr(ord($this->_levelsNumbering[$curLevel]) + $curNumbering[$curLevel] - 1) . '. ';
                    }
                    // output: prepare html anchor
                    $anchor = '{file}';
                    if ($curIndex !== false) {
                        $text = "{$object->text}";
                        $anchor .= "#h{$object->number}"; /// ex: {file}#h1
                    }
                    // output: HTML line break prefix for non-numeric prefixes, except for the absolute first line
                    if ($this->_outputHTML) {
                        if ((($curLevel!=1 || $curNumbering[$curLevel]!=1)  // not level 1, or not numeric
                            && ($curNumbering[$curLevel]) >= 1)             // AND number not nul
                        ) {
                            $prefix = "<br />\n{$prefix}";
                        }
                    }
                    // send parts to files, interpret directives and '{file}' meta      
                    $this->_storeContent($prefix . ' [', null, true);  // keep starting spaces
                    $this->_storeContent($text, $basename, false);
                    $this->_storeContent("]({$anchor})", $basename, false, true); // ending EOL

                    // go next heading 
                    $curIndex += 1;
                    $prevLevel = $curLevel;
                } else {
                    // level skipping 
                    if ($object->level > $nextLevel) {
                        $this->_error("inconsistent heading level (skip from {$curLevel} to {$object->level}) in file {$basename} line {$object->line}");
                        $prevLevel = $curLevel;
                        $curLevel = $nextLevel;
                        $curNumbering[$curLevel] = $this->_levelsNumbering[$curLevel];
                        // loop same object new context
                    } else {
                        $this->_error("unknown error in headings level file {$basename} line {$object->line}");
                    }
                }
            } // while curIndex ok
            return true;
        }

        /** 
         * Find the first heading in the array for a level after a given line number.
         * 
         * @param Heading[] $headings the array for all headings in a file
         * @param int       $level    the heading level to look for
         * @param int       $line     the line number where to start search
         * 
         * @return bool|int false if no heading found, else the index of Heading object
         */
        private function _findHeadingIndex(&$headings, $level = 1, $line = 0) 
        {
            foreach ( $headings as $index => $object) {
                if ($object->line >= $line) {
                    if ($object->level == $level) {
                        return $index;
                    }
                }
            }
            return false;
        }

        /** 
         * Write to an output file, protect against doubled line feeds.
         * 
         * @param string $language the language code
         * 
         * @return nothing
         */
        private function _writeToFile($language) 
        {
            // file = $this->_outFiles[$language]
            // content = $this->_curOutputs[$language]
            if (array_key_exists($language, $this->_lastWritten)) {
                while (substr($this->_curOutputs[$language], 0, 1)=="\n" && $this->_lastWritten[$language]=="\n\n") {
                    $this->_curOutputs[$language] = substr($this->_curOutputs[$language], 1);
                }
            }
            // normalize to unix eol
            $this->_curOutputs[$language] = str_replace("\r\n", "\n", $this->_curOutputs[$language]);
            //reduce triple EOL to double, and trim ending spaces & tabs
            $this->_curOutputs[$language] = str_replace(["\n\n\n"," \n","\t\n"], ["\n\n", "\n","\n"], $this->_curOutputs[$language]);
            $this->_curOutputs[$language] = trim($this->_curOutputs[$language], " \t\0\x0B");
            
            // send to file and clear the buffer
            if (!empty($this->_curOutputs[$language])) {
                fwrite($this->_outFiles[$language], $this->_curOutputs[$language]);
                // retain previous last 2 character written
                $this->_lastWritten[$language] = mb_substr(($this->_lastWritten[$language] ?? "") . $this->_curOutputs[$language], -2, 2);
                $this->_curOutputs[$language] = "";
            } 
        }

        /** 
         * Output a content to current output files. 
         * Lines are buffered before being sent, and beginning : ending spaces are trimed.
         * Variables are expanded for language ({file}, {main}, {language}) in each generated file
         * 
         * @param string $content  the content to send to outputs buffers, and to files if EOL found
         * @param bool   $flush    true to force sending to files (used at end of file)
         * @param string $basename the base filename to use for {file} replacement
         * 
         * @return nothing
         */
        private function _outputToFiles($content, $flush=false, $basename=null)
        {
            switch ($this->_curLanguage) {
            case 'all': // output to all files
                foreach ($this->_outFiles as $language => $outFile) {
                    // replace filename in content?
                    //$finalContent = str_replace('{file}', $basename . ($language == $this->_mainLanguage ? '.md' : ".$language.md"), $content);
                    //TODO: improve this, because it doesn't work if default is not declared first
                    if (!array_key_exists($language, $this->_curOutputs)) {
                        $this->_curOutputs[$language] = '';
                    }
                    if (empty($this->_curOutputs[$language]) && !empty($this->_curOutputs['default'])) {
                        $this->_curOutputs[$language] = $this->_curOutputs['default'] . $content;
                    } else {
                        $this->_curOutputs[$language] .= $content;
                    }
                    // send to file if EOL or EOF
                    if ($flush || substr($this->_curOutputs[$language], -1, 1)=="\n") {
                        $basename = $this->getBasename($this->_inFilename);
                        $this->_curOutputs[$language] = $this->expandVariables($this->_curOutputs[$language], $basename, $language);
                        $this->_writeToFile($language);
                    }
                }
                $this->_curOutputs['default'] = '';
                break;
            case 'ignore': // no output
                break;
            case '': // set 
                $this->_curLanguage = 'all';
                break;
            default:
                // output to current language
                if (!array_key_exists($this->_curLanguage, $this->_curOutputs)) {
                    $this->_curOutputs[$this->_curLanguage] = '';
                }
                // replace filename in content?
                ///$finalContent = str_replace('{file}', $basename . ($this->_curLanguage == $this->_mainLanguage ? '.md' : ".{$this->_curLanguage}.md"), $content);
                if (empty($this->_curOutputs[$this->_curLanguage]) && !empty($this->_curOutputs['default'])) {
                    $this->_curOutputs[$this->_curLanguage] = $this->_curOutputs['default'] . $content;
                } else {
                    $this->_curOutputs[$this->_curLanguage] .= $content;
                }
                if ($flush || substr($this->_curOutputs[$this->_curLanguage], -1, 1)=="\n") {
                    $basename = $this->getBasename($this->_inFilename);
                    $this->_curOutputs[$this->_curLanguage] = $this->expandVariables($this->_curOutputs[$this->_curLanguage], $basename, $this->_curLanguage);
                    $this->_writeToFile($this->_curLanguage);
                }
                break;
            }
        }
        
        /**
         * Output into an array of outputs depending on a language. 
         * Updates output and current language code.
         * 
         * @param string   $content the content to output
         * @param string[] $outputs [IN/OUT] the array of outputs
         * @param string   $out     [IN/OUT] language code/'all'/'default'/'ignore'
         * 
         * @return nothing
         */
        private function _outputToArray($content, &$outputs, &$out) 
        {
            switch ($out) {
            case 'all':
                foreach ($this->_languages as $language => $bool) {
                    if (!array_key_exists($language, $outputs)) {
                        $outputs[$language] = '';
                    }
                    $outputs[$language] .= $content;
                }
                break;
            case 'ignore':
                break;
            case '': // after .))
                $out = 'all';
                break;
            default: // any language and 'default'
                if (!array_key_exists($out, $outputs)) {
                    $outputs[$out] = '';
                }
                $outputs[$out] .= $content;
                break;
            }
        }

        /** 
         * Compute heading level from the starting '#'s.
         * 
         * @param string $content the text with '#'s from which to compute heading level.
         * 
         * @return int the heading level
         */
        private function _getHeadingLevel($content) 
        {
            $heading = trim($content);
            $level = 0;
            $length = mb_strlen($heading, 'UTF-8');
            while ($heading[$level]=='#' && $level <= $length) {
                $level += 1;
            }
            return $level;
        }

        /** 
         * Store content as a header. Interpret any directive in the content and write the result to corresponding files.
         * Allowed directives: .all .ignore .default .<code>
         * Also writes an anchor for the TOC links if there is a recorded heading and no anchor has been forced.
         * The default anchor can be an HTML <A name=""> tag or an MD {: } shortcut, depending on the Generator output mode.
         * This function is also called for the TOC title itself.
         *  
         * @param int    $level   the heading level (number of '#')
         * @param string $content the text line for the heading, starting after the '#' and ending right before the '\n'.
         * @param string $anchor  [OPTIONAL] anchor to use, null to use the default anchor
         * 
         * @return boolean false if any writing error occurs, true if header stored correctly in files
         */
        private function _storeHeading($level, $content, $anchor=null) 
        {
            // compute anchor name if needed
            if ($anchor == null) {
                $headings = $this->_headings[$this->_relFilenames[$this->_inFilename]] ?? false;
                $index = $this->_findHeadingIndex($headings, $level, $this->_curLine);
                $headerObject = $headings[$index] ?? null;
                if ($headerObject) {
                    //TODO: '{:' syntax not known in MD viewers despite https://developers.google.com/style/headings-targets
                    //$anchor = $this->_outputHTML ? "h{$headerObject->number}" : "{: #h{$headerObject->number}}";
                    $anchor = "h{$headerObject->number}";
                }
            } else {
                //TODO: '{:' syntax not known in MD viewers despite  https://developers.google.com/style/headings-targets
                //if (!$this->_outputHTML) {
                //   $anchor = "{: #{$anchor}}";
                //}
            }
            // write prefix 
            $prefix = str_repeat('#', $level);
            foreach ($this->_languages as $language => $bool) {
                // HTML/MD: '#### '
                if (fwrite($this->_outFiles[$language], $prefix . ' ')===false) {
                    $this->_error("unable to write to {$this->_outFilenames[$language]}");
                    return false;
                }
                // write HTML anchor 
                ///TODO: always use html anchors
                //if ($this->_outputHTML) {
                    // HTML: '#### <a name="anchor"></a>'
                if (fwrite($this->_outFiles[$language], "<a name=\"{$anchor}\"></a>")===false) {
                    $this->_error("unable to write to {$this->_outFilenames[$language]}");
                    return false;
                }
                ///}
            }
            // write heading content, interpret variables, no EOL
            // HTML: '#### <a name="anchor"></a>content'
            // MD  : '#### content'
            $heading = trim($content);
            $this->_storeContent($heading, null, false);

            /*TODO: {: anchors not known in MD viewers
            // write MD anchors
            if (!$this->_outputHTML) {
                // MD  : '#### content{: #anchor}'
                foreach ($this->_languages as $language => $bool) {
                    if (fwrite($this->_outFiles[$language], $anchor)===false) {
                        $this->_error("unable to write to {$this->_outFilenames[$language]}");
                        return false;
                    }
                }
            }
            */
            // Finish heading with 2 x EOL
            $this->_storeContent("\n\n", null, false, false, true);
            // Remember L1 heading so we stop ignoring empty EOLs
            if ($level == 1) {
                $this->_L1headingSet = true;
            }
        }

        /** 
         * Store content. Interpret any directive in the content and write the result to corresponding files.
         * Allowed directives: .all .ignore .default .<code>
         * 'all' is assumed at entry.
         * Expand variables for each language.
         * 
         * @param string $content   the text to store, ending right before the '\n'.
         * @param string $basename  the base filename to use for {file} replacement in content
         * @param bool   $keepSTART true to keep the starting spaces (useful for TOC)
         * @param bool   $endCR     [OPTIONAL] true to put a \n at the end [false]
         * @param bool   $keepCR    [OPTIONAL] false to delete \n endings, true to keep them [false]
         * 
         * @return boolean false if any writing error occurs, true if header stored correctly in files
         */
        private function _storeContent($content, $basename, $keepSTART, $endCR = false, $keepCR = false)
        {
            $pos = 0;
            $inDirective = false;
            $curword = '';
            $length = mb_strlen($content, 'UTF-8');
            $curOutput = 'all';
            $outputStack = [];
            $outputs = [];
            $trim = $keepSTART ? "rtrim" : "trim";
            while ($pos < $length) {
                $c = mb_substr($content, $pos, 1);
                if ($c=='.') {
                    // previous content to be stored?
                    if (!empty($curword)) {
                        $this->_outputToArray($curword, $outputs, $curOutput);
                        $curword = '';
                    }
                    $inDirective = true;
                }
                if ($inDirective) {
                    $tryWord = mb_strtolower($curword . $c, 'UTF-8');
                    if (array_key_exists($tryWord, $this->_directives)) {
                        $newOutput = mb_substr($tryWord, 1, -2); // 'all', 'default', 'ignore', '<code>', ''
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
                    $this->_outputToArray($c, $outputs, $curOutput);
                }
                $pos += 1;
            }
            if (!empty($curword)) {
                $this->_outputToArray($curword, $outputs, $curOutput);
                $curword = '';
            }
            // retain only non-empty outputs
            $final = [];
            foreach ($outputs as $output => $content) {
                if (!empty($trim($content, " \t\0\x0B"))) {
                    $final[$output] = $trim($content, " \t\0\x0B");
                    if (!$keepCR) {
                        $final[$output] = rtrim($final[$output], "\n");
                    }
                }
            }
            // expand variables and output content to language files
            foreach ($this->_languages as $language => $bool) {
                $text = array_key_exists($language, $final) ? $final[$language] : ($final['default'] ?? '');
                $text = $this->expandVariables($text, $basename, $language);
                $text .= $endCR ? "\n" : '';
                if (fwrite($this->_outFiles[$language], $text)===false) {
                    return false;
                }
                $this->_lastWritten[$language] = mb_substr(($this->_lastWritten[$language] ?? "") . $text, -2, 2);
            }
            return true;
        }
        
        /** 
         * Add a language to the languages set.
         * 
         * @param string $code the language code to add.
         * 
         * @return nothing
         */
        private function _addLanguage($code) 
        {
            // main=<code> ?
            if (mb_stripos($code, 'main=', 0, 'UTF-8')!==false) {
                $this->_mainLanguage = mb_strtolower(mb_substr($code, 5, null, 'UTF-8'));
            } else {
                if (!array_key_exists($code, $this->_languages)) {
                    $this->_languages[$code] = true;
                }
            }
        }

        /**
         * Find all headings and sub headings in a set of files.
         * The files which are not under the given root directory will be ignored.
         * Files with no headings will receive a heading using their filename
         * 
         * @param string[] $filenames the pathes of the files to explore for headings
         * 
         * @return nothing
         */
        public function exploreHeadings($filenames) 
        {

            $this->_headings = [];
            $number = 1;
            foreach ($filenames as $filename) {
                // get relative filename, ignore if not the right root
                $rootLen = mb_strlen($this->rootDir, 'UTF-8');
                $baseDir = mb_substr($filename, 0, $rootLen, 'UTF-8');
                if ($baseDir != $this->rootDir) {
                    $this->_error("wrong root dir for file [$filename], should be [$this->rootDir]");
                    continue;
                }
                $relFilename = mb_substr($filename, $rootLen+1, null, 'UTF-8');
                $this->_relFilenames[$filename] = $relFilename;
                $this->_inFilename = $filename;
                $this->_inFile = fopen($filename, 'rb');
                if ($this->_inFile===false) {
                    $this->_error("could not open [$filename]");
                    continue;
                }
                $this->_headings[$relFilename] = [];
                $index = 0;
                $this->_curLine = 1;
                $prevLevel = 0;
                do {
                    $text = trim(fgets($this->_inFile));
                    if (($text[0]??'') == '#') {
                        // prepare an object
                        $object = new Heading();
                        // sequential number for all headers of all files
                        $object->number = $number;
                        $number += 1;
                        // count number of '#' = heading level
                        $object->level = $this->_getHeadingLevel($text);
                        if ($object->level > $prevLevel + 1) {
                            $this->_error("level {$object->level} heading skipped one or more heading levels");
                        }
                        // line number in this file
                        $object->line = $this->_curLine;
                        // trimmed text without # prefix
                        $object->text = trim(mb_substr($text, $object->level, null, 'UTF-8'));
                        // store the object in array for this file
                        $this->_headings[$relFilename][$index] = $object;
                        $prevLevel = $object->level;
                        $index += 1;
                    }
                    $this->_curLine += 1;
                } while (!feof($this->_inFile));
                $this->_closeInput();
                // force a level 1 object if no headings
                if (count($this->_headings[$relFilename])==0) {
                    $object = new Heading();
                    $object->number = $number;
                    $number += 1;
                    $object->level = 1;
                    $object->line = 1;
                    $object->text = $relFilename;
                    $this->_headings[$relFilename][] = $object;
                }
            } // next file
        }

        // Buffer for current word starting with a dot
        private $_curWord = '';
        // Flag to know if we're in a word starting with a dot
        private $_inDirective = false;

        /**
         * Start a new possible directive with a dot.
         * 
         * @param string $c the character (must be '.')
         * 
         * @return nothing
         */
        private function _startDirectiveWith($c) 
        {
            $this->_inDirective = true;
            $this->_curWord = $c;
        }

        /** 
         * Reset parsing status to neutral.
         * 
         * @return nothing
         */
        private function _resetParsing() 
        {
            $this->_inDirective = false;
            $this->_curWord = '';
        }

        /**
         * Read a character from input file, buffered in line.
         * 
         * @param handle $file the current opened file handle
         * 
         * @return bool|string next character, "\n" when end of line is reached, or false when file and buffer are finished.
         */
        private function _getChar($file) 
        {
            // any  character left on current line?
            if ($this->_lineBufPos < $this->_lineBufLength - 1) {
                $this->_lineBufPos += 1;
            } else {
                do {
                    $this->_lineBuf  = fgets($file);
                    if (!$this->_lineBuf) {
                        $this->_curChar = false;
                        return false; // finished
                    }
                    $this->_lineBufPos = 0;
                    $this->_lineBufLength = mb_strlen($this->_lineBuf, 'UTF-8');
                } while ($this->_lineBufPos >= $this->_lineBufLength);
            }
            $this->_prevChar = $this->_curChar;
            $this->_curChar = mb_substr($this->_lineBuf, $this->_lineBufPos, 1, 'UTF-8');
            $this->_debugEcho();
            return $this->_curChar;
        }

        /**
         * Parse a heading starting with at least current character which must be '#'.
         * 
         * @param handle $file the current opened file handle
         * 
         * @return nothing 
         */
        private function _parseHeading($file) 
        {
            $headingContent = $this->_curChar; // '#'
            do {
                $c = $this->_getChar($file);
                /// end of line or end of file?
                if ($c===false || $c=="\n") {
                    $level = $this->_getHeadingLevel($headingContent);
                    $this->_storeHeading($level, trim(mb_substr($headingContent, $level, null, 'UTF-8')));
                    $this->_curLine += 1;
                    break;
                }
                // heading line not finished: store and go on
                $headingContent .= $c;
            } while ($c !== false);
        }

        /**
         * Close input file.
         * 
         * @return false in all cases
         */
        private function _closeInput() 
        {
            if ($this->_inFile != null) {
                fclose($this->_inFile);
                $this->_inFile = null;
            }
            return false;
        }

        /** 
         * Close output files.
         * 
         * @return false in all cases
         */
        private function _closeOutput() 
        {
            foreach ($this->_outFiles as &$outFile) {
                if ($outFile != null) {
                    fclose($outFile);
                }
            }
            unset($this->_outFiles);
            $this->_outFiles = [];
            return false;
        }

        /** 
         * Open the input streaming file and prepare the output filename template.
         * 
         * @param string $filename The input file path. 
         *                         Use absolute path or relatives to rootDir.
         * 
         * @return bool true if input file was opened correctly, false for any error.
         */
        public function setInputFile($filename) 
        {
            // close any previous file
            if ($this->_inFile != null) {
                $this->_closeInput();
            }
            // open or exit
            $this->_inFile = fopen($filename, "rb");
            if ($this->_inFile===false) {
                return $this->_closeInput();
            }
            // prepare output file template
            $extension = isMLMDfile($filename);
            if ($extension===false) {
                return $this->_closeInput();
            }

            // retain base name as template and reset line number
            $this->_outFilenameTemplate = mb_substr($filename, 0, -mb_strlen($extension, 'UTF-8'), 'UTF-8');
            $this->_inFilename = $filename;
            $this->_curLine = 1;
            $this->_curLanguage = 'ignore';

            $this->_closeOutput();
            return true;
        }
        
        /** 
         * Parse an input file and generate files.
         * 
         * @param string $filename The path to the input file.
         * 
         * @return bool true if input file processed correctly, false if any error.
         */
        public function parse($filename) 
        {
            if (!$this->setInputFile($filename)) {
                return false;
            }
            if (feof($this->_inFile)) {
                return false;
            }

            $c = '';
            if (getenv("debug")=="1") {
                echo "\n[1]:";//: first line number
            }
            // ignore until .languages is done and first '#' heading has been processed
            $this->_languagesSet = false;
            $this->_L1headingSet = false;
            do {
                $this->_getChar($this->_inFile);
                
                switch ($this->_curChar) { 
                case false:
                    break;
                case '.':
                    // if already in directive detection, flush
                    if ($this->_prevChar=="\n" || $this->_inDirective) {
                        // flush previous content and stop directive detection
                        $this->_outputToFiles($this->_curWord);
                        $this->_resetParsing();
                    }
                    // start directive detection?
                    if (empty($this->_curWord) || !$this->_inDirective) {
                        $this->_startDirectiveWith($this->_curChar);
                        break;
                    }
                    // currently in a directive?
                    if ($this->_inDirective) {
                        // possible end of directive, try to interpret current word
                        $tryWord = mb_strtolower($this->_curWord, 'UTF-8');
                        if (array_key_exists($tryWord, $this->_directives)) {
                            // start effect and restart character capture
                            $functionName = $this->_directives[$tryWord];
                            $this->$functionName($tryWord);
                            $this->_resetParsing();//TODO: check if useless
                        } else {
                            // no: keep storing (in current word)
                            $this->_curWord .= $this->_curChar;
                            break;
                        }
                        // start a possible new directive with this '.'
                        $this->_startDirectiveWith($this->_curChar);
                    } else {
                        $$this->_curWord .= $this->_curChar;
                    }
                    break;
                case "\n":
                    $this->_curLine += 1;
                    if ($this->_languagesSet && $this->_L1headingSet) {
                        $this->_outputToFiles($this->_curWord . $this->_curChar);
                    }
                    $this->_resetParsing();
                    break;
                case '#':
                    if (!$this->_languagesSet) {
                        break;
                    }
                    // start or continue a heading
                    if ($this->_prevChar=="\n") {
                        $this->_parseHeading($this->_inFile);
                        break;
                    } 
                    // not a heading: fall through to default processing
                default:
                    // not '.', '\n' or heading starting '#'
                    if ($this->_inDirective) {
                        // try to identify a directive with current store and this character
                        $tryWord = mb_strtolower($this->_curWord . $this->_curChar, 'UTF-8');
                        if (array_key_exists($tryWord, $this->_directives)) {
                            // apply directive effect and reset current store
                            $functionName = $this->_directives[$tryWord];
                            $this->$functionName($tryWord);
                            break;
                        }
                        // not a directive, add to current word store
                        $this->_curWord .= $this->_curChar;
                    } else {
                        if ($this->_languagesSet) {
                            $this->_outputToFiles($this->_curChar);
                        }
                    }
                    break;
                } // switch $this->_curChar
            } while ($this->_curChar !== false);
            // flush anything left in all output buffers
            $this->_curLanguage = 'all';
            $this->_outputToFiles($this->_curWord, true);
            // MD047: force single \n file ending if needed
            foreach ($this->_languages as $language => $bool) {
                if (substr($this->_lastWritten[$language], -1, 1) != "\n") {
                    fwrite($this->_outFiles[$language], "\n");
                }
                fclose($this->_outFiles[$language]);
                $this->_outFiles[$language] = null;
            }
            return true;
        }

        /**
         * Parse a file list.
         * 
         * @param string[] $inFilenames array of file names to process.
         * 
         * @return nothing
         */
        public function parseFiles($inFilenames) 
        {
            $this->exploreHeadings($inFilenames);
            foreach ($inFilenames as $filename) {
                $this->parse($filename);
            }
        }
    }

} // namespace