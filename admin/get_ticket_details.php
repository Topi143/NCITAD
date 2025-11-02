<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    $ticket_id = $_GET['id'] ?? null;
    
    if (!$ticket_id) {
        throw new Exception('Ticket ID is required');
    }
    
    // Fetch ticket details
    $stmt = $pdo->prepare("
        SELECT * FROM concern_history 
        WHERE id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    echo json_encode([
        'success' => true,
        'ticket' => $ticket
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
