<?php

namespace Symphony\DAL\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers SectionQuery
 */
final class SectionQueryTest extends TestCase
{
    private $db;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->db = new \Database();
    }

    public function testDefaultSchema()
    {
        $q = new \SectionQuery($this->db);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `sections` AS `s`",
            $q->generateSQL(),
            'Simple new SectionQuery test'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testDefaultCount()
    {
        $q = new \SectionQuery($this->db, ['COUNT(*)']);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE COUNT(*) FROM `sections` AS `s`",
            $q->generateSQL(),
            'new SectionQuery test with COUNT(*) projection'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }

    public function testSectionFilter()
    {
        $q = (new \SectionQuery($this->db))->section(4);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `sections` AS `s` WHERE `s`.`id` = :s_id",
            $q->generateSQL(),
            'new SectionQuery test ->section()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values['s_id'], 's_id is 4');
        $this->assertEquals(1, count($values), '1 value');
    }

    public function testSectionsFilter()
    {
        $q = (new \SectionQuery($this->db))->sections([4, 5, 6]);
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `sections` AS `s` WHERE `s`.`id` IN (?, ?, ?)",
            $q->generateSQL(),
            'new SectionQuery test ->sections()'
        );
        $values = $q->getValues();
        $this->assertEquals(4, $values[0], 's_id[0] is 4');
        $this->assertEquals(5, $values[1], 's_id[1] is 5');
        $this->assertEquals(6, $values[2], 's_id[2] is 6');
        $this->assertEquals(3, count($values), '3 values');
    }

    public function testSort()
    {
        $q = (new \SectionQuery($this->db))->sort('x', 'DESC');
        $this->assertEquals(
            "SELECT SQL_NO_CACHE FROM `sections` AS `s` ORDER BY `s`.`x` DESC",
            $q->generateSQL(),
            'new SectionQuery with ->sort(x, DESC)'
        );
        $values = $q->getValues();
        $this->assertEquals(0, count($values), '0 value');
    }
}
