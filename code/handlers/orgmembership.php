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

require_once 'models/Member.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_member_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM members WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_members()
{
    global $conn; 

    $sql = 'SELECT id, name, location, role, join_date, dues_paid, active, events_attended, created_at, updated_at FROM members ORDER BY id DESC';
    $result = $conn->query($sql);
    $members = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['dues_paid'] = (bool)$row['dues_paid'];
            $row['active'] = (bool)$row['active'];
            $members[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $members,
            'count' => count($members)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_member($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, location, role, join_date, dues_paid, active, events_attended, created_at, updated_at FROM members WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();

    if ($member) {
        $member['dues_paid'] = (bool)$member['dues_paid'];
        $member['active'] = (bool)$member['active'];
        echo json_encode([
            'success' => true,
            'data' => $member
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Member not found'
        ]);
    }
    $stmt->close();
}

function create_member()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['role']) || empty($input['join_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name, role, and join_date']);
        return;
    }

    $new_member = new Member();
    $new_member->setName($input['name']);
    $new_member->setLocation($input['location'] ?? 'Unknown');
    $new_member->setRole($input['role']);
    $new_member->setJoinDate($input['join_date']);
    $new_member->setDuesPaid($input['dues_paid'] ?? false);
    $new_member->setActive($input['active'] ?? true);
    $new_member->setEventsAttended($input['events_attended'] ?? 0);
    
    $data = $new_member->toArray();
    
    $sql = "INSERT INTO members (name, location, role, join_date, dues_paid, active, events_attended, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssiiiss", 
        $data['name'], 
        $data['location'], 
        $data['role'], 
        $data['join_date'],
        $data['dues_paid'],
        $data['active'],
        $data['events_attended'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Member created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_member($id)
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
        if (in_array($key, ['name', 'location', 'role', 'join_date'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif (in_array($key, ['dues_paid', 'active', 'events_attended'])) {
            $set_clauses[] = "$key = ?";
            $types .= 'i';
            $params[] = (int)$value;
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

    $sql = "UPDATE members SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_member($id); 
        } else {
            if (get_member_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Member found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Member not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_member($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, location, role, join_date, dues_paid, active, events_attended, created_at, updated_at FROM members WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_member = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_member) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        return;
    }
    
    $deleted_member['dues_paid'] = (bool)$deleted_member['dues_paid'];
    $deleted_member['active'] = (bool)$deleted_member['active'];

    $sql_delete = 'DELETE FROM members WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Member deleted successfully',
            'data' => $deleted_member
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>