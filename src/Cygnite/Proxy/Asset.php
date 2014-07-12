<?php
namespace Cygnite\Proxy;

use Cygnite\Proxy\Resolver;
use Cygnite\DependencyInjection\Container;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Inflector;

/**
 * Class Asset
 *
 * @package Cygnite\Proxy
 *
 * <code>
 * use Cygnite\Proxy\Asset;
 *
 * Asset::style("path-to-your-asset");
 *
 * </code>
 *
 */
class Asset
{
   public function getResolver()
   {
       return 'cygnite.asset-manager.asset';
   }
}
