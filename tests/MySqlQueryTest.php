<?php

use \SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery;

class MySqlQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testCompileWithoutRequiredParams() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->compile() SELECT without type/table did not trigger Exception');
    }

    public function testInsertWithoutRecords() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setType('INSERT');
            $mysqlQuery->setTable('mockTable');
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->compile() INSERT without records did not trigger Exception');
    }

    public function testWrongStatementType() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setType('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setType() with incorrect type did not trigger Exception');
    }

    public function testTableWhitelist() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->addToTableWhitelist('mockTable');
            $mysqlQuery->setTable('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setTable() with non-whitelisted table did not trigger Exception');
    }

    public function testEmptyColumns() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setColumns([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setColumns() with empty array did not trigger Exception');
    }

    public function testEmptyUpdateArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setUpdates([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setUpdates() with empty array did not trigger Exception');
    }

    public function testEmptyUpdatesInCompilation() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setType('UPDATE');
            $mysqlQuery->setTable('mockTable');
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->compile() with empty updates array did not trigger Exception');
    }

    public function testNumericKeysUpdateArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setUpdates(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setUpdates() with numeric-indexed array did not trigger Exception');
    }

    public function testNumericKeysInsertArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->addInsertRecord(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->addInsertRecord() with numeric-indexed array did not trigger Exception');
    }

    public function testInvalidJoinType() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one' => 'two'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setJoin() with invalid join type did not trigger Exception');
    }

    public function testInvalidJoinOnArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one', 'two'], 'LEFT');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setJoin() with invalid join array did not trigger Exception');
    }

    public function testInsertRecordWithoutColumnValidation() {
        $sql = "INSERT INTO `mockTable` (`mockTable`.`one`, `mockTable`.`two`, `mockTable`.`three` ) VALUES (:ins0, :ins1, :ins2) ";

        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('INSERT');
        $mysqlQuery->setTable('mockTable');
        $mysqlQuery->addInsertRecord(['one' => 'first', 'two' => 'second', 'three' => 'third']);

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testEmptyWhereArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setWhere([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setWhere() with empty array did not trigger Exception');
    }

    public function testInvalidBasicWhereArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setWhere(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setWhere() with numeric basic array did not trigger Exception');
    }

    public function testInvalidComplexWhereArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setWhere(['mockTable' => ['one', 'two', 'three']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setWhere() with numeric complex array did not trigger Exception');
    }

    public function testInvalidOrderType() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setOrderBy(['column'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setOrderBy() with invalid order type did not trigger Exception');
    }

    public function testInvalidOrderArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setOrderBy([1,2,3], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setOrderBy() with numeric columns did not trigger Exception');
    }

    public function testInvalidComplexOrderArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setOrderBy(['mockTable' => [1,2,3]], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setOrderBy() with numeric columns in table array did not trigger Exception');
    }

    public function testAggregateInvalidFunction() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setAggregate('fake', 'columnName');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setAggregate() with invalid function name did not trigger Exception');
    }

    public function testAggregateInvalidColumnArray() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setAggregate('SUM', ['columnName'], 'alias');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setAggregate() with invalid column array did not trigger Exception');
    }

    public function testAggregateNumericColumn() {
        $mysqlQuery = new MySqlQuery();

        try {
            $mysqlQuery->setAggregate('SUM', 123, 'alias');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setAggregate() with numeric column did not trigger Exception');
    }

    public function testWhiteListAdditions() {
        $tables = ['mockTable', 'tableone', 'tabletwo'];
        $columns = ['columnName', 'one', 'two', 'three'];

        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->addToColumnWhitelist('columnName');
        $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
        $mysqlQuery->addToTableWhitelist('mockTable');
        $mysqlQuery->addToTableWhitelist(['tableone', 'tabletwo']);

        $this->assertEquals($tables, $mysqlQuery->getSection('tablewhitelist'));
        $this->assertEquals($columns, $mysqlQuery->getSection('columnwhitelist'));
        $this->assertEquals(false, $mysqlQuery->getSection('fake'));
    }

    public function testOperandsAndMultipleWheres() {
        $sql = 'SELECT * FROM `mockTable` WHERE (`mockTable`.`col0` IS NOT NULL AND `mockTable`.`col1` > :wh0 AND `mockTable`.`col2` >= :wh1 AND `mockTable`.`col3` < :wh2 AND `mockTable`.`col4` <= :wh3) OR (`mockTable`.`col5` != :wh4 AND `mockTable`.`col6` !== :wh5 AND `mockTable`.`col7` NOT LIKE :wh6 AND `mockTable`.`col8` LIKE :wh7 AND `mockTable`.`col9` NOT IN (:wh8, :wh9, :wh10) AND `mockTable`.`col10` IS NULL) ';

        $mysqlQuery = new MySqlQuery();

        $mysqlQuery->setTable('mockTable');
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setWhere([
            'col0 !=' => null,
            'col1 >' => 1,
            'col2 >=' => 2,
            'col3 <' => 3,
            'col4 <=' => 4
        ], 'AND', 'OR');
        $mysqlQuery->setWhere([
            'col5 !=' => 5,
            'col6 !==' => 6,
            'col7 not like' => 7,
            'col8 like' => 8,
            'col9 not in' => [1,2,3],
            'col10' => null
        ]);

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testUpdateEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setUpdates(['four' => 'fake']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setUpdates() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setColumns(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setColumns() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setColumns(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setColumns() with non-whitelisted table did not trigger Exception');
    }

    public function testOrderEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setOrderBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setOrderBy() with non-whitelisted column did not trigger Exception');
    }

    public function testOrderEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setOrderBy(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setOrderBy() with non-whitelisted table did not trigger Exception');
    }

    public function testAggregateEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setAggregate('SUM', 'four');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setAggregate() with non-whitelisted column did not trigger Exception');
    }

    public function testAggregateEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setAggregate('SUM', ['fake' => 'columnName']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setAggregate() with non-whitelisted table did not trigger Exception');
    }

    public function testGroupEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setGroupBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setGroupBy() with non-whitelisted column did not trigger Exception');
    }

    public function testGroupEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setGroupBy(['fake' => ['columnName']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setGroupBy() with non-whitelisted table did not trigger Exception');
    }

    public function testJoinEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setJoin() with non-whitelisted table did not trigger Exception');
    }

    public function testWhereEntryWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setWhere(['four' => 4]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setWhere() with non-whitelisted column did not trigger Exception');
    }

    public function testWhereEntryTableWhitelist() {
        try {
            $mysqlQuery = new MySqlQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setWhere(['fake' => ['columnName' => 1]]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MySqlQuery->setWhere() with non-whitelisted table did not trigger Exception');
    }
}