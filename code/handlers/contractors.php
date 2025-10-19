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

require_once 'models/Contractor.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_contractor_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM contractors WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_contractors()
{
    global $conn; 

    $sql = 'SELECT id, name, employer, title, start_date, end_date, supervisor, created_at, updated_at FROM contractors ORDER BY id DESC';
    $result = $conn->query($sql);
    $contractors = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $contractors[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $contractors,
            'count' => count($contractors)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_contractor($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, employer, title, start_date, end_date, supervisor, created_at, updated_at FROM contractors WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contractor = $result->fetch_assoc();

    if ($contractor) {
        echo json_encode([
            'success' => true,
            'data' => $contractor
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Contractor not found'
        ]);
    }
    $stmt->close();
}

function create_contractor()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['employer']) || empty($input['title'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name, employer, and title']);
        return;
    }

    $new_contractor = new Contractor();
    $new_contractor->setName($input['name']);
    $new_contractor->setEmployer($input['employer']);
    $new_contractor->setTitle($input['title']);
    $new_contractor->setStartDate($input['start_date'] ?? null);
    $new_contractor->setEndDate($input['end_date'] ?? null);
    $new_contractor->setSupervisor($input['supervisor'] ?? null);
    
    $data = $new_contractor->toArray();
    
    $sql = "INSERT INTO contractors (name, employer, title, start_date, end_date, supervisor, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssssss", 
        $data['name'], 
        $data['employer'], 
        $data['title'], 
        $data['start_date'],
        $data['end_date'],
        $data['supervisor'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Contractor created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_contractor($id)
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
        if (in_array($key, ['name', 'employer', 'title', 'supervisor', 'start_date', 'end_date'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'updated_at') {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        }
    }
    
    if (empty($set_clauses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No valid fields provided for update']);
        return;
    }

    $types .= 'i'; 
    $params[] = $id; 

    $sql = "UPDATE contractors SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_contractor($id); 
        } else {
            if (get_contractor_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Contractor found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Contractor not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_contractor($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, employer, title, start_date, end_date, supervisor, created_at, updated_at FROM contractors WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_contractor = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_contractor) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Contractor not found']);
        return;
    }

    $sql_delete = 'DELETE FROM contractors WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Contractor deleted successfully',
            'data' => $deleted_contractor
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>
