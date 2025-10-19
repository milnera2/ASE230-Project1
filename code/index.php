<?php
// index.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'handlers/books.php';
require_once 'handlers/contractors.php';
require_once 'handlers/device-registry.php';
require_once 'handlers/inventory.php';
require_once 'handlers/movies.php';
require_once 'handlers/orders.php';
require_once 'handlers/orgmembership.php';
require_once 'handlers/reservations.php';
require_once 'handlers/scores.php';
require_once 'handlers/tasks.php';


$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri_segments = array_values(array_filter(explode('/', trim($uri, '/')), function($value) {
    return $value !== '' && strtolower($value) !== 'index.php';
}));

$resource = $uri_segments[0] ?? '';
$id = $uri_segments[1] ?? null;

if ($resource === '') {
    http_response_code(200);
    echo json_encode([
        'message' => 'Welcome to the Multi-Resource REST API!',
        'available_resources' => [
            'books', 'contractors', 'device-registry', 'inventory', 'movies',
            'orders', 'orgmembership', 'reservations', 'scores', 'tasks',
        ]
    ]);
    exit;
}

$resource_id = null;
if (isset($uri_segments[1])) {
    $resource_id = filter_var($id, FILTER_VALIDATE_INT);
    
    if ($resource_id === false && $id !== null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Invalid ID format for resource '{$resource}'. Must be an integer."]);
        exit;
    }
}

function dispatch_request($resource, $resource_id, $method) {
    
    $prefix = '';
    $suffix = '';

    switch ($method) {
        case 'GET':
            $prefix = ($resource_id !== null) ? 'get' : 'get_all';
            break;
        case 'POST':
            $prefix = 'create';
            break;
        case 'PUT':
            $prefix = 'update';
            break;
        case 'DELETE':
            $prefix = 'delete';
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            return;
    }
    
    $resource_name = str_replace('-', '_', $resource);
    $function_name = $prefix . '_' . $resource_name . $suffix;
    
    if (!function_exists($function_name)) {
        http_response_code(501);
        echo json_encode(['success' => false, 'error' => "Handler function '{$function_name}' not found for resource '{$resource}'."]);
        return;
    }

    $args = [];
    if ($method === 'GET' && $resource_id !== null || $method === 'PUT' || $method === 'DELETE') {
        $args = [$resource_id];
    }
    
    if (($method === 'PUT' || $method === 'DELETE') && $resource_id === null) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "{$method} requires a resource ID."]);
        return;
    }

    if ($method === 'POST' && $resource_id !== null) {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => "Method not allowed for this endpoint. POST to a collection, not an item."]);
        return;
    }
    
    call_user_func_array($function_name, $args);
}


switch ($resource) {
    case 'books':
    case 'contractors':
    case 'device-registry':
    case 'inventory':
    case 'movies':
    case 'orders':
    case 'orgmembership':
    case 'reservations':
    case 'scores':
    case 'tasks':
        dispatch_request($resource, $resource_id, $method);
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Resource Not Found']);
        break;
}


if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>