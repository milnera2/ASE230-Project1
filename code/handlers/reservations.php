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

require_once 'models/Reservation.php';

const BEARER_TOKEN_SECRET = 'bearerTokenKey';

function validate_bearer_token() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Authorization token not provided.']);
        exit;
    }

    $auth_header = $headers['Authorization'];
    if (strpos($auth_header, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid Authorization header format. Must be "Bearer [token]".']);
        exit;
    }

    $token = substr($auth_header, 7);

    if ($token !== BEARER_TOKEN_SECRET) {
        http_response_code(403); 
        echo json_encode(['success' => false, 'error' => 'Invalid or expired token.']);
        exit;
    }
    
    return true; 
}

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_reservation_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM reservations WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_reservations()
{
    global $conn; 

    $sql = 'SELECT id, name, location, time_start, time_end, num_guests, is_purchased, created_at, updated_at FROM reservations ORDER BY id DESC';
    $result = $conn->query($sql);
    $reservations = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['is_purchased'] = (bool)$row['is_purchased'];
            $reservations[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $reservations,
            'count' => count($reservations)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_reservation($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, location, time_start, time_end, num_guests, is_purchased, created_at, updated_at FROM reservations WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if ($reservation) {
        $reservation['is_purchased'] = (bool)$reservation['is_purchased'];
        echo json_encode([
            'success' => true,
            'data' => $reservation
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Reservation not found'
        ]);
    }
    $stmt->close();
}

function create_reservation()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['time_start']) || empty($input['time_end'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name, time_start, and time_end']);
        return;
    }

    $new_reservation = new Reservation();
    $new_reservation->setName($input['name']);
    $new_reservation->setLocation($input['location'] ?? 'Default Location');
    $new_reservation->setTimeStart($input['time_start']);
    $new_reservation->setTimeEnd($input['time_end']);
    $new_reservation->setNumGuests($input['num_guests'] ?? 1);
    $new_reservation->setIsPurchased($input['is_purchased'] ?? false);
    
    $data = $new_reservation->toArray();
    
    $sql = "INSERT INTO reservations (name, location, time_start, time_end, num_guests, is_purchased, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssiiss", 
        $data['name'], 
        $data['location'], 
        $data['time_start'], 
        $data['time_end'],
        $data['num_guests'],
        $data['is_purchased'], 
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Reservation created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_reservation($id)
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
        if (in_array($key, ['name', 'location', 'time_start', 'time_end'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'num_guests') {
            $set_clauses[] = "$key = ?";
            $types .= 'i';
            $params[] = (int)$value;
        } elseif ($key === 'is_purchased') {
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

    $sql = "UPDATE reservations SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_reservation($id); 
        } else {
            if (get_reservation_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Reservation found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Reservation not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_reservation($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, location, time_start, time_end, num_guests, is_purchased, created_at, updated_at FROM reservations WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_reservation = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_reservation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Reservation not found']);
        return;
    }
    
    $deleted_reservation['is_purchased'] = (bool)$deleted_reservation['is_purchased'];

    $sql_delete = 'DELETE FROM reservations WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Reservation deleted successfully',
            'data' => $deleted_reservation
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}

validate_bearer_token(); 

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$resource = end($request_uri);
$id = is_numeric($resource) ? (int)$resource : null;

header('Content-Type: application/json');

if ($id === null) {
    if ($method === 'GET') {
        get_all_reservations();
    } elseif ($method === 'POST') {
        create_reservation();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} else {
    if ($method === 'GET') {
        get_reservation($id);
    } elseif ($method === 'PUT') {
        update_reservation($id);
    } elseif ($method === 'DELETE') {
        delete_reservation($id);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

$conn->close();
?>