# 🏥 Mulago Hospital - Quick Start Guide

## ⚡ Start in 3 Steps

### Step 1: Start PHP Server
```bash
# From the /mulago directory
php -S localhost:8000
```

### Step 2: Open in Browser
- **Public Site**: http://localhost:8000/
- **Admin Panel**: http://localhost:8000/admin/

### Step 3: Login to Admin
- Username: `admin`
- Password: `mulago2024`

---

## 🎯 What You Can Do

### For Public Users
1. Visit homepage
2. Click "Book Appointment"
3. Fill form and submit
4. Get reference code for confirmation

### For Hospital Staff (Admin)
1. Login at `/admin/`
2. View all appointments from submitted bookings
3. Confirm or cancel appointments
4. View appointment details
5. Export data to CSV
6. Filter by department, status, or search by name

---

## 📂 Important Files

| File | Purpose |
|------|---------|
| `BACKEND_SETUP.md` | Complete API documentation |
| `INTEGRATION_SUMMARY.md` | Technical summary of changes |
| `php/submit_appointment.php` | Receives public form submissions |
| `php/get_appointments.php` | Admin API - fetches appointments |
| `php/update_status.php` | Admin API - changes appointment status |
| `data/mulago.db` | SQLite database (auto-created) |

---

## ❓ Troubleshooting

### "Connection Failed" Error
**Solution**: PHP server not running
```bash
php -S localhost:8000
```

### "Unauthorized" Error
**Solution**: Login first at http://localhost:8000/admin/index.html

### Appointments Not Showing in Admin
**Solution**: 
1. Ensure PHP server is running
2. Submit a new appointment on public site
3. Check if reference code appears (means it was saved)
4. Refresh admin appointments page

---

## 📖 Full Documentation

For complete documentation including:
- API endpoint details
- Database schema
- Security notes
- Testing procedures
- Troubleshooting guide

See: **BACKEND_SETUP.md**

For technical details of all changes:

See: **INTEGRATION_SUMMARY.md**

---

## ✅ System Status

- [x] Frontend appointment submissions save to database
- [x] Admin backend fetches real appointments
- [x] Status changes persist
- [x] Search and filters work
- [x] Session authentication active
- [x] Error handling implemented
- [x] Data export (CSV) functional

---

**Ready to go!** 🚀
