<?php
// api.php — version minimale pour test

// Autoriser le retour JSON
header('Content-Type: application/json; charset=utf-8');

// Récupérer la route (après /api/)
$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';

// Réponse selon la route
switch ($route) {
    case 'users':
        echo json_encode([
            'status' => 'success',
            'data' => [
                ['id' => 1, 'name' => 'Alice'],
                ['id' => 2, 'name' => 'Bob'],
            ]
        ]);
        break;

    case 'ping':
        echo json_encode([
            'status' => 'success',
            'message' => 'API OK'
        ]);
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found',
            'route' => $route
        ]);
}
