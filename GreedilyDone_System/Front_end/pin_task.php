<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    $task_id = $data['id'];
    
    try {
        // Check current state
        $stmt = $pdo->prepare("SELECT is_pinned FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch();

        if ($task) {
            if ($task['is_pinned'] == 1) {
                // Toggle off
                $update = $pdo->prepare("UPDATE tasks SET is_pinned = 0 WHERE id = ? AND user_id = ?");
                $update->execute([$task_id, $user_id]);
                echo json_encode(['success' => true, 'message' => 'Unpinned']);
            } else {
                // Check if limit of 3 is reached
                $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND is_pinned = 1");
                $count_stmt->execute([$user_id]);
                $count = $count_stmt->fetchColumn();

                if ($count < 3) {
                    $update = $pdo->prepare("UPDATE tasks SET is_pinned = 1 WHERE id = ? AND user_id = ?");
                    $update->execute([$task_id, $user_id]);
                    echo json_encode(['success' => true, 'message' => 'Pinned']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Maximum of 3 pinned tasks allowed.']);
                }
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Task not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
