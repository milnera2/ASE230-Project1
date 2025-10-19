<?php

class Member {
    private $id;
    private $name;
    private $location;
    private $role;
    private $join_date;
    private $dues_paid;
    private $active;
    private $events_attended;
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
            'role' => $this->role,
            'join_date' => $this->join_date,
            'dues_paid' => $this->dues_paid,
            'active' => $this->active,
            'events_attended' => $this->events_attended,
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
    
    public function getRole() {
        return $this->role;
    }
    
    public function setRole($role) {
        $this->role = trim($role);
    }
    
    public function getJoinDate() {
        return $this->join_date;
    }
    
    public function setJoinDate($join_date) {
        $this->join_date = $join_date;
    }
    
    public function getDuesPaid() {
        return $this->dues_paid;
    }
    
    public function setDuesPaid($dues_paid) {
        $this->dues_paid = (bool)$dues_paid;
    }
    
    public function getActive() {
        return $this->active;
    }
    
    public function setActive($active) {
        $this->active = (bool)$active;
    }
    
    public function getEventsAttended() {
        return $this->events_attended;
    }
    
    public function setEventsAttended($events_attended) {
        $this->events_attended = (int)$events_attended;
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
