<?php

namespace Outdoorsy;

/** Logical representation of a single Outdoorsy customer, abstracted from underlying data models. */
class Customer {
    public string $fname;
    public string $lname;
    public string $email;
    public string $vehicle_type;
    public string $vehicle_name;
    public int $vehicle_length_ft;

    function __construct(
        string $fname, 
        string $lname, 
        string $email, 
        string $vehicle_type = "", 
        string $vehicle_name = "", 
        int $vehicle_length_ft = -1)
    {
        $this->fname = $fname;
        $this->lname = $lname;
        $this->email = $email;
        $this->vehicle_type = $vehicle_type;
        $this->vehicle_name = $vehicle_name;
        $this->vehicle_length_ft = $vehicle_length_ft;
    }

    /**
     * Creates a string representation of a customer object.
     * 
     * @return string
     */
    public function toString(): string
    {
        return "$this->fname $this->lname, $this->email, $this->vehicle_type, $this->vehicle_name, $this->vehicle_length_ft";
    }

    /**
     * Rudimentary function to convert a length string (eg, "32 ft", "32'", "32 ft 8 in") to an int length in feet.
     * Assumes the first int in the string is always the vehicle length in feet.
     * 
     * @param string $vehicle_len description of vehicle length
     * 
     * @return int length of vehicle in feet
     */
    public static function vehicleLengthToInt(string $vehicle_len): int
    {
        $int_len = preg_replace('/{[^0-9]}/', '', $vehicle_len);
        return intval($int_len);
    }
}

?>