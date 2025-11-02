<?php
session_start();
require_once '../includes/config.php';

// Check admin status
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get selected facility
$selected_facility = isset($_GET['facility']) ? (int)$_GET['facility'] : 0;

// Functions
function getFacilitiesWithDeviceCount($pdo) {
    $stmt = $pdo->query("
        SELECT f.*, COUNT(d.device_id) as device_count 
        FROM facilities f 
        LEFT JOIN devices d ON f.facility_id = d.facility_id 
        GROUP BY f.facility_id 
        ORDER BY f.facility_name
    ");
    return $stmt->fetchAll();
}

function getDevicesByFacility($pdo, $facilityId) {
    if ($facilityId <= 0) return [];
    
    $stmt = $pdo->prepare("
        SELECT d.*, 
               (SELECT COUNT(*) FROM concern_devices cd 
                JOIN concerns c ON cd.concern_id = c.concern_id 
                WHERE cd.device_id = d.device_id) as concern_count
        FROM devices d
        WHERE d.facility_id = ?
        ORDER BY d.device_name
    ");
    $stmt->execute([$facilityId]);
    return $stmt->fetchAll();
}

function getFacilityDetails($pdo, $facilityId) {
    if ($facilityId <= 0) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM facilities WHERE facility_id = ?");
    $stmt->execute([$facilityId]);
    return $stmt->fetch();
}

function isDeviceUsed($pdo, $deviceId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM concern_devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    return $stmt->fetchColumn() > 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: facilities_devices.php");
        exit();
    }

    // Add Facility
    if (isset($_POST['action']) && $_POST['action'] === 'add_facility') {
        $name = trim($_POST['name']);
        
        try {
            if (empty($name)) {
                throw new Exception("Facility name cannot be empty");
            }
            
            if (strlen($name) > 100) {
                throw new Exception("Facility name is too long (max 100 characters)");
            }
            
            // Check if facility already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM facilities WHERE facility_name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("A facility with this name already exists");
            }
            
            // Insert new facility
            $stmt = $pdo->prepare("INSERT INTO facilities (facility_name) VALUES (?)");
            $stmt->execute([$name]);
            
            $newId = $pdo->lastInsertId();
            $_SESSION['success'] = "Facility added successfully";
            header("Location: facilities_devices.php?facility=$newId");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
    
    // Edit Facility
    if (isset($_POST['action']) && $_POST['action'] === 'edit_facility') {
        $facilityId = (int)$_POST['facility_id'];
        $name = trim($_POST['name']);
        
        try {
            if ($facilityId <= 0) {
                throw new Exception("Invalid facility ID");
            }
            
            if (empty($name)) {
                throw new Exception("Facility name cannot be empty");
            }
            
            if (strlen($name) > 100) {
                throw new Exception("Facility name is too long (max 100 characters)");
            }
            
            // Check if another facility with same name exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM facilities WHERE facility_name = ? AND facility_id != ?");
            $stmt->execute([$name, $facilityId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("A facility with this name already exists");
            }
            
            // Update facility
            $stmt = $pdo->prepare("UPDATE facilities SET facility_name = ? WHERE facility_id = ?");
            $stmt->execute([$name, $facilityId]);
            
            $_SESSION['success'] = "Facility updated successfully";
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        }
    }
    
    // Add Device
    if (isset($_POST['action']) && $_POST['action'] === 'add_device') {
        $name = trim($_POST['name']);
        $facilityId = (int)$_POST['facility_id'];
        
        try {
            if (empty($name)) {
                throw new Exception("Device name cannot be empty");
            }
            
            if (strlen($name) > 100) {
                throw new Exception("Device name is too long (max 100 characters)");
            }
            
            if ($facilityId <= 0) {
                throw new Exception("Please select a valid facility");
            }
            
            // Check if device already exists in this facility
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE device_name = ? AND facility_id = ?");
            $stmt->execute([$name, $facilityId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("A device with this name already exists in this facility");
            }
            
            // Insert new device
            $stmt = $pdo->prepare("INSERT INTO devices (device_name, facility_id) VALUES (?, ?)");
            $stmt->execute([$name, $facilityId]);
            
            $_SESSION['success'] = "Device added successfully";
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        }
    }
    
    // Edit Device
    if (isset($_POST['action']) && $_POST['action'] === 'edit_device') {
        $deviceId = (int)$_POST['device_id'];
        $name = trim($_POST['name']);
        $facilityId = (int)$_POST['facility_id'];
        
        try {
            if ($deviceId <= 0) {
                throw new Exception("Invalid device ID");
            }
            
            if (empty($name)) {
                throw new Exception("Device name cannot be empty");
            }
            
            if (strlen($name) > 100) {
                throw new Exception("Device name is too long (max 100 characters)");
            }
            
            // Check if another device with same name exists in this facility
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE device_name = ? AND facility_id = ? AND device_id != ?");
            $stmt->execute([$name, $facilityId, $deviceId]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("A device with this name already exists in this facility");
            }
            
            // Update device
            $stmt = $pdo->prepare("UPDATE devices SET device_name = ? WHERE device_id = ?");
            $stmt->execute([$name, $deviceId]);
            
            $_SESSION['success'] = "Device updated successfully";
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: facilities_devices.php?facility=$facilityId");
            exit();
        }
    }
    
    header("Location: facilities_devices.php");
    exit();
}

// Fetch data
$facilities = getFacilitiesWithDeviceCount($pdo);
$selected_facility_data = $selected_facility > 0 ? getFacilityDetails($pdo, $selected_facility) : null;
$devices = $selected_facility > 0 ? getDevicesByFacility($pdo, $selected_facility) : [];

// Calculate statistics
$totalFacilities = count($facilities);
$totalDevices = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
$devicesWithIssues = $pdo->query("
    SELECT COUNT(DISTINCT d.device_id) 
    FROM devices d 
    INNER JOIN concern_devices cd ON d.device_id = cd.device_id
")->fetchColumn();

$page_title = 'Facilities & Devices - NCITAD';
include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-building text-blue-600"></i>
                Facilities & Devices
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Manage facilities, offices, and IT equipment inventory</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 px-4">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-xs font-medium">Total Facilities</p>
                <h3 class="text-3xl font-bold mt-1"><?= $totalFacilities ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-building text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-xs font-medium">Total Devices</p>
                <h3 class="text-3xl font-bold mt-1"><?= $totalDevices ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-pc-display-horizontal text-3xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-4 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-red-100 text-xs font-medium">Devices with Issues</p>
                <h3 class="text-3xl font-bold mt-1"><?= $devicesWithIssues ?></h3>
            </div>
            <div class="bg-white/20 p-3 rounded-lg">
                <i class="bi bi-exclamation-triangle-fill text-3xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Master-Detail Layout -->
<div class="px-4 pb-4">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        
        <!-- MASTER: Facilities List -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden" style="height: calc(100vh - 230px);">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-3 flex justify-between items-center flex-shrink-0">
                    <h2 class="text-white font-bold flex items-center gap-2">
                        <i class="bi bi-building"></i>
                        Facilities
                    </h2>
                    <button onclick="openAddFacilityModal()" 
                            class="bg-white text-blue-600 px-3 py-1.5 rounded-lg hover:bg-blue-50 transition-all text-sm font-semibold shadow-md flex items-center gap-1">
                        <i class="bi bi-plus-lg"></i> Add
                    </button>
                </div>
                
                <!-- Facilities List -->
                <div class="overflow-y-auto" style="height: calc(100% - 53px);">
                    <?php if (empty($facilities)): ?>
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <i class="bi bi-building text-5xl text-gray-300 mb-3"></i>
                            <p class="text-sm font-semibold text-gray-500">No facilities yet</p>
                            <p class="text-xs text-gray-400 mt-1">Click "Add" to create one</p>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-100">
                            <?php foreach ($facilities as $fac): ?>
                                <a href="facilities_devices.php?facility=<?= $fac['facility_id'] ?>" 
                                   class="block px-4 py-3 hover:bg-blue-50 transition-colors <?= $selected_facility == $fac['facility_id'] ? 'bg-blue-100 border-l-4 border-blue-600' : '' ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-800 truncate flex items-center gap-2 text-sm">
                                                <i class="bi bi-building text-blue-600"></i>
                                                <?= htmlspecialchars($fac['facility_name']) ?>
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="bi bi-pc-display text-gray-400"></i>
                                                <?= $fac['device_count'] ?> <?= $fac['device_count'] == 1 ? 'device' : 'devices' ?>
                                            </p>
                                        </div>
                                        <?php if ($selected_facility == $fac['facility_id']): ?>
                                            <i class="bi bi-chevron-right text-blue-600"></i>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- DETAIL: Devices for Selected Facility -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden" style="height: calc(100vh - 230px);">
                <?php if ($selected_facility_data): ?>
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-4 py-3 flex justify-between items-center flex-shrink-0">
                        <div class="flex-1">
                            <h2 class="text-white font-bold flex items-center gap-2">
                                <i class="bi bi-pc-display-horizontal"></i>
                                Devices in <?= htmlspecialchars($selected_facility_data['facility_name']) ?>
                            </h2>
                            <p class="text-purple-100 text-xs mt-0.5"><?= count($devices) ?> devices registered</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="openAddDeviceModal(<?= $selected_facility ?>)" 
                                    class="bg-white text-purple-600 px-3 py-1.5 rounded-lg hover:bg-purple-50 transition-all text-sm font-semibold shadow-md flex items-center gap-1">
                                <i class="bi bi-plus-lg"></i> Add Device
                            </button>
                            <button onclick="openEditFacilityModal(<?= $selected_facility_data['facility_id'] ?>, '<?= htmlspecialchars($selected_facility_data['facility_name'], ENT_QUOTES) ?>')"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg transition-all text-sm font-semibold shadow-md flex items-center gap-1">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                        </div>
                    </div>
                    
                    <!-- Devices List -->
                    <div class="overflow-y-auto p-4" style="height: calc(100% - 60px);">
                        <?php if (empty($devices)): ?>
                            <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="bi bi-pc-display-horizontal text-5xl text-gray-300 mb-3"></i>
                                <p class="text-sm font-semibold text-gray-500">No devices in this facility</p>
                                <p class="text-xs text-gray-400 mt-1">Click "Add Device" to register equipment</p>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach ($devices as $device): ?>
                                    <div class="border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-start gap-2 flex-1">
                                                <div class="bg-purple-100 text-purple-600 rounded-lg p-2">
                                                    <i class="bi bi-pc-display-horizontal text-lg"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h6 class="font-semibold text-gray-800 truncate text-sm"><?= htmlspecialchars($device['device_name']) ?></h6>
                                                    <p class="text-xs text-gray-500">ID: <?= $device['device_id'] ?></p>
                                                </div>
                                            </div>
                                            <button onclick="openEditDeviceModal(<?= $device['device_id'] ?>, '<?= htmlspecialchars($device['device_name'], ENT_QUOTES) ?>', <?= $selected_facility ?>)"
                                                    class="text-blue-600 hover:text-blue-700 text-sm">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                        
                                        <?php if ($device['concern_count'] > 0): ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold inline-flex items-center">
                                                <i class="bi bi-exclamation-triangle-fill mr-1"></i>
                                                <?= $device['concern_count'] ?> <?= $device['concern_count'] == 1 ? 'Issue' : 'Issues' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold inline-flex items-center">
                                                <i class="bi bi-check-circle-fill mr-1"></i>
                                                No Issues
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- No Facility Selected -->
                    <div class="flex flex-col items-center justify-center h-full text-gray-400">
                        <i class="bi bi-arrow-left-circle text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-500 mb-2">Select a Facility</h3>
                        <p class="text-sm text-gray-400">Choose a facility from the list to view and manage its devices</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<!-- Add Facility Modal -->
<div id="addFacilityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="bg-blue-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold flex items-center">
                <i class="bi bi-plus-circle-fill mr-2"></i>Add New Facility
            </h3>
        </div>
        <form method="post" class="p-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="add_facility">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-building text-blue-600"></i> Facility Name
                </label>
                <input type="text" name="name" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       placeholder="e.g., Computer Laboratory 1" 
                       required>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" 
                        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" 
                        data-modal-close>
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">
                    <i class="bi bi-check-circle mr-1"></i> Add Facility
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Facility Modal -->
<div id="editFacilityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="bg-blue-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold flex items-center">
                <i class="bi bi-pencil-fill mr-2"></i>Edit Facility
            </h3>
        </div>
        <form method="post" class="p-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="edit_facility">
            <input type="hidden" name="facility_id" id="editFacilityId">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-building text-blue-600"></i> Facility Name
                </label>
                <input type="text" name="name" id="editFacilityName"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                       placeholder="e.g., Computer Laboratory 1" 
                       required>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" 
                        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" 
                        data-modal-close>
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">
                    <i class="bi bi-check-circle mr-1"></i> Update Facility
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Add Device Modal -->
<div id="addDeviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="bg-purple-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold flex items-center">
                <i class="bi bi-plus-circle-fill mr-2"></i>Add New Device
            </h3>
        </div>
        <form method="post" class="p-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="add_device">
            <input type="hidden" name="facility_id" id="addDeviceFacilityId">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-pc-display-horizontal text-purple-600"></i> Device Name
                </label>
                <input type="text" name="name" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                       placeholder="e.g., PC-01, Projector, Server" 
                       required>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" 
                        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" 
                        data-modal-close>
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium">
                    <i class="bi bi-check-circle mr-1"></i> Add Device
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Device Modal -->
<div id="editDeviceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="bg-purple-600 text-white px-4 py-3 rounded-t-xl">
            <h3 class="text-lg font-bold flex items-center">
                <i class="bi bi-pencil-fill mr-2"></i>Edit Device
            </h3>
        </div>
        <form method="post" class="p-4">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="edit_device">
            <input type="hidden" name="device_id" id="editDeviceId">
            <input type="hidden" name="facility_id" id="editDeviceFacilityId">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-pc-display-horizontal text-purple-600"></i> Device Name
                </label>
                <input type="text" name="name" id="editDeviceName"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                       placeholder="e.g., PC-01, Projector, Server" 
                       required>
            </div>
            
            <div class="flex justify-end gap-2">
                <button type="button" 
                        class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50" 
                        data-modal-close>
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium">
                    <i class="bi bi-check-circle mr-1"></i> Update Device
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function openAddFacilityModal() {
    document.getElementById('addFacilityModal').classList.remove('hidden');
}

function openEditFacilityModal(id, name) {
    document.getElementById('editFacilityId').value = id;
    document.getElementById('editFacilityName').value = name;
    document.getElementById('editFacilityModal').classList.remove('hidden');
}

function openAddDeviceModal(facilityId) {
    document.getElementById('addDeviceFacilityId').value = facilityId;
    document.getElementById('addDeviceModal').classList.remove('hidden');
}

function openEditDeviceModal(id, name, facilityId) {
    document.getElementById('editDeviceId').value = id;
    document.getElementById('editDeviceName').value = name;
    document.getElementById('editDeviceFacilityId').value = facilityId;
    document.getElementById('editDeviceModal').classList.remove('hidden');
}

// Close modals
document.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = btn.closest('.fixed');
        modal.classList.add('hidden');
    });
});

// Close modal on outside click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'footer.php'; ?>
