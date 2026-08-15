# 🛠️ Complete Setup Guide - Domain Manager Hostinger CRM

## Current System Status
- ✅ Node.js v24.13.0
- ✅ npm v11.6.2
- ❌ PHP (MISSING)
- ❌ MySQL (MISSING)
- ❌ Composer (MISSING)

---

## 📋 Installation Steps

### Step 1: Install PHP 8.1+ ⭐ REQUIRED

#### Option A: Using Chocolatey (Recommended - Automated)
```powershell
# Run PowerShell as Administrator, then:
choco install php
```

#### Option B: Manual Download
1. Visit https://windows.php.net/download/
2. Download PHP 8.1 or 8.2 (Thread Safe)
3. Extract to `C:\php`
4. Add to Environment Variables:
   - Add `C:\php` to PATH

#### Verify Installation:
```powershell
php --version
```

---

### Step 2: Install MySQL Server ⭐ REQUIRED

#### Option A: Using Chocolatey (Recommended)
```powershell
# Run PowerShell as Administrator, then:
choco install mysql
```

#### Option B: Download Installer
1. Visit https://dev.mysql.com/downloads/mysql/
2. Download MySQL Community Server
3. Run installer and follow wizard
4. Default port: 3306

#### Verify Installation:
```powershell
mysql --version
```

---

### Step 3: Install Composer ⭐ REQUIRED

#### Option A: Using Chocolatey
```powershell
choco install composer
```

#### Option B: Manual Installation
1. Visit https://getcomposer.org/download/
2. Run installer
3. When prompted, enter PHP path: `C:\php\php.exe`

#### Verify Installation:
```powershell
composer --version
```

---

### Step 4: Download & Set Up PerfexCRM

#### Download PerfexCRM:
```powershell
# Create a working directory
mkdir C:\projects
cd C:\projects

# Download PerfexCRM (you need to get this from their website or have it already)
# Extract it to C:\projects\perfexcrm
```

#### Configure Database:
```powershell
# 1. Create database in MySQL
mysql -u root -p
> CREATE DATABASE perfexcrm;
> EXIT;

# 2. Configure PerfexCRM (config/database.php)
# Update with your MySQL credentials
```

---

### Step 5: Install the Domain Manager Module

```powershell
# Copy module to PerfexCRM
Copy-Item -Path "C:\Users\Sakshi\Downloads\domain_manager_hostinger\domain_manager_hostinger" `
          -Destination "C:\projects\perfexcrm\modules\domain_manager_hostinger" -Recurse
```

---

### Step 6: Activate Module in PerfexCRM

1. Open browser: http://localhost/perfexcrm/
2. Log in as Admin
3. Go to **Admin** → **Settings** → **Modules**
4. Find **Domain Manager Hostinger**
5. Click **Install**
6. Database tables will be created automatically ✅

---

### Step 7: Configure Hostinger API

1. Get API token from Hostinger:
   - Login to https://hpanel.hostinger.com/
   - Profile → API Settings
   - Create API token

2. In PerfexCRM:
   - Go to **Admin** → **Settings** → **Domain Manager**
   - Paste Hostinger API token
   - Save

---

## 🚀 Quick Installation Commands

Copy & paste these commands in PowerShell (as Administrator):

```powershell
# 1. Install PHP
choco install php -y

# 2. Install MySQL
choco install mysql -y

# 3. Install Composer
choco install composer -y

# 4. Verify all installations
php --version
mysql --version
composer --version
```

---

## 📱 Alternative: Use XAMPP (All-in-One)

**Easier Option:** Install XAMPP instead of individual tools

```powershell
choco install xampp-8.2 -y
```

XAMPP includes:
- ✅ PHP 8.2
- ✅ MySQL
- ✅ Apache

---

## ✅ Post-Installation Testing

### Test PHP:
```powershell
php -r "echo 'PHP is working!';exit();"
```

### Test MySQL:
```powershell
mysql -u root -e "SELECT VERSION();"
```

### Test Composer:
```powershell
composer --version
```

---

## 📝 Summary: What Each Tool Does

| Tool | Purpose | Status |
|------|---------|--------|
| **PHP** | Server-side language (CRM runs on this) | ❌ INSTALL NOW |
| **MySQL** | Database server (stores all data) | ❌ INSTALL NOW |
| **Composer** | PHP dependency manager | ❌ INSTALL NOW |
| **Node.js** | JavaScript runtime | ✅ Already have |
| **npm** | JavaScript package manager | ✅ Already have |

---

## 🎯 Next Steps After Installation

1. **Verify all tools installed**
2. **Download/Setup PerfexCRM**
3. **Copy module to `modules/` folder**
4. **Go to PerfexCRM Admin → Install module**
5. **Configure Hostinger API token**
6. **Start managing domains!**

---

## 🆘 Troubleshooting

### PHP Not Recognized After Install
```powershell
# Close and reopen PowerShell
# Or restart computer
# Or manually add to PATH
```

### MySQL Connection Denied
```powershell
# Reset MySQL password
mysql -u root
> USE mysql;
> UPDATE user SET authentication_string=PASSWORD('new_password') WHERE User='root';
> FLUSH PRIVILEGES;
```

### Port 3306 Already in Use
```powershell
# Find process using port 3306
netstat -ano | findstr :3306

# Kill process
taskkill /PID <PID> /F
```

---

## 📞 Need Help?

**Follow these steps in order:**

1. Install PHP
   ```powershell
   choco install php -y
   ```

2. Install MySQL
   ```powershell
   choco install mysql -y
   ```

3. Install Composer
   ```powershell
   choco install composer -y
   ```

4. Verify all installed
   ```powershell
   php --version
   mysql --version
   composer --version
   ```

5. Then continue with PerfexCRM setup

---

**Ready? Start with Step 1! 🚀**
