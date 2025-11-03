# EC2 Git Update Guide - NCITAD

A practical guide for updating your NCITAD application on AWS EC2 using Git.

---

## Table of Contents

1. [Quick Start](#quick-start)
2. [Prerequisites](#prerequisites)
3. [Update Methods](#update-methods)
4. [Step-by-Step Update Process](#step-by-step-update-process)
5. [Automated Deployment](#automated-deployment)
6. [Rollback Procedures](#rollback-procedures)
7. [Troubleshooting](#troubleshooting)
8. [Best Practices](#best-practices)

---

## Quick Start

**For experienced users, the fastest way to update:**

```bash
# On your local machine
git add .
git commit -m "Your update description"
git push origin main

# On EC2 server
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip
cd /var/www/ncitad
git pull origin main
sudo systemctl restart apache2
```

Done! ✅

---

## Prerequisites

### What You Need

- ✅ **SSH access to EC2**: Your `.pem` key file
- ✅ **Git configured on EC2**: Repository already cloned
- ✅ **GitHub credentials**: Access to your repository
- ✅ **Local changes committed**: All work saved and pushed to GitHub

### Verify Your Setup

**On your local machine:**
```bash
# Check you're in the right directory
cd c:\xampp\htdocs\ncitad

# Verify Git status
git status

# Check remote repository
git remote -v
# Should show: origin  https://github.com/yourusername/ncitad.git
```

**On your EC2 server:**
```bash
# Connect to EC2
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip

# Check application directory
cd /var/www/ncitad
ls -la

# Verify Git repository
git status
git remote -v
```

---

## Update Methods

### Method 1: Manual Pull (Recommended for Beginners)

**Pros:**
- ✅ Full control over each step
- ✅ Easy to understand
- ✅ Good for learning

**Cons:**
- ❌ Requires manual SSH login
- ❌ More steps involved

**Best for:** Small updates, learning, troubleshooting

---

### Method 2: Deployment Script (Recommended for Production)

**Pros:**
- ✅ Automated backup before update
- ✅ Consistent deployment process
- ✅ Easy rollback if issues occur
- ✅ Maintains local configuration

**Cons:**
- ❌ Requires initial setup

**Best for:** Regular updates, production environment, team collaboration

---

### Method 3: GitHub Webhook (Advanced)

**Pros:**
- ✅ Fully automated
- ✅ Updates on every push
- ✅ No manual intervention

**Cons:**
- ❌ Complex setup
- ❌ Requires webhook configuration

**Best for:** Continuous deployment, advanced users

---

## Step-by-Step Update Process

### Method 1: Manual Pull Update

#### Step 1: Prepare Your Changes Locally

```bash
# On your local machine (Windows PowerShell)
cd c:\xampp\htdocs\ncitad

# Check what files you've modified
git status

# View your changes
git diff

# Add files to staging
git add .

# Or add specific files only
git add admin/dashboard.php
git add includes/config.example.php

# Commit your changes
git commit -m "Update dashboard with new statistics"

# Push to GitHub
git push origin main
```

**💡 Tip:** Write clear commit messages that explain what you changed and why.

#### Step 2: Connect to Your EC2 Server

```bash
# Using PowerShell on Windows
ssh -i C:\path\to\ncitad-keypair.pem ubuntu@your-ec2-ip

# Example:
ssh -i C:\Users\YourName\Downloads\ncitad-keypair.pem ubuntu@54.123.45.67
```

**If you get "WARNING: UNPROTECTED PRIVATE KEY FILE":**
```powershell
# Fix permissions in PowerShell
icacls C:\path\to\ncitad-keypair.pem /inheritance:r
icacls C:\path\to\ncitad-keypair.pem /grant:r "$($env:USERNAME):(R)"
```

#### Step 3: Navigate to Application Directory

```bash
# Once connected to EC2
cd /var/www/ncitad

# Verify you're in the right place
pwd
# Should show: /var/www/ncitad

# Check current status
git status
```

#### Step 4: Check for Updates

```bash
# Fetch latest changes from GitHub (doesn't apply them yet)
git fetch origin main

# See what's going to change
git log HEAD..origin/main --oneline

# See detailed differences
git diff HEAD..origin/main
```

**Example output:**
```
abc1234 Update dashboard with new statistics
def5678 Fix login bug
```

If you see commits listed, there are updates available. If nothing appears, you're already up to date!

#### Step 5: Backup Current Configuration (Important!)

```bash
# Backup your config.php (it has sensitive data)
cp includes/config.php ~/config-backup.php
cp includes/email_config.php ~/email-config-backup.php 2>/dev/null || true

# Verify backups
ls -lh ~/config-backup.php
```

#### Step 6: Stash Local Changes

```bash
# Save any local modifications (like config files)
git stash

# Verify stash was created
git stash list
# Should show: stash@{0}: WIP on main: commit message
```

#### Step 7: Pull Latest Code

```bash
# Pull updates from GitHub
git pull origin main
```

**Expected output:**
```
Updating abc1234..def5678
Fast-forward
 admin/dashboard.php | 45 ++++++++++++++++++++++++++++-
 1 file changed, 44 insertions(+), 1 deletion(-)
```

#### Step 8: Restore Local Configuration

```bash
# Restore your local changes (config files)
git stash pop

# If there are conflicts, you'll see a message
# Resolve conflicts manually if needed
```

**If you get merge conflicts:**
```bash
# Check which files have conflicts
git status

# Edit conflicted files manually
nano includes/config.php

# Look for conflict markers:
# <<<<<<< Updated upstream
# ... new code from GitHub ...
# =======
# ... your local changes ...
# >>>>>>> Stashed changes

# Remove markers and keep the correct version
# Usually keep your local config.php as-is

# After resolving
git add includes/config.php
```

**Alternative (if stash pop fails):**
```bash
# Just restore from backup
cp ~/config-backup.php includes/config.php
cp ~/email-config-backup.php includes/email_config.php 2>/dev/null || true
```

#### Step 9: Set Correct Permissions

```bash
# Ensure Apache can read the files
sudo chown -R www-data:www-data /var/www/ncitad

# Set directory permissions
sudo find /var/www/ncitad -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/ncitad -type f -exec chmod 644 {} \;

# Make uploads directory writable (if exists)
sudo chmod -R 775 /var/www/ncitad/uploads 2>/dev/null || true
```

#### Step 10: Restart Apache

```bash
# Restart Apache to apply changes
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# Should show: active (running)
```

#### Step 11: Test Your Application

**In your web browser:**
1. Visit your application: `http://your-ec2-ip` or `https://yourdomain.com`
2. Test the updated features
3. Check login works
4. Verify database connections
5. Test any new functionality

**Check for errors:**
```bash
# On EC2, monitor error logs while testing
sudo tail -f /var/log/apache2/ncitad-error.log

# In another terminal, watch PHP errors
sudo tail -f /var/log/php/error.log
```

#### Step 12: Verify Update Success

```bash
# Check current commit
git log --oneline -5

# Check Git status
git status
# Should show: Your branch is up to date with 'origin/main'

# Verify specific files
git show HEAD:admin/dashboard.php | head -20
```

**🎉 Update Complete!**

---

### Method 2: Using Deployment Script (Automated)

This method automates steps 3-10 above with proper error handling.

#### Initial Setup (One-Time)

```bash
# Connect to EC2
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip

# Create deployment script
sudo nano /usr/local/bin/deploy-ncitad.sh
```

**Paste this script:**

```bash
#!/bin/bash

# NCITAD Deployment Script
# Safely deploys updates from GitHub with automatic backup

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

# Create backup directory
mkdir -p $BACKUP_DIR

# Navigate to application
cd $APP_DIR

# Step 1: Create backup
echo "[1/7] Creating backup..."
tar -czf $BACKUP_DIR/ncitad-backup-$TIMESTAMP.tar.gz \
    --exclude='.git' \
    -C /var/www ncitad
echo "✓ Backup created: $BACKUP_DIR/ncitad-backup-$TIMESTAMP.tar.gz"

# Step 2: Backup config files
echo "[2/7] Backing up configuration files..."
cp includes/config.php /tmp/config-backup.php 2>/dev/null || true
cp includes/email_config.php /tmp/email-config-backup.php 2>/dev/null || true
echo "✓ Config files backed up"

# Step 3: Fetch updates
echo "[3/7] Fetching updates from GitHub..."
git fetch origin $BRANCH
echo "✓ Updates fetched"

# Step 4: Check for changes
echo "[4/7] Checking for updates..."
CHANGES_COUNT=$(git rev-list HEAD..origin/$BRANCH --count)

if [ "$CHANGES_COUNT" -eq 0 ]; then
    echo "✓ Already up to date! No deployment needed."
    rm /tmp/config-backup.php 2>/dev/null || true
    rm /tmp/email-config-backup.php 2>/dev/null || true
    exit 0
fi

echo "Found $CHANGES_COUNT new commit(s) to deploy:"
git log HEAD..origin/$BRANCH --oneline --no-decorate

# Step 5: Stash local changes
echo "[5/7] Saving local changes..."
git stash --include-untracked
echo "✓ Local changes saved"

# Step 6: Pull latest code
echo "[6/7] Pulling latest code..."
git pull origin $BRANCH
echo "✓ Code updated successfully"

# Step 7: Restore configuration
echo "[7/7] Restoring configuration..."
cp /tmp/config-backup.php includes/config.php 2>/dev/null || true
cp /tmp/email-config-backup.php includes/email_config.php 2>/dev/null || true
rm /tmp/config-backup.php 2>/dev/null || true
rm /tmp/email-config-backup.php 2>/dev/null || true

# Restore from stash if needed
git stash list | grep -q "stash@{0}" && git checkout --theirs includes/config*.php 2>/dev/null || true
git stash drop 2>/dev/null || true

echo "✓ Configuration restored"

# Set correct permissions
echo "Setting file permissions..."
sudo chown -R www-data:www-data $APP_DIR
sudo find $APP_DIR -type d -exec chmod 755 {} \;
sudo find $APP_DIR -type f -exec chmod 644 {} \;
sudo chmod -R 775 $APP_DIR/uploads 2>/dev/null || true
echo "✓ Permissions set"

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✓ Apache restarted"

# Clean old backups (keep last 10)
echo "Cleaning old backups..."
cd $BACKUP_DIR
ls -t ncitad-backup-*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm
echo "✓ Cleanup complete"

echo "======================================"
echo "✓ Deployment completed successfully!"
echo "Deployed $CHANGES_COUNT commit(s)"
echo "Finished at: $(date)"
echo "======================================"
```

**Make it executable:**

```bash
sudo chmod +x /usr/local/bin/deploy-ncitad.sh

# Allow Apache user to run it (for webhooks)
sudo visudo
# Add this line at the end:
www-data ALL=(ALL) NOPASSWD: /usr/local/bin/deploy-ncitad.sh
```

#### Using the Deployment Script

**From now on, to update your EC2:**

```bash
# 1. On local machine: Commit and push changes
git add .
git commit -m "Your changes"
git push origin main

# 2. SSH to EC2
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip

# 3. Run deployment script
sudo /usr/local/bin/deploy-ncitad.sh
```

**That's it!** The script handles everything automatically.

---

## Automated Deployment

### Option 1: Create Deployment Alias (Quick Access)

```bash
# On EC2, add alias to your profile
echo "alias deploy='sudo /usr/local/bin/deploy-ncitad.sh'" >> ~/.bashrc
source ~/.bashrc

# Now you can just type:
deploy
```

### Option 2: Schedule Regular Updates (Cron)

```bash
# Edit crontab
crontab -e

# Add line to deploy daily at 3 AM (if there are updates)
0 3 * * * /usr/local/bin/deploy-ncitad.sh >> /var/log/deploy-ncitad.log 2>&1
```

### Option 3: GitHub Webhook (Auto-Deploy on Push)

See the [AWS_DEPLOYMENT_GUIDE.md](AWS_DEPLOYMENT_GUIDE.md) Part 5, Option 3 for webhook setup.

---

## Rollback Procedures

### Quick Rollback: Git Reset

If the latest update causes issues:

```bash
# SSH to EC2
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip

cd /var/www/ncitad

# View recent commits
git log --oneline -10

# Rollback to previous commit
git reset --hard HEAD~1

# Or rollback to specific commit
git reset --hard abc1234

# Restart Apache
sudo systemctl restart apache2

# Test application
curl -I http://localhost
```

### Full Rollback: Restore from Backup

```bash
# List available backups
ls -lth /home/ubuntu/backups/

# Choose a backup to restore
BACKUP_FILE="ncitad-backup-20251103_120000.tar.gz"

# Stop Apache
sudo systemctl stop apache2

# Remove current application
sudo rm -rf /var/www/ncitad/*

# Restore backup
sudo tar -xzf /home/ubuntu/backups/$BACKUP_FILE -C /var/www/

# Set permissions
sudo chown -R www-data:www-data /var/www/ncitad
sudo find /var/www/ncitad -type d -exec chmod 755 {} \;
sudo find /var/www/ncitad -type f -exec chmod 644 {} \;

# Start Apache
sudo systemctl start apache2

# Test application
curl -I http://localhost
```

### Rollback and Push to GitHub

If you want to permanently revert GitHub to a previous state:

```bash
# On EC2 or local machine
git log --oneline -10

# Reset to specific commit
git reset --hard abc1234

# Force push to GitHub (⚠️ use with caution!)
git push origin main --force

# Update EC2 if you did this locally
ssh -i ncitad-keypair.pem ubuntu@your-ec2-ip
cd /var/www/ncitad
git pull origin main
sudo systemctl restart apache2
```

---

## Troubleshooting

### Issue: "Permission denied" when pulling

**Error:**
```
fatal: could not create work tree dir 'ncitad': Permission denied
```

**Solution:**
```bash
# Fix ownership
sudo chown -R ubuntu:ubuntu /var/www/ncitad

# Then try again
git pull origin main
```

---

### Issue: "Authentication failed" when pulling

**Error:**
```
fatal: Authentication failed for 'https://github.com/...'
```

**Solution Option 1: Use Personal Access Token**

```bash
# On GitHub: Settings → Developer settings → Personal access tokens → Generate new token
# Give it 'repo' permissions, copy the token

# On EC2, update remote URL
git remote set-url origin https://YOUR_TOKEN@github.com/yourusername/ncitad.git

# Or configure credential helper
git config --global credential.helper store
git pull origin main
# Enter username and token when prompted
```

**Solution Option 2: Use SSH Key (Recommended)**

```bash
# On EC2, generate SSH key
ssh-keygen -t ed25519 -C "your.email@example.com"
# Press Enter for all prompts

# Display public key
cat ~/.ssh/id_ed25519.pub

# Copy output and add to GitHub:
# GitHub → Settings → SSH and GPG keys → New SSH key

# Change Git remote to SSH
git remote set-url origin git@github.com:yourusername/ncitad.git

# Test
git pull origin main
```

---

### Issue: Merge conflicts during pull

**Error:**
```
CONFLICT (content): Merge conflict in includes/config.php
```

**Solution:**

```bash
# Option 1: Keep your local version (for config files)
git checkout --ours includes/config.php
git add includes/config.php
git commit -m "Resolve conflict - keep local config"

# Option 2: Keep GitHub version
git checkout --theirs some_file.php
git add some_file.php
git commit -m "Resolve conflict - accept remote changes"

# Option 3: Manual resolution
nano includes/config.php
# Remove conflict markers: <<<<<<<, =======, >>>>>>>
# Keep the version you want
git add includes/config.php
git commit -m "Resolve merge conflict"
```

---

### Issue: Changes not appearing after pull

**Checklist:**

```bash
# 1. Verify pull was successful
git log --oneline -5

# 2. Check if you're on the right branch
git branch
# Should show: * main

# 3. Verify Apache is using the right directory
cat /etc/apache2/sites-available/ncitad.conf | grep DocumentRoot
# Should show: DocumentRoot /var/www/ncitad

# 4. Clear PHP OpCache (if enabled)
sudo systemctl restart apache2

# 5. Clear browser cache
# Ctrl+Shift+R (hard refresh)

# 6. Check file permissions
ls -la /var/www/ncitad/

# 7. Check for PHP errors
sudo tail -f /var/log/php/error.log
```

---

### Issue: Deployment script fails

**Check logs:**

```bash
# View deployment log
tail -50 /var/log/deploy-ncitad.log

# Check script exists and is executable
ls -l /usr/local/bin/deploy-ncitad.sh

# Run with verbose output
bash -x /usr/local/bin/deploy-ncitad.sh
```

---

### Issue: "error: Your local changes would be overwritten"

**Error:**
```
error: Your local changes to the following files would be overwritten by merge:
    some_file.php
```

**Solution:**

```bash
# See what changed locally
git diff some_file.php

# Option 1: Stash changes and pull
git stash
git pull origin main
git stash pop

# Option 2: Discard local changes (⚠️ be careful!)
git checkout -- some_file.php
git pull origin main

# Option 3: Commit local changes first
git add some_file.php
git commit -m "Local changes before pull"
git pull origin main
```

---

## Best Practices

### 1. Always Test Locally First

```bash
# On local machine
# Test thoroughly on http://localhost/ncitad
# Only push when everything works
```

### 2. Write Meaningful Commit Messages

```bash
# ❌ Bad
git commit -m "update"
git commit -m "fix"

# ✅ Good
git commit -m "Fix login redirect issue for admin users"
git commit -m "Add pagination to concerns list (max 20 per page)"
git commit -m "Update email notification template with college logo"
```

### 3. Use Branches for Major Changes

```bash
# Create feature branch
git checkout -b feature/new-dashboard

# Make changes and test
# ...

# Commit and push
git add .
git commit -m "Redesign dashboard with new statistics"
git push origin feature/new-dashboard

# After testing on EC2, merge to main
git checkout main
git merge feature/new-dashboard
git push origin main

# Delete feature branch
git branch -d feature/new-dashboard
```

### 4. Regular Backups Before Updates

```bash
# Before major updates, backup manually
sudo /usr/local/bin/backup-ncitad.sh

# Verify backup exists
ls -lh /home/ubuntu/backups/ | tail -5
```

### 5. Monitor After Deployment

```bash
# Keep logs open during deployment
sudo tail -f /var/log/apache2/ncitad-error.log

# In another terminal
sudo tail -f /var/log/php/error.log

# Watch system resources
htop
```

### 6. Document Changes

```bash
# Create CHANGELOG.md in your project
# Update it with each release

## [1.2.0] - 2025-11-03
### Added
- Pagination to concerns list
- Export to CSV functionality

### Fixed
- Login redirect issue
- Email notification bug

### Changed
- Updated dashboard statistics layout
```

### 7. Keep config.php Out of Git

```bash
# Verify .gitignore includes
cat .gitignore | grep config.php

# Should show:
# includes/config.php
# includes/email_config.php

# If not, add it
echo "includes/config.php" >> .gitignore
echo "includes/email_config.php" >> .gitignore
git add .gitignore
git commit -m "Add config files to .gitignore"
```

### 8. Regular Git Status Checks

```bash
# Before committing
git status

# Check what's staged
git diff --staged

# Check all changes
git diff

# View commit history
git log --oneline --graph --all -10
```

---

## Quick Reference Commands

### Daily Update Workflow

```bash
# Local Machine
cd c:\xampp\htdocs\ncitad
git add .
git commit -m "Description of changes"
git push origin main

# EC2 Server
ssh -i keypair.pem ubuntu@ec2-ip
cd /var/www/ncitad
git pull origin main
sudo systemctl restart apache2
```

### Using Deployment Script

```bash
# Local
git push origin main

# EC2
ssh -i keypair.pem ubuntu@ec2-ip
sudo /usr/local/bin/deploy-ncitad.sh
```

### Check Status

```bash
# Git status
git status
git log --oneline -5

# Apache status
sudo systemctl status apache2

# View errors
sudo tail -f /var/log/apache2/ncitad-error.log
sudo tail -f /var/log/php/error.log
```

### Emergency Rollback

```bash
cd /var/www/ncitad
git reset --hard HEAD~1
sudo systemctl restart apache2
```

---

## Summary

**Updating EC2 with Git is simple:**

1. **Develop locally** → Test on `http://localhost/ncitad`
2. **Commit changes** → `git commit -m "description"`
3. **Push to GitHub** → `git push origin main`
4. **SSH to EC2** → Connect to server
5. **Pull updates** → `git pull origin main` or run deploy script
6. **Restart Apache** → `sudo systemctl restart apache2`
7. **Test live** → Verify application works

**Pro tip:** Use the deployment script for consistent, safe updates with automatic backups!

---

## Need Help?

- **Git documentation**: https://git-scm.com/doc
- **GitHub guides**: https://guides.github.com/
- **AWS EC2 docs**: https://docs.aws.amazon.com/ec2/
- **Project docs**: See `README.md` and `AWS_DEPLOYMENT_GUIDE.md`

**Happy deploying! 🚀**
