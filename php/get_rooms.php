<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Only GET method allowed', 405);
}

try {
    $hotelId = $_GET['hotel_id'] ?? null;
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    if ($hotelId) {
        $stmt = $pdo->prepare("
            SELECT r.*, h.name as hotel_name 
            FROM rooms r 
            JOIN hotels h ON r.hotel_id = h.id 
            WHERE r.hotel_id = ? AND r.is_available = 1
            ORDER BY r.floor, r.room_number
        ");
        $stmt->execute([$hotelId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT r.*, h.name as hotel_name 
            FROM rooms r 
            JOIN hotels h ON r.hotel_id = h.id 
            WHERE r.is_available = 1
            ORDER BY h.name, r.floor, r.room_number
        ");
        $stmt->execute();
    }
    
    $rooms = $stmt->fetchAll();
    
    // Response formatını düzeltelim
    echo json_encode([
        'success' => true,
        'data' => $rooms,
        'message' => 'Rooms loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Get rooms error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Failed to fetch rooms'
    ]);
}
?>