<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
    $_ENV['DB_USER'],
    $_ENV['DB_PASS']
);

$requestMethod = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

$resource = $segments[1] ?? null;
$id = $segments[2] ?? null;

switch ("$requestMethod $resource") {
    case 'GET products':
        getProducts($db);
        break;
    case 'GET products/' . $id:
        getProduct($db, $id);
        break;
    case 'POST products':
        createProduct($db);
        break;
    case 'PUT products/' . $id:
        updateProduct($db, $id);
        break;
    case 'DELETE products/' . $id:
        deleteProduct($db, $id);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
}

function getProducts($db) {
    $stmt = $db->query("SELECT * FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
}

function getProduct($db, $id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo json_encode($product);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
    }
}

function createProduct($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $db->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
    $stmt->execute([$input['name'], $input['price']]);
    
    $id = $db->lastInsertId();
    http_response_code(201);
    echo json_encode(['id' => $id]);
}

