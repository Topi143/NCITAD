# AWS Deployment Guide for NCITAD System

## Complete Guide to Hosting PHP Application and MySQL Database on AWS

This comprehensive guide will walk you through deploying the NCITAD ticketing system to Amazon Web Services (AWS), including setting up the database, web server, and application deployment.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [AWS Account Setup](#aws-account-setup)
3. [Part 1: Database Setup (Amazon RDS)](#part-1-database-setup-amazon-rds)
4. [Part 2: Web Server Setup (Amazon EC2)](#part-2-web-server-setup-amazon-ec2)
5. [Part 3: Application Deployment](#part-3-application-deployment)
6. [Part 4: Domain and SSL Setup](#part-4-domain-and-ssl-setup)
7. [Part 5: Security Hardening](#part-5-security-hardening)
8. [Part 6: Backup and Monitoring](#part-6-backup-and-monitoring)
9. [Troubleshooting](#troubleshooting)
10. [Cost Optimization](#cost-optimization)

---

## Prerequisites

### What You'll Need:
- ✅ AWS Account (with credit card for verification)
- ✅ Your NCITAD application files
- ✅ MySQL database dump file (`ncitad.sql`)
- ✅ GitHub account (free) for version control
- ✅ Git installed locally (https://git-scm.com/downloads)
- ✅ SSH client (Windows: PowerShell/Git Bash, Mac/Linux: Terminal)
- ✅ Domain name (optional, but recommended)
- ✅ Code editor with Git integration (VS Code recommended)

### Estimated Costs:
- **Free Tier Eligible** (first 12 months): ~$0-10/month
- **After Free Tier**: ~$20-50/month depending on usage
- **Minimal Setup**: ~$15/month (t2.micro EC2 + db.t2.micro RDS)

---

## Git Setup & Repository Creation

### Step 1: Initialize Local Git Repository

Before deploying to AWS, let's version control your project:

```bash
# Navigate to your project directory
cd c:\xampp\htdocs\ncitad

# Initialize Git repository
git init

# Check Git status
git status
```

### Step 2: Create .gitignore File

Create a `.gitignore` file to exclude sensitive and unnecessary files:

```bash
# Create .gitignore
notepad .gitignore
```

Add the following content:

```gitignore
# Sensitive Configuration Files
includes/config.php
includes/email_config.php

# Environment-specific files
.env
*.env.local
*.env.production

# Log files
*.log
error_log

# Temporary files
*.tmp
*.temp
~*

# Database dumps (keep one in repo, ignore others)
*.sql
!ncitad.sql

# PHP specific
vendor/
composer.lock

# OS specific
.DS_Store
Thumbs.db
desktop.ini

# IDE specific
.vscode/
.idea/
*.swp
*.swo
*~

# Upload directories (if you have file uploads)
uploads/*
!uploads/.gitkeep

# Backup files
*.backup
*.bak
backups/

# Cache
cache/*
tmp/*
```

### Step 3: Create Template Configuration Files

Create template versions of sensitive configuration files:

```bash
# Copy config.php to config.example.php
copy includes\config.php includes\config.example.php

# Copy email_config.php to email_config.example.php (if exists)
copy includes\email_config.php includes\email_config.example.php
```

Edit the template files to remove sensitive data:

**includes/config.example.php:**
```php
<?php
// Database configuration template
// Copy this file to config.php and update with your actual credentials

$host = 'your-rds-endpoint.rds.amazonaws.com';  // RDS endpoint
$dbname = 'ncitad';
$username = 'your_db_username';
$password = 'your_db_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact administrator.');
}
?>
```

### Step 4: Create GitHub Repository

1. Go to https://github.com and sign in (or create account)
2. Click the **"+"** icon (top-right) → **"New repository"**
3. Repository settings:
   - Repository name: `ncitad`
   - Description: `Norzagaray College IT Assistance Desk - Ticket Management System`
   - Visibility: **Private** (recommended) or Public
   - **Do NOT** initialize with README, .gitignore, or license (we already have these)
4. Click **"Create repository"**
5. Copy the repository URL (should look like: `https://github.com/yourusername/ncitad.git`)

### Step 5: Initial Commit and Push

```bash
# Add all files to staging
git add .

# Verify what will be committed
git status

# Create first commit
git commit -m "Initial commit: NCITAD Ticket Management System"

# Add GitHub remote (replace with your actual repository URL)
git remote add origin https://github.com/yourusername/ncitad.git

# Verify remote was added
git remote -v

# Push to GitHub
git push -u origin main

# If you get an error about 'main' vs 'master', try:
git branch -M main
git push -u origin main
```

### Step 6: Create README.md

Create a comprehensive README for your repository:

```bash
notepad README.md
```

Add content like:

```markdown
# NCITAD - Norzagaray College IT Assistance Desk

A comprehensive ticket management system for handling IT concerns and support requests.

## Features
- User and Admin role-based access
- Ticket lifecycle management (Pending → Ongoing → Resolved/Declined/Unresolved)
- Email notifications via PHPMailer
- Facility and device tracking
- Historical records and analytics
- Responsive design with Tailwind CSS

## Technology Stack
- **Backend:** PHP 8.0+, PDO/MySQL
- **Frontend:** HTML5, Tailwind CSS, JavaScript ES6+
- **Database:** MySQL 8.0+
- **Email:** PHPMailer with SMTP
- **Deployment:** AWS (EC2, RDS)

## Local Development Setup

1. Clone repository:
   ```bash
   git clone https://github.com/yourusername/ncitad.git
   cd ncitad
   ```

2. Configure database:
   ```bash
   cp includes/config.example.php includes/config.php
   # Edit config.php with your database credentials
   ```

3. Import database:
   ```bash
   mysql -u root -p < ncitad.sql
   ```

4. Configure email (optional):
   ```bash
   cp includes/email_config.example.php includes/email_config.php
   # Edit with your SMTP credentials
   ```

5. Start XAMPP and access: `http://localhost/ncitad`

## AWS Deployment

See [AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md) for complete deployment instructions.

## Default Credentials (Development Only)
- Username: `admin`
- Password: `admin123`

**⚠️ Change immediately in production!**

## Contributing
1. Fork the repository
2. Create feature branch: `git checkout -b feature/your-feature`
3. Commit changes: `git commit -m 'Add some feature'`
4. Push to branch: `git push origin feature/your-feature`
5. Submit pull request

## License
Proprietary - Norzagaray College

## Support
For issues and questions, contact: support@ncitad.edu
```

Commit and push the README:

```bash
git add README.md
git commit -m "Add comprehensive README documentation"
git push
```

### Step 7: Create Development Branch

It's good practice to keep `main` stable and work on features in branches:

```bash
# Create and switch to development branch
git checkout -b development

# Push development branch to GitHub
git push -u origin development

# Switch back to main when needed
git checkout main
```

### Step 8: Set Up GitHub Repository Secrets (for CI/CD)

For future automation, you can store AWS credentials securely in GitHub:

1. Go to your GitHub repository
2. Click **Settings** → **Secrets and variables** → **Actions**
3. Click **"New repository secret"**
4. Add secrets (one at a time):
   - `AWS_ACCESS_KEY_ID`: Your AWS access key
   - `AWS_SECRET_ACCESS_KEY`: Your AWS secret key
   - `RDS_HOST`: Your RDS endpoint
   - `RDS_PASSWORD`: Your database password
   - `SMTP_PASSWORD`: Your email SMTP password

---

## AWS Account Setup

### Step 1: Create AWS Account

1. Go to https://aws.amazon.com/
2. Click **"Create an AWS Account"**
3. Fill in:
   - Email address
   - Password
   - AWS account name (e.g., "NCITAD Production")
4. Choose **"Personal"** account type
5. Enter billing information (credit card required)
6. Verify your phone number
7. Select **"Basic Support - Free"** plan

### Step 2: Secure Your Root Account

1. Log in to AWS Console: https://console.aws.amazon.com/
2. Click your account name → **"Security Credentials"**
3. Enable **Multi-Factor Authentication (MFA)**:
   - Install Google Authenticator or Authy on your phone
   - Scan QR code and enter codes
4. Create **IAM Admin User** (don't use root for daily tasks):
   - Go to IAM service
   - Click "Users" → "Add users"
   - Username: `admin`
   - Enable "AWS Management Console access"
   - Attach "AdministratorAccess" policy
   - Save credentials securely

### Step 3: Choose AWS Region

1. In AWS Console, top-right corner, select region
2. **Recommended regions for Philippines**:
   - **Singapore (ap-southeast-1)** - Closest, lowest latency
   - **Tokyo (ap-northeast-1)** - Alternative
   - **Sydney (ap-southeast-2)** - Backup option
3. **Important**: Use the same region for ALL services (RDS, EC2, etc.)

---

## Part 1: Database Setup (Amazon RDS)

### Why RDS?
- Automated backups
- Easy scaling
- Automatic software patching
- High availability option

### Step 1: Create MySQL Database

1. Go to **RDS** service in AWS Console
2. Click **"Create database"**
3. Choose settings:

   **Engine options:**
   - Engine: **MySQL**
   - Version: **MySQL 8.0.35** (or latest 8.0.x)

   **Templates:**
   - Choose **"Free tier"** (if eligible) or **"Production"**

   **Settings:**
   - DB instance identifier: `ncitad-database`
   - Master username: `admin`
   - Master password: Create strong password (save it securely!)
   - Confirm password

   **Instance configuration:**
   - Free Tier: **db.t2.micro** (1 vCPU, 1 GB RAM)
   - Production: **db.t3.small** or larger

   **Storage:**
   - Storage type: **General Purpose SSD (gp3)**
   - Allocated storage: **20 GB** (minimum)
   - Enable storage autoscaling: **Yes**
   - Maximum storage threshold: **100 GB**

   **Connectivity:**
   - VPC: **Default VPC**
   - Public access: **Yes** (for now, we'll secure it later)
   - VPC security group: Create new
   - Security group name: `ncitad-db-sg`
   - Availability Zone: **No preference**

   **Database authentication:**
   - Password authentication

   **Additional configuration:**
   - Initial database name: `ncitad`
   - Backup retention: **7 days** (or more)
   - Enable encryption: **Yes** (recommended)

4. Click **"Create database"**
5. Wait 5-10 minutes for database to be created

### Step 2: Configure Database Security

1. Go to **RDS** → **Databases** → Click `ncitad-database`
2. Click on **VPC security groups** link
3. Click **"Edit inbound rules"**
4. Add rule:
   - Type: **MySQL/Aurora**
   - Port: **3306**
   - Source: **My IP** (for initial setup)
   - Description: `Temporary access for setup`
5. Click **"Save rules"**

### Step 3: Get Database Endpoint

1. In RDS console, click your database `ncitad-database`
2. Copy **Endpoint** (looks like: `ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com`)
3. Save this - you'll need it later!

### Step 4: Import Your Database

**Option A: Using MySQL Workbench (Recommended for Windows)**

1. Open MySQL Workbench
2. Create new connection:
   - Connection Name: `NCITAD AWS`
   - Hostname: `[Your RDS Endpoint]`
   - Port: `3306`
   - Username: `admin`
   - Password: [Your master password]
3. Click **"Test Connection"**
4. If successful, click **"OK"**
5. Open connection
6. Go to **Server** → **Data Import**
7. Select **"Import from Self-Contained File"**
8. Choose your `Dump20251101.sql` file
9. Target schema: `ncitad`
10. Click **"Start Import"**

**Option B: Using Command Line**

```bash
# Replace with your actual values
mysql -h ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com \
      -u admin \
      -p \
      ncitad < Dump20251101.sql
```

### Step 5: Verify Database Import

```sql
-- Connect to your RDS database
USE ncitad;

-- Check tables
SHOW TABLES;

-- Verify data
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM concerns;
SELECT COUNT(*) FROM facilities;
SELECT COUNT(*) FROM devices;
```

---

## Part 2: Web Server Setup (Amazon EC2)

### Step 1: Launch EC2 Instance

1. Go to **EC2** service in AWS Console
2. Click **"Launch Instance"**
3. Configure:

   **Name and tags:**
   - Name: `NCITAD-WebServer`

   **Application and OS Images (Amazon Machine Image):**
   - Quick Start: **Ubuntu**
   - Ubuntu Server 22.04 LTS (Free tier eligible)
   - Architecture: **64-bit (x86)**

   **Instance type:**
   - Free tier: **t2.micro** (1 vCPU, 1 GB RAM)
   - Recommended: **t2.small** or **t3.small** (2 GB RAM)

   **Key pair (login):**
   - Click **"Create new key pair"**
   - Key pair name: `ncitad-keypair`
   - Key pair type: **RSA**
   - Private key format: **pem** (Linux/Mac) or **ppk** (Windows/PuTTY)
   - Click **"Create key pair"**
   - **IMPORTANT**: Save the downloaded key file securely!

   **Network settings:**
   - VPC: **Default VPC**
   - Auto-assign public IP: **Enable**
   - Firewall (security groups): **Create security group**
   - Security group name: `ncitad-web-sg`
   - Description: `Security group for NCITAD web server`
   - Inbound rules:
     * SSH (22) - My IP (for setup)
     * HTTP (80) - Anywhere (0.0.0.0/0)
     * HTTPS (443) - Anywhere (0.0.0.0/0)

   **Configure storage:**
   - Root volume: **20 GB gp3**
   - Delete on termination: **Yes**

4. Click **"Launch instance"**
5. Wait for instance state to show **"Running"**

### Step 2: Connect to Your Server

**For Windows (using PowerShell):**

```powershell
# Navigate to where you saved the key file
cd C:\Users\YourName\Downloads

# Set correct permissions
icacls ncitad-keypair.pem /inheritance:r
icacls ncitad-keypair.pem /grant:r "$($env:USERNAME):(R)"

# Connect via SSH (replace with your instance public IP)
ssh -i ncitad-keypair.pem ubuntu@54.xxx.xxx.xxx
```

**For Windows (using PuTTY):**

1. Download PuTTY: https://www.putty.org/
2. Convert .pem to .ppk using PuTTYgen:
   - Open PuTTYgen
   - Load your .pem file
   - Click "Save private key"
   - Save as ncitad-keypair.ppk
3. Open PuTTY:
   - Host Name: `ubuntu@[Your EC2 Public IP]`
   - Port: 22
   - Connection → SSH → Auth: Browse for your .ppk file
   - Click "Open"

**For Mac/Linux:**

```bash
# Set correct permissions
chmod 400 ncitad-keypair.pem

# Connect
ssh -i ncitad-keypair.pem ubuntu@54.xxx.xxx.xxx
```

### Step 3: Update System

```bash
# Update package list
sudo apt update

# Upgrade all packages
sudo apt upgrade -y

# Reboot if kernel was updated
sudo reboot
```

Wait 1-2 minutes, then reconnect.

### Step 4: Install LAMP Stack and Git

```bash
# Install Git (essential for deployment)
sudo apt install git -y

# Configure Git with your information
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Install Apache web server
sudo apt install apache2 -y

# Start and enable Apache
sudo systemctl start apache2
sudo systemctl enable apache2

# Install PHP 8.1 and required extensions
sudo apt install php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd -y

# Install MySQL client (to connect to RDS)
sudo apt install mysql-client -y

# Verify installations
git --version
apache2 -v
php -v
mysql --version
```

### Step 5: Configure Apache

```bash
# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl

# Configure Apache for your application
sudo nano /etc/apache2/sites-available/ncitad.conf
```

Paste this configuration:

```apache
<VirtualHost *:80>
    ServerAdmin admin@yourdomain.com
    DocumentRoot /var/www/ncitad
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com

    <Directory /var/www/ncitad>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ncitad-error.log
    CustomLog ${APACHE_LOG_DIR}/ncitad-access.log combined
</VirtualHost>
```

Save and exit (Ctrl+X, then Y, then Enter).

```bash
# Enable the site
sudo a2ensite ncitad.conf

# Disable default site
sudo a2dissite 000-default.conf

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### Step 6: Configure PHP

```bash
# Edit PHP configuration
sudo nano /etc/php/8.1/apache2/php.ini
```

Find and modify these lines:

```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

Save and exit.

```bash
# Create PHP error log directory
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php

# Restart Apache
sudo systemctl restart apache2
```

---

## Part 3: Application Deployment

### Step 1: Prepare Application Directory

```bash
# Create web directory
sudo mkdir -p /var/www/ncitad

# Set ownership
sudo chown -R ubuntu:ubuntu /var/www/ncitad

# Set permissions
sudo chmod -R 755 /var/www/ncitad
```

### Step 2: Upload Your Application Files

**Recommended Method: Deploy from GitHub (Best Practice)**

```bash
# On your EC2 server
cd /var/www/ncitad

# Clone your GitHub repository
git clone https://github.com/yourusername/ncitad.git .

# If repository is private, you'll need to authenticate
# Option 1: Use Personal Access Token
# Go to GitHub → Settings → Developer settings → Personal access tokens → Generate new token
# Use: git clone https://YOUR_TOKEN@github.com/yourusername/ncitad.git .

# Option 2: Use SSH key (recommended for frequent deployments)
# Generate SSH key on EC2:
ssh-keygen -t ed25519 -C "your.email@example.com"
# Press Enter for all prompts (default location, no passphrase for automation)

# Display public key and copy it:
cat ~/.ssh/id_ed25519.pub

# Add this key to GitHub:
# GitHub → Settings → SSH and GPG keys → New SSH key → Paste and save

# Clone using SSH:
git clone git@github.com:yourusername/ncitad.git .

# Verify repository status
git status
git log --oneline -5
```

**Alternative Method A: Using SCP/SFTP (Manual Upload)**

If you prefer uploading files manually:

```bash
# From your local machine (in project directory)
# Using PowerShell on Windows:
scp -i ncitad-keypair.pem -r * ubuntu@54.xxx.xxx.xxx:/var/www/ncitad/

# Or using FileZilla (GUI):
# 1. Download FileZilla from https://filezilla-project.org/
# 2. File → Site Manager → New Site
# 3. Protocol: SFTP, Host: [EC2 IP], Port: 22
# 4. User: ubuntu, Key file: ncitad-keypair.pem
# 5. Connect and drag files to /var/www/ncitad/
```

**Alternative Method B: Using Git Archive (Deployment Package)**

```bash
# On your local machine, create deployment package
git archive --format=zip --output=ncitad-deploy.zip HEAD

# Upload to EC2
scp -i ncitad-keypair.pem ncitad-deploy.zip ubuntu@54.xxx.xxx.xxx:/home/ubuntu/

# On EC2 server, extract
cd /var/www/ncitad
sudo apt install unzip -y
unzip /home/ubuntu/ncitad-deploy.zip -d .
rm /home/ubuntu/ncitad-deploy.zip
```

### Step 3: Configure Database Connection

```bash
# Copy the example config file
cd /var/www/ncitad
cp includes/config.example.php includes/config.php

# Edit the actual config file with your credentials
nano includes/config.php
```

Update with your RDS credentials:

```php
<?php
// Database configuration for AWS RDS Production
$host = 'ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com'; // Your actual RDS endpoint
$dbname = 'ncitad';
$username = 'admin';
$password = 'YourActualStrongPassword'; // Your actual RDS password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log error instead of displaying in production
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection error. Please contact administrator.');
}
?>
```

Save and exit (Ctrl+X, then Y, then Enter).

**Similarly, configure email if you're using PHPMailer:**

```bash
# Copy email config example
cp includes/email_config.example.php includes/email_config.php

# Edit with your SMTP credentials
nano includes/email_config.php
```

**Important: Verify config files are NOT tracked by Git:**

```bash
# Check Git status - config.php should NOT appear in changes
git status

# If config.php appears, it means .gitignore isn't working
# Make sure .gitignore includes:
# includes/config.php
# includes/email_config.php
```

### Step 4: Set Correct Permissions

```bash
# Set ownership to Apache user
sudo chown -R www-data:www-data /var/www/ncitad

# Set directory permissions
sudo find /var/www/ncitad -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/ncitad -type f -exec chmod 644 {} \;

# Make uploads directory writable (if you have one)
sudo chmod -R 775 /var/www/ncitad/uploads
```

### Step 5: Test Database Connection

```bash
# Test MySQL connection from EC2 to RDS
mysql -h ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com -u admin -p ncitad

# If successful, you'll get MySQL prompt
# Run: SHOW TABLES;
# Then exit
```

### Step 6: Update RDS Security Group

1. Go to **EC2** → **Security Groups**
2. Find `ncitad-db-sg`
3. Edit inbound rules
4. Remove "My IP" rule
5. Add new rule:
   - Type: **MySQL/Aurora**
   - Port: **3306**
   - Source: **Custom** → Select `ncitad-web-sg` (your web server security group)
   - Description: `Access from web server only`
6. Save rules

### Step 7: Test Your Application

1. Get your EC2 Public IP from EC2 console
2. Open browser: `http://[Your-EC2-Public-IP]`
3. You should see your NCITAD login page
4. Try logging in with existing credentials
5. Test creating a ticket, managing users, etc.

---

## Part 4: Domain and SSL Setup

### Step 1: Point Domain to EC2

**If you have a domain (e.g., ncitad.com):**

1. Go to your domain registrar (GoDaddy, Namecheap, etc.)
2. Find DNS management
3. Create/Update **A Record**:
   - Name/Host: `@` (root domain) or `www`
   - Type: **A**
   - Value/Points to: **[Your EC2 Public IP]**
   - TTL: 3600 (or default)
4. Save changes (DNS propagation takes 1-48 hours)

**Alternative: Use AWS Route 53**

1. Go to **Route 53** service
2. Create hosted zone for your domain
3. Update nameservers at your registrar
4. Create A record pointing to EC2 IP

### Step 2: Allocate Elastic IP (Recommended)

**Why?** EC2 public IP changes if you stop/start instance. Elastic IP is permanent.

```bash
# On EC2 console
1. Go to EC2 → Elastic IPs
2. Click "Allocate Elastic IP address"
3. Click "Allocate"
4. Select the new Elastic IP
5. Actions → Associate Elastic IP address
6. Select your instance
7. Click "Associate"
```

Update your domain A record with the new Elastic IP.

### Step 3: Install SSL Certificate (Let's Encrypt - Free)

```bash
# On your EC2 server
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get SSL certificate (replace with your domain)
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Follow prompts:
# - Enter email address
# - Agree to terms
# - Choose whether to redirect HTTP to HTTPS (recommended: Yes)

# Test auto-renewal
sudo certbot renew --dry-run
```

Your site is now accessible via `https://yourdomain.com`!

---

## Part 5: Deployment Automation with Git

### Setting Up Git-Based Deployment Workflow

This section covers how to streamline your deployment process using Git.

### Option 1: Manual Git Pull Deployment (Simple)

```bash
# On EC2 server, navigate to application directory
cd /var/www/ncitad

# Pull latest changes from GitHub
git pull origin main

# If you made local changes (like config.php), you might get conflicts
# Stash local changes, pull, then restore:
git stash
git pull origin main
git stash pop

# Set correct permissions after pull
sudo chown -R www-data:www-data /var/www/ncitad
sudo find /var/www/ncitad -type d -exec chmod 755 {} \;
sudo find /var/www/ncitad -type f -exec chmod 644 {} \;

# Restart Apache to apply changes
sudo systemctl restart apache2
```

### Option 2: Deployment Script (Recommended)

Create a deployment script for consistent deployments:

```bash
# Create deployment script
sudo nano /usr/local/bin/deploy-ncitad.sh
```

Add the following content:

```bash
#!/bin/bash

# NCITAD Deployment Script
# This script safely deploys updates from GitHub

set -e  # Exit on any error

echo "======================================"
echo "NCITAD Deployment Script"
echo "Started at: $(date)"
echo "======================================"

# Configuration
APP_DIR="/var/www/ncitad"
BRANCH="main"
BACKUP_DIR="/home/ubuntu/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Navigate to application directory
cd $APP_DIR

# Step 1: Backup current version
echo "[1/6] Creating backup..."
tar -czf $BACKUP_DIR/ncitad-pre-deploy-$TIMESTAMP.tar.gz \
    --exclude='node_modules' \
    --exclude='.git' \
    -C /var/www ncitad
echo "✓ Backup created: ncitad-pre-deploy-$TIMESTAMP.tar.gz"

# Step 2: Stash any local changes (like config.php)
echo "[2/6] Stashing local changes..."
git stash
echo "✓ Local changes stashed"

# Step 3: Fetch latest changes
echo "[3/6] Fetching updates from GitHub..."
git fetch origin $BRANCH
echo "✓ Updates fetched"

# Step 4: Show what's going to change
echo "[4/6] Changes to be deployed:"
git log HEAD..origin/$BRANCH --oneline --no-decorate
CHANGES_COUNT=$(git rev-list HEAD..origin/$BRANCH --count)

if [ "$CHANGES_COUNT" -eq 0 ]; then
    echo "✓ No new changes to deploy. Already up to date!"
    git stash pop 2>/dev/null || true
    exit 0
fi

# Step 5: Pull latest code
echo "[5/6] Pulling latest code..."
git pull origin $BRANCH
echo "✓ Code updated"

# Step 6: Restore local configuration
echo "[6/6] Restoring local configuration..."
git stash pop 2>/dev/null || echo "No stash to restore"
echo "✓ Configuration restored"

# Set correct permissions
echo "Setting file permissions..."
sudo chown -R www-data:www-data $APP_DIR
sudo find $APP_DIR -type d -exec chmod 755 {} \;
sudo find $APP_DIR -type f -exec chmod 644 {} \;
echo "✓ Permissions set"

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✓ Apache restarted"

# Clean old backups (keep last 10)
echo "Cleaning old backups..."
cd $BACKUP_DIR
ls -t ncitad-pre-deploy-*.tar.gz | tail -n +11 | xargs -r rm
echo "✓ Old backups cleaned"

echo "======================================"
echo "Deployment completed successfully!"
echo "Deployed $CHANGES_COUNT commit(s)"
echo "Finished at: $(date)"
echo "======================================"
```

Save and make executable:

```bash
sudo chmod +x /usr/local/bin/deploy-ncitad.sh
```

**Usage:**

```bash
# Deploy latest changes
sudo /usr/local/bin/deploy-ncitad.sh
```

### Option 3: GitHub Webhook Auto-Deployment (Advanced)

Automatically deploy when you push to GitHub:

**Step 1: Create webhook receiver script**

```bash
sudo nano /var/www/webhook-receiver.php
```

Add:

```php
<?php
// GitHub Webhook Receiver for NCITAD Auto-Deployment

// Security: Verify webhook secret
$secret = 'your-webhook-secret-key'; // Change this!
$headers = getallheaders();
$signature = $headers['X-Hub-Signature-256'] ?? '';

$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($hash, $signature)) {
    http_response_code(403);
    die('Forbidden: Invalid signature');
}

// Parse payload
$data = json_decode($payload, true);

// Only deploy on push to main branch
if ($data['ref'] === 'refs/heads/main') {
    // Log deployment
    $log = fopen('/var/log/ncitad-deploy.log', 'a');
    fwrite($log, date('Y-m-d H:i:s') . " - Deployment triggered by: " . $data['pusher']['name'] . "\n");
    fclose($log);
    
    // Execute deployment script
    shell_exec('sudo /usr/local/bin/deploy-ncitad.sh >> /var/log/ncitad-deploy.log 2>&1 &');
    
    http_response_code(200);
    echo json_encode(['status' => 'Deployment initiated']);
} else {
    http_response_code(200);
    echo json_encode(['status' => 'Ignored - not main branch']);
}
?>
```

**Step 2: Configure webhook on GitHub**

1. Go to your GitHub repository
2. **Settings** → **Webhooks** → **Add webhook**
3. Configure:
   - Payload URL: `http://your-server-ip/webhook-receiver.php`
   - Content type: `application/json`
   - Secret: `your-webhook-secret-key` (same as in script)
   - Events: Select "Just the push event"
   - Active: ✓ Checked
4. Click **Add webhook**

**Step 3: Allow Apache to run deployment script**

```bash
# Edit sudoers file
sudo visudo
```

Add line at the end:

```
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/deploy-ncitad.sh
```

Now every time you push to main branch, it auto-deploys! 🚀

### Git Workflow Best Practices

**Development Workflow:**

```bash
# On your local machine

# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Make changes and test locally
# ... edit files ...

# 3. Commit changes
git add .
git commit -m "Add new feature: description"

# 4. Push to GitHub
git push origin feature/new-feature

# 5. Create Pull Request on GitHub
# Review changes, get approval

# 6. Merge to main branch
# On GitHub: Merge pull request

# 7. Deploy to production
# SSH to EC2 and run:
sudo /usr/local/bin/deploy-ncitad.sh
```

**Hotfix Workflow:**

```bash
# For urgent production fixes

# 1. Create hotfix branch from main
git checkout main
git pull origin main
git checkout -b hotfix/urgent-fix

# 2. Make fix and test
# ... fix the issue ...

# 3. Commit and push
git add .
git commit -m "Hotfix: Fix critical bug in login"
git push origin hotfix/urgent-fix

# 4. Merge to main (fast-track)
git checkout main
git merge hotfix/urgent-fix
git push origin main

# 5. Deploy immediately
# SSH to EC2:
sudo /usr/local/bin/deploy-ncitad.sh

# 6. Clean up
git branch -d hotfix/urgent-fix
git push origin --delete hotfix/urgent-fix
```

### Rollback Procedure

If deployment causes issues, rollback quickly:

```bash
# On EC2 server

# Option 1: Rollback using Git
cd /var/www/ncitad

# View recent commits
git log --oneline -10

# Rollback to specific commit (replace COMMIT_HASH)
git reset --hard COMMIT_HASH
sudo systemctl restart apache2

# Option 2: Restore from backup
cd /home/ubuntu/backups

# List backups
ls -lt ncitad-pre-deploy-*.tar.gz

# Restore specific backup
sudo rm -rf /var/www/ncitad/*
sudo tar -xzf ncitad-pre-deploy-20251102_143000.tar.gz -C /var/www/
sudo chown -R www-data:www-data /var/www/ncitad
sudo systemctl restart apache2
```

### Monitoring Deployments

```bash
# View deployment log
tail -f /var/log/ncitad-deploy.log

# View recent deployments
grep "Deployment" /var/log/ncitad-deploy.log | tail -20

# Check Git log on server
cd /var/www/ncitad
git log --oneline -10
git status
```

---

## Part 6: Security Hardening

### Step 1: Configure Firewall (UFW)

```bash
# Enable UFW
sudo ufw enable

# Allow SSH (important - do this first!)
sudo ufw allow 22/tcp

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Check status
sudo ufw status
```

### Step 2: Secure SSH Access

```bash
# Edit SSH config
sudo nano /etc/ssh/sshd_config
```

Update these settings:

```
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 22
```

Save and restart SSH:

```bash
sudo systemctl restart sshd
```

### Step 3: Keep System Updated

```bash
# Enable automatic security updates
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades
```

### Step 4: Configure PHP Security

Already done in php.ini:
- `display_errors = Off` ✓
- `log_errors = On` ✓
- File upload limits set ✓

### Step 5: Protect Sensitive Files

```bash
# Create .htaccess in includes directory
sudo nano /var/www/ncitad/includes/.htaccess
```

Add:

```apache
Order deny,allow
Deny from all
```

### Step 6: Regular Backups

```bash
# Create backup script
sudo nano /usr/local/bin/backup-ncitad.sh
```

Paste:

```bash
#!/bin/bash
BACKUP_DIR="/home/ubuntu/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup application files
tar -czf $BACKUP_DIR/ncitad-files-$DATE.tar.gz /var/www/ncitad

# Backup database (replace with your RDS details)
mysqldump -h ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com \
          -u admin -p'YourPassword' ncitad > $BACKUP_DIR/ncitad-db-$DATE.sql

# Keep only last 7 days of backups
find $BACKUP_DIR -name "ncitad-*" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Make executable:

```bash
sudo chmod +x /usr/local/bin/backup-ncitad.sh
```

Add to crontab (daily at 2 AM):

```bash
crontab -e
```

Add line:

```
0 2 * * * /usr/local/bin/backup-ncitad.sh >> /var/log/backup-ncitad.log 2>&1
```

---

## Part 7: Backup and Monitoring

### Enable RDS Automated Backups

Already configured during RDS setup (7-day retention).

To manually create snapshot:
1. Go to RDS → Databases
2. Select your database
3. Actions → Take snapshot
4. Enter snapshot name
5. Click "Take snapshot"

### Enable CloudWatch Monitoring

1. Go to **CloudWatch** service
2. Create Dashboard: `NCITAD-Monitoring`
3. Add widgets:
   - EC2 CPU Utilization
   - EC2 Network In/Out
   - RDS CPU Utilization
   - RDS Database Connections

### Set Up Alarms

```bash
# High CPU alarm
1. CloudWatch → Alarms → Create alarm
2. Select metric: EC2 → Per-Instance Metrics → CPUUtilization
3. Threshold: Greater than 80%
4. Actions: Send notification to email
```

### Enable AWS Backup (Recommended)

1. Go to **AWS Backup** service
2. Create backup plan:
   - Name: `NCITAD-Daily-Backup`
   - Rule: Daily, 35-day retention
3. Assign resources:
   - RDS database
   - EC2 instance (via tags)

---

## Troubleshooting

### Issue: Can't connect to EC2 via SSH

**Solution:**
```bash
# Check security group allows SSH from your IP
# Verify key file permissions:
chmod 400 ncitad-keypair.pem

# Try verbose mode to see errors:
ssh -v -i ncitad-keypair.pem ubuntu@[EC2-IP]
```

### Issue: Can't access website

**Solution:**
```bash
# Check Apache status
sudo systemctl status apache2

# Check Apache error logs
sudo tail -f /var/log/apache2/ncitad-error.log

# Check firewall
sudo ufw status

# Verify security group allows HTTP (80) and HTTPS (443)
```

### Issue: Database connection error

**Solution:**
```bash
# Test connection from EC2
mysql -h [RDS-Endpoint] -u admin -p

# Check RDS security group allows MySQL from EC2 security group
# Verify credentials in config.php
# Check RDS status in console
```

### Issue: 500 Internal Server Error

**Solution:**
```bash
# Check PHP error log
sudo tail -f /var/log/php/error.log

# Check Apache error log
sudo tail -f /var/log/apache2/ncitad-error.log

# Verify file permissions
ls -la /var/www/ncitad

# Check PHP syntax
php -l /var/www/ncitad/includes/config.php
```

### Issue: Session data not persisting

**Solution:**
```bash
# Check session directory permissions
sudo chmod 1733 /var/lib/php/sessions

# Or set custom session path in config
# Add to config.php:
session_save_path('/var/www/ncitad/sessions');

# Create and set permissions
sudo mkdir -p /var/www/ncitad/sessions
sudo chown www-data:www-data /var/www/ncitad/sessions
sudo chmod 700 /var/www/ncitad/sessions
```

---

## Cost Optimization

### Free Tier Usage (First 12 Months)

- EC2 t2.micro: 750 hours/month
- RDS db.t2.micro: 750 hours/month
- 20 GB EBS storage
- 5 GB snapshots
- 15 GB data transfer out

### Cost-Saving Tips

1. **Use Reserved Instances** (after free tier):
   - 30-75% discount for 1-3 year commitment
   
2. **Stop EC2 when not in use** (development):
   ```bash
   # Stop instance (keeps EBS, releases compute)
   # Only pay for storage
   ```

3. **Use RDS Single-AZ** (not Multi-AZ) for development

4. **Enable RDS auto-scaling** for storage only when needed

5. **Delete old snapshots** regularly

6. **Use CloudFront CDN** for static assets (optional)

### Monthly Cost Estimate (After Free Tier)

| Service | Type | Monthly Cost |
|---------|------|--------------|
| EC2 t2.small | Web Server | $16.82 |
| EBS 20GB | Storage | $2.00 |
| Elastic IP | Static IP | $3.60 |
| RDS db.t3.micro | Database | $12.41 |
| RDS Storage 20GB | Database Storage | $2.30 |
| Data Transfer | 10GB/month | $0.90 |
| **Total** | | **~$38/month** |

---

## Post-Deployment Checklist

- [ ] Database migrated and verified
- [ ] Web server running and accessible
- [ ] Application functioning correctly
- [ ] All features tested (login, tickets, users, etc.)
- [ ] SSL certificate installed (HTTPS working)
- [ ] Domain pointing to server
- [ ] Backups configured and tested
- [ ] Monitoring and alerts set up
- [ ] Security groups properly configured
- [ ] Firewall (UFW) enabled
- [ ] SSH key-only authentication
- [ ] Auto-updates enabled
- [ ] Error logging configured
- [ ] Documentation updated with AWS details

---

## Quick Reference

### Important Endpoints

```
EC2 Public IP: 54.xxx.xxx.xxx
EC2 Elastic IP: xx.xxx.xxx.xxx
RDS Endpoint: ncitad-database.xxxxxx.ap-southeast-1.rds.amazonaws.com
Application URL: https://yourdomain.com
```

### Key File Locations

```
Application: /var/www/ncitad/
Git Repository: /var/www/ncitad/.git/
Config: /var/www/ncitad/includes/config.php
Config Template: /var/www/ncitad/includes/config.example.php
Apache Config: /etc/apache2/sites-available/ncitad.conf
PHP Config: /etc/php/8.1/apache2/php.ini
Apache Logs: /var/log/apache2/
PHP Logs: /var/log/php/error.log
Deployment Log: /var/log/ncitad-deploy.log
Backups: /home/ubuntu/backups/
SSL Cert: /etc/letsencrypt/live/yourdomain.com/
Deployment Script: /usr/local/bin/deploy-ncitad.sh
Backup Script: /usr/local/bin/backup-ncitad.sh
```

### Useful Commands

```bash
# Git Operations
git status                          # Check repository status
git log --oneline -10               # View recent commits
git pull origin main                # Pull latest changes
git branch -a                       # List all branches
git diff HEAD~1                     # See what changed in last commit

# Deployment
sudo /usr/local/bin/deploy-ncitad.sh    # Deploy latest code
tail -f /var/log/ncitad-deploy.log      # Watch deployment log

# Apache Management
sudo systemctl restart apache2      # Restart Apache
sudo systemctl status apache2       # Check Apache status
sudo tail -f /var/log/apache2/ncitad-error.log   # View Apache errors

# PHP Debugging
sudo tail -f /var/log/php/error.log    # View PHP errors
php -v                                  # Check PHP version

# Database Operations
mysql -h [RDS-Endpoint] -u admin -p ncitad    # Connect to database
# Inside MySQL:
SHOW TABLES;                        # List tables
SELECT COUNT(*) FROM concerns;      # Check data

# System Monitoring
df -h                              # Check disk space
free -m                            # Check memory usage
top                                # View running processes
sudo systemctl status              # View all services

# Backups
/usr/local/bin/backup-ncitad.sh    # Manual backup
ls -lh /home/ubuntu/backups/       # List backups
cat /home/ubuntu/backups/manifest-*.txt  # View backup info

# SSL Certificates
sudo certbot certificates          # Check SSL status
sudo certbot renew                 # Renew SSL certificate
sudo certbot renew --dry-run       # Test renewal

# Security
sudo ufw status                    # Check firewall
sudo fail2ban-client status        # Check intrusion prevention (if installed)

# Quick Rollback
cd /var/www/ncitad && git log --oneline -5    # See recent commits
git reset --hard HEAD~1                        # Rollback one commit
git reset --hard COMMIT_HASH                   # Rollback to specific commit
sudo systemctl restart apache2                 # Apply changes
```

---

## Support and Resources

### AWS Documentation
- EC2: https://docs.aws.amazon.com/ec2/
- RDS: https://docs.aws.amazon.com/rds/
- Route 53: https://docs.aws.amazon.com/route53/

### Tutorials
- AWS Free Tier: https://aws.amazon.com/free/
- Let's Encrypt: https://letsencrypt.org/
- Ubuntu Server: https://ubuntu.com/server/docs

### Community
- AWS Forums: https://forums.aws.amazon.com/
- Stack Overflow: https://stackoverflow.com/questions/tagged/aws

---

## Next Steps

After successful deployment:

1. **Performance Optimization**:
   - Enable PHP OPcache
   - Implement Redis/Memcached for sessions
   - Set up CloudFront CDN
   - Optimize database queries

2. **Advanced Features**:
   - Set up CI/CD with AWS CodePipeline
   - Implement auto-scaling
   - Add load balancer for high availability
   - Configure AWS S3 for file uploads

3. **Monitoring**:
   - Set up AWS CloudWatch dashboards
   - Configure detailed monitoring
   - Implement application performance monitoring (APM)
   - Set up log aggregation

4. **Disaster Recovery**:
   - Create multi-AZ RDS deployment
   - Set up cross-region backups
   - Document recovery procedures
   - Test backup restoration

---

## Congratulations! 🎉

You've successfully deployed NCITAD to AWS! Your application is now:
- ✅ Highly available
- ✅ Scalable
- ✅ Secure
- ✅ Backed up
- ✅ Monitored

For questions or issues, refer to the troubleshooting section or AWS documentation.

**Good luck with your deployment!** 🚀
