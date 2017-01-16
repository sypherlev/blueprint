<?php

use PHPUnit\Framework\TestCase;
use SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery;

class MySqlQueryTest extends TestCase
{
    public function testSelect() {
        $query = new MySqlQuery();
        $query->setType('SELECT');
        $query->setTable('testTable');
        $query->setColumns(['column1', 'column2']);
        $query->setLimit(10, 50);

        $SQL = "SELECT `testTable`.`column1`, `testTable`.`column2` FROM `testTable` LIMIT 50, 10 ";

        $this->assertEquals($SQL, $query->compile());
    }

    public function testCount() {
        $query = new MySqlQuery();
        $query->setType('SELECT');
        $query->setTable('testTable');
        $query->setCount(true);

        $SQL = "SELECT COUNT(*) AS count FROM `testTable` ";

        $this->assertEquals($SQL, $query->compile());
    }

    public function testUpdate() {
        $query = new MySqlQuery();
        $query->setType('INSERT');
        $query->setTable('testTable');
        $query->addInsertRecord(['column1' => 'value1', 'column2' => 'value2']);

        $SQL = "INSERT INTO `testTable` (`testTable`.`column1`, `testTable`.`column2` ) VALUES (:ins0, :ins1) ";
        $bindings = [
            ':ins0' => 'value1',
            ':ins1' => 'value2'
        ];

        $this->assertEquals($SQL, $query->compile());
        $this->assertEquals($bindings, $query->getBindings());
    }

    public function testSimpleWhere() {
        $query = new MySqlQuery();
        $query->setType('SELECT');
        $query->setTable('testTable');
        $query->setColumns(['column1', 'column2']);
        $query->setWhere(['column1' => 'value1']);

        $SQL = "SELECT `testTable`.`column1`, `testTable`.`column2` FROM `testTable` WHERE (`testTable`.`column1` = :wh0) ";
        $bindings = [
            ':wh0' => 'value1',
        ];

        $this->assertEquals($SQL, $query->compile());
        $this->assertEquals($bindings, $query->getBindings());
    }

    public function testJoins() {
        $query = new MySqlQuery();
        $query->setType('SELECT');
        $query->setTable('testTable');
        $query->setJoin('testTable', 'joinTable', ['column1' => 'column3'], 'LEFT');
        $query->setColumns(['testTable' => ['column1', 'column2'], 'joinTable' => ['column3', 'column4']]);
        $query->setWhere(['column1' => 'value1']);

        $SQL = "SELECT `testTable`.`column1`, `testTable`.`column2`, `joinTable`.`column3`, `joinTable`.`column4` FROM `testTable` LEFT JOIN `joinTable` ON `testTable`.`column1` = `joinTable`.`column3` WHERE (`testTable`.`column1` = :wh0) ";
        $bindings = [
            ':wh0' => 'value1',
        ];

        $this->assertEquals($SQL, $query->compile());
        $this->assertEquals($bindings, $query->getBindings());
    }

    public function testComplexWhere() {
        $query = new MySqlQuery();
        $query->setType('SELECT');
        $query->setTable('testTable');
        $query->setColumns(['column1', 'column2']);
        $query->setWhere(['column1' => 'value1']);

        $SQL = "SELECT `testTable`.`column1`, `testTable`.`column2` FROM `testTable` WHERE (`testTable`.`column1` = :wh0) ";
        $bindings = [
            ':wh0' => 'value1',
        ];

        $this->assertEquals($SQL, $query->compile());
        $this->assertEquals($bindings, $query->getBindings());
    }
}