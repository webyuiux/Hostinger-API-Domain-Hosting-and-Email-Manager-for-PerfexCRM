# Domain Manager Hostinger CRM - Complete Overview

## 🎯 What is This CRM?

This is a **Domain & Hosting Management CRM Module** built for PerfexCRM (CodeIgniter-based CRM). It integrates with **Hostinger API** to manage domains and hosting services linked to clients and projects.

---

## 📋 Core Features

### 1. **Domain Management**
- Track domain names and their details
- Monitor domain expiry dates with 30-day warning system
- Link domains to clients and projects
- Track domain status (active/inactive)
- Store registrar information
- Auto-sync with Hostinger API

### 2. **Hosting Management**
- Manage hosting accounts/services
- Link hosting to clients and projects
- Track hosting service details
- Auto-sync hosting data from Hostinger

### 3. **Email Management**
- Manage email accounts associated with domains
- Link emails to clients and projects
- Track email configurations

### 4. **Automated Synchronization**
- Daily automatic sync with Hostinger API via cron jobs
- Keeps domain and hosting data up-to-date
- Runs once every 24 hours

---

## 🏗️ Project Structure

```
domain_manager_hostinger/
├── controllers/
│   └── Domain_manager_hostinger.php    # Main controller (handles all requests)
├── models/
│   ├── Domain_manager_model.php        # Domain CRUD operations
│   ├── Email_manager_model.php         # Email CRUD operations
│   ├── Hosting_details_model.php       # Hosting CRUD operations
│   └── Hostinger_api_model.php         # Hostinger API integration
├── views/
│   ├── index.php                       # Dashboard/List view
│   ├── create.php                      # Create domain form
│   ├── edit.php                        # Edit domain form
│   ├── manage.php                      # Manage view
│   ├── admin/                          # Admin-specific views
│   │   ├── client_domain_manager.php
│   │   ├── client_emails_manager.php
│   │   ├── client_hosting_manager.php
│   │   ├── project_domain_manager.php
│   │   ├── project_emails_manager.php
│   │   └── project_hosting_manager.php
│   ├── emails/                         # Email views
│   │   ├── create.php
│   │   ├── edit.php
│   │   └── index.php
│   └── hosting/                        # Hosting views
│       ├── create.php
│       ├── edit.php
│       └── index.php
├── helpers/
│   └── domain_manager_helper.php       # Helper functions
├── language/
│   └── english/
│       └── domain_manager_hostinger_lang.php  # Language strings
├── migrations/
│   ├── 101_version_101.php            # Database migrations
│   └── 102_version_102.php
├── assets/
│   └── css/
│       └── style.css                   # Custom styling
├── api.json                            # Hostinger API documentation
├── domain_manager_hostinger.php        # Module initialization
└── install.php                         # Installation script
```

---

## 🗄️ Database Tables

### Main Tables:
1. **domain_manager**
   - Stores domain information
   - Fields: id, domain_name, registrar, expiry_date, client_id, project_id, status
   - Linked to clients and projects

2. **email_manager**
   - Stores email account information
   - Linked to domains, clients, and projects

3. **hosting_details**
   - Stores hosting service information
   - Linked to clients and projects

4. **hostinger_api_sync_log** (implied)
   - Tracks API sync operations

---

## 🔌 Key Components

### Controller: Domain_manager_hostinger.php
**Main Actions:**
- `index()` - Display all domains with statistics
- `create()` - Show form to create new domain
- `save_domain_manager()` - Save domain data
- `edit()` - Edit existing domain
- `delete()` - Delete domain
- Similar methods for hosting and emails

**Key Features:**
- Permission checking (view, create, edit, delete)
- AJAX table support
- Statistics dashboard (total assets, expiring domains)
- Client and project selection

### Models

**Domain_manager_model.php**
```php
- get($id)       // Get all or single domain with client/project names
- add($data)     // Create new domain
- update($id, $data)  // Update domain
- delete($id)    // Delete domain
```

**Hostinger_api_model.php**
```php
- sync_domains()   // Auto-sync domains from Hostinger
- sync_websites()  // Auto-sync hosting from Hostinger
- get_api_token()  // Retrieve API credentials
```

---

## 🔌 API Integration

### Hostinger API
The module integrates with Hostinger's REST API for:
- Fetching domain data
- Fetching hosting/website data
- Updating domain information

**Authentication:** Bearer token in Authorization header

**Key Endpoints Used:**
- Domains listing
- Hosting services listing
- Domain details retrieval

---

## 🔐 Permissions System

The module uses PerfexCRM's permission system:

```php
- domain_manager/*/view      // View domains
- domain_manager/*/create    // Create domains
- domain_manager/*/edit      // Edit domains
- domain_manager/*/delete    // Delete domains
- domain_manager/*/hosting_view   // View hosting
```

Permissions are defined for:
- Admin users (full access)
- Regular users (based on permissions)
- Client users (based on assigned domains)

---

## 📊 Dashboard/Statistics

The index page displays:
- **Total Assets** - Total number of domains
- **Expiring Soon** - Domains expiring within 30 days
- **Active Domains** - List of active domains
- **Domain Status** - Active/Inactive status tracking

---

## 🔄 Automated Features

### Cron Job Integration
**Trigger:** After PerfexCRM's cron runs

**Process:**
1. Checks last sync time
2. If > 24 hours ago, runs sync
3. Calls `hostinger_api_model->sync_domains()`
4. Calls `hostinger_api_model->sync_websites()`
5. Updates last sync timestamp

**Purpose:** Keeps domain/hosting data current without manual updates

---

## 💼 Client & Project Linking

Each domain can be linked to:
- **Client** - Company or individual owning the domain
- **Project** - Specific project the domain serves

This allows:
- Multi-tenant domain management
- Project-specific domain tracking
- Client-centric billing
- Organized domain portfolio

---

## 🎨 User Interface

### Main Views:
1. **Index/Dashboard** - List all domains with stats
2. **Create** - Form to add new domain
3. **Edit** - Modify domain details
4. **Admin Views** - Separate views for client/project-specific domain management
5. **Email Manager** - Manage associated email accounts
6. **Hosting Manager** - Manage hosting services

### Responsive Tables:
- AJAX-powered tables
- Sorting and filtering
- Bulk actions (if configured)
- Search functionality

---

## 🛠️ Tech Stack

- **Framework:** CodeIgniter (Perfex CRM)
- **Language:** PHP 7.x+
- **Database:** MySQL/MariaDB
- **API:** RESTful API (Hostinger)
- **Frontend:** HTML/CSS/JavaScript
- **Authentication:** Bearer Token (Hostinger API)

---

## 📥 Installation & Setup

### Steps:
1. Extract module to `modules/domain_manager_hostinger/`
2. Run `install.php` to set up database tables
3. Run migrations (101, 102) to create tables
4. Configure Hostinger API credentials
5. Enable module in PerfexCRM admin
6. Set up cron job for auto-sync

### Configuration:
- Hostinger API token required
- Permissions need to be assigned
- Language strings auto-loaded

---

## 🔐 Security Features

- **Permission checking** on all actions
- **Input validation** in forms
- **API token** stored securely
- **CSRF protection** (PerfexCRM built-in)
- **SQL injection prevention** (CodeIgniter's query builder)

---

## 📈 Use Cases

1. **Web Hosting Companies** - Manage client domains and hosting
2. **Digital Agencies** - Track project domains and renewals
3. **Enterprise IT** - Central domain portfolio management
4. **Domain Resellers** - Manage multiple client domains via Hostinger
5. **Freelancers** - Track domain assignments to clients/projects

---

## 🚀 Key Benefits

✅ Centralized domain management  
✅ Automatic data sync with Hostinger  
✅ Expiry date tracking with alerts  
✅ Client & project-based organization  
✅ Multi-tenant support  
✅ Permission-based access control  
✅ Dashboard statistics  
✅ Email and hosting management  

---

## 📝 Summary

This is a **production-ready domain management CRM** that brings Hostinger hosting management into PerfexCRM. It automates domain tracking, provides expiry notifications, and organizes domains by clients and projects—making it ideal for agencies, resellers, and enterprises managing multiple domains.
