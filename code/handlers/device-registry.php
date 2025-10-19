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

require_once 'models/Device.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_device_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM devices WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_devices()
{
    global $conn; 

    $sql = 'SELECT id, name, location, active, manufacturer, age, usage, created_at, updated_at FROM devices ORDER BY id DESC';
    $result = $conn->query($sql);
    $devices = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['active'] = (bool)$row['active'];
            $devices[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $devices,
            'count' => count($devices)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_device($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, location, active, manufacturer, age, usage, created_at, updated_at FROM devices WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $device = $result->fetch_assoc();

    if ($device) {
        $device['active'] = (bool)$device['active'];
        echo json_encode([
            'success' => true,
            'data' => $device
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Device not found'
        ]);
    }
    $stmt->close();
}

function create_device()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['manufacturer'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name and manufacturer']);
        return;
    }

    $new_device = new Device();
    $new_device->setName($input['name']);
    $new_device->setLocation($input['location'] ?? 'Unknown');
    $new_device->setActive($input['active'] ?? true); 
    $new_device->setManufacturer($input['manufacturer']);
    $new_device->setAge($input['age'] ?? 0);
    $new_device->setUsage($input['usage'] ?? 'Normal');
    
    $data = $new_device->toArray();
    
    $sql = "INSERT INTO devices (name, location, active, manufacturer, age, usage, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssisisss", 
        $data['name'], 
        $data['location'], 
        $data['active'], 
        $data['manufacturer'],
        $data['age'],
        $data['usage'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Device created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_device($id)
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
        if (in_array($key, ['name', 'location', 'manufacturer', 'usage'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'age') {
            $set_clauses[] = "$key = ?";
            $types .= 'i';
            $params[] = (int)$value;
        } elseif ($key === 'active') {
            $set_clauses[] = "$key = ?";
            $types .= 'i';
            $params[] = (bool)$value;
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

    $sql = "UPDATE devices SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_device($id); 
        } else {
            if (get_device_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Device found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Device not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_device($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, location, active, manufacturer, age, usage, created_at, updated_at FROM devices WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_device = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_device) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Device not found']);
        return;
    }
    
    $deleted_device['active'] = (bool)$deleted_device['active'];

    $sql_delete = 'DELETE FROM devices WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Device deleted successfully',
            'data' => $deleted_device
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>