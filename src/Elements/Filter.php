<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;

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

    public function setQueryParams(QueryInterface $query) {
        if(!empty($this->order)) {
            $query->setOrderBy($this->order['columns'], $this->order['order'], $this->order['aliases']);
        }
        if(!empty($this->limit)) {
            $query->setLimit($this->limit['rows'], $this->limit['offset']);
        }
        foreach ($this->wheres as $where) {
            $query->where($where['where'], $where['inner'], $where['outer']);
        }
        if(!empty($this->group)) {
            $query->groupBy($this->group);
        }
        return $query;
    }
}