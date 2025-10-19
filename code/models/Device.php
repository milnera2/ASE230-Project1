<?php

class Device {
    private $id;
    private $name;
    private $location;
    private $active;
    private $manufacturer;
    private $age;
    private $usage;
    private $created_at;
    private $updated_at;
    
    public function __construct() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'active' => $this->active,
            'manufacturer' => $this->manufacturer,
            'age' => $this->age,
            'usage' => $this->usage,
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
    
    public function getActive() {
        return $this->active;
    }
    
    public function setActive($active) {
        $this->active = (bool)$active;
    }
    
    public function getManufacturer() {
        return $this->manufacturer;
    }
    
    public function setManufacturer($manufacturer) {
        $this->manufacturer = trim($manufacturer);
    }
    
    public function getAge() {
        return $this->age;
    }
    
    public function setAge($age) {
        $this->age = (int)$age;
    }
    
    public function getUsage() {
        return $this->usage;
    }
    
    public function setUsage($usage) {
        $this->usage = trim($usage);
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