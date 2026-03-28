/* ============================================================
   MULAGO HOSPITAL — ADMIN DASHBOARD JAVASCRIPT
   Handles: Login, Appointments table, Stats, Alerts CRUD
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ─── CLOCK ────────────────────────────────────────────────
  const timeEl = document.getElementById('admin-clock');
  if (timeEl) {
    const tick = () => {
      const now = new Date();
      timeEl.textContent = now.toLocaleString('en-UG', {
        weekday: 'short', year: 'numeric', month: 'short',
        day: 'numeric', hour: '2-digit', minute: '2-digit'
      });
    };
    tick(); setInterval(tick, 1000);
  }

  // ─── LOGIN FORM ───────────────────────────────────────────
  const loginForm = document.getElementById('admin-login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = loginForm.querySelector('button[type=submit]');
      const errEl = document.getElementById('login-error');
      btn.textContent = 'Verifying…';
      btn.disabled = true;

      const data = new FormData(loginForm);
      try {
        const res = await fetch('../php/admin_login.php', { method: 'POST', body: data });
        const json = await res.json();
        if (json.success) {
          sessionStorage.setItem('admin_logged_in', '1');
          window.location.href = 'dashboard.html';
        } else {
          errEl.style.display = 'block';
          errEl.textContent = json.error || 'Invalid credentials.';
          btn.textContent = 'Sign In';
          btn.disabled = false;
        }
      } catch {
        // Demo mode fallback — check hardcoded creds
        const u = data.get('username');
        const p = data.get('password');
        if (u === 'admin' && p === 'mulago2024') {
          sessionStorage.setItem('admin_logged_in', '1');
          window.location.href = 'dashboard.html';
        } else {
          errEl.style.display = 'block';
          errEl.textContent = 'Invalid credentials. Try admin / mulago2024';
          btn.textContent = 'Sign In';
          btn.disabled = false;
        }
      }
    });
  }

  // ─── AUTH GUARD (dashboard pages) ─────────────────────────
  const isDashboard = document.body.classList.contains('admin-dashboard');
  if (isDashboard) {
    if (!sessionStorage.getItem('admin_logged_in')) {
      window.location.href = 'index.html';
      return;
    }
  }

  // ─── LOGOUT ───────────────────────────────────────────────
  document.getElementById('logout-btn')?.addEventListener('click', () => {
    sessionStorage.removeItem('admin_logged_in');
    window.location.href = 'index.html';
  });

  // ─── MOBILE SIDEBAR TOGGLE ────────────────────────────────
  const menuBtn  = document.getElementById('mobile-menu-btn');
  const sidebar  = document.querySelector('.admin-sidebar');
  menuBtn?.addEventListener('click', () => sidebar?.classList.toggle('open'));

  // ─── LOAD APPOINTMENTS ────────────────────────────────────
  if (document.getElementById('appts-tbody')) {
    loadAppointments();
    setupStats();
  }
});

// ─── DEMO DATA ────────────────────────────────────────────────
const DEMO_APPOINTMENTS = [
  { id: 1, ref: 'MNR-A8F2K1', name: 'Nakato Sarah',   nin: 'CM90001234567A', dept: 'Cardiology',   doctor: 'Prof. Charles Odeke',    date: '2025-07-14', reason: 'Chest pain and palpitations', status: 'pending',   phone: '0701234567' },
  { id: 2, ref: 'MNR-B3X9P7', name: 'Okello James',   nin: 'CM85007654321B', dept: 'Paediatrics',  doctor: 'Dr. Samuel Okello',       date: '2025-07-14', reason: 'Child with recurring fever',  status: 'confirmed', phone: '0789876543' },
  { id: 3, ref: 'MNR-C7M4Q2', name: 'Atim Christine', nin: 'CM92003456789C', dept: 'Oncology',     doctor: 'Dr. Peter Ogwang',        date: '2025-07-15', reason: 'Follow-up after biopsy',      status: 'pending',   phone: '0772345678' },
  { id: 4, ref: 'MNR-D1Z6R8', name: 'Mugisha Robert', nin: 'CM88006789012D', dept: 'Surgery',      doctor: 'Dr. John Wasswa',         date: '2025-07-15', reason: 'Hernia assessment',           status: 'confirmed', phone: '0756789012' },
  { id: 5, ref: 'MNR-E5H2T4', name: 'Namutebi Hope',  nin: 'CM95009012345E', dept: 'Obstetrics & Gynae', doctor: 'Dr. Josephine Atim', date: '2025-07-16', reason: 'Antenatal visit (28 weeks)', status: 'pending',   phone: '0741234567' },
  { id: 6, ref: 'MNR-F9J7V5', name: 'Byekwaso Paul',  nin: 'CM82002345678F', dept: 'Neurology',    doctor: 'Dr. Emmanuel Ssenyonga',  date: '2025-07-16', reason: 'Persistent headaches',        status: 'cancelled', phone: '0728901234' },
  { id: 7, ref: 'MNR-G4N3W9', name: 'Akello Mercy',   nin: 'CM97005678901G', dept: 'Dermatology',  doctor: 'Dr. Sarah Tumusiime',     date: '2025-07-17', reason: 'Skin rash diagnosis',         status: 'completed', phone: '0715678901' },
  { id: 8, ref: 'MNR-H6K8X3', name: 'Lubega Denis',   nin: 'CM91008901234H', dept: 'Orthopaedics', doctor: 'Dr. Ronald Byaruhanga',   date: '2025-07-17', reason: 'Back pain evaluation',        status: 'confirmed', phone: '0702345678' },
];

let appointments = JSON.parse(localStorage.getItem('mulago_appts') || 'null') || DEMO_APPOINTMENTS;

function saveAppointments() {
  localStorage.setItem('mulago_appts', JSON.stringify(appointments));
}

// ─── STATS ────────────────────────────────────────────────────
function setupStats() {
  const today = new Date().toISOString().slice(0, 10);
  const total     = appointments.length;
  const todayAppts= appointments.filter(a => a.date === today).length;
  const pending   = appointments.filter(a => a.status === 'pending').length;
  const confirmed = appointments.filter(a => a.status === 'confirmed').length;

  document.getElementById('stat-total')    && (document.getElementById('stat-total').textContent   = total);
  document.getElementById('stat-today')    && (document.getElementById('stat-today').textContent   = todayAppts);
  document.getElementById('stat-pending')  && (document.getElementById('stat-pending').textContent = pending);
  document.getElementById('stat-confirmed')&& (document.getElementById('stat-confirmed').textContent = confirmed);
}

// ─── LOAD APPOINTMENTS TABLE ──────────────────────────────────
let currentPage = 1;
const PER_PAGE  = 8;

function loadAppointments(filter = '', deptFilter = '', statusFilter = '') {
  const tbody = document.getElementById('appts-tbody');
  if (!tbody) return;

  const q = filter.toLowerCase();
  let filtered = appointments.filter(a => {
    const matchQ = !q || a.name.toLowerCase().includes(q) || a.ref.toLowerCase().includes(q) || a.nin.toLowerCase().includes(q);
    const matchD = !deptFilter || a.dept === deptFilter;
    const matchS = !statusFilter || a.status === statusFilter;
    return matchQ && matchD && matchS;
  });

  const total   = filtered.length;
  const pages   = Math.max(1, Math.ceil(total / PER_PAGE));
  if (currentPage > pages) currentPage = 1;

  const start   = (currentPage - 1) * PER_PAGE;
  const paged   = filtered.slice(start, start + PER_PAGE);

  // Update count label
  const countEl = document.getElementById('appts-count');
  if (countEl) countEl.textContent = `${total} record${total !== 1 ? 's' : ''}`;

  if (paged.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:40px;color:#9ca3af;font-style:italic;">No appointments found.</td></tr>`;
  } else {
    tbody.innerHTML = paged.map(a => `
      <tr data-id="${a.id}">
        <td><span class="ref-tag">${a.ref}</span></td>
        <td><strong>${a.name}</strong><br><span style="font-size:.75rem;color:#9ca3af;font-family:var(--font-mono)">${a.nin}</span></td>
        <td><span class="badge">${a.dept}</span></td>
        <td style="font-size:.82rem">${a.doctor}</td>
        <td style="font-family:var(--font-mono);font-size:.8rem">${a.date}</td>
        <td style="font-size:.82rem;max-width:180px">${a.reason}</td>
        <td><span class="status-pill status-${a.status}">${a.status}</span></td>
        <td>
          ${a.status === 'pending' ? `
            <button class="action-btn confirm" onclick="updateStatus(${a.id},'confirmed')">✓ Confirm</button>
            <button class="action-btn cancel" style="margin-top:4px" onclick="updateStatus(${a.id},'cancelled')">✕ Cancel</button>
          ` : `
            <button class="action-btn" onclick="viewAppt(${a.id})" style="border-color:#9ca3af;color:#4b5563">Details</button>
          `}
        </td>
      </tr>
    `).join('');
  }

  renderPagination(pages, total);
}

function renderPagination(pages, total) {
  const wrap = document.getElementById('pagination-wrap');
  if (!wrap) return;
  const info = document.getElementById('pagination-info');
  if (info) info.textContent = `Page ${currentPage} of ${pages} — ${total} total`;

  const btns = document.getElementById('pagination-btns');
  if (!btns) return;
  let html = '';
  for (let i = 1; i <= pages; i++) {
    html += `<button class="page-btn${i === currentPage ? ' active' : ''}" onclick="goPage(${i})">${i}</button>`;
  }
  btns.innerHTML = html;
}

window.goPage = (p) => { currentPage = p; refreshTable(); };

window.updateStatus = (id, status) => {
  const appt = appointments.find(a => a.id === id);
  if (!appt) return;
  appt.status = status;
  saveAppointments();
  setupStats();
  refreshTable();
  showAdminToast(`Appointment ${appt.ref} marked as ${status}.`);
};

window.viewAppt = (id) => {
  const a = appointments.find(a => a.id === id);
  if (!a) return;
  alert(`Appointment Details\n\nRef: ${a.ref}\nPatient: ${a.name}\nNIN: ${a.nin}\nPhone: ${a.phone}\nDepartment: ${a.dept}\nDoctor: ${a.doctor}\nDate: ${a.date}\nReason: ${a.reason}\nStatus: ${a.status}`);
};

function refreshTable() {
  const q = document.getElementById('appts-search')?.value || '';
  const d = document.getElementById('appts-dept')?.value   || '';
  const s = document.getElementById('appts-status')?.value || '';
  loadAppointments(q, d, s);
}

// ─── TABLE CONTROLS ───────────────────────────────────────────
document.getElementById('appts-search')?.addEventListener('input', () => { currentPage = 1; refreshTable(); });
document.getElementById('appts-dept')?.addEventListener('change', () => { currentPage = 1; refreshTable(); });
document.getElementById('appts-status')?.addEventListener('change', () => { currentPage = 1; refreshTable(); });

// Export to CSV
document.getElementById('export-csv')?.addEventListener('click', () => {
  const headers = ['Ref', 'Name', 'NIN', 'Department', 'Doctor', 'Date', 'Reason', 'Status'];
  const rows = appointments.map(a =>
    [a.ref, a.name, a.nin, a.dept, a.doctor, a.date, `"${a.reason}"`, a.status].join(',')
  );
  const csv = [headers.join(','), ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url; link.download = 'mulago_appointments.csv';
  link.click();
  showAdminToast('📥 Appointments exported to CSV.');
});

// ─── ALERT FORM ───────────────────────────────────────────────
// NOTE: Alert form is now handled entirely within alerts.html
// This prevents duplicate form submission handlers

// ─── TOAST ────────────────────────────────────────────────────
function showAdminToast(msg) {
  let c = document.querySelector('.toast-container');
  if (!c) {
    c = document.createElement('div');
    c.className = 'toast-container';
    c.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
    document.body.appendChild(c);
  }
  const t = document.createElement('div');
  t.className = 'toast';
  t.textContent = msg;
  t.style.cssText = 'background:#0d3d3d;color:white;padding:12px 18px;border-radius:4px;font-family:monospace;font-size:.8rem;box-shadow:0 4px 12px rgba(0,0,0,.2);animation:slideIn .3s ease forwards;';
  c.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}
