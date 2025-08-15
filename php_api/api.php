<?php
header('Content-Type: application/json');
require 'db.php'; // fichier de connexion PDO
/** @var PDO $pdo */

$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function jsonResponse($status, $data = null, $message = '') {
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

try {
    switch ($route) {

        /* ---------------- USERS ---------------- */
        case 'users':
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT * FROM users WHERE archived_date IS NULL");
                jsonResponse('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            elseif ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, color, created_by, created_date) VALUES (:name, :email)");
                $stmt->execute(['name' => $input['username'], 'email' => $input['email'], 'password_hash' => $input['password_hash'], 'color' => $input['color'], 'created_by' => $input['created_by'], 'created_date' => $input['created_date']]);
                jsonResponse('success', ['id' => $pdo->lastInsertId()]);
            }
            elseif ($method === 'DELETE') {
                $id = $_GET['id'] ?? null;
                $archived_by = $_GET['archived_by'] ?? null;
                if (!$id || !$archived_by) jsonResponse('error', null, 'Missing parameters');
                $stmt = $pdo->prepare("UPDATE users SET archived_by = :archived_by, archived_date = NOW() WHERE id = :id");
                $stmt->execute(['archived_by' => $archived_by, 'id' => $id]);
                jsonResponse('success', null, 'User archived');
            }
            break;

        /* ---------------- EVENTS ---------------- */
        case 'events':
            if ($method === 'GET') {
                $stmt = $pdo->query("SELECT * FROM events WHERE archived_date IS NULL");
                jsonResponse('success', $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            elseif ($method === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true);
                $stmt = $pdo->prepare("INSERT INTO events (group_id, title, date, end_date, time, end_time, created_by, created_date) 
                                      VALUES (:group_id, :title, :date, :end_date, :time, :end_time, :created_by, :created_date)");
                $stmt->execute($input);
                jsonResponse('success', ['id' => $pdo->lastInsertId()]);
            }
            elseif ($method === 'DELETE') {
                $id = $_GET['id'] ?? null;
                $archived_by = $_GET['archived_by'] ?? null;
                if (!$id || !$archived_by) jsonResponse('error', null, 'Missing parameters');
                $stmt = $pdo->prepare("UPDATE events SET archived_by = :archived_by, archived_date = NOW() WHERE id = :id");
                $stmt->execute(['archived_by' => $archived_by, 'id' => $id]);
                jsonResponse('success', null, 'Event archived');
            }
            break;

        default:
            jsonResponse('error', null, 'Endpoint not found');
    }
}
catch (Exception $e) {
    jsonResponse('error', null, $e->getMessage());
}
