# Domain Manager CRM - Quick Start & Code Reference

## 🚀 Quick Overview (60 seconds)

**What it does:** Manages domains and hosting linked to clients/projects, with automatic Hostinger API sync every 24 hours.

**Key parts:**
- **Controller** - Handles user requests (CRUD operations)
- **Models** - Talk to database (Domain, Email, Hosting, Hostinger API)
- **Views** - Show data to users (Forms, Lists, Admin pages)
- **Database** - Stores domains, hosting, emails linked to clients & projects

---

## 📁 File Guide - Where to Find What

| Need | File | Purpose |
|------|------|---------|
| Create domain | `controllers/Domain_manager_hostinger.php::create()` | Display form |
| Save domain | `controllers/Domain_manager_hostinger.php::save_domain_manager()` | Process form |
| Get domain data | `models/Domain_manager_model.php::get()` | Query database |
| Add domain | `models/Domain_manager_model.php::add()` | Insert to DB |
| Update domain | `models/Domain_manager_model.php::update()` | Modify DB |
| Delete domain | `models/Domain_manager_model.php::delete()` | Remove from DB |
| Hostinger sync | `models/Hostinger_api_model.php::sync_domains()` | API integration |
| Auto sync cron | `domain_manager_hostinger.php::domain_manager_automated_sync()` | Scheduled job |
| Display list | `views/index.php` | Show all domains |
| Domain form | `views/create.php` | Create/Edit form |
| Email management | `views/emails/` | Email CRUD views |
| Hosting management | `views/hosting/` | Hosting CRUD views |
| Admin views | `views/admin/` | Client/Project specific |
| Styling | `assets/css/style.css` | Custom CSS |

---

## 💾 Database Tables Overview

### domain_manager
```sql
id                  INT PRIMARY KEY
domain_name         VARCHAR - Actual domain (e.g., example.com)
registrar          VARCHAR - Where domain is registered
expiry_date        DATE - When domain expires
client_id          INT - Links to clients table
project_id         INT - Links to projects table
status             ENUM - 'active' or 'inactive'
created_at         TIMESTAMP
updated_at         TIMESTAMP
```

### email_manager
```sql
id                  INT PRIMARY KEY
email_address       VARCHAR - Full email address
domain_id          INT - Links to domain_manager
client_id          INT - Links to clients table
project_id         INT - Links to projects table
created_at         TIMESTAMP
```

### hosting_details
```sql
id                  INT PRIMARY KEY
hosting_name       VARCHAR - Service description
client_id          INT - Links to clients table
project_id         INT - Links to projects table
service_details    TEXT - Additional info
created_at         TIMESTAMP
```

---

## 🔄 Key Operations - How It Works

### 1️⃣ Creating a Domain

**Flow:**
```
User clicks "Create Domain"
         ↓
Controller::create() loads form
         ↓
User fills: domain name, client, project, expiry date
         ↓
Form submitted to Controller::save_domain_manager()
         ↓
Data passed to Domain_manager_model->add()
         ↓
Database INSERT query
         ↓
Record saved ✓
```

**Code:**
```php
// In Controller
public function save_domain_manager() {
    $data = [
        'domain_name' => $this->input->post('domain_name'),
        'client_id'   => $this->input->post('client_id'),
        'project_id'  => $this->input->post('project_id'),
        'expiry_date' => $this->input->post('expiry_date'),
        'status'      => 'active'
    ];
    
    $id = $this->domain_manager_model->add($data);
    // Returns new domain ID
}

// In Model
public function add($data) {
    $this->db->insert(db_prefix() . 'domain_manager', $data);
    return $this->db->insert_id(); // Returns new ID
}
```

---

### 2️⃣ Viewing All Domains

**Flow:**
```
User navigates to /domain_manager_hostinger
         ↓
Controller::index() called
         ↓
Checks permissions
         ↓
Calculates stats:
  - Total domains
  - Expiring soon (within 30 days)
         ↓
Loads view with data
         ↓
Display table with all domains
```

**Code:**
```php
public function index() {
    // Check permission
    if (!has_permission('domain_manager', '', 'view')) {
        access_denied('domain_manager');
    }
    
    // Get stats
    $data['total_assets'] = total_rows(db_prefix() . 'domain_manager');
    
    // Expiring soon = expiry within 30 days and status active
    $data['expiring_soon'] = total_rows(db_prefix() . 'domain_manager', 
        'expiry_date <= "' . date('Y-m-d', strtotime('+30 days')) . '" 
        AND expiry_date >= "' . date('Y-m-d') . '" 
        AND status = "active"'
    );
    
    // Load view
    $this->load->view('index', $data);
}
```

---

### 3️⃣ Automatic Hostinger Sync (Every 24 Hours)

**Flow:**
```
PerfexCRM runs daily cron job
         ↓
domain_manager_automated_sync() triggered
         ↓
Check: Was sync run in last 24 hours?
  - If YES: Stop (don't sync again)
  - If NO: Continue
         ↓
Load Hostinger_api_model
         ↓
Call sync_domains()
  - Connect to Hostinger API (Bearer token)
  - Fetch all domains from Hostinger
  - Update/Insert into database
         ↓
Call sync_websites()
  - Fetch all hosting from Hostinger
  - Update/Insert into database
         ↓
Update last sync timestamp
         ↓
Complete ✓
```

**Code:**
```php
// In domain_manager_hostinger.php (module init file)
function domain_manager_automated_sync() {
    $last_run = get_option('domain_manager_last_cron_sync');
    
    // Run only once every 24 hours (86400 seconds)
    if (empty($last_run) || (time() - $last_run) > 86400) {
        $CI = &get_instance();
        $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/hostinger_api_model');
        
        // Sync data from Hostinger
        $CI->hostinger_api_model->sync_domains();
        $CI->hostinger_api_model->sync_websites();
        
        // Update last run time
        update_option('domain_manager_last_cron_sync', time());
    }
}
```

---

### 4️⃣ Editing a Domain

**Flow:**
```
User clicks Edit on a domain
         ↓
Controller::edit($id) called
         ↓
Load domain details from DB
  (joins with clients and projects)
         ↓
Display form with current data
         ↓
User modifies fields
         ↓
Form submitted
         ↓
Controller updates domain
         ↓
Database UPDATE query
         ↓
Updated ✓
```

**Code:**
```php
// In Model - Get with joins
public function get($id = ''){
    if ($id == '') {
        return $this->db->get(...)->result_array(); // All records
    } else {
        $this->db->select(db_prefix() . 'domain_manager.*, ' . 
                         db_prefix() . 'clients.company AS client_name, ' . 
                         db_prefix() . 'projects.name AS project_name');
        $this->db->from(db_prefix() . 'domain_manager');
        $this->db->where(db_prefix() . 'domain_manager.id', $id);
        $this->db->join(db_prefix() . 'clients', '...', 'left');
        return $this->db->get()->row(); // Single record with joined data
    }
}

// In Model - Update
public function update($id, $data) {
    if ($id) {
        unset($data['id']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'domain_manager', $data);
        return $this->db->affected_rows();
    }
    return false;
}
```

---

### 5️⃣ Deleting a Domain

**Flow:**
```
User clicks Delete
         ↓
Confirmation prompt
         ↓
User confirms
         ↓
Controller::delete($id) called
         ↓
Domain_manager_model->delete($id)
         ↓
Database DELETE query
         ↓
Record removed ✓
```

---

## 📊 Key Statistics Calculations

```php
// Total domains in system
$total = total_rows(db_prefix() . 'domain_manager');

// Domains expiring within 30 days (and currently active)
$expiring_soon = total_rows(db_prefix() . 'domain_manager', 
    'expiry_date <= "' . date('Y-m-d', strtotime('+30 days')) . '" 
    AND expiry_date >= "' . date('Y-m-d') . '" 
    AND status = "active"'
);

// Expired domains
$expired = total_rows(db_prefix() . 'domain_manager',
    'expiry_date < "' . date('Y-m-d') . '"'
);

// Active domains
$active = total_rows(db_prefix() . 'domain_manager',
    'status = "active"'
);

// Domains by client
$client_domains = total_rows(db_prefix() . 'domain_manager',
    'client_id = ' . $client_id
);
```

---

## 🔐 Permission Checks

Every action checks permissions first:

```php
// View all domains
if (!has_permission('domain_manager', '', 'view')) {
    access_denied('domain_manager');
}

// Create new domain
if (!has_permission('domain_manager', '', 'create')) {
    access_denied('domain_manager');
}

// Edit domain
if (!has_permission('domain_manager', '', 'edit')) {
    access_denied('domain_manager');
}

// Delete domain
if (!has_permission('domain_manager', '', 'delete')) {
    access_denied('domain_manager');
}
```

---

## 🔗 Data Relationships

### Linking to Clients & Projects

```php
// When creating domain:
$domain_data = [
    'domain_name' => 'example.com',
    'client_id'   => 42,      // Links to clients table
    'project_id'  => 15,      // Links to projects table
    'expiry_date' => '2025-12-31'
];

// Query to get domain WITH client/project names:
SELECT 
    domain_manager.id,
    domain_manager.domain_name,
    domain_manager.expiry_date,
    clients.company AS client_name,
    projects.name AS project_name
FROM domain_manager
LEFT JOIN clients ON clients.userid = domain_manager.client_id
LEFT JOIN projects ON projects.id = domain_manager.project_id
WHERE domain_manager.id = 1;

// Result:
// id: 1
// domain_name: example.com
// expiry_date: 2025-12-31
// client_name: ABC Corporation
// project_name: Website Redesign
```

---

## 📱 User Roles & Access

### Admin User
- ✅ View all domains
- ✅ Create domains
- ✅ Edit all domains
- ✅ Delete domains
- ✅ Access admin views

### Staff User
- ✅ View domains (based on permissions)
- ✅ Create domains (if permitted)
- ✅ Edit domains (if permitted)
- ✅ Delete domains (if permitted)

### Client Portal
- ✅ View own domains
- ❌ Create domains
- ❌ Edit domains
- ❌ Delete domains

---

## 🛠️ Common Tasks

### Task 1: Find all domains expiring this month
```php
$expiring_this_month = $this->db
    ->where('expiry_date >=', date('Y-m-01'))
    ->where('expiry_date <=', date('Y-m-t'))
    ->where('status', 'active')
    ->get(db_prefix() . 'domain_manager')
    ->result_array();
```

### Task 2: Get domains for a specific client
```php
$client_domains = $this->db
    ->where('client_id', $client_id)
    ->get(db_prefix() . 'domain_manager')
    ->result_array();
```

### Task 3: Get domains linked to a project
```php
$project_domains = $this->db
    ->where('project_id', $project_id)
    ->get(db_prefix() . 'domain_manager')
    ->result_array();
```

### Task 4: Mark domain as inactive
```php
$this->db->where('id', $domain_id);
$this->db->update(db_prefix() . 'domain_manager', ['status' => 'inactive']);
```

### Task 5: Count domains by status
```php
$active = $this->db
    ->where('status', 'active')
    ->count_all_results(db_prefix() . 'domain_manager');

$inactive = $this->db
    ->where('status', 'inactive')
    ->count_all_results(db_prefix() . 'domain_manager');
```

---

## 🚀 Installation Checklist

- [ ] Extract module to `modules/domain_manager_hostinger/`
- [ ] Run `install.php` (creates tables)
- [ ] Run migrations (101, 102)
- [ ] Set up Hostinger API token
- [ ] Enable module in PerfexCRM admin
- [ ] Assign permissions to users/roles
- [ ] Set up cron job for auto-sync
- [ ] Test: Create a sample domain
- [ ] Test: Edit domain
- [ ] Test: Check expiry calculation
- [ ] Test: Manual/auto sync

---

## 📞 Support Files

- `api.json` - Hostinger API documentation
- `CRM_OVERVIEW.md` - This overview guide
- `ARCHITECTURE.md` - Architecture diagrams
- `AGENTS.md` - Development guidelines

---

## 🎯 Summary

This CRM automates domain management by:
1. **Storing** domain data linked to clients/projects
2. **Tracking** expiry dates with 30-day alerts
3. **Syncing** automatically with Hostinger every 24h
4. **Displaying** dashboards with statistics
5. **Managing** hosting and email accounts
6. **Controlling** access with permissions

Start by creating a domain, then let the auto-sync keep everything updated!
