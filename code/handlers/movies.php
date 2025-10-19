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

require_once 'models/Movie.php';

function getRequestData()
{
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function get_movie_exists($id, $conn) {
    $stmt = $conn->prepare('SELECT 1 FROM movies WHERE id = ? LIMIT 1');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

function get_all_movies()
{
    global $conn; 

    $sql = 'SELECT id, name, producer, actors, genre, year, created_at, updated_at FROM movies ORDER BY id DESC';
    $result = $conn->query($sql);
    $movies = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }
        $result->free();

        echo json_encode([
            'success' => true,
            'data' => $movies,
            'count' => count($movies)
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during fetch: ' . $conn->error]);
    }
}

function get_movie($id)
{
    global $conn;
    
    $sql = 'SELECT id, name, producer, actors, genre, year, created_at, updated_at FROM movies WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $movie = $result->fetch_assoc();

    if ($movie) {
        echo json_encode([
            'success' => true,
            'data' => $movie
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Movie not found'
        ]);
    }
    $stmt->close();
}

function create_movie()
{
    global $conn;
    
    $input = getRequestData();

    if (empty($input['name']) || empty($input['producer']) || empty($input['year'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields: name, producer, and year']);
        return;
    }

    $new_movie = new Movie();
    $new_movie->setName($input['name']);
    $new_movie->setProducer($input['producer']);
    $new_movie->setActors($input['actors'] ?? 'N/A'); 
    $new_movie->setGenre($input['genre'] ?? 'Unknown');
    $new_movie->setYear($input['year']);
    
    $data = $new_movie->toArray();
    
    $sql = "INSERT INTO movies (name, producer, actors, genre, year, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    

    $stmt->bind_param("ssssiss", 
        $data['name'], 
        $data['producer'], 
        $data['actors'], 
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
            'message' => 'Movie created successfully',
            'data' => $data
        ]);

    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during creation: ' . $stmt->error]);
    }
    $stmt->close();
}

function update_movie($id)
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
        if (in_array($key, ['name', 'producer', 'actors', 'genre'])) {
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

    $sql = "UPDATE movies SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);

    $bind_params = array_merge([$types], $params);
    call_user_func_array([$stmt, 'bind_param'], $bind_params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            get_movie($id); 
        } else {
            if (get_movie_exists($id, $conn)) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Movie found, but no new changes were made.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Movie not found']);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during update: ' . $stmt->error]);
    }
    $stmt->close();
}

function delete_movie($id)
{
    global $conn;
    
    $sql_select = 'SELECT id, name, producer, actors, genre, year, created_at, updated_at FROM movies WHERE id = ?';
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $deleted_movie = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$deleted_movie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Movie not found']);
        return;
    }

    $sql_delete = 'DELETE FROM movies WHERE id = ?';
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);
    
    if ($stmt_delete->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Movie deleted successfully',
            'data' => $deleted_movie
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error during deletion: ' . $stmt_delete->error]);
    }
    $stmt_delete->close();
}
?>