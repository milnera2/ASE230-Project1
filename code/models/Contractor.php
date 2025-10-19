<?php

class Contractor {
    private $id;
    private $name;
    private $employer;
    private $title;
    private $start_date;
    private $end_date;
    private $supervisor;
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
            'employer' => $this->employer,
            'title' => $this->title,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'supervisor' => $this->supervisor,
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
    
    public function getEmployer() {
        return $this->employer;
    }
    
    public function setEmployer($employer) {
        $this->employer = trim($employer);
    }
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($title) {
        $this->title = trim($title);
    }
    
    public function getStartDate() {
        return $this->start_date;
    }
    
    public function setStartDate($start_date) {
        $this->start_date = $start_date;
    }
    
    public function getEndDate() {
        return $this->end_date;
    }
    
    public function setEndDate($end_date) {
        $this->end_date = $end_date;
    }
    
    public function getSupervisor() {
        return $this->supervisor;
    }
    
    public function setSupervisor($supervisor) {
        $this->supervisor = trim($supervisor);
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