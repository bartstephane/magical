<?php
header('Content-Type: application/json');
require 'db.php'; // ton fichier de connexion PDO
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
                $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
                $stmt->execute(['name' => $input['name'], 'email' => $input['email']]);
                jsonResponse('success', ['id' => $db->lastInsertId()]);
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
                $stmt = $pdo->prepare("INSERT INTO events (title, date, end_date, time, end_time, created_by) 
                                      VALUES (:title, :date, :end_date, :time, :end_time, :created_by)");
                $stmt->execute($input);
                jsonResponse('success', ['id' => $db->lastInsertId()]);
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
