<?php

namespace SypherLev\Blueprint;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;

abstract class Blueprint
{
    protected $source;
    private $patterns = [];
    private $filters = [];
    private $transforms = [];
    private $insert_records = [];
    private $set = [];

    private $activePattern = false;

    private $activeFilters = [];
    private $activeTransformations = [];

    protected function __construct(SourceInterface $source) {
        $this->source = $source;
    }

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
            throw (new \Exception('Could not set filter '.$transformName.': filter not found'));
        }
        $this->activeTransformations[] = $transformName;
        return $this;
    }

    protected function select()
    {
        $this->source->select();
        return $this;
    }

    protected function update()
    {
        $this->source->update();
        return $this;
    }

    protected function insert()
    {
        $this->source->insert();
        return $this;
    }

    protected function delete()
    {
        $this->source->delete();
        return $this;
    }

    protected function one() {
        $source = $this->loadElements();
        $result = $source->one();
        if($result && !empty($this->activeTransformations)) {
            foreach ($this->activeTransformations as $transform) {
                $result = call_user_func($transform, $result);
            }
        }
        $this->reset();
        return $result;
    }

    protected function many() {
        $source = $this->loadElements();
        $result = $source->many();
        if($result && !empty($this->activeTransformations)) {
            foreach ($result as $idx => $r) {
                foreach ($this->activeTransformations as $transform) {
                    $result[$idx] = call_user_func($transform, $r);
                }
            }
        }
        $this->reset();
        return $result;
    }

    protected function execute() {
        $source = $this->loadElements();
        if(!empty($this->transforms) && !empty($this->set)) {
            foreach ($this->transforms as $transformation) {
                $this->set = call_user_func($transformation, $this->set);
            }
        }
        if(!empty($this->transforms) && !empty($this->insert_records)) {
            foreach ($this->transforms as $transformation) {
                foreach ($this->insert_records as $idx => $record) {
                    $this->insert_records[$idx] = call_user_func($transformation, $record);
                }
            }
        }
        if(!empty($this->set)) {
            $source->set($this->set);
        }
        if(!empty($this->insert_records)) {
            foreach ($this->insert_records as $record) {
                $source->add($record);
            }
        }
        $this->reset();
        return $source->execute();
    }

    protected function count() {
        $source = $this->loadElements();
        $result = $source->count();
        if($result && !empty($this->activeTransformations)) {
            foreach ($this->activeTransformations as $transform) {
                $result = call_user_func($transform, $result);
            }
        }
        $this->reset();
        return $result;
    }

    protected function columns($columnname_or_columnarray) {
        $this->source->columns($columnname_or_columnarray);
        return $this;
    }

    protected function table($tablename) {
        $this->source->table($tablename);
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
        $this->source->limit($rows, $offset);
        return $this;
    }

    protected function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false) {
        $this->source->orderBy($columnname_or_columnarray, $order, $useAliases);
        return $this;
    }

    protected function groupBy($columnname_or_columnarray) {
        $this->source->groupBy($columnname_or_columnarray);
        return $this;
    }

    protected function join($firsttable, $secondtable, Array $on, $type = 'inner') {
        $this->source->join($firsttable, $secondtable, $on, $type);
        return $this;
    }

    protected function where(Array $where, $innercondition = 'AND', $outercondition = 'AND') {
        $this->source->where($where, $innercondition, $outercondition);
        return $this;
    }

    private function loadElements() {
        if($this->activePattern) {
            $source = $this->patterns[$this->activePattern]->setSourceParams($this->source);
            if(!empty($this->activeFilters)) {
                foreach ($this->activeFilters as $filter) {
                    $source = $this->filters[$filter]->setSourceParams($source);
                }
            }
            $this->activePattern = false;
            $this->activeFilter = false;
            return $source;
        }
        else {
            return $this->source;
        }
    }

    private function reset() {
        $this->activePattern = false;
        $this->activeFilters = [];
        $this->activeTransformations = [];
    }
}