<?php

namespace Test\unit;

include_once realpath(__DIR__."/../testObjects/BlueprintMock.php");
include_once realpath(__DIR__."/../testObjects/PDOMock.php");

use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresSource;
use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresQuery;
use Test\testObjects\PDOMock;

class PostgresSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testOneException() {

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $this->assertEquals(new \stdClass(), $postgresSource->one());
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM "mockTable" ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
    }

    public function testManyException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $this->assertEquals([], $postgresSource->many());
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM "mockTable" ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
    }

    public function testExecuteException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('UPDATE');
        $postgresQuery->setTable('mockTable');
        $postgresQuery->setWhere(['id' => 1]);
        $postgresQuery->setUpdates(['col1' => 'one']);
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $this->assertEquals(false, $postgresSource->execute());
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'UPDATE "mockTable" SET "col1" = :up0 WHERE ("mockTable"."id" = :wh0) ',
            'binds' => [
                ':wh0' => 1,
                ':up0' => 'one'
            ],
            'error' => ''
        ]];

        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
    }
    
    public function testCountException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO(new \stdClass);

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        try {
            $postgresSource->count();
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

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $postgresSource->many();
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM "mockTable" ',
            'binds' => [],
            'error' => null
        ]];

        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
    }

    public function testCount() {

        $returnObject = new \stdClass();
        $returnObject->count = 3;

        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO($returnObject);

        $postgresSource = new PostgresSource($pdo);
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setCount(true);
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);

        $this->assertEquals(3, $postgresSource->count());
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

        $postgresSource = new PostgresSource($pdo);

        $sql = 'SELECT * FROM "mockTable" WHERE "mockTable"."id" = :id';
        $postgresSource->startRecording();
        $result = $postgresSource->raw($sql, [':id' => 1], 'fetch');
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM "mockTable" WHERE "mockTable"."id" = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => ''
        ]];

        $this->assertEquals($obj, $result);
        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
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

        $postgresSource = new PostgresSource($pdo);

        $sql = 'SELECT * FROM "mockTable" WHERE "mockTable"."id" = :id';
        $postgresSource->startRecording();
        $result = $postgresSource->raw($sql, [':id' => 1], 'fetchAll');
        $postgresSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM "mockTable" WHERE "mockTable"."id" = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => ''
        ]];

        $this->assertEquals($objectArray, $result);
        $this->assertEquals($recordedOutput, $postgresSource->getRecordedOutput());
    }

    public function testRawException() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createExceptionPDO();

        $postgresSource = new PostgresSource($pdo);

        $sql = 'SELECT * FROM "mockTable"';
        $result = $postgresSource->raw($sql, [':id' => 1], 'fetchAll');

        $this->assertInstanceOf('\Exception', $result);
    }

    public function testPDOUtilitiesTrue() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createUtilityPDOPostgres(true);

        $postgresSource = new PostgresSource($pdo);
        $postgresSource->beginTransaction();

        $this->assertEquals(1, $postgresSource->lastInsertId('tablename'));
        $this->assertEquals(true, $postgresSource->commit());

        $postgresSource->beginTransaction();
        $this->assertEquals(true, $postgresSource->rollBack());

        try {
            $postgresSource->lastInsertId('');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresSource->lastInsertId() with invalid parameters did not trigger an Exception');
    }

    public function testPDOSchemaFunctions() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createPostgresSchemaPDO();

        $postgresSource = new PostgresSource($pdo);
        $postgresSource->beginTransaction();

        $this->assertEquals('mockDatabase', $postgresSource->getDatabaseName());

        $this->assertEquals(['mockColumn'], $postgresSource->getTableColumns('mockTable'));
    }

    public function testPDOPrimaryKeyFunctions() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createPostgresPrimaryKeysPDO();

        $postgresSource = new PostgresSource($pdo);
        $postgresSource->beginTransaction();

        $this->assertEquals('id', $postgresSource->getPrimaryKey('mockTable'));
    }

    public function testInvalidPrimaryKeyFunction() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchPDO(new \stdClass());

        $postgresSource = new PostgresSource($pdo);
        $postgresSource->beginTransaction();

        $this->assertEquals('', $postgresSource->getPrimaryKey('mockTable'));
    }

    public function testInvalidLastInsertIDFunction() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createFetchAllPDO([new \stdClass()]);

        $postgresSource = new PostgresSource($pdo);
        try {
            $postgresSource->lastInsertId('mockTable');
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('PostgresSource->lastInsertId() with invalid parameters did not trigger an Exception');
    }

    public function testPDOUtilitiesFalse() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createUtilityPDOPostgres(false);

        $postgresSource = new PostgresSource($pdo);

        $this->assertEquals(false, $postgresSource->commit());

        $this->assertEquals(false, $postgresSource->rollBack());
    }

    public function testPDOBindings() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $postgresSource = new PostgresSource($pdo);

        $sql = 'SELECT * FROM "mockTable" WHERE "mockTable"."col1" IS :nullbind OR "mockTable"."col1" = :truebind';
        $result = $postgresSource->raw($sql, [':nullbind' => null, ':truebind' => true]);

        $this->assertEquals(true, $result);
    }

    public function testQueryGeneration() {
        $PDOMock = new PDOMock();
        /** @var \PDO&\PHPUnit\Framework\MockObject\MockObject $pdo */
        $pdo = $PDOMock->createBooleanPDO(true);

        $postgresSource = new PostgresSource($pdo);

        $query = $postgresSource->generateNewQuery();
        $this->assertInstanceOf(PostgresQuery::class, $query);
    }
}