<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;

class Pattern
{
    private $table;
    private $columns;
    private $joins = [];
    private $order = [];
    private $limit = [];
    private $group;

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

    public function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false) {
        $this->order = array(
            'columns' => $columnname_or_columnarray,
            'order' => $order,
            'aliases' => $useAliases
        );
        return $this;
    }

    public function limit($rows, $offset = false) {
        $this->limit = array(
            'rows' => $rows,
            'offset' => $offset
        );
        return $this;
    }

    public function groupBy($columnname_or_columnarray) {
        $this->group = $columnname_or_columnarray;
        return $this;
    }

    public function setSourceParams(SourceInterface $source) {
        $source->table($this->table);

        $source->columns($this->columns);

        foreach ($this->joins as $join) {
            $source->join($join['firsttable'], $join['secondtable'], $join['on'], $join['type']);
        }

        if(!empty($this->order)) {
            $source->orderBy($this->order['columns'], $this->order['order'], $this->order['aliases']);
        }
        if(!empty($this->limit)) {
            $source->limit($this->limit['rows'], $this->limit['offset']);
        }
        if(!empty($this->group)) {
            $source->groupBy($this->group);
        }
        return $source;
    }
}