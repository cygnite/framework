<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\EventHandler;

use Cygnite\Helpers\Inflector;

/**
 * Class EventRegistrarTrait.
 */
trait EventRegistrarTrait
{
    /**
     * Register event listeners.
     *
     * @param array $listen
     * @return $this
     */
    public function boot(array $listen)
    {
        $this->registerEvents($listen);

        return $this;
    }

    /**
     * We will register user defined events, it will trigger event when matches.
     *
     * @param $events
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
            $this->register("$event.before", $parts[0].'@before'.ucfirst(Inflector::pathAction(end($parts))))
                 ->register("$event", $parts[0].'@'.Inflector::pathAction(end($parts)))
                 ->register("$event.after", $parts[0].'@after'.ucfirst(Inflector::pathAction(end($parts))));
        }
    }

    /**
     * Fire all events registered with EventHandler.
     *
     * @param $event
     * @return $this
     */
    public function fire($event)
    {
        $this->dispatch("$event.before");
        $this->dispatch($event);
        $this->dispatch("$event.after");

        return $this;
    }
}
