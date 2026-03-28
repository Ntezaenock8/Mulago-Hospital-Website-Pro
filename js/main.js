/* ============================================================
   MULAGO HOSPITAL — MAIN JAVASCRIPT
   Handles: Navigation, Specialist Filter, Appointment Form,
            Health Alerts, Walk-in Status, Scroll effects
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  // ─── ALERT BANNER ─────────────────────────────────────────
  const alertClose = document.querySelector('.alert-close');
  if (alertClose) {
    alertClose.addEventListener('click', () => {
      document.getElementById('alert-banner')?.remove();
    });
  }

  // ─── HAMBURGER MENU ───────────────────────────────────────
  const hamburger = document.querySelector('.hamburger');
  const navLinks  = document.querySelector('.nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });
  }

  // ─── ACTIVE NAV LINK ──────────────────────────────────────
  const currentPage = window.location.pathname.split('/').pop() || 'index_public.html';
  document.querySelectorAll('.nav-links a').forEach(a => {
    if (a.getAttribute('href') === currentPage) a.classList.add('active');
  });

  // ─── SCROLL-TO-TOP ────────────────────────────────────────
  const scrollBtn = document.getElementById('scrollTop');
  if (scrollBtn) {
    window.addEventListener('scroll', () => {
      scrollBtn.classList.toggle('visible', window.scrollY > 400);
    });
    scrollBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // ─── WALK-IN STATUS (based on real local time) ────────────
  updateWalkInStatus();

  // ─── SPECIALIST DIRECTORY FILTER ──────────────────────────
  setupSpecialistFilter();

  // ─── APPOINTMENT FORM ─────────────────────────────────────
  setupAppointmentForm();

  // ─── FADE-IN ON SCROLL ────────────────────────────────────
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('fade-up');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  document.querySelectorAll('.directorate-card, .alert-card, .walkin-card, .appt-form-card')
    .forEach(el => observer.observe(el));

});

// ─── WALK-IN CLINIC STATUS ────────────────────────────────────
function updateWalkInStatus() {
  const now   = new Date();
  const day   = now.getDay(); // 0=Sun, 6=Sat
  const hour  = now.getHours();
  const isWeekday = day >= 1 && day <= 5;
  const isSat     = day === 6;

  const clinics = [
    { id: 'wk-opd',   openDays: [1,2,3,4,5,6], open: 7, close: 19 },
    { id: 'wk-emerg', openDays: [0,1,2,3,4,5,6], open: 0, close: 24 },
    { id: 'wk-lab',   openDays: [1,2,3,4,5], open: 7, close: 17 },
    { id: 'wk-pharm', openDays: [1,2,3,4,5,6], open: 8, close: 20 },
    { id: 'wk-radio', openDays: [1,2,3,4,5], open: 8, close: 17 },
    { id: 'wk-dental',openDays: [1,2,3,4,5], open: 8, close: 16 },
  ];
  clinics.forEach(c => {
    const el = document.getElementById(c.id);
    if (!el) return;
    const isOpenDay  = c.openDays.includes(day);
    const isOpenHour = hour >= c.open && hour < c.close;
    const isOpen     = isOpenDay && isOpenHour;
    el.textContent   = isOpen ? '🟢 Open Now' : '🔴 Closed';
    el.className     = 'walkin-status ' + (isOpen ? 'status-open' : 'status-closed');
  });
}

// ─── SPECIALIST DIRECTORY ────────────────────────────────────
const specialists = [
  { name: 'Prof. Charles Odeke',    dept: 'Cardiology',        qual: 'MBChB, MMed (Int Med), PhD', days: 'Mon, Wed, Fri', available: true,  room: 'Block A - 201' },
  { name: 'Dr. Ritah Namugga',      dept: 'Cardiology',        qual: 'MBChB, MMed (Cardio)',        days: 'Tue, Thu',      available: true,  room: 'Block A - 202' },
  { name: 'Dr. Peter Ogwang',       dept: 'Oncology',          qual: 'MBChB, MMed (Onco), FCPS',    days: 'Mon, Tue, Thu', available: true,  room: 'Block B - 105' },
  { name: 'Dr. Florence Nabwire',   dept: 'Oncology',          qual: 'MBChB, MMed, FCPS',           days: 'Wed, Fri',      available: false, room: 'Block B - 106' },
  { name: 'Dr. Samuel Okello',      dept: 'Paediatrics',       qual: 'MBChB, MMed (Paeds)',         days: 'Mon–Fri',       available: true,  room: 'Block C - 301' },
  { name: 'Dr. Grace Apio',         dept: 'Paediatrics',       qual: 'MBChB, MMed, MMED',           days: 'Mon, Wed, Fri', available: true,  room: 'Block C - 302' },
  { name: 'Dr. John Wasswa',        dept: 'Surgery',           qual: 'MBChB, MMed (Surg), FCS',     days: 'Tue, Thu, Fri', available: true,  room: 'Block D - 401' },
  { name: 'Dr. Patience Nakato',    dept: 'Surgery',           qual: 'MBChB, MMed (Surg)',          days: 'Mon, Wed',      available: true,  room: 'Block D - 402' },
  { name: 'Dr. Emmanuel Ssenyonga', dept: 'Neurology',         qual: 'MBChB, MMed (Neuro)',         days: 'Mon, Tue, Fri', available: false, room: 'Block E - 501' },
  { name: 'Dr. Josephine Atim',     dept: 'Obstetrics & Gynae',qual: 'MBChB, MMed (O&G)',           days: 'Mon–Fri',       available: true,  room: 'Block F - 601' },
  { name: 'Dr. Ronald Byaruhanga',  dept: 'Orthopaedics',      qual: 'MBChB, MMed (Ortho), FCS',   days: 'Tue, Thu',      available: true,  room: 'Block D - 405' },
  { name: 'Dr. Agnes Kiconco',      dept: 'Ophthalmology',     qual: 'MBChB, MMed (Ophth)',         days: 'Mon, Wed, Thu', available: true,  room: 'Block G - 701' },
  { name: 'Dr. David Ochieng',      dept: 'Psychiatry',        qual: 'MBChB, MMed (Psych), PhD',   days: 'Tue, Wed, Fri', available: true,  room: 'Block H - 801' },
  { name: 'Dr. Sarah Tumusiime',    dept: 'Dermatology',       qual: 'MBChB, MMed (Derm)',          days: 'Mon, Thu',      available: true,  room: 'Block A - 210' },
  { name: 'Dr. Isaac Mwesige',      dept: 'Internal Medicine', qual: 'MBChB, MMed (Int Med)',       days: 'Mon–Fri',       available: true,  room: 'Block A - 101' },
  { name: 'Dr. Rebecca Abalo',      dept: 'Radiology',         qual: 'MBChB, MMed (Radio)',         days: 'Mon, Tue, Thu', available: true,  room: 'Imaging Centre' },
];

function setupSpecialistFilter() {
  const table = document.getElementById('specialist-tbody');
  if (!table) return;

  const searchInput = document.getElementById('doc-search');
  const deptFilter  = document.getElementById('dept-filter');
  const availFilter = document.getElementById('avail-filter');
  const countEl     = document.getElementById('result-count');

  // Populate departments
  const depts = [...new Set(specialists.map(s => s.dept))].sort();
  depts.forEach(d => {
    const opt = document.createElement('option');
    opt.value = d; opt.textContent = d;
    deptFilter.appendChild(opt);
  });

  function renderTable() {
    const q     = (searchInput?.value || '').toLowerCase();
    const dept  = deptFilter?.value || '';
    const avail = availFilter?.value || '';

    const filtered = specialists.filter(s => {
      const matchQ = !q || s.name.toLowerCase().includes(q) || s.dept.toLowerCase().includes(q);
      const matchD = !dept || s.dept === dept;
      const matchA = !avail || (avail === 'available' ? s.available : !s.available);
      return matchQ && matchD && matchA;
    });

    if (countEl) countEl.textContent = `${filtered.length} specialist${filtered.length !== 1 ? 's' : ''} found`;

    if (filtered.length === 0) {
      table.innerHTML = `<tr><td colspan="7" class="no-results">No specialists match your criteria. Try adjusting the filters.</td></tr>`;
      return;
    }

    table.innerHTML = filtered.map(s => `
      <tr>
        <td>
          <strong style="font-size:.9rem">${s.name}</strong>
        </td>
        <td><span class="badge">${s.dept}</span></td>
        <td style="font-size:.85rem;color:#666">${s.qual}</td>
        <td style="font-size:.85rem">${s.days}</td>
        <td style="font-size:.85rem;color:#666">${s.room}</td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:99px;font-size:.75rem;font-weight:600;${s.available ? 'background:#d1fae5;color:#065f46' : 'background:#fef08a;color:#854d0e'}">
            <span style="width:6px;height:6px;border-radius:50%;background:currentColor"></span>
            ${s.available ? 'Available' : 'On Leave'}
          </span>
        </td>
        <td>
          <a href="appointment_public.html?dept=${encodeURIComponent(s.dept)}&doc=${encodeURIComponent(s.name)}"
             class="btn btn--outline" style="padding:6px 14px;font-size:.7rem;">Book</a>
        </td>
      </tr>
    `).join('');
  }

  searchInput?.addEventListener('input', renderTable);
  deptFilter?.addEventListener('change', renderTable);
  availFilter?.addEventListener('change', renderTable);
  renderTable();
}

// ─── APPOINTMENT FORM ─────────────────────────────────────────
function setupAppointmentForm() {
  const form = document.getElementById('appt-form');
  if (!form) return;

  // Load departments dynamically from backend
  const deptSelect = form.querySelector('[name="department"]');
  fetch('php/get_departments.php')
    .then(r => r.json())
    .then(departments => {
      // Clear hardcoded options except the placeholder
      while (deptSelect.options.length > 1) {
        deptSelect.remove(1);
      }
      // Populate with backend departments
      departments.forEach(dept => {
        const opt = document.createElement('option');
        opt.value = dept.code;
        opt.textContent = dept.name;
        deptSelect.appendChild(opt);
      });
      
      // Pre-fill from URL params (when booking from directorates page)
      const params = new URLSearchParams(window.location.search);
      if (params.get('dept')) {
        const requestedDept = params.get('dept');
        // Find matching department by name and set its code value
        const matchingDept = departments.find(d => d.name === requestedDept);
        if (matchingDept) {
          deptSelect.value = matchingDept.code;
        }
      }
    })
    .catch(err => console.error('Failed to load departments:', err));

  // Pre-fill doctor from URL params (when booking from specialist directory)
  const params = new URLSearchParams(window.location.search);
  if (params.get('doc')) {
    const docEl = form.querySelector('[name="preferred_doctor"]');
    if (docEl) docEl.value = params.get('doc');
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('.form-submit');
    const deptCode = form.querySelector('[name="department"]')?.value;
    const prefDate = form.querySelector('[name="preferred_date"]')?.value;

    // Check if department is open on the preferred date
    if (deptCode && prefDate) {
      try {
        const checkRes = await fetch(`php/check_department_status.php?dept=${deptCode}&date=${prefDate}`);
        const checkJson = await checkRes.json();
        
        if (checkJson.success && !checkJson.is_open) {
          const hours = checkJson.hours;
          let closedMsg = `This department is closed on ${prefDate}.`;
          
          if (hours) {
            const dayName = new Date(prefDate + 'T00:00:00').toLocaleDateString('en-GB', { weekday: 'long' });
            const dayColumn = dayName.toLowerCase();
            
            if (!hours[dayColumn]) {
              closedMsg = `${hours.department} is not open on ${dayName}s. `;
              closedMsg += `It's open: ${hours.monday ? 'Mon' : ''}${hours.tuesday ? ', Tue' : ''}${hours.wednesday ? ', Wed' : ''}${hours.thursday ? ', Thu' : ''}${hours.friday ? ', Fri' : ''}${hours.saturday ? ', Sat' : ''}${hours.sunday ? ', Sun' : ''}.`;
            } else if (hours.open_time && hours.close_time) {
              closedMsg = `${hours.department} on ${dayName}s is open from ${hours.open_time} to ${hours.close_time}.`;
            }
          }

          const proceed = confirm(closedMsg + '\n\nWould you still like to proceed with your appointment request?');
          if (!proceed) {
            btn.textContent = 'Submit Appointment Request';
            btn.disabled = false;
            return;
          }
        }
      } catch (err) {
        console.error('Error checking department status:', err);
        // Continue anyway if check fails
      }
    }

    btn.textContent = 'Submitting…';
    btn.disabled = true;

    const data = new FormData(form);

    try {
      const res = await fetch('php/submit_appointment.php', {
        method: 'POST',
        body: data
      });
      const json = await res.json();

      if (json.success) {
        form.style.display = 'none';
        const success = document.getElementById('appt-success');
        success.style.display = 'block';
        document.getElementById('ref-number').textContent = json.ref;
      } else {
        let errorMsg = json.error || 'Submission failed. Please try again.';
        if (json.errors && json.errors.length > 0) {
          errorMsg = json.errors.join(' ');
        }
        showToast('⚠ ' + errorMsg, 'error');
        btn.textContent = 'Submit Appointment Request';
        btn.disabled = false;
      }
    } catch (err) {
      // Demo mode: simulate success without server
      form.style.display = 'none';
      const success = document.getElementById('appt-success');
      success.style.display = 'block';
      const ref = 'MNR-' + Date.now().toString(36).toUpperCase().slice(-6);
      document.getElementById('ref-number').textContent = ref;
    }
  });
}

// ─── TOAST UTILITY ────────────────────────────────────────────
function showToast(msg, type = 'info') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = 'toast' + (type === 'error' ? ' toast-error' : '');
  toast.textContent = msg;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}
