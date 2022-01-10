<?php
namespace Outdoorsy;

require_once("ProcessCustomers.php");

use PHPUnit\Framework\TestCase;
use \Phake;

class ProcessCustomersTest extends TestCase
{

    public function testParseLine_valid()
    {
        // Partial mocks are necessary to test protected methods. Unstubbed methods in partial mocks
        // run normally.
        $process_customers = Phake::partialMock(ProcessCustomers::class);

        $line = "Aladdin,NoLastName,aladdin@prince.org,rug,Fuzzy,6'";
        $aladdin = new Customer("Aladdin", "NoLastName", "aladdin@prince.org", "rug", "Fuzzy", 6);
        $result = Phake::makeVisible($process_customers)->parseLine($line, ",");
        $this->assertEquals(
            $aladdin,
            $result,
            "valid line becomes Customer object"
        );
    }

    public function testParseLine_invalid()
    {
        // Partial mocks are necessary to test protected methods. Unstubbed methods in partial mocks
        // run normally.
        $process_customers = Phake::partialMock(ProcessCustomers::class);

        $line = "Aladdin,NoLastName,aladdin@prince.org,rug,Fuzzy,6'";
        $result = Phake::makeVisible($process_customers)->parseLine($line, "|");
        $this->assertNull(
            $result,
            "valid line with mismatched separator fails"
        );

        $line = "not,enough,data";
        $result = Phake::makeVisible($process_customers)->parseLine($line, ",");
        $this->assertNull(
            $result,
            "line with not enough fields fails"
        );

        $line = "w,a,y,t,o,o,m,a,n,y,fields,here,wow,so,much,data";
        $result = Phake::makeVisible($process_customers)->parseLine($line, ",");
        $this->assertNull(
            $result,
            "line with too many fields fails"
        );
    }

}

?>