# Domain Manager Hostinger — PerfexCRM Module

**Module Name:** Hostinger Manager  
**Version:** 1.0.1  
**Author:** Vaibhav Kondekar
**Minimum PerfexCRM Version:** 3.0.0  
**PHP Requirement:** 8.1+  

---

## Overview

The **Domain Manager Hostinger** module extends PerfexCRM with a full domain and web hosting management system. It integrates directly with the **Hostinger API** to sync domains and websites automatically, tracks expiry dates, links assets to clients and projects, and sends automated expiry notification emails.

## Screenshots

![Dashboard](screenshots/Screenshot%202026-08-15%20at%206.54.45%20PM.png)
![Domains](screenshots/Screenshot%202026-08-15%20at%206.54.58%20PM.png)
![Hosting](screenshots/Screenshot%202026-08-15%20at%206.55.08%20PM.png)
![Settings](screenshots/Screenshot%202026-08-15%20at%206.55.26%20PM.png)

---

## Key Features

| Feature | Description |
|---|---|
| **Domain Management** | Add, edit, view, and soft-delete domains with full metadata |
| **Hosting Management** | Track web hosting plans, datacenter, access URLs, and credentials |
| **Email/Mailbox Management** | Manage email accounts linked to domains and clients |
| **Hostinger API Sync** | Auto-sync domains and websites from Hostinger via cron |
| **Expiry Notifications** | Configurable automated email alerts before domain/hosting expiry |
| **Client & Project Linking** | Associate domains and hosting to clients and projects |
| **Admin Tab Integration** | Embedded tab views inside Client and Project detail pages |
| **Permissions System** | Granular staff permission controls |
| **Activity Logging** | All CRUD operations are logged in PerfexCRM activity log |

---

## Module File Structure

```
modules/domain_manager_hostinger/
├── domain_manager_hostinger.php       # Main module bootstrap (hooks, menu, settings)
├── install.php                        # Module installer — creates all DB tables
├── README.md                          # This file
├── SETUP_GUIDE.md                     # Step-by-step environment setup guide
├── AGENTS.md                          # AI agent operating rules
│
├── controllers/
│   └── Domain_manager_hostinger.php   # Main controller (all routes/actions)
│
├── models/
│   ├── Domain_manager_model.php        # Domains CRUD + joined queries
│   ├── Hosting_details_model.php       # Hosting plans CRUD + sync
│   ├── Email_manager_model.php         # Email/mailbox CRUD
│   └── Hostinger_api_model.php         # Hostinger REST API integration
│
├── views/
│   ├── index.php                       # Domain list (main dashboard)
│   ├── create.php                      # Add new domain form
│   ├── edit.php                        # Edit domain form
│   ├── view.php                        # Domain detail view
│   ├── manage.php                      # Domain management page
│   ├── create_edit_modal.php           # Modal for quick create/edit
│   ├── domain_manager_table.php        # Reusable table partial
│   │
│   ├── emails/
│   │   ├── index.php                   # Emails list
│   │   ├── create.php                  # Add new email form
│   │   └── edit.php                    # Edit email form
│   │
│   ├── hosting/
│   │   ├── index.php                   # Hosting list
│   │   ├── create.php                  # Add hosting plan form
│   │   ├── edit.php                    # Edit hosting plan form
│   │   └── view.php                    # Hosting detail view
│   │
│   ├── admin/
│   │   ├── client_domain_manager.php   # Domain tab inside Client profile
│   │   ├── client_emails_manager.php   # Emails tab inside Client profile
│   │   ├── client_hosting_manager.php  # Hosting tab inside Client profile
│   │   ├── project_domain_manager.php  # Domain tab inside Project page
│   │   ├── project_emails_manager.php  # Emails tab inside Project page
│   │   └── project_hosting_manager.php # Hosting tab inside Project page
│   │
│   └── tables/                         # Datatable partial views
│
├── migrations/
│   ├── 101_version_101.php             # Initial tables + columns
│   └── 102_version_102.php             # Hostinger API sync columns
│
├── helpers/
│   └── domain_manager_helper.php       # Utility helper functions
│
├── language/                           # Language translation files
└── assets/                             # CSS, JS, images
```

---

## Database Schema

### `tbl_domain_manager` — Domains Table

| Column | Type | Description |
|---|---|---|
| `id` | INT AUTO_INCREMENT | Primary key |
| `domain_name` | VARCHAR(255) | Domain name (required) |
| `registrar` | VARCHAR(255) | Registrar name |
| `purchase_date` | DATE | Domain purchase date |
| `expiry_date` | DATE | Domain expiry date |
| `status` | VARCHAR(255) | `active`, `expired`, etc. |
| `domain_type` | VARCHAR(50) | `external` or `hostinger` |
| `dns_hosting` | VARCHAR(255) | DNS hosting status |
| `provider_name` | VARCHAR(255) | Provider name |
| `provider_url` | VARCHAR(255) | Provider panel URL |
| `username` | VARCHAR(255) | Panel login username |
| `password` | VARCHAR(255) | Panel login password |
| `registration_status` | VARCHAR(255) | Registration status |
| `client_id` | INT(11) | FK to tbl_clients.userid |
| `client_email` | VARCHAR(255) | Client email reference |
| `available_mailbox_count` | INT(11) | Number of available mailboxes |
| `start_date` | DATE | Start date |
| `project_id` | INT(11) | FK to tbl_projects.id |
| `created_by` | INT(11) | Staff user who created |
| `description` | TEXT | Additional notes |
| `hostinger_domain_id` | VARCHAR(100) | Hostinger internal domain ID |
| `hostinger_synced_at` | DATETIME | Last sync timestamp from Hostinger |
| `deleted` | TINYINT(1) | Soft-delete flag (0 = active) |
| `created_at` | DATETIME | Record creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

---

### `tbl_hosting_details` — Hosting Plans Table

| Column | Type | Description |
|---|---|---|
| `id` | INT AUTO_INCREMENT | Primary key |
| `domain_id` | INT | FK to tbl_domain_manager.id |
| `website_name` | VARCHAR(255) | Website/plan name |
| `provider` | VARCHAR(255) | Hosting provider |
| `start_date` | DATE | Hosting start date |
| `expiration_date` | DATE | Hosting expiry date |
| `access_url` | VARCHAR(255) | Control panel URL |
| `username` | VARCHAR(255) | Panel username |
| `password` | TEXT | Panel password |
| `status` | VARCHAR(255) | `active`, `expired`, etc. |
| `client_id` | INT(11) | FK to tbl_clients.userid |
| `project_id` | INT(11) | FK to tbl_projects.id |
| `created_by` | INT(11) | Staff user who created |
| `description` | TEXT | Additional notes |
| `datacenter` | VARCHAR(100) | Datacenter/region |
| `hostinger_website_id` | VARCHAR(255) | Hostinger internal website ID |
| `hostinger_synced_at` | DATETIME | Last sync timestamp |
| `deleted` | TINYINT(1) | Soft-delete flag (0 = active) |
| `created_at` | DATETIME | Record creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

---

### `tbl_emails_manager` — Email Accounts Table

Manages mailbox / email account records linked to domains and clients.

---

### `tbl_expiry_notification_logs` — Notification Log Table

Tracks which expiry notification emails have already been sent to prevent duplicate sends.

| Column | Description |
|---|---|
| `domain_id` | Domain or hosting plan ID |
| `asset_type` | `domain` or `website` |
| `email_sent_to` | Recipient email address |
| `days_before_expiry` | Notification trigger (e.g., 7, 30) |
| `sent_at` | Timestamp of email send |

---

## Hostinger API Integration

The module integrates with the **Hostinger API** (https://developers.hostinger.com) to auto-import and sync domain and website data.

### Configuration
1. Log in to hpanel.hostinger.com
2. Go to **Profile → API Settings** → Create a new API token
3. In PerfexCRM: **Admin → Settings → Domain Manager** → Paste the token and save

### Sync Methods (in `Hostinger_api_model.php`)

| Method | Description |
|---|---|
| `sync_domains()` | Fetches all domains from Hostinger and upserts into `tbl_domain_manager` |
| `sync_websites()` | Fetches all hosting plans from Hostinger and upserts into `tbl_hosting_details` |
| `get($endpoint, $params)` | Internal authenticated GET request to Hostinger REST API |
| `post($endpoint, $data)` | Internal authenticated POST request |

### Cron Auto-Sync
The module hooks into PerfexCRM's `after_cron_run` action. Sync runs **once every 24 hours** automatically.

---

## Expiry Notification System

Automated email notifications are sent to configured recipients when a domain or hosting plan is approaching expiry.

### Notification Trigger Days
Configurable via **Admin → Settings → Domain Manager**. Default: `30, 15, 7, 3, 1, 0` days before expiry.

### Recipient Routing Options

| Option | Who Gets Notified |
|---|---|
| `Customer Only` | Primary contact of the linked client |
| `Staff Only` | Staff assigned to the domain/hosting |
| `Customer's Contact Email + Staff Assigned to Customer` | Both customer and assigned staff (default) |
| `Customer + Assigned Staff + Additional Emails` | All of the above + custom email list |

### Duplicate Prevention
Each notification send is logged in `tbl_expiry_notification_logs`. Before sending, the system checks if an email was already sent for the same asset, recipient, day threshold, and date. Duplicates are skipped.

### Notification Flow
1. Cron fires → `domain_manager_automated_sync()` runs
2. All domains & hosting records are checked against today's date
3. Records expiring in configured days are collected
4. For each expiring asset, the system determines recipients
5. Emails are sent via PerfexCRM's SMTP settings
6. Each send is logged to prevent duplicates

---

## Module Settings (Admin Panel)

Navigate to **Admin → Settings → Domain Manager** to configure:

| Setting | Description |
|---|---|
| **Hostinger API Token** | Bearer token for Hostinger API access |
| **Notify Recipients** | Who receives expiry notifications |
| **Notify Days** | Comma-separated days before expiry to send alerts (e.g., `30,15,7,3,1,0`) |
| **Specific Staff IDs** | Comma-separated staff IDs to always notify |
| **Additional Emails** | Comma-separated extra email addresses for notifications |

---

## Permissions

The module defines granular permissions for staff members:

| Permission | Description |
|---|---|
| `view` | View domain list and details |
| `create` | Add new domains |
| `edit` | Modify existing domains |
| `delete` | Soft-delete domains |

Permissions are managed through **Admin → Staff → Roles**.

---

## Migrations

| Migration | Version | Changes |
|---|---|---|
| `101_version_101.php` | v1.0.1 | Creates `tbl_domain_manager` and `tbl_hosting_details` tables; adds `provider_name`, `provider_url`, `username`, `password` columns |
| `102_version_102.php` | v1.0.2 | Adds Hostinger sync columns: `hostinger_domain_id`, `domain_type`, `hostinger_synced_at` to domains; adds `hostinger_website_id`, `website_name`, `datacenter`, `hostinger_synced_at` to hosting; registers API token option |

---

## Changes Made During Development

### 1. Hostinger API Model (`models/Hostinger_api_model.php`)
- Built full REST API integration class with Bearer token authentication
- Implemented `sync_domains()` to import Hostinger domains via API
- Implemented `sync_websites()` to import hosting plans via API
- Added cURL-based `get()` and `post()` private methods with SSL verification, timeouts, and error handling
- Added upsert logic to avoid duplicates during sync

### 2. Main Module Bootstrap (`domain_manager_hostinger.php`)
- Added `after_cron_run` hook to trigger `domain_manager_automated_sync()`
- Implemented 24-hour throttle check for cron sync (prevents repeated API calls)
- Built complete **expiry notification system**:
  - Collects all expiring domains and hosting plans
  - Supports multiple recipient routing options
  - Sends per-recipient, per-asset, per-day emails via PerfexCRM SMTP
  - Logs each notification to prevent duplicate sends
  - Falls back inherited assigned staff from parent domain for hosting plans
  - Validates client is active before sending customer-facing emails

### 3. Migration `102_version_102.php` (NEW FILE)
- Added `hostinger_domain_id` column to `tbl_domain_manager`
- Added `domain_type` column (`external` / `hostinger`) to `tbl_domain_manager`
- Added `hostinger_synced_at` datetime to `tbl_domain_manager`
- Added `hostinger_website_id` column to `tbl_hosting_details`
- Added `website_name` column to `tbl_hosting_details`
- Added `datacenter` column to `tbl_hosting_details`
- Added `hostinger_synced_at` datetime to `tbl_hosting_details`
- Registered `domain_manager_hostinger_api_token` option placeholder

### 4. Install Script (`install.php`)
- Updated schema to include all new Hostinger sync columns in initial table creation
- Added `available_mailbox_count`, `client_email`, `start_date`, `domain_type` fields
- Added `hostinger_domain_id`, `hostinger_synced_at` to domain_manager table
- Added `website_name`, `datacenter`, `hostinger_website_id`, `hostinger_synced_at` to hosting_details table
- Created `tbl_expiry_notification_logs` table for tracking sent notifications

### 5. Domain Manager Model (`models/Domain_manager_model.php`)
- Enhanced `get()` method with LEFT JOINs to include `client_name` from `tbl_clients` and `project_name` from `tbl_projects`
- Added soft-delete filtering (`deleted = 0`) to all queries
- Added `ORDER BY id DESC` for default listing

### 6. Email Manager Model (`models/Email_manager_model.php`)
- Added `get_emails_with_relations()` method that JOINs `tbl_domain_manager` and `tbl_clients` for enriched email listing
- Soft-delete filtering applied to all list queries

### 7. Admin Tab Views (`views/admin/`) — 6 NEW FILES
- Created tab partial views for embedding data inside **Client** and **Project** detail pages:
  - `client_domain_manager.php` — Domains tab on Client profile
  - `client_emails_manager.php` — Emails tab on Client profile
  - `client_hosting_manager.php` — Hosting tab on Client profile
  - `project_domain_manager.php` — Domains tab on Project page
  - `project_emails_manager.php` — Emails tab on Project page
  - `project_hosting_manager.php` — Hosting tab on Project page

### 8. Diagnostic & Debug Scripts (root of module)
Several diagnostic scripts were created during debugging and testing:
- `check_admin.php` — Verifies admin session/permissions
- `check_data.php` — Checks domain data in DB
- `check_domains.php` — Lists domains from DB
- `check_logs.php` — Reads expiry notification logs
- `check_smtp.php` — Tests SMTP configuration
- `check_table.php` — Verifies table existence
- `diag_settings.php` — Dumps module settings from DB
- `error_debugger.php` — Generic PHP error reader
- `fix_smtp.php` — Quick SMTP fix utility
- `get_admin.php` — Fetches admin user info
- `get_error_log.php` — Reads PHP error log
- `reset_lead.php` — Resets lead test data
- `setup_trigger.php` — Trigger management utility
- `trigger_notifications.php` — Manually triggers the expiry notification cron for testing

---

## Installation

### Prerequisites
- XAMPP (PHP 8.1+, MySQL, Apache)
- PerfexCRM installed at `C:\xampp\htdocs\perfex`

### Steps
1. Copy the `domain_manager_hostinger` folder into:
   ```
   C:\xampp\htdocs\perfex\modules\
   ```
2. Open browser and navigate to:
   ```
   http://localhost/perfex/
   ```
3. Log in as Admin
4. Go to **Admin → Settings → Modules**
5. Find **Domain Manager Hostinger** and click **Install**
6. Database tables are created automatically
7. Go to **Admin → Settings → Domain Manager** and enter your Hostinger API token
8. Done!

---

## Troubleshooting

### Module not appearing in Modules list
- Ensure the folder name exactly matches: `domain_manager_hostinger`
- Check that `domain_manager_hostinger.php` exists in the module root

### Hostinger sync not working
- Verify API token is saved in settings
- Run `trigger_notifications.php` manually to test
- Check PHP error log at `C:\xampp\php\logs\php_error_log`

### Expiry emails not sending
- Verify SMTP settings in **Admin → Settings → Email**
- Check `tbl_expiry_notification_logs` for duplicate-sent records
- Run `check_smtp.php` from the module root for diagnostics

### Database table errors
- Run `check_table.php` to verify table existence
- Re-run the module install from **Admin → Modules**
- Ensure DB prefix matches your PerfexCRM configuration

---

## Support

For support, contact **Virrat Global** at https://virratglobal.com/

---

*README last updated: June 2026*
