<?php
namespace Cygnite\Common;

class SessionDatabase
{
    public function __construct()
    {

    }

    public function sessionSaveHandler()
    {
        @session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc_session')
        );

    }

    public function set()
    {

    }

    public function get()
    {

    }

    public function has()
    {

    }

    public function delete()
    {

    }

}