# 📚 Documentation Guide - Where to Start

## 🎯 What This CRM Does (In 30 Seconds)

**Domain Manager Hostinger** is a CRM module that:
- 📦 Manages domains linked to clients and projects
- 🔄 Auto-syncs with Hostinger API every 24 hours
- ⏰ Tracks domain expiry dates with 30-day warnings
- 📧 Manages associated email accounts
- 🏠 Tracks hosting services
- 📊 Shows dashboard with statistics

---

## 📖 Documentation Files (Start Here!)

### For Quick Understanding (5 min read)
👉 **Read First:** [QUICK_REFERENCE.md](QUICK_REFERENCE.md)
- 60-second overview
- File guide (where to find what)
- Database tables
- Key operations explained
- Common tasks

### For Complete Overview (15 min read)
👉 **Read Second:** [CRM_OVERVIEW.md](CRM_OVERVIEW.md)
- Full feature list
- Project structure
- Database tables
- Key components
- Permission system
- Use cases

### For Architecture Understanding (10 min read)
👉 **Read Third:** [ARCHITECTURE.md](ARCHITECTURE.md)
- System architecture diagram
- Data flow diagrams
- Complete domain lifecycle
- Database relationships
- Workflow visualization

---

## 🎓 Learning Path

### If you want to understand the CRM flow:
```
1. Start: QUICK_REFERENCE.md (Quick Overview section)
   └─ Get the 60-second understanding
   
2. Then: ARCHITECTURE.md (Complete Domain Lifecycle)
   └─ See how domains move through the system
   
3. Then: CRM_OVERVIEW.md (Features & Components)
   └─ Understand all capabilities
```

### If you want to modify/develop:
```
1. Start: QUICK_REFERENCE.md (File Guide)
   └─ Know where the code is
   
2. Then: ARCHITECTURE.md (System Architecture)
   └─ Understand dependencies
   
3. Read: Actual source files
   └─ Follow the code
```

### If you want to deploy/install:
```
1. Read: CRM_OVERVIEW.md (Installation & Setup)
   └─ Learn prerequisites
   
2. Run: install.php
   └─ Create database
   
3. Configure: Hostinger API token
   └─ Enable auto-sync
   
4. Check: Permission system
   └─ Set up user access
```

---

## 🗂️ File Structure Quick Reference

```
Domain Manager Module
│
├─ Controllers
│  └─ Domain_manager_hostinger.php
│     ├─ create()        → Show create form
│     ├─ save_domain_manager() → Save data
│     ├─ edit()          → Show edit form
│     ├─ delete()        → Delete domain
│     └─ + hosting & email actions
│
├─ Models
│  ├─ Domain_manager_model.php
│  │  ├─ get()    → Retrieve domain(s)
│  │  ├─ add()    → Create domain
│  │  ├─ update() → Modify domain
│  │  └─ delete() → Remove domain
│  │
│  ├─ Hosting_details_model.php
│  │  └─ Similar CRUD operations
│  │
│  ├─ Email_manager_model.php
│  │  └─ Similar CRUD operations
│  │
│  └─ Hostinger_api_model.php
│     ├─ sync_domains()  → Get domains from API
│     ├─ sync_websites() → Get hosting from API
│     └─ get_api_token() → API authentication
│
├─ Views
│  ├─ index.php         → Dashboard/List
│  ├─ create.php        → Create form
│  ├─ edit.php          → Edit form
│  ├─ admin/            → Admin-specific views
│  ├─ emails/           → Email management views
│  └─ hosting/          → Hosting management views
│
├─ Database
│  ├─ domain_manager table
│  ├─ email_manager table
│  └─ hosting_details table
│
└─ Configuration
   ├─ Hostinger API token
   ├─ Permissions
   └─ Cron job (auto-sync)
```

---

## 🔑 Key Concepts

### 1. Domain
- The actual domain name (e.g., example.com)
- Has expiry date
- Linked to client and/or project
- Status: active or inactive
- Synced from Hostinger

### 2. Client
- Person or company that owns domains
- Can have multiple domains
- Native PerfexCRM entity (users/customers)
- Linked to domain_manager via client_id

### 3. Project
- Specific project within your company
- Can have multiple domains
- Native PerfexCRM entity
- Linked to domain_manager via project_id

### 4. Hosting
- Web hosting service
- Associated with domain (or standalone)
- Linked to client and/or project
- Synced from Hostinger

### 5. Email
- Email accounts associated with domains
- Linked to domain, client, and project
- Managed within this module

### 6. Hostinger API
- External service integration
- Provides domain and hosting data
- Auto-syncs every 24 hours
- Uses Bearer token authentication

---

## 💾 Database at a Glance

```
Table: domain_manager
├─ id (PK)
├─ domain_name          (e.g., "example.com")
├─ registrar            (e.g., "Hostinger")
├─ expiry_date          (2025-12-31)
├─ client_id      (FK → clients.userid)
├─ project_id     (FK → projects.id)
├─ status               ("active" or "inactive")
└─ timestamps           (created_at, updated_at)

Table: email_manager
├─ id (PK)
├─ email_address        (e.g., "user@example.com")
├─ domain_id      (FK → domain_manager.id)
├─ client_id      (FK → clients.userid)
├─ project_id     (FK → projects.id)
└─ timestamps

Table: hosting_details
├─ id (PK)
├─ hosting_name         (e.g., "Premium Hosting")
├─ client_id      (FK → clients.userid)
├─ project_id     (FK → projects.id)
├─ service_details      (Any additional info)
└─ timestamps
```

---

## 🔄 Main Data Flows

### User Creates Domain
```
User Form Input
    ↓
Controller: create() → save_domain_manager()
    ↓
Model: Domain_manager_model->add()
    ↓
Database: INSERT
    ↓
Domain saved ✓
```

### Auto-Sync (Every 24h)
```
PerfexCRM Cron Job
    ↓
domain_manager_automated_sync()
    ↓
Hostinger_api_model->sync_domains()
Hostinger_api_model->sync_websites()
    ↓
Database: UPDATE records
    ↓
Data synchronized ✓
```

### User Views Domains
```
User navigates to module
    ↓
Controller: index()
    ↓
Model: get() with client/project joins
    ↓
Calculate stats (total, expiring_soon)
    ↓
Render view with data
    ↓
Display dashboard ✓
```

---

## 🔐 Access Control

**Permission System:**
- ✅ view - Can see domains
- ✅ create - Can create domains
- ✅ edit - Can modify domains
- ✅ delete - Can remove domains
- ✅ hosting_view - Can see hosting

**User Types:**
- Admin: Full access to all
- Staff: Based on assigned permissions
- Clients: See only their domains (via portal)

---

## ⚙️ Configuration Checklist

- [ ] Hostinger API token set up
- [ ] Database tables created (via install.php)
- [ ] Migrations run (101, 102)
- [ ] Module enabled in PerfexCRM
- [ ] Permissions assigned to users
- [ ] Cron job configured
- [ ] Custom CSS loaded (style.css)

---

## 🆘 Troubleshooting Quick Links

| Problem | Solution |
|---------|----------|
| Domains not syncing | Check Hostinger API token, verify cron job |
| Permission denied | Check permission settings in PerfexCRM admin |
| Expiry dates wrong | Verify date format in database |
| No data showing | Run install.php, check database tables |
| API errors | Verify token permissions, check rate limiting |

---

## 📚 Reading Order (Recommended)

### For 5-Minute Understanding:
1. [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Overview section
2. [ARCHITECTURE.md](ARCHITECTURE.md) - System Architecture diagram

### For 30-Minute Deep Dive:
1. [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - Full file
2. [CRM_OVERVIEW.md](CRM_OVERVIEW.md) - Full file
3. [ARCHITECTURE.md](ARCHITECTURE.md) - Full file

### For Development/Modification:
1. [QUICK_REFERENCE.md](QUICK_REFERENCE.md) - File Guide section
2. [ARCHITECTURE.md](ARCHITECTURE.md) - Data Flow section
3. Source code files (following the guides)

---

## 🎯 One-Sentence Summary

**This CRM automatically manages domains linked to clients/projects, syncs data from Hostinger every 24 hours, and alerts when domains expire within 30 days.**

---

## 📞 Need Help?

- **Understanding flow?** → Read ARCHITECTURE.md
- **Finding code?** → Read QUICK_REFERENCE.md (File Guide)
- **All features?** → Read CRM_OVERVIEW.md
- **Common tasks?** → Read QUICK_REFERENCE.md (Common Tasks)
- **Development?** → Read AGENTS.md for coding guidelines

---

**Start with [QUICK_REFERENCE.md](QUICK_REFERENCE.md) → 60-Second Overview! ⭐**
