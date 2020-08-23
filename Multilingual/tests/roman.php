<?php

/*
function getRoman1(int $number) : string
{
    $result = '';
    foreach (self::$intToRoman as $limit => $roman) {
        $result .= \str_repeat($roman, intdiv($number,$limit));
        $number = $number % $limit;
        if ($number <= 0) {
           break;
        }
    }            
    return $result;
}
function getRoman3(int $number) : string
{
    $result = '';
    while ($number > 0) {
        foreach (self::$intToRoman as $limit => $roman) {
            if ($number >= $limit) {
                $number -= $limit;
                $result .= $roman;
                break;
            }
        }
    }            
    return $result;
}


function getRoman4($N) : string 
{
    $c='IVXLCDM';
    for($a=5, $b = $s = ''; $N ; $b++, $a ^= 7) {
        for($o=$N % $a, $N=$N/$a^0 ; $o-- ; $s=$c[ $o > 2 ? $b + $N - ($N &= -2) + $o = 1 : $b > 0 ? $b : 0].$s);
    }
    return $s;
}


    romanLoop(int $nbLoops, int $index) : float
    {
        $function = "getRoman$index";
        $timer = microtime(true);
        for ($i = 0 ; $i < $nbLoops ; $i++) {
            $test = Numbering::$function($i);
        }
        return microtime(true) - $timer;
    }

    /// Check three metods for getRoman
    public function testRomanPerformance()
    {
        $nbLoops = 20000;
        foreach( [1,2,3,4] as $index ) {
            $timer = "time$index";
            $$timer = $this->romanLoop($nbLoops, $index);
            $mean = "mean$index";
            $$mean = $$timer / $nbLoops;
        }
        echo "1: $time1\t$mean1\n";
        echo "2: $time2\t$mean2\n";
        echo "3: $time3\t$mean3\n";
        echo "4: $time4\t$mean4\n";
        $this->assertTrue(true);
    }

    */

/**
 * @param int $number
 * @return string
 */
function getRoman1(int $number) : string
{
    $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
    $returnValue = '';
    while ($number > 0) {
        foreach ($map as $roman => $int) {
            if($number >= $int) {
                $number -= $int;
                $returnValue .= $roman;
                break;
            }
        }
    }
    return $returnValue;
}
function getRoman2(int $number) : string
{
    global $map;
    $returnValue = '';
    foreach ($map as $roman => $int) {
        while ($number >= $int) {
            $number -= $int;
            $returnValue .= $roman;
        }
        if ($number == 0) {
           break;
        }
    }            
    return $returnValue;
}

$map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);

function romanLoop(int $nbLoops,$index) : float
{
    $timer = microtime(true);
    $function = "getRoman$index";
    for ($i = 0 ; $i < $nbLoops ; $i++) {
        $test = $function($i);
    }
    return microtime(true) - $timer;
}

$nbLoops = 20000;
$timer1 = romanLoop($nbLoops,1);
$mean1 = $timer1 / $nbLoops;
$timer2 = romanLoop($nbLoops,2);
$mean2 = $timer2 / $nbLoops;
echo "1:$timer1\t$mean1\n";
echo "2:$timer2\t$mean2\n";
