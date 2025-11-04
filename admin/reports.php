<?php
session_start();
require_once '../includes/config.php';

// Check admin status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Get overall statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM concerns");
$total_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concerns WHERE status = 'Pending'");
$pending_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concerns WHERE status = 'Ongoing'");
$ongoing_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concern_history");
$total_archived = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Resolved'");
$resolved_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Declined'");
$declined_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Unresolved'");
$unresolved_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM facilities");
$total_facilities = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM devices");
$total_devices = $stmt->fetchColumn();

$page_title = 'System Reports - NCITAD';
include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-file-earmark-bar-graph text-blue-600"></i>
                System Reports
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Overview of ticketing system statistics</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="px-4 pb-4 space-y-6">
    
    <!-- Summary Section -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="bi bi-clipboard-data text-blue-600"></i>
            System Summary
        </h2>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
                <div class="text-3xl font-bold text-blue-600"><?= $total_concerns ?></div>
                <div class="text-sm text-gray-600 mt-1">Active Tickets</div>
            </div>
            
            <div class="text-center p-4 bg-purple-50 rounded-lg border-2 border-purple-200">
                <div class="text-3xl font-bold text-purple-600"><?= $total_archived ?></div>
                <div class="text-sm text-gray-600 mt-1">Archived Tickets</div>
            </div>
            
            <div class="text-center p-4 bg-green-50 rounded-lg border-2 border-green-200">
                <div class="text-3xl font-bold text-green-600"><?= $total_users ?></div>
                <div class="text-sm text-gray-600 mt-1">Faculty Users</div>
            </div>
            
            <div class="text-center p-4 bg-orange-50 rounded-lg border-2 border-orange-200">
                <div class="text-3xl font-bold text-orange-600"><?= $total_facilities ?></div>
                <div class="text-sm text-gray-600 mt-1">Facilities</div>
            </div>
            
            <div class="text-center p-4 bg-red-50 rounded-lg border-2 border-red-200">
                <div class="text-3xl font-bold text-red-600"><?= $total_devices ?></div>
                <div class="text-sm text-gray-600 mt-1">Devices</div>
            </div>
        </div>
    </div>

    <!-- Ticket Status Breakdown -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Active Tickets -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-list-check text-blue-600"></i>
                Active Tickets
            </h3>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <span class="font-medium text-gray-700">Pending</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $pending_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between pb-3 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-orange-500 rounded-full"></div>
                        <span class="font-medium text-gray-700">Ongoing</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $ongoing_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between pt-2 bg-blue-50 -mx-2 px-2 py-3 rounded">
                    <span class="font-bold text-gray-700">Total Active</span>
                    <span class="text-2xl font-bold text-blue-600"><?= $total_concerns ?></span>
                </div>
            </div>
        </div>

        <!-- Archived Tickets -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-archive text-purple-600"></i>
                Archived Tickets
            </h3>
            
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="font-medium text-gray-700">Resolved</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $resolved_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between pb-3 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="font-medium text-gray-700">Declined</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $declined_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between pb-3 border-b">
                    <div class="flex items-center gap-3">
                        <div class="w-3 h-3 bg-gray-500 rounded-full"></div>
                        <span class="font-medium text-gray-700">Unresolved</span>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $unresolved_concerns ?></span>
                </div>
                
                <div class="flex items-center justify-between pt-2 bg-purple-50 -mx-2 px-2 py-3 rounded">
                    <span class="font-bold text-gray-700">Total Archived</span>
                    <span class="text-2xl font-bold text-purple-600"><?= $total_archived ?></span>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Overall Statistics -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="bi bi-graph-up text-green-600"></i>
            Overall Performance
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                <div class="text-4xl font-bold text-green-600">
                    <?php 
                    echo $total_archived > 0 ? round(($resolved_concerns / $total_archived) * 100) : 0;
                    ?>%
                </div>
                <div class="text-sm text-gray-700 mt-2 font-medium">Resolution Rate</div>
                <div class="text-xs text-gray-500 mt-1"><?= $resolved_concerns ?> of <?= $total_archived ?> archived</div>
            </div>
            
            <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                <div class="text-4xl font-bold text-blue-600"><?= $total_archived ?></div>
                <div class="text-sm text-gray-700 mt-2 font-medium">Closed Tickets</div>
                <div class="text-xs text-gray-500 mt-1">All time</div>
            </div>
            
            <div class="text-center p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg">
                <div class="text-4xl font-bold text-orange-600"><?= $total_concerns ?></div>
                <div class="text-sm text-gray-700 mt-2 font-medium">Pending Action</div>
                <div class="text-xs text-gray-500 mt-1">Requires attention</div>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
