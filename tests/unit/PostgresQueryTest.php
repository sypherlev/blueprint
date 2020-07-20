<?php

namespace Test\unit;

use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresQuery;

class PostgresQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testCompileWithoutRequiredParams() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() SELECT without type/table did not trigger Exception');
    }

    public function testInsertWithoutRecords() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setType('INSERT');
            $postgresQuery->setTable('mockTable');
            $postgresQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() INSERT without records did not trigger Exception');
    }

    public function testWrongStatementType() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setType('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setType() with incorrect type did not trigger Exception');
    }

    public function testTableWhitelist() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setTable('fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setTable() with non-whitelisted table did not trigger Exception');
    }

    public function testEmptyColumns() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setColumns([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with empty array did not trigger Exception');
    }

    public function testEmptyUpdateArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setUpdates([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with empty array did not trigger Exception');
    }

    public function testEmptyUpdatesInCompilation() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setType('UPDATE');
            $postgresQuery->setTable('mockTable');
            $postgresQuery->compile();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->compile() with empty updates array did not trigger Exception');
    }

    public function testNumericKeysUpdateArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setUpdates(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with numeric-indexed array did not trigger Exception');
    }

    public function testNumericKeysInsertArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->addInsertRecord(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->addInsertRecord() with numeric-indexed array did not trigger Exception');
    }

    public function testInvalidJoinType() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setJoin('firsttable', 'secondtable', ['one' => 'two'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setJoin() with invalid join type did not trigger Exception');
    }

    public function testInvalidJoinOnArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setJoin('firsttable', 'secondtable', ['one', 'two'], 'LEFT');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setJoin() with invalid join array did not trigger Exception');
    }

    public function testInsertRecordWithoutColumnValidation() {
        $sql = 'INSERT INTO "mockTable" ("one", "two", "three" ) VALUES (:ins0, :ins1, :ins2) ';

        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('INSERT');
        $postgresQuery->setTable('mockTable');
        $postgresQuery->addInsertRecord(['one' => 'first', 'two' => 'second', 'three' => 'third']);

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testEmptyWhereArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setWhere([]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with empty array did not trigger Exception');
    }

    public function testInvalidBasicWhereArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setWhere(['one', 'two', 'three']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with numeric basic array did not trigger Exception');
    }

    public function testInvalidComplexWhereArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setWhere(['mockTable' => ['one', 'two', 'three']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with numeric complex array did not trigger Exception');
    }

    public function testInvalidOrderType() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setOrderBy(['column'], 'fake');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with invalid order type did not trigger Exception');
    }

    public function testInvalidOrderArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setOrderBy([1,2,3], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with numeric columns did not trigger Exception');
    }

    public function testInvalidComplexOrderArray() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setOrderBy(['mockTable' => [1,2,3]], 'DESC');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with numeric columns in table array did not trigger Exception');
    }

    public function testAggregateInvalidFunction() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setAggregate('fake', ['columnName']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with invalid function name did not trigger Exception');
    }

    public function testAggregateNumericColumn() {
        $postgresQuery = new PostgresQuery();

        try {
            $postgresQuery->setAggregate('SUM', [123]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with numeric column did not trigger Exception');
    }

    public function testAggregateBasicColumn() {
        $postgresQuery = new PostgresQuery();

        $postgresQuery->setTable('table');
        $postgresQuery->setType('SELECT');
        $postgresQuery->setAggregate('SUM', ['column1', 'column2']);

        $sql = 'SELECT SUM("table"."column1") AS "column1", SUM("table"."column2") AS "column2" FROM "table" ';

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testAggregateBasicColumnAlias() {
        $postgresQuery = new PostgresQuery();

        $postgresQuery->setTable('table');
        $postgresQuery->setType('SELECT');
        $postgresQuery->setAggregate('SUM', ['alias1' => 'column1', 'alias2' => 'column2']);

        $sql = 'SELECT SUM("table"."column1") AS "alias1", SUM("table"."column2") AS "alias2" FROM "table" ';

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testAggregateComplexColumnWithAlias() {
        $postgresQuery = new PostgresQuery();

        $postgresQuery->setTable('table');
        $postgresQuery->setType('SELECT');
        $postgresQuery->setAggregate('SUM', ['table' => ['alias1' => 'column1']]);

        $sql = 'SELECT SUM("table"."column1") AS "alias1" FROM "table" ';

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testAggregateComplexColumn() {
        $postgresQuery = new PostgresQuery();

        $postgresQuery->setTable('table');
        $postgresQuery->setType('SELECT');
        $postgresQuery->setAggregate('SUM', ['table' => ['column1']]);

        $sql = 'SELECT SUM("table"."column1") AS "column1" FROM "table" ';

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testWhiteListAdditions() {
        $tables = ['mockTable', 'tableone', 'tabletwo'];
        $columns = ['columnName', 'one', 'two', 'three'];

        $postgresQuery = new PostgresQuery();
        $postgresQuery->addToColumnWhitelist(['columnName']);
        $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
        $postgresQuery->addToTableWhitelist(['mockTable']);
        $postgresQuery->addToTableWhitelist(['tableone', 'tabletwo']);

        $this->assertEquals($tables, $postgresQuery->getSection('tablewhitelist'));
        $this->assertEquals($columns, $postgresQuery->getSection('columnwhitelist'));
        $this->assertEquals([], $postgresQuery->getSection('fake'));
    }

    public function testOperandsAndMultipleWheres() {
        $sql = 'SELECT * FROM "mockTable" WHERE ("mockTable"."col0" IS NOT NULL AND "mockTable"."col1" > :wh0 AND "mockTable"."col2" >= :wh1 AND "mockTable"."col3" < :wh2 AND "mockTable"."col4" <= :wh3) OR ("mockTable"."col5" != :wh4 AND "mockTable"."col6" != :wh5 AND "mockTable"."col7" NOT LIKE :wh6 AND "mockTable"."col8" LIKE :wh7 AND "mockTable"."col9" NOT IN (:wh8, :wh9, :wh10) AND "mockTable"."col10" IS NULL) ';

        $postgresQuery = new PostgresQuery();

        $postgresQuery->setTable('mockTable');
        $postgresQuery->setType('SELECT');
        $postgresQuery->setWhere([
            'col0 !=' => null,
            'col1 >' => 1,
            'col2 >=' => 2,
            'col3 <' => 3,
            'col4 <=' => 4
        ], 'AND', 'OR');
        $postgresQuery->setWhere([
            'col5 !=' => 5,
            'col6 !=' => 6,
            'col7 not like' => 7,
            'col8 like' => 8,
            'col9 not in' => [1,2,3],
            'col10' => null
        ]);

        $this->assertEquals($sql, $postgresQuery->compile());
    }

    public function testUpdateEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setUpdates(['four' => 'fake']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setUpdates() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setColumns(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with non-whitelisted column did not trigger Exception');
    }

    public function testColumnEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setColumns(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setColumns() with non-whitelisted table did not trigger Exception');
    }

    public function testOrderEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setOrderBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with non-whitelisted column did not trigger Exception');
    }

    public function testOrderEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setOrderBy(['fake' => ['four']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setOrderBy() with non-whitelisted table did not trigger Exception');
    }

    public function testAggregateEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setAggregate('SUM', ['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with non-whitelisted column did not trigger Exception');
    }

    public function testAggregateNumericColumns() {
        $postgresQuery = new PostgresQuery();
        try {
            $postgresQuery->setAggregate('SUM', [123]);
        }
        catch (\Exception $e) {
            try {
                $postgresQuery->setAggregate('SUM', ['test' => 123]);
            }
            catch (\Exception $e) {
                try {
                    $postgresQuery->setAggregate('SUM', ['test' => [123]]);
                }
                catch (\Exception $e) {
                    return;
                }
            }
        }
        $this->fail('PostgresQuery->setAggregate() with numeric column did not trigger Exception');
    }

    public function testAggregateEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setAggregate('SUM', ['fake' => ['columnName']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setAggregate() with non-whitelisted table did not trigger Exception');
    }

    public function testGroupEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setGroupBy(['four']);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setGroupBy() with non-whitelisted column did not trigger Exception');
    }

    public function testGroupEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setGroupBy(['fake' => ['columnName']]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setGroupBy() with non-whitelisted table did not trigger Exception');
    }

    public function testJoinEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
        }
        catch (\Exception $e) {
            try {
                $postgresQuery->addToTableWhitelist(['firsttable']);
                $postgresQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
            }
            catch (\Exception $e) {
                return;
            }
        }
        $this->fail('PostgresQuery->setJoin() with non-whitelisted table did not trigger Exception');
    }

    public function testJoinEntryColumnWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['firsttable', 'secondtable']);
            $postgresQuery->addToColumnWhitelist(['mockColumn']);
            $postgresQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
        }
        catch (\Exception $e) {
            try {
                $postgresQuery->addToTableWhitelist(['firsttable', 'secondtable']);
                $postgresQuery->addToColumnWhitelist(['one']);
                $postgresQuery->setJoin('firsttable', 'secondtable', ['one' => 'two']);
            }
            catch (\Exception $e) {
                return;
            }
        }
        $this->fail('PostgresQuery->setJoin() with non-whitelisted column did not trigger Exception');
    }

    public function testWhereEntryWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToColumnWhitelist(['one', 'two', 'three']);
            $postgresQuery->setWhere(['four' => 4]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with non-whitelisted column did not trigger Exception');
    }

    public function testWhereEntryTableWhitelist() {
        try {
            $postgresQuery = new PostgresQuery();
            $postgresQuery->addToTableWhitelist(['mockTable']);
            $postgresQuery->setWhere(['fake' => ['columnName' => 1]]);
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresQuery->setWhere() with non-whitelisted table did not trigger Exception');
    }
}