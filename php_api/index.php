<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // à restreindre à ton domaine Flutter
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'db.php';

// Vérification token API
$headers = getallheaders();
if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'Bearer '.API_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

if ($method === 'GET' && $path === 'list') {
    // Liste des événements
    $stmt = $pdo->query("SELECT id, title, date, endDate, time, endTime FROM events ORDER BY date ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($method === 'POST' && $path === 'add') {
    // Ajout d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO events (title, date, endDate, time, endTime) VALUES (?, ?)");
    $stmt->execute([$data['title'], $data['date'], $data['endDate'], $data['time'], $data['endTime']]);
    echo json_encode(['status' => 'ok']);

} elseif ($method === 'PUT' && $path === 'update') {
    // Mise à jour d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE events SET title=?, date=?, endDate=?, time=?, endTime=? WHERE id=?");
    $stmt->execute([$data['title'], $data['date'], $data['endDate'], $data['time'], $data['endTime'], $data['id']]);
    echo json_encode(['status' => 'ok']);

} elseif ($method === 'DELETE' && $path === 'delete') {
    // Suppression d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
    $stmt->execute([$data['id']]);
    echo json_encode(['status' => 'ok']);

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint introuvable']);
}
