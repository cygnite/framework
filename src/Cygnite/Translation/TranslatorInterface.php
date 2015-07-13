<?php
namespace Cygnite\Translation;

interface TranslatorInterface
{
    public function locale($lang = null);

    public function get($string, $lang = null);

    public function has($string);
}
