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

require_once 'models/Stock.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_stock_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM stocks WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_stocks()
{
    global $conn; 

    $sql = 'SELECT id, name, aisle, quantity_store, quantity_storage, price, SKU, created_at, updated_at FROM stocks ORDER BY id DESC';
    $result = $conn->query($sql);
    $stocks = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $stocks[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $stocks,
            'count' => count($stocks)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_stock($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, aisle, quantity_store, quantity_storage, price, SKU, created_at, updated_at FROM stocks WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();

    if ($stock) {
        echo json_encode([
            'success' => true,
            'data' => $stock
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Stock item not found'
        ]);
    }
    $stmt->close();
}

function create_stock()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['price']) || empty($input['SKU'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name, price, and SKU']);
        return;
    }

    $new_stock = new Stock();
    $new_stock->setName($input['name']);
    $new_stock->setAisle($input['aisle'] ?? 'N/A');
    $new_stock->setQuantityStore($input['quantity_store'] ?? 0); 
    $new_stock->setQuantityStorage($input['quantity_storage'] ?? 0);
    $new_stock->setPrice($input['price']);
    $new_stock->setSKU($input['SKU']);
    
    $data = $new_stock->toArray();
    
    $sql = "INSERT INTO stocks (name, aisle, quantity_store, quantity_storage, price, SKU, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssiiss", 
        $data['name'], 
        $data['aisle'], 
        $data['quantity_store'], 
        $data['quantity_storage'],
        $data['price'],
        $data['SKU'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Stock item created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_stock($id)
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
        if (in_array($key, ['name', 'aisle', 'price', 'SKU'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif (in_array($key, ['quantity_store', 'quantity_storage'])) {
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

    $sql = "UPDATE stocks SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_stock($id); 
        } else {
            if (get_stock_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Stock item found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Stock item not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_stock($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, aisle, quantity_store, quantity_storage, price, SKU, created_at, updated_at FROM stocks WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_stock = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_stock) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Stock item not found']);
        return;
    }

    $sql_delete = 'DELETE FROM stocks WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Stock item deleted successfully',
            'data' => $deleted_stock
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>