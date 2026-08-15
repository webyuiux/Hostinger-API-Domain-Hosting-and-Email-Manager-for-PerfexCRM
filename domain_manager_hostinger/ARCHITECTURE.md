# Domain Manager CRM - Architecture Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PerfexCRM Platform                       │
│  (CodeIgniter-based CRM with Permission System)            │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│        Domain Manager Hostinger Module                      │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │         Controller Layer                               │ │
│  │   Domain_manager_hostinger.php                         │ │
│  │   - index() - List domains                             │ │
│  │   - create() - Create domain form                      │ │
│  │   - save_domain_manager() - Save data                  │ │
│  │   - edit() - Edit domain form                          │ │
│  │   - delete() - Delete domain                           │ │
│  │   - Similar methods for hosting & emails              │ │
│  └────────────────────────────────────────────────────────┘ │
│                           │                                  │
│           ┌───────────────┼───────────────┐                 │
│           ▼               ▼               ▼                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Model 1    │  │   Model 2    │  │   Model 3    │      │
│  │ Domain Mgr   │  │ Hosting Mgr  │  │ Email Mgr    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│           │               │               │                 │
│           └───────────────┼───────────────┘                 │
│                           ▼                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │   Hostinger API Model (Orchestrator)                 │  │
│  │   - sync_domains()                                   │  │
│  │   - sync_websites()                                  │  │
│  │   - API token management                            │  │
│  └──────────────────────────────────────────────────────┘  │
│                           │                                  │
└───────────────────────────┼──────────────────────────────────┘
                            │
                            ▼
              ┌─────────────────────────────────┐
              │   Database (MySQL/MariaDB)      │
              │                                 │
              │  ├─ domain_manager              │
              │  ├─ email_manager               │
              │  ├─ hosting_details             │
              │  ├─ clients (linked)            │
              │  └─ projects (linked)           │
              └─────────────────────────────────┘
                            │
                            ▼
              ┌─────────────────────────────────┐
              │   Hostinger REST API            │
              │   (Bearer Token Auth)           │
              │                                 │
              │  GET /api/domains              │
              │  GET /api/websites             │
              │  GET /api/hosting              │
              └─────────────────────────────────┘
                            │
                            ▼
              ┌─────────────────────────────────┐
              │   Hostinger Services            │
              │   (Actual domains & hosting)    │
              └─────────────────────────────────┘
```

---

## Data Flow Diagram

### 1. User Creates Domain Flow
```
User (Admin/Staff)
       │
       ▼
  Create Form
   (UI Layer)
       │
       ▼
Controller::create()
       │
       ▼
Domain_manager_model->add()
       │
       ▼
  Insert into DB
       │
       ▼
Link to Client/Project
       │
       ▼
Success Response
```

### 2. Automated Sync Flow (Every 24 Hours)
```
PerfexCRM Cron Job
       │
       ▼
domain_manager_automated_sync()
       │
       ▼
Check last_cron_sync timestamp
       │
       ├─ < 24 hours? STOP
       │
       └─ > 24 hours?
           │
           ▼
    Load Hostinger_api_model
           │
           ▼
    sync_domains()
           │
           ├─ API Call to Hostinger
           ├─ Fetch all domains
           └─ Update DB
           │
           ▼
    sync_websites()
           │
           ├─ API Call to Hostinger
           ├─ Fetch all hosting
           └─ Update DB
           │
           ▼
    Update last_cron_sync timestamp
           │
           ▼
       Complete
```

### 3. User Views Domain List Flow
```
User Navigates to index
       │
       ▼
Controller::index()
       │
       ├─ Check permissions
       │
       ├─ Calculate stats:
       │  ├─ total_assets (all domains)
       │  └─ expiring_soon (expiry_date ≤ +30 days)
       │
       ├─ Check AJAX request
       │
       ├─ Load table data
       │  └─ Domain_manager_model->get()
       │
       ▼
Render views/index.php
       │
       └─ Display:
           ├─ Total domains count
           ├─ Expiring soon count
           ├─ Domain table with:
           │  ├─ Domain name
           │  ├─ Client name
           │  ├─ Project name
           │  ├─ Expiry date
           │  └─ Status (Active/Inactive)
           └─ Action buttons (edit, delete)
```

---

## Database Schema Relationships

```
┌──────────────────────┐
│    clients           │
│ (PerfexCRM native)   │
└──────────────────────┘
          ▲
          │ (1:N)
          │
          │ client_id
          │
┌──────────────────────┐          ┌──────────────────────┐
│   domain_manager     │◄─────────│    projects          │
│                      │ project  │ (PerfexCRM native)   │
│ ├─ id                │  _id     │                      │
│ ├─ domain_name       │          │                      │
│ ├─ registrar         │          │                      │
│ ├─ expiry_date       │ (1:N)    │                      │
│ ├─ client_id         │          │                      │
│ ├─ project_id        │          │                      │
│ └─ status            │          │                      │
└──────────────────────┘          └──────────────────────┘

┌──────────────────────────────────────────┐
│         email_manager                    │
│ ├─ id                                    │
│ ├─ email_address                         │
│ ├─ domain_id (links to domain_manager)   │
│ ├─ client_id                             │
│ └─ project_id                            │
└──────────────────────────────────────────┘

┌──────────────────────────────────────────┐
│       hosting_details                    │
│ ├─ id                                    │
│ ├─ hosting_name/service_type             │
│ ├─ client_id                             │
│ ├─ project_id                            │
│ └─ other_details                         │
└──────────────────────────────────────────┘
```

---

## Permission Model

```
Domain Manager Permissions:

view       ──► Can view domains, hosting, emails
create     ──► Can create new domains
edit       ──► Can edit existing domains
delete     ──► Can delete domains
hosting_view ──► Can view hosting details

Applied to:
├─ Admin users (all permissions)
├─ Staff users (based on role)
└─ Client portal (limited to own domains)
```

---

## Module Features Map

```
Domain Manager Module
│
├─ Domain Management
│  ├─ Create domain
│  ├─ Edit domain details
│  ├─ Delete domain
│  ├─ Track expiry dates
│  ├─ Link to client/project
│  └─ Monitor status
│
├─ Hosting Management
│  ├─ Create hosting account
│  ├─ Edit hosting details
│  ├─ Delete hosting
│  └─ Link to client/project
│
├─ Email Management
│  ├─ Create email account
│  ├─ Edit email details
│  ├─ Delete email account
│  └─ Link to domain
│
├─ Hostinger Integration
│  ├─ API authentication
│  ├─ Auto-sync domains
│  ├─ Auto-sync hosting
│  └─ 24-hour sync cycle
│
├─ Dashboard & Statistics
│  ├─ Total domains count
│  ├─ Expiring soon alert
│  ├─ Active/Inactive breakdown
│  └─ Client/Project breakdown
│
├─ Admin Views
│  ├─ Client domain manager
│  ├─ Project domain manager
│  ├─ Client hosting manager
│  ├─ Project hosting manager
│  ├─ Client emails manager
│  └─ Project emails manager
│
└─ Access Control
   ├─ Permission-based access
   ├─ Role-based views
   └─ Client portal integration
```

---

## Workflow Visualization

### Complete Domain Lifecycle

```
Step 1: CREATION
   └─► Admin creates new domain entry
       ├─ Enter domain name
       ├─ Select client/project
       ├─ Set expiry date
       └─ Set status → SAVED TO DB

Step 2: INITIAL SYNC (24h sync cycle)
   └─► Cron job triggers
       └─► Hostinger API sync
           └─► Database updated with latest data

Step 3: ACTIVE MONITORING
   └─► Dashboard shows:
       ├─ Domain status
       ├─ Days until expiry
       ├─ Associated client/project
       └─ Expiring soon (if ≤ 30 days)

Step 4: EDIT/UPDATE
   └─► Admin updates domain details
       └─ Changes saved to DB
       └─ Next sync fetches from Hostinger

Step 5: EXPIRY ALERT (Auto-calculated)
   └─► System flags if:
       expiry_date ≤ TODAY + 30 days
       └─ Shown in "Expiring Soon" widget

Step 6: RENEWAL (Manual)
   └─► Admin renews domain in Hostinger
       └─► Next auto-sync updates DB
           └─► Expiry alert clears

Step 7: DELETION (Optional)
   └─► Admin deletes domain entry
       └─ Removed from CRM tracking
       └─ Note: Doesn't affect actual Hostinger domain
```

---

## Key Technologies Used

```
Frontend Layer:
├─ HTML/CSS
├─ JavaScript (AJAX)
└─ Bootstrap/CSS Framework

Backend Layer:
├─ PHP 7.x+
├─ CodeIgniter Framework
├─ MVC Pattern
└─ Database: MySQL/MariaDB

API Integration:
├─ REST API (Hostinger)
├─ cURL or HTTP Client
├─ Bearer Token Authentication
└─ JSON Request/Response

Security:
├─ Permission System
├─ CSRF Protection
├─ Input Validation
├─ SQL Injection Prevention
└─ API Token Encryption
```

