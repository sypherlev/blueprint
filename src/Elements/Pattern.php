<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class Pattern
{
    private $table;
    private $columns;
    private $joins = [];
    private $group = null;
    private $aggregates = [];

    public function table(string $tableName) : Pattern {
        $this->table = $tableName;
        return $this;
    }

    public function columns(array $columns) : Pattern {
        $this->columns = $columns;
        return $this;
    }

    public function join(string $firsttable, string $secondtable, array $on, string $type = 'inner') : Pattern {
        $this->joins[] = array(
            'firsttable' => $firsttable,
            'secondtable' => $secondtable,
            'on' => $on,
            'type' => $type
        );
        return $this;
    }

    public function groupBy(array $columns) : Pattern {
        $this->group = $columns;
        return $this;
    }

    public function aggregate(string $function, array $columns) : Pattern {
        $this->aggregates[] = array(
            'function' => $function,
            'columns' => $columns
        );
        return $this;
    }

    public function setQueryParams(QueryInterface $query) : QueryInterface {
        $query->setTable($this->table);
        foreach ($this->joins as $join) {
            $query->setJoin($join['firsttable'], $join['secondtable'], $join['on'], $join['type']);
        }
        foreach ($this->aggregates as $agg) {
            $query->setAggregate(strtoupper($agg['function']), $agg['columns']);
        }
        $query->setColumns($this->columns);
        if(!is_null($this->group)) {
            $query->setGroupBy($this->group);
        }
        return $query;
    }
}