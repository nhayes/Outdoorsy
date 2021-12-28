<?php
namespace Outdoorsy;

use \mysqli;

require_once("Customer.php");

/**
 * Script that reads a specified file where each line represents one of Outdoorsy's customers and
 * saves each customer to Outdoorsy's database.
 * 
 * Options:
 * -f or --filename: name of text file with customer data. The file should exist in the same 
 *                   directory as the script.
 * -s or --separator: separator character used in customer data. Defaults to ',' if unspecified.
 */
class ProcessCustomers {

    const NUM_REQUIRED_FIELDS = 6; // first name, last name, email, vehicle type, vehicle name, vehicle length

    public function run(): void
    {
        print(
            "Welcome! This tool processes customers from a local file and saves them to Outdoorsy's tables.\n\n"
        );

        $opts = $this->parseOptions();
        $filename = $opts["file"];
        $separator = $opts["separator"];

        $db_conn = $this->connectToDatabase();
        $this->createTablesIfNecessary($db_conn);

        print("Reading customer info from '$filename', splitting lines on '$separator'.\n");

        $failed_lines = $this->parseFileAndSaveCustomers($db_conn, $filename, $separator);

        print("Saved customers from input file.\n");
        if (count($failed_lines) > 0) {
            print("Failed to parse " . count($failed_lines) . " line(s).\n");
            print("An example of a failed line is '$failed_lines[0]'.\n");
        }

        mysqli_close($db_conn);
        print("\nFinished task: cleaning up and exiting.\n");
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

        // Check if a database named Outdoorsy exists, and create it if not.
        try {
            $db_conn->select_db("Outdoorsy");
        } catch(\mysqli_sql_exception $e) {
            $result = $db_conn->query("CREATE DATABASE Outdoorsy");
            if (!$result) {
                die("Error creating database: " . $db_conn->connect_error);
            }
        }

        return $db_conn;
    }

    /**
     * Creates Customer, Vehicle, and CustomerVehicle tables if they don't already exist.
     * If there's an error creating a table, prints the issue and exits the script.
     * 
     * @param mysqli $db_conn
     */
    protected function createTablesIfNecessary(mysqli $db_conn): void
    {
        $result = $db_conn->query("
CREATE TABLE IF NOT EXISTS Customers
(
CustomerID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
LastName varchar(255) NOT NULL,
FirstName varchar(255) NOT NULL,
Email varchar(255) NOT NULL
);");
        if (!$result) {
            die("Couldn't create Customers table: " . $db_conn->error);
        }

        $result = $db_conn->query("
CREATE TABLE IF NOT EXISTS Vehicles
(
VehicleID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
Name varchar(255) NOT NULL,
Type varchar(255) NOT NULL,
LengthFt int NOT NULL
);");
        if (!$result) {
            die("Couldn't create Vehicles table: " . $db_conn->error);
        }

        $result = $db_conn->query("
CREATE TABLE IF NOT EXISTS CustomerVehicles
(
CustomerID int NOT NULL,
VehicleID int NOT NULL
);");
        if (!$result) {
            die("Couldn't create CustomerVehicles table: " . $db_conn->error);
        }
    }

    /**
     * Parses commandline options and returns validated values. If required options aren't 
     * present or options can't be processed, exits the script with an error message.
     * 
     * @return array
     */
    protected function parseOptions(): ?array
    {
        $short_options = "f:s:";
        $long_options = ["filename:separator"];
        $opts = getopt($short_options, $long_options);

        if ($opts === false) {
            die("Couldn't parse options, please try again.");
        }

        $parsed_opts = [];
        if (isset($opts["f"]) || isset($opts["filename"])) {
            $parsed_opts["file"] = isset($opts["f"]) ? $opts["f"] : $opts["filename"];
        } else {
            die("File name must be specified.\n");
        }

        if (isset($opts["s"]) || isset($opts["separator"])) {
            $parsed_opts["separator"] = isset($opts["s"]) ? $opts["s"] : $opts["separator"];
        } else {
            $parsed_opts["separator"] = ",";
        }

        return $parsed_opts;
    }

    /**
     * Processes each line in the given file and saves all described customers. Returns a
     * list of unprocessable lines.
     * 
     * @param mysqli $db_conn   Database connection
     * @param string $separator Delimiter between fields
     * 
     * @return array failed lines
     */
    protected function parseFileAndSaveCustomers(mysqli $db_conn, string $filename, string $separator): array
    {
        $file = fopen($filename, "r");
        if (!$file) {
            die("Error opening $filename. Does it exist in this directory?\n");
        }

        $failed_lines = [];
        while (($line = fgets($file)) !== false) {
            $customer = $this->parseLine($line, $separator);

            if (is_null($customer)) {
                // Something went wrong while attempting to process this line.
                $failed_lines[] = $line;
                continue;
            }

            $this->insertCustomer($db_conn, $customer);
        }

        fclose($file);

        return $failed_lines;
    }

    /**
     * Processes a single line from the given input file. 
     * 
     * Assumes that a line contains all data that we expect for each customer.
     * 
     * @param string $line      Line representing a single customer
     * @param string $separator Delimiter between fields
     * 
     * @return Customer
     */
    protected function parseLine(string $line, string $separator): ?Customer
    {
        $info = explode($separator, $line);

        if (count($info) != self::NUM_REQUIRED_FIELDS) {
            return null;
        }

        $vehicle_len = Customer::vehicleLengthToInt($info[5]);

        return new Customer(
            $info[0],
            $info[1],
            $info[2],
            $info[3],
            $info[4],
            $vehicle_len
        );
    }

    /**
     * Inserts the customer's data into Outdoorsy's tables. Will insert duplicates if this customer
     * already exists.
     * 
     * @param mysqli $db_conn
     * @param Customer $customer
     */
    public function insertCustomer(mysqli $db_conn, Customer $customer): void
    {
        $db_conn->begin_transaction();

        $cust_stmt = $db_conn->prepare(
            "INSERT INTO Customers(FirstName, LastName, Email) VALUE(?, ?, ?)"
        );
        $cust_stmt->bind_param("sss", $customer->fname, $customer->lname, $customer->email);
        $cust_stmt->execute();

        // insert_id returns the autogenerated id of the most recently inserted row
        $cust_id = $db_conn->insert_id;

        $vehicle_stmt = $db_conn->prepare(
            "INSERT INTO Vehicles(Name, Type, LengthFt) VALUE(?, ?, ?)"
        );
        $vehicle_stmt->bind_param(
            "sss",
            $customer->vehicle_name,
            $customer->vehicle_type,
            $customer->vehicle_length_ft
        );
        $vehicle_stmt->execute();

        // insert_id returns the autogenerated id of the most recently inserted row
        $v_id = $db_conn->insert_id;
        $cust_vehicle_stmt = $db_conn->prepare(
            "INSERT INTO CustomerVehicles(CustomerID, VehicleID) VALUE(?, ?)"
        );
        $cust_vehicle_stmt->bind_param("ss", $cust_id, $v_id);
        $cust_vehicle_stmt->execute();

        $db_conn->commit();
    }
}
?>