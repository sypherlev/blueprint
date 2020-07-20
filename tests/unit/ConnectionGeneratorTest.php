<?php

namespace Test\unit;

use \SypherLev\Blueprint\ConnectionGenerator;

class ConnectionGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testConnection() {
        $connection = new ConnectionGenerator();
        // we can't test a real PDO being returned
        // so let's attempt to generate a PDO with parameters that will pass validation
        // but still trigger a PDOException
        $connection->setConnectionParameters('mysql', 'localhost', 'database', 'user', 'password');
        try {
            $connection->generateNewPDO();
        }
        catch (\PDOException $e) {
            return;
        }
        $this->fail('ConnectionGenerator->generateNewPDO() with valid parameters failed to generate PDOException');
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