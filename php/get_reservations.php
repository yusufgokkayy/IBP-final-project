<?php
require_once 'config_oracle.php';

// Set headers for AJAX requests
header("Access-Control-Allow-Origin: *"); // Tüm kaynaklardan gelen isteklere izin verir
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // İzin verilen HTTP metodları
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // İzin verilen headerlar

// OPTIONS preflight isteğine cevap ver
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get session token
    $token = $_COOKIE['session_token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);
    
    if (empty($token)) {
        errorResponse('Authentication required', 401);
    }
    
    // Validate session
    $user = validateSession($token);
    if (!$user) {
        errorResponse('Invalid or expired session', 401);
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get user's reservations
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            CASE 
                WHEN r.property_type = 'hotel_room' THEN CONCAT(h.name, ' - Room ', rm.room_number)
                WHEN r.property_type = 'house' THEN hs.name
            END as property_name,
            CASE 
                WHEN r.property_type = 'hotel_room' THEN h.location
                WHEN r.property_type = 'house' THEN hs.location
            END as property_location
        FROM reservations r
        LEFT JOIN rooms rm ON r.property_type = 'hotel_room' AND r.property_id = rm.id
        LEFT JOIN hotels h ON rm.hotel_id = h.id
        LEFT JOIN houses hs ON r.property_type = 'house' AND r.property_id = hs.id
        WHERE r.user_id = ?
        ORDER BY r.check_in_date DESC
    ");
    $stmt->execute([$user['user_id']]);
    $reservations = $stmt->fetchAll();
    
    // Format dates and add status indicators
    foreach ($reservations as &$reservation) {
        $checkIn = new DateTime($reservation['check_in_date']);
        $checkOut = new DateTime($reservation['check_out_date']);
        $today = new DateTime();
        
        // Determine reservation status based on dates
        if ($reservation['status'] === 'cancelled') {
            $reservation['display_status'] = 'Cancelled';
        } elseif ($today < $checkIn) {
            $reservation['display_status'] = 'Upcoming';
        } elseif ($today >= $checkIn && $today <= $checkOut) {
            $reservation['display_status'] = 'Active';
        } else {
            $reservation['display_status'] = 'Completed';
        }
        
        // Format dates for display
        $reservation['check_in_formatted'] = $checkIn->format('M j, Y');
        $reservation['check_out_formatted'] = $checkOut->format('M j, Y');
        $reservation['booking_date_formatted'] = (new DateTime($reservation['booking_date']))->format('M j, Y');
    }
    
    successResponse([
        'reservations' => $reservations
    ]);
    
} catch (Exception $e) {
    error_log("Get reservations error: " . $e->getMessage());
    errorResponse('Failed to load reservations');
}
?>
