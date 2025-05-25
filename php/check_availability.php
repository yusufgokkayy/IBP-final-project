<?php
require_once 'config.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Only POST method allowed', 405);
}

try {
    // Get input data (JSON or POST)
    $input = json_decode(file_get_contents('php://input'), true);
    
    // If JSON decode failed, try POST data
    if (!$input) {
        $input = $_POST;
    }
    
    if (!$input) {
        errorResponse('No input data received');
    }
    
    // Validate required fields
    $required = ['propertyType', 'propertyId', 'checkIn', 'checkOut'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '$field' is required");
        }
    }
    
    // Validate property type
    if (!in_array($input['propertyType'], ['hotel_room', 'house'])) {
        errorResponse('Invalid property type');
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
    
    // Check availability
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check for conflicting reservations
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
        successResponse([
            'available' => false,
            'message' => 'Property is not available for the selected dates'
        ]);
        exit;
    }
    
    // Get property details and pricing
    $propertyDetails = null;
    $pricePerNight = 0;
    
    if ($input['propertyType'] === 'hotel_room') {
        $stmt = $pdo->prepare("
            SELECT r.*, h.name as hotel_name 
            FROM rooms r 
            JOIN hotels h ON r.hotel_id = h.id 
            WHERE r.id = ? AND r.is_available = 1
        ");
        $stmt->execute([$input['propertyId']]);
        $propertyDetails = $stmt->fetch();
        
        if ($propertyDetails) {
            $pricePerNight = $propertyDetails['price_per_night'];
        }
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM houses 
            WHERE id = ? AND is_available = 1
        ");
        $stmt->execute([$input['propertyId']]);
        $propertyDetails = $stmt->fetch();
        
        if ($propertyDetails) {
            $pricePerNight = $propertyDetails['price_per_night'];
        }
    }
    
    if (!$propertyDetails) {
        errorResponse('Property not found or not available');
    }
    
    // Calculate total price
    $nights = $checkIn->diff($checkOut)->days;
    $totalPrice = $nights * $pricePerNight;
    
    successResponse([
        'available' => true,
        'property' => $propertyDetails,
        'pricing' => [
            'pricePerNight' => $pricePerNight,
            'nights' => $nights,
            'totalPrice' => $totalPrice
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Availability check error: " . $e->getMessage());
    errorResponse('Failed to check availability. Please try again.');
}
?>