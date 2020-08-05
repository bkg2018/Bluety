<?php
//MARK: Global Utility functions

/**
 * Check if running on Windows
 */
function isWindows()
{
    return stripos(getenv('SYSTEMROOT'), 'windows') !== false;
}

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
