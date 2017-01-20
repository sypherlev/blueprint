<?php

use PHPUnit\Framework\TestCase;

class PDOMock extends TestCase
{
    public function createFetchPDO(stdClass $expectedOutput) {
        $mockPDOStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $mockPDOStatement->expects($this->any())
            ->method('fetch')
            ->will($this->returnValue($expectedOutput));

        $mockPDOStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(true));

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $mockPDO->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($mockPDOStatement));
        return $mockPDO;
    }

    public function createFetchAllPDO(Array $expectedOutput) {
        $mockPDOStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $mockPDOStatement->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($expectedOutput));

        $mockPDOStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(true));

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $mockPDO->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($mockPDOStatement));
        return $mockPDO;
    }

    public function createBooleanPDO($boolean) {
        $mockPDOStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $mockPDOStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($boolean));
        $mockPDOStatement->expects($this->any())
            ->method('errorInfo')
            ->will($this->returnValue('00000'));

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $mockPDO->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($mockPDOStatement));
        return $mockPDO;
    }

    public function createExceptionPDO() {
        $mockPDOStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $mockPDOStatement->expects($this->any())
            ->method('execute')
            ->will($this->throwException(new \Exception()));

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();
        $mockPDO->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($mockPDOStatement));
        return $mockPDO;
    }

    public function createUtilityPDO($boolean) {

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();

        $mockPDO->expects($this->once())
            ->method('lastInsertId')
            ->will($this->returnValue(1));

        $mockPDO->expects($this->once())
            ->method('commit')
            ->will($this->returnValue($boolean));

        $mockPDO->expects($this->once())
            ->method('rollBack')
            ->will($this->returnValue($boolean));

        return $mockPDO;
    }

    public function createSchemaPDO() {
        $mockPDOStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $mockPDOStatement->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue('mockDatabase'));

        $columnName = new \stdClass();
        $columnName->COLUMN_NAME = 'mockColumn';

        $mockPDOStatement->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue([$columnName]));

        $mockPDOStatement->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(true));

        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();

        $mockPDO->expects($this->any())
            ->method('query')
            ->will($this->returnValue($mockPDOStatement));

        $mockPDO->expects($this->once())
            ->method('prepare')
            ->will($this->returnValue($mockPDOStatement));

        return $mockPDO;
    }

    public function createConnectionPDO() {
        $mockPDO = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()
            ->getMock();

        return $mockPDO;
    }
}