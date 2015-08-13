<?php
use Cygnite\Foundation\Application as App;
use Cygnite\AssetManager\Html;
use Cygnite\Translation\Translator;
use Cygnite\Foundation\Http\CsrfValidator;
use Cygnite\Common\SessionManager\Session;

if (! function_exists('clear_sanity')) {
    /*
    * $_POST   = array_map("clear_sanity", $_POST);
    * Strip html encoding out of a string, useful to prevent cross site scripting attacks
    * Use this function in view page to display values into web page
    */
    function clear_sanity($values)
    {
        $values = (is_array($values)) ?
            array_map("clear_sanity", $values) :
            Html::santize($values);

        return $values;
    }
}

if (! function_exists('days_diff')) {
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

if (! function_exists('show')) {
    /**
     * @param array $data
     * @param bool  $hasExit
     */
    function show($data = [], $hasExit = false)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        if ($hasExit) {
            exit;
        }
    }
}


if (! function_exists('string_split')) {
    /**
     * @param        $string
     * @param string $needle
     * @return array
     */
    function string_split($string, $needle = '.')
    {
        $expression = [];
        $expression = explode($needle, $string);
        return $expression;
    }
}

if (! function_exists('string_has')) {
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

if (! function_exists('app')) {
    /*
    * We will get the Application instance
    */
    function app($callback = null)
    {
        return App::instance($callback);
    }
}

if (! function_exists('compress')) {
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

if (! function_exists('isCli')) {
    /**
     * Check if code is running via command line interface or web
     * @return bool
     */
    function isCli()
    {
        return (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) ? true : false;
    }
}

if (! function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     *
     * @param  string  $value
     * @return string
     */
    function e($value)
    {
        return Html::entities($value);
    }
}

if (!function_exists('trans')) {
    /**
     * trans('Hello, :user', array(':user' => $username));
     *
     * The target language is defined by [Translator::$locale].
     *
     * @uses     Translator::get()
     * @param         $key
     * @param   array $replace values to replace in the translated text
     * @param string  $locale
     * @internal param string $string text to translate
     * @internal param string $lang source language
     * @return  string
     */
    function trans($key, array $replace = null, $locale = 'en-us')
    {
        return Translator::make(function ($trans) use ($key, $replace, $locale) {
            if ($locale !== $trans->locale()) {
                // The message and target languages are different
                // Get the translation for this message
                $key = $trans->get($key);
            }

            return empty($replace) ? $key : strtr($key, $replace);
        });
    }
}

if (! function_exists('toPath')) {
    /**
     * We will replace dot / slash(/) to directory separator
     *
     * @param $string
     * @return string
     */
    function toPath($string)
    {
        switch ($string) {
            case string_has($string, '.'):
                $string = str_replace('.', DS, $string);
                break;
            case string_has($string, '/'):
                $string = str_replace('/', DS, $string);
                break;
        }

        return $string;
    }
}

if (! function_exists('csrf')) {

    /**
     * Get Csrfvalidator instance
     *
     * @return static
     */
    function csrf()
    {
        $session = Session::make()->factory();
        return CsrfValidator::make($session);
    }
}

if (! function_exists('csrf_token')) {
    /**
     * We will get csrf token generated by CsrfValidator
     *
     * @return string
     */
    function csrf_token()
    {
        return csrf()->token();
    }
}

if (! function_exists('validate_token')) {

    /**
     * Validate csrf token
     *
     * @param $token
     * @return mixed
     */
    function validate_token($token)
    {
        return csrf()->validateRequestToken($token);
    }
}
