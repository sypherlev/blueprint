<?php

namespace SypherLev\Blueprint;

use SypherLev\Blueprint\Error\BlueprintException;
use SypherLev\Blueprint\QueryBuilders\QueryInterface;
use SypherLev\Blueprint\QueryBuilders\SourceInterface;

abstract class Blueprint
{
    /* @var SourceInterface */
    protected $source;
    /* @var QueryInterface */
    protected $query;
    private $patterns = [];
    private $filters = [];
    private $transforms = [];
    private $arraytransforms = [];

    private $insert_records = [];
    private $set = [];

    private $activePattern = false;
    private $activeFilters = [];
    private $activeTransformations = [];

    protected function __construct(SourceInterface $source, QueryInterface $query)
    {
        $this->source = $source;
        $this->query = $query;
    }

    // ELEMENT METHODS

    /**
     * Add a Pattern to the available pattern list
     * Throws an Exception if the Closure $pattern does not produce a Pattern object
     *
     * @param $patternName
     * @param \Closure $pattern
     * @throws BlueprintException
     */
    protected function addPattern(string $patternName, \Closure $pattern)
    {
        $pattern = call_user_func($pattern);
        if (!is_a($pattern, 'SypherLev\Blueprint\Elements\Pattern')) {
            throw (new BlueprintException('Pattern named ' . $patternName . ' could not be added; Closure does not return a valid Pattern object'));
        }
        $this->patterns[$patternName] = $pattern;
    }

    /**
     * Sets the currently active Pattern
     * Throws an Exception if $patternName does not exist in the available list
     *
     * @param $patternName
     * @return $this
     * @throws BlueprintException
     */
    protected function withPattern(string $patternName): Blueprint
    {
        if (!isset($this->patterns[$patternName])) {
            throw (new BlueprintException('Could not set pattern ' . $patternName . ': pattern not found'));
        }
        $this->activePattern = $patternName;
        return $this;
    }

    /**
     * Add a Filter to the available filter list
     * Throws an Exception if the Closure $filter does not produce a Filter object
     *
     * @param $filterName
     * @param \Closure $filter
     * @throws \Exception
     */
    protected function addFilter(string $filterName, \Closure $filter)
    {
        $filter = call_user_func($filter);
        if (!is_a($filter, 'SypherLev\Blueprint\Elements\Filter')) {
            throw (new BlueprintException('Filter named ' . $filterName . ' could not be added; Closure does not return a valid Filter object'));
        }
        $this->filters[$filterName] = $filter;
    }

    /**
     * Activates a Filter from the currently available list
     * Throws an Exception if the $filterName does not exist in the available list
     *
     * @param $filterName
     * @return $this
     * @throws \Exception
     */
    protected function withFilter(string $filterName): Blueprint
    {
        if (!isset($this->filters[$filterName])) {
            throw (new BlueprintException('Could not set filter ' . $filterName . ': filter not found'));
        }
        $this->activeFilters[] = $filterName;
        return $this;
    }

    /**
     * Adds a new Transformation function to the available list
     * If $operateOnArray is true, the transformation will be passed an array of records;
     * otherwise it will get one record at a time
     *
     * @param $transformName
     * @param \Closure $transform
     * @param bool $operateOnArray
     */
    protected function addTransformation(string $transformName, \Closure $transform, bool $operateOnArray = false)
    {
        if ($operateOnArray) {
            $this->arraytransforms[$transformName] = $transform;
        } else {
            $this->transforms[$transformName] = $transform;
        }
    }

    /**
     * Activates a Transformation from the currently available list
     * Throws an Exception if the $transformName does not exist in the available list
     *
     * @param $transformName
     * @return $this
     * @throws \Exception
     */
    protected function withTransformation(string $transformName): Blueprint
    {
        if (!isset($this->transforms[$transformName]) && !isset($this->arraytransforms[$transformName])) {
            throw (new \Exception('Could not set transformation ' . $transformName . ': transformation not found'));
        }
        $this->activeTransformations[] = $transformName;
        return $this;
    }

    // QUERY BUILDER METHODS

    // set the current query type
    // call one of these first.
    protected function select(): Blueprint
    {
        $this->query->setType('SELECT');
        return $this;
    }

    protected function update(): Blueprint
    {
        $this->query->setType('UPDATE');
        return $this;
    }

    protected function insert(): Blueprint
    {
        $this->query->setType('INSERT');
        return $this;
    }

    protected function delete(): Blueprint
    {
        $this->query->setType('DELETE');
        return $this;
    }

    // set the required termination. SELECT = one|many|count, UPDATE|INSERT|DELETE = execute
    // call one of these last
    protected function one()
    {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->one();
        $activeTransformations = $this->activeTransformations;
        $this->reset();
        if (!empty($activeTransformations)) {
            foreach ($activeTransformations as $transform) {
                if (isset($this->transforms[$transform])) {
                    $result = call_user_func($this->transforms[$transform], $result);
                }
                if (isset($this->arraytransforms[$transform])) {
                    $temp = call_user_func($this->arraytransforms[$transform], [$result]);
                    $result = array_shift($temp);
                }
            }
        }
        $this->reset();
        return $result;
    }

    protected function many()
    {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->many();
        $activeTransformations = $this->activeTransformations;
        $this->reset();
        if ($result && !empty($activeTransformations)) {
            foreach ($activeTransformations as $transform) {
                if (isset($this->transforms[$transform])) {
                    foreach ($result as $idx => $r) {
                        $result[$idx] = call_user_func($this->transforms[$transform], $r);
                    }
                }
                if (isset($this->arraytransforms[$transform])) {
                    $result = call_user_func($this->arraytransforms[$transform], $result);
                }
            }
        }
        $this->reset();
        return $result;
    }

    protected function execute()
    {
        $query = $this->loadElements();
        if (!empty($this->activeTransformations) && !empty($this->set)) {
            foreach ($this->activeTransformations as $transformation) {
                if (isset($this->transforms[$transformation])) {
                    $this->set = call_user_func($this->transforms[$transformation], $this->set);
                }
                if (isset($this->arraytransforms[$transformation])) {
                    $temp = $this->set = call_user_func($this->arraytransforms[$transformation], [$this->set]);
                    $this->set = $temp[0];
                }
            }
        }
        if (!empty($this->activeTransformations) && !empty($this->insert_records)) {
            foreach ($this->activeTransformations as $transformation) {
                if (isset($this->transforms[$transformation])) {
                    foreach ($this->insert_records as $idx => $record) {
                        $this->insert_records[$idx] = call_user_func($this->transforms[$transformation], $record);
                    }
                }
                if (isset($this->arraytransforms[$transformation])) {
                    $this->insert_records = call_user_func($this->arraytransforms[$transformation], $this->insert_records);
                }
            }
        }
        if (!empty($this->set)) {
            $query->setUpdates($this->set);
        }
        if (!empty($this->insert_records)) {
            foreach ($this->insert_records as $record) {
                $query->addInsertRecord($record);
            }
        }
        $this->reset();
        $this->source->setQuery($query);
        $result = $this->source->execute();
        $this->reset();
        return $result;
    }

    protected function count()
    {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->count();
        $this->reset();
        return $result;
    }

    // available chain methods

    /**
     * Set the primary table on the query
     * HIGHLY RECOMMENDED to set this just after the type before any other chain methods
     *
     * @param string $tablename
     * @return $this
     */
    protected function table(string $tablename) : Blueprint
    {
        $this->query->setTable($tablename);
        return $this;
    }

    /**
     * Selects columns to attach to the query
     *
     * @param $columnName_or_columnArray - has four possible types
     *     array($columnone, $columntwo, ...)
     *     array($alias => $column, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     *     array($tableone => array($aliasone => $columnone, $aliastwo => $columntwo,  ...), $tabletwo => array(...) ...)
     * @return $this
     */
    protected function columns(array $columnarray)
    {
        $this->query->setColumns($columnarray);
        return $this;
    }

    /**
     * Used to add records for INSERT statements
     * Use this in a loop to add a batch of records
     *
     * @param array $record - array('column' => $variable, ... )
     * @return $this
     */
    protected function add(array $record)
    {
        $this->insert_records[] = $record;
        return $this;
    }

    /**
     * Used only with UPDATE - set the array of columns to update
     *
     * @param array $updates - array('column' => $variable, ... )
     * @return $this
     */
    protected function set(array $set)
    {
        $this->set = $set;
        return $this;
    }

    /**
     * Sets the LIMIT for the current query.
     *
     * @param int $rows
     * @param int $offset
     * @return $this
     */
    protected function limit(int $rows, int $offset = 0)
    {
        $this->query->setLimit($rows, $offset);
        return $this;
    }

    /**
     * Sets the order for the query
     *
     * @param $columns - has two possible types:
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     * @param $direction - must be one of 'ASC' or 'DESC'
     * @param $aliases - whether aliases are being used or not
     * @return $this
     */
    protected function orderBy(array $columns, $order = 'ASC', $useAliases = false)
    {
        $this->query->setOrderBy($columns, $order, $useAliases);
        return $this;
    }

    /**
     * Sets one or more aggregate clauses in the current query, ex. SUM(columnone), SUM(column2)
     * Use multiple columns for quick and dirty data retrieval.
     * $function must ne one of SUM|COUNT|AVG|MIN|MAX
     *
     * @param $function - ex. SUM - applied to all selected columns
     * @param $columns - has four possible types
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     *     array($tableone => array($alias => $columnone, $alias => $columntwo,  ...), $tabletwo => array(...), ...)
     * @return $this
     */
    protected function aggregate($function, $columns)
    {
        $this->query->setAggregate(strtoupper($function), $columns);
        return $this;
    }

    /**
     * Sets the groupby clause
     *
     * @param $columns - has two possible types:
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     * @return $this
     */
    protected function groupBy($columns)
    {
        $this->query->setGroupBy($columns);
        return $this;
    }

    /**
     * Adds a JOIN clause
     *
     * @param $firsttable - tablename
     * @param $secondtable - tablename
     * @param array $on - must be in the format array('firsttablecolumn' => 'secondtablecolumn, ...)
     * @param string $type - inner|full|left|right
     * @return $this
     */
    protected function join($firsttable, $secondtable, array $on, $type = 'inner')
    {
        $this->query->setJoin($firsttable, $secondtable, $on, strtoupper($type));
        return $this;
    }

    /**
     * Adds a WHERE clause
     * Column names can use the format 'columnname operand' to use operands other than '=', e.g. 'id >'
     * Valid operands: >|<|>=|<=|like|in
     * If the tablename is not specified in the $where array parameter, $this->currentquery->table will be used
     * instead
     * Using the IN operand will make the param be treated as an array.
     * Setting the param to NULL will force the operand to IS.
     *
     * @param array $where - has three possible types:
     *     array($column => $param, ...)
     *     array($tableone => array($column => $param, ...), $tabletwo => array($column => $param, ...), ...)
     * @param string $innercondition - AND|OR - used between clauses in the WHERE statement
     * @param string $outercondition - AND|OR - used to append this WHERE statement to the query
     * @return $this
     */
    protected function where(array $where, $innercondition = 'AND', $outercondition = 'AND')
    {
        $this->query->setWhere($where, strtoupper($innercondition), strtoupper($outercondition));
        return $this;
    }

    // UTILITY METHODS

    /**
     * Attempts to compile the current query, including all Patterns and Filters
     * Excludes Transformations
     *
     * @return string
     */
    protected function getCurrentSQL()
    {
        $cloneQuery = clone $this->query;
        $cloneQuery = $this->loadElements($cloneQuery);
        return $cloneQuery->compile();
    }

    /**
     * Attempts to compile the current query and returns the resulting bindings
     *
     * @return array
     */
    protected function getCurrentBindings()
    {
        $cloneQuery = clone $this->query;
        $cloneQuery = $this->loadElements($cloneQuery);
        if (!empty($this->set)) {
            $cloneQuery->setUpdates($this->set);
        }
        if (!empty($this->insert_records)) {
            foreach ($this->insert_records as $record) {
                $cloneQuery->addInsertRecord($record);
            }
        }
        return $cloneQuery->getBindings();
    }

    /**
     * Start recording queries, bindings and error output as they are executed
     */
    protected function record()
    {
        $this->source->startRecording();
    }

    /**
     *  Stop recording queries, bindings and error output as they are executed
     */
    protected function stop()
    {
        $this->source->stopRecording();
    }

    /**
     * Returns an array of SQL queries, their associated bindings, and the result of the query execution
     *
     * @return array $output
     */
    protected function output()
    {
        return $this->source->getRecordedOutput();
    }

    protected function getTableColumns($tableName)
    {
        return $this->source->getTableColumns($tableName);
    }

    protected function getPrimaryKey($tableName)
    {
        return $this->source->getPrimaryKey($tableName);
    }

    /**
     * Add either a table or an array of tables to the current whitelist
     *
     * @param array $tables
     */
    protected function whitelistTable(array $tables)
    {
        $this->query->addToTableWhitelist($tables);
    }

    /**
     * Add either a column or an array of columns to the current whitelist
     *
     * @param array $columns
     */
    protected function whitelistColumn($columns)
    {
        $this->query->addToColumnWhitelist($columns);
    }

    /**
     * Reset the query and source; prep for another pass
     *
     */
    protected function reset()
    {
        $this->activePattern = false;
        $this->activeFilters = [];
        $this->activeTransformations = [];
        $this->insert_records = [];
        $this->set = [];
        $queryclass = get_class($this->query);
        $this->query = new $queryclass();
    }

    // PRIVATE METHODS

    private function loadElements(QueryInterface $query = null) : QueryInterface
    {
        if (is_null($query)) {
            $query = $this->query;
        }
        if ($this->activePattern) {
            $query = $this->patterns[$this->activePattern]->setQueryParams($query);
            if (!empty($this->activeFilters)) {
                foreach ($this->activeFilters as $filter) {
                    $query = $this->filters[$filter]->setQueryParams($query);
                }
            }
            $this->activePattern = false;
            $this->activeFilters = [];
            return $query;
        } else {
            return $this->query;
        }
    }
}