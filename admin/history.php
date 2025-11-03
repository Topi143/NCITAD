<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Ticket History';

// Pagination settings
$items_per_page = 10;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'resolved';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Filter parameters
$filter_facility = isset($_GET['facility']) ? intval($_GET['facility']) : 0;
$filter_device = isset($_GET['device']) ? intval($_GET['device']) : 0;
$filter_date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Validate tab
$valid_tabs = ['resolved', 'unresolved', 'declined'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'resolved';
}

// Map tab to status
$status_map = [
    'resolved' => 'Resolved',
    'unresolved' => 'Unresolved',
    'declined' => 'Declined'
];
$current_status = $status_map[$current_tab];

// Build filter conditions
$filter_conditions = ["ch.status = ?"];
$filter_params = [$current_status];

if ($filter_facility > 0) {
    $filter_conditions[] = "JSON_SEARCH(ch.devices, 'one', ?, NULL, '$[*].facility_name') IS NOT NULL";
    // Get facility name
    $fac_stmt = $pdo->prepare("SELECT facility_name FROM facilities WHERE facility_id = ?");
    $fac_stmt->execute([$filter_facility]);
    $fac_name = $fac_stmt->fetchColumn();
    $filter_params[] = $fac_name;
}

if ($filter_device > 0) {
    $filter_conditions[] = "JSON_SEARCH(ch.devices, 'one', ?, NULL, '$[*].device_name') IS NOT NULL";
    // Get device name
    $device_stmt = $pdo->prepare("SELECT device_name FROM devices WHERE device_id = ?");
    $device_stmt->execute([$filter_device]);
    $device_name = $device_stmt->fetchColumn();
    $filter_params[] = $device_name;
}

// Date range filter
if ($filter_date_range === 'last_30_days') {
    $filter_conditions[] = "ch.archived_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filter_date_range === 'last_7_days') {
    $filter_conditions[] = "ch.archived_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter_date_range === 'today') {
    $filter_conditions[] = "DATE(ch.archived_at) = CURDATE()";
}

if (!empty($search_query)) {
    $filter_conditions[] = "(ch.description LIKE ? OR ch.faculty_name LIKE ? OR ch.email LIKE ? OR ch.resolution_feedback LIKE ?)";
    $search_param = "%{$search_query}%";
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
}

$filter_sql = " WHERE " . implode(" AND ", $filter_conditions);

// Fetch facilities for filter
$fac_stmt = $pdo->query("SELECT * FROM facilities ORDER BY facility_name ASC");
$facilities = $fac_stmt->fetchAll();

// Fetch devices for filter
$device_stmt = $pdo->query("SELECT d.*, fac.facility_name FROM devices d JOIN facilities fac ON d.facility_id = fac.facility_id ORDER BY d.device_name ASC");
$devices = $device_stmt->fetchAll();

// Count total tickets for current status
$count_sql = "SELECT COUNT(*) as total FROM concern_history ch $filter_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($filter_params);
$total_tickets = $count_stmt->fetch()['total'];
$total_pages = ceil($total_tickets / $items_per_page);
$offset = ($page - 1) * $items_per_page;

// Fetch tickets with pagination
$tickets_sql = "
    SELECT 
        ch.*
    FROM concern_history ch
    $filter_sql
    ORDER BY ch.archived_at DESC
    LIMIT $items_per_page OFFSET $offset
";
$tickets_stmt = $pdo->prepare($tickets_sql);
$tickets_stmt->execute($filter_params);
$tickets = $tickets_stmt->fetchAll();

// Get counts for all statuses (for tab badges)
$resolved_count = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Resolved'")->fetchColumn();
$unresolved_count = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Unresolved'")->fetchColumn();
$declined_count = $pdo->query("SELECT COUNT(*) FROM concern_history WHERE status = 'Declined'")->fetchColumn();

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-clock-history text-purple-600"></i>
                Ticket History
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">View archived and completed tickets</p>
        </div>
        <a href="concerns.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md hover:shadow-lg text-sm font-semibold">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Concerns</span>
        </a>
    </div>
</div>

<!-- Filters Section -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col lg:flex-row gap-3">
        <!-- Search Bar -->
        <div class="flex-1">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchInput" value="<?= htmlspecialchars($search_query) ?>" 
                       placeholder="Search by description, faculty name, email, or feedback..." 
                       class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="w-full lg:w-48">
            <select id="dateRangeFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="all" <?= $filter_date_range === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="today" <?= $filter_date_range === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="last_7_days" <?= $filter_date_range === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="last_30_days" <?= $filter_date_range === 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>
        </div>
        
        <!-- Facility Filter -->
        <div class="w-full lg:w-48">
            <select id="facilityFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="0">All Facilities</option>
                <?php foreach ($facilities as $fac): ?>
                    <option value="<?= $fac['facility_id'] ?>" <?= $filter_facility == $fac['facility_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fac['facility_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Device Filter -->
        <div class="w-full lg:w-48">
            <select id="deviceFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <option value="0">All Devices</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?= $device['device_id'] ?>" 
                            data-facility="<?= $device['facility_id'] ?>"
                            <?= $filter_device == $device['device_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($device['device_name']) ?> - <?= htmlspecialchars($device['facility_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Clear Button -->
        <div>
            <button type="button" onclick="clearFilters()" class="w-full px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-semibold transition-colors text-sm flex items-center justify-center gap-2">
                <i class="bi bi-x-circle"></i>
                Clear
            </button>
        </div>
    </div>
</div>

<!-- Stats Overview -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-xs font-medium">Resolved Tickets</p>
                <h3 class="text-3xl font-bold mt-1"><?= $resolved_count ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-check-circle-fill text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-xs font-medium">Unresolved Tickets</p>
                <h3 class="text-3xl font-bold mt-1"><?= $unresolved_count ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-exclamation-circle-fill text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-100 text-xs font-medium">Declined Tickets</p>
                <h3 class="text-3xl font-bold mt-1"><?= $declined_count ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-x-circle-fill text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabbed Interface -->
<div class="bg-white shadow-lg rounded-xl overflow-hidden flex flex-col" style="height: calc(100vh - 310px);">
    <!-- Tabs Header -->
    <div class="flex border-b border-gray-200 bg-gray-50 flex-shrink-0">
        <button onclick="switchTab('resolved')" 
                class="flex-1 px-4 py-3 text-sm font-semibold transition-all relative <?= $current_tab === 'resolved' ? 'text-green-600 bg-white' : 'text-gray-600 hover:text-green-600 hover:bg-gray-100' ?>"
                id="tab-resolved">
            <div class="flex items-center justify-center gap-2">
                <i class="bi bi-check-circle-fill"></i>
                <span>Resolved</span>
                <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-0.5 rounded-full"><?= $resolved_count ?></span>
            </div>
            <?php if ($current_tab === 'resolved'): ?>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-green-600"></div>
            <?php endif; ?>
        </button>
        
        <button onclick="switchTab('unresolved')" 
                class="flex-1 px-4 py-3 text-sm font-semibold transition-all relative <?= $current_tab === 'unresolved' ? 'text-orange-600 bg-white' : 'text-gray-600 hover:text-orange-600 hover:bg-gray-100' ?>"
                id="tab-unresolved">
            <div class="flex items-center justify-center gap-2">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Unresolved</span>
                <span class="bg-orange-100 text-orange-800 text-xs font-bold px-2 py-0.5 rounded-full"><?= $unresolved_count ?></span>
            </div>
            <?php if ($current_tab === 'unresolved'): ?>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-orange-600"></div>
            <?php endif; ?>
        </button>
        
        <button onclick="switchTab('declined')" 
                class="flex-1 px-4 py-3 text-sm font-semibold transition-all relative <?= $current_tab === 'declined' ? 'text-red-600 bg-white' : 'text-gray-600 hover:text-red-600 hover:bg-gray-100' ?>"
                id="tab-declined">
            <div class="flex items-center justify-center gap-2">
                <i class="bi bi-x-circle-fill"></i>
                <span>Declined</span>
                <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-0.5 rounded-full"><?= $declined_count ?></span>
            </div>
            <?php if ($current_tab === 'declined'): ?>
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-red-600"></div>
            <?php endif; ?>
        </button>
    </div>
    
    <!-- Tab Content -->
    <div class="p-4 overflow-y-auto flex-1">
        <?php if (empty($tickets)): ?>
            <div class="flex flex-col items-center justify-center py-16">
                <i class="bi bi-inbox text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No <?= ucfirst($current_tab) ?> Tickets</h3>
                <p class="text-gray-500 text-sm">There are no tickets with this status yet.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <?php foreach ($tickets as $ticket): 
                    // Parse devices JSON
                    $devices = json_decode($ticket['devices'], true);
                    $devices_list = [];
                    if ($devices && is_array($devices)) {
                        foreach ($devices as $device) {
                            $devices_list[] = htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['facility_name']) . ')';
                        }
                    }
                    
                    // Status styling
                    $status_colors = [
                        'Resolved' => ['border' => 'border-green-500', 'bg' => 'bg-green-50', 'badge' => 'bg-green-100 text-green-800', 'icon' => 'bi-check-circle-fill text-green-600'],
                        'Unresolved' => ['border' => 'border-orange-500', 'bg' => 'bg-orange-50', 'badge' => 'bg-orange-100 text-orange-800', 'icon' => 'bi-exclamation-circle-fill text-orange-600'],
                        'Declined' => ['border' => 'border-red-500', 'bg' => 'bg-red-50', 'badge' => 'bg-red-100 text-red-800', 'icon' => 'bi-x-circle-fill text-red-600']
                    ];
                    $colors = $status_colors[$ticket['status']];
                ?>
                    <div class="border-l-4 <?= $colors['border'] ?> <?= $colors['bg'] ?> rounded-lg p-4 hover:shadow-lg transition-shadow cursor-pointer" onclick="viewTicketDetails(<?= $ticket['id'] ?>)">
                        <!-- Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-500">Ticket #<?= $ticket['concern_id'] ?></span>
                                <span class="<?= $colors['badge'] ?> text-xs font-semibold px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <i class="bi <?= $colors['icon'] ?>"></i>
                                    <?= $ticket['status'] ?>
                                </span>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">
                                    <i class="bi bi-calendar3"></i>
                                    <?= date('M d, Y', strtotime($ticket['archived_at'])) ?>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <?= date('g:i A', strtotime($ticket['archived_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <p class="text-gray-800 text-sm font-medium mb-3 line-clamp-2">
                            <?= htmlspecialchars($ticket['description']) ?>
                        </p>
                        
                        <!-- Details -->
                        <div class="space-y-2 mb-3 text-xs">
                            <div class="flex items-start gap-2">
                                <i class="bi bi-person-fill text-gray-400 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold text-gray-700"><?= htmlspecialchars($ticket['faculty_name']) ?></span>
                                    <span class="text-gray-500 ml-1">(<?= htmlspecialchars($ticket['email']) ?>)</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($devices_list)): ?>
                                <div class="flex items-start gap-2">
                                    <i class="bi bi-pc-display text-gray-400 mt-0.5"></i>
                                    <span class="text-gray-700"><?= implode(', ', $devices_list) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-start gap-2">
                                <i class="bi bi-clock-history text-gray-400 mt-0.5"></i>
                                <div class="text-gray-700">
                                    <div><span class="font-semibold">Created:</span> <?= date('M d, Y g:i A', strtotime($ticket['created_at'])) ?></div>
                                    <div class="mt-1">
                                        <span class="font-semibold"><?= $ticket['status'] ?>:</span> 
                                        <span class="text-<?= $ticket['status'] === 'Resolved' ? 'green' : ($ticket['status'] === 'Declined' ? 'red' : 'orange') ?>-600 font-medium">
                                            <?= date('M d, Y g:i A', strtotime($ticket['archived_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Feedback -->
                        <?php if (!empty($ticket['resolution_feedback'])): ?>
                            <div class="border-t border-gray-300 pt-3 mt-3">
                                <div class="flex items-start gap-2">
                                    <i class="bi bi-chat-left-text-fill text-gray-400 text-xs mt-0.5"></i>
                                    <div class="flex-1">
                                        <p class="text-xs font-semibold text-gray-600 mb-1">Resolution Feedback:</p>
                                        <p class="text-xs text-gray-700 line-clamp-2"><?= htmlspecialchars($ticket['resolution_feedback']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination Footer -->
    <?php if ($total_pages > 1): ?>
        <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-white flex-shrink-0">
            <div class="text-xs text-gray-600">
                Page <?= $page ?> of <?= $total_pages ?>
            </div>
            
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                    <a href="?tab=<?= $current_tab ?>&page=<?= $page - 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $filter_date_range !== 'all' ? '&date_range=' . $filter_date_range : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" 
                       class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium transition-colors">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                // Determine active page color based on current tab
                $active_colors = [
                    'resolved' => 'bg-green-600 text-white',
                    'unresolved' => 'bg-orange-600 text-white',
                    'declined' => 'bg-red-600 text-white'
                ];
                $active_class = $active_colors[$current_tab];
                ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?tab=<?= $current_tab ?>&page=<?= $i ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $filter_date_range !== 'all' ? '&date_range=' . $filter_date_range : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" 
                       class="px-2 py-1 <?= $i == $page ? $active_class : 'bg-gray-200 hover:bg-gray-300' ?> rounded text-xs font-medium transition-colors">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?tab=<?= $current_tab ?>&page=<?= $page + 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $filter_date_range !== 'all' ? '&date_range=' . $filter_date_range : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>" 
                       class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium transition-colors">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-white flex-shrink-0">
            <div class="text-xs text-gray-600">
                Page 1 of 1
            </div>
            <div class="text-xs text-gray-400">
                No pagination needed
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Ticket Details Modal -->
<div id="ticketModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-4 flex justify-between items-center no-print">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <i class="bi bi-file-earmark-text-fill"></i>
                Ticket Details
            </h3>
            <div class="flex items-center gap-3">
                <button onclick="printTicket()" class="text-white hover:text-gray-200 flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded-lg transition-colors">
                    <i class="bi bi-printer-fill"></i>
                    <span class="text-sm font-semibold">Print</span>
                </button>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-2xl leading-none">
                    &times;
                </button>
            </div>
        </div>
        
        <div id="modalContent" class="p-6 overflow-y-auto" style="max-height: calc(90vh - 80px);">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Print Styles */
@media print {
    /* Hide everything except modal content */
    body * {
        visibility: hidden;
    }
    
    #ticketModal, #ticketModal * {
        visibility: visible;
    }
    
    #ticketModal {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: white;
        padding: 0;
        margin: 0;
    }
    
    #ticketModal > div {
        max-width: 100%;
        max-height: 100%;
        box-shadow: none;
        border-radius: 0;
        overflow: visible;
    }
    
    #modalContent {
        max-height: 100% !important;
        overflow: visible !important;
        padding: 20px;
    }
    
    /* Hide modal header and buttons */
    .no-print {
        display: none !important;
    }
    
    /* Add print header */
    #modalContent::before {
        content: '';
        display: block;
        margin-bottom: 20px;
    }
    
    /* Ensure proper page breaks */
    .print-section {
        page-break-inside: avoid;
    }
    
    /* Remove backgrounds for printing */
    * {
        background: white !important;
        color: black !important;
    }
    
    /* Keep badge colors visible */
    .bg-green-100, .bg-orange-100, .bg-red-100, .bg-blue-100 {
        border: 1px solid #000 !important;
    }
}

/* Print header (only visible when printing) */
.print-header {
    display: none;
}

@media print {
    .print-header {
        display: block;
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #000;
    }
    
    .print-header h1 {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .print-header p {
        font-size: 14px;
        color: #666 !important;
    }
}
</style>

<script>
let filterTimeout = null;

// Tab switching
function switchTab(tab) {
    const search = document.getElementById('searchInput').value;
    const facility = document.getElementById('facilityFilter').value;
    const device = document.getElementById('deviceFilter').value;
    const dateRange = document.getElementById('dateRangeFilter').value;
    
    const params = new URLSearchParams();
    params.append('tab', tab);
    if (search) params.append('search', search);
    if (facility && facility !== '0') params.append('facility', facility);
    if (device && device !== '0') params.append('device', device);
    if (dateRange && dateRange !== 'all') params.append('date_range', dateRange);
    
    window.location.href = 'history.php?' + params.toString();
}

// Filter functions
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const facility = document.getElementById('facilityFilter').value;
    const device = document.getElementById('deviceFilter').value;
    const dateRange = document.getElementById('dateRangeFilter').value;
    const currentTab = '<?= $current_tab ?>';
    
    const params = new URLSearchParams();
    params.append('tab', currentTab);
    if (search) params.append('search', search);
    if (facility && facility !== '0') params.append('facility', facility);
    if (device && device !== '0') params.append('device', device);
    if (dateRange && dateRange !== 'all') params.append('date_range', dateRange);
    
    window.location.href = 'history.php?' + params.toString();
}

function clearFilters() {
    const currentTab = '<?= $current_tab ?>';
    window.location.href = 'history.php?tab=' + currentTab;
}

// Filter device dropdown based on selected facility
function filterDevicesByFacility() {
    const facilityId = document.getElementById('facilityFilter').value;
    const deviceFilter = document.getElementById('deviceFilter');
    const allOptions = deviceFilter.querySelectorAll('option');
    
    // Reset device selection if current device doesn't belong to selected facility
    const currentDevice = deviceFilter.value;
    if (currentDevice !== '0' && facilityId !== '0') {
        const currentOption = deviceFilter.querySelector(`option[value="${currentDevice}"]`);
        if (currentOption && currentOption.dataset.facility !== facilityId) {
            deviceFilter.value = '0';
        }
    }
    
    // Show/hide device options based on facility
    allOptions.forEach(option => {
        if (option.value === '0') {
            option.style.display = 'block'; // Always show "All Devices"
        } else if (facilityId === '0') {
            option.style.display = 'block'; // Show all devices when "All Facilities" is selected
        } else {
            option.style.display = option.dataset.facility === facilityId ? 'block' : 'none';
        }
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const facilityFilter = document.getElementById('facilityFilter');
    const deviceFilter = document.getElementById('deviceFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');
    
    // Initialize device filter on page load
    filterDevicesByFacility();
    
    // Debounced search input
    searchInput.addEventListener('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 800);
    });
    
    // Facility filter change
    facilityFilter.addEventListener('change', function() {
        filterDevicesByFacility();
        applyFilters();
    });
    
    // Device filter change
    deviceFilter.addEventListener('change', applyFilters);
    
    // Date range filter change
    dateRangeFilter.addEventListener('change', applyFilters);
    
    // Apply filter on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(filterTimeout);
            applyFilters();
        }
    });
});

// View ticket details
function viewTicketDetails(ticketId) {
    // Fetch ticket details via AJAX
    fetch(`get_ticket_details.php?id=${ticketId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTicketModal(data.ticket);
            } else {
                showAlert('Failed to load ticket details', 'error');
            }
        })
        .catch(error => {
            showAlert('An error occurred', 'error');
            console.error('Error:', error);
        });
}

function displayTicketModal(ticket) {
    const modal = document.getElementById('ticketModal');
    const modalContent = document.getElementById('modalContent');
    
    // Parse devices
    let devicesHtml = '';
    if (ticket.devices) {
        const devices = JSON.parse(ticket.devices);
        devicesHtml = devices.map(d => `
            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                <i class="bi bi-pc-display"></i>
                ${d.device_name} (${d.facility_name})
            </span>
        `).join(' ');
    }
    
    // Status styling
    const statusConfig = {
        'Resolved': { color: 'green', icon: 'check-circle-fill' },
        'Unresolved': { color: 'orange', icon: 'exclamation-circle-fill' },
        'Declined': { color: 'red', icon: 'x-circle-fill' }
    };
    const config = statusConfig[ticket.status];
    
    // Calculate duration
    const createdDate = new Date(ticket.created_at);
    const archivedDate = new Date(ticket.archived_at);
    const durationMs = archivedDate - createdDate;
    const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
    const durationDays = Math.floor(durationHours / 24);
    const remainingHours = durationHours % 24;
    
    let durationText = '';
    if (durationDays > 0) {
        durationText = `${durationDays} day${durationDays > 1 ? 's' : ''} ${remainingHours} hour${remainingHours !== 1 ? 's' : ''}`;
    } else if (durationHours > 0) {
        durationText = `${durationHours} hour${durationHours > 1 ? 's' : ''}`;
    } else {
        const durationMinutes = Math.floor(durationMs / (1000 * 60));
        durationText = `${durationMinutes} minute${durationMinutes !== 1 ? 's' : ''}`;
    }
    
    modalContent.innerHTML = `
        <!-- Print Header (only visible when printing) -->
        <div class="print-header">
            <h1>NCIT IT Support Ticket Report</h1>
            <p>Ticket Management System - History Record</p>
            <p>Printed on: ${new Date().toLocaleString()}</p>
        </div>
        
        <div class="space-y-4">
            <!-- Status Badge -->
            <div class="flex items-center justify-between print-section">
                <span class="bg-${config.color}-100 text-${config.color}-800 text-sm font-semibold px-4 py-2 rounded-lg flex items-center gap-2">
                    <i class="bi bi-${config.icon}"></i>
                    ${ticket.status}
                </span>
                <span class="text-sm text-gray-500">Ticket #${ticket.concern_id}</span>
            </div>
            
            <!-- Description -->
            <div class="print-section">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Description</h4>
                <p class="text-gray-800 bg-gray-50 p-3 rounded-lg">${ticket.description}</p>
            </div>
            
            <!-- Faculty Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 print-section">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Faculty Name</h4>
                    <p class="text-gray-800 flex items-center gap-2">
                        <i class="bi bi-person-fill text-gray-400"></i>
                        ${ticket.faculty_name}
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Email</h4>
                    <p class="text-gray-800 flex items-center gap-2">
                        <i class="bi bi-envelope-fill text-gray-400"></i>
                        ${ticket.email}
                    </p>
                </div>
            </div>
            
            <!-- Devices -->
            ${devicesHtml ? `
                <div class="print-section">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Affected Devices</h4>
                    <div class="flex flex-wrap gap-2">
                        ${devicesHtml}
                    </div>
                </div>
            ` : ''}
            
            <!-- Timeline -->
            <div class="print-section">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Timeline</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="bi bi-clock-fill text-gray-400"></i>
                        <span class="font-medium">Created:</span>
                        <span>${new Date(ticket.created_at).toLocaleString()}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="bi bi-${config.icon} text-${config.color}-500"></i>
                        <span class="font-medium">Marked as ${ticket.status}:</span>
                        <span class="text-${config.color}-700 font-semibold">${new Date(ticket.archived_at).toLocaleString()}</span>
                    </div>
                    <div class="flex items-center gap-2 bg-blue-50 px-3 py-2 rounded-lg">
                        <i class="bi bi-hourglass-split text-blue-600"></i>
                        <span class="font-medium text-gray-700">Duration:</span>
                        <span class="text-blue-700 font-semibold">${durationText}</span>
                    </div>
                </div>
            </div>
            
            <!-- Resolution Feedback -->
            ${ticket.resolution_feedback ? `
                <div class="print-section">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Resolution Feedback</h4>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                        <p class="text-gray-800">${ticket.resolution_feedback}</p>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
    
    modal.classList.remove('hidden');
}

function printTicket() {
    window.print();
}

function closeModal() {
    document.getElementById('ticketModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('ticketModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center gap-3">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-circle-fill'} text-xl"></i>
            <span class="font-medium">${message}</span>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<?php include 'footer.php'; ?>
