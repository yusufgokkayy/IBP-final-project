<?php
require_once 'config_oracle.php';

echo "<h1>Holiday Village System Test</h1>";

$tests = [];
$allPassed = true;

// Test 1: Database Connection
try {
    $db = new Database();
    $pdo = $db->getConnection();
    $tests['Database Connection'] = ['status' => 'PASS', 'message' => 'Successfully connected to database'];
} catch (Exception $e) {
    $tests['Database Connection'] = ['status' => 'FAIL', 'message' => 'Database connection failed: ' . $e->getMessage()];
    $allPassed = false;
}

// Test 2: Tables Exist
try {
    $tables = ['users', 'hotels', 'rooms', 'houses', 'reservations', 'timeshare_contracts', 'timeshare_owners', 'user_sessions'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        $tests['Database Tables'] = ['status' => 'PASS', 'message' => 'All required tables exist'];
    } else {
        $tests['Database Tables'] = ['status' => 'FAIL', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
        $allPassed = false;
    }
} catch (Exception $e) {
    $tests['Database Tables'] = ['status' => 'FAIL', 'message' => 'Error checking tables: ' . $e->getMessage()];
    $allPassed = false;
}

// Test 3: Sample Data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM hotels");
    $hotelCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM rooms");
    $roomCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM houses");
    $houseCount = $stmt->fetch()['count'];
    
    if ($hotelCount >= 2 && $roomCount >= 10 && $houseCount >= 5) {
        $tests['Sample Data'] = ['status' => 'PASS', 'message' => "Hotels: $hotelCount, Rooms: $roomCount, Houses: $houseCount"];
    } else {
        $tests['Sample Data'] = ['status' => 'WARN', 'message' => "Limited data - Hotels: $hotelCount, Rooms: $roomCount, Houses: $houseCount"];
    }
} catch (Exception $e) {
    $tests['Sample Data'] = ['status' => 'FAIL', 'message' => 'Error checking sample data: ' . $e->getMessage()];
}

// Test 4: Password Hashing
try {
    $testPassword = 'test123';
    $hash = hashPassword($testPassword);
    $verified = verifyPassword($testPassword, $hash);
    
    if ($verified) {
        $tests['Password Hashing'] = ['status' => 'PASS', 'message' => 'Password hashing and verification working'];
    } else {
        $tests['Password Hashing'] = ['status' => 'FAIL', 'message' => 'Password verification failed'];
        $allPassed = false;
    }
} catch (Exception $e) {
    $tests['Password Hashing'] = ['status' => 'FAIL', 'message' => 'Password hashing error: ' . $e->getMessage()];
    $allPassed = false;
}

// Test 5: Session Token Generation
try {
    $token = generateToken();
    if (strlen($token) >= 32) {
        $tests['Token Generation'] = ['status' => 'PASS', 'message' => 'Token generation working (length: ' . strlen($token) . ')'];
    } else {
        $tests['Token Generation'] = ['status' => 'FAIL', 'message' => 'Token too short: ' . strlen($token)];
        $allPassed = false;
    }
} catch (Exception $e) {
    $tests['Token Generation'] = ['status' => 'FAIL', 'message' => 'Token generation error: ' . $e->getMessage()];
    $allPassed = false;
}

// Test 6: Validation Functions
try {
    $validEmail = validateEmail('test@example.com');
    $invalidEmail = !validateEmail('invalid-email');
    $validPhone = validatePhone('+1234567890');
    $age = calculateAge('1990-01-01');
    
    if ($validEmail && $invalidEmail && $validPhone && $age > 30) {
        $tests['Validation Functions'] = ['status' => 'PASS', 'message' => 'All validation functions working'];
    } else {
        $tests['Validation Functions'] = ['status' => 'FAIL', 'message' => 'Some validation functions failed'];
        $allPassed = false;
    }
} catch (Exception $e) {
    $tests['Validation Functions'] = ['status' => 'FAIL', 'message' => 'Validation error: ' . $e->getMessage()];
    $allPassed = false;
}

// Test 7: File Permissions
$phpFiles = [
    'config.php', 'register.php', 'login.php', 'logout.php', 'check_session.php',
    'get_properties.php', 'get_hotel_rooms.php', 'make_reservation.php',
    'get_reservations.php', 'apply_timeshare.php', 'get_timeshare_info.php',
    'admin.php', 'setup_database.php'
];

$missingFiles = [];
foreach ($phpFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $tests['PHP Files'] = ['status' => 'PASS', 'message' => 'All PHP files present'];
} else {
    $tests['PHP Files'] = ['status' => 'FAIL', 'message' => 'Missing files: ' . implode(', ', $missingFiles)];
    $allPassed = false;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>System Test - Holiday Village</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .header { background: #343a40; color: white; padding: 20px; margin: -40px -40px 40px -40px; }
        .test-result { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .pass { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .fail { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warn { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .status { font-weight: bold; float: right; }
        .summary { padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center; font-size: 1.2em; }
        .overall-pass { background: #d4edda; color: #155724; }
        .overall-fail { background: #f8d7da; color: #721c24; }
        .links { margin: 20px 0; }
        .links a { display: inline-block; margin-right: 15px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .links a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Holiday Village System Test Results</h1>
        <p>Testing all system components for functionality and integrity</p>
    </div>

    <?php foreach ($tests as $testName => $result): ?>
        <div class="test-result <?= strtolower($result['status']) ?>">
            <strong><?= htmlspecialchars($testName) ?></strong>
            <span class="status"><?= $result['status'] ?></span>
            <br>
            <small><?= htmlspecialchars($result['message']) ?></small>
        </div>
    <?php endforeach; ?>

    <div class="summary <?= $allPassed ? 'overall-pass' : 'overall-fail' ?>">
        <?php if ($allPassed): ?>
            ✅ All Tests Passed - System Ready for Use!
        <?php else: ?>
            ❌ Some Tests Failed - Please Review Issues Above
        <?php endif; ?>
    </div>

    <div class="links">
        <h3>Quick Actions:</h3>
        <a href="../index.html">Visit Homepage</a>
        <a href="admin.php">Admin Dashboard</a>
        <a href="setup_database.php">Database Setup</a>
    </div>

    <div style="margin-top: 40px; padding: 20px; background: white; border-radius: 5px;">
        <h3>System Information:</h3>
        <ul>
            <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
            <li><strong>Test Date:</strong> <?= date('Y-m-d H:i:s') ?></li>
            <li><strong>Database:</strong> <?= $tests['Database Connection']['status'] === 'PASS' ? 'Connected' : 'Not Connected' ?></li>
            <li><strong>Total Tests:</strong> <?= count($tests) ?></li>
            <li><strong>Passed:</strong> <?= count(array_filter($tests, function($t) { return $t['status'] === 'PASS'; })) ?></li>
            <li><strong>Failed:</strong> <?= count(array_filter($tests, function($t) { return $t['status'] === 'FAIL'; })) ?></li>
        </ul>
    </div>
</body>
</html>
