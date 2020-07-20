<?php

namespace SypherLev\Blueprint\QueryBuilders;

interface QueryInterface
{

    /**
     * Attempts to compile the current query and returns it as a string
     *
     * @return string
     */
    public function compile();

    /**
     * Set the primary table on the query
     *
     * @param string $tablename
     */
    public function setTable(string $tablename);

    /**
     * Sets the current query type
     *
     * @param $type
     */
    public function setType(string $type);

    /**
     * Selects columns to attach to the query
     *
     * @param $columns - has four possible types
     *     array($columnone, $columntwo, ...)
     *     array($alias => $column, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     *     array($tableone => array($aliasone => $columnone, $aliastwo => $columntwo,  ...), $tabletwo => array(...) ...)
     */
    public function setColumns(array $columns);

    /**
     * Used only with UPDATE
     *
     * @param array $updates - array('column' => $variable, ... )
     */
    public function setUpdates(array $updates);

    /**
     * Sets the current query to return COUNT(*)
     *
     * @param bool $count
     */
    public function setCount(bool $count = false);

    /**
     * Used to add records for INSERT statements
     * Use this in a loop to add a batch of records
     *
     * @param array $record - array('column' => $variable, ... )
     */
    public function addInsertRecord(array $record);

    /**
     * Sets the LIMIT for the current query.
     *
     * @param int $rows
     * @param int $offset
     */
    public function setLimit(int $rows, int $offset = 0);

    /**
     * Adds a JOIN clause
     *
     * @param $firsttable - tablename
     * @param $secondtable - tablename
     * @param array $on - must be in the format array('firsttablecolumn' => 'secondtablecolumn, ...)
     * @param string $type - inner|full|left|right
     */
    public function setJoin(string $first, string $second, array $on, string $type = 'INNER');

    /**
     * Adds a WHERE clause
     * Column names can use the format 'columnname operand' to use operands other than '=', e.g. 'id >'
     * Valid operands: >|<|>=|<=|like|in
     * If the tablename is not specified in the $where array parameter, $this->currentquery->table will be used
     * instead
     * Using the IN operand will make the param be treated as an array.
     * Setting the param to NULL will force the operand to IS.
     *
     * @param array $where - has three possible types:
     *     array($column => $param, ...)
     *     array($tableone => array($column => $param, ...), $tabletwo => array($column => $param, ...), ...)
     * @param string $innercondition - AND|OR - used between clauses in the WHERE statement
     * @param string $outercondition - AND|OR - used to append this WHERE statement to the query
     */
    public function setWhere(array $where, string $innercondition = 'AND', string $outercondition = 'AND');

    /**
     * Sets the order for the query
     *
     * @param $orderby - has two possible types
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     * @param $direction - must be one of 'ASC' or 'DESC'
     * @param $aliases - whether aliases are being used or not
     */
    public function setOrderBy(array $orderby, string $direction = 'ASC', bool $aliases = false);

    /**
     * Sets one or more aggregate clauses in the current query, ex. SUM(columnone), SUM(column2)
     * Use multiple columns for quick and dirty data retrieval,
     *
     * @param $function - ex. SUM, AVG, MAX - applied to all selected columns
     * @param $columns - has three possible types:
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     *     array($tableone => array($alias => $columnone, $alias => $columntwo,  ...), $tabletwo => array(...), ...)
     */
    public function setAggregate(string $function, array $columns);

    /**
     * Sets the groupby clause
     *
     * @param $groupby - has two possible types:
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     */
    public function setGroupBy(array $groupby);

    /**
     * Adds to the columns whitelist in the case where the query is executing with
     * user input as the column names. Takes an array or a string
     *
     * @param array $whitelist
     */
    public function addToColumnWhitelist(array $columns);

    /**
     * Adds to the table whitelist in the case where the query is executing with
     * user input as the table names. Applies to the primary table and all joined tables
     * Accepts an array or a string
     *
     * @param array $whitelist
     */
    public function addToTableWhitelist(array $table);

    /**
     * Gets the bindings which will be used in the current query
     *
     * @return array
     */
    public function getBindings();

    /**
     * Debugging method used to examine any private property of the current query
     *
     * @param $sectionName
     * @return mixed
     */
    public function getSection(string $sectionName);
}