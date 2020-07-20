<?php

namespace SypherLev\Blueprint\Elements;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class Filter
{
    private $wheres = [];
    private $order = [];
    private $limit = [];

    public function where(array $where, string $innercondition = 'AND', string $outercondition = 'AND') : Filter {
        $this->wheres[] = array(
            'where' => $where,
            'inner' => $innercondition,
            'outer' => $outercondition
        );
        return $this;
    }

    public function orderBy(array $columns, string $order = 'ASC', bool $useAliases = false) : Filter {
        $this->order = array(
            'columns' => $columns,
            'order' => $order,
            'aliases' => $useAliases
        );
        return $this;
    }

    public function limit(int $rows, int $offset = 0) : Filter {
        $this->limit = array(
            'rows' => $rows,
            'offset' => $offset
        );
        return $this;
    }

    public function setQueryParams(QueryInterface $query) : QueryInterface {
        if(!empty($this->order)) {
            $query->setOrderBy($this->order['columns'], $this->order['order'], $this->order['aliases']);
        }
        if(!empty($this->limit)) {
            $query->setLimit($this->limit['rows'], $this->limit['offset']);
        }
        foreach ($this->wheres as $where) {
            $query->setWhere($where['where'], $where['inner'], $where['outer']);
        }
        return $query;
    }
}