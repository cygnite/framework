<?php

namespace Cygnite\Database\Cyrus\Relations;

class HasManyThough
{
    protected $baseClass;

    protected $foreignClass;

    protected $mapClass;

    protected $localId;

    protected $foreignId;

    protected $firstKey;

    protected $secondKey;

    /**
     * @param      $class
     * @param      $this->foreignClass
     * @param null $mapClass
     * @param null $localId
     * @param null $foreignId
     * @param null $firstKey
     * @param null $secondKey
     */
    public function __construct(
        $ar,
        $foreignClass,
        $mapClass = null,
        $localId = null,
        $foreignId = null,
        $firstKey = null,
        $secondKey = null
    ) {
        $this->baseClass = get_class($ar);
        $this->foreignClass = $foreignClass;
        $this->mapClass = $mapClass;
        $this->localId = $localId;
        $this->foreignId = $foreignId;
        $this->firstKey = $firstKey;
        $this->secondKey = $secondKey;
    }

    public function match()
    {
        if (is_null($this->mapClass)) {
            $this->mapClass = $this->getJoinClassName($this->baseClass, $this->foreignClass);
        }

        // Get table names from each model class
        $classes = [$this->baseClass, $this->foreignClass, $this->mapClass];
        list($baseTable, $associatedTable, $joinTable) = $this->filterTableNameFromClass($classes);

        // Get baseTableId & associatedTableId from the given input
        $localId = (is_null($this->firstKey)) ? $this->getIdColumn($this->baseClass) : $this->firstKey;
        $foreignId = (is_null($this->secondKey)) ? $this->getIdColumn($this->foreignClass) : $this->secondKey;

        // Get the mappingId and associatedId for joining table
        $mappingId = $this->buildForeignKeyName($localId, $baseTable);
        $foreignId = $this->buildForeignKeyName($foreignId, $associatedTable);

        return (new $this->foreignClass())
            ->select("{$associatedTable}.*")
            ->innerJoin($joinTable, [
                    "{$associatedTable}.{$foreignId}",
                    '=',
                    "{$joinTable}.{$foreignId}", ]
            )->where("{$joinTable}.{$mappingId}", '=', $this->$localId);
    }
}
