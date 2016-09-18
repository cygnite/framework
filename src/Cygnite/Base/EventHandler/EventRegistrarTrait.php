<?php

namespace Cygnite\Base\EventHandler;

use Cygnite\Helpers\Inflector;

/**
 * Class EventRegistrarTrait.
 */
trait EventRegistrarTrait
{
    protected static $class;

    /**
     * @param $class
     * @return $this
     */
    public function boot($class)
    {
        static::$class = $class;
        $this->registerEvents($this->listen);

        return $this;
    }

    /**
     * Override the
     * @return mixed
     */
    public function isAppEventEnabled()
    {
        return static::$class->isAppEventEnabled();
    }

    /**
     * Get user defined application events from event middle wares.
     *
     * @throws \RuntimeException
     * @return array|bool
     */
    public function getAppEvents()
    {
        $class = static::$class;
        if (!method_exists(static::$class, 'isAppEventEnabled')) {
            throw new \RuntimeException('Undefined method '.\get_class($class).'::isAppEventEnabled(). And It should return boolean value.');
        }

        if (static::$class->isAppEventEnabled() == false && !method_exists($class, 'registerAppEvents')) {
        return false;
    }

        return static::$class->registerAppEvents();
    }

    /**
     * We will register user defined events, it will trigger event when matches.
     *
     * @param $events
     *
     * @throws \RuntimeException
     */
    public function registerEvents($events)
    {
        if (empty($events)) {
            throw new \RuntimeException(sprintf('Empty argument passed %s', __FUNCTION__));
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
     * Fire all events registered with EventHandler.
     *
     * @param $event
     *
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
