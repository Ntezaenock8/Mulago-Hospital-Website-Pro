# MULAGO HOSPITAL MANAGEMENT SYSTEM
## SYSTEM DOCUMENTATION

**Version:** 1.0  
**Status:** Production Ready (for Development/Testing)  
**Last Updated:** March 28, 2026  

---

## TABLE OF CONTENTS

1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [Installation & Setup](#installation--setup)
4. [Database Schema](#database-schema)
5. [API Reference](#api-reference)
6. [User Guides](#user-guides)
7. [Admin Guide](#admin-guide)
8. [Developer Guide](#developer-guide)
9. [Troubleshooting](#troubleshooting)
10. [Known Limitations](#known-limitations)

---

## SYSTEM OVERVIEW

### Purpose
The Mulago Hospital Management System is a lightweight, web-based healthcare appointment and clinic management platform designed for the Mulago Hospital complex in Uganda. It enables patients to book appointments, check clinic hours, and receive health alerts while providing administrators tools to manage clinic schedules, emergency notices, and health information.

### Target Users
- **Patients:** Book appointments, check clinic hours, view specialists
- **Admin Staff:** Manage clinic hours, post emergency notices, view appointments
- **Records Staff:** Access appointment history and patient data
- **Matron:** Monitor clinic operations and alerts

### Key Features
- 📅 **Appointment Booking System** - Book appointments across 12 departments
- 🕐 **Clinic Hours Management** - Dynamic scheduling with day-specific hours
- 🚨 **Emergency Notice System** - Real-time alerts displayed on homepage
- 👨‍⚕️ **Specialist Directory** - View available doctors and departments
- 🏥 **Department Overview** - Browse all hospital institutes/directorates
- 💊 **Health Alerts** - Public health bulletins and notices
- 🔐 **Role-Based Access** - Admin, Matron, and Records staff roles

### Technical Stack
- **Backend:** PHP 8.x
- **Database:** SQLite3
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Server:** Apache/PHP-CLI (localhost:8000)
- **Responsive:** Mobile-friendly design

---

## ARCHITECTURE

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                       PUBLIC WEB INTERFACE                   │
│  (index_public.html, appointment_public.html, etc.)          │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────┴───────────────────────────────────────────┐
│                    JAVASCRIPT LAYER                          │
│  (main.js - API calls, dynamic content loading)              │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────┴───────────────────────────────────────────┐
│                  API & AUTH ENDPOINTS                        │
│  ├─ admin_login.php (Authentication)                        │
│  ├─ get_departments.php (Public data)                       │
│  ├─ get_clinic_hours.php (Schedule data)                    │
│  ├─ and 16 other API endpoints                              │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────┴───────────────────────────────────────────┐
│              DATABASE ABSTRACTION LAYER                       │
│  (database.php - SQL query execution, data validation)       │
└─────────────────┬───────────────────────────────────────────┘
                  │
┌─────────────────┴───────────────────────────────────────────┐
│                  SQLITE3 DATABASE                            │
│  (data/mulago.db - 8 tables, 77KB)                           │
└─────────────────────────────────────────────────────────────┘
```

### Directory Structure
```
mulago/
├── index.html                          # Login page
├── index_public.html                   # Homepage (with emergency notice)
├── admin/                              # Admin dashboard
│   ├── index.html
│   ├── dashboard.html
│   ├── walkin.html                     # Clinic hours management
│   ├── appointments.html               # Appointment viewer
│   ├── alerts.html                     # Health alerts editor
│   └── [other admin pages]
├── php/                                # Backend API endpoints
│   ├── database.php                    # Database abstraction layer
│   ├── admin_login.php                 # Authentication
│   ├── get_departments.php             # Retrieve departments
│   ├── get_clinic_hours.php            # Retrieve clinic schedules
│   ├── save_specialist.php             # Save doctor info
│   ├── [16 other API endpoints]
│   └── admin_logout.php
├── css/
│   ├── style.css                       # Main stylesheet
│   └── admin.css                       # Admin dashboard styles
├── js/
│   ├── main.js                         # Frontend script
│   └── admin.js                        # Admin dashboard script
├── data/
│   └── mulago.db                       # SQLite3 database
└── [documentation files]
```

---

## INSTALLATION & SETUP

### Requirements
- **PHP 7.1+** (PHP-CLI or Apache with mod_php)
- **SQLite3** (included with PHP)
- **Modern Web Browser** (Chrome, Firefox, Edge, Safari)
- **Windows/Linux/Mac** support

### Quick Start

#### Step 1: Download & Extract
```bash
cd c:\Users\JOTHAM\Desktop\HUSTLE\mulago
# or use a PHP server:
php -S localhost:8000
```

#### Step 2: Access Application
- **User Interface:** http://localhost:8000/index_public.html
- **Admin Dashboard:** http://localhost:8000/admin/index.html

#### Step 3: Login to Admin (Demo Credentials)
```
Username: admin
Password: mulago2024

Alternative:
Username: matron
Password: mulago2024

Username: records
Password: mulago2024
```

#### Step 4: Verify Installation
- Check that 12 departments appear on the clinic hours page
- Verify emergency notice appears on homepage
- Check that admin login works

#### Step 5: (Optional) Reset Database
```bash
# Delete the database file - it auto-recreates on next API call
rm data/mulago.db
```

### Database Auto-Initialization
On first run, the system automatically:
1. Creates `data/mulago.db` if it doesn't exist
2. Initializes 8 database tables
3. Seeds 12 departments with clinic hours
4. Creates emergency notice
5. Sets up appointment tracking

---

## DATABASE SCHEMA

### Tables Overview

#### 1. **departments**
Stores hospital departments/institutes.
```sql
CREATE TABLE IF NOT EXISTS departments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```
**Records:** 12 departments (Cardiology, Oncology, Paediatrics, etc.)

---

#### 2. **doctors**
Stores healthcare professionals/specialists.
```sql
CREATE TABLE IF NOT EXISTS doctors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    department_id INTEGER,
    name TEXT NOT NULL,
    specialization TEXT,
    email TEXT,
    phone TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(department_id) REFERENCES departments(id)
)
```

---

#### 3. **patients**
Stores patient information (basic).
```sql
CREATE TABLE IF NOT EXISTS patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    email TEXT,
    visit_type_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

---

#### 4. **appointments**
Transaction log of all appointment bookings.
```sql
CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    patient_id INTEGER,
    department_id INTEGER,
    appointment_date TEXT NOT NULL,
    time_slot TEXT,
    status_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(patient_id) REFERENCES patients(id),
    FOREIGN KEY(department_id) REFERENCES departments(id),
    FOREIGN KEY(status_id) REFERENCES appointment_statuses(id)
)
```

---

#### 5. **clinic_hours**
Stores department clinic schedules (key table).
```sql
CREATE TABLE IF NOT EXISTS clinic_hours (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    department_id INTEGER,
    day_of_week INTEGER,        -- 0=Monday, 1=Tuesday... 6=Sunday
    opening_time TEXT,           -- e.g., "08:00"
    closing_time TEXT,           -- e.g., "17:00"
    is_open INTEGER,             -- 1 = open, 0 = closed
    walk_in_fee_ugx INTEGER,     -- Fee in Uganda Shillings
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(department_id) REFERENCES departments(id)
)
```
**Records:** 7 days × 12 departments = 84 rows (baseline)

---

#### 6. **clinic_notices**
Stores emergency notices/alerts displayed on homepage.
```sql
CREATE TABLE IF NOT EXISTS clinic_notices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    content TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

---

#### 7. **health_alerts**
Public health bulletins for patients.
```sql
CREATE TABLE IF NOT EXISTS health_alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    alert_type TEXT,            -- 'warning', 'info', 'success'
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

---

#### 8. **appointment_statuses**
Lookup table for appointment states.
```sql
CREATE TABLE IF NOT EXISTS appointment_statuses (
    id INTEGER PRIMARY KEY,
    status_name TEXT NOT NULL
)
```
**Sample Data:**
- 1 = Booked
- 2 = Confirmed
- 3 = Completed
- 4 = Cancelled
- 5 = No-Show

---

### Sample Data Seeding

#### Departments (12 Total)
```
1. Cardiology
2. Oncology
3. Paediatrics
4. General Medicine
5. Surgery
6. Obstetrics & Gynaecology
7. Psychiatry
8. Infectious Diseases
9. Nephrology
10. Neurology
11. Orthopedics
12. Emergency & Trauma
```

#### Clinic Hours Example (Monday - Cardiology)
```
Department: Cardiology
Day: Monday (0)
Opening: 08:00
Closing: 17:00
Is Open: 1 (Yes)
Walk-in Fee: 50,000 UGX
```

#### Emergency Notice (Seeded)
```
Content: "We are rennovating the West Wing Currently"
Is Active: 1 (Displayed)
```

---

## API REFERENCE

### Base URL
```
http://localhost:8000/php/[endpoint].php
```

### Public Endpoints (No Authentication Required)

#### 1. Get All Departments
```
GET /php/get_departments.php

Response (JSON):
{
    "success": true,
    "departments": [
        {
            "id": 1,
            "code": "CARD",
            "name": "Cardiology",
            "description": "Heart and cardiovascular diseases"
        },
        ...
    ]
}
```

---

#### 2. Get Clinic Hours
```
GET /php/get_clinic_hours.php

Response (JSON):
{
    "success": true,
    "clinic_hours": [
        {
            "department_id": 1,
            "department_name": "Cardiology",
            "monday_open": "08:00",
            "monday_close": "17:00",
            "monday_is_open": 1,
            "tuesday_open": "08:00",
            ...
            "walk_in_fee": 50000
        },
        ...
    ]
}
```

---

#### 3. Get Emergency Notice
```
GET /php/get_emergency_notice.php

Response (JSON):
{
    "success": true,
    "notice": {
        "id": 1,
        "content": "We are rennovating the West Wing Currently",
        "is_active": 1
    }
}
```

---

#### 4. Get Specialists
```
GET /php/get_specialists.php

Response (JSON):
{
    "success": true,
    "specialists": [
        {
            "id": 1,
            "department_id": 1,
            "name": "Dr. John Sempijja",
            "specialization": "Cardiologist",
            "email": "john@mulago.ug"
        },
        ...
    ]
}
```

---

#### 5. Get All Health Alerts
```
GET /php/get_alerts.php

Response (JSON):
{
    "success": true,
    "alerts": [
        {
            "id": 1,
            "title": "Cholera Prevention",
            "content": "Wash hands with clean water",
            "alert_type": "warning",
            "is_active": 1
        },
        ...
    ]
}
```

---

#### 6. Submit Appointment
```
POST /php/submit_appointment.php

Request (JSON):
{
    "patient_name": "John Doe",
    "patient_phone": "256700123456",
    "patient_email": "john@example.com",
    "department_id": 1,
    "appointment_date": "2026-04-15",
    "visit_type_id": 1,
    "notes": "Follow-up for medication"
}

Response (JSON):
{
    "success": true,
    "message": "Appointment booked successfully",
    "appointment_id": 42
}
```

---

### Admin Endpoints (Authentication Required)

#### 1. Admin Login
```
POST /php/admin_login.php

Request (JSON or form-encoded):
{
    "username": "admin",
    "password": "mulago2024"
}

Response (JSON):
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "username": "admin",
        "role": "Administrator"
    }
}
```
**Supports both:** JSON payloads AND traditional form-encoded data

---

#### 2. Save Emergency Notice
```
POST /php/save_alert.php

Request (JSON):
{
    "content": "New emergency notice text",
    "is_active": 1
}

Response (JSON):
{
    "success": true,
    "message": "Notice saved successfully",
    "notice_id": 1
}
```
**Requires:** Active session (login first)

---

#### 3. Update Clinic Hours
```
POST /php/update_status.php

Request (JSON):
{
    "department_id": 1,
    "day_of_week": 0,
    "opening_time": "08:00",
    "closing_time": "17:00",
    "is_open": 1,
    "walk_in_fee_ugx": 50000
}

Response (JSON):
{
    "success": true,
    "message": "Clinic hours updated successfully"
}
```
**Requires:** Active session

---

#### 4. Get Appointments
```
GET /php/get_appointments.php

Response (JSON):
{
    "success": true,
    "appointments": [
        {
            "id": 42,
            "patient_name": "John Doe",
            "department": "Cardiology",
            "appointment_date": "2026-04-15",
            "status": "Booked",
            "created_at": "2026-03-28 10:30:00"
        },
        ...
    ]
}
```
**Requires:** Active session

---

#### 5. Admin Logout
```
GET /php/admin_logout.php

Response (JSON):
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## USER GUIDES

### For Patients (Public Interface)

#### Accessing the System
1. Open http://localhost:8000/index_public.html
2. You'll see emergency notices at the top (if any active)
3. Use navigation menu to browse departments

#### Booking an Appointment
1. Click **"Book Appointment"** button
2. Select a **Department** from dropdown
3. Choose **Appointment Date** (future dates only)
4. Fill in **Your Information:**
   - Full Name
   - Phone Number (format: 256700123456)
   - Email Address
   - Visit Type (Walk-in, Follow-up, etc.)
   - Notes (optional)
5. Review appointment shows:
   - ✅ Green if department is OPEN on selected date
   - ❌ Red if department is CLOSED (appointment refused)
6. Click **Submit** to book

#### Checking Clinic Hours
1. Click **"Clinic Hours"** in menu
2. See all 12 departments with:
   - Opening and closing times (each day of week)
   - Walk-in fees in Uganda Shillings
   - Green/Red indicator for OPEN/CLOSED status

#### Viewing Specialists
1. Click **"Specialists"** in menu
2. See list of available doctors by department
3. View their specialization and contact email

#### Health Information
1. Click **"Health Alerts"** in menu
2. Read current public health bulletins
3. Follow safety recommendations

---

### For Admin/Staff (Admin Dashboard)

#### Accessing Admin Dashboard
1. Navigate to http://localhost:8000/admin/index.html
2. Enter credentials:
   - Admin: `admin` / `mulago2024`
   - Matron: `matron` / `mulago2024`
   - Records: `records` / `mulago2024`
3. Click **"Login"**

#### Dashboard Menu
- **Dashboard:** Overview of appointments, staff
- **Clinic Hours:** Manage department schedules
- **Appointments:** View all booked appointments
- **Alerts:** Create/update emergency notices
- **Specialists:** Manage doctor information
- **Walk-in:** Monitor real-time check-ins
- **Settings:** Change admin settings
- **Logout:** Exit and return to login

#### Managing Clinic Hours
1. Go to **Admin Dashboard → Clinic Hours**
2. You'll see a table with all 12 departments
3. For each department/day:
   - Edit opening time (e.g., 08:00)
   - Edit closing time (e.g., 17:00)
   - Toggle OPEN/CLOSED status
   - Update walk-in fee
4. Click **Save Changes** to update database

#### Creating Emergency Notice
1. Go to **Admin Dashboard → Alerts** (or Clinic Notices)
2. Click **"New Notice"** button
3. Enter notice text:
   - "We are rennovating the West Wing Currently"
   - Or any urgent message
4. Toggle **"Active"** to display on homepage
5. Click **"Save"** - notice appears immediately for all patients

#### Viewing Appointments
1. Go to **Admin Dashboard → Appointments**
2. See all booked appointments with:
   - Patient name & contact
   - Department & date
   - Booking status (Booked, Confirmed, Completed, Cancelled)
   - Booking timestamp
3. Click appointment for more details

---

## ADMIN GUIDE

### System Administration

#### User Management
Currently using 3 demo roles (hardcoded). For production:

```php
// file: php/admin_login.php

// Expected change for production:
// Query users from database instead of hardcoded array
$valid_users = queryDatabase("SELECT * FROM admin_users WHERE username=?");
```

#### Backup & Recovery

**Backup Database:**
```bash
# Windows Command Prompt
copy data\mulago.db data\mulago_backup_$(date /T).db

# Or Linux/Mac
cp data/mulago.db data/mulago_backup_$(date +%Y%m%d).db
```

**Restore Database:**
```bash
copy data\mulago_backup_20260328.db data\mulago.db
```

**Complete Reset:**
```bash
# Delete database to get fresh seeding
del data\mulago.db
# Next API call will auto-recreate
```

---

#### Monitoring

**Check System Health:**
Visit http://localhost:8000/php/check_db.php
- Verifies database connectivity
- Shows table status
- Confirms auto-initialization worked

**View Error Logs:**
- PHP errors appear in terminal/console where you ran `php -S localhost:8000`
- Check browser console (F12 → Console tab) for JavaScript errors

---

#### Performance Tuning

**Database Size Standards:**
- Fresh install: ~77 KB
- Per appointment added: ~200 bytes
- Per patient: ~150 bytes
- After 1 year (5000 appointments): ~77 KB + ~1000 KB ≈ 1.1 MB

**Optimization Tips:**
1. Archive old appointments (create `appointments_archive` table yearly)
2. Rebuild database index:
```sql
VACUUM;  -- Reduces file size
ANALYZE; -- Optimizes query performance
```

3. Monitor database file size:
```bash
# Windows
dir data\mulago.db

# Linux/Mac
ls -lh data/mulago.db
```

---

## DEVELOPER GUIDE

### Code Structure

#### Key Files for Development

**[database.php](php/database.php)** - Database Abstraction Layer
- Contains all SQL functions
- Key methods:
  - `initializeDatabase()` - Creates tables
  - `getClinicHours()` - Retrieves clinic schedules (12 departments)
  - `isDepartmentOpenOnDate()` - Checks if dept open on specific date
  - `saveDepartmentHours()` - Updates clinic schedule
  - `saveEmergencyNotice()` - Updates homepage alert

**[admin_login.php](php/admin_login.php)** - Authentication Handler
- Handles both JSON and form-encoded POST
- Validates credentials against hardcoded array
- Creates PHP session on success
- Returns error for invalid credentials

**[main.js](js/main.js)** - Frontend JavaScript
- API client functions
- Dynamic content loading
- Form validation before appointment submission
- Emergency notice refresh (5-minute intervals)

**[style.css](css/style.css)** - Main Stylesheet
- `.alert-banner` - Emergency notice styling
- `.clinic-schedule-table` - Clinic hours table layout
- `.btn-*` - Button styles
- Responsive breakpoints for mobile

---

### Common Development Tasks

#### Adding New API Endpoint

1. Create file: `php/new_endpoint.php`
```php
<?php
session_start();
header('Content-Type: application/json');
require 'database.php';

try {
    // Check authentication if needed
    if (!isset($_SESSION['admin_user'])) {
        throw new Exception('Unauthorized');
    }
    
    // Get request data
    $json = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (empty($json['required_field'])) {
        throw new Exception('Missing required field');
    }
    
    // Call database method
    $result = methodFromDatabase($json['data']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

2. Call from JavaScript:
```javascript
fetch('/php/new_endpoint.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        required_field: 'value'
    })
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Success:', data.data);
    } else {
        console.error('Error:', data.error);
    }
});
```

---

#### Adding New Database Table

Edit [database.php](php/database.php) > `initializeDatabase()`:

```php
$db->exec("CREATE TABLE IF NOT EXISTS new_table (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
```

---

#### Modifying Clinic Hours Logic

Edit [database.php](php/database.php) > `isDepartmentOpenOnDate()`:

Current logic uses SQL CASE statement to check day of week:
```php
$query = "SELECT 
    CASE 
        WHEN strftime('%w', ?) = '1' THEN monday_is_open
        WHEN strftime('%w', ?) = '2' THEN tuesday_is_open
        ...
    END as is_open
    FROM clinic_hours WHERE department_id = ?";
```

To add custom logic (e.g., holidays): Modify the CASE statement to exclude holiday dates.

---

### Testing Locally

#### Using the UAT Test Suite

Run comprehensive tests:
```bash
python uat_comprehensive.py
# Expected: 10/10 tests passing
```

Test single endpoint with curl:
```bash
# Get departments
curl http://localhost:8000/php/get_departments.php

# Admin login
curl -X POST http://localhost:8000/php/admin_login.php \
  -H "Content-Type: application/json" \
  -d "{\"username\":\"admin\",\"password\":\"mulago2024\"}"
```

---

### Debugging

#### Enable Debug Output
Edit any PHP file and add at top:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

#### Check Database
```bash
# Windows - Install SQLite from: https://www.sqlite.org/download.html
sqlite3 data/mulago.db
sqlite> .tables
sqlite> SELECT COUNT(*) FROM appointments;
sqlite> .exit
```

#### Browser Console (F12)
Check for JavaScript errors:
- Network tab: View API responses
- Console tab: Error messages
- Application tab: Session cookies

---

## TROUBLESHOOTING

### Common Issues & Solutions

| Problem | Cause | Solution |
|---------|-------|----------|
| **"Page not found"** | Wrong URL or PHP not running | Check URL: http://localhost:8000/index_public.html<br>Start PHP: `php -S localhost:8000` |
| **"Admin login fails"** | Wrong credentials or session issue | Use `admin`/`mulago2024`<br>Check browser cookies enabled |
| **"No clinics showing"** | Database not initialized | Delete `data/mulago.db` and refresh |
| **"Appointment rejected - CLOSED"** | Department closed on date | Check clinic hours; select different date |
| **"Can't update clinic hours"** | Not logged in | Login to admin first |
| **"404 on API endpoints"** | PHP files missing | Verify `php/` directory has all `.php` files |
| **"Database locked"** | Concurrent access | Restart PHP server: Stop and `php -S localhost:8000` |
| **"Emergency notice not showing"** | Notice not active | Admin → Alerts → toggle "Active" |

---

### Getting Help

**For Database Issues:**
```bash
# Check database integrity
http://localhost:8000/php/check_db.php

# View schema
sqlite3 data/mulago.db ".schema"
```

**For API Issues:**
- Check Network tab in browser (F12)
- Copy failed request URL and test in new tab
- Check admin_login.php for auth errors

**For Frontend Issues:**
- Open browser Console (F12 → Console)
- Check if JavaScript errors appear
- Verify CSS is loading (Network tab)

---

## KNOWN LIMITATIONS

### Limitations & Notes

1. **SQLite3 Database**
   - ⚠️ Not suitable for >10 concurrent users
   - ⚠️ Recommended upgrade path: PostgreSQL for production
   - ✅ Perfect for development/testing

2. **Hardcoded Credentials**
   - ⚠️ Demo only - 3 roles hardcoded in admin_login.php
   - ⚠️ No password hashing (plaintext comparison)
   - ✅ For production: Use bcrypt + database-driven users

3. **Session Management**
   - ⚠️ No session timeout (30-minute auto-logout recommended)
   - ⚠️ No HTTPS enforcement (develop on localhost only)
   - ✅ PHP sessions adequate for testing

4. **Scalability**
   - ⚠️ Single-threaded PHP-CLI
   - ⚠️ No caching layer
   - ✅ Suitable for 1-20 concurrent users

5. **Missing Features**
   - ❌ SMS appointment confirmations (ready for third-party API)
   - ❌ Email notifications (ready for mail library)
   - ❌ Audit logging (data changes not tracked)
   - ❌ Backup automation (manual process only)

6. **Browser Support**
   - ✅ Chrome 90+
   - ✅ Firefox 88+
   - ✅ Safari 14+
   - ✅ Edge 90+
   - ⚠️ IE11 not tested (modern JS features used)

---

## FUTURE ROADMAP

### Phase 2 Features (Q2 2026)
- [ ] Email notifications for appointments
- [ ] SMS reminders (Twilio integration)
- [ ] Patient portal (check appointment status)
- [ ] Advanced search & filtering
- [ ] Appointment cancellation by patients

### Phase 3 Features (Q3 2026)
- [ ] Doctor availability calendar
- [ ] Waitlist management
- [ ] Queue monitoring dashboard
- [ ] Staff shift scheduling
- [ ] Performance analytics

### Phase 4+ (Q4 2026+)
- [ ] Mobile app (React Native)
- [ ] Multi-language support
- [ ] Integration with external EMR systems
- [ ] Insurance verification
- [ ] Telemedicine capabilities

---

## CONCLUSION

The Mulago Hospital Management System provides a solid foundation for appointment booking and clinic management. The system is **stable for development/testing** and can be extended with the features above as needed. Follow the security recommendations before any production deployment.

**For questions or updates, refer to:**
- UAT_REPORT.md (system testing details)
- README_QUICK_START.md (quick reference)
- Individual PHP files (inline code comments)

---

**Documentation Version:** 1.0  
**Last Updated:** March 28, 2026  
**System Version:** 1.0 (UAT Complete)

