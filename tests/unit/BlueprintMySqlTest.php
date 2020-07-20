<?php

namespace Test\unit;

include_once realpath(__DIR__."/../testObjects/BlueprintMock.php");
include_once realpath(__DIR__."/../testObjects/PDOMock.php");

use PHPUnit\Framework\TestCase;
use SypherLev\Blueprint\QueryBuilders\MySql\MySqlSource;
use SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery;
use Test\testObjects\BlueprintMock;
use Test\testObjects\PDOMock;

class BlueprintMySqlTest extends TestCase
{
    public function testSelectMany() {

        $objectArray = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = 1484784000;
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $objectArray[] = $obj;
        }

        $resultArray = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = "2017-01-19";
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $resultArray[] = $obj;
        }

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchAllPDO($objectArray);

        $blueprintmany = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($resultArray, $blueprintmany->getMany());
    }

    public function testSelectFilter() {
        $objectArray = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = 1484784000;
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $objectArray[] = $obj;
        }

        $resultArray = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = 1484784000;
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $obj->current_index = $i;
            $resultArray[] = $obj;
        }

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchAllPDO($objectArray);

        $blueprintfilter = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($resultArray, $blueprintfilter->getWithFilter());
    }

    public function testSelectSingle() {
        $obj = new \stdClass();
        $obj->id = 1;
        $obj->mockcol = 'mockcol1';
        $obj->created = 1484784000;
        $obj->firstcolumn = 'firstcolumn1';
        $obj->secondcolumn = 'secondcolumn1';

        $res = clone $obj;
        $res->created = '2017-01-19';
        $res->current_index = 0;

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($obj);

        $blueprintsingle = new BlueprintMock(
            new MySqlSource($pdo), new MySqlQuery()
        );
        $this->assertEquals($res, $blueprintsingle->getSingle());
    }

    public function testInsert() {

        $insertRecord = [
            'created' => time(),
            'col1' => 'firstcolumn',
            'col2' => 'secondcolumn'
        ];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintinsert = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals(true, $blueprintinsert->insertRecord($insertRecord));
    }

    public function testSelectSql() {
        $sql = 'SELECT `mockTable`.*, `joinTable`.`firstcolumn` AS `alias1`, `joinTable`.`secondcolumn` AS `alias2`, SUM(`mockTable`.`col2`) AS `alias` FROM `mockTable` LEFT JOIN `joinTable` ON `mockTable`.`id` = `joinTable`.`join_id` WHERE (`mockTable`.`id` > :wh0) GROUP BY `mockTable`.`col1` ORDER BY `mockTable`.`id` DESC LIMIT 0, 5 ';

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($sql, $blueprintquery->testSelectQuery());
    }

    public function testSelectInSql() {
        $sql = 'SELECT * FROM `mockTable` WHERE (`mockTable`.`id` IN (:wh0, :wh1, :wh2)) ';

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($sql, $blueprintquery->getInArray());
    }

    public function testSelectBindings() {
        $bindings = [':wh0' => 0];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($bindings, $blueprintquery->testSelectBindings());
    }

    public function testInsertBindings() {
        $insertRecord = [
            'created' => '2017-01-19',
            'col1' => 'firstcolumn',
            'col2' => 'secondcolumn'
        ];

        $bindings = [
            ':ins0' => '2017-01-19',
            ':ins1' => 'firstcolumn',
            ':ins2' => 'secondcolumn'
        ];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($bindings, $blueprintquery->testInsertBindings($insertRecord));
    }

    public function testUpdateBindings() {
        $updateRecord = [
            'col1' => 'firstcolumn',
            'col2' => 'secondcolumn'
        ];

        $bindings = [
            ':up0' => 'firstcolumn',
            ':up1' => 'secondcolumn',
            ':wh0' => 1
        ];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($bindings, $blueprintquery->testUpdateBindings(1, $updateRecord));
    }

    public function testSelectWithoutColumns() {
        $sql = 'SELECT * FROM `mockTable` WHERE (`mockTable`.`id` = :wh0) ';

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($sql, $blueprintquery->getWithoutColumns());
    }

    public function testSelectOnlyAggregates() {
        $sql = 'SELECT SUM(`mockTable`.`firstcolumn`) AS `firstcolumn` FROM `mockTable` GROUP BY `mockTable`.`id` ';

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );
        $this->assertEquals($sql, $blueprintquery->getOnlyAggregates());
    }

    public function testInsertQuery() {
        $output = [[
            'sql' => 'INSERT INTO `mockTable` (`mockTable`.`created`, `mockTable`.`col1`, `mockTable`.`col2`, `mockTable`.`current_index` ) VALUES (:ins0, :ins1, :ins2, :ins3) ',
            'binds' => [
                ':ins0' => 1484784000,
                ':ins1' => 'firstcolumn',
                ':ins2' => 'secondcolumn',
                ':ins3' => 0
            ],
            'error' => '00000'
        ]];

        $insertRecord = [
            'created' => '2017-01-19',
            'col1' => 'firstcolumn',
            'col2' => 'secondcolumn'
        ];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($output, $blueprintquery->testInsertQuery($insertRecord));
    }

    public function testUpdateQuery() {
        $output = [[
            'sql' => 'UPDATE `mockTable` SET `created` = :up0, `col1` = :up1, `col2` = :up2, `current_index` = :up3 WHERE (`mockTable`.`id` = :wh0) ',
            'binds' => [
                ':wh0' => 1,
                ':up0' => 1484784000,
                ':up1' => 'firstcolumn',
                ':up2' => 'secondcolumn',
                ':up3' => '0'
            ],
            'error' => '00000'
        ]];

        $updateRecord = [
            'created' => '2017-01-19',
            'col1' => 'firstcolumn',
            'col2' => 'secondcolumn',
            'current_index' => 0
        ];

        $id = 1;

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($output, $blueprintquery->testUpdateQuery($id, $updateRecord));
    }

    public function testDeleteQuery() {
        $output = [[
            'sql' => 'DELETE FROM `mockTable` WHERE (`mockTable`.`id` = :wh0) ',
            'binds' => [
                ':wh0' => 1
            ],
            'error' => '00000'
        ]];

        $id = 1;

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($output, $blueprintquery->testDeleteQuery($id));
    }

    public function testCountQuery() {
        $output = [[
            'sql' => 'SELECT COUNT(*) AS `count` FROM `mockTable` WHERE (`mockTable`.`id` > :wh0) ',
            'binds' => [
                ':wh0' => 1
            ],
            'error' => null
        ]];

        $result = new \stdClass();
        $result->count = 10;

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($result);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($output, $blueprintquery->testCountQuery());
    }

    public function testReverseOrderTableQuery() {
        $sql = 'SELECT `mockTable`.`one`, `mockTable`.`two`, `mockTable`.`three` FROM `mockTable` ';

        $result = new \stdClass();
        $result->one = 'one';
        $result->two = 'two';
        $result->three = 'three';

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($result);

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals($sql, $blueprintquery->testReverseOrderTableSetting());
    }

    public function testExceptions() {
        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $blueprintexceptions = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertInstanceOf('Exception', $blueprintexceptions->testPatternException());
        $this->assertInstanceOf('Exception', $blueprintexceptions->testFilterException());
        $this->assertInstanceOf('Exception', $blueprintexceptions->testTransformationException());

        $this->assertInstanceOf('Exception', $blueprintexceptions->testPatternAddingException());
        $this->assertInstanceOf('Exception', $blueprintexceptions->testFilterAddingException());
        $this->assertInstanceOf('Exception', $blueprintexceptions->testCountBadResult());
    }

    public function testAdditionalFunctions() {
        $output = [
            ':wh0' => 1
        ];

        $PDOMock = new PDOMock();

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createMysqlSchemaPDO();

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $blueprintquery->testWhitelists();
        $this->assertEquals($output, $blueprintquery->testGetBindings());
        $this->assertEquals(['mockColumn'], $blueprintquery->testGetColumns());

        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createMysqlPrimaryKeysPDO();

        $blueprintquery = new BlueprintMock(
            new MySqlSource($pdo),
            new MySqlQuery()
        );

        $this->assertEquals("id", $blueprintquery->testGetPrimaryKey());
    }
}