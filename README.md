Welcome to the Outdoorsy customer processing tool!

This tool is composed of two scripts:
1. `ProcessCustomers.php`, which parses an input file and saves the described customers to local MySQL tables.
2. `DisplayCustomers.php`, which prints out a list of all of Outdoorsy's customers.

Outdoorsy's customer data is stored in three database tables: Customers, Vehicles, and CustomerVehicles.
CustomerVehicles maps customer and vehicle IDs, for flexibility around customers either sharing a vehicle
or owning several vehicles.

## Setup
You'll need PHP 8 and associated tools (PHPUnit, Composer) installed on your computer as well as a
running MySQL database with a username and password that you can access.

To connect the scripts to your MySQL instance, you'll need to hardcode your username and password in the
'connectToDatabase' function in each script. Obviously this is not suitable for production! It does, however,
get us up and running quickly in this quick-and-dirty program.

Dependencies should work out of the box, but if something seems broken you can see if resetting composer
will fix it via:
```
composer dump-autoload
```

## ProcessCustomers
ProcessCustomers has two options:
- `-f` or `--filename`: required. The file to read customers from.
- `-s` or `--separator`: optional (defaults to ','). Delimiter between customer data on each line.

You can run ProcessCustomers like:
```
php process_customers.php -f customers.txt -s |
```


## DisplayCustomers
DisplayCustomers prints out a list of all customers. This list is unsorted by default, but you can use
commandline options to specify how to sort the list:
- `-f`: sort customers by first name
- `-l`: sort customers by last name
- `-t`: sort customers by vehicle type

You can run DisplayCustomers like:
```
php display_customers.php -t
```

Note that behavior is not guaranteed if multiple sort options are specified.


## Tests
You can run tests using phpunit and specifying the test file, like so:

```
phpunit --bootstrap vendor/autoload.php DisplayCustomersTest.php
```

`--bootstrap vendor/autoload.php` ensures that vendored dependencies are loaded into the test file.

Note that using PHP's built-in functions to open files and interact with MySQL significantly increases the
complexity of unit testing associated code. A good next step to extend and improve this project would be
including database and file system wrappers for PHP's built-in functions to make file/database interaction
mockable and therefore unit testable.


## Assumptions
- Each line in the ProcessCustomers input file will contain the following fields, in order: first name, last name,
  email, vehicle type, vehicle name, and vehicle length.
- The vehicle length field:
    - Input vehicle length will always be specified in feet and stored as the integer number of feet
    - Length in feet will always be the first integer in the vehicle length field (eg, "32 ft 8 in", not "8 in 32 ft")