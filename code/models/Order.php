<?php

class Order {
    private $id;
    private $customer_name;
    private $address;
    private $is_delivered;
    private $last_location;
    private $current_location;
    private $tracking_sku;
    private $created_at;
    private $updated_at;
    
    public function __construct() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
        $this->is_delivered = false; 
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'customer_name' => $this->customer_name,
            'address' => $this->address,
            'is_delivered' => $this->is_delivered,
            'last_location' => $this->last_location,
            'current_location' => $this->current_location,
            'tracking_sku' => $this->tracking_sku,
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
    
    public function getCustomerName() {
        return $this->customer_name;
    }
    
    public function setCustomerName($name) {
        $this->customer_name = trim($name);
    }
    
    public function getAddress() {
        return $this->address;
    }
    
    public function setAddress($address) {
        $this->address = trim($address);
    }
    
    public function getIsDelivered() {
        return $this->is_delivered;
    }
    
    public function setIsDelivered($delivered) {
        $this->is_delivered = (bool)$delivered;
    }
    
    public function getLastLocation() {
        return $this->last_location;
    }
    
    public function setLastLocation($location) {
        $this->last_location = trim($location);
    }
    
    public function getCurrentLocation() {
        return $this->current_location;
    }
    
    public function setCurrentLocation($location) {
        $this->current_location = trim($location);
    }
    
    public function getTrackingSKU() {
        return $this->tracking_sku;
    }
    
    public function setTrackingSKU($SKU) {
        $this->tracking_sku = trim($SKU);
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