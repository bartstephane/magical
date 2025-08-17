<?php
header('Content-Type: application/json');
require 'db.php'; // fichier de connexion PDO
/** @var PDO $pdo */

$route = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$currentUserId = $_GET['user_id'] ?? null;

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
            elseif ($method === 'POST') { // CREATE USER
                $input = json_decode(file_get_contents('php://input'), true);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, color, created_by, created_date) 
                           VALUES (:username, :email, :password_hash, :color, :created_by, :created_date)");
                $stmt->execute([
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'password_hash' => $input['password_hash'],
                    'color' => $input['color'],
                    'created_by' => $input['created_by'],
                    'created_date' => $input['created_date']
                ]);

                $userId = $pdo->lastInsertId();

                // gestion de l’appartenance aux groupes
                if (!empty($input['groups']) && is_array($input['groups'])) {
                    $stmtGroup = $pdo->prepare("INSERT INTO groups_users (group_id, user_id) VALUES (:group_id, :user_id)");
                    foreach ($input['groups'] as $groupId) {
                        $stmtGroup->execute(['group_id' => $groupId, 'user_id' => $userId]);
                    }
                }

                jsonResponse('success', ['id' => $userId]);
            }
            elseif ($method === 'PUT') { // UPDATE USER
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    jsonResponse('error', 'ID manquant');
                }

                $stmt = $pdo->prepare("UPDATE users 
                           SET username = :username, email = :email, color = :color 
                           WHERE id = :id");
                $stmt->execute([
                    'username' => $input['username'],
                    'email' => $input['email'],
                    'color' => $input['color'],
                    'id' => $id
                ]);

                // mise à jour des groupes
                $pdo->prepare("DELETE FROM groups_users WHERE user_id = :user_id")
                    ->execute(['user_id' => $id]);

                if (!empty($input['groups']) && is_array($input['groups'])) {
                    $stmtGroup = $pdo->prepare("INSERT INTO groups_users (group_id, user_id) VALUES (:group_id, :user_id)");
                    foreach ($input['groups'] as $groupId) {
                        $stmtGroup->execute(['group_id' => $groupId, 'user_id' => $id]);
                    }
                }

                jsonResponse('success', ['id' => $id]);
            }
            elseif ($method === 'DELETE') { // DELETE USER
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    jsonResponse('error', 'ID manquant');
                }

                // Soft delete user
                $stmt = $pdo->prepare("UPDATE users 
                           SET archived_by = :by, archived_date = NOW() 
                           WHERE id = :id");
                $stmt->execute(['by' => $currentUserId, 'id' => $id]);

                // Soft delete dans groups_users
                $stmt = $pdo->prepare("UPDATE groups_users 
                           SET archived_by = :by, archived_date = NOW() 
                           WHERE user_id = :id");
                $stmt->execute(['by' => $currentUserId, 'id' => $id]);

                // Soft delete dans events_users
                $stmt = $pdo->prepare("UPDATE events_users 
                           SET archived_by = :by, archived_date = NOW() 
                           WHERE user_id = :id");
                $stmt->execute(['by' => $currentUserId, 'id' => $id]);

                jsonResponse('success', "Utilisateur $id archivé");
            }
            break;

        /* ---------------- EVENTS ---------------- */
        case 'events':
            if ($method === 'GET') {
                $stmt = $pdo->query("
                    SELECT id, group_id, title, description, date, end_date, time, end_time 
                    FROM events 
                    WHERE archived_date IS NULL
                ");
                $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // transformer en format compatible avec Flutter (CalendarView)
                $formatted = array_map(function($e) {
                    return [
                        'id'    => $e['id'],
                        'title' => $e['title'],
                        'description' => $e['description'],
                        'start' => $e['date'] . ' ' . $e['time'],
                        'end'   => $e['end_date'] . ' ' . $e['end_time'],
                        'group_id' => $e['group_id']
                    ];
                }, $events);

                jsonResponse('success', $formatted);
            }
            elseif ($method === 'POST') { // CREATE EVENT
                $input = json_decode(file_get_contents('php://input'), true);

                $stmt = $pdo->prepare("INSERT INTO events (group_id, title, date, end_date, time, end_time, created_by, created_date) 
                           VALUES (:group_id, :title, :date, :end_date, :time, :end_time, :created_by, :created_date)");
                $stmt->execute([
                    'group_id' => $input['group_id'],
                    'title' => $input['title'],
                    'date' => $input['date'],
                    'end_date' => $input['end_date'],
                    'time' => $input['time'],
                    'end_time' => $input['end_time'],
                    'created_by' => $input['created_by'],
                    'created_date' => $input['created_date']
                ]);

                $eventId = $pdo->lastInsertId();

                // gestion des users associés à l’event
                if (!empty($input['users']) && is_array($input['users'])) {
                    $stmtUser = $pdo->prepare("INSERT INTO events_users (event_id, user_id) VALUES (:event_id, :user_id)");
                    foreach ($input['users'] as $userId) {
                        $stmtUser->execute(['event_id' => $eventId, 'user_id' => $userId]);
                    }
                }

                jsonResponse('success', ['id' => $eventId]);
            }
            elseif ($method === 'PUT') { // UPDATE EVENT
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    jsonResponse('error', 'ID manquant');
                }

                $stmt = $pdo->prepare("UPDATE events 
                           SET group_id = :group_id, title = :title, date = :date, 
                               end_date = :end_date, time = :time, end_time = :end_time 
                           WHERE id = :id");
                $stmt->execute([
                    'group_id' => $input['group_id'],
                    'title' => $input['title'],
                    'date' => $input['date'],
                    'end_date' => $input['end_date'],
                    'time' => $input['time'],
                    'end_time' => $input['end_time'],
                    'id' => $id
                ]);

                // mise à jour des users associés
                $pdo->prepare("DELETE FROM events_users WHERE event_id = :event_id")
                    ->execute(['event_id' => $id]);

                if (!empty($input['users']) && is_array($input['users'])) {
                    $stmtUser = $pdo->prepare("INSERT INTO events_users (event_id, user_id) VALUES (:event_id, :user_id)");
                    foreach ($input['users'] as $userId) {
                        $stmtUser->execute(['event_id' => $id, 'user_id' => $userId]);
                    }
                }

                jsonResponse('success', ['id' => $id]);
            }
            elseif ($method === 'DELETE') { // DELETE EVENT
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    jsonResponse('error', 'ID manquant');
                }

                // Soft delete event
                $stmt = $pdo->prepare("UPDATE events 
                           SET archived_by = :by, archived_date = NOW() 
                           WHERE id = :id");
                $stmt->execute(['by' => $currentUserId, 'id' => $id]);

                // Soft delete dans events_users
                $stmt = $pdo->prepare("UPDATE events_users 
                           SET archived_by = :by, archived_date = NOW() 
                           WHERE event_id = :id");
                $stmt->execute(['by' => $currentUserId, 'id' => $id]);

                jsonResponse('success', "Événement $id archivé");
            }
            break;

        default:
            jsonResponse('error', null, 'Endpoint not found');
    }
}
catch (Exception $e) {
    jsonResponse('error', null, $e->getMessage());
}
