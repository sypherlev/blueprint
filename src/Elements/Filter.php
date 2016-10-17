<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;

class Filter
{
    private $wheres = [];
    private $joins = [];

    public function where(Array $where, $innercondition = 'AND', $outercondition = 'AND') {
        $this->wheres[] = array(
            'where' => $where,
            'inner' => $innercondition,
            'outer' => $outercondition
        );
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
        foreach ($this->joins as $join) {
            $source->join($join['firsttable'], $join['secondtable'], $join['on'], $join['type']);
        }
        foreach ($this->wheres as $where) {
            $source->where($where['where'], $where['inner'], $where['outer']);
        }
        return $source;
    }
}