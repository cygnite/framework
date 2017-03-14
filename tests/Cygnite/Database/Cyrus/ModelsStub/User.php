<?php
namespace Cygnite\Tests\Database\Cyrus\ModelsStub;

use Cygnite\Database\Cyrus\ActiveRecord;

class User extends ActiveRecord
{
    protected $database = 'foo_bar';

    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }
}
