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
    
    // Get user's timeshare contracts
    $stmt = $pdo->prepare("
        SELECT 
            tc.*,
            h.name as house_name,
            h.location as house_location,
            h.size_sqm,
            h.bedrooms,
            h.bathrooms,
            h.has_pool,
            to.ownership_percentage
        FROM timeshare_contracts tc
        JOIN houses h ON tc.house_id = h.id
        LEFT JOIN timeshare_owners to ON tc.user_id = to.user_id AND tc.house_id = to.house_id
        WHERE tc.user_id = ?
        ORDER BY tc.created_at DESC
    ");
    $stmt->execute([$user['user_id']]);
    $contracts = $stmt->fetchAll();
    
    // Format dates and add status indicators
    foreach ($contracts as &$contract) {
        $effectiveFrom = new DateTime($contract['effective_from']);
        $validUntil = new DateTime($contract['valid_until']);
        $contractDate = new DateTime($contract['contract_date']);
        
        // Format dates for display
        $contract['contract_date_formatted'] = $contractDate->format('M j, Y');
        $contract['effective_from_formatted'] = $effectiveFrom->format('M j, Y');
        $contract['valid_until_formatted'] = $validUntil->format('M j, Y');
        
        // Calculate remaining years
        $today = new DateTime();
        $remainingYears = $today->diff($validUntil)->y;
        $contract['remaining_years'] = max(0, $remainingYears);
        
        // Format pricing
        $contract['purchase_price_formatted'] = number_format($contract['purchase_price'], 2);
        $contract['annual_maintenance_fee_formatted'] = number_format($contract['annual_maintenance_fee'], 2);
        
        // Capitalize period
        $contract['period_formatted'] = ucfirst($contract['period']);
    }
    
    // Get available timeshare houses
    $stmt = $pdo->prepare("
        SELECT 
            id,
            name,
            type,
            size_sqm,
            bedrooms,
            bathrooms,
            max_occupancy,
            has_pool,
            pool_size,
            price_per_night,
            description,
            amenities,
            location,
            image_url
        FROM houses
        WHERE is_timeshare = 1 AND is_available = 1
        ORDER BY name
    ");
    $stmt->execute();
    $availableHouses = $stmt->fetchAll();
    
    // Parse JSON amenities for houses
    foreach ($availableHouses as &$house) {
        $house['amenities'] = json_decode($house['amenities'], true) ?: [];
        
        // Calculate pricing for different periods
        $basePricePerWeek = $house['price_per_night'] * 7;
        $house['period_pricing'] = [
            'spring' => $basePricePerWeek * 1.0,
            'summer' => $basePricePerWeek * 1.5,
            'autumn' => $basePricePerWeek * 1.2,
            'winter' => $basePricePerWeek * 0.8
        ];
    }
    
    successResponse([
        'contracts' => $contracts,
        'availableHouses' => $availableHouses,
        'userEligible' => isEligibleForTimeshare($user['birth_date'], $user['marital_status'])
    ]);
    
} catch (Exception $e) {
    error_log("Get timeshare info error: " . $e->getMessage());
    errorResponse('Failed to load timeshare information');
}
?>
