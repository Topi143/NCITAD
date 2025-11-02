<?php
session_start();
require_once '../includes/config.php';

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'User Management - NCITAD';

// Add required columns if they don't exist
try {
    // Add is_active column
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT NOT NULL DEFAULT 1 AFTER is_admin");
    }
    
    // Add first_login column to track if user has configured their account
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'first_login'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN first_login TINYINT NOT NULL DEFAULT 1 AFTER is_active");
    }
    
    // Add account_expires_at column for 24-hour expiration
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'account_expires_at'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN account_expires_at DATETIME NULL AFTER first_login");
    }
} catch (PDOException $e) {
    // Columns might already exist, continue
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    
    try {
        // Add new user
        if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Validation
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email address is required']);
                exit();
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit();
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email address already exists']);
                exit();
            }
            
            // Generate username from email (part before @)
            $username = strtolower(explode('@', $email)[0]);
            
            // Check if username exists, append number if needed
            $base_username = $username;
            $counter = 1;
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            while ($stmt->fetch()) {
                $username = $base_username . $counter;
                $stmt->execute([$username]);
                $counter++;
            }
            
            // Default password and temporary faculty name
            $default_password = 'ncitad1234';
            $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
            $faculty_name = 'New User'; // Temporary, will be changed on first login
            
            // Set account expiration to 24 hours from now
            $account_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users (username, faculty_name, email, password, is_admin, is_active, first_login, account_expires_at) VALUES (?, ?, ?, ?, ?, 1, 1, ?)");
            $stmt->execute([$username, $faculty_name, $email, $hashed_password, $is_admin, $account_expires_at]);
            
            // No email sent - admin must share credentials manually
            echo json_encode([
                'success' => true, 
                'message' => 'User account created successfully!<br><strong>Username:</strong> ' . $username . '<br><strong>Default Password:</strong> ' . $default_password . '<br><strong>Email:</strong> ' . $email
            ]);
            exit();
        }
        
        // Update user
        if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
            $user_id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $faculty_name = trim($_POST['faculty_name']);
            $email = trim($_POST['email']);
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            // Validation
            if (empty($username) || empty($faculty_name) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit();
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit();
            }
            
            // Check if username or email already exists for other users
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
                exit();
            }
            
            // Update user
            $stmt = $pdo->prepare("UPDATE users SET username = ?, faculty_name = ?, email = ?, is_admin = ? WHERE user_id = ?");
            $stmt->execute([$username, $faculty_name, $email, $is_admin, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
            exit();
        }
        
        // Toggle user status (enable/disable)
        if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
            $user_id = intval($_POST['user_id']);
            
            // Prevent disabling self
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'You cannot disable your own account']);
                exit();
            }
            
            // Get current status
            $stmt = $pdo->prepare("SELECT is_active, faculty_name FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }
            
            // Toggle status
            $new_status = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            $status_text = $new_status ? 'enabled' : 'disabled';
            echo json_encode(['success' => true, 'message' => "User {$status_text} successfully!", 'new_status' => $new_status]);
            exit();
        }
        
        // Reset password
        if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
            $user_id = intval($_POST['user_id']);
            $new_password = $_POST['new_password'];
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit();
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
            exit();
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}

// Auto-deactivate expired accounts (unconfigured after 24 hours)
try {
    $pdo->exec("UPDATE users SET is_active = 0 WHERE first_login = 1 AND account_expires_at IS NOT NULL AND account_expires_at < NOW()");
} catch (PDOException $e) {
    error_log("Failed to auto-deactivate expired accounts: " . $e->getMessage());
}

// Fetch all users
try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    $where_clauses = [];
    $params = [];
    
    if (!empty($search)) {
        $where_clauses[] = "(username LIKE ? OR faculty_name LIKE ? OR email LIKE ?)";
        $search_term = '%' . $search . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    if ($filter === 'admin') {
        $where_clauses[] = "is_admin = 1";
    } elseif ($filter === 'user') {
        $where_clauses[] = "is_admin = 0";
    } elseif ($filter === 'active') {
        $where_clauses[] = "is_active = 1";
    } elseif ($filter === 'inactive') {
        $where_clauses[] = "is_active = 0";
    } elseif ($filter === 'unconfigured') {
        $where_clauses[] = "first_login = 1";
    }
    
    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
    
    $sql = "SELECT user_id, username, faculty_name, email, is_admin, is_active, first_login, account_expires_at, created_at 
            FROM users 
            $where_sql 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get statistics
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn(),
        'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
        'inactive' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 0")->fetchColumn(),
        'unconfigured' => $pdo->query("SELECT COUNT(*) FROM users WHERE first_login = 1")->fetchColumn()
    ];
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    $users = [];
    $stats = ['total' => 0, 'admins' => 0, 'users' => 0, 'active' => 0, 'inactive' => 0, 'unconfigured' => 0];
}

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-person-gear text-blue-600"></i>
                User Management
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Add, edit, and manage user accounts</p>
        </div>
        <button onclick="openAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2">
            <i class="bi bi-plus-circle"></i>
            Add New User
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="px-4 pb-4">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Total Users</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1"><?= $stats['total'] ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Admins</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1"><?= $stats['admins'] ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-shield-check text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Users</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['users'] ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-person-fill text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Active</p>
                    <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['active'] ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-check-circle-fill text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Disabled</p>
                    <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['inactive'] ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-x-circle-fill text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-600 font-semibold uppercase">Unconfigured</p>
                    <p class="text-2xl font-bold text-orange-600 mt-1"><?= $stats['unconfigured'] ?></p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="bi bi-clock-history text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-lg mb-4 overflow-hidden">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px">
                <button onclick="switchTab('all')" id="tab-all" class="tab-button <?= $filter === 'all' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill mr-2"></i>
                    All Users
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700"><?= $stats['total'] ?></span>
                </button>
                <button onclick="switchTab('active')" id="tab-active" class="tab-button <?= $filter === 'active' ? 'active' : '' ?>">
                    <i class="bi bi-check-circle-fill mr-2"></i>
                    Active
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><?= $stats['active'] ?></span>
                </button>
                <button onclick="switchTab('inactive')" id="tab-inactive" class="tab-button <?= $filter === 'inactive' ? 'active' : '' ?>">
                    <i class="bi bi-x-circle-fill mr-2"></i>
                    Disabled
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700"><?= $stats['inactive'] ?></span>
                </button>
                <button onclick="switchTab('unconfigured')" id="tab-unconfigured" class="tab-button <?= $filter === 'unconfigured' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history mr-2"></i>
                    Unconfigured
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-orange-100 text-orange-700"><?= $stats['unconfigured'] ?></span>
                </button>
                <button onclick="switchTab('admin')" id="tab-admin" class="tab-button <?= $filter === 'admin' ? 'active' : '' ?>">
                    <i class="bi bi-shield-check mr-2"></i>
                    Admins
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700"><?= $stats['admins'] ?></span>
                </button>
                <button onclick="switchTab('user')" id="tab-user" class="tab-button <?= $filter === 'user' ? 'active' : '' ?>">
                    <i class="bi bi-person-fill mr-2"></i>
                    Users
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700"><?= $stats['users'] ?></span>
                </button>
            </nav>
        </div>
        
        <!-- Search Bar -->
        <div class="p-4">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="Search by username, name, or email..." 
                       class="w-full pl-10 pr-24 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       value="<?= htmlspecialchars($search) ?>">
                <?php if (!empty($search)): ?>
                    <button onclick="clearSearch()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="bi bi-x-circle-fill text-lg"></i>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <style>
        .tab-button {
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }
        
        .tab-button:hover {
            color: #3b82f6;
            background-color: #f9fafb;
        }
        
        .tab-button.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            background-color: #eff6ff;
        }
        
        @media (max-width: 768px) {
            .tab-button {
                padding: 0.75rem 1rem;
                font-size: 0.75rem;
            }
        }
    </style>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="bi bi-inbox text-4xl mb-2 block"></i>
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                                            <?= strtoupper(substr($user['faculty_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($user['faculty_name']) ?></div>
                                            <div class="text-xs text-gray-500">@<?= htmlspecialchars($user['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['is_admin']): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700 border border-purple-300">
                                            <i class="bi bi-shield-check mr-1"></i> Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-300">
                                            <i class="bi bi-person mr-1"></i> User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <?php if ($user['is_active']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 border border-green-300">
                                                <i class="bi bi-check-circle-fill mr-1"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 border border-red-300">
                                                <i class="bi bi-x-circle-fill mr-1"></i> Disabled
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['first_login']): ?>
                                            <br>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 border border-orange-300">
                                                <i class="bi bi-clock-history mr-1"></i> Unconfigured
                                            </span>
                                            <?php if ($user['account_expires_at']): ?>
                                                <br>
                                                <span class="text-xs text-gray-500">
                                                    Expires: <?= date('M d, g:i A', strtotime($user['account_expires_at'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick='editUser(<?= json_encode($user) ?>)' 
                                                class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Edit User">
                                            <i class="bi bi-pencil-square text-lg"></i>
                                        </button>
                                        <button onclick="toggleUserStatus(<?= $user['user_id'] ?>, <?= $user['is_active'] ?>)" 
                                                class="<?= $user['is_active'] ? 'text-orange-600 hover:text-orange-800 hover:bg-orange-50' : 'text-green-600 hover:text-green-800 hover:bg-green-50' ?> p-2 rounded-lg transition-colors"
                                                title="<?= $user['is_active'] ? 'Disable' : 'Enable' ?> User"
                                                <?= $user['user_id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                            <i class="bi bi-<?= $user['is_active'] ? 'toggle-on' : 'toggle-off' ?> text-lg"></i>
                                        </button>
                                        <button onclick="resetPassword(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['faculty_name']) ?>')" 
                                                class="text-purple-600 hover:text-purple-800 p-2 hover:bg-purple-50 rounded-lg transition-colors"
                                                title="Reset Password">
                                            <i class="bi bi-key-fill text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeUserModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800" id="modalTitle">Add New User</h3>
                <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="bi bi-x-lg text-2xl"></i>
                </button>
            </div>
            
            <form id="userForm" class="space-y-4">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action" value="add_user">
                
                <div id="addUserFields">
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <p class="text-sm text-blue-800">
                            <i class="bi bi-info-circle-fill mr-2"></i>
                            <strong>New Account Setup:</strong> Default password is <strong>ncitad1234</strong>. 
                            Please share the login credentials with the user manually. They must configure their account within 24 hours or it will be automatically deactivated.
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-envelope text-blue-600 mr-1"></i>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="user@example.com">
                        <p class="text-xs text-gray-500 mt-1">Username will be auto-generated from email</p>
                    </div>
                    
                    <div class="flex items-center gap-2 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <input type="checkbox" id="is_admin" name="is_admin" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <label for="is_admin" class="text-sm font-semibold text-gray-700">
                            <i class="bi bi-shield-check text-purple-600 mr-1"></i>
                            Grant Administrator Privileges
                        </label>
                    </div>
                </div>
                
                <div id="editUserFields" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-person text-blue-600 mr-1"></i>
                                Username
                            </label>
                            <input type="text" id="username_edit" name="username" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Enter username">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="bi bi-envelope text-blue-600 mr-1"></i>
                                Email Address
                            </label>
                            <input type="email" id="email_edit" name="email" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="user@example.com">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="bi bi-person-badge text-blue-600 mr-1"></i>
                            Full Name
                        </label>
                        <input type="text" id="faculty_name" name="faculty_name" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter full name">
                    </div>
                    
                    <div class="flex items-center gap-2 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <input type="checkbox" id="is_admin_edit" name="is_admin" class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                        <label for="is_admin_edit" class="text-sm font-semibold text-gray-700">
                            <i class="bi bi-shield-check text-purple-600 mr-1"></i>
                            Grant Administrator Privileges
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeUserModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition-colors">
                        <i class="bi bi-save mr-2"></i>
                        <span id="submitButtonText">Add User</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeResetPasswordModal()"></div>
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
                <button onclick="closeResetPasswordModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="bi bi-x-lg text-2xl"></i>
                </button>
            </div>
            
            <form id="resetPasswordForm" class="space-y-4">
                <input type="hidden" id="resetUserId" name="user_id">
                <input type="hidden" name="action" value="reset_password">
                
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="bi bi-exclamation-triangle-fill mr-2"></i>
                        Resetting password for: <strong id="resetUserName"></strong>
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="bi bi-key text-blue-600 mr-1"></i>
                        New Password
                    </label>
                    <input type="password" id="new_password" name="new_password" required minlength="6"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Enter new password (min 6 characters)">
                </div>
                
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeResetPasswordModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-semibold transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-semibold transition-colors">
                        <i class="bi bi-key-fill mr-2"></i>
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('submitButtonText').textContent = 'Add User';
    document.getElementById('formAction').value = 'add_user';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('addUserFields').style.display = 'block';
    document.getElementById('editUserFields').style.display = 'none';
    
    // Enable add user fields
    document.getElementById('email').required = true;
    document.getElementById('email').disabled = false;
    
    // Disable edit fields to prevent them from being submitted
    document.getElementById('username_edit').required = false;
    document.getElementById('username_edit').disabled = true;
    document.getElementById('faculty_name').required = false;
    document.getElementById('faculty_name').disabled = true;
    document.getElementById('email_edit').required = false;
    document.getElementById('email_edit').disabled = true;
    
    document.getElementById('userModal').classList.remove('hidden');
}

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('submitButtonText').textContent = 'Update User';
    document.getElementById('formAction').value = 'update_user';
    document.getElementById('userId').value = user.user_id;
    document.getElementById('username_edit').value = user.username;
    document.getElementById('faculty_name').value = user.faculty_name;
    document.getElementById('email_edit').value = user.email;
    document.getElementById('is_admin_edit').checked = user.is_admin == 1;
    document.getElementById('addUserFields').style.display = 'none';
    document.getElementById('editUserFields').style.display = 'block';
    
    // Disable add user fields to prevent them from being submitted
    document.getElementById('email').required = false;
    document.getElementById('email').disabled = true;
    
    // Enable edit fields
    document.getElementById('username_edit').required = true;
    document.getElementById('username_edit').disabled = false;
    document.getElementById('faculty_name').required = true;
    document.getElementById('faculty_name').disabled = false;
    document.getElementById('email_edit').required = true;
    document.getElementById('email_edit').disabled = false;
    
    document.getElementById('userModal').classList.remove('hidden');
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function resetPassword(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('resetPasswordModal').classList.remove('hidden');
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').classList.add('hidden');
}

// Form submissions
document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Debug: Check what's being sent
    console.log('Form action:', formData.get('action'));
    console.log('Email:', formData.get('email'));
    
    try {
        const response = await fetch('adduserandadmin.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeUserModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
        console.error('Error:', error);
    }
});

document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('adduserandadmin.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            closeResetPasswordModal();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
    }
});

// Toggle user status
async function toggleUserStatus(userId, currentStatus) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('user_id', userId);
    
    try {
        const response = await fetch('adduserandadmin.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', 'error');
    }
}

// Tab switching
function switchTab(filter) {
    const search = document.getElementById('searchInput').value;
    const url = new URLSearchParams();
    if (search) url.append('search', search);
    url.append('filter', filter);
    window.location.href = `adduserandadmin.php?${url.toString()}`;
}

// Search functionality
function applySearch() {
    const search = document.getElementById('searchInput').value;
    const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
    const url = new URLSearchParams();
    if (search) url.append('search', search);
    url.append('filter', currentFilter);
    window.location.href = `adduserandadmin.php?${url.toString()}`;
}

function clearSearch() {
    const currentFilter = new URLSearchParams(window.location.search).get('filter') || 'all';
    window.location.href = `adduserandadmin.php?filter=${currentFilter}`;
}

// Enter key to search
document.getElementById('searchInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') applySearch();
});
</script>

<?php include 'footer.php'; ?>
