<?php
namespace Cygnite\Database\Service;

use Illuminate\Database\Eloquent\Model;

/**
 * If you want to use Eloquent ORM register EloquentServiceProvider
 * as Service and extend \Cygnite\Database\Service\Eloquent in your
 * model class
 *
 * <code>
 * class User extends \Cygnite\Database\Service\Eloquent
 * {
 *
 * }
 * </code>
 *
 * Class Eloquent
 *
 * @package Cygnite\Database\Service\Eloquent
 */
class Eloquent extends Model
{

}
