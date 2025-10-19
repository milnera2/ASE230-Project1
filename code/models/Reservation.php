<?php

class Reservation {
    private $id;
    private $name;
    private $location;
    private $time_start;
    private $time_end;
    private $num_guests;
    private $is_purchased;
    private $created_at;
    private $updated_at;
    
    public function __construct() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        $this->is_purchased = false; 
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'is_purchased' => $this->is_purchased,
            'time_start' => $this->time_start,
            'time_end' => $this->time_end,
            'num_guests' => $this->num_guests,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($id) {
        $this->id = (int)$id;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function setName($name) {
        $this->name = trim($name);
    }
    
    public function getLocation() {
        return $this->location;
    }
    
    public function setLocation($location) {
        $this->location = trim($location);
    }
    
    public function getIsPurchased() {
        return $this->is_purchased;
    }

    public function setIsPurchased($is_purchased) {
        $this->is_purchased = (bool)$is_purchased;
    }

    public function getTimeStart() {
        return $this->time_start;
    }

    public function setTimeStart($time_start) {
        $this->time_start = $time_start;
    }

    public function getTimeEnd() {
        return $this->time_end;
    }

    public function setTimeEnd($time_end) {
        $this->time_end = $time_end;
    }
    
    public function getNumGuests() {
        return $this->num_guests;
    }
    
    public function setNumGuests($num_guests) {
        $this->num_guests = (int)$num_guests;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
    }
}
