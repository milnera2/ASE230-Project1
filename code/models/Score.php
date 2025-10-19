<?php

class Score {
    private $id;
    private $student_name;
    private $class_name;
    private $assignment_score;
    private $assignment_letter_grade;
    private $class_score;
    private $class_letter_grade;
    private $created_at;
    private $updated_at;
    
    public function __construct() {
        $this->created_at = date('Y-m-d H:i:s');
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    public function toArray() {
        return [
            'id' => $this->id,
            'student_name' => $this->student_name,
            'class_name' => $this->class_name,
            'assignment_score' => $this->assignment_score,
            'assignment_letter_grade' => $this->assignment_letter_grade,
            'class_score' => $this->class_score,
            'class_letter_grade' => $this->class_letter_grade,
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
    
    public function getStudentName() {
        return $this->student_name;
    }
    
    public function setStudentName($student_name) {
        $this->student_name = trim($student_name);
    }
    
    public function getClassName() {
        return $this->class_name;
    }
    
    public function setClassName($class_name) {
        $this->class_name = trim($class_name);
    }
    
    public function getAssignmentScore() {
        return $this->assignment_score;
    }
    
    public function setAssignmentScore($assignment_score) {
        $this->assignment_score = (int)$assignment_score;
    }
    
    public function getAssignmentLetterGrade() {
        return $this->assignment_letter_grade;
    }
    
    public function setAssignmentLetterGrade($assignment_letter_grade) {
        $this->assignment_letter_grade = trim($assignment_letter_grade);
    }
    
    public function getClassScore() {
        return $this->class_score;
    }
    
    public function setClassScore($class_score) {
        $this->class_score = (int)$class_score;
    }
    
    public function getClassLetterGrade() {
        return $this->class_letter_grade;
    }
    
    public function setClassLetterGrade($class_letter_grade) {
        $this->class_letter_grade = trim($class_letter_grade);
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
