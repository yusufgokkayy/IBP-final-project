<?php
require_once 'config_oracle.php';

// Simple admin authentication (in production, use proper admin authentication)
$admin_password = 'admin123'; // Change this!
$authenticated = false;

if (isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === $admin_password) {
        $authenticated = true;
        setcookie('admin_auth', md5($admin_password), time() + 3600);
    }
} elseif (isset($_COOKIE['admin_auth']) && $_COOKIE['admin_auth'] === md5($admin_password)) {
    $authenticated = true;
}

if (!$authenticated) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login - Holiday Village</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
            .login-form { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
            button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #0056b3; }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>Admin Access</h2>
            <form method="POST">
                <input type="password" name="admin_password" placeholder="Enter admin password" required>
                <button type="submit">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin is authenticated, show dashboard
try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    // Get statistics
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as "count" FROM users WHERE is_active = 1");
    $stats['users'] = $stmt->fetch()['count'];
    
    // Total reservations
    $stmt = $pdo->query("SELECT COUNT(*) as "count" FROM reservations");
    $stats['reservations'] = $stmt->fetch()['count'];
    
    // Total timeshare contracts
    $stmt = $pdo->query("SELECT COUNT(*) as "count" FROM timeshare_contracts");
    $stats['timeshare_contracts'] = $stmt->fetch()['count'];
    
    // Revenue from reservations
    $stmt = $pdo->query("SELECT SUM(total_price) as revenue FROM reservations WHERE status = 'confirmed'");
    $stats['reservation_revenue'] = $stmt->fetch()['revenue'] ?: 0;
    
    // Recent reservations
    $stmt = $pdo->query("
        SELECT 
            r.*,
            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
            u.email,
            CASE 
                WHEN r.property_type = 'hotel_room' THEN CONCAT(h.name, ' - Room ', rm.room_number)
                WHEN r.property_type = 'house' THEN hs.name
            END as property_name
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN rooms rm ON r.property_type = 'hotel_room' AND r.property_id = rm.id
        LEFT JOIN hotels h ON rm.hotel_id = h.id
        LEFT JOIN houses hs ON r.property_type = 'house' AND r.property_id = hs.id
        ORDER BY r.booking_date DESC
        ROWNUM <= 10
    ");
    $recent_reservations = $stmt->fetchAll();
    
    // Recent timeshare contracts
    $stmt = $pdo->query("
        SELECT 
            tc.*,
            CONCAT(u.first_name, ' ', u.last_name) as customer_name,
            u.email,
            h.name as house_name
        FROM timeshare_contracts tc
        JOIN users u ON tc.user_id = u.id
        JOIN houses h ON tc.house_id = h.id
        ORDER BY tc.created_at DESC
        ROWNUM <= 10
    ");
    $recent_timeshares = $stmt->fetchAll();
    
} catch (Exception $e) {
    echo "Error loading dashboard: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Holiday Village</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f8f9fa; }
        .header { background: #343a40; color: white; padding: 20px; }
        .header h1 { margin: 0; }
        .logout { float: right; color: #ffc107; text-decoration: none; }
        .logout:hover { text-decoration: underline; }
        .container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #6c757d; margin-top: 5px; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h3 { margin-top: 0; color: #343a40; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: bold; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }
        .status.confirmed { background: #d4edda; color: #155724; }
        .status.pending { background: #fff3cd; color: #856404; }
        .status.active { background: #d1ecf1; color: #0c5460; }
        .nav-links { margin: 20px 0; }
        .nav-links a { display: inline-block; margin-right: 15px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .nav-links a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Holiday Village Admin Dashboard</h1>
        <a href="?logout=1" class="logout">Logout</a>
    </div>
    
    <div class="container">
        <div class="nav-links">
            <a href="../index.html">View Website</a>
            <a href="setup_database.php">Database Setup</a>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['users'] ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['reservations'] ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['timeshare_contracts'] ?></div>
                <div class="stat-label">Timeshare Contracts</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($stats['reservation_revenue'], 0) ?></div>
                <div class="stat-label">Reservation Revenue</div>
            </div>
        </div>
        
        <div class="section">
            <h3>Recent Reservations</h3>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Property</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Guests</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_reservations as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['customer_name']) ?><br><small><?= htmlspecialchars($res['email']) ?></small></td>
                        <td><?= htmlspecialchars($res['property_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($res['check_in_date'])) ?></td>
                        <td><?= date('M j, Y', strtotime($res['check_out_date'])) ?></td>
                        <td><?= $res['num_guests'] ?></td>
                        <td>$<?= number_format($res['total_price'], 2) ?></td>
                        <td><span class="status <?= $res['status'] ?>"><?= ucfirst($res['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h3>Recent Timeshare Contracts</h3>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>House</th>
                        <th>Period</th>
                        <th>Duration</th>
                        <th>Purchase Price</th>
                        <th>Status</th>
                        <th>Contract Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_timeshares as $ts): ?>
                    <tr>
                        <td><?= htmlspecialchars($ts['customer_name']) ?><br><small><?= htmlspecialchars($ts['email']) ?></small></td>
                        <td><?= htmlspecialchars($ts['house_name']) ?></td>
                        <td><?= ucfirst($ts['period']) ?></td>
                        <td><?= $ts['duration_weeks'] ?> weeks</td>
                        <td>$<?= number_format($ts['purchase_price'], 2) ?></td>
                        <td><span class="status <?= $ts['status'] ?>"><?= ucfirst($ts['status']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($ts['contract_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    setcookie('admin_auth', '', time() - 3600);
    header('Location: admin.php');
    exit;
}
?>
