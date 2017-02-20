<?php

namespace SypherLev\Blueprint;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;
use SypherLev\Blueprint\QueryBuilders\SourceInterface;

abstract class Blueprint
{
    protected $source;
    protected $query;
    private $patterns = [];
    private $filters = [];
    private $transforms = [];

    private $insert_records = [];
    private $set = [];

    private $activePattern = false;
    private $activeFilters = [];
    private $activeTransformations = [];

    protected function __construct(SourceInterface $source, QueryInterface $query) {
        $this->source = $source;
        $this->query = $query;
    }

    // ELEMENT METHODS

    protected function addPattern($patternName, \Closure $pattern) {
        $pattern = call_user_func($pattern);
        if(!is_a($pattern, 'SypherLev\Blueprint\Elements\Pattern')) {
            throw (new \Exception('Pattern named '.$patternName. ' could not be added; Closure does not return a valid Pattern object'));
        }
        $this->patterns[$patternName] = $pattern;
    }

    protected function withPattern($patternName) {
        if(!isset($this->patterns[$patternName])) {
            throw (new \Exception('Could not set pattern '.$patternName.': pattern not found'));
        }
        $this->activePattern = $patternName;
        return $this;
    }

    protected function addFilter($filterName, \Closure $filter) {
        $filter = call_user_func($filter);
        if(!is_a($filter, 'SypherLev\Blueprint\Elements\Filter')) {
            throw (new \Exception('Filter named '.$filterName. ' could not be added; Closure does not return a valid Filter object'));
        }
        $this->filters[$filterName] = $filter;
    }

    protected function withFilter($filterName) {
        if(!isset($this->filters[$filterName])) {
            throw (new \Exception('Could not set filter '.$filterName.': filter not found'));
        }
        $this->activeFilters[] = $filterName;
        return $this;
    }

    protected function addTransformation($transformName, \Closure $transform) {
        $this->transforms[$transformName] = $transform;
    }

    protected function withTransformation($transformName) {
        if(!isset($this->transforms[$transformName])) {
            throw (new \Exception('Could not set transformation '.$transformName.': transformation not found'));
        }
        $this->activeTransformations[] = $transformName;
        return $this;
    }

    // QUERY BUILDER METHODS

    protected function select()
    {
        $this->query->setType('SELECT');
        return $this;
    }

    protected function update()
    {
        $this->query->setType('UPDATE');
        return $this;
    }

    protected function insert()
    {
        $this->query->setType('INSERT');
        return $this;
    }

    protected function delete()
    {
        $this->query->setType('DELETE');
        return $this;
    }

    protected function one() {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->one();
        if($result && !empty($this->activeTransformations)) {
            foreach ($this->activeTransformations as $transform) {
                $result = call_user_func($this->transforms[$transform], $result);
            }
        }
        $this->reset();
        return $result;
    }

    protected function many() {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->many();
        if($result && !empty($this->activeTransformations)) {
            foreach ($result as $idx => $r) {
                foreach ($this->activeTransformations as $transform) {
                    $result[$idx] = call_user_func($this->transforms[$transform], $r);
                }
            }
        }
        $this->reset();
        return $result;
    }

    protected function execute() {
        $query = $this->loadElements();
        if(!empty($this->activeTransformations) && !empty($this->set)) {
            foreach ($this->activeTransformations as $transformation) {
                $this->set = call_user_func($this->transforms[$transformation], $this->set);
            }
        }
        if(!empty($this->activeTransformations) && !empty($this->insert_records)) {
            foreach ($this->activeTransformations as $transformation) {
                foreach ($this->insert_records as $idx => $record) {
                    $this->insert_records[$idx] = call_user_func($this->transforms[$transformation], $record);
                }
            }
        }
        if(!empty($this->set)) {
            $query->setUpdates($this->set);
        }
        if(!empty($this->insert_records)) {
            foreach ($this->insert_records as $record) {
                $query->addInsertRecord($record);
            }
        }
        $this->reset();
        $this->source->setQuery($query);
        return $this->source->execute();
    }

    protected function count() {
        $query = $this->loadElements();
        $this->source->setQuery($query);
        $result = $this->source->count();
        $this->reset();
        return $result;
    }

    protected function columns($columnname_or_columnarray) {
        $this->query->setColumns($columnname_or_columnarray);
        return $this;
    }

    protected function table($tablename) {
        $this->query->setTable($tablename);
        return $this;
    }

    protected function add(Array $record) {
        $this->insert_records[] = $record;
        return $this;
    }

    protected function set(Array $set) {
        $this->set = $set;
        return $this;
    }

    protected function limit($rows, $offset = false) {
        $this->query->setLimit($rows, $offset);
        return $this;
    }

    protected function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false) {
        if (!is_array($columnname_or_columnarray)) {
            $columnname_or_columnarray = [$columnname_or_columnarray];
        }
        $this->query->setOrderBy($columnname_or_columnarray, $order, $useAliases);
        return $this;
    }

    protected function aggregate($function, $columnName_or_columnArray, $alias = false)
    {
        $this->query->setAggregate(strtoupper($function), $columnName_or_columnArray);
        return $this;
    }

    protected function groupBy($columnname_or_columnarray) {
        if (!is_array($columnname_or_columnarray)) {
            $columnname_or_columnarray = [$columnname_or_columnarray];
        }
        $this->query->setGroupBy($columnname_or_columnarray);
        return $this;
    }

    protected function join($firsttable, $secondtable, Array $on, $type = 'inner') {
        $this->query->setJoin($firsttable, $secondtable, $on, strtoupper($type));
        return $this;
    }

    protected function where(Array $where, $innercondition = 'AND', $outercondition = 'AND') {
        $this->query->setWhere($where, strtoupper($innercondition), strtoupper($outercondition));
        return $this;
    }

    // UTILITY METHODS

    protected function getCurrentSQL() {
        $cloneQuery = clone $this->query;
        $cloneQuery = $this->loadElements($cloneQuery);
        return $cloneQuery->compile();
    }

    protected function getCurrentBindings() {
        $cloneQuery = clone $this->query;
        $cloneQuery = $this->loadElements($cloneQuery);
        if(!empty($this->set)) {
            $cloneQuery->setUpdates($this->set);
        }
        if(!empty($this->insert_records)) {
            foreach ($this->insert_records as $record) {
                $cloneQuery->addInsertRecord($record);
            }
        }
        return $cloneQuery->getBindings();
    }

    // PRIVATE METHODS

    private function loadElements($query = false) {
        if(!$query) {
            $query = $this->query;
        }
        if($this->activePattern) {
            $query = $this->patterns[$this->activePattern]->setQueryParams($query);
            if(!empty($this->activeFilters)) {
                foreach ($this->activeFilters as $filter) {
                    $query = $this->filters[$filter]->setQueryParams($query);
                }
            }
            $this->activePattern = false;
            $this->activeFilter = false;
            return $query;
        }
        else {
            return $this->query;
        }
    }

    private function reset() {
        $this->activePattern = false;
        $this->activeFilters = [];
        $this->activeTransformations = [];
        $this->insert_records = [];
        $this->set = [];
    }
}