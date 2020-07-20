<?php

namespace Test\unit;

include_once realpath(__DIR__."/../testObjects/BlueprintMock.php");
include_once realpath(__DIR__."/../testObjects/PDOMock.php");

use \SypherLev\Blueprint\QueryBuilders\MySql\MySqlSource;
use \SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery;
use Test\testObjects\PDOMock;

class MySqlSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testOneException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $this->assertEquals(new \stdClass(), $mysqlSource->one());
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testManyException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $this->assertEquals([], $mysqlSource->many());
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testExecuteException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('UPDATE');
        $mysqlQuery->setTable('mockTable');
        $mysqlQuery->setWhere(['id' => 1]);
        $mysqlQuery->setUpdates(['col1' => 'one']);
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $this->assertEquals(false, $mysqlSource->execute());
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'UPDATE `mockTable` SET `col1` = :up0 WHERE (`mockTable`.`id` = :wh0) ',
            'binds' => [
                ':wh0' => 1,
                ':up0' => 'one'
            ],
            'error' => ''
        ]];

        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testCountException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO(new \stdClass);

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        try {
            $mysqlSource->count();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('MysqlSource->count() returning invalid result did not trigger an Exception');
    }

    public function testManyRecording() {

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

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchAllPDO($objectArray);

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $mysqlSource->many();
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testCount() {

        $returnObject = new \stdClass();
        $returnObject->count = 3;

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($returnObject);

        $mysqlSource = new MySqlSource($pdo);
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setCount(true);
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);

        $this->assertEquals(3, $mysqlSource->count());
    }

    public function testRawFetch() {

        $obj = new \stdClass();
        $obj->id = 1;
        $obj->mockcol = 'mockcol';
        $obj->created = 1484784000;
        $obj->firstcolumn = 'firstcolumn';
        $obj->secondcolumn = 'secondcolumn';

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($obj);

        $mysqlSource = new MySqlSource($pdo);

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id';
        $mysqlSource->startRecording();
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetch');
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => null
        ]];

        $this->assertEquals($obj, $result);
        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testRawFetchAll() {

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

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchAllPDO($objectArray);

        $mysqlSource = new MySqlSource($pdo);

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id';
        $mysqlSource->startRecording();
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetchAll');
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => null
        ]];

        $this->assertEquals($objectArray, $result);
        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testRawException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $mysqlSource = new MySqlSource($pdo);

        $sql = 'SELECT * FROM `mockTable`';
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetchAll');

        $this->assertInstanceOf('\Exception', $result);
    }

    public function testPDOUtilitiesTrue() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createUtilityPDOMySQL(true);

        $mysqlSource = new MySqlSource($pdo);
        $mysqlSource->beginTransaction();

        $this->assertEquals(1, $mysqlSource->lastInsertId());
        $this->assertEquals(1, $mysqlSource->lastInsertId('mockTable'));
        $this->assertEquals(true, $mysqlSource->commit());

        $mysqlSource->beginTransaction();
        $this->assertEquals(true, $mysqlSource->rollBack());
    }

    public function testPDOSchemaFunctions() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createMysqlSchemaPDO();

        $mysqlSource = new MySqlSource($pdo);
        $mysqlSource->beginTransaction();

        $this->assertEquals('mockDatabase', $mysqlSource->getDatabaseName());

        $this->assertEquals(['mockColumn'], $mysqlSource->getTableColumns('mockTable'));
    }

    public function testPrimaryKeyFunctions() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createMysqlPrimaryKeysPDO();

        $mysqlSource = new MySqlSource($pdo);
        $mysqlSource->beginTransaction();

        $this->assertEquals('id', $mysqlSource->getPrimaryKey('mockTable'));
    }

    public function testInvalidPrimaryKeyFunction() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO(new \stdClass());

        $mysqlSource = new MySqlSource($pdo);
        $mysqlSource->beginTransaction();

        $this->assertEquals('', $mysqlSource->getPrimaryKey('mockTable'));
    }

    public function testPDOUtilitiesFalse() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createUtilityPDOMySQL(false);

        $mysqlSource = new MySqlSource($pdo);

        $this->assertEquals(false, $mysqlSource->commit());

        $this->assertEquals(false, $mysqlSource->rollBack());
    }

    public function testPDOBindings() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $mysqlSource = new MySqlSource($pdo);

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`col1` IS :nullbind OR `mockTable`.`col1` = :truebind';
        $result = $mysqlSource->raw($sql, [':nullbind' => null, ':truebind' => true]);

        $this->assertEquals(true, $result);
    }

    public function testQueryGeneration() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $mysqlSource = new MySqlSource($pdo);

        $query = $mysqlSource->generateNewQuery();
        $this->assertInstanceOf(MySqlQuery::class, $query);
    }
}