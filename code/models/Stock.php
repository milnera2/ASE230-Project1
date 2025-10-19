<?php

class Stock {
    private $id;
    private $name;
    private $aisle;
    private $quantity_store;
    private $quantity_storage;
    private $price;
    private $SKU;
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
            'aisle' => $this->aisle,
            'quantity_store' => $this->quantity_store,
            'quantity_storage' => $this->quantity_storage,
            'price' => $this->price,
            'SKU' => $this->SKU,
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
    
    public function getAisle() {
        return $this->aisle;
    }
    
    public function setAisle($aisle) {
        $this->aisle = trim($aisle);
    }
    
    public function getQuantityStore() {
        return $this->quantity_store;
    }
    
    public function setQuantityStore($quantity_store) {
        $this->quantity_store = (int)$quantity_store;
    }
    
    public function getQuantityStorage() {
        return $this->quantity_storage;
    }
    
    public function setQuantityStorage($quantity_storage) {
        $this->quantity_storage = (int)$quantity_storage;
    }
    
    public function getPrice() {
        return $this->price;
    }
    
    public function setPrice($price) {
        $this->price = (string)$price;
    }
    
    public function getSKU() {
        return $this->SKU;
    }
    
    public function setSKU($SKU) {
        $this->SKU = trim($SKU);
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
