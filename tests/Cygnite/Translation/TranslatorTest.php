<?php

use Cygnite\Translation\Translator;

class TranslatorTest extends PHPUnit_Framework_TestCase
{
    public function testTranslatorInstance()
    {
        $translation = Translator::make();

        $this->assertInstanceOf('Cygnite\Translation\Translator', $translation);
    }

    public function testTranslatorClouserInstance()
    {
        $trans = Translator::make(function ($trans) {
            $trans->locale('es');
            $trans->setFallback('en');

            return $trans;
        });

        $this->assertInstanceOf('Cygnite\Translation\Translator', $trans);
        $this->assertEquals('es', $trans->locale());
        $this->assertEquals('en', $trans->getFallback());
    }

    public function testTranslatorRootDirectory()
    {
        $translation = Translator::make();

        $translation->setRootDirectory('/var/www/cygnite/')
                    ->setLangDir('src/Apps/Resources/Languages/');

        $this->assertEquals('/var/www/cygnite/', $translation->getRootDirectory());
        $this->assertEquals('src/Apps/Resources/Languages/', $translation->getLangDir());
    }

    /*
    // We need to test
    public function testLoadTranslationFiles()
    {

    }
    */
}
