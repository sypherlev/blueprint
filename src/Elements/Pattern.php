<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;

class Pattern
{
    private $table;
    private $columns;
    private $joins = [];

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

    public function setSourceParams(SourceInterface $source) {
        $source->table($this->table);
        foreach ($this->joins as $join) {
            $source->join($join['firsttable'], $join['secondtable'], $join['on'], $join['type']);
        }
        $source->columns($this->columns);
        return $source;
    }
}