<?php

use \SypherLev\Blueprint\QueryBuilders\MySql\MySqlSource;
use \SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery;

class MySqlSourceTest extends \PHPUnit\Framework\TestCase
{
    public function testOneException() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createExceptionPDO());
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $this->assertEquals(false, $mysqlSource->one());
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
        $mysqlSource = new MySqlSource($PDOMock->createExceptionPDO());
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);
        $mysqlSource->startRecording();
        $this->assertEquals(false, $mysqlSource->many());
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
        $mysqlSource = new MySqlSource($PDOMock->createExceptionPDO());
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
        $mysqlSource = new MySqlSource($PDOMock->createFetchAllPDO($objectArray));
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
        $mysqlSource = new MySqlSource($PDOMock->createFetchPDO($returnObject));
        $mysqlQuery = new MySqlQuery();
        $mysqlQuery->setType('SELECT');
        $mysqlQuery->setCount(true);
        $mysqlQuery->setTable('mockTable');
        $mysqlSource->setQuery($mysqlQuery);

        $this->assertEquals(3, $mysqlSource->count());
    }

    public function testRawFetch() {

        $obj = new stdClass();
        $obj->id = 1;
        $obj->mockcol = 'mockcol';
        $obj->created = 1484784000;
        $obj->firstcolumn = 'firstcolumn';
        $obj->secondcolumn = 'secondcolumn';

        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createFetchPDO($obj));

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id';
        $mysqlSource->startRecording();
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetch');
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => ''
        ]];

        $this->assertEquals($obj, $result);
        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
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
        $mysqlSource = new MySqlSource($PDOMock->createFetchAllPDO($objectArray));

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id';
        $mysqlSource->startRecording();
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetchAll');
        $mysqlSource->stopRecording();

        $recordedOutput = [[
            'sql' => 'SELECT * FROM `mockTable` WHERE `mockTable`.`id` = :id',
            'binds' => [
                ':id' => 1
            ],
            'error' => ''
        ]];

        $this->assertEquals($objectArray, $result);
        $this->assertEquals($recordedOutput, $mysqlSource->getRecordedOutput());
    }

    public function testRawException() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createExceptionPDO());

        $sql = 'SELECT * FROM `mockTable`';
        $result = $mysqlSource->raw($sql, [':id' => 1], 'fetchAll');

        $this->assertInstanceOf('\Exception', $result);
    }

    public function testPDOUtilitiesTrue() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createUtilityPDO(true));
        $mysqlSource->beginTransaction();

        $this->assertEquals(1, $mysqlSource->lastInsertId());
        $this->assertEquals(true, $mysqlSource->commit());

        $mysqlSource->beginTransaction();
        $this->assertEquals(true, $mysqlSource->rollBack());
    }

    public function testPDOSchemaFunctions() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createSchemaPDO());
        $mysqlSource->beginTransaction();

        $this->assertEquals('mockDatabase', $mysqlSource->getDatabaseName());

        $columnName = new \stdClass();
        $columnName->COLUMN_NAME = 'mockColumn';

        $this->assertEquals([$columnName], $mysqlSource->getTableColumns('mockTable'));
    }

    public function testPDOUtilitiesFalse() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createUtilityPDO(false));

        $this->assertEquals(false, $mysqlSource->commit());

        $this->assertEquals(false, $mysqlSource->rollBack());
    }

    public function testPDOBindings() {
        $PDOMock = new PDOMock();
        $mysqlSource = new MySqlSource($PDOMock->createBooleanPDO(true));

        $sql = 'SELECT * FROM `mockTable` WHERE `mockTable`.`col1` IS :nullbind OR `mockTable`.`col1` = :truebind';
        $result = $mysqlSource->raw($sql, [':nullbind' => null, ':truebind' => true]);

        $this->assertEquals(true, $result);
    }
}