<?php

namespace SypherLev\Blueprint\QueryBuilders;

interface QueryInterface
{
    public function compile();

    public function setTable($tablename);

    public function setType($type);

    public function setColumns(Array $columns);

    public function setUpdates(Array $updates);

    public function setCount($count = false);

    public function addInsertRecord(Array $record);

    public function setLimit($rows, $offset = 0);

    public function setJoin($first, $second, Array $on, $type = 'INNER');

    public function setWhere(Array $where, $innercondition = 'AND', $outercondition = 'AND');

    public function setOrderBy(Array $orderby, $direction = 'ASC', $aliases = false);

    public function setGroupBy(Array $groupby);

    public function addToColumnWhitelist($column);

    public function addToTableWhitelist($table);

    public function getBindings();

    public function merge(QueryInterface $query, $type);

    public function getSection($sectionName);
}