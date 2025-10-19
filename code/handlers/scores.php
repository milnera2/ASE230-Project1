<?php
$servername = "localhost";
$username = "root";
$password = "freezerburn15";
$dbname = "project1apis";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Global Database Connection failed: ' . $conn->connect_error]);
    exit;
}

require_once 'models/Score.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_score_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM scores WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_scores()
{
    global $conn; 

    $sql = 'SELECT id, student_name, class_name, assignment_score, assignment_letter_grade, class_score, class_letter_grade, created_at, updated_at FROM scores ORDER BY id DESC';
    $result = $conn->query($sql);
    $scores = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $scores[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $scores,
            'count' => count($scores)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_score($id)
{
    global $conn;
    
    $sql = 'SELECT id, student_name, class_name, assignment_score, assignment_letter_grade, class_score, class_letter_grade, created_at, updated_at FROM scores WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $score = $result->fetch_assoc();

    if ($score) {
        echo json_encode([
            'success' => true,
            'data' => $score
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Score record not found'
        ]);
    }
    $stmt->close();
}

function create_score()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['student_name']) || empty($input['class_name']) || !isset($input['assignment_score']) || !isset($input['class_score'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: student_name, class_name, assignment_score, and class_score']);
        return;
    }

    $new_score = new Score();
    $new_score->setStudentName($input['student_name']);
    $new_score->setClassName($input['class_name']);
    $new_score->setAssignmentScore($input['assignment_score']);
    $new_score->setAssignmentLetterGrade($input['assignment_letter_grade'] ?? 'N/A'); 
    $new_score->setClassScore($input['class_score']);
    $new_score->setClassLetterGrade($input['class_letter_grade'] ?? 'N/A');
    
    $data = $new_score->toArray();
    
    $sql = "INSERT INTO scores (student_name, class_name, assignment_score, assignment_letter_grade, class_score, class_letter_grade, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssisisss", 
        $data['student_name'], 
        $data['class_name'], 
        $data['assignment_score'], 
        $data['assignment_letter_grade'],
        $data['class_score'],
        $data['class_letter_grade'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Score record created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_score($id)
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No data provided for update']);
        return;
    }

    $set_clauses = [];
    $params = [];
    $types = '';
    
    $input['updated_at'] = date('Y-m-d H:i:s');
    
    foreach ($input as $key => $value) {
        if (in_array($key, ['student_name', 'class_name', 'assignment_letter_grade', 'class_letter_grade', 'updated_at'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif (in_array($key, ['assignment_score', 'class_score'])) {
            $set_clauses[] = "$key = ?";
            $types .= 'i';
            $params[] = (int)$value;
        }
    }
    
    if (empty($set_clauses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No valid fields provided for update']);
        return;
    }

    $types .= 'i'; 
    $params[] = $id; 

    $sql = "UPDATE scores SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_score($id); 
        } else {
            if (get_score_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Score record found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Score record not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_score($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, student_name, class_name, assignment_score, assignment_letter_grade, class_score, class_letter_grade, created_at, updated_at FROM scores WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_score = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_score) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Score record not found']);
        return;
    }

    $sql_delete = 'DELETE FROM scores WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Score record deleted successfully',
            'data' => $deleted_score
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>