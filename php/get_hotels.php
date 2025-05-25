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
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT h.*, 
               COUNT(r.id) as total_rooms,
               COUNT(CASE WHEN r.is_available = 1 THEN 1 END) as available_rooms
        FROM hotels h 
        LEFT JOIN rooms r ON h.id = r.hotel_id 
        GROUP BY h.id
        ORDER BY h.name
    ");
    $stmt->execute();
    $hotels = $stmt->fetchAll();
    
    successResponse($hotels);
    
} catch (Exception $e) {
    error_log("Get hotels error: " . $e->getMessage());
    errorResponse('Failed to fetch hotels. Please try again.');
}
?>