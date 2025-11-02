<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Admin Dashboard - NCITAD';

// Fetch dashboard statistics
try {
    // Total concerns by status
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
            SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) as unresolved
        FROM concerns
    ");
    $concern_stats = $stmt->fetch();
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
    $total_users = $stmt->fetch()['total'];
    
    // Total admins
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 1");
    $total_admins = $stmt->fetch()['total'];
    
    // Total facilities
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM facilities");
    $total_facilities = $stmt->fetch()['total'];
    
    // Total devices
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices");
    $total_devices = $stmt->fetch()['total'];
    
    // Recent concerns (last 5)
    $stmt = $pdo->query("
        SELECT c.*, u.faculty_name, u.email,
               (SELECT COUNT(*) FROM concern_devices cd WHERE cd.concern_id = c.concern_id) as device_count
        FROM concerns c
        JOIN users u ON c.user_id = u.user_id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $recent_concerns = $stmt->fetchAll();
    
    // Concerns by facility (top 5)
    $stmt = $pdo->query("
        SELECT f.facility_name, COUNT(DISTINCT c.concern_id) as concern_count
        FROM facilities f
        LEFT JOIN devices d ON f.facility_id = d.facility_id
        LEFT JOIN concern_devices cd ON d.device_id = cd.device_id
        LEFT JOIN concerns c ON cd.concern_id = c.concern_id
        GROUP BY f.facility_id, f.facility_name
        ORDER BY concern_count DESC
        LIMIT 5
    ");
    $facility_stats = $stmt->fetchAll();
    
    // Today's activity
    $stmt = $pdo->query("
        SELECT COUNT(*) as today_concerns
        FROM concerns
        WHERE DATE(created_at) = CURDATE()
    ");
    $today_concerns = $stmt->fetch()['today_concerns'];
    
    // This week's activity
    $stmt = $pdo->query("
        SELECT COUNT(*) as week_concerns
        FROM concerns
        WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $week_concerns = $stmt->fetch()['week_concerns'];
    
    // This month's activity
    $stmt = $pdo->query("
        SELECT COUNT(*) as month_concerns
        FROM concerns
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ");
    $month_concerns = $stmt->fetch()['month_concerns'];
    
    // Calculate resolution rate
    $total_resolved = $concern_stats['resolved'] ?? 0;
    $total_all = $concern_stats['total'] ?? 0;
    $resolution_rate = $total_all > 0 ? round(($total_resolved / $total_all) * 100, 1) : 0;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch dashboard data: " . $e->getMessage();
    $concern_stats = ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'resolved' => 0, 'declined' => 0, 'unresolved' => 0];
    $recent_concerns = [];
    $facility_stats = [];
}

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-speedometer2 text-blue-600"></i>
                Dashboard Overview
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Here's your system overview.</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
            <i class="bi bi-calendar-event text-blue-600"></i>
            <span class="font-semibold"><?= date('l, F j, Y') ?></span>
        </div>
    </div>
</div>

<!-- Dashboard Content -->
<div class="px-4 pb-4 space-y-4">
    
    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Concerns -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-xs font-medium">Total Concerns</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['total'] ?? 0 ?></h3>
                    <p class="text-blue-100 text-xs mt-1">All time</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="bi bi-list-task text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Pending Concerns -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-4 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-xs font-medium">Pending</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['pending'] ?? 0 ?></h3>
                    <p class="text-yellow-100 text-xs mt-1">Awaiting action</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="bi bi-clock-history text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Ongoing Concerns -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-4 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-xs font-medium">Ongoing</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['ongoing'] ?? 0 ?></h3>
                    <p class="text-orange-100 text-xs mt-1">In progress</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="bi bi-arrow-repeat text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Resolved Concerns -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-xs font-medium">Resolved</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['resolved'] ?? 0 ?></h3>
                    <p class="text-green-100 text-xs mt-1">Completed</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="bi bi-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Secondary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Users -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">Total Users</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_users ?></h4>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="bi bi-people-fill text-xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Admins -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">Administrators</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_admins ?></h4>
                </div>
                <div class="bg-indigo-100 p-3 rounded-lg">
                    <i class="bi bi-shield-check text-xl text-indigo-600"></i>
                </div>
            </div>
        </div>

        <!-- Facilities -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">Facilities</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_facilities ?></h4>
                </div>
                <div class="bg-cyan-100 p-3 rounded-lg">
                    <i class="bi bi-building text-xl text-cyan-600"></i>
                </div>
            </div>
        </div>

        <!-- Devices -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">Devices</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_devices ?></h4>
                </div>
                <div class="bg-teal-100 p-3 rounded-lg">
                    <i class="bi bi-hdd-rack text-xl text-teal-600"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Activity Stats Row -->
    <div class="grid grid-cols-1 gap-4">
        
        <!-- Activity Timeline -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
                <i class="bi bi-activity text-blue-600"></i>
                Activity Timeline
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-500 p-2 rounded-lg">
                            <i class="bi bi-calendar-day text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Today</p>
                            <p class="text-xs text-gray-600">New concerns</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-blue-600"><?= $today_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="bg-green-500 p-2 rounded-lg">
                            <i class="bi bi-calendar-week text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">This Week</p>
                            <p class="text-xs text-gray-600">New concerns</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-green-600"><?= $week_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="bg-purple-500 p-2 rounded-lg">
                            <i class="bi bi-calendar-month text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">This Month</p>
                            <p class="text-xs text-gray-600">New concerns</p>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-purple-600"><?= $month_concerns ?></span>
                </div>
            </div>
        </div>

    </div>

    <!-- Top Facilities & Recent Concerns Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        
        <!-- Top Facilities by Concerns -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
                <i class="bi bi-building text-blue-600"></i>
                Top Facilities by Concerns
            </h3>
            <?php if (!empty($facility_stats)): ?>
                <div class="space-y-3">
                    <?php foreach ($facility_stats as $index => $facility): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($facility['facility_name']) ?></p>
                            </div>
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                    <i class="bi bi-exclamation-circle"></i>
                                    <?= $facility['concern_count'] ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="bi bi-inbox text-4xl mb-2"></i>
                    <p>No facility data available</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Concerns -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-clock-history text-blue-600"></i>
                    Recent Concerns
                </h3>
                <a href="concerns.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php if (!empty($recent_concerns)): ?>
                <div class="space-y-3">
                    <?php foreach ($recent_concerns as $concern): ?>
                        <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 line-clamp-2"><?= htmlspecialchars($concern['description']) ?></p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <i class="bi bi-person-circle"></i>
                                        <?= htmlspecialchars($concern['faculty_name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="bi bi-clock"></i>
                                        <?= date('M j, Y g:i A', strtotime($concern['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <?php
                                    $status_colors = [
                                        'Pending' => 'bg-yellow-100 text-yellow-700',
                                        'Ongoing' => 'bg-orange-100 text-orange-700',
                                        'Resolved' => 'bg-green-100 text-green-700',
                                        'Declined' => 'bg-red-100 text-red-700',
                                        'Unresolved' => 'bg-gray-100 text-gray-700'
                                    ];
                                    $color_class = $status_colors[$concern['status']] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold <?= $color_class ?>">
                                        <?= htmlspecialchars($concern['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="bi bi-inbox text-4xl mb-2"></i>
                    <p>No recent concerns</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
            <i class="bi bi-lightning-charge-fill text-blue-600"></i>
            Quick Actions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <a href="concerns.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl hover:shadow-md transition-all group">
                <div class="bg-blue-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                    <i class="bi bi-list-task text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">View Concerns</p>
                    <p class="text-xs text-gray-600">Manage tickets</p>
                </div>
            </a>
            
            <a href="adduserandadmin.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl hover:shadow-md transition-all group">
                <div class="bg-purple-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                    <i class="bi bi-person-plus-fill text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Add User</p>
                    <p class="text-xs text-gray-600">Create account</p>
                </div>
            </a>
            
            <a href="facilities_devices.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl hover:shadow-md transition-all group">
                <div class="bg-green-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                    <i class="bi bi-hdd-rack-fill text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Manage Devices</p>
                    <p class="text-xs text-gray-600">Facilities & equipment</p>
                </div>
            </a>
            
            <a href="history.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl hover:shadow-md transition-all group">
                <div class="bg-orange-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                    <i class="bi bi-archive-fill text-white text-xl"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">View History</p>
                    <p class="text-xs text-gray-600">Past records</p>
                </div>
            </a>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
