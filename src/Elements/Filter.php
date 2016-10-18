<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;

class Filter
{
    private $wheres = [];
    private $order = [];
    private $limit = [];
    private $group;

    public function where(Array $where, $innercondition = 'AND', $outercondition = 'AND') {
        $this->wheres[] = array(
            'where' => $where,
            'inner' => $innercondition,
            'outer' => $outercondition
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
        if(!empty($this->order)) {
            $source->orderBy($this->order['columns'], $this->order['order'], $this->order['aliases']);
        }
        if(!empty($this->limit)) {
            $source->limit($this->limit['rows'], $this->limit['offset']);
        }
        foreach ($this->wheres as $where) {
            $source->where($where['where'], $where['inner'], $where['outer']);
        }
        if(!empty($this->group)) {
            $source->groupBy($this->group);
        }
        return $source;
    }
}