<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid form submission";
        header("Location: addnewconcern.php");
        exit();
    }

    $description = trim($_POST['description']);
    $device_ids = $_POST['devices'] ?? [];

    try {
        // Validation
        if (empty($description)) {
            throw new Exception("Please describe your concern");
        }

        if (strlen($description) < 10) {
            throw new Exception("Description must be at least 10 characters long");
        }

        if (empty($device_ids)) {
            throw new Exception("Please select at least one device");
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert concern
        $stmt = $pdo->prepare("INSERT INTO concerns (user_id, description, status, created_at) VALUES (?, ?, 'Pending', NOW())");
        $stmt->execute([$user_id, $description]);
        $concern_id = $pdo->lastInsertId();

        // Insert device associations
        $stmt = $pdo->prepare("INSERT INTO concern_devices (concern_id, device_id) VALUES (?, ?)");
        foreach ($device_ids as $device_id) {
            if (is_numeric($device_id) && $device_id > 0) {
                $stmt->execute([$concern_id, $device_id]);
            }
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Concern submitted successfully! Ticket #$concern_id has been created.";
        header("Location: concernlist.php");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: addnewconcern.php");
        exit();
    }
}

// Fetch all facilities with their devices
$stmt = $pdo->query("
    SELECT f.facility_id, f.facility_name,
           d.device_id, d.device_name
    FROM facilities f
    LEFT JOIN devices d ON f.facility_id = d.facility_id
    ORDER BY f.facility_name, d.device_name
");

$facilities = [];
while ($row = $stmt->fetch()) {
    $facility_id = $row['facility_id'];
    if (!isset($facilities[$facility_id])) {
        $facilities[$facility_id] = [
            'facility_name' => $row['facility_name'],
            'devices' => []
        ];
    }
    if ($row['device_id']) {
        $facilities[$facility_id]['devices'][] = [
            'device_id' => $row['device_id'],
            'device_name' => $row['device_name']
        ];
    }
}

$page_title = 'Add New Concern - NCITAD';
include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-plus-circle-fill text-blue-600"></i>
                Submit New Concern
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Report an IT issue or request technical assistance</p>
        </div>
        <a href="concernlist.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold flex items-center gap-2 transition-colors">
            <i class="bi bi-arrow-left"></i> Back to My Concerns
        </a>
    </div>
</div>

<!-- Form Content -->
<div class="px-4 pb-4">
    <div class="w-full">
        
        <!-- Main Form -->
        <form method="POST" id="concernForm" class="bg-white rounded-xl shadow-lg overflow-hidden">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                <h2 class="text-white font-bold text-lg flex items-center gap-2">
                    <i class="bi bi-pencil-square"></i>
                    Concern Details
                </h2>
            </div>

            <!-- Form Body -->
            <div class="p-6 space-y-6">
                
                <!-- Description Field -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="bi bi-chat-left-text text-blue-600"></i> Describe Your Concern <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        name="description" 
                        id="description"
                        rows="6" 
                        class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="Please provide a detailed description of your IT concern or issue. Include any error messages, symptoms, or relevant information that will help us resolve your problem quickly."
                        required
                        minlength="10"
                    ></textarea>
                    <div class="flex justify-between items-center mt-2">
                        <p class="text-xs text-gray-500">Minimum 10 characters required</p>
                        <p class="text-xs text-gray-500">
                            <span id="charCount">0</span> characters
                        </p>
                    </div>
                </div>

                <!-- Device Selection -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-3">
                        <i class="bi bi-hdd-rack text-blue-600"></i> Select Affected Device(s) <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-600 mb-3">Choose the facility and device(s) related to your concern. You can select multiple devices.</p>
                    
                    <!-- Search Box -->
                    <div class="mb-4">
                        <div class="relative">
                            <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input 
                                type="text" 
                                id="deviceSearch" 
                                placeholder="Search facilities or devices..."
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                        </div>
                    </div>

                    <!-- Selected Devices Display -->
                    <div id="selectedDevices" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-semibold text-green-800 mb-2 flex items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i>
                            Selected Devices (<span id="selectedCount">0</span>)
                        </p>
                        <div id="selectedList" class="flex flex-wrap gap-2"></div>
                    </div>

                    <!-- Facilities and Devices Grid -->
                    <div id="facilitiesContainer" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-96 overflow-y-auto p-2 border border-gray-200 rounded-lg">
                        <?php if (!empty($facilities)): ?>
                            <?php foreach ($facilities as $facility_id => $facility): ?>
                                <div class="facility-card bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors">
                                    <h3 class="font-bold text-gray-800 mb-2 flex items-center gap-2">
                                        <i class="bi bi-building text-blue-600"></i>
                                        <?= htmlspecialchars($facility['facility_name']) ?>
                                    </h3>
                                    <?php if (!empty($facility['devices'])): ?>
                                        <div class="space-y-2">
                                            <?php foreach ($facility['devices'] as $device): ?>
                                                <label class="device-item flex items-center gap-2 p-2 bg-white rounded hover:bg-blue-50 cursor-pointer transition-colors">
                                                    <input 
                                                        type="checkbox" 
                                                        name="devices[]" 
                                                        value="<?= $device['device_id'] ?>"
                                                        class="device-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                                                        data-device-name="<?= htmlspecialchars($device['device_name']) ?>"
                                                        data-facility-name="<?= htmlspecialchars($facility['facility_name']) ?>"
                                                    >
                                                    <span class="text-sm text-gray-700 flex-1"><?= htmlspecialchars($device['device_name']) ?></span>
                                                    <i class="bi bi-hdd text-gray-400"></i>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-xs text-gray-500 italic">No devices available</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-2 text-center py-8 text-gray-500">
                                <i class="bi bi-inbox text-4xl mb-2"></i>
                                <p>No facilities or devices available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Form Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                <a href="concernlist.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors flex items-center gap-2">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors shadow-md hover:shadow-lg flex items-center gap-2"
                >
                    <i class="bi bi-send-fill"></i> Submit Concern
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// Character counter
const descriptionField = document.getElementById('description');
const charCount = document.getElementById('charCount');

descriptionField.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});

// Device selection handling
const deviceCheckboxes = document.querySelectorAll('.device-checkbox');
const selectedDevicesContainer = document.getElementById('selectedDevices');
const selectedList = document.getElementById('selectedList');
const selectedCount = document.getElementById('selectedCount');

function updateSelectedDevices() {
    const selected = Array.from(deviceCheckboxes).filter(cb => cb.checked);
    
    if (selected.length > 0) {
        selectedDevicesContainer.classList.remove('hidden');
        selectedCount.textContent = selected.length;
        
        selectedList.innerHTML = selected.map(cb => `
            <span class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-green-300 rounded-lg text-sm">
                <i class="bi bi-hdd text-green-600"></i>
                <span class="font-medium text-gray-800">${cb.dataset.deviceName}</span>
                <span class="text-gray-500">•</span>
                <span class="text-gray-600 text-xs">${cb.dataset.facilityName}</span>
            </span>
        `).join('');
    } else {
        selectedDevicesContainer.classList.add('hidden');
    }
}

deviceCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateSelectedDevices);
});

// Search functionality
const searchInput = document.getElementById('deviceSearch');
const facilityCards = document.querySelectorAll('.facility-card');

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    
    facilityCards.forEach(card => {
        const facilityName = card.querySelector('h3').textContent.toLowerCase();
        const deviceItems = card.querySelectorAll('.device-item');
        let hasVisibleDevice = false;
        
        deviceItems.forEach(item => {
            const deviceName = item.querySelector('span').textContent.toLowerCase();
            const matches = deviceName.includes(searchTerm) || facilityName.includes(searchTerm);
            
            if (matches) {
                item.style.display = 'flex';
                hasVisibleDevice = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        card.style.display = hasVisibleDevice || facilityName.includes(searchTerm) ? 'block' : 'none';
    });
});

// Form validation
const concernForm = document.getElementById('concernForm');
concernForm.addEventListener('submit', function(e) {
    const selectedDevices = Array.from(deviceCheckboxes).filter(cb => cb.checked);
    
    if (selectedDevices.length === 0) {
        e.preventDefault();
        showToast('Please select at least one device', 'error');
        return false;
    }
    
    if (descriptionField.value.trim().length < 10) {
        e.preventDefault();
        showToast('Description must be at least 10 characters long', 'error');
        descriptionField.focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split animate-spin"></i> Submitting...';
});
</script>

<?php include 'footer.php'; ?>
