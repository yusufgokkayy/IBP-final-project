<?php
require_once 'config_oracle.php';

// Set headers for AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Only POST method allowed', 405);
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
    
    // Check timeshare eligibility
    if (!isEligibleForTimeshare($user['birth_date'], $user['marital_status'])) {
        errorResponse('You are not eligible for timeshare. Must be over 30 and married.');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        errorResponse('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['houseId', 'period', 'durationWeeks'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            errorResponse("Field '$field' is required");
        }
    }
    
    // Validate period
    if (!in_array($input['period'], ['spring', 'summer', 'autumn', 'winter'])) {
        errorResponse('Invalid period selected');
    }
    
    // Validate duration
    $durationWeeks = (int)$input['durationWeeks'];
    if ($durationWeeks < 1 || $durationWeeks > 12) {
        errorResponse('Duration must be between 1 and 12 weeks');
    }
    
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Check if house exists and is available for timeshare
    $stmt = $pdo->prepare("SELECT * FROM houses WHERE id = ? AND is_timeshare = 1 AND is_available = 1");
    $stmt->execute([$input['houseId']]);
    $house = $stmt->fetch();
    
    if (!$house) {
        errorResponse('House not available for timeshare');
    }
    
    // Check if user already has a timeshare contract for this house and period
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as existing
        FROM timeshare_contracts
        WHERE user_id = ? AND house_id = ? AND period = ? AND status IN ('active', 'pending')
    ");
    $stmt->execute([$user['user_id'], $input['houseId'], $input['period']]);
    
    if ($stmt->fetch()['existing'] > 0) {
        errorResponse('You already have a timeshare contract for this house and period');
    }
    
    // Calculate pricing (base price varies by period and duration)
    $basePricePerWeek = $house['price_per_night'] * 7;
    $periodMultiplier = [
        'spring' => 1.0,
        'summer' => 1.5,
        'autumn' => 1.2,
        'winter' => 0.8
    ];
    
    $weeklyPrice = $basePricePerWeek * $periodMultiplier[$input['period']];
    $purchasePrice = $weeklyPrice * $durationWeeks * 10; // 10x weekly rate for purchase
    $annualMaintenanceFee = $purchasePrice * 0.05; // 5% of purchase price annually
    
    // Generate contract number
    $contractNumber = 'TS' . date('Y') . str_pad($input['houseId'], 3, '0', STR_PAD_LEFT) . strtoupper(substr($input['period'], 0, 3)) . random_int(1000, 9999);
    
    // Calculate contract dates (1 year from now)
    $effectiveFrom = new DateTime('+1 month');
    $validUntil = new DateTime('+25 years'); // 25-year contract
    
    // Create timeshare contract
    $stmt = $pdo->prepare("
        INSERT INTO timeshare_contracts (
            contract_number, user_id, house_id, period, duration_weeks,
            purchase_price, annual_maintenance_fee, contract_date,
            effective_from, valid_until, status, terms_conditions
        ) VALUES (?, ?, ?, ?, ?, ?, ?, TRUNC(SYSDATE), ?, ?, 'pending', ?)
    ");
    
    $termsConditions = "This timeshare contract grants the holder the right to use the specified property for {$durationWeeks} weeks during the {$input['period']} period each year for 25 years. The contract includes annual maintenance fees and is subject to the Holiday Village terms and conditions.";
    
    $stmt->execute([
        $contractNumber,
        $user['user_id'],
        $input['houseId'],
        $input['period'],
        $durationWeeks,
        $purchasePrice,
        $annualMaintenanceFee,
        $effectiveFrom->format('Y-m-d'),
        $validUntil->format('Y-m-d'),
        $termsConditions
    ]);
    
    $contractId = $pdo->lastInsertId();
    
    // Add to timeshare owners table
    $ownershipPercentage = ($durationWeeks / 52) * 100; // Percentage based on weeks per year
    $stmt = $pdo->prepare("
        INSERT INTO timeshare_owners (user_id, house_id, ownership_percentage)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['user_id'], $input['houseId'], $ownershipPercentage]);
    
    successResponse([
        'contract' => [
            'id' => $contractId,
            'contractNumber' => $contractNumber,
            'houseName' => $house['name'],
            'period' => $input['period'],
            'durationWeeks' => $durationWeeks,
            'purchasePrice' => $purchasePrice,
            'annualMaintenanceFee' => $annualMaintenanceFee,
            'effectiveFrom' => $effectiveFrom->format('Y-m-d'),
            'validUntil' => $validUntil->format('Y-m-d'),
            'ownershipPercentage' => round($ownershipPercentage, 2),
            'status' => 'pending'
        ]
    ], 'Timeshare application submitted successfully');
    
} catch (Exception $e) {
    error_log("Apply timeshare error: " . $e->getMessage());
    errorResponse('Failed to submit timeshare application. Please try again.');
}
?>
