<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'DocumentController.php';

header('Content-Type: application/json');

$request = $_GET['request'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', trim($request, '/'));
$resource = $segments[0] ?? null;
$id = $segments[1] ?? null;

switch ($method) {
    case 'POST':
        if ($resource === 'documents') {
            DocumentController::upload();
        }
        break;
    case 'GET':
        if ($resource === 'documents') {
            if ($id) {
                DocumentController::get((int) $id);
            } else {
                DocumentController::list();
            }
        } elseif ($resource === 'search') {
            DocumentController::search();
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
        }
        break;
    case 'DELETE':
        if ($resource === 'documents' && $id) {
            DocumentController::delete((int) $id);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
