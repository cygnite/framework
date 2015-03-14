<?php
use Cygnite\Foundation\Application as App;

if ( ! function_exists('clear_sanity')) {
    /*
    * $_POST   = array_map("clear_sanity", $_POST);
    * Strip html encoding out of a string, useful to prevent cross site scripting attacks
    * Use this function in view page to display values into web page
    */
    function clear_sanity($values)
    {
        $values = (is_array($values)) ?
            array_map("clear_sanity", $values) :
            htmlentities($values, ENT_QUOTES, 'UTF-8');

        return $values;
    }
}

if ( ! function_exists('days_diff')) {

    /**
     * @param $date
     * @return int
     */
    function days_diff($date)
    {
        if (!$date) {
            $date ="0000-00-00 00:00:00";
        }

        if (preg_match("/(\d+)-(\d+)-(\d+)/", $date, $f)) {
            $time_val=mktime(0, 0, 0, $f[2], $f[3], $f[1]);
        }
        $today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
        $s = $today - $time_val;
        $d = intval($s/86400);

        return $d;
    }
}

if ( ! function_exists('show')) {

    /**
     * @param array $data
     * @param bool  $hasExit
     */
    function show($data = array(), $hasExit = false)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        if ($hasExit) {
            exit;
        }
    }
}


if ( ! function_exists('string_split')) {

    /**
     * @param        $string
     * @param string $needle
     * @return array
     */
    function string_split($string, $needle = '.')
    {
        $expression = array();
        $expression = explode($needle,$string);
        return $expression;
    }

}

if ( ! function_exists('string_has')) {

    /**
     * @param $haystack
     * @param $needle
     * @return bool
     */
    function string_has($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}

if ( ! function_exists('app')) {
    /*
    * We will get the Application instance
    */
    function app($callback = null)
    {
        return Application::instance($callback);
    }
}

if ( ! function_exists('compress')) {
    /**
     * We will remove comments and empty spaces from the resource
     * and compress contents
     *
     * @param $content
     * @return mixed
     */
    function compress($content)
    {
        // We will remove comments from the string content
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        // We will remove tabs, spaces, newlines, etc. from the string
        $content = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $content);

        return $content;
    }
}
