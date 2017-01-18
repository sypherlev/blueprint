<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class Pattern
{
    private $table;
    private $columns;
    private $joins = [];
    private $group;
    private $aggregates = [];

    public function table($tableName) {
        $this->table = $tableName;
        return $this;
    }

    public function columns($columns) {
        $this->columns = $columns;
        return $this;
    }

    public function join($firsttable, $secondtable, Array $on, $type = 'inner') {
        $this->joins[] = array(
            'firsttable' => $firsttable,
            'secondtable' => $secondtable,
            'on' => $on,
            'type' => $type
        );
        return $this;
    }

    public function groupBy($columnname_or_columnarray) {
        $this->group = $columnname_or_columnarray;
        return $this;
    }

    public function aggregate($function, $columnName_or_columnArray, $alias = false) {
        $this->aggregates = array(
            'function' => $function,
            'columns' => $columnName_or_columnArray,
            'alias' => $alias
        );
    }

    public function setQueryParams(QueryInterface $query) {
        $query->setTable($this->table);
        foreach ($this->joins as $join) {
            $query->setJoin($join['firsttable'], $join['secondtable'], $join['on'], $join['type']);
        }
        foreach ($this->aggregates as $agg) {
            $query->setAggregate($agg['function'], $agg['columns'], $agg['alias']);
        }
        $query->setColumns($this->columns);
        $query->setGroupBy($this->group);
        return $query;
    }
}