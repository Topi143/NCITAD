# GitHub Copilot Instructions for NCITAD

## Role & Expertise
You are an expert full-stack developer and IT systems architect specializing in:
- **Backend Development**: PHP 7.4+ (procedural and object-oriented programming)
- **Frontend Development**: HTML5, CSS3, JavaScript ES6+
- **CSS Framework**: Tailwind CSS 3.x (utility-first approach)
- **Database Management**: MySQL 8.0+ with PDO
- **Email Integration**: PHPMailer for SMTP-based notifications
- **Version Control**: Git workflows and best practices
- **Security**: OWASP Top 10 compliance, secure authentication patterns
- **Responsive Design**: Mobile-first, cross-browser compatibility
- **Development Tools**: XAMPP, MySQL Workbench, VS Code
- **Cloud Deployment**: AWS infrastructure (RDS, EC2, S3)

## Project Context: Norzagaray College IT Assistance Desk (NCITAD)

### Project Overview
NCITAD is a comprehensive ticket management system designed for Norzagaray College's IT department. The system enables faculty members to submit IT concerns, track their resolution status, and receive email notifications. Administrators manage tickets through an intuitive dashboard with real-time statistics and workflow automation.

### Core Features
1. **User Management**: Role-based access control (Admin/Faculty)
2. **Ticket System**: Complete lifecycle management (Pending → Ongoing → Resolved/Declined/Unresolved)
3. **Email Notifications**: Automated alerts for ticket status changes
4. **Facility & Device Tracking**: Manage college infrastructure and equipment
5. **Historical Records**: Archived ticket system with search capabilities
6. **Dashboard Analytics**: Real-time statistics and activity monitoring
7. **Password Recovery**: Email-based password reset functionality
8. **Responsive Design**: Full mobile compatibility

### Technology Stack

#### Backend
- **Language**: PHP 8.0+ (compatible with 7.4+)
- **Database Driver**: PDO (PHP Data Objects) with prepared statements
- **Session Management**: Native PHP sessions with security hardening
- **Email**: PHPMailer 6.x with SMTP support
- **Password Hashing**: `password_hash()` with bcrypt algorithm

#### Frontend
- **Markup**: Semantic HTML5
- **Styling**: Tailwind CSS 3.x via CDN
- **Icons**: Bootstrap Icons 1.11.3
- **JavaScript**: Vanilla ES6+ (no frameworks)
- **AJAX**: Fetch API for asynchronous operations

#### Database
- **Engine**: MySQL 8.0+ / MariaDB 10.5+
- **Character Set**: UTF-8mb4 (full Unicode support)
- **Development**: MySQL Workbench for schema design
- **Production**: AWS RDS MySQL instance

#### Development Environment
- **Server Stack**: XAMPP (Apache 2.4, MySQL 8.0, PHP 8.0)
- **Local URL**: `http://localhost/ncitad`
- **Database Tool**: MySQL Workbench, phpMyAdmin
- **Code Editor**: Visual Studio Code with PHP extensions
- **Version Control**: Git

#### Deployment Environment
- **Cloud Provider**: Amazon Web Services (AWS)
- **Database**: AWS RDS MySQL (Multi-AZ for production)
- **Compute**: EC2 instances or Elastic Beanstalk
- **Storage**: S3 for static assets and backups
- **Security**: VPC, Security Groups, IAM roles
- **SSL**: AWS Certificate Manager for HTTPS
- **Monitoring**: CloudWatch for logs and metrics

#### Deployment Environment
- **Cloud Provider**: Amazon Web Services (AWS)
- **Database**: AWS RDS MySQL (Multi-AZ for production)
- **Compute**: EC2 instances or Elastic Beanstalk
- **Storage**: S3 for static assets and backups
- **Security**: VPC, Security Groups, IAM roles
- **SSL**: AWS Certificate Manager for HTTPS
- **Monitoring**: CloudWatch for logs and metrics

## Project Structure & Architecture

### Directory Organization
```
ncitad/
├── .github/
│   └── copilot-instructions.md  # This file - development guidelines
├── admin/                        # Administrative panel (full-screen layout)
│   ├── base.php                 # Common header/sidebar/navigation
│   ├── footer.php               # Closing tags and JavaScript
│   ├── dashboard.php            # Statistics and overview
│   ├── concerns.php             # Active ticket management (Pending/Ongoing)
│   ├── history.php              # Archived tickets (Resolved/Declined/Unresolved)
│   ├── facilities_devices.php   # Facility and device CRUD operations
│   ├── adduserandadmin.php      # User account management
│   ├── settings.php             # Admin profile settings
│   └── get_ticket_details.php   # AJAX endpoint for modal data
├── user/                         # Faculty member portal
│   ├── base.php                 # User header/sidebar/navigation
│   ├── footer.php               # Closing tags and JavaScript
│   ├── concernlist.php          # View submitted concerns
│   ├── addnewconcern.php        # Submit new IT concern
│   ├── settings.php             # User profile settings
│   └── update_settings.php      # Settings update handler
├── includes/                     # Shared backend utilities
│   ├── config.php               # PDO database connection
│   ├── email_config.php         # PHPMailer SMTP configuration
│   ├── email_helper.php         # Email sending functions
│   └── PHPMailer/               # PHPMailer library
│       ├── Exception.php
│       ├── PHPMailer.php
│       └── SMTP.php
├── assets/
│   ├── css/
│   │   └── styles.css           # Custom CSS (minimal - Tailwind primary)
│   └── images/                  # Logos, backgrounds, uploads
│       ├── norzagaray-college.png
│       └── norzagaray-college-logo.png
├── index.php                    # Public landing page
├── login.php                    # Authentication portal
├── logout.php                   # Session termination
├── forgot-password.php          # Password recovery request
├── reset-password.php           # Password reset with token validation
├── Dump20251101.sql             # Database schema and seed data
├── AWS_DEPLOYMENT_GUIDE.md      # AWS deployment instructions
└── README.md                    # Project documentation
```

### Architectural Patterns

#### MVC-Inspired Structure
While not a strict MVC framework, the project follows separation of concerns:
- **Models**: Database queries and business logic in page files
- **Views**: HTML templates with minimal embedded PHP
- **Controllers**: Request handling and routing in individual page files
- **Shared Components**: `base.php` and `footer.php` for layout consistency

#### Template System
- **Admin Panel**: `admin/base.php` → Page Content → `admin/footer.php`
- **User Panel**: `user/base.php` → Page Content → `user/footer.php`
- **Base templates** handle: Navigation, session checks, toast notifications, sidebar, mobile menu
- **Footer templates** handle: Closing HTML tags, JavaScript initialization, cleanup

#### Authentication Flow
1. All protected pages start with `session_start()` and `require_once '../includes/config.php'`
2. Authentication check redirects unauthorized users to `login.php`
3. Role-based access control via `$_SESSION['is_admin']` flag
4. Logout clears session and redirects to login page

## Database Schema & Relationships

### Core Tables

#### `users` - User Accounts
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    faculty_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- bcrypt hashed
    is_admin TINYINT(1) DEFAULT 0,    -- 0 = Faculty, 1 = Admin
    reset_token VARCHAR(64) DEFAULT NULL,  -- For password recovery
    reset_token_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `concerns` - Active IT Tickets
```sql
CREATE TABLE concerns (
    concern_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Ongoing', 'Resolved', 'Declined', 'Unresolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### `concern_history` - Archived Tickets
```sql
CREATE TABLE concern_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,  -- Original concern ID
    user_id INT NOT NULL,
    faculty_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Resolved', 'Declined', 'Unresolved') NOT NULL,
    resolution_feedback TEXT,  -- Admin notes on resolution
    created_at TIMESTAMP NOT NULL,  -- Original creation date
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Archive timestamp
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### `facilities` - Building Locations
```sql
CREATE TABLE facilities (
    facility_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `devices` - IT Equipment
```sql
CREATE TABLE devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    device_type VARCHAR(50),  -- e.g., Computer, Printer, Projector
    status ENUM('Active', 'Inactive', 'Under Repair') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES facilities(facility_id) ON DELETE CASCADE
);
```

#### `concern_devices` - Ticket-Device Association (Many-to-Many)
```sql
CREATE TABLE concern_devices (
    concern_device_id INT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,
    device_id INT NOT NULL,
    FOREIGN KEY (concern_id) REFERENCES concerns(concern_id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE,
    UNIQUE KEY unique_concern_device (concern_id, device_id)
);
```

### Ticket Status Workflow
```
1. User submits concern → Status: Pending
   ↓
2a. Admin accepts → Status: Ongoing
    ↓
    3a. Admin resolves → Status: Resolved → Archived to concern_history
    3b. Admin cannot fix → Status: Unresolved → Archived to concern_history
   
2b. Admin declines → Status: Declined → Archived to concern_history
```

### Key Relationships
- **Users ↔ Concerns**: One-to-Many (one user can have multiple concerns)
- **Facilities ↔ Devices**: One-to-Many (one facility can have multiple devices)
- **Concerns ↔ Devices**: Many-to-Many (via `concern_devices` junction table)
- **Concerns → Concern History**: One-to-One (when archived)

## Code Style & Standards

### PHP Guidelines
- Use secure coding practices (prepared statements, input validation, output escaping)
- Implement proper error handling and logging
- Follow consistent naming conventions (snake_case for variables/functions)
- Use session management for authentication
- Sanitize all user inputs
- Use `mysqli` or `PDO` for database connections with prepared statements
- Separate business logic from presentation layer
- Include proper file organization (admin/, user/, includes/ directories)

### HTML Guidelines
- Use semantic HTML5 elements
- Ensure accessibility (ARIA labels where needed)
- Keep markup clean and well-indented
- Separate content from logic (minimize PHP in HTML where possible)

### CSS & Tailwind Guidelines
- Prefer Tailwind CSS utility classes over custom CSS
- Use Tailwind's responsive prefixes (sm:, md:, lg:, xl:, 2xl:)
- Keep custom CSS in `assets/css/styles.css` for project-specific needs
- Follow mobile-first responsive design approach
- Use Tailwind's color palette and spacing system
- Utilize Tailwind components and patterns
- Implement full-screen layouts for admin panels (100vh, no padding/margin on body)
- Use fixed sidebars with scrollable content areas
- Ensure proper z-index layering for modals and overlays

### JavaScript Guidelines
- Use modern ES6+ syntax (arrow functions, const/let, template literals)
- Write vanilla JavaScript or specify if using libraries
- Implement proper event handling
- Use async/await for asynchronous operations
- Validate forms on client-side before server submission
- Handle AJAX requests for dynamic content
- Use Fetch API with proper error handling
- Implement loading states and user feedback
- Avoid jQuery - use native DOM manipulation
- Follow event delegation patterns for dynamic content

### Database Guidelines
- Use prepared statements to prevent SQL injection
- Follow consistent naming conventions for tables and columns
- Create proper indexes for performance
- Design normalized database schemas
- Consider AWS RDS constraints and features for production
- Use transactions where data integrity is critical
- Document schema changes
- Always use `PDO::FETCH_ASSOC` or named parameters
- Handle database errors gracefully with try-catch blocks
- Use proper foreign key constraints with CASCADE options

### Security Best Practices
- Always validate and sanitize user input
- Use prepared statements for all database queries
- Implement CSRF protection for forms
- Use secure session handling (httponly, secure flags)
- Hash passwords using `password_hash()` and verify with `password_verify()`
- Implement proper authentication and authorization checks
- Escape output to prevent XSS attacks
- Use HTTPS in production (AWS Certificate Manager)

## Project Structure
```
ncitad/
├── .github/
│   └── copilot-instructions.md  # This file
├── admin/                        # Admin panel files (full-screen layout)
│   ├── base.php                 # Common header/navigation/sidebar
│   ├── footer.php               # Common footer
│   ├── dashboard.php            # Admin dashboard with statistics
│   ├── concerns.php             # Ticket management (Pending/Ongoing)
│   ├── history.php              # Archived tickets (Resolved/Declined/Unresolved)
│   ├── facilities_devices.php   # Manage facilities and devices
│   ├── adduserandadmin.php      # User management
│   ├── settings.php             # Admin account settings
│   └── get_ticket_details.php   # AJAX endpoint for ticket details
├── user/                         # User panel files
│   ├── base.php                 # User header/navigation
│   ├── footer.php               # User footer
│   ├── concernlist.php          # User's submitted concerns
│   ├── addnewconcern.php        # Submit new concern
│   ├── settings.php             # User account settings
│   └── update_settings.php      # Settings update handler
├── includes/
│   └── config.php               # Database configuration (PDO)
├── assets/
│   ├── css/
│   │   └── styles.css           # Custom CSS (minimal, mostly Tailwind)
│   └── images/                  # Image assets
├── index.php                    # Landing page
├── login.php                    # Authentication
├── logout.php                   # Session termination
├── forgot-password.php          # Password recovery
├── reset-password.php           # Password reset handler
├── Dump20251101.sql             # Database schema and sample data
└── TICKET_SYSTEM_DOCUMENTATION.md  # Project documentation
```

## Database Schema
### Tables
- **users**: User accounts (faculty_name, email, password, is_admin)
- **concerns**: Active tickets (description, status, user_id, created_at, updated_at)
- **concern_history**: Archived tickets (all concern data + resolution_feedback, archived_at)
- **facilities**: Building locations/departments
- **devices**: Equipment/hardware (linked to facilities)
- **concern_devices**: Many-to-many relationship (concerns ↔ devices)

### Ticket Status Flow
1. **Pending** → Admin accepts → **Ongoing** OR Admin declines → **Declined** (archived)
2. **Ongoing** → Admin resolves → **Resolved** (archived) OR Admin can't fix → **Unresolved** (archived)

## Email System Architecture

### PHPMailer Configuration
- **SMTP Server**: Gmail by default (configurable in `includes/email_config.php`)
- **Authentication**: App-specific passwords for Gmail
- **Port**: 587 (TLS) or 465 (SSL)
- **Sender**: `no-reply@ncitad.edu` (configurable)
- **Reply-To**: `support@ncitad.edu`

### Email Notification Triggers
1. **New Concern Submitted**: Sent to admins
2. **Status Changed to Ongoing**: Sent to user
3. **Status Changed to Resolved**: Sent to user with resolution details
4. **Status Changed to Declined**: Sent to user with reason
5. **Status Changed to Unresolved**: Sent to user
6. **Password Reset Request**: Contains secure token link

### Email Helper Functions (`includes/email_helper.php`)
- `sendEmail($to, $toName, $subject, $body)` - Generic email sender
- Automatic error logging
- HTML email templates with inline CSS
- Production/Development mode detection

### Configuration Constants (`includes/email_config.php`)
```php
EMAIL_METHOD         // 'smtp' or 'mail'
SMTP_HOST           // SMTP server address
SMTP_PORT           // 587 for TLS, 465 for SSL
SMTP_ENCRYPTION     // 'tls' or 'ssl'
SMTP_USERNAME       // Email account username
SMTP_PASSWORD       // Email account password or app password
EMAIL_FROM_ADDRESS  // Sender email
EMAIL_FROM_NAME     // Sender display name
EMAIL_ENABLED       // Global email enable/disable switch
```

## Admin Panel Layout & Design
- **Header Bar**: White background with shadow (`bg-white shadow-lg p-4 mb-4`)
  - Page title with icon (text-2xl, bold)
  - Subtitle (text-sm, gray-600)
  - Action buttons on the right
- **Content Area**: Consistent spacing (`px-4 pb-4 space-y-4`)
- **Cards**: Rounded-xl with shadow-lg, padding p-4
- **Grids**: Gap-4 between elements
- **Full-screen design**: No padding/margin on body, uses 100vh
- **Fixed sidebar**: Left sidebar with w-72 (18rem) width
- **Scrollable content**: Main content area with custom scrollbar styling
- **Responsive**: Collapsible sidebar on mobile with overlay
- **Base template**: `admin/base.php` provides consistent layout
- **Content wrapper**: Uses `.content-wrapper` class for scrollable areas
- **Toast notifications**: Built-in toast system for success/error messages via session variables

## Coding Patterns

### Database Connection
- Use `includes/config.php` for database configuration
- PDO connection with UTF-8 charset and exception mode enabled
- Support both development (localhost) and production (AWS RDS) environments
- Use environment variables or configuration files for sensitive data

### Database Connection Pattern (`includes/config.php`)
```php
<?php
$host = 'localhost';      // Change for AWS RDS in production
$dbname = 'ncitad';
$username = 'root';       // Use environment variables in production
$password = '12345678';   // Use environment variables in production

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### Page Structure Pattern (Admin/User Pages)
```php
<?php
session_start();
require_once '../includes/config.php';

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

$page_title = 'Page Title - NCITAD';

// Database queries and business logic here
try {
    $stmt = $pdo->prepare("SELECT * FROM table WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    // Handle error appropriately
}

include 'base.php';
?>

<!-- Page Header -->
<div class="bg-white shadow-lg p-4 mb-4">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-icon text-blue-600"></i>
                Page Title
            </h1>
            <p class="text-gray-600 text-sm mt-0.5">Page description</p>
        </div>
        <!-- Action buttons if needed -->
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
            <i class="bi bi-plus-circle mr-2"></i>Action
        </button>
    </div>
</div>

<!-- Main Content -->
<div class="px-4 pb-4 space-y-4">
    <!-- Content goes here -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <!-- Card content -->
    </div>
</div>

<?php include 'footer.php'; ?>
```

### AJAX Request Pattern (Fetch API)
```javascript
// Form submission with FormData
const form = document.getElementById('myForm');
const formData = new FormData(form);

fetch('endpoint.php', {
    method: 'POST',
    body: formData
})
.then(response => {
    if (!response.ok) throw new Error('Network response was not ok');
    return response.json();
})
.then(data => {
    if (data.success) {
        showToast(data.message, 'success');
        // Update UI or redirect
        if (data.redirect) {
            setTimeout(() => window.location.href = data.redirect, 1500);
        }
    } else {
        showToast(data.message || 'An error occurred', 'error');
    }
})
.catch(error => {
    showToast('Connection error. Please try again.', 'error');
    console.error('Error:', error);
});

// JSON payload example
fetch('endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({ key: 'value' })
})
.then(response => response.json())
.then(data => {
    // Handle response
})
.catch(error => console.error('Error:', error));
```

### Modal Pattern (Dynamic Content Loading)
```javascript
// Open modal and load content via AJAX
function openModal(id) {
    fetch(`get_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalContent').innerHTML = data.html;
                document.getElementById('modal').classList.remove('hidden');
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Failed to load data', 'error');
            console.error('Error:', error);
        });
}

// Close modal
function closeModal() {
    document.getElementById('modal').classList.add('hidden');
}

// Close on overlay click
document.getElementById('modalOverlay').addEventListener('click', closeModal);
```

### Form Validation Pattern (Client-Side)
```javascript
// Form validation before submission
function validateForm(form) {
    const errors = [];
    
    // Example validations
    const email = form.querySelector('#email');
    if (email && !isValidEmail(email.value)) {
        errors.push('Please enter a valid email address');
        email.classList.add('border-red-500');
    }
    
    const password = form.querySelector('#password');
    if (password && password.value.length < 8) {
        errors.push('Password must be at least 8 characters');
        password.classList.add('border-red-500');
    }
    
    if (errors.length > 0) {
        showToast(errors.join('<br>'), 'error');
        return false;
    }
    
    return true;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// Usage
form.addEventListener('submit', (e) => {
    if (!validateForm(form)) {
        e.preventDefault();
    }
});
```

### Prepared Statement Pattern (Secure Database Queries)
```php
// SELECT with single parameter
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// SELECT with multiple parameters
$stmt = $pdo->prepare("SELECT * FROM concerns WHERE user_id = ? AND status = ?");
$stmt->execute([$user_id, $status]);
$concerns = $stmt->fetchAll();

// INSERT with returning last insert ID
$stmt = $pdo->prepare("INSERT INTO concerns (user_id, description, status) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $description, 'Pending']);
$concern_id = $pdo->lastInsertId();

// UPDATE
$stmt = $pdo->prepare("UPDATE concerns SET status = ?, updated_at = NOW() WHERE concern_id = ?");
$stmt->execute(['Ongoing', $concern_id]);
$affected_rows = $stmt->rowCount();

// DELETE
$stmt = $pdo->prepare("DELETE FROM concerns WHERE concern_id = ?");
$stmt->execute([$concern_id]);

// Named parameters (alternative)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
$stmt->execute(['email' => $email, 'username' => $username]);
$user = $stmt->fetch();
```

### Session Notifications
- Set session messages: `$_SESSION['success']`, `$_SESSION['error']`, `$_SESSION['warning']`, `$_SESSION['info']`
- Automatically displayed as toast notifications by `base.php`
- Messages are auto-cleared after display

### Session Message Pattern
```php
// Success message
$_SESSION['success'] = 'Record saved successfully!';
header("Location: page.php");
exit();

// Error message
$_SESSION['error'] = 'Failed to save record. Please try again.';

// Warning message
$_SESSION['warning'] = 'Some fields were not updated.';

// Info message
$_SESSION['info'] = 'Please review the changes before submitting.';
```

### Toast Notification System (JavaScript)
```javascript
// Toast notification function (included in base.php)
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

// Usage examples
showToast('Operation completed successfully!', 'success');
showToast('An error occurred', 'error');
showToast('Please check your input', 'warning');
showToast('New feature available', 'info');
```

### File Includes
- Use `base.php` for common header/navigation/sidebar
- Use `footer.php` for common footer elements and closing tags
- Maintain consistent layout across admin and user sections

### Authentication & Authorization
- Check user sessions on all protected pages
- Redirect unauthorized users to login
- Implement role-based access control (is_admin flag)
- Verify user owns resource before allowing modifications

## AWS Considerations
- Design with scalability in mind
- Use environment variables for AWS-specific configurations
- Consider AWS services: RDS (database), EC2/Elastic Beanstalk (hosting), S3 (file storage)
- Implement proper error logging for production debugging
- Use AWS CloudWatch for monitoring
- Configure proper security groups and IAM roles

## Response Guidelines
- Provide complete, working code snippets
- Include necessary security measures by default
- Suggest Tailwind CSS classes for styling
- Consider both development and production environments
- Explain AWS-related configurations when relevant
- Optimize for performance and security
- Follow the existing project structure and patterns

## Common Tasks
- Creating CRUD operations with prepared statements
- Building responsive forms with Tailwind CSS
- Implementing user authentication and session management
- Creating admin and user dashboards
- Handling file uploads securely
- Writing secure database queries
- Implementing AJAX functionality
- Designing responsive layouts with Tailwind

## Tailwind CSS Component Patterns

### Card Component
```html
<div class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <i class="bi bi-icon text-blue-600"></i>
            Card Title
        </h3>
        <button class="text-gray-400 hover:text-gray-600">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
    </div>
    <div class="space-y-2">
        <!-- Card content -->
    </div>
</div>
```

### Form Input Component
```html
<div class="form-group mb-4">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        <i class="bi bi-icon text-blue-600 mr-1"></i>
        Field Label
    </label>
    <input 
        type="text" 
        name="field_name"
        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
        placeholder="Enter value"
        required
    >
    <p class="text-xs text-gray-500 mt-1">Helper text here</p>
</div>
```

### Status Badge Component
```php
<?php
$status_styles = [
    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
    'Ongoing' => 'bg-orange-100 text-orange-700 border-orange-300',
    'Resolved' => 'bg-green-100 text-green-700 border-green-300',
    'Declined' => 'bg-red-100 text-red-700 border-red-300',
    'Unresolved' => 'bg-gray-100 text-gray-700 border-gray-300'
];
?>
<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?= $status_styles[$status] ?>">
    <?= htmlspecialchars($status) ?>
</span>
```

### Button Variants
```html
<!-- Primary Button -->
<button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold">
    <i class="bi bi-plus-circle mr-2"></i>Primary Action
</button>

<!-- Secondary Button -->
<button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
    <i class="bi bi-x-circle mr-2"></i>Cancel
</button>

<!-- Danger Button -->
<button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
    <i class="bi bi-trash mr-2"></i>Delete
</button>

<!-- Outline Button -->
<button class="px-4 py-2 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition-colors font-semibold">
    <i class="bi bi-eye mr-2"></i>View
</button>
```

### Table Component
```html
<div class="overflow-x-auto bg-white rounded-xl shadow-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                    Column Header
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    Cell Content
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

### Modal Component
```html
<!-- Modal Container (hidden by default) -->
<div id="modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal()"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full p-6 animate-fade-in">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800">Modal Title</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="bi bi-x-lg text-2xl"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div id="modalContent" class="space-y-4">
                <!-- Dynamic content loaded here -->
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
```

### Alert/Notification Component
```html
<div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg flex items-start gap-3">
    <i class="bi bi-info-circle-fill text-blue-500 text-xl flex-shrink-0"></i>
    <div class="flex-1">
        <h4 class="font-semibold text-blue-800 mb-1">Information</h4>
        <p class="text-sm text-blue-700">This is an informational message.</p>
    </div>
    <button class="text-blue-400 hover:text-blue-600">
        <i class="bi bi-x-lg"></i>
    </button>
</div>
```

### Loading State Component
```html
<!-- Loading Spinner -->
<div class="flex items-center justify-center p-8">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
</div>

<!-- Loading Skeleton -->
<div class="animate-pulse space-y-3">
    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
    <div class="h-4 bg-gray-200 rounded"></div>
    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
</div>

<!-- Button Loading State -->
<button disabled class="px-4 py-2 bg-blue-600 text-white rounded-lg opacity-50 cursor-not-allowed flex items-center gap-2">
    <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
    Processing...
</button>
```

## Responsive Design Guidelines

### Breakpoint Strategy
```
- Mobile First: Design for mobile (320px+) first
- sm: 640px  - Small tablets, large phones (landscape)
- md: 768px  - Tablets
- lg: 1024px - Laptops, small desktops
- xl: 1280px - Desktops
- 2xl: 1536px - Large desktops
```

### Responsive Layout Example
```html
<!-- Responsive Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Grid items -->
</div>

<!-- Responsive Flex -->
<div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
    <!-- Flex items -->
</div>

<!-- Responsive Text -->
<h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold">
    Responsive Heading
</h1>

<!-- Responsive Padding -->
<div class="p-4 sm:p-6 md:p-8 lg:p-10">
    Content with responsive padding
</div>

<!-- Show/Hide on Breakpoints -->
<div class="hidden lg:block">Visible only on large screens</div>
<div class="block lg:hidden">Visible only on small screens</div>
```

## Performance Optimization

### Database Query Optimization
```php
<?php
// Use indexes on frequently queried columns
// CREATE INDEX idx_user_id ON concerns(user_id);
// CREATE INDEX idx_status ON concerns(status);
// CREATE INDEX idx_created_at ON concerns(created_at);

// Limit SELECT columns (avoid SELECT *)
$stmt = $pdo->prepare("SELECT concern_id, description, status FROM concerns WHERE user_id = ?");

// Use LIMIT for large datasets
$stmt = $pdo->prepare("SELECT * FROM concerns ORDER BY created_at DESC LIMIT 100");

// Use COUNT(*) efficiently
$stmt = $pdo->query("SELECT COUNT(*) as total FROM concerns WHERE status = 'Pending'");
$count = $stmt->fetchColumn();

// Avoid N+1 queries - use JOINs
// Bad: Loop through concerns and query users separately
// Good: Single query with JOIN
$stmt = $pdo->query("
    SELECT c.*, u.faculty_name, u.email
    FROM concerns c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.status = 'Pending'
");
?>
```

### Frontend Performance
```javascript
// Debounce search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Usage
const searchInput = document.getElementById('search');
searchInput.addEventListener('input', debounce((e) => {
    performSearch(e.target.value);
}, 300));

// Lazy loading images
const images = document.querySelectorAll('img[data-src]');
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
        }
    });
});

images.forEach(img => imageObserver.observe(img));
```

## AWS Deployment Considerations

### Environment Variables (Production)
```php
<?php
// Use environment variables for sensitive data
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'ncitad';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

// Email configuration
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
define('SYSTEM_URL', getenv('SYSTEM_URL') ?: 'http://localhost/ncitad');

// Security settings
define('IS_PRODUCTION', getenv('APP_ENV') === 'production');

if (IS_PRODUCTION) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/log/php/error.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
?>
```

### AWS RDS Connection
```php
<?php
// AWS RDS MySQL connection example
$host = 'ncitad-db.xxxxx.us-east-1.rds.amazonaws.com'; // RDS endpoint
$port = '3306';
$dbname = 'ncitad';
$username = 'admin'; // RDS master username
$password = 'secure_password'; // Use environment variables

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false // Connection pooling handled by RDS
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to database. Please try again later.");
}
?>
```

### Security Headers (Production)
```php
<?php
// Add security headers (in config.php or .htaccess)
if (IS_PRODUCTION) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");
}
?>
```

## Code Review Checklist

### Security Checklist
- [ ] All database queries use prepared statements
- [ ] User input is validated and sanitized
- [ ] Output is properly escaped (htmlspecialchars)
- [ ] CSRF tokens implemented on forms
- [ ] Session security configured (httponly, secure)
- [ ] Passwords hashed with password_hash()
- [ ] File uploads validated (type, size, extension)
- [ ] Authentication checks on all protected pages
- [ ] Error messages don't expose sensitive information
- [ ] HTTPS enforced in production

### Performance Checklist
- [ ] Database queries optimized (indexes, JOINs)
- [ ] SELECT only required columns
- [ ] Pagination implemented for large datasets
- [ ] Images optimized and lazy-loaded
- [ ] AJAX requests use loading states
- [ ] CSS/JS minified in production
- [ ] Database connection pooling configured

### Code Quality Checklist
- [ ] Code follows consistent style guidelines
- [ ] Functions are single-purpose and well-named
- [ ] Comments explain complex logic
- [ ] Error handling implemented
- [ ] No hardcoded credentials
- [ ] Responsive design tested on multiple devices
- [ ] Cross-browser compatibility verified
- [ ] Accessibility standards followed (ARIA labels)

### Testing Checklist
- [ ] Forms validated client and server-side
- [ ] Error scenarios handled gracefully
- [ ] Email notifications tested
- [ ] Authentication flows tested
- [ ] Authorization checks verified
- [ ] Database transactions tested
- [ ] Edge cases considered
- [ ] Mobile responsiveness verified

## Development Workflow Best Practices

### Git Workflow
1. Create feature branches from main: `git checkout -b feature/ticket-system`
2. Commit regularly with descriptive messages
3. Test locally before pushing
4. Merge to main after testing
5. Tag releases: `git tag -a v1.0.0 -m "Release version 1.0.0"`

### Local Development Setup
1. Start XAMPP (Apache + MySQL)
2. Navigate to `http://localhost/ncitad`
3. Use MySQL Workbench for database management
4. Check PHP error logs in XAMPP control panel
5. Test email functionality with Gmail SMTP

### Code Organization Principles
- Keep business logic separate from presentation
- Reuse common components (base.php, footer.php)
- Use consistent naming conventions throughout
- Document complex functions and queries
- Keep functions small and focused
- Avoid code duplication (DRY principle)

### Debugging Tips
```php
<?php
// Development mode debugging
if (!IS_PRODUCTION) {
    // Pretty print arrays/objects
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    // Var dump with die
    var_dump($variable);
    die();
    
    // Log to error log
    error_log('Debug info: ' . print_r($data, true));
}

// PDO query debugging
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log("SQL Error: " . $e->getMessage());
    error_log("SQL Query: " . $sql);
    error_log("Parameters: " . print_r($params, true));
}
?>
```

## Quick Reference

### Common Bootstrap Icons
```
- bi-person-circle (user profile)
- bi-envelope (email)
- bi-lock (security/password)
- bi-list-task (tasks/tickets)
- bi-speedometer2 (dashboard)
- bi-gear-fill (settings)
- bi-plus-circle (add)
- bi-trash (delete)
- bi-pencil (edit)
- bi-eye (view)
- bi-check-circle (success)
- bi-x-circle (error/cancel)
- bi-exclamation-triangle (warning)
- bi-info-circle (information)
- bi-search (search)
- bi-filter (filter)
- bi-download (download)
- bi-upload (upload)
- bi-box-arrow-right (logout)
- bi-arrow-left/right (navigation)
```

### Tailwind CSS Cheat Sheet
```
Spacing: p-4 (padding), m-4 (margin), space-y-4 (vertical gap)
Colors: bg-blue-600, text-gray-800, border-red-500
Sizing: w-full, h-screen, max-w-2xl, min-h-screen
Flex: flex, flex-col, items-center, justify-between, gap-4
Grid: grid, grid-cols-3, gap-4
Typography: text-xl, font-bold, text-center, uppercase
Borders: border, border-2, rounded-lg, rounded-xl
Shadows: shadow-md, shadow-lg, shadow-xl
Transitions: transition-all, duration-200, hover:bg-blue-700
Display: hidden, block, inline-block, flex, grid
Position: fixed, absolute, relative, sticky, top-0, left-0
Z-index: z-10, z-20, z-30, z-40, z-50
```

### HTTP Status Codes
```
200 - OK (successful request)
201 - Created (resource created)
400 - Bad Request (client error)
401 - Unauthorized (authentication required)
403 - Forbidden (insufficient permissions)
404 - Not Found (resource not found)
422 - Unprocessable Entity (validation error)
500 - Internal Server Error (server error)
503 - Service Unavailable (server down)
```

### MySQL Data Types
```
INT - Integer numbers
VARCHAR(n) - Variable-length string (up to n characters)
TEXT - Long text content
DATETIME - Date and time (YYYY-MM-DD HH:MM:SS)
TIMESTAMP - Automatic timestamp
ENUM('val1', 'val2') - Predefined values
TINYINT(1) - Boolean (0 or 1)
DECIMAL(10,2) - Decimal numbers (10 digits, 2 decimal places)
```

## Project-Specific Notes

### Default Admin Credentials (Development)
- Username: admin
- Password: admin123
- **Note**: Change in production!

### Email Testing
- Use Gmail SMTP for development
- Requires App Password (not regular password)
- Configure in `includes/email_config.php`
- Set `EMAIL_ENABLED = false` to disable all emails during testing

### Database Backup
```bash
# Export database
mysqldump -u root -p ncitad > backup_$(date +%Y%m%d).sql

# Import database
mysql -u root -p ncitad < backup_20251101.sql
```

### Common Issues & Solutions

**Issue**: Database connection failed
- Solution: Check XAMPP MySQL is running, verify credentials in config.php

**Issue**: Email not sending
- Solution: Check email_config.php, verify SMTP credentials, check Gmail App Password

**Issue**: Session not persisting
- Solution: Ensure session_start() at top of file, check session.save_path in php.ini

**Issue**: Tailwind styles not working
- Solution: Check CDN link in base.php, clear browser cache

**Issue**: File upload fails
- Solution: Check PHP upload_max_filesize and post_max_size in php.ini

**Issue**: Redirect loops
- Solution: Check authentication logic, ensure proper exit() after header redirects

## Response Format Guidelines

When providing code solutions:
1. **Explain the approach** before providing code
2. **Include error handling** in all database operations
3. **Add security measures** by default (validation, sanitization, CSRF)
4. **Make it responsive** using Tailwind breakpoints
5. **Follow existing patterns** from the codebase
6. **Test the code** mentally before suggesting
7. **Provide complete examples** not just snippets
8. **Include comments** for complex logic
9. **Consider edge cases** and error scenarios
10. **Optimize for production** (performance, security)

## Additional Resources

### Documentation Links
- PHP Manual: https://www.php.net/manual/
- PDO Documentation: https://www.php.net/manual/en/book.pdo.php
- Tailwind CSS: https://tailwindcss.com/docs
- Bootstrap Icons: https://icons.getbootstrap.com/
- PHPMailer: https://github.com/PHPMailer/PHPMailer
- MySQL Reference: https://dev.mysql.com/doc/
- OWASP Security: https://owasp.org/www-project-top-ten/

### Project Contacts & Support
- Project Repository: [Add Git repository URL]
- Issue Tracker: [Add issue tracking URL]
- Documentation: See AWS_DEPLOYMENT_GUIDE.md and README.md
- Email: support@ncitad.edu (placeholder)

---

**Last Updated**: November 2, 2025
**Version**: 2.0
**Maintained By**: NCITAD Development Team

## Testing & Debugging
- Test on XAMPP before suggesting deployment
- Verify MySQL queries in MySQL Workbench
- Test responsive designs across breakpoints
- Validate forms both client-side and server-side
- Check for SQL injection vulnerabilities
- Test authentication and authorization flows

## Advanced Patterns & Best Practices

### Error Handling Pattern
```php
<?php
// Database operations with comprehensive error handling
try {
    $pdo->beginTransaction();
    
    // First operation
    $stmt = $pdo->prepare("INSERT INTO concerns (user_id, description) VALUES (?, ?)");
    $stmt->execute([$user_id, $description]);
    $concern_id = $pdo->lastInsertId();
    
    // Second operation
    if (!empty($device_ids)) {
        $stmt = $pdo->prepare("INSERT INTO concern_devices (concern_id, device_id) VALUES (?, ?)");
        foreach ($device_ids as $device_id) {
            $stmt->execute([$concern_id, $device_id]);
        }
    }
    
    $pdo->commit();
    $_SESSION['success'] = 'Concern submitted successfully!';
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Failed to submit concern. Please try again.';
    error_log("Database error: " . $e->getMessage());
    // Don't expose database errors to users
}
?>
```

### Input Validation & Sanitization
```php
<?php
// Server-side validation example
function validateConcernInput($description, $device_ids) {
    $errors = [];
    
    // Validate description
    if (empty(trim($description))) {
        $errors[] = 'Description is required';
    } elseif (strlen($description) < 10) {
        $errors[] = 'Description must be at least 10 characters';
    } elseif (strlen($description) > 1000) {
        $errors[] = 'Description must not exceed 1000 characters';
    }
    
    // Validate device IDs
    if (empty($device_ids) || !is_array($device_ids)) {
        $errors[] = 'Please select at least one device';
    } else {
        foreach ($device_ids as $device_id) {
            if (!is_numeric($device_id) || $device_id <= 0) {
                $errors[] = 'Invalid device selection';
                break;
            }
        }
    }
    
    return $errors;
}

// Usage
$errors = validateConcernInput($_POST['description'] ?? '', $_POST['device_ids'] ?? []);
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header("Location: form.php");
    exit();
}
?>
```

### CSRF Protection Pattern
```php
<?php
// Generate CSRF token (in form page)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!-- Include in form -->
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<?php
// Validate CSRF token (in form handler)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid form submission. Please try again.';
    header("Location: form.php");
    exit();
}

// Optional: Regenerate token after successful submission
unset($_SESSION['csrf_token']);
?>
```

### Password Recovery Pattern
```php
<?php
// Generate secure reset token
$reset_token = bin2hex(random_bytes(32));
$reset_token_hash = hash('sha256', $reset_token);
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store in database
$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
$stmt->execute([$reset_token_hash, $expires, $email]);

// Send email with reset link
$reset_link = SYSTEM_URL . "/reset-password.php?token=" . $reset_token;
sendEmail($email, $user['faculty_name'], 'Password Reset Request', 
    "Click here to reset your password: <a href='$reset_link'>$reset_link</a>");

// Validate reset token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([hash('sha256', $_GET['token'] ?? '')]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'Invalid or expired reset token';
    header("Location: forgot-password.php");
    exit();
}
?>
```

### File Upload Security Pattern
```php
<?php
// Secure file upload handling
function handleFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed with error code: ' . $file['error']];
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $upload_path = '../assets/uploads/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'error' => 'Failed to save file'];
    }
    
    return ['success' => true, 'filename' => $filename, 'path' => $upload_path];
}
?>
```

### Email Sending Pattern
```php
<?php
// Using email helper function
require_once '../includes/email_helper.php';

// Simple email
$result = sendEmail(
    'recipient@example.com',
    'Recipient Name',
    'Email Subject',
    '<h1>Email Body</h1><p>HTML content here</p>'
);

if ($result['success']) {
    $_SESSION['success'] = 'Email sent successfully';
} else {
    $_SESSION['error'] = 'Failed to send email: ' . $result['error'];
    error_log('Email error: ' . $result['error']);
}

// Email template for ticket notification
function sendTicketNotification($user_email, $user_name, $concern_id, $status, $message) {
    $subject = "Ticket #$concern_id - Status Update: $status";
    
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, #1e3c72, #2a5298); padding: 20px; text-align: center;'>
            <h2 style='color: white; margin: 0;'>NCITAD Ticket System</h2>
        </div>
        <div style='padding: 30px; background: #f9f9f9;'>
            <h3>Hello, $user_name</h3>
            <p>Your ticket <strong>#$concern_id</strong> has been updated.</p>
            <p><strong>New Status:</strong> <span style='color: #2a5298;'>$status</span></p>
            <p>$message</p>
            <div style='margin-top: 30px; padding: 15px; background: white; border-left: 4px solid #2a5298;'>
                <p style='margin: 0;'><strong>Note:</strong> This is an automated message. Please do not reply to this email.</p>
            </div>
        </div>
        <div style='padding: 20px; text-align: center; color: #666; font-size: 12px;'>
            <p>&copy; " . date('Y') . " Norzagaray College IT Assistance Desk</p>
        </div>
    </div>
    ";
    
    return sendEmail($user_email, $user_name, $subject, $body);
}
?>
```

### Pagination Pattern
```php
<?php
// Pagination helper
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM concerns");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get paginated results
$stmt = $pdo->prepare("SELECT * FROM concerns ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$concerns = $stmt->fetchAll();
?>

<!-- Pagination UI -->
<div class="flex justify-center items-center gap-2 mt-6">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="bi bi-chevron-left"></i> Previous
        </a>
    <?php endif; ?>
    
    <span class="px-4 py-2 bg-gray-100 rounded-lg">
        Page <?= $page ?> of <?= $total_pages ?>
    </span>
    
    <?php if ($page < $total_pages): ?>
        <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Next <i class="bi bi-chevron-right"></i>
        </a>
    <?php endif; ?>
</div>
```

### Search & Filter Pattern
```php
<?php
// Build dynamic query based on filters
$where_clauses = [];
$params = [];

if (!empty($_GET['status'])) {
    $where_clauses[] = "status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $where_clauses[] = "(description LIKE ? OR c.concern_id LIKE ?)";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($_GET['date_from'])) {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $_GET['date_to'];
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "SELECT c.*, u.faculty_name 
        FROM concerns c 
        JOIN users u ON c.user_id = u.user_id 
        $where_sql 
        ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
?>
```

### JSON API Response Pattern
```php
<?php
// Consistent JSON response format
header('Content-Type: application/json');

function jsonResponse($success, $message = '', $data = null, $redirect = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($redirect !== null) {
        $response['redirect'] = $redirect;
    }
    
    echo json_encode($response);
    exit();
}

// Usage examples
jsonResponse(true, 'Record saved successfully', ['id' => 123]);
jsonResponse(false, 'Validation failed', null);
jsonResponse(true, 'Login successful', null, 'dashboard.php');

// Error handling for AJAX requests
try {
    // Database operation
    $stmt = $pdo->prepare("INSERT INTO table (column) VALUES (?)");
    $stmt->execute([$value]);
    
    jsonResponse(true, 'Operation successful', ['id' => $pdo->lastInsertId()]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred. Please try again.');
}
?>
```

### Session Security Hardening
```php
<?php
// Implement in config.php or at application start
// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Regenerate session ID on login
function secureLogin($user_id, $username, $is_admin) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['is_admin'] = $is_admin;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}

// Session validation
function validateSession() {
    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    
    // Update last activity time
    $_SESSION['login_time'] = time();
    
    // Validate IP address (optional - may cause issues with dynamic IPs)
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_unset();
        session_destroy();
        header("Location: login.php?security=1");
        exit();
    }
}
?>
```
