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
    $stmt = $pdo->query("SELECT id, group_id, title, date, end_date, time, end_time, description, created_by, created_date, modified_by, modified_date FROM events WHERE archived_date IS NULL ORDER BY date ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($method === 'POST' && $path === 'add') {
    // Ajout d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("INSERT INTO events (id, group_id, title, date, end_date, time, end_time, description, created_by, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['title'], $data['group_id'], $data['date'], $data['end_date'], $data['time'], $data['end_time'], $data['description'], $data['created_by'], $data['created_date']]);
    echo json_encode(['status' => 'ok']);

} elseif ($method === 'PUT' && $path === 'update') {
    // Mise à jour d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE events SET group_id=?, title=?, date=?, end_date=?, time=?, end_time=?, description=?, modified_by=?, modified_date=? WHERE id=?");
    $stmt->execute([$data['group_id'], $data['title'], $data['date'], $data['end_date'], $data['time'], $data['end_time'], $data['description'], $data['modified_by'], $data['modified_date'], $data['id']]);
    echo json_encode(['status' => 'ok']);

} elseif ($method === 'DELETE' && $path === 'delete') {
    // Suppression d'événement
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE events SET archived_by=?, archived_date=? WHERE id=?");
    $stmt->execute([$data['archived_by'], $data['archived_date'], $data['id']]);
    echo json_encode(['status' => 'ok']);

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint introuvable']);
}
