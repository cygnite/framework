<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Services\Omnipay;

use Cygnite\Helpers\Config;
use Omnipay\Common\CreditCard;
use Omnipay\Common\GatewayFactory;
use Cygnite\Foundation\Application;

/**
 * Class Omnipay
 *
 * @package Cygnite\Services\Omnipay
 */
class GatewayManager
{
    protected $factory;

    protected $config;

    /**
     * The array of resolved queue connections.
     *
     * @var array
     */
    protected $gateways = [];

    public function __construct(Application $app, GatewayFactory $factory, $config = [])
    {
        $this->app = $app;
        $this->factory = $factory;
        $this->config = $config;
    }

    /**
     * Gateway factory return the gateway instance
     *
     * @param null $class
     * @return mixed
     */
    public function gateway($class = null)
    {
        $class = $class ?: $this->getDefaultGateway();

        if (!isset($this->gateways[$class])) {
            $gateway = $this->factory->create($class);
            $gateway->initialize($this->getConfig($class));
            $this->gateways[$class] = $gateway;
        }

        return $this->gateways[$class];
    }

    /**
     * Returns Gateways
     *
     * @return array
     */
    public function getGateways()
    {
        return $this->gateways;
    }

    /**
     * Get the config item
     *
     * @param $name
     * @return mixed
     */
    protected function getConfig($name)
    {
        return $this->config["gateways"][$name];
    }

    /**
     * Return Omnipay\Common\CreditCard instance
     *
     * @param $input
     * @return CreditCard
     */
    public function creditCard($input)
    {
        return new CreditCard($input);
    }

    /**
     * Set the payment gateway
     *
     * @param $name
     * @return $this
     */
    public function setGateway($name)
    {
        $this->gateway = $name;

        return $this;
    }

    /**
     * Get the Gateway
     *
     * @return string
     */
    public function getGateway()
    {
        return (!isset($this->gateway)) ?
            $this->gateway = $this->getDefaultGateway() :
            $this->gateway;
    }

    /**
     * Get the default gateway
     *
     * @return string
     */
    public function getDefaultGateway()
    {
        return $this->config['default'];
    }

    /**
     * Try Calling gateway method if user tries to access method
     * which is not available in the class GatewayManager
     *
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->gateway(), $method)) {
            return call_user_func_array([$this->gateway(), $method], $parameters);
        }

        throw new \BadMethodCallException("Method [$method] is not supported by the gateway [$this->gateway].");
    }
}
