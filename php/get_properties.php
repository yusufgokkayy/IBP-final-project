<?php
require_once 'config_oracle.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Only GET method allowed', 405);
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get hotels with room counts
    $stmt = $pdo->prepare("
        SELECT 
            h.id,
            h.name,
            h.description,
            h.floors,
            h.total_rooms,
            h.location,
            COUNT(r.id) as available_rooms,
            MIN(r.price_per_night) as min_price,
            MAX(r.price_per_night) as max_price
        FROM hotels h
        LEFT JOIN rooms r ON h.id = r.hotel_id AND r.is_available = 1
        GROUP BY h.id
        ORDER BY h.name
    ");
    $stmt->execute();
    $hotels = $stmt->fetchAll();
    
    // Get houses
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            type,
            floors,
            size_sqm,
            bedrooms,
            bathrooms,
            max_occupancy,
            has_pool,
            pool_size,
            price_per_night,
            is_timeshare,
            is_available,
            description,
            amenities,
            location,
            image_url
        FROM houses
        WHERE is_available = 1
        ORDER BY is_timeshare DESC, name
    ");
    $stmt->execute();
    $houses = $stmt->fetchAll();
    
    // Parse JSON amenities for houses
    foreach ($houses as &$house) {
        $house['amenities'] = json_decode($house['amenities'], true) ?: [];
    }
    
    successResponse([
        'hotels' => $hotels,
        'houses' => $houses
    ]);
    
} catch (Exception $e) {
    error_log("Get properties error: " . $e->getMessage());
    errorResponse('Failed to load properties');
}
?>
