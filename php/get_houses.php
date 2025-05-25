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
        SELECT * FROM houses 
        WHERE is_available = 1 
        ORDER BY house_number
    ");
    $stmt->execute();
    $houses = $stmt->fetchAll();
    
    // Response formatını düzeltelim
    echo json_encode([
        'success' => true,
        'data' => $houses,
        'message' => 'Houses loaded successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Get houses error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Failed to fetch houses'
    ]);
}
?>