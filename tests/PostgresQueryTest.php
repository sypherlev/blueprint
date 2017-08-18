<?php

use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresQuery;

class PostgresQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testCompileWithoutRequiredParams() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() SELECT without type/table did not trigger Exception');
    }

    public function testInsertWithoutRecords() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setType('INSERT');
            $mysqlQuery->setTable('mockTable');
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() INSERT without records did not trigger Exception');
    }

    public function testWrongStatementType() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setType('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setType() with incorrect type did not trigger Exception');
    }

    public function testTableWhitelist() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->addToTableWhitelist('mockTable');
            $mysqlQuery->setTable('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setTable() with non-whitelisted table did not trigger Exception');
    }

    public function testEmptyColumns() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setColumns([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with empty array did not trigger Exception');
    }

    public function testEmptyUpdateArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setUpdates([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with empty array did not trigger Exception');
    }

    public function testEmptyUpdatesInCompilation() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setType('UPDATE');
            $mysqlQuery->setTable('mockTable');
            $mysqlQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() with empty updates array did not trigger Exception');
    }

    public function testNumericKeysUpdateArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setUpdates(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with numeric-indexed array did not trigger Exception');
    }

    public function testNumericKeysInsertArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->addInsertRecord(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->addInsertRecord() with numeric-indexed array did not trigger Exception');
    }

    public function testInvalidJoinType() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one' => 'two'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setJoin() with invalid join type did not trigger Exception');
    }

    public function testInvalidJoinOnArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one', 'two'], 'LEFT');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setJoin() with invalid join array did not trigger Exception');
    }

    public function testInsertRecordWithoutColumnValidation() {
        $sql = 'INSERT INTO "mockTable" ("one", "two", "three" ) VALUES (:ins0, :ins1, :ins2) ';

        $mysqlQuery = new PostgresQuery();
        $mysqlQuery->setType('INSERT');
        $mysqlQuery->setTable('mockTable');
        $mysqlQuery->addInsertRecord(['one' => 'first', 'two' => 'second', 'three' => 'third']);

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testEmptyWhereArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setWhere([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with empty array did not trigger Exception');
    }

    public function testInvalidBasicWhereArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setWhere(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with numeric basic array did not trigger Exception');
    }

    public function testInvalidComplexWhereArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setWhere(['mockTable' => ['one', 'two', 'three']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with numeric complex array did not trigger Exception');
    }

    public function testInvalidOrderType() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setOrderBy(['column'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with invalid order type did not trigger Exception');
    }

    public function testInvalidOrderArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setOrderBy([1,2,3], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with numeric columns did not trigger Exception');
    }

    public function testInvalidComplexOrderArray() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setOrderBy(['mockTable' => [1,2,3]], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with numeric columns in table array did not trigger Exception');
    }

    public function testAggregateInvalidFunction() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setAggregate('fake', 'columnName');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with invalid function name did not trigger Exception');
    }

    public function testAggregateNumericColumn() {
        $mysqlQuery = new PostgresQuery();

        try {
            $mysqlQuery->setAggregate('SUM', 123);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with numeric column did not trigger Exception');
    }

    public function testAggregateBasicColumn() {
        $mysqlQuery = new PostgresQuery();

        $mysqlQuery->setTable('table');
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setAggregate('SUM', ['column1', 'column2']);

        $sql = 'SELECT SUM("table"."column1") AS "column1", SUM("table"."column2") AS "column2" FROM "table" ';

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testAggregateBasicColumnAlias() {
        $mysqlQuery = new PostgresQuery();

        $mysqlQuery->setTable('table');
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setAggregate('SUM', ['alias1' => 'column1', 'alias2' => 'column2']);

        $sql = 'SELECT SUM("table"."column1") AS "alias1", SUM("table"."column2") AS "alias2" FROM "table" ';

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testAggregateComplexColumnWithAlias() {
        $mysqlQuery = new PostgresQuery();

        $mysqlQuery->setTable('table');
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setAggregate('SUM', ['table' => ['alias1' => 'column1']]);

        $sql = 'SELECT SUM("table"."column1") AS "alias1" FROM "table" ';

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testAggregateComplexColumn() {
        $mysqlQuery = new PostgresQuery();

        $mysqlQuery->setTable('table');
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setAggregate('SUM', ['table' => ['column1']]);

        $sql = 'SELECT SUM("table"."column1") AS "column1" FROM "table" ';

        $this->assertEquals($sql, $mysqlQuery->compile());
    }

    public function testWhiteListAdditions() {
        $tables = ['mockTable', 'tableone', 'tabletwo'];
        $columns = ['columnName', 'one', 'two', 'three'];

        $mysqlQuery = new PostgresQuery();
        $mysqlQuery->addToColumnWhitelist('columnName');
        $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
        $mysqlQuery->addToTableWhitelist('mockTable');
        $mysqlQuery->addToTableWhitelist(['tableone', 'tabletwo']);

        $this->assertEquals($tables, $mysqlQuery->getSection('tablewhitelist'));
        $this->assertEquals($columns, $mysqlQuery->getSection('columnwhitelist'));
        $this->assertEquals(false, $mysqlQuery->getSection('fake'));
    }

    public function testOperandsAndMultipleWheres() {
        $sql = 'SELECT * FROM "mockTable" WHERE ("mockTable"."col0" IS NOT NULL AND "mockTable"."col1" > :wh0 AND "mockTable"."col2" >= :wh1 AND "mockTable"."col3" < :wh2 AND "mockTable"."col4" <= :wh3) OR ("mockTable"."col5" != :wh4 AND "mockTable"."col6" !== :wh5 AND "mockTable"."col7" NOT LIKE :wh6 AND "mockTable"."col8" LIKE :wh7 AND "mockTable"."col9" NOT IN (:wh8, :wh9, :wh10) AND "mockTable"."col10" IS NULL) ';

        $mysqlQuery = new PostgresQuery();

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
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setUpdates(['four' => 'fake']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setColumns(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setColumns(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with non-whitelisted table did not trigger Exception');
    }

    public function testOrderEntryWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setOrderBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with non-whitelisted column did not trigger Exception');
    }

    public function testOrderEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setOrderBy(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with non-whitelisted table did not trigger Exception');
    }

    public function testAggregateEntryWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setAggregate('SUM', 'four');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with non-whitelisted column did not trigger Exception');
    }

    public function testAggregateEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setAggregate('SUM', ['fake' => ['columnName']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with non-whitelisted table did not trigger Exception');
    }

    public function testGroupEntryWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setGroupBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setGroupBy() with non-whitelisted column did not trigger Exception');
    }

    public function testGroupEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setGroupBy(['fake' => ['columnName']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setGroupBy() with non-whitelisted table did not trigger Exception');
    }

    public function testJoinEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setJoin() with non-whitelisted table did not trigger Exception');
    }

    public function testWhereEntryWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $mysqlQuery->setWhere(['four' => 4]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with non-whitelisted column did not trigger Exception');
    }

    public function testWhereEntryTableWhitelist() {
        try {
            $mysqlQuery = new PostgresQuery();
            $mysqlQuery->addToTableWhitelist(['mockTable']);
            $mysqlQuery->setWhere(['fake' => ['columnName' => 1]]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with non-whitelisted table did not trigger Exception');
    }
}