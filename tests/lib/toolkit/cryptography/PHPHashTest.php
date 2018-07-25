<?php

namespace Symphony\Crypto\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers PHPHash
 */
final class PHPHashTest extends TestCase
{
    public function testHash()
    {
        $hash = \PHPHash::hash('test', [
            'cost' => 8,
        ]);
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertLessThanOrEqual(150, strlen($hash));
    }

    public function testCompare()
    {
        $this->assertTrue(\PHPHash::compare('test', '$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq'));
        $this->assertTrue(\PHPHash::compare('test', '$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq'));
        $this->assertFalse(\PHPHash::compare('test', '$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2I'));
        $this->assertFalse(\PHPHash::compare('test', '$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq1'));
        $this->assertFalse(\PHPHash::compare('test', '2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq1'));
        $this->assertFalse(\PHPHash::compare('test', '22y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq1'));
        $this->assertFalse(\PHPHash::compare('tesu', '$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq'));
    }

    public function testRequiresMigration()
    {
        $this->assertTrue(\PHPHash::requiresMigration(''));
        $this->assertTrue(\PHPHash::requiresMigration('$2y$12$.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq'));
        $this->assertFalse(\PHPHash::requiresMigration('$2y$12$99g5Em6OHuSXzF.vRBdfau2A82y418GoUDpRHT/2J7cYAmNDMJ2Iq'));
        $this->assertTrue(\PHPHash::requiresMigration(\PHPHash::hash('test', [
            'cost' => 8,
        ])));
    }
}
