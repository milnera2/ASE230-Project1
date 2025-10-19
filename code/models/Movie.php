<?php

class Movie {
    private $id;
    private $name;
    private $producer;
    private $actors;
    private $genre;
    private $year;
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
            'producer' => $this->producer,
            'actors' => $this->actors,
            'genre' => $this->genre,
            'year' => $this->year,
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
    
    public function getProducer() {
        return $this->producer;
    }
    
    public function setProducer($producer) {
        $this->producer = trim($producer);
    }
    
    public function getActors() {
        return $this->actors;
    }
    
    public function setActors($actors) {
        $this->actors = trim($actors);
    }
    
    public function getGenre() {
        return $this->genre;
    }
    
    public function setGenre($genre) {
        $this->genre = trim($genre);
    }
    
    public function getYear() {
        return $this->year;
    }
    
    public function setYear($year) {
        $this->year = (int)$year;
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
