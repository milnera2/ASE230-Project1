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

require_once 'models/Task.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_task_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM tasks WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_tasks()
{
    global $conn; 

    $sql = 'SELECT id, title, time_requirement, importance, complete, completion_date, owner, created_at, updated_at FROM tasks ORDER BY id DESC';
    $result = $conn->query($sql);
    $tasks = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['complete'] = (bool)$row['complete'];
            $tasks[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $tasks,
            'count' => count($tasks)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_task($id)
{
    global $conn;
    
    $sql = 'SELECT id, title, time_requirement, importance, complete, completion_date, owner, created_at, updated_at FROM tasks WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if ($task) {
        $task['complete'] = (bool)$task['complete'];
        echo json_encode([
            'success' => true,
            'data' => $task
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Task not found'
        ]);
    }
    $stmt->close();
}

function create_task()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['title']) || empty($input['owner'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: title and owner']);
        return;
    }

    $new_task = new Task();
    $new_task->setTitle($input['title']);
    $new_task->setTimeRequirement($input['time_requirement'] ?? 'N/A');
    $new_task->setImportance($input['importance'] ?? 'Medium');
    $new_task->setComplete($input['complete'] ?? false);
    $new_task->setCompletionDate($input['completion_date'] ?? null);
    $new_task->setOwner($input['owner']);
    
    $data = $new_task->toArray();
    
    $sql = "INSERT INTO tasks (title, time_requirement, importance, complete, completion_date, owner, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $completion_date_value = $data['completion_date'] === null ? NULL : $data['completion_date'];
    
    $stmt->bind_param("sssissss", 
        $data['title'], 
        $data['time_requirement'], 
        $data['importance'], 
        $data['complete'],
        $completion_date_value,
        $data['owner'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;
        $data['complete'] = (bool)$data['complete']; 

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_task($id)
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
        if (in_array($key, ['title', 'time_requirement', 'importance', 'owner', 'updated_at'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'completion_date') {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value ?? NULL;
        } elseif ($key === 'complete') {
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

    $sql = "UPDATE tasks SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    
    $bind_params_ref = [];
    foreach ($bind_params as $key => &$value) {
        $bind_params_ref[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params_ref);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_task($id); 
        } else {
            if (get_task_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Task found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Task not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_task($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, title, time_requirement, importance, complete, completion_date, owner, created_at, updated_at FROM tasks WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_task = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_task) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        return;
    }
    
    $deleted_task['complete'] = (bool)$deleted_task['complete'];

    $sql_delete = 'DELETE FROM tasks WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Task deleted successfully',
            'data' => $deleted_task
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>