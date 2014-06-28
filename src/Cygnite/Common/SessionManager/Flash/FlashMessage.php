<?php
namespace Cygnite\Common\SessionManager\Flash;

use Cygnite\Common\Encrypt;
use Cygnite\Helpers\Inflector;
use Cygnite\Common\SessionManager;
use Cygnite\Common\SessionManager\Session;

class FlashMessage
{
    // Valid Flash Types
    private $validFlashTypes = array('help', 'info', 'success', 'error', 'warning');

    private $class = __CLASS__;

    private $flashWrapper = "<div class='%s %s'><a href='#' class='closeFlash'></a>\n%s</div>\n";

    private $inflection;

    /**
     * Constructor
     *
     * @param Inflector $inflection
     */
    public function __construct(Inflector $inflection)
    {
        // Check whether $inflection is instance of Inflector
        if ($inflection instanceof Inflector) {
            $this->inflection = $inflection;
        }

        if( !session_id() ) session_start();

        if (!isset($_SESSION['flashMessages'])) {
            $_SESSION['flashMessages'] = array();
        }
    }

    /**
     * Set a flash message to the queue
     *
     * @param  string $key      Flash type to set
     * @param  string $message  Flash Message
     * @throws \Exception
     * @return  bool
     */
    public function setFlash($key, $message)
    {
        if (!isset($_SESSION['flashMessages'])) {
            return false;
        }

        if (!isset($key) || !isset($message[0])) {
            return false;
        }

        // Verify $key is a valid message type or not
        if (!in_array($key, $this->validFlashTypes)) {
            throw new \Exception('"' . strip_tags($key) . '" is not a valid message type!');
        }

        // If the flash session array doesn't exist, make it
        if (!array_key_exists($key, $_SESSION['flashMessages'])) {
            $_SESSION['flashMessages'][$key] = array();
        }

        $_SESSION['flashMessages'][$key][] = $message;

        return true;
    }

    /**
     * Get the Queued message and return it
     *
     * @param  string $key
     * @return mixed
     *
     */
    public function getFlash($key = null)
    {
        $messages = $flash = '';

        if (!isset($_SESSION['flashMessages'])) {
            return false;
        }

        // Check $key is valid flash type
        if (in_array($key, $this->validFlashTypes)) {

            if (isset($_SESSION['flashMessages'][$key])) {

                foreach ($_SESSION['flashMessages'][$key] as $msg) {
                    $messages .= '<p>' . $msg . "</p>\n";
                }
            }

            $flash .= sprintf(
                $this->flashWrapper,
                strtolower($this->inflection->getClassName($this->class)),
                $key,
                $messages
            );

            // clear the viewed messages from browser
            $this->clearViewedMessages($key);

            // Print ALL queued messages
        } elseif (is_null($key)) {

            foreach ($_SESSION['flashMessages'] as $key => $msgArray) {
                $messages = '';
                foreach ($msgArray as $msg) {
                    $messages .= '<p>' . $msg . "</p>\n";
                }

                $flash .= sprintf($this->flashWrapper, strtolower($this->class), $key, $messages);
            }

            // clear already viewed messages
            $this->clearViewedMessages();

        // Invalid message type
        } else {
            return false;
        }

        return $flash;
    }


    /**
     * Check is there any error messages
     *
     * @return bool  true/false
     */
    public function hasError()
    {
        return empty($_SESSION['flashMessages']['error']) ? false : true;
    }

    /**
     * Check is there any flash message in session
     *
     * @param  string $key
     * @return bool
     *
     */
    public function hasFlash($key = null)
    {
        if (!is_null($key)) {
            if (!empty($_SESSION['flashMessages'][$key])) {
                return $_SESSION['flashMessages'][$key];
            }
        }

        return false;
    }

    /**
     * Clear viewed messages from the session
     *
     * @param  string $key
     * @return bool
     *
     */
    public function clearViewedMessages($key = null)
    {
        if (is_null($key)) {
            unset($_SESSION['flashMessages']);
        } else {
            unset($_SESSION['flashMessages'][$key]);
        }
        return true;
    }
}