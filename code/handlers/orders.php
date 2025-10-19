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

require_once 'models/Order.php';

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

function get_order_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM orders WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_orders()
{
    global $conn; 

    $sql = 'SELECT id, customer_name, address, is_delivered, last_location, current_location, tracking_sku, created_at, updated_at FROM orders ORDER BY id DESC';
    $result = $conn->query($sql);
    $orders = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['is_delivered'] = (bool)$row['is_delivered'];
            $orders[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $orders,
            'count' => count($orders)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_order($id)
{
    global $conn;
    
    $sql = 'SELECT id, customer_name, address, is_delivered, last_location, current_location, tracking_sku, created_at, updated_at FROM orders WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if ($order) {
        $order['is_delivered'] = (bool)$order['is_delivered'];
        echo json_encode([
            'success' => true,
            'data' => $order
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Order not found'
        ]);
    }
    $stmt->close();
}

function create_order()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['customer_name']) || empty($input['address'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: customer_name and address']);
        return;
    }

    $new_order = new Order();
    $new_order->setCustomerName($input['customer_name']);
    $new_order->setAddress($input['address']);
    $new_order->setIsDelivered($input['is_delivered'] ?? false);
    $new_order->setLastLocation($input['last_location'] ?? 'Warehouse');
    $new_order->setCurrentLocation($input['current_location'] ?? 'Processing');
    $new_order->setTrackingSKU($input['tracking_sku'] ?? uniqid('SKU_'));
    
    $data = $new_order->toArray();
    
    $sql = "INSERT INTO orders (customer_name, address, is_delivered, last_location, current_location, tracking_sku, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssisssss", 
        $data['customer_name'], 
        $data['address'], 
        $data['is_delivered'], 
        $data['last_location'],
        $data['current_location'],
        $data['tracking_sku'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_order($id)
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
        if (in_array($key, ['customer_name', 'address', 'last_location', 'current_location', 'tracking_sku'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'is_delivered') {
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

    $sql = "UPDATE orders SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_order($id); 
        } else {
            if (get_order_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Order found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Order not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_order($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, customer_name, address, is_delivered, last_location, current_location, tracking_sku, created_at, updated_at FROM orders WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_order = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_order) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        return;
    }
    
    $deleted_order['is_delivered'] = (bool)$deleted_order['is_delivered'];

    $sql_delete = 'DELETE FROM orders WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Order deleted successfully',
            'data' => $deleted_order
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
        get_all_orders();
    } elseif ($method === 'POST') {
        create_order();
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} else {
    if ($method === 'GET') {
        get_order($id);
    } elseif ($method === 'PUT') {
        update_order($id);
    } elseif ($method === 'DELETE') {
        delete_order($id);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

$conn->close();
?>