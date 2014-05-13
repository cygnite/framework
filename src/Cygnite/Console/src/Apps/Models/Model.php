
namespace Apps\Models;

use Cygnite\Application;
use Cygnite\Helpers\Url;
use Cygnite\Database\Schema;
use Cygnite\Database\ActiveRecord;

class %StaticModelName% extends ActiveRecord
{

    //your database connection name
    protected $database = '%databaseName%';

    // your table name here
    //protected $tableName = '%modelName%';

    protected $primaryKey = 'id';

    public $perPage = 5;

    public function __construct()
    {
        parent::__construct();
    }

    protected function pageLimit()
    {
        return Url::segment(3);
    }
}// End of the %StaticModelName% Model