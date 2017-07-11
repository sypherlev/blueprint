<?php

use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresSource;
use SypherLev\Blueprint\QueryBuilders\Postgres\PostgresQuery;

class PostgresSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testOneException() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createExceptionPDO());
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $this->assertEquals(false, $postgresSource->one());
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
        $postgresSource = new PostgresSource($PDOMock->createExceptionPDO());
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);
        $postgresSource->startRecording();
        $this->assertEquals(false, $postgresSource->many());
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
        $postgresSource = new PostgresSource($PDOMock->createExceptionPDO());
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

    public function testManyRecording() {

        $objectArray = [];
        for ($i = 0; $i < 5; $i++) {
            $obj = new stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = 1484784000;
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $objectArray[] = $obj;
        }

        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createFetchAllPDO($objectArray));
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
        $postgresSource = new PostgresSource($PDOMock->createFetchPDO($returnObject));
        $postgresQuery = new PostgresQuery();
        $postgresQuery->setType('SELECT');
        $postgresQuery->setCount(true);
        $postgresQuery->setTable('mockTable');
        $postgresSource->setQuery($postgresQuery);

        $this->assertEquals(3, $postgresSource->count());
    }

    public function testRawFetch() {

        $obj = new stdClass();
        $obj->id = 1;
        $obj->mockcol = 'mockcol';
        $obj->created = 1484784000;
        $obj->firstcolumn = 'firstcolumn';
        $obj->secondcolumn = 'secondcolumn';

        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createFetchPDO($obj));

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
            $obj = new stdClass();
            $obj->id = $i;
            $obj->mockcol = 'mockcol'.$i;
            $obj->created = 1484784000;
            $obj->firstcolumn = 'firstcolumn'.$i;
            $obj->secondcolumn = 'secondcolumn'.$i;
            $objectArray[] = $obj;
        }

        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createFetchAllPDO($objectArray));

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
        $postgresSource = new PostgresSource($PDOMock->createExceptionPDO());

        $sql = 'SELECT * FROM "mockTable"';
        $result = $postgresSource->raw($sql, [':id' => 1], 'fetchAll');

        $this->assertInstanceOf('\Exception', $result);
    }

    public function testPDOUtilitiesTrue() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createUtilityPDO(true));
        $postgresSource->beginTransaction();

        $this->assertEquals(1, $postgresSource->lastInsertId());
        $this->assertEquals(true, $postgresSource->commit());

        $postgresSource->beginTransaction();
        $this->assertEquals(true, $postgresSource->rollBack());
    }

    public function testPDOSchemaFunctions() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createPostgresSchemaPDO());
        $postgresSource->beginTransaction();

        $this->assertEquals('mockDatabase', $postgresSource->getDatabaseName());

        $this->assertEquals(['mockColumn'], $postgresSource->getTableColumns('mockTable'));
    }

    public function testPDOPrimaryKeyFunctions() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createPostgresPrimaryKeysPDO());
        $postgresSource->beginTransaction();

        $this->assertEquals('id', $postgresSource->getPrimaryKey('mockTable'));
    }

    public function testPDOUtilitiesFalse() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createUtilityPDO(false));

        $this->assertEquals(false, $postgresSource->commit());

        $this->assertEquals(false, $postgresSource->rollBack());
    }

    public function testPDOBindings() {
        $PDOMock = new PDOMock();
        $postgresSource = new PostgresSource($PDOMock->createBooleanPDO(true));

        $sql = 'SELECT * FROM "mockTable" WHERE "mockTable"."col1" IS :nullbind OR "mockTable"."col1" = :truebind';
        $result = $postgresSource->raw($sql, [':nullbind' => null, ':truebind' => true]);

        $this->assertEquals(true, $result);
    }
}