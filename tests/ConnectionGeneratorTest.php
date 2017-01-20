<?php

use \SypherLev\Blueprint\ConnectionGenerator;

class ConnectionGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testConnection() {
        $connection = new ConnectionGenerator();
        // real settings used here during testing
        $connection->setConnectionParameters('mysql', 'localhost', 'database', 'user', 'password');
        $this->assertInstanceOf('\PDO', $connection->generateNewPDO());
    }

    public function testValidationException() {
        $connection = new ConnectionGenerator();
        $connection->setConnectionParameters('', '', '', '', '');
        try {
            $connection->generateNewPDO();
        }
        catch (\Exception $e) {
            return;
        }
        $this->fail('ConnectionGenerator->generateNewPDO() with invalid parameters did not trigger an Exception');
    }
}