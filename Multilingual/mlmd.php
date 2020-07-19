<?php 
/** Multilingual Markdown generator 
 * -i <filename.base.md> [...]
 * generates <filename.xx.md> for languages declared in '.languages' directive.
 * if no parameter is given, explore current and sub directories for '*.base.md' and '.mlmd' files
 * and generate files for each file found.
*/

/** Recursively explore a directory and its subdirectories and return an array of '.base.md' and '.mlmd' file pathes. */
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
            } else {
                $pos = mb_stripos($thisFile, ".base.md", 0, 'UTF-8');
                if ($pos===false) $pos = mb_stripos($thisFile, ".mlmd", 0, 'UTF-8');
                if ($pos!==false) {
                    $filenames[] = $thisFile;
                }
            }
        }
        closedir($dir);
    }
    return $filenames;
}

/** Generator class. */
class MultilingualMarkdownGenerator {

    // Directives handling function names
    private $directives = [
        '.all(('        => 'all',
        '.ignore(('     => 'ignore',
        '.default(('    => 'default',
        '.(('           => 'default',
        '.))'           => 'end',
        '.languages'    => 'languages',
        '.toc'          => 'toc'
    ];
    // Input filename and file
    private $inFilename = null;             // 'example.base.md'
    private $inFile = null;                 // input file handle
    private $lineBuf = "";                  // current line content
    private $lineBufPos = 0;                // current pos in line buffer (utf-8)
    private $lineBufLength = 0;             // current line size in characters (utf-8)
    private $prevChar = '';                 // used to detect line and heading start
    private $curChar = '';                  // current char
    // Output filenames and files
    private $outFilenameTemplate = null;    // 'example'
    private $outFilenames = [];             // '<language>' => 'example.md' / 'example.<language>.md'
    private $outFiles = [];                 // '<language>' => file handle
    private $lastWritten = [];              // last  character written to file
    private $curOutputs = [];               // current utput buffers for files
    private $languages = [];                // added languages
    private $mainLanguage =false;
    // Directives status
    private $curLanguage = 'ignore';        // last language directive (all / <code> / ignore)
    private $directiveStack = [];           // previous language directive (all / <code>)

    // debug help
    private $curLine = 1;

    /** directives */

    /** .all(( */
    private function all($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'all';
        $this->resetParsing();
    }
    /** .ignore(( */
    private function ignore($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'ignore';
        $this->resetParsing();
    }
    /** .(( or .default(( */
    private function default($dummy) {
        array_push($this->directiveStack,$this->curLanguage);
        $this->curLanguage = 'default';
        $this->resetParsing();
    }
    /** .)) */
    private function end($dummy) {
        // return to previous state
        if (count($this->directiveStack) > 0) {
            $this->curLanguage = array_pop($this->directiveStack);
        } else {
            $this->curLanguage = 'all';
        }
        $this->resetParsing();
    }
    /** .<code>(( */
    private function change($directive) {
        $code = mb_strtolower(mb_substr($directive, 1, -2,'UTF-8'),'UTF-8'); // '.en(('  -> 'en'
        if (array_key_exists($code, $this->languages)) {
            array_push($this->directiveStack,$this->curLanguage);
            $this->curLanguage = $code;
            $this->resetParsing();
        }
    }
    /** .languages */
    private function languages($dummy) {
        $curWord = '';
        $stopNow = false;
        do {
            $this->getChar($this->inFile);
            if (getenv("debug")=="1") {
                echo $this->curChar;
            }
            if ($this->curChar!==false) {
                switch ($this->curChar) {
                    // CR?
                    case "\r":
                        // do not store
                        break;
                    // line feed?
                    case "\n":
                        // stop this directive 
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

        // finish languages registering, open files, and set initial status
        $this->endLanguages();
    }
    /** .toc */
    private function toc($dummy) {
        /*
        $curWord = '';
        $stopNow = false;
        $startLevel = false;
        $endLevel = false;
        while (!feof($this->inFile) && !$stopNow) {
            $c = fgetc($this->inFile);
            switch ($c) {
                // end of line?
                case "\n":
                case "\r":
                    // stop this directive 
                    $stopNow = true;
                    // but finish with current word (fall-through)
                // end of keyword?
                case '-':
                    // if we have a word, set it as additionnal language
                    if (!empty($curWord)) {
                        $this->addLanguage($curWord);
                        $curWord = '';
                    }
                    break;
                // store current keyword
                default:
                    $curWord .= $c;
                    break;
            }
        }
        $this->curLanguage = 'ignore';
        */
    }

    /** Write to an output file, protect against doubled line feeds */
    private function writeToFile($code) {
        // file = $this->outFiles[$code]
        // content = $this->curOutputs[$code]
        if (array_key_exists($code,$this->lastWritten)) {
            while (substr($this->curOutputs[$code],0,1)=="\n" && $this->lastWritten[$code]=="\n\n") {
                $this->curOutputs[$code] = substr($this->curOutputs[$code], 1);
            }
        }
        // normalize to unix eol
        $this->curOutputs[$code] = str_replace("\r\n", "\n", $this->curOutputs[$code]);
        //reduce triple EOL to double, and trim ending spaces & tabs
        $this->curOutputs[$code] = str_replace(["\n\n\n"," \n","\t\n"], ["\n\n", "\n","\n"], $this->curOutputs[$code]);
        $this->curOutputs[$code] = trim($this->curOutputs[$code], " \t\0\x0B");
        
        // send to file and clear the buffer
        if (!empty($this->curOutputs[$code])) {
            fwrite($this->outFiles[$code], $this->curOutputs[$code]);
            // retain previous last 2 character written
            $this->lastWritten[$code] = mb_substr(($this->lastWritten[$code] ?? "") . $this->curOutputs[$code],-2,2);
            $this->curOutputs[$code] = "";
        } 
    }

    /** Output a content to current output files. 
     *  Lines are buffered before being sent, and beginning : ending spaces are trimed.
     *  @param string $content the content to send to outputs buffers, and to files if an end of line is found
     *  @param bool $flush true to force sending to files (used at end of file)
    */
    private function outputToFiles($content, $flush=false) {

        switch ($this->curLanguage) {
            case 'all': // output to all files
                foreach ($this->outFiles as $code => $outFile) {
                    // concatenate to buffer, use default if empty content
                    //TODO: improve this, because it doesn't work if default is not declared first
                    if (!array_key_exists($code, $this->curOutputs)) $this->curOutputs[$code] = '';
                    if (empty($this->curOutputs[$code]) && !empty($this->curOutputs['default'])) {
                        $this->curOutputs[$code] = $this->curOutputs['default'] . $content;
                    } else {
                        $this->curOutputs[$code] .= $content;
                    }
                    // send to file if EOL or EOF
                    if ($flush || substr($this->curOutputs[$code],-1,1)=="\n") {
                        $this->writeToFile($code);
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
                if (empty($this->curOutputs[$this->curLanguage]) && !empty($this->curOutputs['default'])) {
                    $this->curOutputs[$this->curLanguage] = $this->curOutputs['default'] . $content;
                } else {
                    $this->curOutputs[$this->curLanguage] .= $content;
                }
                if ($flush || substr($this->curOutputs[$this->curLanguage],-1,1)=="\n") {
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

    /** store current heading  */
    private function storeHeading($content) {
        // find level
        $heading = trim($content);
        $level = 0;
        $length = mb_strlen($heading,'UTF-8');
        while ($heading[$level]=='#' && $level <= $length) {
            $level += 1;
        }
        $prefix = trim(mb_substr($heading, 0, $level, 'UTF-8'));
        $heading = trim(mb_substr($heading, $level, NULL, 'UTF-8'));
        // write prefix to all output files
        foreach($this->outFiles as $outFile) {
            fwrite($outFile, $prefix . ' ');
        }
        // write parts
        $pos = 0;
        $inDirective = false;
        $curword = '';
        $length = mb_strlen($heading,'UTF-8');
        $curOutput = 'all';
        $outputStack = [];
        $outputs = [];
        while ($pos < $length) {
            $c = mb_substr($heading,$pos,1);
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
                    if (empty($newOutput)) { // means directive is .))
                        $curOutput = array_pop($outputStack);
                    } else {
                        array_push($outputStack, $curOutput);
                        $curOutput = $newOutput;
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
        // retain only non-empty outputs
        $final = [];
        foreach($outputs as $output => $content) {
            if (!empty(trim($content))) {
                $final[$output] = trim($content);
                if (substr($final[$output],-1,1) == "\n") {
                    $final[$output] = substr($final[$output],0,-1);
                }
            }
        }
        /*if (getenv("debug")=="1") {
            print_r($final);
        }*/
        // output headings with an ending \n
        foreach ($this->languages as $language => $bool) {
            $text = array_key_exists($language, $final) ? $final[$language] : $final['default'];
            fwrite($this->outFiles[$language], $text . "\n");
        }
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

    /** Finish languages registering */
    private function endLanguages() {
        if ($this->outFilenameTemplate==null) return false;
        foreach($this->languages as $language => $bool) {
            // prepare filename
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
        array_push($this->directiveStack,$this->curLanguage);
   }


    /** Find all headings and sub headings */
    private function getHeadings($filename) {
        $inFile = fopen($filename,'rb');
        $headings = [];
        $curHeading = '';
        $curLevel = 0;
        while (!feof($inFile)) {
            $line = trim($fgets($inFile));
            if ($line[0]!='#') continue;
            // count level
            $level = 0;
            $length = min(9,mb_strlen($line,'UTF-8'));
            while ($line[$level]=='#' && $level <= $length) {
                $level += 1;
            }
            $line = trim(mb_substr($line, $level, NULL,'UTF-8'));
        }
    }

    /** Store a character depending on mode. */
    private function storeCharacter($c) {
        $this->curword .= $c;
    }


    /** Close all output files. */
    private function endAll() {
        foreach ($this->outFiles as $outFile) {
            fclose($outFile);
        }
    }

    // Buffer for current word starting with a dot
    private $curword = '';
    // Flag to know if we're in a word starting with a dot
    private $inDirective = false;

    /** Start a new possible directive with a dot. */
    private function startDirectiveWith($c) {
        $this->inDirective = true;
        $this->curword = $c;
    }

    /** reset parsing status to neutral */
    private function resetParsing() {
        $this->inDirective = false;
        $this->curword = '';
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
        return $this->curChar;
    }

    /** Parse a heading starting with at least one '#' */
    private function parseHeading($file) {
        $headingContent = $this->curChar; // '#'
        do {
            $c = $this->getChar($file);
            if (getenv("debug")=="1") {
                echo $this->curChar;
            }
            /// end of line or end of file?
            if ($c===false || $c=="\n") {
                $this->storeHeading($headingContent);
                $this->curLine += 1;
                break;
            }
            // heding line not finished: store and go on
            $headingContent .= $c;
        } while ($c);
        return true;
    }

    /** Open the input streaming file and prepare the output filename template. */
    public function setInputFile($filename) {
        // no null file
        if ($this->inFile != null) {
            fclose($this->inFile);
        }
        // open or exit
        $this->inFile = fopen($filename, "rb");
        if ($this->inFile===false) {
            return false;
        }
        // prepare output file template
        $pos = mb_stripos($filename, '.base.', 0,'UTF-8');
        if ($pos===false) {
            $pos = mb_stripos($filename, '.mlmd', 0,'UTF-8');
            if ($pos===false) return false;
        }
        // retain base name as template and reset line number
        $this->outFilenameTemplate = mb_substr($filename, 0, $pos,'UTF-8');
        $this->inFilename = $filename;
        $this->curLine = 1;
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
            echo "[1]:";//: first line number
        }

        do {
            $this->getChar($this->inFile);
            //$$ DEBUG $$
            if (getenv("debug")=="1") {
                if (($this->curChar !== false) && ($this->prevChar=="\n")) {
                    echo "[{$this->curLine}]:";
                }
                echo $this->curChar;
            }
            //$$ DEBUG $$
            switch ($this->curChar) { 
            case false:
                break;
            case '.':
                // if already in directive detection, flush
                if ($this->prevChar=="\n" || $this->inDirective) {
                    // flush previous content and stop directive detection
                    $this->outputToFiles($this->curword);
                    $this->resetParsing();
                }
                // start directive detection?
                if (empty($this->curword) || !$this->inDirective) {
                    $this->startDirectiveWith($this->curChar);
                    break;
                }
                // currently in a directive?
                if ($this->inDirective) {
                    // possible end of directive, try to interpret current word
                    $tryWord = mb_strtolower($this->curword,'UTF-8');
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
            case '#':
                // start or continue a heading
                if ($this->prevChar=="\n") {
                    $this->parseHeading($this->inFile);
                } else {
                    // store as normal text if not at line beginning
                    $this->storeCharacter($this->curChar);
                }
                break;
            case "\n":
                $this->outputToFiles($this->curword . $this->curChar);
                $this->curLine += 1;
                $this->resetParsing();
                break;
            default:
                // not '.', '\n' or '#'
                if ($this->inDirective) {
                    // try to identify a directive with current store and this character
                    $tryWord = mb_strtolower($this->curword . $this->curChar,'UTF-8');
                    if (array_key_exists($tryWord, $this->directives)) {
                        // apply directive effect and reset current store
                        $functionName = $this->directives[$tryWord];
                        $this->$functionName($tryWord);
                        break;
                    }
                    // not a directive, add to current word store
                    $this->storeCharacter($this->curChar);
                } else {
                    // out of any directive and heading: output using current settings
                    if ($this->curChar=="\n") {
                        // DEBUG: count the line
                        $this->curLine += 1;
                    }
                    $this->outputToFiles($this->curChar);
                }
                break;
            } // switch $this->curChar
        } while ($this->curChar !== false);
        // flush anything left in all output buffers
        $this->curLanguage = 'all';
        $this->outputToFiles($this->curword, true);
        // MD047: force single \n file ending if needed
        foreach($this->languages as $code => $bool) {
            if (substr($this->lastWritten[$code],-1,1) != "\n") {
                fwrite($this->outFiles[$code], "\n");
            }
            fclose($this->outFiles[$code]);
            $this->outFiles[$code] = null;
        }
    }
}

// CLI launch arguments parsing
$inFilenames = [];
$arg = 1;
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
    }
    $arg += 1;
}

// no file: explore
if (count($inFilenames)==0) {
    $inFilenames = exploreDirectory(getcwd());
}

$parser = new MultilingualMarkdownGenerator();
foreach($inFilenames as $filename) {
    $parser->Parse($filename);
}
?>