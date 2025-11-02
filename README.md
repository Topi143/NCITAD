# NCITAD - Norzagaray College IT Assistance Desk

<div align="center">
  <img src="assets/images/norzagaray-college-logo.png" alt="NCITAD Logo" width="200"/>
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php)](https://www.php.net/)
  [![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql)](https://www.mysql.com/)
  [![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-38B2AC?logo=tailwind-css)](https://tailwindcss.com/)
  [![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)
</div>

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Deployment](#deployment)
- [Git Workflow](#git-workflow)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Security](#security)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## 🎯 Overview

**NCITAD** (Norzagaray College IT Assistance Desk) is a comprehensive web-based ticket management system designed specifically for educational institutions. It streamlines IT support requests, enabling faculty members to submit concerns and track their resolution status while providing administrators with powerful tools to manage and resolve tickets efficiently.

### Key Highlights

- **Role-Based Access Control**: Separate interfaces for administrators and faculty members
- **Complete Ticket Lifecycle**: From submission to resolution with status tracking
- **Email Notifications**: Automated updates via PHPMailer SMTP integration
- **Facility & Device Management**: Track IT infrastructure and equipment
- **Historical Records**: Comprehensive archive system with search capabilities
- **Responsive Design**: Mobile-first approach using Tailwind CSS
- **Production-Ready**: Includes AWS deployment guide and security best practices

---

## ✨ Features

### For Faculty Members (Users)
- ✅ Submit IT concerns with detailed descriptions
- ✅ Select affected devices and facilities
- ✅ Track ticket status in real-time
- ✅ View submission history
- ✅ Receive email notifications on status changes
- ✅ Update profile settings
- ✅ Password recovery via email

### For IT Staff (Administrators)
- ✅ Dashboard with real-time statistics
- ✅ Manage tickets: Accept, decline, or mark as ongoing
- ✅ Resolve tickets with feedback notes
- ✅ User management: Add faculty and admin accounts
- ✅ Facility and device CRUD operations
- ✅ View historical records with filtering
- ✅ Email notification management
- ✅ Secure authentication and session management

### Technical Features
- 🔒 **Security**: PDO prepared statements, CSRF protection, password hashing
- 📧 **Email System**: PHPMailer with SMTP support
- 🎨 **Modern UI**: Tailwind CSS with custom components
- 📱 **Responsive**: Works on desktop, tablet, and mobile
- ☁️ **Cloud-Ready**: AWS RDS and EC2 deployment support
- 📊 **Analytics**: Dashboard with ticket statistics
- 🔄 **Git Integration**: Version control and automated deployments

---

## 🛠 Technology Stack

### Backend
- **Language**: PHP 8.0+ (compatible with 7.4+)
- **Database**: MySQL 8.0+ / MariaDB 10.5+
- **Database Driver**: PDO (PHP Data Objects) with prepared statements
- **Session Management**: Native PHP sessions with security hardening
- **Email**: PHPMailer 6.x with SMTP support
- **Authentication**: bcrypt password hashing

### Frontend
- **Markup**: Semantic HTML5
- **Styling**: Tailwind CSS 3.x (utility-first CSS framework)
- **Icons**: Bootstrap Icons 1.11.3
- **JavaScript**: Vanilla ES6+ (no frameworks)
- **AJAX**: Fetch API for asynchronous operations

### Development Environment
- **Server Stack**: XAMPP (Apache 2.4, MySQL 8.0, PHP 8.0)
- **Local URL**: `http://localhost/ncitad`
- **Database Tool**: MySQL Workbench / phpMyAdmin
- **Code Editor**: Visual Studio Code
- **Version Control**: Git / GitHub

### Production Environment (AWS)
- **Cloud Provider**: Amazon Web Services
- **Database**: AWS RDS MySQL (Multi-AZ optional)
- **Compute**: EC2 instances (t2.micro/t2.small)
- **Storage**: EBS volumes
- **SSL**: Let's Encrypt / AWS Certificate Manager
- **Monitoring**: CloudWatch

---

## 📦 Prerequisites

### Required Software
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache 2.4 or Nginx
- Git 2.x
- Composer (optional, for future dependencies)

### Development Setup
- XAMPP / WAMP / MAMP (for local development)
- MySQL Workbench (recommended)
- VS Code with PHP extensions

### Optional
- GitHub account (for version control)
- AWS account (for cloud deployment)
- Domain name (for production)

---

## 🚀 Installation

### 1. Clone Repository

```bash
# Clone from GitHub
git clone https://github.com/yourusername/ncitad.git

# Navigate to project directory
cd ncitad
```

### 2. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ncitad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema and data
mysql -u root -p ncitad < ncitad.sql

# Verify import
mysql -u root -p ncitad -e "SHOW TABLES;"
```

### 3. Configure Application

```bash
# Copy example configuration files
cp includes/config.example.php includes/config.php
cp includes/email_config.example.php includes/email_config.php

# Edit configuration with your credentials
# Use your preferred editor (nano, vim, VS Code, etc.)
nano includes/config.php
```

### 4. Set Up Web Server

**For XAMPP (Windows):**
```
1. Copy project to C:\xampp\htdocs\ncitad
2. Start Apache and MySQL from XAMPP Control Panel
3. Access: http://localhost/ncitad
```

**For Linux/Apache:**
```bash
# Create virtual host (see AWS_DEPLOYMENT_GUIDE.md for details)
sudo cp ncitad.conf /etc/apache2/sites-available/
sudo a2ensite ncitad
sudo systemctl restart apache2
```

### 5. Verify Installation

1. Open browser and navigate to your local URL
2. You should see the NCITAD login page
3. Login with default credentials (see below)
4. Change default password immediately!

---

## ⚙️ Configuration

### Database Configuration (`includes/config.php`)

```php
<?php
$host = 'localhost';           // Database host
$dbname = 'ncitad';           // Database name
$username = 'root';           // Database username
$password = 'your_password';  // Database password

// PDO connection (don't modify unless needed)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
                   $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
```

### Email Configuration (`includes/email_config.php`)

```php
<?php
// Email method: 'smtp' or 'mail'
define('EMAIL_METHOD', 'smtp');

// SMTP Settings (for Gmail)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');  // Use App Password, not regular password

// Sender Information
define('EMAIL_FROM_ADDRESS', 'no-reply@ncitad.edu');
define('EMAIL_FROM_NAME', 'NCITAD System');

// Enable/Disable emails globally
define('EMAIL_ENABLED', true);  // Set to false to disable all emails
?>
```

### Default Credentials

**Administrator Account:**
- Username: `admin`
- Password: `admin123`

**⚠️ SECURITY WARNING**: Change default credentials immediately after first login!

---

## 💻 Usage

### For Faculty Members

1. **Login**: Access the system with your credentials
2. **Submit Concern**:
   - Navigate to "Add New Concern"
   - Describe your IT issue
   - Select affected device(s)
   - Submit ticket
3. **Track Status**: View all your concerns and their current status
4. **Receive Updates**: Get email notifications when status changes

### For Administrators

1. **Dashboard**: View statistics and recent activity
2. **Manage Tickets**:
   - View pending concerns
   - Accept and set to "Ongoing"
   - Resolve with feedback or mark as unresolved
   - Decline if not an IT issue
3. **User Management**: Add new faculty or admin accounts
4. **Facility Management**: Add/edit buildings and departments
5. **Device Management**: Track IT equipment and their locations

---

## 🌐 Deployment

### AWS Deployment

For complete AWS deployment instructions, see **[AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md)**

Quick summary:
1. **RDS Setup**: Create MySQL database instance
2. **EC2 Setup**: Launch Ubuntu server with LAMP stack
3. **Deploy Code**: Clone from GitHub to `/var/www/ncitad`
4. **Configure**: Set up database connection and email
5. **SSL**: Install Let's Encrypt certificate
6. **Monitor**: Set up CloudWatch and backups

### Quick Deploy Script

```bash
# On EC2 server
sudo /usr/local/bin/deploy-ncitad.sh
```

---

## 🔄 Git Workflow

### Development Workflow

```bash
# Create feature branch
git checkout -b feature/ticket-notifications

# Make changes and commit
git add .
git commit -m "Add: Email notifications for ticket status changes"

# Push to GitHub
git push origin feature/ticket-notifications

# Create Pull Request on GitHub
# After review and approval, merge to main
```

### Deployment Workflow

```bash
# After merging to main, deploy to production
ssh ubuntu@your-ec2-server
sudo /usr/local/bin/deploy-ncitad.sh
```

### Hotfix Workflow

```bash
# For urgent production fixes
git checkout -b hotfix/critical-bug
# ... make fix ...
git commit -m "Fix: Critical login bug"
git push origin hotfix/critical-bug
# Fast-track merge and deploy
```

See [AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md) for detailed Git integration.

---

## 📁 Project Structure

```
ncitad/
├── .github/
│   └── copilot-instructions.md   # GitHub Copilot development guidelines
├── admin/                         # Administrator panel
│   ├── base.php                  # Header, navigation, sidebar
│   ├── footer.php                # Footer and closing tags
│   ├── dashboard.php             # Admin dashboard
│   ├── concerns.php              # Active ticket management
│   ├── history.php               # Archived tickets
│   ├── facilities_devices.php    # Facility & device CRUD
│   ├── adduserandadmin.php       # User management
│   ├── settings.php              # Admin profile settings
│   └── get_ticket_details.php    # AJAX endpoint for modals
├── user/                          # Faculty member portal
│   ├── base.php                  # User navigation
│   ├── footer.php                # Footer
│   ├── dashboard.php             # User dashboard
│   ├── concernlist.php           # View submitted concerns
│   ├── addnewconcern.php         # Submit new ticket
│   ├── settings.php              # User profile
│   ├── get_concern_details.php   # AJAX endpoint
│   └── update_settings.php       # Settings handler
├── includes/                      # Backend utilities
│   ├── config.php                # Database connection (gitignored)
│   ├── config.example.php        # Config template
│   ├── email_config.php          # Email settings (gitignored)
│   ├── email_config.example.php  # Email template
│   ├── email_helper.php          # Email functions
│   └── PHPMailer/                # PHPMailer library
├── assets/
│   ├── css/
│   │   └── styles.css            # Custom CSS
│   └── images/                   # Logos and images
├── .gitignore                     # Git ignore rules
├── index.php                      # Landing page
├── login.php                      # Authentication
├── logout.php                     # Session termination
├── forgot-password.php            # Password recovery
├── reset-password.php             # Password reset handler
├── ncitad.sql                     # Database schema
├── AWS_DEPLOYMENT_GUIDE.md        # AWS deployment instructions
└── README.md                      # This file
```

---

## 🔒 Security

### Implemented Security Measures

- ✅ **SQL Injection Prevention**: PDO prepared statements for all queries
- ✅ **XSS Protection**: Output escaping with `htmlspecialchars()`
- ✅ **CSRF Protection**: Token validation on forms
- ✅ **Password Security**: bcrypt hashing with `password_hash()`
- ✅ **Session Security**: HttpOnly, Secure flags, regeneration on login
- ✅ **Authentication**: Role-based access control
- ✅ **Input Validation**: Server-side validation for all inputs
- ✅ **Error Handling**: No sensitive data in error messages
- ✅ **File Upload Security**: Type and size validation
- ✅ **HTTPS Enforcement**: SSL/TLS in production

### Security Best Practices

```php
// Always use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Always escape output
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');

// Always hash passwords
$hashed = password_hash($password, PASSWORD_BCRYPT);
if (password_verify($input, $hashed)) { /* ... */ }
```

---

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
```bash
# Check credentials in includes/config.php
# Verify MySQL is running
sudo systemctl status mysql
```

**Email Not Sending**
```bash
# Check email_config.php settings
# For Gmail, use App Password (not regular password)
# Verify SMTP port and encryption
```

**Login Issues**
```bash
# Clear browser cache and cookies
# Check session configuration in php.ini
# Verify database contains user records
```

**Permission Denied**
```bash
# Set correct permissions
sudo chown -R www-data:www-data /var/www/ncitad
sudo chmod -R 755 /var/www/ncitad
```

For more troubleshooting, see [AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md#troubleshooting).

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### How to Contribute

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/amazing-feature`
3. **Commit** your changes: `git commit -m 'Add amazing feature'`
4. **Push** to branch: `git push origin feature/amazing-feature`
5. **Open** a Pull Request

### Coding Standards

- Follow existing code style and conventions
- Use meaningful variable and function names
- Comment complex logic
- Test thoroughly before submitting PR
- Follow security best practices

### Commit Message Format

```
Type: Brief description

Detailed explanation if needed

Examples:
- Add: New email notification system
- Fix: Login redirect issue on session timeout
- Update: Improve dashboard performance
- Refactor: Simplify ticket status logic
```

---

## 📄 License

**Proprietary License** - © 2024 Norzagaray College

This software is proprietary and confidential. Unauthorized copying, distribution, or modification is strictly prohibited.

For licensing inquiries, contact: admin@norzagaraycollege.edu

---

## 📞 Support

### Getting Help

- 📧 **Email**: support@ncitad.edu
- 📚 **Documentation**: See [AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md)
- 🐛 **Report Bugs**: Open an issue on GitHub
- 💡 **Feature Requests**: Submit via GitHub Issues

### Useful Resources

- [PHP Documentation](https://www.php.net/manual/)
- [MySQL Reference](https://dev.mysql.com/doc/)
- [Tailwind CSS Docs](https://tailwindcss.com/docs)
- [PHPMailer Guide](https://github.com/PHPMailer/PHPMailer)
- [AWS Documentation](https://docs.aws.amazon.com/)

---

## 🙏 Acknowledgments

- **Norzagaray College** - For supporting this project
- **PHP Community** - For excellent documentation and resources
- **Tailwind CSS Team** - For the amazing CSS framework
- **PHPMailer Contributors** - For reliable email functionality
- **AWS** - For scalable cloud infrastructure

---

## 📊 Project Status

- ✅ **Version**: 1.0.0
- ✅ **Status**: Production Ready
- ✅ **Last Updated**: November 2, 2024
- ✅ **PHP**: 8.0+
- ✅ **MySQL**: 8.0+
- ✅ **License**: Proprietary

---

<div align="center">
  <p>Made with ❤️ by the NCITAD Development Team</p>
  <p>© 2024 Norzagaray College. All rights reserved.</p>
</div>
