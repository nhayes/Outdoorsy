<?php
namespace Outdoorsy;

require_once("DisplayCustomers.php");

use PHPUnit\Framework\TestCase;
use \Phake;

class DisplayCustomersTest extends TestCase
{

    public function testBuildSelectStmt_unsorted()
    {
        // Partial mocks are necessary to test protected methods. Unstubbed methods in partial mocks
        // run normally.
        $display_customers = Phake::partialMock(DisplayCustomers::class);

        $result = Phake::makeVisible($display_customers)->buildSelectStmt(SortBy::None);

        $this->assertStringNotContainsString("ORDER BY", $result, "should be unordered");
    }


    public function testBuildSelectStmt_orderByFirstName()
    {
        // Partial mocks are necessary to test protected methods. Unstubbed methods in partial mocks
        // run normally.
        $display_customers = Phake::partialMock(DisplayCustomers::class);

        $result = Phake::makeVisible($display_customers)->buildSelectStmt(SortBy::FirstName);

        $this->assertStringContainsString("ORDER BY FirstName", $result, "should be sorted by first name");
    }

    public function testBuildSelectStmt_orderByLastName()
    {
        // Partial mocks are necessary to test protected methods. Unstubbed methods in partial mocks
        // run normally.
        $display_customers = Phake::partialMock(DisplayCustomers::class);

        $result = Phake::makeVisible($display_customers)->buildSelectStmt(SortBy::VehicleType);

        $this->assertStringContainsString("ORDER BY Type", $result, "should be sorted by vehicle type");
    }

}

?>