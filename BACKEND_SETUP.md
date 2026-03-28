# Mulago Hospital Backend Integration Guide

## 🚀 Quick Start

### 1. Start the PHP Development Server
From the `/mulago` directory, run:
```bash
php -S localhost:8000
```

### 2. Access the Application
- **Public Site**: http://localhost:8000/
- **Admin Dashboard**: http://localhost:8000/admin/
- **Admin Login**: http://localhost:8000/admin/index.html

### 3. Default Admin Credentials
| Username | Password |
|----------|----------|
| admin | mulago2024 |
| matron | mulago_matron |
| records | records_desk |

---

## 📋 System Architecture

### Frontend Flow
1. **Public Appointment Booking** (`appointment_public.html`)
   - Form submits to `php/submit_appointment.php` via POST
   - Generates unique reference code
   - Saves to `data/mulago.db` (SQLite)
   - Returns reference code to user

2. **Admin Login** (`admin/index.html`)
   - Submits to `php/admin_login.php`
   - Sets PHP `$_SESSION['admin_logged_in']`
   - Also stores in `sessionStorage` for immediate checks
   - Redirects to dashboard

3. **Admin Dashboard** (`admin/dashboard.html`)
   - Fetches appointment stats from `php/get_appointments.php`
   - Shows department breakdown from database
   - Auto-refreshes every 30 seconds
   - Quick action buttons link to appointments page

4. **Appointments Management** (`admin/appointments.html`)
   - Fetches appointments dynamically on every page load
   - Search, filter, and pagination all call backend
   - Status change buttons call `php/update_status.php`
   - Detail modal shows full appointment information
   - CSV export functionality included

5. **Admin Logout** (`admin/index.html` logout button)
   - Calls `php/admin_logout.php` via POST
   - Destroys PHP session
   - Clears session cookie
   - Returns to login page

### Backend Flow
```
Request → PHP Session Check → Database Query → Response (JSON)
```

**Protected Endpoints** (require session):
- `GET /php/get_appointments.php` - Fetch appointments
- `POST /php/update_status.php` - Update appointment status
- `POST /php/admin_logout.php` - Destroy session

**Public Endpoints** (no session required):
- `POST /php/submit_appointment.php` - Submit appointment form

---

## 🗄️ Database Schema

### Table: `appointments`
```sql
CREATE TABLE appointments (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  ref             TEXT NOT NULL UNIQUE,              -- e.g., MNR-ABC123
  first_name      TEXT NOT NULL,
  last_name       TEXT NOT NULL,
  nin             TEXT NOT NULL,                     -- National ID (14 chars)
  phone           TEXT NOT NULL,
  gender          TEXT,                              -- Male/Female/Other
  dob             TEXT,                              -- YYYY-MM-DD
  age             INTEGER,
  department      TEXT NOT NULL,                     -- Cardiology, Surgery, etc.
  preferred_doctor TEXT,                             -- Doctor name or null for "Any"
  reason          TEXT NOT NULL,                     -- Reason for visit
  preferred_date  TEXT,                              -- YYYY-MM-DD
  visit_type      TEXT DEFAULT 'New Patient',        -- New Patient/Review/Emergency
  referred_from   TEXT,                              -- Referring hospital/clinic
  status          TEXT DEFAULT 'pending',            -- pending/confirmed/cancelled/completed
  submitted_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Location
- File: `data/mulago.db`
- Auto-created on first submission
- Auto-created on first admin login
- All tables created automatically by PHP

---

## 🔍 API Endpoints Reference

### 1. Submit Appointment (Public)
**Endpoint**: `POST /php/submit_appointment.php`

**Parameters** (form data):
- `first_name` (required)
- `last_name` (required)
- `nin` (required, 14 chars)
- `phone` (required)
- `gender` (optional)
- `dob` (optional, YYYY-MM-DD)
- `age` (optional)
- `department` (required)
- `preferred_doctor` (optional)
- `reason` (required)
- `preferred_date` (optional, YYYY-MM-DD)
- `visit_type` (default: New Patient)
- `referred_from` (optional)

**Response**:
```json
{
  "success": true,
  "ref": "MNR-ABC123",
  "message": "Appointment request submitted successfully..."
}
```

---

### 2. Get Appointments (Admin)
**Endpoint**: `GET /php/get_appointments.php`

**Query Parameters**:
- `search` - Filter by name, NIN, or reference code
- `dept` - Filter by department
- `status` - Filter by status (pending/confirmed/cancelled/completed)
- `page` - Page number (default: 1, 8 per page)

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "ref": "MNR-ABC123",
      "first_name": "John",
      "last_name": "Doe",
      "nin": "CM12345678901A",
      "phone": "+256 ....",
      "gender": "Male",
      "dob": "1990-01-15",
      "age": 34,
      "department": "Cardiology",
      "preferred_doctor": "Dr. Smith",
      "reason": "Chest pains",
      "preferred_date": "2025-07-20",
      "visit_type": "New Patient",
      "referred_from": "Private Clinic",
      "status": "pending",
      "submitted_at": "2025-07-15T10:30:00"
    }
  ],
  "total": 15,
  "page": 1,
  "pages": 2,
  "per_page": 8,
  "stats": {
    "total": 15,
    "pending": 5,
    "confirmed": 7,
    "cancelled": 2,
    "completed": 1
  }
}
```

---

### 3. Update Appointment Status (Admin)
**Endpoint**: `POST /php/update_status.php`

**Parameters** (form data):
- `id` (required, appointment ID)
- `status` (required: pending/confirmed/cancelled/completed)

**Response**:
```json
{
  "success": true,
  "message": "Appointment updated to 'confirmed'."
}
```

---

### 4. Admin Login
**Endpoint**: `POST /php/admin_login.php`

**Parameters** (form data):
- `username` (required)
- `password` (required)

**Response**:
```json
{
  "success": true,
  "username": "admin",
  "message": "Login successful."
}
```

**Side Effect**: Sets PHP `$_SESSION['admin_logged_in'] = true`

---

### 5. Admin Logout
**Endpoint**: `POST /php/admin_logout.php`

**Response**:
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

**Side Effect**: Destroys session and clears session cookie

---

## ⚠️ Error Handling

### When PHP Server Isn't Running
The admin pages detect connection failures and display:
```
PHP Server Not Running

Run this command from the /mulago directory:
php -S localhost:8000
```

### When Not Authenticated
- API endpoints return: `{"success": false, "error": "Unauthorized."}`
- Admin pages redirect to login

### Form Validation Errors
Submit appointment endpoint returns:
```json
{
  "success": false,
  "error": "First name is required. NIN must be exactly 14 characters."
}
```

---

## 🧪 Testing Workflow

### Test 1: Book an Appointment
1. Go to http://localhost:8000/
2. Click "Book Appointment"
3. Fill form with:
   - Name: John Doe
   - NIN: CM12345678901A (14 chars)
   - Phone: +256701234567
   - Department: Cardiology
   - Reason: Chest pain
   - Date: 2025-07-20
4. Submit
5. Note the reference code

### Test 2: View in Admin
1. Go to http://localhost:8000/admin/
2. Login: admin / mulago2024
3. Go to Appointments
4. Search for "John Doe" - appointment should appear
5. Click View to see full details

### Test 3: Change Status
1. Click "Confirm" on the appointment
2. Verify status changed to "confirmed" in table
3. Stats should update

### Test 4: Logout
1. Click "Sign Out" button
2. Should redirect to login page
3. Try accessing /admin/appointments.html directly
4. Should redirect to login (sessionStorage cleared)

---

## 🔐 Security Notes

### Current Implementation (Demo)
- Passwords stored in plaintext in config
- Session-based auth using PHP `$_SESSION`
- CSRF protection NOT implemented
- SQL injection protection: Uses PDO prepared statements ✓

### Production Recommendations
1. Use `password_hash()` and `password_verify()` for passwords
2. Store credentials in database, not hardcoded
3. Implement CSRF tokens for state-changing requests
4. Use HTTPS in production
5. Set secure session cookie flags
6. Implement rate limiting on login
7. Add audit logging for sensitive actions
8. Use prepared statements (already done ✓)

---

## 📁 File Structure

```
mulago/
├── admin/
│   ├── index.html                 ← Login page
│   ├── dashboard.html             ← Dashboard (fetches from API)
│   ├── appointments.html          ← Appointments table (fetches from API)
│   ├── specialists.html
│   ├── alerts.html
│   ├── walkin.html
│   └── settings.html
├── php/
│   ├── admin_login.php            ← Session login handler
│   ├── admin_logout.php           ← Session logout handler (NEW)
│   ├── get_appointments.php       ← Fetch appointments (with session check)
│   ├── update_status.php          ← Update status (with session check)
│   ├── submit_appointment.php     ← Submit form (public)
│   └── save_alert.php
├── data/
│   └── mulago.db                  ← SQLite database
├── css/
│   ├── style.css                  ← Public pages
│   └── admin.css                  ← Admin pages (updated with badges)
└── js/
    ├── main.js
    └── admin.js
```

---

## 🐛 Troubleshooting

### Issue: "Connection Failed" Error
**Solution**: Start PHP server
```bash
php -S localhost:8000
```

### Issue: "Unauthorized" Error in Admin
**Solution**: Log in first at http://localhost:8000/admin/index.html

### Issue: Database Lock Error
**Solution**: SQLite can lock if two processes write simultaneously. Stop PHP server, delete `data/mulago.db`, restart.

### Issue: Appointments Not Showing After Booking
**Solution**: 
1. Check browser console for errors
2. Verify reference code was received
3. Check if `data/mulago.db` exists
4. Verify admin is logged in (check sessionStorage)

### Issue: Status Updates Aren't Working
**Solution**:
1. Verify session is active (should auto-refresh on dashboard)
2. Check browser network tab for errors in update_status.php response
3. Verify appointment ID is correct

---

## 📞 Quick Commands

```bash
# Start PHP server
php -S localhost:8000

# Check if PHP is running
# Try accessing http://localhost:8000/

# View database contents (requires SQLite CLI)
sqlite3 data/mulago.db "SELECT * FROM appointments;"

# Reset database (WARNING: deletes all data)
rm data/mulago.db
# Database will auto-recreate on first submission
```

---

## ✅ Verification Checklist

- [x] All hardcoded demo data removed from admin/appointments.html
- [x] Admin pages fetch from backend (php/get_appointments.php)
- [x] Status updates call backend (php/update_status.php)
- [x] Logout properly destroys session (php/admin_logout.php)
- [x] Error messages shown when PHP server isn't running
- [x] Search, filter, pagination all call backend
- [x] Database auto-creates on first appointment submission
- [x] Session validation on all protected endpoints
- [x] CSV export works with real data
- [x] Dashboard auto-refreshes every 30 seconds
