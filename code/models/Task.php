<?php

class Task {
    private $id;
    private $title;
    private $time_requirement;
    private $importance;
    private $complete;
    private $completion_date;
    private $owner;
    private $created_at;
    private $updated_at;
    
    public function __construct() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'time_requirement' => $this->time_requirement,
            'importance' => $this->importance,
            'complete' => $this->complete,
            'completion_date' => $this->completion_date,
            'owner' => $this->owner,
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
    
    public function getTitle() {
        return $this->title;
    }
    
    public function setTitle($title) {
        $this->title = trim($title);
    }
    
    public function getTimeRequirement() {
        return $this->time_requirement;
    }
    
    public function setTimeRequirement($time_requirement) {
        $this->time_requirement = trim($time_requirement);
    }
    
    public function getImportance() {
        return $this->importance;
    }
    
    public function setImportance($importance) {
        $this->importance = trim($importance);
    }
    
    public function getComplete() {
        return $this->complete;
    }
    
    public function setComplete($complete) {
        $this->complete = (bool)$complete;
    }
    
    public function getCompletionDate() {
        return $this->completion_date;
    }
    
    public function setCompletionDate($completion_date) {
        $this->completion_date = $completion_date;
    }
    
    public function getOwner() {
        return $this->owner;
    }
    
    public function setOwner($owner) {
        $this->owner = trim($owner);
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
