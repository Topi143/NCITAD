<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$concern_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    // First, try to get concern from active concerns table
    $stmt = $pdo->prepare("
        SELECT c.*, u.faculty_name, u.email
        FROM concerns c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.concern_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$concern_id, $user_id]);
    $concern = $stmt->fetch();

    $is_archived = false;
    
    // If not found in active concerns, check concern_history
    if (!$concern) {
        $stmt = $pdo->prepare("
            SELECT h.concern_id, h.user_id, h.description, h.status, 
                   h.created_at, h.updated_at, h.resolution_feedback,
                   h.faculty_name, h.email
            FROM concern_history h
            WHERE h.concern_id = ? AND h.user_id = ?
        ");
        $stmt->execute([$concern_id, $user_id]);
        $concern = $stmt->fetch();
        $is_archived = true;
    }

    if (!$concern) {
        echo json_encode(['success' => false, 'message' => 'Concern not found']);
        exit();
    }

    // Get associated devices
    if ($is_archived) {
        // For archived concerns, parse devices from JSON if available
        $devices = [];
        if (isset($concern['devices']) && !empty($concern['devices'])) {
            $devices = json_decode($concern['devices'], true) ?: [];
        }
    } else {
        // For active concerns, get from concern_devices table
        $stmt = $pdo->prepare("
            SELECT d.device_name, f.facility_name
            FROM concern_devices cd
            JOIN devices d ON cd.device_id = d.device_id
            JOIN facilities f ON d.facility_id = f.facility_id
            WHERE cd.concern_id = ?
            ORDER BY f.facility_name, d.device_name
        ");
        $stmt->execute([$concern_id]);
        $devices = $stmt->fetchAll();
    }

    // Status styling
    $status_styles = [
        'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
        'Ongoing' => 'bg-orange-100 text-orange-700 border-orange-300',
        'Resolved' => 'bg-green-100 text-green-700 border-green-300',
        'Declined' => 'bg-red-100 text-red-700 border-red-300',
        'Unresolved' => 'bg-gray-100 text-gray-700 border-gray-300'
    ];

    // Build HTML content
    $html = '
    <div class="space-y-4">
        <!-- Concern Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200">
            <div class="bg-blue-100 text-blue-700 rounded-lg px-4 py-2 font-bold text-lg">
                Ticket #' . htmlspecialchars($concern['concern_id']) . '
            </div>
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold border ' . $status_styles[$concern['status']] . '">
                ' . htmlspecialchars($concern['status']) . '
            </span>
        </div>

        <!-- Submitter Information -->
        <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-person-circle text-blue-600"></i>
                Submitted By
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-600">Name:</span>
                    <span class="font-semibold text-gray-800 ml-2">' . htmlspecialchars($concern['faculty_name']) . '</span>
                </div>
                <div>
                    <span class="text-gray-600">Email:</span>
                    <span class="font-semibold text-gray-800 ml-2">' . htmlspecialchars($concern['email']) . '</span>
                </div>
            </div>
        </div>

        <!-- Timestamps -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2 flex items-center gap-2 text-sm">
                    <i class="bi bi-calendar-plus"></i>
                    Created
                </h4>
                <p class="text-blue-700 font-medium">
                    ' . date('F d, Y', strtotime($concern['created_at'])) . '
                </p>
                <p class="text-blue-600 text-sm">
                    ' . date('h:i A', strtotime($concern['created_at'])) . '
                </p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4">
                <h4 class="font-semibold text-purple-800 mb-2 flex items-center gap-2 text-sm">
                    <i class="bi bi-arrow-repeat"></i>
                    Last Updated
                </h4>
                <p class="text-purple-700 font-medium">
                    ' . date('F d, Y', strtotime($concern['updated_at'])) . '
                </p>
                <p class="text-purple-600 text-sm">
                    ' . date('h:i A', strtotime($concern['updated_at'])) . '
                </p>
            </div>
        </div>

        <!-- Description -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-chat-left-text text-blue-600"></i>
                Description
            </h4>
            <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">' . htmlspecialchars($concern['description']) . '</p>
        </div>

        <!-- Affected Devices -->
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-hdd-rack text-blue-600"></i>
                Affected Devices (' . count($devices) . ')
            </h4>';

    if (!empty($devices)) {
        $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
        foreach ($devices as $device) {
            $html .= '
                <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
                    <i class="bi bi-hdd text-blue-600 text-xl"></i>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">' . htmlspecialchars($device['device_name']) . '</p>
                        <p class="text-gray-600 text-xs flex items-center gap-1">
                            <i class="bi bi-building"></i>
                            ' . htmlspecialchars($device['facility_name']) . '
                        </p>
                    </div>
                </div>';
        }
        $html .= '</div>';
    } else {
        $html .= '<p class="text-gray-500 text-sm italic">No devices associated</p>';
    }

    $html .= '</div>';

    // Resolution Feedback
    if (!empty($concern['resolution_feedback'])) {
        $html .= '
        <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
            <h4 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                <i class="bi bi-check-circle-fill"></i>
                Resolution / Admin Response
            </h4>
            <p class="text-green-700 text-sm leading-relaxed whitespace-pre-wrap">' . htmlspecialchars($concern['resolution_feedback']) . '</p>
        </div>';
    }

    $html .= '
    </div>';

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_concern_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching concern details'
    ]);
}
?>
