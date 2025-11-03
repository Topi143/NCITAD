<?php
session_start();
require_once '../includes/config.php';

// Check admin status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Date range filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // First day of current month
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // Today

// Get overall statistics
$stmt = $pdo->query("SELECT COUNT(*) FROM concerns");
$total_concerns = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM concern_history");
$total_archived = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0");
$total_users = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM facilities");
$total_facilities = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM devices");
$total_devices = $stmt->fetchColumn();

// Status breakdown for active concerns
$status_breakdown = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM concerns 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Archived concerns breakdown
$archived_breakdown = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM concern_history 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top 5 most reported devices
$top_devices = $pdo->query("
    SELECT d.device_name, f.facility_name, COUNT(cd.device_id) as concern_count
    FROM concern_devices cd
    JOIN devices d ON cd.device_id = d.device_id
    JOIN facilities f ON d.facility_id = f.facility_id
    GROUP BY cd.device_id
    ORDER BY concern_count DESC
    LIMIT 5
")->fetchAll();

// Top 5 facilities with most concerns
$top_facilities = $pdo->query("
    SELECT f.facility_name, COUNT(DISTINCT cd.concern_id) as concern_count
    FROM concern_devices cd
    JOIN devices d ON cd.device_id = d.device_id
    JOIN facilities f ON d.facility_id = f.facility_id
    GROUP BY f.facility_id
    ORDER BY concern_count DESC
    LIMIT 5
")->fetchAll();

$page_title = 'Reports & Analytics - NCITAD';
include 'base.php';
?>

<!-- Print Header (only visible when printing) -->
<div class="print-header">
    <h1>NCITAD - Reports & Analytics</h1>
    <p>Norzagaray College IT Assistance Desk</p>
    <p>Ticket Management System - Comprehensive Report</p>
    <p>Generated on: <?= date('F d, Y g:i A') ?></p>
</div>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-graph-up text-blue-600"></i>
                Reports & Analytics
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Comprehensive statistics and insights for the IT ticketing system</p>
        </div>
    </div>
</div>

<!-- Main Statistics Cards -->
<div class="px-4 pb-4 space-y-4">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total Concerns -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="bi bi-list-task text-3xl opacity-80"></i>
                <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold">Active</span>
            </div>
            <h3 class="text-3xl font-bold"><?= $total_concerns ?></h3>
            <p class="text-blue-100 text-xs mt-1">Total Concerns</p>
        </div>

        <!-- Archived -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="bi bi-archive text-3xl opacity-80"></i>
                <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold">History</span>
            </div>
            <h3 class="text-3xl font-bold"><?= $total_archived ?></h3>
            <p class="text-purple-100 text-xs mt-1">Archived Tickets</p>
        </div>

        <!-- Users -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="bi bi-people text-3xl opacity-80"></i>
                <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold">Faculty</span>
            </div>
            <h3 class="text-3xl font-bold"><?= $total_users ?></h3>
            <p class="text-green-100 text-xs mt-1">Registered Users</p>
        </div>

        <!-- Facilities -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="bi bi-building text-3xl opacity-80"></i>
                <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold">Locations</span>
            </div>
            <h3 class="text-3xl font-bold"><?= $total_facilities ?></h3>
            <p class="text-orange-100 text-xs mt-1">Facilities</p>
        </div>

        <!-- Devices -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-4 text-white">
            <div class="flex items-center justify-between mb-2">
                <i class="bi bi-pc-display-horizontal text-3xl opacity-80"></i>
                <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold">Equipment</span>
            </div>
            <h3 class="text-3xl font-bold"><?= $total_devices ?></h3>
            <p class="text-red-100 text-xs mt-1">Total Devices</p>
        </div>
    </div>

    <!-- Status Breakdown Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Active Concerns Status -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-pie-chart-fill text-blue-600"></i>
                Active Concerns Status
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                        <span class="text-sm text-gray-700">Pending</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-800"><?= $status_breakdown['Pending'] ?? 0 ?></span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: <?= $total_concerns > 0 ? (($status_breakdown['Pending'] ?? 0) / $total_concerns * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-orange-500 rounded"></div>
                        <span class="text-sm text-gray-700">Ongoing</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-800"><?= $status_breakdown['Ongoing'] ?? 0 ?></span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: <?= $total_concerns > 0 ? (($status_breakdown['Ongoing'] ?? 0) / $total_concerns * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archived Concerns Status -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-bar-chart-fill text-purple-600"></i>
                Archived Concerns Status
            </h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-green-500 rounded"></div>
                        <span class="text-sm text-gray-700">Resolved</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-800"><?= $archived_breakdown['Resolved'] ?? 0 ?></span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: <?= $total_archived > 0 ? (($archived_breakdown['Resolved'] ?? 0) / $total_archived * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-red-500 rounded"></div>
                        <span class="text-sm text-gray-700">Declined</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-800"><?= $archived_breakdown['Declined'] ?? 0 ?></span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: <?= $total_archived > 0 ? (($archived_breakdown['Declined'] ?? 0) / $total_archived * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 bg-gray-500 rounded"></div>
                        <span class="text-sm text-gray-700">Unresolved</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-gray-800"><?= $archived_breakdown['Unresolved'] ?? 0 ?></span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-500 h-2 rounded-full" style="width: <?= $total_archived > 0 ? (($archived_breakdown['Unresolved'] ?? 0) / $total_archived * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Lists Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Top Devices with Most Issues -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-red-600"></i>
                Top Devices with Most Issues
            </h3>
            <?php if (!empty($top_devices)): ?>
                <div class="space-y-2">
                    <?php foreach ($top_devices as $index => $device): ?>
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="w-8 h-8 bg-red-100 text-red-600 rounded-full flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-gray-800 truncate"><?= htmlspecialchars($device['device_name']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($device['facility_name']) ?></p>
                            </div>
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold">
                                <?= $device['concern_count'] ?> issues
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm text-center py-8">No device issues reported yet</p>
            <?php endif; ?>
        </div>

        <!-- Top Facilities with Most Concerns -->
        <div class="bg-white rounded-xl shadow-lg p-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-building text-orange-600"></i>
                Top Facilities with Most Concerns
            </h3>
            <?php if (!empty($top_facilities)): ?>
                <div class="space-y-2">
                    <?php foreach ($top_facilities as $index => $facility): ?>
                        <div class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg transition-colors">
                            <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-gray-800 truncate"><?= htmlspecialchars($facility['facility_name']) ?></p>
                            </div>
                            <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">
                                <?= $facility['concern_count'] ?> concerns
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm text-center py-8">No facility data available</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Print Footer (only visible when printing) -->
<div class="print-footer">
    <p>This report was generated by NCITAD - Norzagaray College IT Assistance Desk</p>
    <p>Report Date: <?= date('F d, Y g:i A') ?> | Confidential - For Internal Use Only</p>
</div>

<style>
    @media print {
        /* Hide non-essential elements */
        .no-print,
        #mobile-menu-btn,
        #sidebar-overlay,
        #sidebar,
        #toast-container,
        button {
            display: none !important;
        }
        
        /* Reset layout for print */
        .sidebar-full {
            display: none !important;
        }
        
        .main-content-full {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        body {
            overflow: visible !important;
            background: white !important;
        }
        
        .content-wrapper {
            overflow: visible !important;
            height: auto !important;
        }
        
        /* Add print header */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #000;
            page-break-after: avoid;
        }
        
        .print-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000 !important;
        }
        
        .print-header p {
            font-size: 14px;
            color: #666 !important;
            margin: 5px 0;
        }
        
        /* Page header styling */
        .bg-white.shadow-lg {
            box-shadow: none !important;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
            page-break-after: avoid;
        }
        
        /* Statistics cards - convert gradients to borders */
        .bg-gradient-to-br {
            background: white !important;
            border: 2px solid #333 !important;
            page-break-inside: avoid;
        }
        
        .bg-gradient-to-br.from-blue-500 {
            border-color: #3b82f6 !important;
        }
        
        .bg-gradient-to-br.from-purple-500 {
            border-color: #a855f7 !important;
        }
        
        .bg-gradient-to-br.from-green-500 {
            border-color: #22c55e !important;
        }
        
        .bg-gradient-to-br.from-orange-500 {
            border-color: #f97316 !important;
        }
        
        .bg-gradient-to-br.from-red-500 {
            border-color: #ef4444 !important;
        }
        
        /* Make text visible in cards */
        .bg-gradient-to-br * {
            color: #000 !important;
        }
        
        /* Shadow removal */
        .shadow-lg,
        .shadow-xl,
        .shadow-2xl {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
        }
        
        /* Rounded corners for print */
        .rounded-xl {
            border-radius: 8px !important;
        }
        
        /* Page breaks */
        .space-y-4 > * {
            page-break-inside: avoid;
            margin-bottom: 15px;
        }
        
        /* Grid adjustments */
        .grid {
            display: grid !important;
        }
        
        .grid-cols-2,
        .grid-cols-3,
        .grid-cols-5 {
            grid-template-columns: repeat(5, 1fr) !important;
        }
        
        @media print and (max-width: 8.5in) {
            .grid-cols-2,
            .grid-cols-3,
            .grid-cols-5 {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
        
        /* Progress bars visibility */
        .bg-gray-200 {
            background: #e5e7eb !important;
            border: 1px solid #9ca3af;
        }
        
        .bg-yellow-500,
        .bg-orange-500,
        .bg-green-500,
        .bg-red-500,
        .bg-gray-500 {
            background: #333 !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        
        /* Status indicators */
        .bg-red-100,
        .bg-orange-100,
        .bg-green-100,
        .bg-blue-100,
        .bg-yellow-100 {
            border: 1px solid #333 !important;
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        
        /* Table styling */
        table {
            page-break-inside: auto;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        /* Font sizes for print */
        .text-5xl {
            font-size: 2.5rem !important;
        }
        
        .text-3xl {
            font-size: 1.5rem !important;
        }
        
        /* Ensure icons print */
        i.bi {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        
        /* Page margins */
        @page {
            margin: 0.75in;
            size: letter;
        }
        
        /* Footer with page numbers */
        @page {
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-size: 10px;
                color: #666;
            }
        }
        
        /* Hide hover effects */
        *:hover {
            background-color: inherit !important;
        }
        
        /* Print timestamp footer */
        .print-footer {
            display: block !important;
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #666 !important;
        }
    }
    
    /* Print header and footer (hidden by default) */
    .print-header,
    .print-footer {
        display: none;
    }
</style>

<?php include 'footer.php'; ?>
