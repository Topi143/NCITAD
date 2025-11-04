# NCITAD - System Architecture & Algorithms Documentation

> **Norzagaray College IT Assistance Desk**  
> Comprehensive guide to algorithms, system processes, and panel details  
> Last Updated: November 5, 2025

---

## 📑 Table of Contents

1. [Authentication & Security Algorithms](#authentication--security-algorithms)
2. [Database Query Algorithms](#database-query-algorithms)
3. [Ticket Workflow Algorithm](#ticket-workflow-algorithm)
4. [Email Notification System](#email-notification-system)
5. [Data Validation Algorithms](#data-validation-algorithms)
6. [Search Algorithms](#search-algorithms)
7. [Admin Panel Details](#admin-panel-details)
8. [User Panel Details](#user-panel-details)
9. [Security Summary](#security-summary)

---

## 🔐 Authentication & Security Algorithms

### 1. Password Hashing - bcrypt Algorithm

**Algorithm**: `PASSWORD_DEFAULT` (bcrypt with automatic salt generation)

**Implementation Files**:
- `login.php`
- `admin/settings.php`
- `user/settings.php`
- `admin/adduserandadmin.php`

**Process Flow**:
```php
// Password Hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Password Verification
if (password_verify($input_password, $stored_hash)) {
    // Authentication successful
    // Create session and redirect
}
```

**Security Features**:
- ✅ Automatic salt generation
- ✅ Adaptive cost factor (automatically adjusts with hardware improvements)
- ✅ One-way hashing (cannot be reversed)
- ✅ Industry-standard bcrypt algorithm

---

### 2. Session Management Algorithm

**Type**: Native PHP Sessions with security hardening

**Session Security Configuration**:
```php
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access
ini_set('session.use_only_cookies', 1); // Only use cookies for sessions
ini_set('session.cookie_secure', 0);    // Set to 1 in production (HTTPS)
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

**Session Variables Stored**:
| Variable | Type | Purpose |
|----------|------|---------|
| `user_id` | Integer | User's database ID |
| `username` | String | User's login username |
| `faculty_name` | String | Full name of user |
| `email` | String | User's email address |
| `is_admin` | Boolean | Admin role flag (1 = Admin, 0 = User) |
| `first_login` | Boolean | First login status flag |
| `login_time` | Timestamp | Login timestamp for timeout |

**Security Features**:
- ✅ **Session Regeneration**: New session ID on login
- ✅ **Session Timeout**: 30-minute inactivity timeout
- ✅ **IP Validation**: Optional IP address checking
- ✅ **User Agent Tracking**: Detects session hijacking attempts

**Authentication Flow**:
```
┌─────────────────┐
│  User Login     │
│  (POST request) │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ Validate Credentials    │
│ - Check username/email  │
│ - Verify password hash  │
└────────┬────────────────┘
         │
    ┌────┴────┐
    │ Valid?  │
    └────┬────┘
         │
    ┌────┴─────────┐
    │              │
    ▼              ▼
┌───────┐     ┌─────────┐
│ FAIL  │     │ SUCCESS │
└───┬───┘     └────┬────┘
    │              │
    ▼              ▼
Show Error    Create Session
              Regenerate ID
              Set Variables
              Redirect to Dashboard
```

---

### 3. CSRF Protection - Token-Based

**Algorithm**: Cryptographically secure random token generation

**Implementation**:
```php
// Token Generation (in forms)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Token Validation (on form submission)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Invalid form submission";
    exit();
}
```

**Token Characteristics**:
- **Length**: 64 characters (32 bytes hex-encoded)
- **Entropy**: 256 bits of randomness
- **Lifetime**: Per-session (regenerated on login)
- **Storage**: Session variable only

**Protected Forms**:
- ✅ User/Admin profile updates
- ✅ Password changes
- ✅ Concern submissions
- ✅ Ticket status updates
- ✅ Facility/Device management
- ✅ User account creation

---

### 4. Password Reset Token Algorithm

**Purpose**: Secure password recovery without exposing user passwords

**Token Generation Process**:
```php
// 1. Generate random token
$token = bin2hex(random_bytes(32)); // 64-char hex string

// 2. Set expiration (1 hour)
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 3. Store in database
INSERT INTO password_resets (user_id, token, expiry) 
VALUES (?, ?, ?)

// 4. Send token via email
$reset_link = "http://example.com/reset-password.php?token=" . $token;
sendEmail($user_email, $subject, $reset_link);
```

**Verification Process**:
```php
// 1. Receive token from URL
$token = $_GET['token'];

// 2. Check database
SELECT * FROM password_resets 
WHERE token = ? AND expiry > NOW()

// 3. Validate and allow password reset
if ($valid_token) {
    // Show password reset form
    // Update password
    // Delete used token
}
```

**Security Features**:
- ✅ **One-time use**: Token deleted after successful reset
- ✅ **Time-limited**: Expires after 1 hour
- ✅ **Secure random**: Uses `random_bytes()` for cryptographic security
- ✅ **Email verification**: Sent only to registered email address

---

## 💾 Database Query Algorithms

### 1. Prepared Statements - SQL Injection Prevention

**Driver**: PDO (PHP Data Objects)

**Connection Configuration**:
```php
$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username, 
    $password
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

**Query Methods**:

#### Positional Parameters (`?`)
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
```

#### Named Parameters (`:param`)
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
$stmt->execute(['email' => $email, 'username' => $username]);
$user = $stmt->fetch();
```

**Transaction Support** (ACID Compliance):
```php
try {
    $pdo->beginTransaction();
    
    // Multiple operations
    $stmt1 = $pdo->prepare("INSERT INTO concerns (...) VALUES (...)");
    $stmt1->execute([...]);
    
    $stmt2 = $pdo->prepare("INSERT INTO concern_devices (...) VALUES (...)");
    $stmt2->execute([...]);
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    // Handle error
}
```

---

### 2. Search & Filter Algorithm

**Type**: Dynamic query building with parameterized conditions

**Implementation** (`admin/concerns.php`):
```php
$filter_conditions = [];
$filter_params = [];

// Facility filter
if ($filter_facility > 0) {
    $filter_conditions[] = "fac.facility_id = ?";
    $filter_params[] = $filter_facility;
}

// Device filter
if ($filter_device > 0) {
    $filter_conditions[] = "d.device_id = ?";
    $filter_params[] = $filter_device;
}

// Search query (multiple fields)
if (!empty($search_query)) {
    $filter_conditions[] = "(c.description LIKE ? OR u.faculty_name LIKE ? OR u.email LIKE ?)";
    $search_param = "%{$search_query}%";
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
    $filter_params[] = $search_param;
}

// Build WHERE clause
$filter_sql = !empty($filter_conditions) ? " AND " . implode(" AND ", $filter_conditions) : "";

// Execute query
$sql = "SELECT ... FROM concerns c WHERE c.status = 'Pending' $filter_sql";
$stmt = $pdo->prepare($sql);
$stmt->execute($filter_params);
```

**Features**:
- ✅ Multi-field search (description, faculty name, email)
- ✅ Facility and device filtering
- ✅ Combined filters (additive)
- ✅ SQL injection safe (parameterized)
- ✅ Maintains filter state across pagination

---

### 3. Pagination Algorithm

**Type**: LIMIT/OFFSET-based pagination

**Formula**:
```php
$items_per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;

// Calculate total pages
$total_records = $pdo->query("SELECT COUNT(*) FROM concerns")->fetchColumn();
$total_pages = ceil($total_records / $items_per_page);

// Fetch paginated results
$stmt = $pdo->prepare("
    SELECT * FROM concerns 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$items_per_page, $offset]);
```

**Pagination Display Logic**:
```php
// Show 5 page numbers: current ± 2
for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++) {
    // Display page number link
}
```

**Features**:
- ✅ Separate pagination for Pending and Ongoing tickets
- ✅ Maintains filter state across pages
- ✅ Shows current page context (X of Y pages)
- ✅ Previous/Next navigation
- ✅ Direct page number links

---

## 🎫 Ticket Workflow Algorithm

### Status Transition State Machine

```
┌──────────────────────────────────────────────────────┐
│                  USER SUBMITS CONCERN                 │
│                   Status: PENDING                     │
│  - Creates record in concerns table                  │
│  - Links devices via concern_devices junction table  │
└───────────────────────┬──────────────────────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │      ADMIN REVIEWS TICKET     │
        │   (From admin/concerns.php)   │
        └───────────────┬───────────────┘
                        │
            ┌───────────┴────────────┐
            │                        │
            ▼                        ▼
    ┌───────────────┐        ┌──────────────┐
    │    ACCEPT     │        │   DECLINE    │
    │ Status: ONGOING│       │Status: DECLINED│
    └───────┬───────┘        └──────┬───────┘
            │                       │
            │                       ▼
            │              ┌─────────────────────┐
            │              │   Archive Process   │
            │              │ 1. INSERT to        │
            │              │    concern_history  │
            │              │ 2. Store reason     │
            │              │ 3. DELETE from      │
            │              │    concerns table   │
            │              └─────────────────────┘
            │
            ▼
    ┌──────────────────────┐
    │  ADMIN WORKS ON IT   │
    │   Status: ONGOING    │
    └──────────┬───────────┘
               │
    ┌──────────┴──────────────────┐
    │                             │
    ▼                             ▼
┌────────────┐            ┌──────────────┐
│  RESOLVED  │            │  UNRESOLVED  │
│Status:     │            │Status:       │
│RESOLVED    │            │UNRESOLVED    │
└─────┬──────┘            └──────┬───────┘
      │                          │
      └──────────┬───────────────┘
                 │
                 ▼
        ┌────────────────────┐
        │  Archive Process   │
        │ 1. INSERT to       │
        │    concern_history │
        │ 2. Store feedback  │
        │ 3. DELETE from     │
        │    concerns table  │
        └────────────────────┘
```

### Archival Algorithm Details

**Purpose**: Move completed/declined tickets to history table while preserving data

**Trigger Events**:
1. Admin declines pending ticket
2. Admin marks ongoing ticket as resolved
3. Admin marks ongoing ticket as unresolved

**Process Flow**:
```php
try {
    $pdo->beginTransaction();
    
    // Step 1: Copy to concern_history with all details
    $stmt = $pdo->prepare("
        INSERT INTO concern_history (
            concern_id, user_id, description, status,
            created_at, updated_at, resolution_feedback,
            faculty_name, email, devices
        )
        SELECT 
            c.concern_id,
            c.user_id,
            c.description,
            'Resolved',  -- New status
            c.created_at,
            NOW(),
            ?,  -- Resolution feedback
            u.faculty_name,
            u.email,
            CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT('device_name', d.device_name, 'facility_name', f.facility_name)
            ), ']')
        FROM concerns c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN concern_devices cd ON c.concern_id = cd.concern_id
        LEFT JOIN devices d ON cd.device_id = d.device_id
        LEFT JOIN facilities f ON d.facility_id = f.facility_id
        WHERE c.concern_id = ?
        GROUP BY c.concern_id
    ");
    $stmt->execute([$feedback, $concern_id]);
    
    // Step 2: Delete from active concerns
    $stmt = $pdo->prepare("DELETE FROM concerns WHERE concern_id = ?");
    $stmt->execute([$concern_id]);
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    // Handle error
}
```

**Data Preservation**:
- ✅ Original concern ID (for tracking)
- ✅ Original creation timestamp
- ✅ Archive timestamp (when moved to history)
- ✅ Resolution feedback/reason
- ✅ Associated devices (JSON format)
- ✅ User information snapshot

---

## 📧 Email Notification System

### Email Sending Algorithm

**Primary Method**: PHPMailer with SMTP (Gmail default)  
**Fallback**: PHP `mail()` function

**Configuration** (`includes/email_config.php`):
```php
define('EMAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'app-password');
define('EMAIL_ENABLED', true);
```

### SMTP Authentication Protocol

**Connection Flow**:
```
┌──────────────────┐
│ Connect to       │
│ smtp.gmail.com   │
│ Port: 587 (TLS)  │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ STARTTLS         │
│ (Upgrade to TLS) │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ AUTH LOGIN       │
│ Base64(username) │
│ Base64(password) │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│ Send Email       │
│ MAIL FROM        │
│ RCPT TO          │
│ DATA             │
└──────────────────┘
```

**Implementation** (`includes/email_helper.php`):
```php
function sendEmailSMTP($to, $toName, $subject, $htmlBody, $textBody = '') {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = SMTP_AUTH;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION;
    $mail->Port = SMTP_PORT;
    $mail->Timeout = 10;
    
    // Recipients
    $mail->setFrom(SMTP_USERNAME, EMAIL_FROM_NAME);
    $mail->addAddress($to, $toName);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody ?: strip_tags($htmlBody);
    
    $mail->send();
    return true;
}
```

### Email Templates

#### 1. New User Credentials Email
**Trigger**: Admin creates new user account

**Content**:
- Welcome message
- Login credentials (username, temporary password)
- Login URL
- Security warnings
- Call-to-action button

**Template Features**:
- Responsive HTML design
- Inline CSS for email client compatibility
- Plain text alternative
- Professional branding

#### 2. Password Reset Email
**Trigger**: User requests password reset

**Content**:
- Personalized greeting
- Password reset link with secure token
- Expiration warning (1 hour)
- Security information
- Alternative copy-paste link

#### 3. Ticket Status Update Email
**Trigger**: Admin changes ticket status

**Content**:
- Ticket ID reference
- New status notification
- Resolution details (if applicable)
- Contact information

---

## ✅ Data Validation Algorithms

### Client-Side Validation (JavaScript)

**Real-Time Character Counting**:
```javascript
const descriptionField = document.getElementById('description');
const charCount = document.getElementById('charCount');

descriptionField.addEventListener('input', function() {
    charCount.textContent = this.value.length;
    
    // Visual feedback
    if (this.value.length < 10) {
        this.classList.add('border-red-500');
    } else {
        this.classList.remove('border-red-500');
    }
});
```

**Form Validation**:
```javascript
concernForm.addEventListener('submit', function(e) {
    const selectedDevices = Array.from(deviceCheckboxes).filter(cb => cb.checked);
    
    // Check device selection
    if (selectedDevices.length === 0) {
        e.preventDefault();
        showToast('Please select at least one device', 'error');
        return false;
    }
    
    // Check description length
    if (descriptionField.value.trim().length < 10) {
        e.preventDefault();
        showToast('Description must be at least 10 characters', 'error');
        descriptionField.focus();
        return false;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Submitting...';
});
```

**Email Validation**:
```javascript
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}
```

**Password Strength Validation**:
```javascript
function validatePassword(password) {
    const errors = [];
    
    if (password.length < 6) {
        errors.push('Password must be at least 6 characters');
    }
    
    return errors;
}
```

### Server-Side Validation (PHP)

**Input Sanitization**:
```php
// Trim whitespace
$username = trim($_POST['username']);

// Remove HTML/PHP tags
$description = strip_tags($_POST['description']);

// HTML entity encoding (prevent XSS)
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

**Email Validation**:
```php
$email = trim($_POST['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new Exception('Invalid email address');
}
```

**Data Type Validation**:
```php
$device_id = $_POST['device_id'];

if (!is_numeric($device_id) || $device_id <= 0) {
    throw new Exception('Invalid device ID');
}

$device_id = intval($device_id); // Convert to integer
```

**Length Validation**:
```php
$description = trim($_POST['description']);

if (strlen($description) < 10) {
    throw new Exception('Description must be at least 10 characters');
}

if (strlen($description) > 1000) {
    throw new Exception('Description too long (max 1000 characters)');
}
```

**Duplicate Checking**:
```php
// Check if username exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute([$username]);

if ($stmt->fetchColumn() > 0) {
    throw new Exception('Username already exists');
}
```

---

## 🔍 Search Algorithms

### Real-Time Search (JavaScript)

**Type**: Debounced client-side filtering  
**Delay**: 800ms after last keystroke

**Implementation**:
```javascript
let filterTimeout = null;

searchInput.addEventListener('input', function() {
    clearTimeout(filterTimeout);
    
    filterTimeout = setTimeout(() => {
        applyFilters();
    }, 800);
});

// Instant search on Enter key
searchInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        clearTimeout(filterTimeout);
        applyFilters();
    }
});
```

**Live Filtering** (Facility/Device Search):
```javascript
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
```

### Database Full-Text Search

**Type**: LIKE-based pattern matching

**Query Pattern**:
```sql
SELECT * FROM concerns 
WHERE description LIKE '%search_term%'
   OR faculty_name LIKE '%search_term%'
   OR email LIKE '%search_term%'
```

**Optimization**:
- Indexes on frequently searched columns
- FULLTEXT index for large text fields (future enhancement)

---

## 🎛️ Admin Panel Details

### 1. Dashboard (`admin/dashboard.php`)

**Purpose**: System overview and real-time statistics

#### Key Metrics Cards

| Metric | Color Scheme | Data Source |
|--------|--------------|-------------|
| **Total Concerns** | Blue Gradient | `COUNT(*)` from concerns |
| **Pending** | Yellow Gradient | `SUM(CASE WHEN status = 'Pending')` |
| **Ongoing** | Orange Gradient | `SUM(CASE WHEN status = 'Ongoing')` |
| **Resolved** | Green Gradient | `SUM(CASE WHEN status = 'Resolved')` |

#### Secondary Metrics

| Metric | Icon | Query |
|--------|------|-------|
| Total Users | 👥 | `COUNT(*) WHERE is_admin = 0` |
| Administrators | 🛡️ | `COUNT(*) WHERE is_admin = 1` |
| Facilities | 🏢 | `COUNT(*) FROM facilities` |
| Devices | 💻 | `COUNT(*) FROM devices` |

#### Activity Timeline

```php
// Today's concerns
SELECT COUNT(*) FROM concerns WHERE DATE(created_at) = CURDATE()

// This week's concerns
SELECT COUNT(*) FROM concerns WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)

// This month's concerns
SELECT COUNT(*) FROM concerns WHERE MONTH(created_at) = MONTH(CURDATE())
```

#### Top 5 Facilities by Concerns

```sql
SELECT f.facility_name, COUNT(DISTINCT c.concern_id) as concern_count
FROM facilities f
LEFT JOIN devices d ON f.facility_id = d.facility_id
LEFT JOIN concern_devices cd ON d.device_id = cd.device_id
LEFT JOIN concerns c ON cd.concern_id = c.concern_id
GROUP BY f.facility_id
ORDER BY concern_count DESC
LIMIT 5
```

#### Recent 5 Concerns

```sql
SELECT c.*, u.faculty_name, u.email,
       (SELECT COUNT(*) FROM concern_devices cd WHERE cd.concern_id = c.concern_id) as device_count
FROM concerns c
JOIN users u ON c.user_id = u.user_id
ORDER BY c.created_at DESC
LIMIT 5
```

#### Quick Action Buttons

1. **View Concerns** → `admin/concerns.php`
2. **Add User** → `admin/adduserandadmin.php`
3. **Manage Devices** → `admin/facilities_devices.php`
4. **View History** → `admin/history.php`

---

### 2. Concerns Panel (`admin/concerns.php`)

**Purpose**: Active ticket management with Kanban-style interface

#### Layout Structure

```
┌─────────────────────────────────────────────────────┐
│               HEADER & FILTERS                       │
│  - Search bar (description, faculty, email)         │
│  - Facility dropdown filter                         │
│  - Device dropdown filter (auto-filtered)           │
│  - Clear filters button                             │
└─────────────────────────────────────────────────────┘

┌────────────────────┬────────────────────────────────┐
│  PENDING COLUMN    │     ONGOING COLUMN             │
│  (Gray Theme)      │     (Yellow Theme)             │
├────────────────────┼────────────────────────────────┤
│ [Ticket Card]      │  [Ticket Card]                 │
│ #0001              │  #0003                         │
│ Description...     │  Description...                │
│ 👤 Faculty Name    │  👤 Faculty Name               │
│ 💻 Device List     │  💻 Device List                │
│ ┌────────┬──────┐  │  ┌─────────┬──────────┐        │
│ │ Accept │Decline│ │  │ Resolved │Unresolved│       │
│ └────────┴──────┘  │  └─────────┴──────────┘        │
├────────────────────┼────────────────────────────────┤
│ [Ticket Card]      │  [Ticket Card]                 │
│ ...                │  ...                           │
├────────────────────┼────────────────────────────────┤
│   Pagination       │     Pagination                 │
│  Page 1 of 3       │    Page 1 of 2                 │
└────────────────────┴────────────────────────────────┘
```

#### Features

**Real-Time Filtering**:
- Search across multiple fields (description, faculty name, email)
- Filter by facility (auto-filters device dropdown)
- Filter by device
- Debounced search (800ms delay)
- Maintains state across pagination

**Pagination**:
- 5 items per page per column
- Independent pagination for Pending and Ongoing
- Preserves filter state in URL parameters
- Shows page range (current ± 2 pages)

**Auto-Refresh**:
- Automatically reloads every 30 seconds
- Pauses during user activity (mousemove)
- Pauses when modals are open
- Countdown timer resets on activity

**Dynamic Device Filtering**:
```javascript
function filterDevicesByFacility() {
    const facilityId = document.getElementById('facilityFilter').value;
    const deviceOptions = deviceFilter.querySelectorAll('option');
    
    deviceOptions.forEach(option => {
        if (option.value === '0') {
            option.style.display = 'block'; // Always show "All Devices"
        } else if (facilityId === '0') {
            option.style.display = 'block'; // Show all if no facility selected
        } else {
            option.style.display = option.dataset.facility === facilityId ? 'block' : 'none';
        }
    });
}
```

#### Ticket Actions

| Status | Action | Next Status | Database Operation |
|--------|--------|-------------|-------------------|
| Pending | Accept | Ongoing | UPDATE concerns SET status = 'Ongoing' |
| Pending | Decline | Declined | INSERT to concern_history, DELETE from concerns |
| Ongoing | Resolved | Resolved | INSERT to concern_history, DELETE from concerns |
| Ongoing | Unresolved | Unresolved | INSERT to concern_history, DELETE from concerns |

#### Modals

**Feedback Modal** (Resolved/Unresolved):
- Textarea for resolution notes
- Required feedback
- Color-coded by action type

**Decline Modal**:
- Textarea for decline reason (required)
- Pre-filled default reason
- Warning about notification to user
- Reason sent to faculty member

---

### 3. History Panel (`admin/history.php`)

**Purpose**: View and manage archived tickets

#### Status Categories

| Status | Color Theme | Icon | Meaning |
|--------|-------------|------|---------|
| Resolved | Green | ✅ | Successfully fixed |
| Declined | Red | ❌ | Not IT department scope |
| Unresolved | Orange | ⚠️ | Could not be fixed |

#### Filtering System

**Status Tabs**:
```html
<button data-status="all">All (45)</button>
<button data-status="resolved">Resolved (30)</button>
<button data-status="declined">Declined (10)</button>
<button data-status="unresolved">Unresolved (5)</button>
```

**Date Range Filtering**:
```php
if (!empty($_GET['date_from'])) {
    $where_clauses[] = "DATE(archived_at) >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where_clauses[] = "DATE(archived_at) <= ?";
    $params[] = $_GET['date_to'];
}
```

**Search Functionality**:
- Ticket ID
- Description
- Faculty name
- Resolution feedback

#### Ticket Details Modal

**Information Displayed**:
- Ticket ID with formatted number
- Status badge (color-coded)
- Full description
- Faculty name and email
- Associated devices with facilities
- Resolution feedback/reason
- Timestamps:
  - Created date/time
  - Archived date/time
  - Duration calculation

**Duration Calculation**:
```javascript
const createdDate = new Date(ticket.created_at);
const archivedDate = new Date(ticket.archived_at);
const durationMs = archivedDate - createdDate;
const durationHours = Math.floor(durationMs / (1000 * 60 * 60));
const durationDays = Math.floor(durationHours / 24);
const remainingHours = durationHours % 24;

let durationText = '';
if (durationDays > 0) {
    durationText = `${durationDays} day${durationDays > 1 ? 's' : ''}, ${remainingHours} hour${remainingHours !== 1 ? 's' : ''}`;
} else {
    durationText = `${durationHours} hour${durationHours !== 1 ? 's' : ''}`;
}
```

**Print Functionality**:
- Print-friendly CSS styles
- Hides non-essential elements
- Preserves branding
- Clean layout for documentation

---

### 4. Facilities & Devices (`admin/facilities_devices.php`)

**Purpose**: Infrastructure and equipment management

#### Facilities Management

**CRUD Operations**:
```php
// Create
INSERT INTO facilities (facility_name, description) VALUES (?, ?)

// Read
SELECT f.*, COUNT(d.device_id) as device_count 
FROM facilities f 
LEFT JOIN devices d ON f.facility_id = d.facility_id 
GROUP BY f.facility_id

// Update
UPDATE facilities SET facility_name = ?, description = ? WHERE facility_id = ?

// Delete (with cascade check)
DELETE FROM facilities WHERE facility_id = ?
```

**Display Information**:
- Facility name
- Description
- Device count
- Created date
- Edit/Delete actions

#### Devices Management

**CRUD Operations**:
```php
// Create
INSERT INTO devices (facility_id, device_name, device_type, status) VALUES (?, ?, ?, ?)

// Read with JOIN
SELECT d.*, f.facility_name 
FROM devices d 
JOIN facilities f ON d.facility_id = f.facility_id

// Update
UPDATE devices SET device_name = ?, device_type = ?, status = ?, facility_id = ? WHERE device_id = ?

// Delete
DELETE FROM devices WHERE device_id = ?
```

**Device Status Options**:
- Active (Green badge)
- Inactive (Gray badge)
- Under Repair (Orange badge)

**Relationship**:
- Many-to-One: Multiple devices → One facility
- Foreign Key: `devices.facility_id` → `facilities.facility_id`
- ON DELETE CASCADE: Deleting facility removes all devices

---

### 5. User Management (`admin/adduserandadmin.php`)

**Purpose**: Create and manage user accounts

#### Account Creation Process

```
┌─────────────────────┐
│ Admin fills form    │
│ - Username (unique) │
│ - Email (unique)    │
│ - Role (User/Admin) │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│ Validation                  │
│ - Check username not taken  │
│ - Check email not taken     │
│ - Validate email format     │
└──────────┬──────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Create Account               │
│ - Default password: ncitad1234│
│ - Hash password (bcrypt)     │
│ - Set first_login = 1        │
│ - Set expiration: +24 hours  │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────┐
│ INSERT into users table  │
└──────────┬───────────────┘
           │
           ▼
┌──────────────────────────┐
│ Success message          │
│ (No email sent)          │
└──────────────────────────┘
```

**Default Password**: `ncitad1234`

**Account Expiration System**:
```php
// Set expiration to 24 hours from creation
$account_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Check on login
if ($user['first_login'] == 1 && strtotime($user['account_expires_at']) < time()) {
    $error = 'Your account has expired. Contact administrator.';
}
```

#### User States

| State | Flag Values | Behavior |
|-------|-------------|----------|
| **New Account** | `first_login=1`, `account_expires_at` set | Must configure within 24 hours |
| **Configured** | `first_login=0`, `account_expires_at=NULL` | Normal access |
| **Expired** | `first_login=1`, expired timestamp | Login blocked |
| **Disabled** | `is_active=0` | Login blocked |

#### Filtering Options

```javascript
<select id="filterSelect">
  <option value="all">All Users</option>
  <option value="admins">Admins Only</option>
  <option value="active">Active Users</option>
  <option value="inactive">Inactive Users</option>
  <option value="unconfigured">Unconfigured Accounts</option>
</select>
```

#### User Table Display

| Column | Data | Action |
|--------|------|--------|
| Username | `username` | Unique identifier |
| Full Name | `faculty_name` | Display name |
| Email | `email` | Contact & login |
| Role | `is_admin` (Badge) | Admin/User |
| Status | `is_active` (Badge) | Active/Inactive |
| First Login | `first_login` (Badge) | Yes/No |
| Expiry | `account_expires_at` | Countdown if unexpired |
| Actions | Edit/Disable/Delete buttons | - |

---

### 6. Settings (`admin/settings.php`)

**Purpose**: Admin profile and password management

#### Profile Update

**Editable Fields**:
```php
- Username (unique validation)
- Faculty Name (full name)
- Email (unique validation, email format)
```

**Validation Logic**:
```php
// Check username uniqueness (excluding self)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND user_id != ?");
$stmt->execute([$username, $user_id]);
if ($stmt->fetchColumn() > 0) {
    throw new Exception("Username already taken");
}

// Check email uniqueness (excluding self)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
$stmt->execute([$email, $user_id]);
if ($stmt->fetchColumn() > 0) {
    throw new Exception("Email already registered");
}
```

#### Password Change

**Requirements**:
1. Current password (must verify)
2. New password (min 6 characters)
3. Confirm password (must match new password)
4. New password ≠ current password

**Verification Process**:
```php
// Get current password hash
$stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_hash = $stmt->fetchColumn();

// Verify current password
if (!password_verify($current_password, $current_hash)) {
    throw new Exception("Current password is incorrect");
}

// Check new password strength
if (strlen($new_password) < 6) {
    throw new Exception("New password must be at least 6 characters");
}

// Check passwords match
if ($new_password !== $confirm_password) {
    throw new Exception("New passwords do not match");
}

// Check not same as current
if ($current_password === $new_password) {
    throw new Exception("New password must be different");
}

// Hash and update
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
$stmt->execute([$hashed, $user_id]);
```

**First Login Flag Reset**:
```php
// Mark account as fully configured
if ($user['first_login'] == 1) {
    $stmt = $pdo->prepare("UPDATE users SET first_login = 0, account_expires_at = NULL WHERE user_id = ?");
    $stmt->execute([$user_id]);
}
```

---

### 7. Reports (`admin/reports.php`)

**Purpose**: System analytics and statistics

#### Statistics Displayed

**Active Concerns**:
- Pending count
- Ongoing count

**Archived Concerns**:
- Resolved count
- Declined count
- Unresolved count

**User Metrics**:
- Total users
- Active users
- Inactive users
- Admin count

**Infrastructure**:
- Total facilities
- Total devices
- Devices per facility (breakdown)

**Query Example**:
```sql
SELECT 
    COUNT(*) as total_concerns,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Ongoing' THEN 1 ELSE 0 END) as ongoing
FROM concerns
```

---

## 👤 User Panel Details

### 1. User Dashboard (`user/dashboard.php`)

**Purpose**: Personal concern overview

#### Personal Metrics Cards

| Metric | Color | Query |
|--------|-------|-------|
| Total Concerns | Blue | `COUNT(*) WHERE user_id = ?` |
| Pending | Yellow | `COUNT(*) WHERE user_id = ? AND status = 'Pending'` |
| Ongoing | Orange | `COUNT(*) WHERE user_id = ? AND status = 'Ongoing'` |
| Resolved | Green | Count from concern_history |

#### Recent Concerns List

**Display**:
- Last 5 submitted concerns
- Status badge (color-coded)
- Short description (truncated)
- Submission date/time
- Click to view details

**Query**:
```sql
SELECT * FROM concerns 
WHERE user_id = ? 
ORDER BY created_at DESC 
LIMIT 5
```

#### Status Legend

Visual guide explaining status meanings:
- 🟡 **Pending**: Awaiting admin review
- 🟠 **Ongoing**: Being worked on
- 🟢 **Resolved**: Successfully fixed
- 🔴 **Declined**: Not IT department scope
- ⚫ **Unresolved**: Could not be fixed

---

### 2. Concern List (`user/concernlist.php`)

**Purpose**: View all submitted tickets

#### Concern Display

**Card Layout**:
```
┌─────────────────────────────────────────┐
│ Ticket #0001             [Status Badge] │
├─────────────────────────────────────────┤
│ Description preview (truncated)...      │
│                                         │
│ 💻 Device: Computer Lab 1 - Desktop 01 │
│ 📅 Submitted: Nov 1, 2025 at 9:30 AM   │
│                                         │
│           [View Details Button]         │
└─────────────────────────────────────────┘
```

**Status Badges**:
```php
$status_styles = [
    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
    'Ongoing' => 'bg-orange-100 text-orange-700 border-orange-300',
    'Resolved' => 'bg-green-100 text-green-700 border-green-300',
    'Declined' => 'bg-red-100 text-red-700 border-red-300',
    'Unresolved' => 'bg-gray-100 text-gray-700 border-gray-300'
];
```

#### Ticket Details Modal

**AJAX Loading**:
```javascript
function viewConcernDetails(concernId) {
    fetch(`get_concern_details.php?id=${concernId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalContent').innerHTML = data.html;
                document.getElementById('detailsModal').classList.remove('hidden');
            }
        });
}
```

**Modal Content**:
- Ticket ID with formatted number
- Status badge
- Full description (no truncation)
- Associated devices (list)
- Timestamps (created, updated)
- Resolution feedback (if status = Resolved/Declined/Unresolved)

---

### 3. Add New Concern (`user/addnewconcern.php`)

**Purpose**: Submit new IT support request

#### Form Structure

**Required Fields**:

1. **Description** (Textarea)
   - Minimum: 10 characters
   - Maximum: 1000 characters (recommended)
   - Multiline input
   - Character counter (real-time)
   - Validation on blur and submit

2. **Device Selection** (Checkboxes)
   - Minimum: 1 device
   - Maximum: Unlimited
   - Grouped by facility
   - Searchable list
   - Visual selection preview

#### Device Selection System

**Layout**:
```
┌──────────────────────────────────────┐
│  🔍 Search facilities or devices...  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ✅ Selected Devices (2)              │
│ 💻 Desktop PC 01 • Computer Lab 1    │
│ 🖨️ Printer • Faculty Lounge          │
└──────────────────────────────────────┘

┌─────────────────┬──────────────────┐
│ 🏢 Computer Lab 1│ 🏢 Faculty Lounge│
│ ☐ Desktop PC 01 │ ☐ Printer         │
│ ☐ Desktop PC 02 │ ☐ Whiteboard      │
│ ☐ Projector     │                   │
└─────────────────┴──────────────────┘
```

**Search Algorithm**:
```javascript
searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    
    facilityCards.forEach(card => {
        const facilityName = card.querySelector('h3').textContent.toLowerCase();
        const deviceItems = card.querySelectorAll('.device-item');
        let hasVisibleDevice = false;
        
        deviceItems.forEach(item => {
            const deviceName = item.querySelector('span').textContent.toLowerCase();
            const matches = deviceName.includes(searchTerm) || facilityName.includes(searchTerm);
            
            item.style.display = matches ? 'flex' : 'none';
            if (matches) hasVisibleDevice = true;
        });
        
        card.style.display = hasVisibleDevice || facilityName.includes(searchTerm) ? 'block' : 'none';
    });
});
```

**Selection Tracking**:
```javascript
function updateSelectedDevices() {
    const selected = Array.from(deviceCheckboxes).filter(cb => cb.checked);
    
    if (selected.length > 0) {
        selectedDevicesContainer.classList.remove('hidden');
        selectedCount.textContent = selected.length;
        
        selectedList.innerHTML = selected.map(cb => `
            <span class="device-badge">
                <i class="bi bi-hdd text-green-600"></i>
                ${cb.dataset.deviceName} • ${cb.dataset.facilityName}
            </span>
        `).join('');
    } else {
        selectedDevicesContainer.classList.add('hidden');
    }
}
```

#### Form Submission Process

```
┌─────────────────────┐
│ User fills form     │
│ - Description       │
│ - Select device(s)  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Client-side         │
│ Validation          │
│ - Min 10 chars      │
│ - At least 1 device │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Submit via POST     │
│ + CSRF token        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Server-side         │
│ Validation          │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Begin Transaction   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────────────┐
│ INSERT into concerns        │
│ - user_id                   │
│ - description               │
│ - status = 'Pending'        │
│ - created_at = NOW()        │
└──────────┬──────────────────┘
           │
           ▼
┌─────────────────────────────┐
│ Get last insert ID          │
│ $concern_id = $pdo->lastInsertId() │
└──────────┬──────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Loop through device IDs      │
│ INSERT into concern_devices  │
│ - concern_id                 │
│ - device_id                  │
└──────────┬───────────────────┘
           │
           ▼
┌─────────────────────┐
│ Commit Transaction  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Success Message     │
│ Redirect to list    │
└─────────────────────┘
```

**Transaction Code**:
```php
try {
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
    
    $pdo->commit();
    $_SESSION['success'] = "Concern submitted successfully! Ticket #$concern_id created.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error: " . $e->getMessage();
}
```

---

### 4. User Settings (`user/settings.php`)

**Purpose**: Profile and password management

**Similar to Admin Settings** but:
- Limited to own account only
- Cannot change role/admin status
- Cannot disable own account
- Same password requirements

---

## 🔒 Security Summary

### Security Features Implementation

| Security Feature | Algorithm/Method | Implementation File | OWASP Coverage |
|------------------|------------------|---------------------|----------------|
| **SQL Injection Prevention** | PDO Prepared Statements | All PHP files with DB queries | A1: Injection |
| **Password Hashing** | bcrypt (PASSWORD_DEFAULT) | `login.php`, `*settings.php` | A2: Broken Authentication |
| **Session Security** | HttpOnly, Secure flags, Regeneration | `login.php`, `includes/config.php` | A2: Broken Authentication |
| **CSRF Protection** | Cryptographic tokens (32 bytes) | All forms | A8: CSRF |
| **XSS Prevention** | `htmlspecialchars()` output escaping | All display files | A7: XSS |
| **Email Validation** | `FILTER_VALIDATE_EMAIL` | All email inputs | A1: Injection |
| **Input Sanitization** | `trim()`, `strip_tags()`, type checking | All input processing | A1: Injection |
| **Password Reset** | Secure tokens with expiration | `forgot-password.php` | A2: Broken Authentication |
| **Session Timeout** | 30-minute inactivity check | Session validation | A2: Broken Authentication |
| **Account Expiration** | 24-hour unconfigured account lock | `login.php` | A5: Broken Access Control |

### Data Flow Security

```
┌──────────────┐
│ User Input   │
└──────┬───────┘
       │
       ▼
┌──────────────────┐
│ Client-Side      │
│ Validation       │
│ (JavaScript)     │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Server-Side      │
│ Validation       │
│ (PHP)            │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Sanitization     │
│ - trim()         │
│ - strip_tags()   │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Prepared         │
│ Statement        │
│ (PDO)            │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Database         │
│ (MySQL)          │
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Output Escaping  │
│ htmlspecialchars()│
└──────┬───────────┘
       │
       ▼
┌──────────────────┐
│ Display to User  │
└──────────────────┘
```

---

## 📊 Database Schema Summary

### Entity Relationship Diagram

```
┌──────────────┐
│    users     │
│──────────────│
│ user_id (PK) │
│ username     │
│ faculty_name │
│ email        │
│ password     │
│ is_admin     │
│ is_active    │
│ first_login  │
└──────┬───────┘
       │
       │ 1:N
       ▼
┌──────────────────┐
│    concerns      │
│──────────────────│
│ concern_id (PK)  │
│ user_id (FK)     │
│ description      │
│ status           │
│ created_at       │
│ updated_at       │
└──────┬───────────┘
       │
       │ N:M (via concern_devices)
       ▼
┌──────────────────┐         ┌──────────────────┐
│ concern_devices  │         │    devices       │
│──────────────────│         │──────────────────│
│ concern_id (FK)  │────────▶│ device_id (PK)   │
│ device_id (FK)   │         │ facility_id (FK) │
└──────────────────┘         │ device_name      │
                             │ device_type      │
                             │ status           │
                             └──────┬───────────┘
                                    │ N:1
                                    ▼
                             ┌──────────────────┐
                             │   facilities     │
                             │──────────────────│
                             │ facility_id (PK) │
                             │ facility_name    │
                             │ description      │
                             └──────────────────┘

┌──────────────────────┐
│  concern_history     │
│──────────────────────│
│ id (PK)              │
│ concern_id           │
│ user_id              │
│ description          │
│ status               │
│ resolution_feedback  │
│ faculty_name         │
│ email                │
│ devices (JSON)       │
│ archived_at          │
└──────────────────────┘
```

---

## 🎯 Key Performance Optimizations

### Database Optimizations

1. **Indexed Columns**:
   - `users.username` (UNIQUE index)
   - `users.email` (UNIQUE index)
   - `concerns.user_id` (FK index)
   - `concerns.status` (for filtering)
   - `concern_devices.concern_id` (FK index)
   - `concern_devices.device_id` (FK index)

2. **Query Optimizations**:
   - Use `COUNT(*)` instead of `COUNT(column)`
   - Use `LIMIT` for pagination
   - Use `GROUP BY` with `GROUP_CONCAT` for device lists
   - Avoid N+1 queries with JOINs

3. **Transaction Usage**:
   - Multi-table operations wrapped in transactions
   - Rollback on any error
   - ACID compliance guaranteed

### Frontend Optimizations

1. **Debounced Search**: 800ms delay prevents excessive requests
2. **Auto-Refresh**: Pauses during user activity
3. **Lazy Loading**: Modals load content only when opened
4. **Client-Side Filtering**: Reduces server requests for device search

---

## 📝 Naming Conventions

### PHP Variables & Functions
- **Snake Case**: `$user_id`, `$faculty_name`, `send_email()`
- **Descriptive Names**: `$pending_tickets`, `$total_concerns`

### Database Tables & Columns
- **Snake Case**: `concern_devices`, `created_at`
- **Plural for Tables**: `users`, `concerns`, `facilities`
- **Singular for Columns**: `user_id`, `device_name`

### JavaScript
- **Camel Case**: `concernId`, `updateSelectedDevices()`
- **Constants**: `UPPERCASE_SNAKE_CASE` (for config values)

### CSS Classes (Tailwind)
- **Utility Classes**: `bg-blue-600`, `text-white`, `rounded-lg`
- **Custom Classes**: `ticket-card`, `device-item`, `facility-card`

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | November 1, 2025 | Initial system deployment |
| 1.1 | November 5, 2025 | Documentation complete |

---

## 📚 Additional Documentation

- **[README.md](README.md)**: Project overview and setup
- **[AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md)**: AWS deployment instructions
- **[EC2_GIT_UPDATE_GUIDE.md](EC2_GIT_UPDATE_GUIDE.md)**: Git workflow on AWS EC2
- **[GIT_QUICK_START.md](GIT_QUICK_START.md)**: Git basics and commands

---

**End of Documentation**

*For questions or support, contact the NCITAD development team.*
