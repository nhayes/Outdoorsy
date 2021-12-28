<?php
namespace Outdoorsy;

require_once("Customer.php");

use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{

    public function testVehicleLengthToInt_returnsOnlyInts()
    {
        $result = Customer::vehicleLengthToInt("32'");
        $this->assertEquals(32, $result);

        $result = Customer::vehicleLengthToInt("45 ft long");
        $this->assertEquals(45, $result);
    }

    public function testVehicleLengthToInt_returnsFirstInt()
    {
        $result = Customer::vehicleLengthToInt("52 ft 8 in");
        $this->assertEquals(52, $result);

        $result = Customer::vehicleLengthToInt("32'1\"");
        $this->assertEquals(32, $result);
    }

}

?>