<?php
session_start();
require_once '../includes/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Concern Tickets';

// Pagination settings
$items_per_page = 5;
$pending_page = isset($_GET['pending_page']) ? max(1, intval($_GET['pending_page'])) : 1;
$ongoing_page = isset($_GET['ongoing_page']) ? max(1, intval($_GET['ongoing_page'])) : 1;

// Filter parameters
$filter_facility = isset($_GET['facility']) ? intval($_GET['facility']) : 0;
$filter_device = isset($_GET['device']) ? intval($_GET['device']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build filter conditions
$filter_conditions = [];
$filter_params = [];

if ($filter_facility > 0) {
    $filter_conditions[] = "fac.facility_id = ?";
    $filter_params[] = $filter_facility;
}

if ($filter_device > 0) {
    $filter_conditions[] = "d.device_id = ?";
    $filter_params[] = $filter_device;
}

if (!empty($search_query)) {
    $filter_conditions[] = "(c.description LIKE ? OR u.faculty_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%{$search_query}%";
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
}

$filter_sql = !empty($filter_conditions) ? " AND " . implode(" AND ", $filter_conditions) : "";

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $concern_id = $_POST['concern_id'] ?? null;
        $action = $_POST['action'];
        
        if (!$concern_id) {
            throw new Exception('Concern ID is required');
        }
        
        switch ($action) {
            case 'accept':
                // Move from Pending to Ongoing
                $stmt = $pdo->prepare("UPDATE concerns SET status = 'Ongoing', updated_at = NOW() WHERE concern_id = ? AND status = 'Pending'");
                $stmt->execute([$concern_id]);
                echo json_encode(['success' => true, 'message' => 'Ticket accepted and moved to Ongoing']);
                break;
                
            case 'decline':
                // Move from Pending to Declined (archive to history)
                $reason = $_POST['reason'] ?? 'Request declined by administrator';
                
                $stmt = $pdo->prepare("
                    INSERT INTO concern_history (concern_id, user_id, description, status, created_at, updated_at, resolution_feedback, faculty_name, email, devices)
                    SELECT 
                        c.concern_id,
                        c.user_id,
                        c.description,
                        'Declined',
                        c.created_at,
                        NOW(),
                        ?,
                        u.faculty_name,
                        u.email,
                        CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT('device_name', d.device_name, 'facility_name', dep.facility_name)
                        ), ']')
                    FROM concerns c
                    JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
                    LEFT JOIN devices d ON cd.device_id = d.device_id
                    LEFT JOIN facilities dep ON d.facility_id = dep.facility_id
                    WHERE c.concern_id = ? AND c.status = 'Pending'
                    GROUP BY c.concern_id
                ");
                $stmt->execute([$reason, $concern_id]);
                
                // Delete from concerns table
                $stmt = $pdo->prepare("DELETE FROM concerns WHERE concern_id = ?");
                $stmt->execute([$concern_id]);
                
                echo json_encode(['success' => true, 'message' => 'Ticket declined and moved to history']);
                break;
                
            case 'resolved':
                // Move from Ongoing to Resolved (archive to history)
                $feedback = $_POST['feedback'] ?? 'Issue resolved successfully';
                
                $stmt = $pdo->prepare("
                    INSERT INTO concern_history (concern_id, user_id, description, status, created_at, updated_at, resolution_feedback, faculty_name, email, devices)
                    SELECT 
                        c.concern_id,
                        c.user_id,
                        c.description,
                        'Resolved',
                        c.created_at,
                        NOW(),
                        ?,
                        u.faculty_name,
                        u.email,
                        CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT('device_name', d.device_name, 'facility_name', dep.facility_name)
                        ), ']')
                    FROM concerns c
                    JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
                    LEFT JOIN devices d ON cd.device_id = d.device_id
                    LEFT JOIN facilities dep ON d.facility_id = dep.facility_id
                    WHERE c.concern_id = ? AND c.status = 'Ongoing'
                    GROUP BY c.concern_id
                ");
                $stmt->execute([$feedback, $concern_id]);
                
                // Delete from concerns table
                $stmt = $pdo->prepare("DELETE FROM concerns WHERE concern_id = ?");
                $stmt->execute([$concern_id]);
                
                echo json_encode(['success' => true, 'message' => 'Ticket marked as resolved and moved to history']);
                break;
                
            case 'unresolved':
                // Move from Ongoing to Unresolved (archive to history)
                $feedback = $_POST['feedback'] ?? 'Unable to resolve issue';
                
                $stmt = $pdo->prepare("
                    INSERT INTO concern_history (concern_id, user_id, description, status, created_at, updated_at, resolution_feedback, faculty_name, email, devices)
                    SELECT 
                        c.concern_id,
                        c.user_id,
                        c.description,
                        'Unresolved',
                        c.created_at,
                        NOW(),
                        ?,
                        u.faculty_name,
                        u.email,
                        CONCAT('[', GROUP_CONCAT(
                            JSON_OBJECT('device_name', d.device_name, 'facility_name', dep.facility_name)
                        ), ']')
                    FROM concerns c
                    JOIN users u ON c.user_id = u.user_id
                    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
                    LEFT JOIN devices d ON cd.device_id = d.device_id
                    LEFT JOIN facilities dep ON d.facility_id = dep.facility_id
                    WHERE c.concern_id = ? AND c.status = 'Ongoing'
                    GROUP BY c.concern_id
                ");
                $stmt->execute([$feedback, $concern_id]);
                
                // Delete from concerns table
                $stmt = $pdo->prepare("DELETE FROM concerns WHERE concern_id = ?");
                $stmt->execute([$concern_id]);
                
                echo json_encode(['success' => true, 'message' => 'Ticket marked as unresolved and moved to history']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch facilities for filter
$facility_stmt = $pdo->query("SELECT * FROM facilities ORDER BY facility_name ASC");
$facilities = $facility_stmt->fetchAll();

// Fetch devices for filter
$device_stmt = $pdo->query("SELECT d.*, fac.facility_name FROM devices d JOIN facilities fac ON d.facility_id = fac.facility_id ORDER BY d.device_name ASC");
$devices = $device_stmt->fetchAll();

// Count total Pending tickets
$pending_count_sql = "
    SELECT COUNT(DISTINCT c.concern_id) as total
    FROM concerns c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
    LEFT JOIN devices d ON cd.device_id = d.device_id
    LEFT JOIN facilities fac ON d.facility_id = fac.facility_id
    WHERE c.status = 'Pending' $filter_sql
";
$pending_count_stmt = $pdo->prepare($pending_count_sql);
$pending_count_stmt->execute($filter_params);
$total_pending = $pending_count_stmt->fetch()['total'];
$pending_total_pages = ceil($total_pending / $items_per_page);
$pending_offset = ($pending_page - 1) * $items_per_page;

// Count total Ongoing tickets
$ongoing_count_sql = "
    SELECT COUNT(DISTINCT c.concern_id) as total
    FROM concerns c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
    LEFT JOIN devices d ON cd.device_id = d.device_id
    LEFT JOIN facilities fac ON d.facility_id = fac.facility_id
    WHERE c.status = 'Ongoing' $filter_sql
";
$ongoing_count_stmt = $pdo->prepare($ongoing_count_sql);
$ongoing_count_stmt->execute($filter_params);
$total_ongoing = $ongoing_count_stmt->fetch()['total'];
$ongoing_total_pages = ceil($total_ongoing / $items_per_page);
$ongoing_offset = ($ongoing_page - 1) * $items_per_page;

// Fetch Pending tickets with pagination
$pending_sql = "
    SELECT 
        c.*,
        u.faculty_name,
        u.email,
        GROUP_CONCAT(CONCAT(d.device_name, ' (', fac.facility_name, ')') SEPARATOR ', ') as devices
    FROM concerns c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
    LEFT JOIN devices d ON cd.device_id = d.device_id
    LEFT JOIN facilities fac ON d.facility_id = fac.facility_id
    WHERE c.status = 'Pending' $filter_sql
    GROUP BY c.concern_id
    ORDER BY c.created_at ASC
    LIMIT $items_per_page OFFSET $pending_offset
";
$pending_stmt = $pdo->prepare($pending_sql);
$pending_stmt->execute($filter_params);
$pending_tickets = $pending_stmt->fetchAll();

// Fetch Ongoing tickets with pagination
$ongoing_sql = "
    SELECT 
        c.*,
        u.faculty_name,
        u.email,
        GROUP_CONCAT(CONCAT(d.device_name, ' (', fac.facility_name, ')') SEPARATOR ', ') as devices
    FROM concerns c
    JOIN users u ON c.user_id = u.user_id
    LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
    LEFT JOIN devices d ON cd.device_id = d.device_id
    LEFT JOIN facilities fac ON d.facility_id = fac.facility_id
    WHERE c.status = 'Ongoing' $filter_sql
    GROUP BY c.concern_id
    ORDER BY c.updated_at ASC
    LIMIT $items_per_page OFFSET $ongoing_offset
";
$ongoing_stmt = $pdo->prepare($ongoing_sql);
$ongoing_stmt->execute($filter_params);
$ongoing_tickets = $ongoing_stmt->fetchAll();

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-ticket-detailed-fill text-blue-600"></i>
                Concern Tickets
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Manage and track IT support tickets</p>
        </div>
        <a href="history.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all shadow-md hover:shadow-lg text-sm font-semibold">
            <i class="bi bi-clock-history"></i>
            <span>View History</span>
        </a>
    </div>
</div>

<!-- Filters -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col lg:flex-row gap-3">
        <!-- Search Bar -->
        <div class="flex-1">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchInput" value="<?= htmlspecialchars($search_query) ?>" 
                       placeholder="Search by description, faculty name, or email..." 
                       class="w-full pl-10 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>
        
        <!-- Facility Filter -->
        <div class="w-full lg:w-48">
            <select id="facilityFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
            <select id="deviceFilter" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4 px-4">
    <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-100 text-xs font-medium">Pending Tickets</p>
                <h3 class="text-3xl font-bold mt-1"><?= $total_pending ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-hourglass-split text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-xs font-medium">Ongoing Tickets</p>
                <h3 class="text-3xl font-bold mt-1"><?= $total_ongoing ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-gear-fill text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tickets Grid -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 px-4 pb-4">
    
    <!-- Pending Panel -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col" style="height: calc(100vh - 3 15px);">
        <div class="bg-gradient-to-r from-gray-500 to-gray-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="bi bi-hourglass-split text-lg"></i>
                <h2 class="text-lg font-bold">Pending Approval</h2>
            </div>
            <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold"><?= $total_pending ?></span>
        </div>
        
        <div class="p-4 space-y-3 overflow-y-auto flex-1">
            <?php if (empty($pending_tickets)): ?>
                <div class="text-center py-8">
                    <i class="bi bi-inbox text-5xl text-gray-300"></i>
                    <p class="text-gray-500 mt-3">No pending tickets</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_tickets as $ticket): ?>
                    <div class="border-l-4 border-gray-500 bg-gray-50 rounded-lg p-3 hover:shadow-md transition-shadow ticket-card" data-ticket-id="<?= $ticket['concern_id'] ?>">
                        <div class="flex justify-between items-start mb-2">
                            <span class="bg-gray-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold">
                                #<?= str_pad($ticket['concern_id'], 4, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($ticket['created_at'])) ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-800 text-sm font-medium mb-2 line-clamp-2"><?= htmlspecialchars($ticket['description']) ?></p>
                        
                        <div class="space-y-1 mb-3">
                            <div class="flex items-center gap-1 text-xs text-gray-600">
                                <i class="bi bi-person-fill text-blue-500"></i>
                                <span class="truncate"><?= htmlspecialchars($ticket['faculty_name']) ?></span>
                            </div>
                            <?php if ($ticket['devices']): ?>
                                <div class="flex items-start gap-1 text-xs text-gray-600">
                                    <i class="bi bi-pc-display text-blue-500 mt-0.5"></i>
                                    <span class="line-clamp-1"><?= htmlspecialchars($ticket['devices']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex gap-2 pt-2 border-t border-gray-200">
                            <button onclick="handleAction(<?= $ticket['concern_id'] ?>, 'accept')" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-1">
                                <i class="bi bi-check-circle"></i>
                                Accept
                            </button>
                            <button onclick="showDeclineModal(<?= $ticket['concern_id'] ?>)" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-1">
                                <i class="bi bi-x-circle"></i>
                                Decline
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination for Pending - Always at bottom -->
        <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-white flex-shrink-0">
            <div class="text-xs text-gray-600">
                Page <?= $pending_page ?> of <?= max(1, $pending_total_pages) ?>
            </div>
            <?php if ($pending_total_pages > 1): ?>
                <div class="flex gap-1">
                    <?php if ($pending_page > 1): ?>
                        <a href="?pending_page=<?= $pending_page - 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&ongoing_page=<?= $ongoing_page ?>" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $pending_page - 2); $i <= min($pending_total_pages, $pending_page + 2); $i++): ?>
                        <a href="?pending_page=<?= $i ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&ongoing_page=<?= $ongoing_page ?>" class="px-2 py-1 <?= $i == $pending_page ? 'bg-gray-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded text-xs font-medium">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($pending_page < $pending_total_pages): ?>
                        <a href="?pending_page=<?= $pending_page + 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&ongoing_page=<?= $ongoing_page ?>" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-xs text-gray-400">
                    No pagination needed
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Ongoing Panel -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden flex flex-col" style="height: calc(100vh - 315px);">
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white px-4 py-3 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="bi bi-gear-fill text-lg"></i>
                <h2 class="text-lg font-bold">In Progress</h2>
            </div>
            <span class="bg-white/20 px-2 py-1 rounded-full text-xs font-semibold"><?= $total_ongoing ?></span>
        </div>
        
        <div class="p-4 space-y-3 overflow-y-auto flex-1">
            <?php if (empty($ongoing_tickets)): ?>
                <div class="text-center py-8">
                    <i class="bi bi-inbox text-5xl text-gray-300"></i>
                    <p class="text-gray-500 mt-3">No ongoing tickets</p>
                </div>
            <?php else: ?>
                <?php foreach ($ongoing_tickets as $ticket): ?>
                    <div class="border-l-4 border-yellow-500 bg-yellow-50 rounded-lg p-3 hover:shadow-md transition-shadow ticket-card" data-ticket-id="<?= $ticket['concern_id'] ?>">
                        <div class="flex justify-between items-start mb-2">
                            <span class="bg-yellow-500 text-white px-2 py-0.5 rounded-full text-xs font-semibold">
                                #<?= str_pad($ticket['concern_id'], 4, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span class="text-xs text-gray-600">
                                <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($ticket['updated_at'])) ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-800 text-sm font-medium mb-2 line-clamp-2"><?= htmlspecialchars($ticket['description']) ?></p>
                        
                        <div class="space-y-1 mb-3">
                            <div class="flex items-center gap-1 text-xs text-gray-600">
                                <i class="bi bi-person-fill text-yellow-600"></i>
                                <span class="truncate"><?= htmlspecialchars($ticket['faculty_name']) ?></span>
                            </div>
                            <?php if ($ticket['devices']): ?>
                                <div class="flex items-start gap-1 text-xs text-gray-600">
                                    <i class="bi bi-pc-display text-yellow-600 mt-0.5"></i>
                                    <span class="line-clamp-1"><?= htmlspecialchars($ticket['devices']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex gap-2 pt-2 border-t border-yellow-200">
                            <button onclick="showFeedbackModal(<?= $ticket['concern_id'] ?>, 'resolved')" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-1">
                                <i class="bi bi-check-circle-fill"></i>
                                Resolved
                            </button>
                            <button onclick="showFeedbackModal(<?= $ticket['concern_id'] ?>, 'unresolved')" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors flex items-center justify-center gap-1">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                Unresolved
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination for Ongoing - Always at bottom -->
        <div class="border-t border-gray-200 px-4 py-3 flex items-center justify-between bg-white flex-shrink-0">
            <div class="text-xs text-gray-600">
                Page <?= $ongoing_page ?> of <?= max(1, $ongoing_total_pages) ?>
            </div>
            <?php if ($ongoing_total_pages > 1): ?>
                <div class="flex gap-1">
                    <?php if ($ongoing_page > 1): ?>
                        <a href="?ongoing_page=<?= $ongoing_page - 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&pending_page=<?= $pending_page ?>" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $ongoing_page - 2); $i <= min($ongoing_total_pages, $ongoing_page + 2); $i++): ?>
                        <a href="?ongoing_page=<?= $i ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&pending_page=<?= $pending_page ?>" class="px-2 py-1 <?= $i == $ongoing_page ? 'bg-yellow-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?> rounded text-xs font-medium">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($ongoing_page < $ongoing_total_pages): ?>
                        <a href="?ongoing_page=<?= $ongoing_page + 1 ?><?= $filter_facility ? '&facility=' . $filter_facility : '' ?><?= $filter_device ? '&device=' . $filter_device : '' ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>&pending_page=<?= $pending_page ?>" class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs font-medium">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-xs text-gray-400">
                    No pagination needed
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<!-- Feedback Modal -->
<div id="feedbackModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold" id="modalTitle">Resolution Feedback</h3>
        </div>
        <div class="p-4">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Provide feedback or notes:</label>
            <textarea id="feedbackText" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Enter resolution details..."></textarea>
        </div>
        <div class="flex gap-2 px-4 pb-4">
            <button onclick="closeFeedbackModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                Cancel
            </button>
            <button onclick="submitFeedback()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                Submit
            </button>
        </div>
    </div>
</div>

<!-- Decline Modal -->
<div id="declineModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold">Decline Ticket - Reason Required</h3>
        </div>
        <div class="p-4">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Reason for declining this ticket:</label>
            <textarea id="declineReasonText" rows="4" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Enter reason for declining...">Request does not fall under IT department scope</textarea>
            <p class="text-xs text-gray-500 mt-2">
                <i class="bi bi-info-circle"></i> This reason will be sent to the faculty member.
            </p>
        </div>
        <div class="flex gap-2 px-4 pb-4">
            <button onclick="closeDeclineModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                Cancel
            </button>
            <button onclick="submitDecline()" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-semibold transition-colors">
                Decline Ticket
            </button>
        </div>
    </div>
</div>

<style>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
let currentTicketId = null;
let currentAction = null;
let filterTimeout = null;

// Dynamic filter functions
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const facility = document.getElementById('facilityFilter').value;
    const device = document.getElementById('deviceFilter').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (facility && facility !== '0') params.append('facility', facility);
    if (device && device !== '0') params.append('device', device);
    
    const queryString = params.toString();
    window.location.href = 'concerns.php' + (queryString ? '?' + queryString : '');
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('facilityFilter').value = '0';
    document.getElementById('deviceFilter').value = '0';
    window.location.href = 'concerns.php';
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

// Auto-apply filters on change
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const facilityFilter = document.getElementById('facilityFilter');
    const deviceFilter = document.getElementById('deviceFilter');
    
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
    
    // Apply filter on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(filterTimeout);
            applyFilters();
        }
    });
});

function handleAction(ticketId, action) {
    const formData = new FormData();
    formData.append('concern_id', ticketId);
    formData.append('action', action);
    
    fetch('concerns.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

function showDeclineModal(ticketId) {
    currentTicketId = ticketId;
    const modal = document.getElementById('declineModal');
    document.getElementById('declineReasonText').value = 'Request does not fall under IT department scope';
    modal.classList.remove('hidden');
}

function closeDeclineModal() {
    document.getElementById('declineModal').classList.add('hidden');
    currentTicketId = null;
}

function submitDecline() {
    const reason = document.getElementById('declineReasonText').value.trim();
    
    if (!reason) {
        showToast('Please provide a reason for declining this ticket.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('concern_id', currentTicketId);
    formData.append('action', 'decline');
    formData.append('reason', reason);
    
    fetch('concerns.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeDeclineModal();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

function showFeedbackModal(ticketId, action) {
    currentTicketId = ticketId;
    currentAction = action;
    
    const modal = document.getElementById('feedbackModal');
    const modalTitle = document.getElementById('modalTitle');
    
    if (action === 'resolved') {
        modalTitle.textContent = 'Mark as Resolved - Feedback';
    } else {
        modalTitle.textContent = 'Mark as Unresolved - Feedback';
    }
    
    document.getElementById('feedbackText').value = '';
    modal.classList.remove('hidden');
}

function closeFeedbackModal() {
    document.getElementById('feedbackModal').classList.add('hidden');
    currentTicketId = null;
    currentAction = null;
}

function submitFeedback() {
    const feedback = document.getElementById('feedbackText').value.trim();
    
    if (!feedback) {
        showToast('Please provide feedback before submitting.', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('concern_id', currentTicketId);
    formData.append('action', currentAction);
    formData.append('feedback', feedback);
    
    fetch('concerns.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeFeedbackModal();
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    });
}

// Close modal when clicking outside
document.getElementById('feedbackModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFeedbackModal();
    }
});

document.getElementById('declineModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeclineModal();
    }
});

// Auto-reload concerns page every 30 seconds to keep tickets fresh
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
                // Reload the page while preserving current filters and pagination
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

// Also pause when modals are open
const feedbackModal = document.getElementById('feedbackModal');
const declineModal = document.getElementById('declineModal');

const modalObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const isFeedbackModalOpen = !feedbackModal.classList.contains('hidden');
            const isDeclineModalOpen = !declineModal.classList.contains('hidden');
            if (isFeedbackModalOpen || isDeclineModalOpen) {
                isPaused = true;
                countdown = 30;
            }
        }
    });
});

modalObserver.observe(feedbackModal, { attributes: true });
modalObserver.observe(declineModal, { attributes: true });

// Start auto-reload when page loads
window.addEventListener('load', () => {
    startAutoReload();
});
</script>

<?php include 'footer.php'; ?>
