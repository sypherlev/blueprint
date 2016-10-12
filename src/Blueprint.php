<?php

namespace SypherLev\Blueprint;

use SypherLev\Blueprint\Elements\Pattern;
use SypherLev\Blueprint\QueryBuilders\SourceInterface;

class Blueprint
{
    protected $source;
    private $patterns = [];
    private $filters = [];
    private $transforms = [];
    private $insert_records = [];
    private $set = [];

    /* @var Pattern */
    private $activePattern = false;

    private $activeFilters = [];
    private $activeTransformations = [];

    public function __construct(SourceInterface $source) {
        $this->source = $source;
    }

    public function addPattern($patternName, \Closure $pattern) {
        $pattern = call_user_func($pattern);
        if(!is_a($pattern, 'SypherLev\Blueprint\Elements\Pattern')) {
            throw (new \Exception('Pattern named '.$patternName. ' could not be added; Closure does not return a valid Pattern object'));
        }
        $this->patterns[$patternName] = $pattern;
    }

    public function withPattern($patternName) {
        if(!isset($this->patterns[$patternName])) {
            throw (new \Exception('Could not set pattern '.$patternName.': pattern not found'));
        }
        $this->activePattern = $patternName;
        return $this;
    }

    public function addFilter($filterName, \Closure $filter) {
        $filter = call_user_func($filter);
        if(!is_a($filter, 'SypherLev\Blueprint\Elements\Filter')) {
            throw (new \Exception('Filter named '.$filterName. ' could not be added; Closure does not return a valid Filter object'));
        }
        $this->filters[$filterName] = $filter;
    }

    public function withFilter($filterName) {
        if(!isset($this->filters[$filterName])) {
            throw (new \Exception('Could not set filter '.$filterName.': filter not found'));
        }
        $this->activeFilters[] = $filterName;
        return $this;
    }

    public function addTransformation($transformName, \Closure $transform) {
        $this->transforms[$transformName] = $transform;
    }

    public function withTransformation($transformName) {
        if(!isset($this->transforms[$transformName])) {
            throw (new \Exception('Could not set filter '.$transformName.': filter not found'));
        }
        $this->activeTransformations[] = $transformName;
        return $this;
    }

    public function select()
    {
        $this->source->select();
        return $this;
    }

    public function update()
    {
        $this->source->update();
        return $this;
    }

    public function insert()
    {
        $this->source->insert();
        return $this;
    }

    public function delete()
    {
        $this->source->delete();
        return $this;
    }

    public function one() {
        $source = $this->loadElements();
        $result = $source->one();
        if($result && !empty($this->activeTransformations)) {
            foreach ($this->activeTransformations as $transform) {
                $result = call_user_func($transform, $result);
            }
        }
        return $result;
    }

    public function many() {
        $source = $this->loadElements();
        $result = $source->many();
        if($result && !empty($this->activeTransformations)) {
            foreach ($result as $idx => $r) {
                foreach ($this->activeTransformations as $transform) {
                    $result[$idx] = call_user_func($transform, $r);
                }
            }
        }
        return $result;
    }

    public function execute() {
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
        $source->set($this->set);
        return $source->execute();
    }

    public function columns($columnname_or_columnarray) {
        $this->source->columns($columnname_or_columnarray);
        return $this;
    }

    public function table($tablename) {
        $this->source->table($tablename);
        return $this;
    }

    public function add(Array $record) {
        $this->insert_records[] = $record;
        return $this;
    }

    public function set(Array $set) {
        $this->set = $set;
        return $this;
    }

    public function limit($rows, $offset = false) {
        $this->source->limit($rows, $offset);
        return $this;
    }

    public function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false) {
        $this->source->orderBy($columnname_or_columnarray, $order, $useAliases);
        return $this;
    }

    public function groupBy($columnname_or_columnarray) {
        $this->source->groupBy($columnname_or_columnarray);
        return $this;
    }

    public function join($firsttable, $secondtable, Array $on, $type = 'inner') {
        $this->source->join($firsttable, $secondtable, $on, $type);
        return $this;
    }

    public function where(Array $where, $innercondition = 'AND', $outercondition = 'AND') {
        $this->source->where($where, $innercondition, $outercondition);
        return $this;
    }

    private function loadElements() {
        if(!$this->activePattern) {
            throw (new \Exception('Could not start database interaction: pattern not set'));
        }
        $source = $this->activePattern->setSourceParams($this->source);
        if(!empty($this->activeFilters)) {
            foreach ($this->activeFilters as $filter) {
                $source = $filter->setSourceParams($source);
            }
        }
        $this->activePattern = false;
        $this->activeFilter = false;
        $this->activeContext = false;
        return $source;
    }
}