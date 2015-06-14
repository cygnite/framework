<?php
namespace Cygnite\Base\EventHandler;

use Cygnite\Helpers\Inflector;

/**
 * Class EventRegistrarTrait
 *
 * @package Cygnite\Base\EventHandler
 */
trait EventRegistrarTrait
{
    /**
     * @return $this
     */
    public function boot()
    {
        $this->registerEvents($this->listen);

        return $this;
    }

    /**
     * Get user defined application events from event middle wares
     *
     * @return array|bool
     */
    public function getAppEvents()
    {
        $class = 'Apps\Middlewares\Events\Event';

        if (property_exists($class, 'appEvents') && $class::$activateAppEvent == true) {

            return \Apps\Middlewares\Events\Event::$appEvents;
        }

        return false;
    }

    /**
     * We will register user defined events, it will trigger event when matches
     *
     * @param $events
     * @throws \RuntimeException
     */
    public function registerEvents($events)
    {
        if (empty($events)) {
            throw new \RuntimeException(sprintf("Empty argument passed %s", __FUNCTION__));
        }

        foreach ($events as $event => $namespace) {

            $parts = explode('@', $namespace);
            // attach all before and after event to handler
            $this->attach("$event.before", $parts[0].'@before'.ucfirst(Inflector::pathAction(end($parts))))
                 ->attach("$event", $parts[0].'@'.Inflector::pathAction(end($parts)))
                 ->attach("$event.after", $parts[0].'@after'.ucfirst(Inflector::pathAction(end($parts))));
        }
    }

    /**
     * Fire all events registered with EventHandler
     *
     * @param $event
     * @return $this
     */
    public function fire($event)
    {
        $this->trigger("$event.before");
        $this->trigger($event);
        $this->trigger("$event.after");

        return $this;
    }
}