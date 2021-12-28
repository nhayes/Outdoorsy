<?php
namespace Outdoorsy;

require_once("Customer.php");

use \mysqli;

/**
 * Script which prints out all of Outdoorsy's customers.
 * 
 * Customers are unsorted by default, but can be sorted by first name, last name, or vehicle type
 * if the right command line options are passed in.
 * 
 * Options:
 * -f sorts customers by first name
 * -l sorts customers by last name
 * -t sorts customers by vehicle type
 */
class DisplayCustomers
{
    /**
     * Entrypoint for printing out customers.
     */
    public function run(): void
    {
        print("Fetching Outdoorsy's customers, hang tight!\n");

        $opts = $this->parseOptions();
        $sort_by = $opts["sort_by"];

        $db_conn = $this->connectToDatabase();

        $customers = $this->getAllCustomers($db_conn, $sort_by);
        print("Outdoorsy has " . count($customers) . " customers: \n");
        foreach ($customers as $customer) {
            print("- " . $customer->toString() . "\n");
        }
    }

    /**
     * Parses commandline options and returns validated values.
     * 
     * @return array
     */
    protected function parseOptions(): array
    {
        $opts = getopt("flt");

        if ($opts === false) {
            die("Couldn't parse options.");
        }

        // Note that if multiple sort options are set, we'll just sort by the
        // first one we come across in this block.
        $parsed_opts = [];
        if (isset($opts["f"])) {
            $parsed_opts["sort_by"] = SortBy::FirstName;
        } else if (isset($opts["l"])) {
            $parsed_opts["sort_by"] = SortBy::LastName;
        } else if (isset($opts["t"])) {
            $parsed_opts["sort_by"] = SortBy::VehicleType;
        } else {
            $parsed_opts["sort_by"] = SortBy::None;
        }

        return $parsed_opts;
    }

    /**
     * Creates and returns a new MySQL connection.
     * 
     * If there's an error setting up the connection, prints the error and exits the script.
     * 
     * @return mysqli
     * @throws DatabaseConnectionException
     */
    public function connectToDatabase(): mysqli
    {
        $servername = "localhost";
        $database = "outdoorsy";
        $username = "";
        $password = "";

        if ($username == "" || $password == "") {
            die("Please specify a database username and password in connectToDatabase() function.\n");
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $db_conn = new mysqli($servername, $username, $password);
        $db_conn->set_charset('utf8mb4');

        if ($db_conn->connect_error) {
            die("Failed to connect to database: " . $db_conn->connect_error);
        }

        try {
            $db_conn->select_db($database);
        } catch(\mysqli_sql_exception $e) {
            die("Please run the ProcessCustomers script first so you have customers saved!\n");
        }

        return $db_conn;
    }

    /**
     * Reads list of customers sorted in specified order and returns them as a list of Customer objects.
     * 
     * @return array customers
     */
    protected function getAllCustomers(mysqli $db_conn, int $sort_by): array
    {
        $stmt = $this->buildSelectStmt($sort_by);
        $result = $db_conn->query($stmt);

        $customers = [];
        while($row = $result->fetch_assoc()) {
            $customers[] =  new Customer(
                $row["FirstName"],
                $row["LastName"],
                $row["Email"],
                $row["Type"],
                $row["VehicleName"],
                $row["LengthFt"]
            );
        }

        return $customers;
    }

    protected function buildSelectStmt(int $sort_by): string
    {
        $stmt = "SELECT DISTINCT FirstName, LastName, Email, Type, LengthFt, Name as VehicleName
FROM Customers c
JOIN CustomerVehicles cv on c.CustomerID = cv.CustomerID
JOIN Vehicles v ON v.VehicleID = cv.VehicleID";

        switch ($sort_by) {
            case SortBy::FirstName:
                return $stmt . " ORDER BY FirstName";
            case Sortby::LastName:
                return $stmt . " ORDER BY LastName";
            case Sortby::VehicleType:
                return $stmt . " ORDER BY Type";
            default:
                return $stmt;
        }
    }
}

/** 
 * Functionally an enum definining how to sort the customer list.
 * 
 * This is necessary because PHP doesn't support enums.
 */
class SortBy
{
    const None = 0;
    const FirstName = 1;
    const LastName = 2;
    const VehicleType = 3;
}

?>