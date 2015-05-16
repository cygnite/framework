<?php
namespace Cygnite\Common;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/*
 $sampleData = array(
    'first' => array(
        'first-1' => 1,
        'first-2' => 2,
        'first-3' => 3,
        'first-4' => 4,
        'first-5' => 5,
    ),
    'second' => array(
        'second-1' => 1,
        'second-2' => 2,
        'second-3' => 3,
        'second-4' => 4,
        'second-5' => 5,
    ));

$parser->writeIni($sampleData, './data.ini', true);
 */


class Parser
{
    public function writeIni($array, $file)
    {
        $res = array();

        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $res[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    $res[] = "$skey = ".(isNumeric($sval) ? $sval : '"'.$sval.'"');
                }
            } else {
                $res[] = "$key = ".(isNumeric($val) ? $val : '"'.$val.'"');
            }
        }
        safefilerewrite($file, implode("\r\n", $res));
    }


    public function safefilerewrite($file_name, $data_to_save)
    {
        if ($fp = fopen($file_name, 'w')) {
            $startTime = microtime();

            do {
                $canWrite = flock($fp, LOCK_EX);

                //If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                if (!$canWrite) {
                    usleep(round(rand(0, 100)*1000));
                }
            } while ((!$canWrite)and((microtime()-$startTime) < 1000));

            //file was locked so now we can store information
            if ($canWrite) {
                fwrite($fp, $data_to_save);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    public function write_ini($assoc_arr, $path, $has_sections=false)
    {
        $content = "";

        if ($has_sections) {
            foreach ($assoc_arr as $key => $elem) {
                $content .= "[".$key."]\n";
                foreach ($elem as $key2 => $elem2) {
                    if (is_array($elem2)) {
                        for ($i=0; $i<count($elem2); $i++) {
                            $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                        }

                    } elseif ($elem2=="") {
                        $content .= $key2." = \n";
                    } else {
                        $content .= $key2." = \"".$elem2."\"\n";
                    }
                }
            }
        } else {
            foreach ($assoc_arr as $key2 => $elem) {
                if (is_array($elem)) {
                    for ($i=0; $i<count($elem); $i++) {
                        $content .= $key2."[] = \"".$elem[$i]."\"\n";
                    }
                } elseif ($elem=="") {
                    $content .= $key2." = \n";
                } else {
                    $content .= $key2." = \"".$elem."\"\n";
                }
            }
        }

        if (!$handle = fopen($path, 'w')) {
            return false;
        }
        if (!fwrite($handle, $content)) {
            return false;
        }
        fclose($handle);
        return true;
    }

    public function read($file_name)
    {
        $contents =  parse_ini_file($file_name, true);
        return $contents;
    }

}