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

require_once 'models/Book.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_all_books()
{
    global $conn; 

    $sql = 'SELECT id, name, author, genre, year, created_at, updated_at FROM books ORDER BY id DESC';
    $result = $conn->query($sql);
    $books = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $books,
            'count' => count($books)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_book($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, author, genre, year, created_at, updated_at FROM books WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();

    if ($book) {
        echo json_encode([
            'success' => true,
            'data' => $book
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Book not found'
        ]);
    }
    $stmt->close();
}

function create_book()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['author'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name and author']);
        return;
    }

    $new_book = new Book();
    $new_book->setName($input['name']);
    $new_book->setAuthor($input['author']);
    $new_book->setGenre($input['genre'] ?? 'Unknown');
    $new_book->setYear($input['year'] ?? 2000);
    
    $data = $new_book->toArray();
    
    $sql = "INSERT INTO books (name, author, genre, year, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("sssis", 
        $data['name'], 
        $data['author'], 
        $data['genre'], 
        $data['year'],
        $data['created_at'],
        $data['updated_at']
    );

    if ($stmt->execute()) {
        $data['id'] = $stmt->insert_id;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Book created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_book($id)
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
        if (in_array($key, ['name', 'author', 'genre'])) {
            $set_clauses[] = "$key = ?";
            $types .= 's';
            $params[] = $value;
        } elseif ($key === 'year') {
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

    $sql = "UPDATE books SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_book($id); 
        } else {
            if (get_book_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Book found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Book not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function get_book_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM books WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function delete_book($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, author, genre, year, created_at, updated_at FROM books WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_book = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_book) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Book not found']);
        return;
    }

    $sql_delete = 'DELETE FROM books WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Book deleted successfully',
            'data' => $deleted_book
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>