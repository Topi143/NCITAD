<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'My Concerns - NCITAD';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 5;
$offset = ($page - 1) * $per_page;

// Determine which table to query based on status
// Resolved, Declined, Unresolved are in concern_history
// Pending, Ongoing are in concerns
$concerns = [];
$total_records = 0;

if ($status_filter === 'all') {
    // Get from both tables
    
    // First, get total count
    $count_active_sql = "SELECT COUNT(*) FROM concerns c WHERE c.user_id = ?";
    $count_active_params = [$user_id];
    
    if (!empty($search)) {
        $count_active_sql .= " AND (c.description LIKE ? OR c.concern_id LIKE ?)";
        $search_term = '%' . $search . '%';
        $count_active_params[] = $search_term;
        $count_active_params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_active_sql);
    $stmt->execute($count_active_params);
    $active_total = $stmt->fetchColumn();
    
    $count_history_sql = "SELECT COUNT(*) FROM concern_history h WHERE h.user_id = ?";
    $count_history_params = [$user_id];
    
    if (!empty($search)) {
        $count_history_sql .= " AND (h.description LIKE ? OR h.concern_id LIKE ?)";
        $count_history_params[] = $search_term;
        $count_history_params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_history_sql);
    $stmt->execute($count_history_params);
    $history_total = $stmt->fetchColumn();
    
    $total_records = $active_total + $history_total;
    
    // Get active concerns (Pending, Ongoing)
    $active_sql = "
        SELECT c.concern_id, c.user_id, c.description, c.status, 
               c.created_at, c.updated_at, c.resolution_feedback,
               COUNT(DISTINCT cd.device_id) as device_count
        FROM concerns c
        LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
        WHERE c.user_id = ?
    ";
    $active_params = [$user_id];
    
    if (!empty($search)) {
        $active_sql .= " AND (c.description LIKE ? OR c.concern_id LIKE ?)";
        $active_params[] = $search_term;
        $active_params[] = $search_term;
    }
    
    $active_sql .= " GROUP BY c.concern_id";
    
    $stmt = $pdo->prepare($active_sql);
    $stmt->execute($active_params);
    $active_concerns = $stmt->fetchAll();
    
    // Get archived concerns (Resolved, Declined, Unresolved)
    $history_sql = "
        SELECT h.concern_id, h.user_id, h.description, h.status, 
               h.created_at, h.updated_at, h.resolution_feedback,
               0 as device_count
        FROM concern_history h
        WHERE h.user_id = ?
    ";
    $history_params = [$user_id];
    
    if (!empty($search)) {
        $history_sql .= " AND (h.description LIKE ? OR h.concern_id LIKE ?)";
        $history_params[] = $search_term;
        $history_params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($history_sql);
    $stmt->execute($history_params);
    $history_concerns = $stmt->fetchAll();
    
    // Merge and sort by created_at
    $all_concerns = array_merge($active_concerns, $history_concerns);
    usort($all_concerns, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Apply pagination
    $concerns = array_slice($all_concerns, $offset, $per_page);
    
} elseif (in_array($status_filter, ['Resolved', 'Declined', 'Unresolved'])) {
    // Get total count first
    $count_sql = "SELECT COUNT(*) FROM concern_history h WHERE h.user_id = ? AND h.status = ?";
    $count_params = [$user_id, $status_filter];
    
    if (!empty($search)) {
        $count_sql .= " AND (h.description LIKE ? OR h.concern_id LIKE ?)";
        $search_term = '%' . $search . '%';
        $count_params[] = $search_term;
        $count_params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($count_params);
    $total_records = $stmt->fetchColumn();
    
    // Query concern_history for archived statuses
    $sql = "
        SELECT h.concern_id, h.user_id, h.description, h.status, 
               h.created_at, h.updated_at, h.resolution_feedback,
               0 as device_count
        FROM concern_history h
        WHERE h.user_id = ? AND h.status = ?
    ";
    $params = [$user_id, $status_filter];
    
    if (!empty($search)) {
        $sql .= " AND (h.description LIKE ? OR h.concern_id LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " ORDER BY h.created_at DESC LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $concerns = $stmt->fetchAll();
    
} else {
    // Get total count first
    $count_sql = "SELECT COUNT(*) FROM concerns c WHERE c.user_id = ? AND c.status = ?";
    $count_params = [$user_id, $status_filter];
    
    if (!empty($search)) {
        $count_sql .= " AND (c.description LIKE ? OR c.concern_id LIKE ?)";
        $search_term = '%' . $search . '%';
        $count_params[] = $search_term;
        $count_params[] = $search_term;
    }
    
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($count_params);
    $total_records = $stmt->fetchColumn();
    
    // Query concerns table for active statuses (Pending, Ongoing)
    $sql = "
        SELECT c.concern_id, c.user_id, c.description, c.status, 
               c.created_at, c.updated_at, c.resolution_feedback,
               COUNT(DISTINCT cd.device_id) as device_count
        FROM concerns c
        LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
        WHERE c.user_id = ? AND c.status = ?
    ";
    $params = [$user_id, $status_filter];
    
    if (!empty($search)) {
        $sql .= " AND (c.description LIKE ? OR c.concern_id LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " GROUP BY c.concern_id ORDER BY c.created_at DESC LIMIT $per_page OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $concerns = $stmt->fetchAll();
}

// Calculate total pages
$total_pages = ceil($total_records / $per_page);

// Get status counts for filter tabs (from both tables)
$status_counts = [];

// Count from concerns table (Pending, Ongoing)
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM concerns WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

// Count from concern_history table (Resolved, Declined, Unresolved)
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM concern_history WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

// Total count (both tables)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM concerns WHERE user_id = ?");
$stmt->execute([$user_id]);
$active_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM concern_history WHERE user_id = ?");
$stmt->execute([$user_id]);
$history_count = $stmt->fetchColumn();

$total_count = $active_count + $history_count;

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-file-earmark-text-fill text-blue-600"></i>
                My Concerns
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">View and track all your submitted IT concerns</p>
        </div>
        <a href="addnewconcern.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2 transition-colors shadow-md hover:shadow-lg">
            <i class="bi bi-plus-circle"></i> Submit New Concern
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="px-4 pb-4 space-y-4">

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Search Box -->
            <div class="flex-1">
                <form method="GET" action="concernlist.php" class="relative">
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search by ticket number or description..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <?php if ($status_filter !== 'all'): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Clear Filters Button -->
            <?php if (!empty($search) || $status_filter !== 'all'): ?>
                <a href="concernlist.php" class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold flex items-center gap-2 transition-colors">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Filter Tabs with Integrated Pagination -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Status Tabs -->
        <div class="flex flex-wrap border-b border-gray-200">
            <a href="?status=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                All
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'all' ? 'bg-white text-blue-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $total_count ?>
                </span>
            </a>
            <a href="?status=Pending<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'Pending' ? 'bg-yellow-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                Pending
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'Pending' ? 'bg-white text-yellow-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $status_counts['Pending'] ?? 0 ?>
                </span>
            </a>
            <a href="?status=Ongoing<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'Ongoing' ? 'bg-orange-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                Ongoing
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'Ongoing' ? 'bg-white text-orange-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $status_counts['Ongoing'] ?? 0 ?>
                </span>
            </a>
            <a href="?status=Resolved<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'Resolved' ? 'bg-green-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                Resolved
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'Resolved' ? 'bg-white text-green-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $status_counts['Resolved'] ?? 0 ?>
                </span>
            </a>
            <a href="?status=Declined<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'Declined' ? 'bg-red-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                Declined
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'Declined' ? 'bg-white text-red-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $status_counts['Declined'] ?? 0 ?>
                </span>
            </a>
            <a href="?status=Unresolved<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
               class="flex-1 min-w-max px-6 py-3 text-center font-semibold transition-colors <?= $status_filter === 'Unresolved' ? 'bg-gray-500 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100' ?>">
                Unresolved
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs <?= $status_filter === 'Unresolved' ? 'bg-white text-gray-600' : 'bg-gray-200 text-gray-600' ?>">
                    <?= $status_counts['Unresolved'] ?? 0 ?>
                </span>
            </a>
        </div>
        
        <!-- Integrated Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="bg-gray-50 px-6 py-3 flex justify-center items-center gap-2">
                <?php
                // Build query parameters
                $query_params = [];
                if ($status_filter !== 'all') $query_params['status'] = $status_filter;
                if (!empty($search)) $query_params['search'] = $search;
                
                // Previous button
                if ($page > 1):
                    $prev_params = array_merge($query_params, ['page' => $page - 1]);
                    $prev_url = '?' . http_build_query($prev_params);
                ?>
                    <a href="<?= $prev_url ?>" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm transition-colors flex items-center gap-1">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-gray-200 text-gray-400 rounded-lg font-semibold text-sm cursor-not-allowed flex items-center gap-1">
                        <i class="bi bi-chevron-left"></i> Previous
                    </span>
                <?php endif; ?>
                
                <!-- Page info -->
                <span class="px-4 py-1.5 text-gray-700 font-semibold text-sm">
                    Page <?= $page ?> of <?= $total_pages ?>
                </span>
                
                <!-- Next button -->
                <?php if ($page < $total_pages):
                    $next_params = array_merge($query_params, ['page' => $page + 1]);
                    $next_url = '?' . http_build_query($next_params);
                ?>
                    <a href="<?= $next_url ?>" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm transition-colors flex items-center gap-1">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="px-3 py-1.5 bg-gray-200 text-gray-400 rounded-lg font-semibold text-sm cursor-not-allowed flex items-center gap-1">
                        Next <i class="bi bi-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Concerns List -->
    <?php if (!empty($concerns)): ?>
        <div class="max-h-[calc(100vh-20rem)] overflow-y-auto pr-2 custom-scrollbar">
            <div class="grid grid-cols-1 gap-4">
                <?php 
                $status_styles = [
                    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                    'Ongoing' => 'bg-orange-100 text-orange-700 border-orange-300',
                    'Resolved' => 'bg-green-100 text-green-700 border-green-300',
                    'Declined' => 'bg-red-100 text-red-700 border-red-300',
                    'Unresolved' => 'bg-gray-100 text-gray-700 border-gray-300'
                ];
                
                foreach ($concerns as $concern): 
                ?>
                <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow overflow-hidden">
                    <div class="p-5">
                        <!-- Header Row -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-100 text-blue-700 rounded-lg px-3 py-1.5 font-bold text-sm">
                                    #<?= $concern['concern_id'] ?>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?= $status_styles[$concern['status']] ?>">
                                    <?= htmlspecialchars($concern['status']) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-calendar3"></i>
                                    <?= date('M d, Y', strtotime($concern['created_at'])) ?>
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="bi bi-clock"></i>
                                    <?= date('h:i A', strtotime($concern['created_at'])) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                <i class="bi bi-chat-left-text text-blue-600"></i>
                                Description
                            </h3>
                            <p class="text-gray-700 text-sm leading-relaxed pl-6">
                                <?= nl2br(htmlspecialchars($concern['description'])) ?>
                            </p>
                        </div>

                        <!-- Resolution Feedback (if exists) -->
                        <?php if (!empty($concern['resolution_feedback'])): ?>
                            <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                <h3 class="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                                    <i class="bi bi-info-circle-fill"></i>
                                    Admin Response
                                </h3>
                                <p class="text-blue-700 text-sm leading-relaxed">
                                    <?= nl2br(htmlspecialchars($concern['resolution_feedback'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Footer Row -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <i class="bi bi-hdd-rack text-blue-600"></i>
                                <span class="font-semibold">
                                    <?= $concern['device_count'] ?> 
                                    <?= $concern['device_count'] == 1 ? 'device' : 'devices' ?> affected
                                </span>
                            </div>
                            <button 
                                onclick="viewConcernDetails(<?= $concern['concern_id'] ?>)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm flex items-center gap-2 transition-colors"
                            >
                                <i class="bi bi-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-lg p-8 min-h-[calc(100vh-20rem)] flex items-center justify-center">
            <div class="max-w-md mx-auto text-center">
                <i class="bi bi-inbox text-5xl text-gray-300 mb-3"></i>
                <h3 class="text-lg font-bold text-gray-700 mb-2">No Concerns Found</h3>
                <?php if (!empty($search) || $status_filter !== 'all'): ?>
                    <p class="text-gray-600 text-sm mb-4">No concerns match your current filters. Try adjusting your search or filter criteria.</p>
                    <a href="concernlist.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors text-sm">
                        <i class="bi bi-arrow-clockwise"></i> Clear Filters
                    </a>
                <?php else: ?>
                    <p class="text-gray-600 text-sm mb-4">You haven't submitted any IT concerns yet. Click the button below to submit your first concern.</p>
                    <a href="addnewconcern.php" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors shadow-md hover:shadow-lg text-sm">
                        <i class="bi bi-plus-circle"></i> Submit New Concern
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Concern Details Modal -->
<div id="detailsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeDetailsModal()"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-3xl w-full animate-fade-in">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="bi bi-file-earmark-text"></i>
                        Concern Details
                    </h3>
                    <button onclick="closeDetailsModal()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="bi bi-x-lg text-2xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div id="modalContent" class="p-6 max-h-[70vh] overflow-y-auto">
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// View concern details
function viewConcernDetails(concernId) {
    const modal = document.getElementById('detailsModal');
    const modalContent = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    
    // Show loading spinner
    modalContent.innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
    `;
    
    // Fetch concern details
    fetch(`get_concern_details.php?id=${concernId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalContent.innerHTML = data.html;
            } else {
                modalContent.innerHTML = `
                    <div class="text-center py-8">
                        <i class="bi bi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
                        <p class="text-gray-700">${data.message || 'Failed to load concern details'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = `
                <div class="text-center py-8">
                    <i class="bi bi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
                    <p class="text-gray-700">An error occurred while loading the details.</p>
                </div>
            `;
        });
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDetailsModal();
    }
});

// Auto-reload concern list every 30 seconds to keep data fresh
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
                // Reload the page while preserving current filters and page
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

// Also pause when modal is open
const detailsModal = document.getElementById('detailsModal');
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.attributeName === 'class') {
            const isModalOpen = !detailsModal.classList.contains('hidden');
            if (isModalOpen) {
                isPaused = true;
                countdown = 30;
            }
        }
    });
});

observer.observe(detailsModal, { attributes: true });

// Start auto-reload when page loads
window.addEventListener('load', () => {
    startAutoReload();
});
</script>

<?php include 'footer.php'; ?>
