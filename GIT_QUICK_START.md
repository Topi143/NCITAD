# Git Quick Start Guide for NCITAD

This guide will help you set up Git version control for your NCITAD project.

## 📋 Prerequisites

- Git installed on your computer ([Download Git](https://git-scm.com/downloads))
- GitHub account ([Sign up](https://github.com/signup))
- Basic command line knowledge

## 🚀 Quick Setup (5 Minutes)

### Step 1: Configure Git (First Time Only)

Open PowerShell in your project directory and run:

```powershell
# Set your name and email (used in commits)
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Verify configuration
git config --list
```

### Step 2: Initialize Repository

```powershell
# Navigate to your project
cd c:\xampp\htdocs\ncitad

# Initialize Git repository
git init

# Check status
git status
```

### Step 3: Stage and Commit Files

```powershell
# Add all files (respects .gitignore)
git add .

# Check what will be committed
git status

# Create first commit
git commit -m "Initial commit: NCITAD Ticket Management System"
```

### Step 4: Create GitHub Repository

1. Go to https://github.com/new
2. Repository name: `ncitad`
3. Description: `Norzagaray College IT Assistance Desk`
4. Visibility: **Private** (recommended) or Public
5. **Do NOT** check "Initialize with README" (we already have one)
6. Click **Create repository**

### Step 5: Connect to GitHub

```powershell
# Add remote repository (replace with your actual URL)
git remote add origin https://github.com/yourusername/ncitad.git

# Verify remote
git remote -v

# Rename branch to main (if needed)
git branch -M main

# Push to GitHub
git push -u origin main
```

You'll be prompted to authenticate with GitHub. Use your GitHub username and **Personal Access Token** (not password).

#### Creating GitHub Personal Access Token

1. GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
2. Click "Generate new token (classic)"
3. Note: "NCITAD Development"
4. Expiration: 90 days (or custom)
5. Select scopes: `repo` (full control of private repositories)
6. Click "Generate token"
7. **Copy the token immediately** (you won't see it again!)
8. Use this token as your password when pushing

## 🔒 Verify Configuration Files Are Protected

```powershell
# Check Git status - these files should NOT appear:
# - includes/config.php
# - includes/email_config.php

git status

# If they appear, .gitignore isn't working
# Make sure .gitignore exists and contains:
# includes/config.php
# includes/email_config.php
```

## 📝 Daily Git Workflow

### Making Changes

```powershell
# Check current status
git status

# Stage specific files
git add admin/dashboard.php
git add user/concernlist.php

# Or stage all changes
git add .

# Commit with descriptive message
git commit -m "Add: Dashboard statistics and filtering"

# Push to GitHub
git push
```

### Creating Feature Branches

```powershell
# Create and switch to new branch
git checkout -b feature/email-notifications

# Make changes and commit
git add .
git commit -m "Add email notification system"

# Push branch to GitHub
git push -u origin feature/email-notifications

# Switch back to main
git checkout main

# Merge feature branch
git merge feature/email-notifications

# Delete feature branch
git branch -d feature/email-notifications
```

### Viewing History

```powershell
# View commit history
git log --oneline -10

# View changes in last commit
git diff HEAD~1

# View changes in specific file
git log --follow -- admin/dashboard.php
```

### Pulling Updates

```powershell
# Fetch and merge changes from GitHub
git pull origin main

# Or pull all branches
git pull
```

## 🛠 Common Tasks

### Undo Uncommitted Changes

```powershell
# Discard changes in specific file
git checkout -- filename.php

# Discard all uncommitted changes
git reset --hard HEAD
```

### Undo Last Commit (Keep Changes)

```powershell
# Undo last commit but keep changes
git reset --soft HEAD~1

# Make corrections and commit again
git add .
git commit -m "Corrected commit message"
```

### Stash Changes (Temporary Save)

```powershell
# Save current changes without committing
git stash

# View stashed changes
git stash list

# Restore stashed changes
git stash pop
```

### View Remote Repository

```powershell
# Check remote URL
git remote -v

# Change remote URL
git remote set-url origin https://github.com/newusername/ncitad.git
```

## 🔍 Useful Git Commands Cheat Sheet

```powershell
# Status and Information
git status                      # Check working directory status
git log --oneline -10          # View recent commits
git branch -a                  # List all branches
git diff                       # View unstaged changes
git diff --staged              # View staged changes

# Staging and Committing
git add filename.php           # Stage specific file
git add .                      # Stage all changes
git commit -m "message"        # Commit with message
git commit --amend             # Modify last commit

# Branching
git branch                     # List local branches
git branch feature-name        # Create new branch
git checkout branch-name       # Switch to branch
git checkout -b new-branch     # Create and switch to new branch
git branch -d branch-name      # Delete branch

# Remote Operations
git push                       # Push to current branch
git push origin main           # Push to main branch
git pull                       # Fetch and merge changes
git fetch                      # Fetch changes without merging
git clone URL                  # Clone repository

# Undoing Changes
git reset --hard HEAD          # Discard all uncommitted changes
git reset --soft HEAD~1        # Undo last commit, keep changes
git revert COMMIT_HASH         # Create new commit that undoes changes
git clean -fd                  # Remove untracked files

# Stashing
git stash                      # Save changes temporarily
git stash pop                  # Restore stashed changes
git stash list                 # View all stashes
git stash drop                 # Delete latest stash
```

## 🚨 Important Reminders

### Never Commit These Files:
- ❌ `includes/config.php` (database credentials)
- ❌ `includes/email_config.php` (SMTP credentials)
- ❌ `.env` files
- ❌ Log files (`*.log`)
- ❌ Backup files (`*.backup`, `*.bak`)

### Always Commit These Files:
- ✅ `includes/config.example.php` (template)
- ✅ `includes/email_config.example.php` (template)
- ✅ `.gitignore`
- ✅ `README.md`
- ✅ Source code files (`.php`, `.css`, `.js`)
- ✅ `ncitad.sql` (database schema)

## 📖 Commit Message Best Practices

Use this format for commit messages:

```
Type: Brief description (50 chars or less)

Detailed explanation if needed (wrap at 72 chars)

Examples:
✅ Add: Email notification system for ticket updates
✅ Fix: Login redirect issue after session timeout
✅ Update: Improve dashboard query performance
✅ Refactor: Simplify ticket status transition logic
✅ Remove: Deprecated test files and setup scripts
```

### Commit Types:
- `Add:` - New feature or file
- `Fix:` - Bug fix
- `Update:` - Improve existing feature
- `Refactor:` - Code restructuring (no feature change)
- `Remove:` - Delete code or files
- `Docs:` - Documentation changes
- `Style:` - Code formatting (no logic change)
- `Test:` - Add or update tests

## 🔄 GitHub Desktop (Alternative GUI)

If you prefer a graphical interface:

1. Download [GitHub Desktop](https://desktop.github.com/)
2. Install and sign in with your GitHub account
3. File → Add Local Repository → Select `c:\xampp\htdocs\ncitad`
4. Use the GUI for commits, pushes, and branch management

## 🆘 Need Help?

- 📚 [Git Documentation](https://git-scm.com/doc)
- 📖 [GitHub Guides](https://guides.github.com/)
- 🎓 [Learn Git Branching](https://learngitbranching.js.org/)
- ❓ [Stack Overflow](https://stackoverflow.com/questions/tagged/git)

## ✅ Quick Checklist

Before pushing to GitHub:

- [ ] Tested changes locally
- [ ] Committed with descriptive message
- [ ] No sensitive data (credentials, passwords) in commits
- [ ] `.gitignore` is working correctly
- [ ] Code follows project conventions
- [ ] Documentation updated if needed

---

**Happy Coding! 🚀**

For deployment using Git, see the [AWS Deployment Guide](AWS_DEPLOYMENT_GUIDE.md).
