
<?php 

/** Clean a title so it can be used as MD link anchor 
 * - downcase
 * - remove anything that is not a letter, number, space or hyphen
 * - changes spaces to hyphen
 * - reduce multiple spaces to one hyphen
 * Beware Markdown headings reduction is not bijective so reduced links can be identical even when their original headings differ 
 * by some symbols or spacing. To make a Markdown anchor unique requires additional characters, like a globally unique number.
 * MLMD will use such globally unique numbers.
 * @param
 */
function cleanedMDtitle($text) {
    $text = mb_strtolower($text,'UTF-8');
    $result='';
    $prevChar = '';
    $pos = 0;
    $max = mb_strlen($text,'UTF-8');
    while ($pos < $max) {
        $curChar = mb_substr($text,$pos,1);
        if (($curChar >= 'a' && $curChar <= 'z') || ($curChar >= '0' && $curChar <= '9') || ($curChar == '-')) {
            $result .= $curChar;
        } else if ($curChar == ' ') {
            if ($prevChar != ' ') {
                $result .= '-';
            }
        }
        $prevChar = $curChar;
        $pos += 1;
    }
    return $result;
}