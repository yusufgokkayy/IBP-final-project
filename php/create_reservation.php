<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Only POST method allowed', 405);
}

try {
    // Get session token and validate user
    $token = $_COOKIE['session_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        errorResponse('Authentication required', 401);
    }
    
    $user = validateSession($token);
    if (!$user) {
        errorResponse('Invalid session', 401);
    }
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    if (!$input) {
        errorResponse('No input data received');
    }
    
    // Validate required fields
    $required = ['propertyType', 'propertyId', 'checkIn', 'checkOut', 'guestCount'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '$field' is required");
        }
    }
    
    // Validate dates
    $checkIn = DateTime::createFromFormat('Y-m-d', $input['checkIn']);
    $checkOut = DateTime::createFromFormat('Y-m-d', $input['checkOut']);
    
    if (!$checkIn || !$checkOut) {
        errorResponse('Invalid date format');
    }
    
    if ($checkIn >= $checkOut) {
        errorResponse('Check-out date must be after check-in date');
    }
    
    if ($checkIn < new DateTime()) {
        errorResponse('Check-in date cannot be in the past');
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check availability again
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM reservations 
        WHERE property_type = ? 
        AND property_id = ? 
        AND status IN ('confirmed', 'pending')
        AND (
            (check_in_date <= ? AND check_out_date > ?) OR
            (check_in_date < ? AND check_out_date >= ?) OR
            (check_in_date >= ? AND check_out_date <= ?)
        )
    ");
    
    $stmt->execute([
        $input['propertyType'],
        $input['propertyId'],
        $input['checkIn'], $input['checkIn'],
        $input['checkOut'], $input['checkOut'],
        $input['checkIn'], $input['checkOut']
    ]);
    
    $conflictCount = $stmt->fetchColumn();
    
    if ($conflictCount > 0) {
        errorResponse('Property is no longer available for the selected dates');
    }
    
    // Get property details and calculate price
    if ($input['propertyType'] === 'hotel_room') {
        $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT price_per_night FROM houses WHERE id = ?");
    }
    
    $stmt->execute([$input['propertyId']]);
    $property = $stmt->fetch();
    
    if (!$property) {
        errorResponse('Property not found');
    }
    
    $nights = $checkIn->diff($checkOut)->days;
    $totalPrice = $nights * $property['price_per_night'];
    
    // Create reservation
    $stmt = $pdo->prepare("
        INSERT INTO reservations (user_id, property_type, property_id, check_in_date, check_out_date, guest_count, total_price, special_requests, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $user['id'],
        $input['propertyType'],
        $input['propertyId'],
        $input['checkIn'],
        $input['checkOut'],
        $input['guestCount'],
        $totalPrice,
        $input['specialRequests'] ?? ''
    ]);
    
    $reservationId = $pdo->lastInsertId();
    
    successResponse([
        'reservationId' => $reservationId,
        'totalPrice' => $totalPrice,
        'nights' => $nights
    ], 'Reservation created successfully');
    
} catch (Exception $e) {
    error_log("Create reservation error: " . $e->getMessage());
    errorResponse('Failed to create reservation. Please try again.');
}
?>