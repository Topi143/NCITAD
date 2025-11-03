<?php
// This file should be included after session_start() and admin check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'NCITAD Admin' ?></title>
    <link rel="icon" type="image/png" href="../assets/images/norzagaray-college-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#1e293b',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Toast Notification Styles */
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .toast-enter {
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast-exit {
            animation: slideOutRight 0.3s ease-in;
        }
        
        /* Full screen layout - no padding/margin */
        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        /* Sidebar takes full height */
        .sidebar-full {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
        }
        
        /* Main content area fills remaining space */
        .main-content-full {
            height: 100vh;
            margin-left: 0;
        }
        
        @media (min-width: 1024px) {
            .main-content-full {
                margin-left: 18rem; /* 72 = w-72 */
            }
        }
        
        /* Scrollable content area */
        .content-wrapper {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Custom scrollbar */
        .content-wrapper::-webkit-scrollbar {
            width: 8px;
        }
        .content-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .content-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        .content-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Ensure sidebar scrolling works */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    
    <!-- Mobile Menu Toggle -->
    <button id="mobile-menu-btn" class="lg:hidden fixed top-4 left-4 z-50 bg-white p-3 rounded-xl shadow-lg hover:bg-gray-50 transition-all">
        <i class="bi bi-list text-2xl text-gray-700"></i>
    </button>

    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar-full w-72 bg-gradient-to-b from-slate-800 via-slate-900 to-slate-950 text-white flex flex-col shadow-2xl z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Logo Section -->
        <div class="text-center mb-6 mt-6 px-6 flex-shrink-0">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-4 shadow-xl">
                <h4 class="text-2xl font-extrabold flex items-center justify-center gap-2">
                    <i class="bi bi-shield-lock-fill text-white"></i> 
                    <span class="bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">NCITAD</span>
                </h4>
                <p class="text-blue-100 text-xs mt-1 font-medium">Norzagaray College IT Assistance Desk</p>
            </div>
        </div>

        <!-- User Info Card -->
        <div class="mx-4 mb-6 bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-xl font-bold shadow-lg">
                    <?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                    <p class="text-xs text-blue-200">Administrator</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto pb-4 sidebar-scroll">
            <a href="dashboard.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'dashboard.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-speedometer2 mr-3 text-lg <?= $current_page === 'dashboard.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'dashboard.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Dashboard</span>
                <?php if ($current_page === 'dashboard.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <a href="concerns.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'concerns.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-list-task mr-3 text-lg <?= $current_page === 'concerns.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'concerns.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Concerns</span>
                <?php if ($current_page === 'concerns.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <a href="history.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'history.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-archive mr-3 text-lg <?= $current_page === 'history.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'history.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">History</span>
                <?php if ($current_page === 'history.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <a href="reports.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'reports.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-graph-up-arrow mr-3 text-lg <?= $current_page === 'reports.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'reports.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Reports</span>
                <?php if ($current_page === 'reports.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <div class="my-4 border-t border-white/10"></div>
            
            <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p>
            
            <a href="facilities_devices.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= ($current_page === 'facilities_devices.php' || $current_page === 'facilities.php' || $current_page === 'device.php') ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-layout-split mr-3 text-lg <?= ($current_page === 'facilities_devices.php' || $current_page === 'facilities.php' || $current_page === 'device.php') ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= ($current_page === 'facilities_devices.php' || $current_page === 'facilities.php' || $current_page === 'device.php') ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Facilities & Devices</span>
                <?php if ($current_page === 'facilities_devices.php' || $current_page === 'facilities.php' || $current_page === 'device.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <a href="adduserandadmin.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'adduserandadmin.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-person-gear mr-3 text-lg <?= $current_page === 'adduserandadmin.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'adduserandadmin.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Users</span>
                <?php if ($current_page === 'adduserandadmin.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
            
            <div class="my-4 border-t border-white/10"></div>
            
            <a href="settings.php" class="group flex items-center px-4 py-3.5 rounded-xl transition-all duration-200 <?= $current_page === 'settings.php' ? 'bg-gradient-to-r from-blue-500 to-blue-600 shadow-lg shadow-blue-500/50' : 'hover:bg-white/10' ?>">
                <i class="bi bi-gear-fill mr-3 text-lg <?= $current_page === 'settings.php' ? 'text-white' : 'text-blue-300 group-hover:text-white' ?>"></i>
                <span class="font-medium <?= $current_page === 'settings.php' ? 'text-white' : 'text-gray-300 group-hover:text-white' ?>">Account Settings</span>
                <?php if ($current_page === 'settings.php'): ?>
                    <i class="bi bi-chevron-right ml-auto text-white"></i>
                <?php endif; ?>
            </a>
        </nav>

        <!-- Logout Button -->
        <div class="p-4 border-t border-white/10 flex-shrink-0">
            <a href="../logout.php" class="flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <i class="bi bi-box-arrow-right text-lg"></i>
                <span class="font-semibold">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content-full">
        <div class="content-wrapper">
            <div class="w-full h-full">

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-3 pointer-events-none">
        <!-- Toasts will be inserted here -->
    </div>

    <script>
        // Toast Notification System
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const colors = {
                success: 'border-green-500',
                error: 'border-red-500',
                warning: 'border-yellow-500',
                info: 'border-blue-500'
            };
            
            const icons = {
                success: 'check-circle-fill text-green-500',
                error: 'x-circle-fill text-red-500',
                warning: 'exclamation-triangle-fill text-yellow-500',
                info: 'info-circle-fill text-blue-500'
            };
            
            toast.className = `toast-enter pointer-events-auto flex items-start gap-3 bg-white rounded-xl shadow-2xl p-4 max-w-md border-l-4 ${colors[type] || colors.info}`;
            toast.innerHTML = `
                <i class="bi bi-${icons[type] || icons.info} text-2xl flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 break-words">${message}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;
            
            container.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // Show toast notifications from session
        <?php if (isset($_SESSION['success'])): ?>
            showToast('<?= addslashes($_SESSION['success']) ?>', 'success');
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            showToast('<?= addslashes($_SESSION['error']) ?>', 'error');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning'])): ?>
            showToast('<?= addslashes($_SESSION['warning']) ?>', 'warning');
            <?php unset($_SESSION['warning']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info'])): ?>
            showToast('<?= addslashes($_SESSION['info']) ?>', 'info');
            <?php unset($_SESSION['info']); ?>
        <?php endif; ?>

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        });
        
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        });
    </script>
