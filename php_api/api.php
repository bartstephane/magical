<?php
header('Content-Type: application/json');

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'users':
        // Exemple : réponse fictive
        echo json_encode([
            'status' => 'success',
            'data' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob']
            ]
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'route' => $route
        ]);
        break;
}
