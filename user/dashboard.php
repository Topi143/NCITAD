<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Dashboard - NCITAD';
$user_id = $_SESSION['user_id'];

// Fetch user's concern statistics
try {
    // User's concerns by status
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
            SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as declined,
            SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) as unresolved
        FROM concerns
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $concern_stats = $stmt->fetch();
    
    // User's recent concerns (last 5)
    $stmt = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM concern_devices cd WHERE cd.concern_id = c.concern_id) as device_count
        FROM concerns c
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_concerns = $stmt->fetchAll();
    
    // User's concerns from history
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as history_total,
               SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as history_resolved,
               SUM(CASE WHEN status = 'Declined' THEN 1 ELSE 0 END) as history_declined,
               SUM(CASE WHEN status = 'Unresolved' THEN 1 ELSE 0 END) as history_unresolved
        FROM concern_history
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $history_stats = $stmt->fetch();
    
    // This month's concerns
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as month_concerns
        FROM concerns
        WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    ");
    $stmt->execute([$user_id]);
    $month_concerns = $stmt->fetch()['month_concerns'];
    
    // This week's concerns
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as week_concerns
        FROM concerns
        WHERE user_id = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
    ");
    $stmt->execute([$user_id]);
    $week_concerns = $stmt->fetch()['week_concerns'];
    
    // Average resolution time (from history)
    $stmt = $pdo->prepare("
        SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, archived_at)) as avg_hours
        FROM concern_history
        WHERE user_id = ? AND status = 'Resolved'
    ");
    $stmt->execute([$user_id]);
    $avg_resolution = $stmt->fetch()['avg_hours'];
    $avg_resolution_display = $avg_resolution ? round($avg_resolution, 1) . ' hrs' : 'N/A';
    
    // Total concerns (active + history)
    $total_all_concerns = ($concern_stats['total'] ?? 0) + ($history_stats['history_total'] ?? 0);
    
    // Resolution rate
    $total_resolved = ($concern_stats['resolved'] ?? 0) + ($history_stats['history_resolved'] ?? 0);
    $resolution_rate = $total_all_concerns > 0 ? round(($total_resolved / $total_all_concerns) * 100, 1) : 0;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to fetch dashboard data: " . $e->getMessage();
    $concern_stats = ['total' => 0, 'pending' => 0, 'ongoing' => 0, 'resolved' => 0, 'declined' => 0, 'unresolved' => 0];
    $recent_concerns = [];
    $history_stats = ['history_total' => 0, 'history_resolved' => 0, 'history_declined' => 0, 'history_unresolved' => 0];
}

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-speedometer2 text-blue-600"></i>
                My Dashboard
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Welcome back, <?= htmlspecialchars($user['faculty_name']) ?>! Here's your overview.</p>
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
        
        <!-- Total Active Concerns -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-xs font-medium">Active Concerns</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['total'] ?? 0 ?></h3>
                    <p class="text-blue-100 text-xs mt-1">Current tickets</p>
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
                    <p class="text-yellow-100 text-xs mt-1">Awaiting review</p>
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
                    <p class="text-orange-100 text-xs font-medium">In Progress</p>
                    <h3 class="text-3xl font-bold mt-1"><?= $concern_stats['ongoing'] ?? 0 ?></h3>
                    <p class="text-orange-100 text-xs mt-1">Being resolved</p>
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
                    <h3 class="text-3xl font-bold mt-1"><?= $history_stats['history_resolved'] ?? 0 ?></h3>
                    <p class="text-green-100 text-xs mt-1">Completed</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="bi bi-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Secondary Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Total All Time -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">Total All Time</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $total_all_concerns ?></h4>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="bi bi-archive-fill text-xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-white rounded-xl shadow-md p-4 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-xs font-medium">This Month</p>
                    <h4 class="text-2xl font-bold text-gray-800 mt-1"><?= $month_concerns ?></h4>
                </div>
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="bi bi-calendar-month text-xl text-orange-600"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Activity Stats -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
            <i class="bi bi-activity text-blue-600"></i>
            My Activity
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-500 p-2 rounded-lg">
                        <i class="bi bi-calendar-week text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">This Week</p>
                        <p class="text-xs text-gray-600">Concerns submitted</p>
                    </div>
                </div>
                <span class="text-2xl font-bold text-blue-600"><?= $week_concerns ?></span>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="bg-green-500 p-2 rounded-lg">
                        <i class="bi bi-calendar-month text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">This Month</p>
                        <p class="text-xs text-gray-600">Concerns submitted</p>
                    </div>
                </div>
                <span class="text-2xl font-bold text-green-600"><?= $month_concerns ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Concerns & Quick Actions Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        
        <!-- Recent Concerns -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-clock-history text-blue-600"></i>
                    Recent Concerns
                </h3>
                <a href="concernlist.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-1">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <?php if (!empty($recent_concerns)): ?>
                <div class="space-y-3">
                    <?php foreach ($recent_concerns as $concern): ?>
                        <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-800 line-clamp-2 mb-1"><?= htmlspecialchars($concern['description']) ?></p>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                            <?php 
                                                echo $concern['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-700' : 
                                                     ($concern['status'] == 'Ongoing' ? 'bg-orange-100 text-orange-700' : 
                                                     ($concern['status'] == 'Resolved' ? 'bg-green-100 text-green-700' : 
                                                     ($concern['status'] == 'Declined' ? 'bg-red-100 text-red-700' : 
                                                     'bg-gray-100 text-gray-700')));
                                            ?>">
                                            <?= htmlspecialchars($concern['status']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="bi bi-calendar3"></i>
                                            <?= date('M d, Y', strtotime($concern['created_at'])) ?>
                                        </span>
                                        <?php if ($concern['device_count'] > 0): ?>
                                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                                <i class="bi bi-hdd"></i>
                                                <?= $concern['device_count'] ?> device(s)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="bi bi-inbox text-4xl mb-2"></i>
                    <p>No concerns submitted yet</p>
                    <a href="addnewconcern.php" class="text-blue-600 hover:text-blue-700 text-sm font-semibold mt-2 inline-block">
                        Submit your first concern <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
                <i class="bi bi-lightning-charge-fill text-blue-600"></i>
                Quick Actions
            </h3>
            <div class="space-y-3">
                <a href="addnewconcern.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl hover:shadow-md transition-all group">
                    <div class="bg-blue-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="bi bi-plus-circle-fill text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Submit New Concern</p>
                        <p class="text-xs text-gray-600">Report an IT issue or request</p>
                    </div>
                    <i class="bi bi-chevron-right text-gray-400 group-hover:text-blue-600"></i>
                </a>
                
                <a href="concernlist.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl hover:shadow-md transition-all group">
                    <div class="bg-green-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="bi bi-file-earmark-text-fill text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">View My Concerns</p>
                        <p class="text-xs text-gray-600">Track all submitted tickets</p>
                    </div>
                    <i class="bi bi-chevron-right text-gray-400 group-hover:text-green-600"></i>
                </a>
                
                <a href="settings.php" class="flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl hover:shadow-md transition-all group">
                    <div class="bg-purple-500 p-3 rounded-lg group-hover:scale-110 transition-transform">
                        <i class="bi bi-gear-fill text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">Account Settings</p>
                        <p class="text-xs text-gray-600">Update profile and password</p>
                    </div>
                    <i class="bi bi-chevron-right text-gray-400 group-hover:text-purple-600"></i>
                </a>
            </div>
        </div>

    </div>

    <!-- Status Legend -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill text-blue-600"></i>
            Status Legend
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">Pending</span>
                <span class="text-xs text-gray-600">Awaiting admin review</span>
            </div>
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-semibold">Ongoing</span>
                <span class="text-xs text-gray-600">Being worked on</span>
            </div>
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Resolved</span>
                <span class="text-xs text-gray-600">Issue fixed</span>
            </div>
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Declined</span>
                <span class="text-xs text-gray-600">Cannot be addressed</span>
            </div>
            <div class="flex items-center gap-2 p-2 bg-gray-50 rounded-lg">
                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">Unresolved</span>
                <span class="text-xs text-gray-600">Unable to fix</span>
            </div>
        </div>
    </div>

</div>

<script>
// Auto-reload dashboard every 30 seconds to keep stats fresh
let autoReloadInterval;
let countdown = 30;
let isPaused = false;

function startAutoReload() {
    // Clear any existing interval
    if (autoReloadInterval) {
        clearInterval(autoReloadInterval);
    }
    
    autoReloadInterval = setInterval(() => {
        if (!isPaused) {
            countdown--;
            
            if (countdown <= 0) {
                // Reload the page
                location.reload();
            }
        }
    }, 1000);
}

// Pause auto-reload when user is interacting with the page
document.addEventListener('mousemove', () => {
    isPaused = true;
    countdown = 30; // Reset countdown on activity
    
    // Resume after 5 seconds of inactivity
    clearTimeout(window.resumeTimer);
    window.resumeTimer = setTimeout(() => {
        isPaused = false;
    }, 5000);
});

// Start auto-reload when page loads
window.addEventListener('load', () => {
    startAutoReload();
});
</script>

<?php include 'footer.php'; ?>
