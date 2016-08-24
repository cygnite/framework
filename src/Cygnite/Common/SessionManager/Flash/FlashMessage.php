<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\SessionManager\Flash;

use Cygnite\Common\SessionManager\Session;
use Cygnite\Helpers\Inflector;

class FlashMessage
{
    // Valid Flash Types
    private $validFlashTypes = ['help', 'info', 'success', 'error', 'warning'];
    private $class = __CLASS__;
    private $flashWrapper = "<div class='%s %s'><a href='#' class='closeFlash'></a>\n%s</div>\n";
    private $inflection;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!Session::has('flashMessages')) {
            Session::set('flashMessages', []);
        }
    }

    /**
     * Set a flash message to the queue.
     *
     * @param string $key     Flash type to set
     * @param string $message Flash Message
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function setFlash($key, $message)
    {
        if (!Session::has('flashMessages')) {
            return false;
        }

        if (!isset($key) || !isset($message[0])) {
            return false;
        }

        // Verify $key is a valid message type or not
        if (!in_array($key, $this->validFlashTypes)) {
            throw new \Exception('"'.strip_tags($key).'" is not a valid message type!');
        }

        // If the flash session array doesn't exist, make it
        if (!array_key_exists($key, Session::get('flashMessages'))) {
            Session::set('flashMessages', [$key => []]);
        }

        Session::set('flashMessages', [$key => [$message]]);

        return true;
    }

    /**
     * Get the Queued message and return it.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getFlash($key = null)
    {
        $messages = $flash = '';

        if (!Session::has('flashMessages')) {
            return false;
        }

        $flashArray = Session::get('flashMessages');

        // Check $key is valid flash type
        if (in_array($key, $this->validFlashTypes)) {
            if (isset($flashArray[$key])) {
                foreach ($flashArray[$key] as $msg) {
                    $messages .= '<p>'.$msg."</p>\n";
                }
            }

            $flash .= sprintf(
                $this->flashWrapper,
                strtolower(Inflector::getClassName($this->class)),
                $key,
                $messages
            );

            // clear the viewed messages from browser
            $this->clearViewedMessages($key);

            // Print ALL queued messages
        } elseif (is_null($key)) {
            foreach ($flashArray as $key => $msgArray) {
                $messages = '';
                foreach ($msgArray as $msg) {
                    $messages .= '<p>'.$msg."</p>\n";
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
     * Clear viewed messages from the session.
     *
     * @param string $key
     *
     * @return bool
     */
    public function clearViewedMessages($key = null)
    {
        if (is_null($key)) {
            Session::delete('flashMessages');
        } else {
            Session::delete('flashMessages');
        }

        return true;
    }

    /**
     * Check is there any error messages.
     *
     * @return bool true/false
     */
    public function hasError()
    {
        $flashArray = Session::get('flashMessages');

        return empty($flashArray['error']) ? false : true;
    }

    /**
     * Check is there any flash message in session.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasFlash($key = null)
    {
        $flashArray = Session::get('flashMessages');

        if (!is_null($key)) {
            if (isset($flashArray[$key])) {
                return $flashArray[$key];
            }
        }

        return false;
    }
}
