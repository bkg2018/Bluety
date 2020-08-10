<?php
//MARK: Global Utility functions

/**
 * Check if running on Windows.
 * Check if the environment variable SYSTEMROOT exists and include 'Windows' in value.
 * 
 * @return true if Windows is detected.
 */
function isWindows() : bool
{
    return stripos(getenv('SYSTEMROOT'), 'windows') !== false;
}

/**
 * Check if a filename has an MLMD valid extension and get this extension.
 *
 * @param string $filename the file name or path to test.
 *
 * @return string the file extension (.base.md or .mlmd), null if invalid
 *                mlmd file name.
 */
function isMLMDfile(string $filename) : ?string
{
    $extension = ".base.md";
    $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
    if ($pos === false) {
        $extension = ".mlmd";
        $pos = mb_stripos($filename, $extension, 0, 'UTF-8');
        if ($pos === false) {
            return null;
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
function exploreDirectory(string $dirName) : array
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
            } elseif (isMLMDfile($thisFile) !== null) {
                $filenames[] = $thisFile;
            }
        }
        closedir($dir);
    }
    return $filenames;
}
