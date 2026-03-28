<?php
/**
 * Mulago Hospital — Database Abstraction Layer
 * Handles all database operations with normalized schema
 * Separates database logic from API/UI logic
 */

class MulagoDatabase {
  private $db;
  private $dbPath = __DIR__ . '/../data/mulago.db';

  public function __construct() {
    $this->connect();
    $this->initializeSchema();
  }

  private function connect() {
    try {
      $this->db = new PDO("sqlite:" . $this->dbPath);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
      throw new Exception("Database connection failed: " . $e->getMessage());
    }
  }

  private function initializeSchema() {
    $result = $this->db->query(
      "SELECT name FROM sqlite_master WHERE type='table' AND name='appointments'"
    );
    if ($result->fetch()) {
      return;
    }

    // Lookup tables
    $this->db->exec("CREATE TABLE departments (id INTEGER PRIMARY KEY, code TEXT UNIQUE NOT NULL, name TEXT UNIQUE NOT NULL)");
    $this->db->exec("CREATE TABLE doctors (id INTEGER PRIMARY KEY, name TEXT NOT NULL, department_id INTEGER NOT NULL, qualifications TEXT, phone TEXT, available_days TEXT, room TEXT, is_active BOOLEAN DEFAULT 1, FOREIGN KEY (department_id) REFERENCES departments(id))");
    $this->db->exec("CREATE TABLE visit_types (id INTEGER PRIMARY KEY, code TEXT UNIQUE NOT NULL, description TEXT NOT NULL)");
    $this->db->exec("CREATE TABLE appointment_statuses (id INTEGER PRIMARY KEY, code TEXT UNIQUE NOT NULL, description TEXT NOT NULL)");

    // Core tables
    $this->db->exec("CREATE TABLE patients (id INTEGER PRIMARY KEY, nin TEXT UNIQUE NOT NULL, first_name TEXT NOT NULL, last_name TEXT NOT NULL, gender TEXT, dob TEXT, phone TEXT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
    
    $this->db->exec("CREATE TABLE appointments (id INTEGER PRIMARY KEY, ref TEXT UNIQUE NOT NULL, patient_id INTEGER NOT NULL, department_id INTEGER NOT NULL, doctor_id INTEGER, reason TEXT NOT NULL, preferred_date TEXT, visit_type_id INTEGER NOT NULL, referred_from TEXT, status_id INTEGER NOT NULL DEFAULT 1, submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (patient_id) REFERENCES patients(id), FOREIGN KEY (department_id) REFERENCES departments(id), FOREIGN KEY (doctor_id) REFERENCES doctors(id), FOREIGN KEY (visit_type_id) REFERENCES visit_types(id), FOREIGN KEY (status_id) REFERENCES appointment_statuses(id))");

    $this->db->exec("CREATE TABLE appointment_status_log (id INTEGER PRIMARY KEY, appointment_id INTEGER NOT NULL, status_id INTEGER NOT NULL, changed_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (appointment_id) REFERENCES appointments(id), FOREIGN KEY (status_id) REFERENCES appointment_statuses(id))");

    $this->db->exec("CREATE TABLE health_alerts (id INTEGER PRIMARY KEY, title TEXT NOT NULL, content TEXT NOT NULL, severity TEXT DEFAULT 'info', published_at DATETIME DEFAULT CURRENT_TIMESTAMP, is_active BOOLEAN DEFAULT 1)");

    $this->db->exec("CREATE TABLE clinic_hours (id INTEGER PRIMARY KEY, department_id INTEGER NOT NULL, monday BOOLEAN DEFAULT 1, tuesday BOOLEAN DEFAULT 1, wednesday BOOLEAN DEFAULT 1, thursday BOOLEAN DEFAULT 1, friday BOOLEAN DEFAULT 1, saturday BOOLEAN DEFAULT 0, sunday BOOLEAN DEFAULT 0, open_time TEXT DEFAULT '08:00', close_time TEXT DEFAULT '16:00', walk_in_fee TEXT DEFAULT '10,000', notes TEXT, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (department_id) REFERENCES departments(id), UNIQUE(department_id))");

    $this->db->exec("CREATE TABLE clinic_notices (id INTEGER PRIMARY KEY, content TEXT, is_active BOOLEAN DEFAULT 0, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

    $this->db->exec("CREATE TABLE special_closures (id INTEGER PRIMARY KEY, closure_date TEXT NOT NULL, reason TEXT NOT NULL, affected_department_id INTEGER, is_active BOOLEAN DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (affected_department_id) REFERENCES departments(id))");
  }

  private function seedLookupTables() {
    $statuses = [
      ['id' => 1, 'code' => 'pending', 'description' => 'Awaiting Confirmation'],
      ['id' => 2, 'code' => 'confirmed', 'description' => 'Confirmed'],
      ['id' => 3, 'code' => 'cancelled', 'description' => 'Cancelled'],
      ['id' => 4, 'code' => 'completed', 'description' => 'Completed'],
    ];

    foreach ($statuses as $status) {
      $existsStmt = $this->db->prepare("SELECT id FROM appointment_statuses WHERE code = ?");
      $existsStmt->execute([$status['code']]);
      if (!$existsStmt->fetch()) {
        $stmt = $this->db->prepare("INSERT INTO appointment_statuses (id, code, description) VALUES (?, ?, ?)");
        $stmt->execute([$status['id'], $status['code'], $status['description']]);
      }
    }

    $visitTypes = [
      ['code' => 'new', 'description' => 'New Patient'],
      ['code' => 'review', 'description' => 'Review'],
      ['code' => 'emergency', 'description' => 'Emergency'],
    ];

    foreach ($visitTypes as $type) {
      $existsStmt = $this->db->prepare("SELECT id FROM visit_types WHERE code = ?");
      $existsStmt->execute([$type['code']]);
      if (!$existsStmt->fetch()) {
        $stmt = $this->db->prepare("INSERT INTO visit_types (code, description) VALUES (?, ?)");
        $stmt->execute([$type['code'], $type['description']]);
      }
    }

    $departments = [
      ['code' => 'cardiology', 'name' => 'Cardiology'],
      ['code' => 'surgery', 'name' => 'Surgery'],
      ['code' => 'paediatrics', 'name' => 'Paediatrics'],
      ['code' => 'oncology', 'name' => 'Oncology'],
      ['code' => 'neurology', 'name' => 'Neurology'],
      ['code' => 'orthopaedics', 'name' => 'Orthopaedics'],
      ['code' => 'dermatology', 'name' => 'Dermatology'],
      ['code' => 'obgyn', 'name' => 'Obstetrics & Gynaecology'],
      ['code' => 'ophthalmology', 'name' => 'Ophthalmology'],
      ['code' => 'psychiatry', 'name' => 'Psychiatry'],
      ['code' => 'internal_medicine', 'name' => 'Internal Medicine'],
      ['code' => 'radiology', 'name' => 'Radiology'],
    ];

    foreach ($departments as $dept) {
      $existsStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
      $existsStmt->execute([$dept['code']]);
      if (!$existsStmt->fetch()) {
        $stmt = $this->db->prepare("INSERT INTO departments (code, name) VALUES (?, ?)");
        $stmt->execute([$dept['code'], $dept['name']]);
      }
    }
  }

  private function seedDoctorsTable() {
    $doctors = [
      // Cardiology
      ['name' => 'Prof. Charles Odeke', 'department' => 'cardiology', 'qualifications' => 'MBChB, MMed (Int Med), PhD', 'phone' => '+256 700 100001', 'available_days' => 'Mon,Wed,Fri', 'room' => 'Block A - 201', 'is_active' => 1],
      ['name' => 'Dr. Ritah Namugga', 'department' => 'cardiology', 'qualifications' => 'MBChB, MMed (Cardio)', 'phone' => '+256 700 100002', 'available_days' => 'Tue,Thu', 'room' => 'Block A - 202', 'is_active' => 1],
      // Surgery
      ['name' => 'Dr. John Wasswa', 'department' => 'surgery', 'qualifications' => 'MBChB, MMed (Surg), FCS', 'phone' => '+256 700 100007', 'available_days' => 'Tue,Thu,Fri', 'room' => 'Block D - 401', 'is_active' => 1],
      ['name' => 'Dr. Patience Nakato', 'department' => 'surgery', 'qualifications' => 'MBChB, MMed (Surg)', 'phone' => '+256 700 100008', 'available_days' => 'Mon,Wed', 'room' => 'Block D - 402', 'is_active' => 1],
      // Paediatrics
      ['name' => 'Dr. Samuel Okello', 'department' => 'paediatrics', 'qualifications' => 'MBChB, MMed (Paeds)', 'phone' => '+256 700 100005', 'available_days' => 'Mon,Tue,Wed,Thu,Fri', 'room' => 'Block C - 301', 'is_active' => 1],
      ['name' => 'Dr. Grace Apio', 'department' => 'paediatrics', 'qualifications' => 'MBChB, MMed, MMED', 'phone' => '+256 700 100006', 'available_days' => 'Mon,Wed,Fri', 'room' => 'Block C - 302', 'is_active' => 1],
      // Oncology
      ['name' => 'Dr. Peter Ogwang', 'department' => 'oncology', 'qualifications' => 'MBChB, MMed (Onco), FCPS', 'phone' => '+256 700 100003', 'available_days' => 'Mon,Tue,Thu', 'room' => 'Block B - 105', 'is_active' => 1],
      ['name' => 'Dr. Florence Nabwire', 'department' => 'oncology', 'qualifications' => 'MBChB, MMed, FCPS', 'phone' => '+256 700 100004', 'available_days' => 'Wed,Fri', 'room' => 'Block B - 106', 'is_active' => 0],
      // Neurology
      ['name' => 'Dr. Emmanuel Ssenyonga', 'department' => 'neurology', 'qualifications' => 'MBChB, MMed (Neuro)', 'phone' => '+256 700 100009', 'available_days' => 'Mon,Tue,Fri', 'room' => 'Block E - 501', 'is_active' => 0],
      // Obstetrics & Gynaecology
      ['name' => 'Dr. Josephine Atim', 'department' => 'obgyn', 'qualifications' => 'MBChB, MMed (O&G)', 'phone' => '+256 700 100010', 'available_days' => 'Mon,Tue,Wed,Thu,Fri', 'room' => 'Block F - 601', 'is_active' => 1],
      // Orthopaedics
      ['name' => 'Dr. Ronald Byaruhanga', 'department' => 'orthopaedics', 'qualifications' => 'MBChB, MMed (Ortho), FCS', 'phone' => '+256 700 100011', 'available_days' => 'Tue,Thu', 'room' => 'Block D - 405', 'is_active' => 1],
      // Ophthalmology
      ['name' => 'Dr. Agnes Kiconco', 'department' => 'ophthalmology', 'qualifications' => 'MBChB, MMed (Ophth)', 'phone' => '+256 700 100012', 'available_days' => 'Mon,Wed,Thu', 'room' => 'Block G - 701', 'is_active' => 1],
      // Psychiatry
      ['name' => 'Dr. David Ochieng', 'department' => 'psychiatry', 'qualifications' => 'MBChB, MMed (Psych), PhD', 'phone' => '+256 700 100013', 'available_days' => 'Tue,Wed,Fri', 'room' => 'Block H - 801', 'is_active' => 1],
      // Dermatology
      ['name' => 'Dr. Sarah Tumusiime', 'department' => 'dermatology', 'qualifications' => 'MBChB, MMed (Derm)', 'phone' => '+256 700 100014', 'available_days' => 'Mon,Thu', 'room' => 'Block A - 210', 'is_active' => 1],
      // Internal Medicine
      ['name' => 'Dr. Isaac Mwesige', 'department' => 'internal_medicine', 'qualifications' => 'MBChB, MMed (Int Med)', 'phone' => '+256 700 100015', 'available_days' => 'Mon,Tue,Wed,Thu,Fri', 'room' => 'Block A - 101', 'is_active' => 1],
      // Radiology
      ['name' => 'Dr. Rebecca Abalo', 'department' => 'radiology', 'qualifications' => 'MBChB, MMed (Radio)', 'phone' => '+256 700 100016', 'available_days' => 'Mon,Tue,Thu', 'room' => 'Imaging Centre', 'is_active' => 1],
    ];

    foreach ($doctors as $doctor) {
      // Get department ID
      $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
      $deptStmt->execute([$doctor['department']]);
      $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
      
      if ($deptRow) {
        $existsStmt = $this->db->prepare("SELECT id FROM doctors WHERE name = ? AND department_id = ?");
        $existsStmt->execute([$doctor['name'], $deptRow['id']]);
        
        if (!$existsStmt->fetch()) {
          $stmt = $this->db->prepare("INSERT INTO doctors (name, department_id, qualifications, phone, available_days, room, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
          $stmt->execute([
            $doctor['name'],
            $deptRow['id'],
            $doctor['qualifications'],
            $doctor['phone'],
            $doctor['available_days'],
            $doctor['room'],
            $doctor['is_active']
          ]);
        }
      }
    }
  }

  private function seedClinicHours() {
    $departmentCodes = [
      'cardiology', 'surgery', 'paediatrics', 'oncology', 'neurology', 'orthopaedics',
      'dermatology', 'obgyn', 'ophthalmology', 'psychiatry', 'internal_medicine', 'radiology'
    ];

    $defaultSchedules = [
      'cardiology' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '08:00', 'close' => '16:00', 'fee' => '15,000', 'notes' => ''],
      'surgery' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '07:00', 'close' => '15:00', 'fee' => '20,000', 'notes' => ''],
      'paediatrics' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 0, 'open' => '07:30', 'close' => '16:30', 'fee' => '10,000', 'notes' => 'Saturday mornings'],
      'oncology' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '08:00', 'close' => '15:00', 'fee' => '15,000', 'notes' => 'Referral required'],
      'neurology' => ['mon' => 1, 'tue' => 0, 'wed' => 1, 'thu' => 0, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '09:00', 'close' => '15:00', 'fee' => '15,000', 'notes' => 'Mon, Wed, Fri only'],
      'orthopaedics' => ['mon' => 0, 'tue' => 1, 'wed' => 0, 'thu' => 1, 'fri' => 0, 'sat' => 0, 'sun' => 0, 'open' => '08:00', 'close' => '14:00', 'fee' => '15,000', 'notes' => 'Tue & Thu only'],
      'dermatology' => ['mon' => 1, 'tue' => 0, 'wed' => 1, 'thu' => 0, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '09:00', 'close' => '15:00', 'fee' => '12,000', 'notes' => ''],
      'obgyn' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 0, 'open' => '07:30', 'close' => '16:00', 'fee' => '12,000', 'notes' => 'Antenatal Sat'],
      'ophthalmology' => ['mon' => 1, 'tue' => 0, 'wed' => 1, 'thu' => 0, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '09:00', 'close' => '15:00', 'fee' => '15,000', 'notes' => ''],
      'psychiatry' => ['mon' => 0, 'tue' => 1, 'wed' => 0, 'thu' => 1, 'fri' => 0, 'sat' => 0, 'sun' => 0, 'open' => '09:00', 'close' => '14:00', 'fee' => '10,000', 'notes' => 'Appointment preferred'],
      'internal_medicine' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 0, 'sun' => 0, 'open' => '08:00', 'close' => '16:00', 'fee' => '15,000', 'notes' => ''],
      'radiology' => ['mon' => 1, 'tue' => 1, 'wed' => 1, 'thu' => 1, 'fri' => 1, 'sat' => 1, 'sun' => 0, 'open' => '08:00', 'close' => '17:00', 'fee' => 'Varies', 'notes' => 'Imaging Sat mornings'],
    ];

    foreach ($departmentCodes as $code) {
      $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
      $deptStmt->execute([$code]);
      $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
      
      if ($deptRow) {
        $existsStmt = $this->db->prepare("SELECT id FROM clinic_hours WHERE department_id = ?");
        $existsStmt->execute([$deptRow['id']]);
        
        if (!$existsStmt->fetch()) {
          $schedule = $defaultSchedules[$code];
          $stmt = $this->db->prepare(
            "INSERT INTO clinic_hours (department_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, open_time, close_time, walk_in_fee, notes) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
          );
          $stmt->execute([
            $deptRow['id'],
            $schedule['mon'],
            $schedule['tue'],
            $schedule['wed'],
            $schedule['thu'],
            $schedule['fri'],
            $schedule['sat'],
            $schedule['sun'],
            $schedule['open'],
            $schedule['close'],
            $schedule['fee'],
            $schedule['notes']
          ]);
        }
      }
    }
  }

  public function getOrCreatePatient($nin, $firstName, $lastName, $phone, $gender, $dob) {
    $stmt = $this->db->prepare("SELECT id FROM patients WHERE nin = ?");
    $stmt->execute([$nin]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
      return $existing['id'];
    }

    $stmt = $this->db->prepare("INSERT INTO patients (nin, first_name, last_name, phone, gender, dob) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nin, $firstName, $lastName, $phone, $gender, $dob]);
    return $this->db->lastInsertId();
  }

  public function getPatientById($patientId) {
    $stmt = $this->db->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patientId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function createAppointment($patientId, $departmentCode, $reason, $preferredDate, $visitTypeCode, $referredFrom = null, $doctorId = null) {
    // Get department ID
    $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
    $deptStmt->execute([$departmentCode]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $departmentId = $deptRow['id'] ?? null;

    // Get visit type ID
    $vtStmt = $this->db->prepare("SELECT id FROM visit_types WHERE code = ?");
    $vtStmt->execute([$visitTypeCode]);
    $vtRow = $vtStmt->fetch(PDO::FETCH_ASSOC);
    $visitTypeId = $vtRow['id'] ?? 1;

    $ref = $this->generateRef();
    $stmt = $this->db->prepare("INSERT INTO appointments (ref, patient_id, department_id, doctor_id, reason, preferred_date, visit_type_id, referred_from, status_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$ref, $patientId, $departmentId, $doctorId, $reason, $preferredDate, $visitTypeId, $referredFrom]);

    $appointmentId = $this->db->lastInsertId();
    $this->logStatusChange($appointmentId, 1);

    return ['id' => $appointmentId, 'ref' => $ref];
  }

  public function getAppointmentById($appointmentId) {
    $stmt = $this->db->prepare("
      SELECT 
        a.id, a.ref, a.reason, a.preferred_date, a.referred_from, a.submitted_at,
        p.id as patient_id, p.nin, p.first_name, p.last_name, p.gender, p.dob, p.phone,
        d.id as dept_id, d.name as department,
        doc.id as doctor_id, doc.name as preferred_doctor,
        vt.description as visit_type,
        s.code as status
      FROM appointments a
      JOIN patients p ON a.patient_id = p.id
      JOIN departments d ON a.department_id = d.id
      LEFT JOIN doctors doc ON a.doctor_id = doc.id
      JOIN visit_types vt ON a.visit_type_id = vt.id
      JOIN appointment_statuses s ON a.status_id = s.id
      WHERE a.id = ?
    ");
    $stmt->execute([$appointmentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function getAppointments($page = 1, $perPage = 8, $search = '', $departmentCode = null, $statusCode = null, $preferredDate = null) {
    $offset = ($page - 1) * $perPage;
    $conditions = [];
    $params = [];

    if ($search) {
      $conditions[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR p.nin LIKE ? OR a.ref LIKE ?)";
      $searchTerm = "%$search%";
      $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    if ($departmentCode) {
      $conditions[] = "d.code = ?";
      $params[] = $departmentCode;
    }

    if ($statusCode) {
      $conditions[] = "s.code = ?";
      $params[] = $statusCode;
    }

    if ($preferredDate) {
      $conditions[] = "DATE(a.preferred_date) = ?";
      $params[] = $preferredDate;
    }

    $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $countStmt = $this->db->prepare("
      SELECT COUNT(*) as total FROM appointments a
      JOIN patients p ON a.patient_id = p.id
      JOIN departments d ON a.department_id = d.id
      JOIN visit_types vt ON a.visit_type_id = vt.id
      JOIN appointment_statuses s ON a.status_id = s.id
      $where
    ");
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $dataStmt = $this->db->prepare("
      SELECT 
        a.id, a.ref, a.reason, a.preferred_date, a.referred_from, a.submitted_at,
        p.nin, p.first_name, p.last_name, p.phone, p.gender, p.dob, d.name as department,
        doc.name as preferred_doctor, vt.description as visit_type,
        s.code as status
      FROM appointments a
      JOIN patients p ON a.patient_id = p.id
      JOIN departments d ON a.department_id = d.id
      LEFT JOIN doctors doc ON a.doctor_id = doc.id
      JOIN visit_types vt ON a.visit_type_id = vt.id
      JOIN appointment_statuses s ON a.status_id = s.id
      $where
      ORDER BY a.submitted_at DESC
      LIMIT ? OFFSET ?
    ");
    
    $dataParams = array_merge($params, [$perPage, $offset]);
    $dataStmt->execute($dataParams);
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    return ['data' => $data, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
  }

  public function getAppointmentStats() {
    $stmt = $this->db->prepare("
      SELECT s.code as status, COUNT(*) as count
      FROM appointments a
      JOIN appointment_statuses s ON a.status_id = s.id
      GROUP BY s.code
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
      'pending' => 0,
      'confirmed' => 0,
      'cancelled' => 0,
      'completed' => 0
    ];
    
    foreach ($results as $row) {
      if (isset($stats[$row['status']])) {
        $stats[$row['status']] = (int)$row['count'];
      }
    }
    
    $stats['total'] = array_sum($stats);
    
    // Get today's appointments (appointments SUBMITTED today, not scheduled for today)
    $today = date('Y-m-d');
    $todayStmt = $this->db->prepare("
      SELECT COUNT(*) as count FROM appointments
      WHERE DATE(submitted_at) = ?
    ");
    $todayStmt->execute([$today]);
    $todayRow = $todayStmt->fetch(PDO::FETCH_ASSOC);
    $stats['today'] = (int)($todayRow['count'] ?? 0);
    
    return $stats;
  }

  public function updateAppointmentStatus($appointmentId, $statusCode) {
    $statusStmt = $this->db->prepare("SELECT id FROM appointment_statuses WHERE code = ?");
    $statusStmt->execute([$statusCode]);
    $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

    if (!$statusRow) {
      throw new Exception("Invalid status code: $statusCode");
    }

    $statusId = $statusRow['id'];
    $stmt = $this->db->prepare("UPDATE appointments SET status_id = ? WHERE id = ?");
    $stmt->execute([$statusId, $appointmentId]);
    $this->logStatusChange($appointmentId, $statusId);

    return true;
  }

  private function logStatusChange($appointmentId, $statusId) {
    $stmt = $this->db->prepare("INSERT INTO appointment_status_log (appointment_id, status_id) VALUES (?, ?)");
    $stmt->execute([$appointmentId, $statusId]);
  }

  public function getDepartments() {
    $stmt = $this->db->query("SELECT id, code, name FROM departments ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createAlert($title, $content, $severity = 'info') {
    $stmt = $this->db->prepare("INSERT INTO health_alerts (title, content, severity) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $severity]);
    return $this->db->lastInsertId();
  }

  public function getActiveAlerts() {
    $stmt = $this->db->query("SELECT id, title, content, severity, published_at FROM health_alerts WHERE is_active = 1 ORDER BY published_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getAllAlerts() {
    $stmt = $this->db->query("SELECT id, title, content, severity, published_at, is_active FROM health_alerts ORDER BY published_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getAlert($alertId) {
    $stmt = $this->db->prepare("SELECT id, title, content, severity, published_at, is_active FROM health_alerts WHERE id = ?");
    $stmt->execute([$alertId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function updateAlert($alertId, $title, $content, $severity = 'info') {
    $stmt = $this->db->prepare("UPDATE health_alerts SET title = ?, content = ?, severity = ? WHERE id = ?");
    $stmt->execute([$title, $content, $severity, $alertId]);
    return true;
  }

  public function toggleAlertStatus($alertId) {
    $stmt = $this->db->prepare("UPDATE health_alerts SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$alertId]);
    return true;
  }

  public function deleteAlert($alertId) {
    $stmt = $this->db->prepare("DELETE FROM health_alerts WHERE id = ?");
    $stmt->execute([$alertId]);
    return true;
  }

  // ─── SPECIALISTS / DOCTORS MANAGEMENT ───────────────────────────────────────
  public function getDoctors() {
    $stmt = $this->db->query("
      SELECT d.id, d.name, d.phone, d.qualifications, d.available_days, d.room, d.is_active,
             dept.name as department, dept.code as dept_code
      FROM doctors d
      JOIN departments dept ON d.department_id = dept.id
      ORDER BY dept.name, d.name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function createDoctor($name, $departmentCode, $qualifications = '', $phone = '', $availableDays = '', $room = '', $isActive = 1) {
    $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
    $deptStmt->execute([$departmentCode]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $departmentId = $deptRow['id'] ?? null;

    if (!$departmentId) {
      throw new Exception("Invalid department code: $departmentCode");
    }

    $stmt = $this->db->prepare("INSERT INTO doctors (name, department_id, qualifications, phone, available_days, room, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $departmentId, $qualifications, $phone, $availableDays, $room, $isActive]);
    return $this->db->lastInsertId();
  }

  public function updateDoctor($doctorId, $name, $departmentCode, $qualifications = '', $phone = '', $availableDays = '', $room = '', $isActive = 1) {
    $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
    $deptStmt->execute([$departmentCode]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $departmentId = $deptRow['id'] ?? null;

    if (!$departmentId) {
      throw new Exception("Invalid department code: $departmentCode");
    }

    $stmt = $this->db->prepare("UPDATE doctors SET name = ?, department_id = ?, qualifications = ?, phone = ?, available_days = ?, room = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$name, $departmentId, $qualifications, $phone, $availableDays, $room, $isActive, $doctorId]);
    return true;
  }

  public function getDoctor($doctorId) {
    $stmt = $this->db->prepare("
      SELECT d.id, d.name, d.phone, d.qualifications, d.available_days, d.room, d.is_active,
             dept.name as department, dept.code as dept_code
      FROM doctors d
      JOIN departments dept ON d.department_id = dept.id
      WHERE d.id = ?
    ");
    $stmt->execute([$doctorId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function deleteDoctor($doctorId) {
    $stmt = $this->db->prepare("DELETE FROM doctors WHERE id = ?");
    $stmt->execute([$doctorId]);
    return true;
  }

  public function toggleDoctorStatus($doctorId) {
    $stmt = $this->db->prepare("UPDATE doctors SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$doctorId]);
    return true;
  }

  private function generateRef() {
    $prefix = 'MNR-';
    $timestamp = substr(uniqid(), -8);
    $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
    return $prefix . substr($random . $timestamp, -8);
  }

  // ─── CLINIC HOURS MANAGEMENT ──────────────────────────────────────────────
  public function getClinicHours() {
    $stmt = $this->db->query("
      SELECT ch.id, ch.department_id, d.code, d.name as department,
             ch.monday, ch.tuesday, ch.wednesday, ch.thursday, ch.friday, ch.saturday, ch.sunday,
             ch.open_time, ch.close_time, ch.walk_in_fee, ch.notes
      FROM clinic_hours ch
      JOIN departments d ON ch.department_id = d.id
      ORDER BY d.name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getClinicHoursByDepartmentCode($deptCode) {
    $stmt = $this->db->prepare("
      SELECT ch.id, ch.department_id, d.code, d.name as department,
             ch.monday, ch.tuesday, ch.wednesday, ch.thursday, ch.friday, ch.saturday, ch.sunday,
             ch.open_time, ch.close_time, ch.walk_in_fee, ch.notes
      FROM clinic_hours ch
      JOIN departments d ON ch.department_id = d.id
      WHERE d.code = ?
    ");
    $stmt->execute([$deptCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function updateClinicHours($deptCode, $monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $openTime, $closeTime, $fee, $notes) {
    $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
    $deptStmt->execute([$deptCode]);
    $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$deptRow) {
      throw new Exception("Department not found");
    }

    $stmt = $this->db->prepare(
      "UPDATE clinic_hours SET monday = ?, tuesday = ?, wednesday = ?, thursday = ?, friday = ?, saturday = ?, sunday = ?,
                              open_time = ?, close_time = ?, walk_in_fee = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
       WHERE department_id = ?"
    );
    $stmt->execute([$monday, $tuesday, $wednesday, $thursday, $friday, $saturday, $sunday, $openTime, $closeTime, $fee, $notes, $deptRow['id']]);
    return true;
  }

  public function isDepartmentOpenOnDate($deptCode, $date = null, $time = null) {
    if (!$date) $date = date('Y-m-d');
    if (!$time) $time = date('H:i');

    $dateObj = new DateTime($date, new DateTimeZone('Africa/Nairobi'));
    $dayOfWeek = (int)$dateObj->format('N'); // 1=Monday, 7=Sunday

    // Check special closures first
    $closureStmt = $this->db->prepare(
      "SELECT id FROM special_closures 
       WHERE closure_date = ? AND is_active = 1 
       AND (affected_department_id IS NULL OR affected_department_id = (SELECT id FROM departments WHERE code = ?))"
    );
    $closureStmt->execute([$date, $deptCode]);
    if ($closureStmt->fetch()) {
      return false; // Department is closed on this date
    }

    // Check clinic hours - use CASE statement to check the correct day
    $stmt = $this->db->prepare(
      "SELECT open_time, close_time,
              CASE ?
                WHEN 1 THEN monday
                WHEN 2 THEN tuesday
                WHEN 3 THEN wednesday
                WHEN 4 THEN thursday
                WHEN 5 THEN friday
                WHEN 6 THEN saturday
                WHEN 7 THEN sunday
              END as is_open
       FROM clinic_hours ch
       JOIN departments d ON ch.department_id = d.id
       WHERE d.code = ?"
    );
    $stmt->execute([$dayOfWeek, $deptCode]);
    $hours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hours) {
      return true; // No restrictions found, assume open
    }

    if (!$hours['is_open']) {
      return false; // Department closed on this day
    }

    // Check if time is within operating hours
    if (!$hours['open_time'] || !$hours['close_time']) {
      return true;
    }

    try {
      $timeObj = DateTime::createFromFormat('H:i', $time);
      $openObj = DateTime::createFromFormat('H:i', $hours['open_time']);
      $closeObj = DateTime::createFromFormat('H:i', $hours['close_time']);

      if (!$timeObj || !$openObj || !$closeObj) {
        return true; // If time parsing fails, assume open
      }

      return $timeObj >= $openObj && $timeObj < $closeObj;
    } catch (Exception $e) {
      return true; // If any error, assume open
    }
  }

  // ─── EMERGENCY NOTICE MANAGEMENT ───────────────────────────────────────────
  public function getEmergencyNotice() {
    $stmt = $this->db->query("SELECT id, content, is_active FROM clinic_notices ORDER BY id DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
      $this->db->exec("INSERT INTO clinic_notices (content, is_active) VALUES ('', 0)");
      return ['id' => $this->db->lastInsertId(), 'content' => '', 'is_active' => 0];
    }
    return $result;
  }

  public function saveEmergencyNotice($content, $isActive) {
    $existingStmt = $this->db->query("SELECT id FROM clinic_notices LIMIT 1");
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
      $stmt = $this->db->prepare("UPDATE clinic_notices SET content = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
      $stmt->execute([$content, $isActive, $existing['id']]);
    } else {
      $stmt = $this->db->prepare("INSERT INTO clinic_notices (content, is_active) VALUES (?, ?)");
      $stmt->execute([$content, $isActive]);
    }
    return true;
  }

  // ─── SPECIAL CLOSURES MANAGEMENT ───────────────────────────────────────────
  public function getSpecialClosures() {
    $stmt = $this->db->query(
      "SELECT sc.id, sc.closure_date, sc.reason, sc.affected_department_id, 
              d.code as dept_code, d.name as dept_name, sc.is_active
       FROM special_closures sc
       LEFT JOIN departments d ON sc.affected_department_id = d.id
       WHERE sc.is_active = 1
       ORDER BY sc.closure_date"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function addSpecialClosure($date, $reason, $departmentCode = null) {
    $deptId = null;
    if ($departmentCode) {
      $deptStmt = $this->db->prepare("SELECT id FROM departments WHERE code = ?");
      $deptStmt->execute([$departmentCode]);
      $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
      $deptId = $deptRow ? $deptRow['id'] : null;
    }

    $stmt = $this->db->prepare("INSERT INTO special_closures (closure_date, reason, affected_department_id, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$date, $reason, $deptId]);
    return $this->db->lastInsertId();
  }

  public function removeSpecialClosure($closureId) {
    $stmt = $this->db->prepare("UPDATE special_closures SET is_active = 0 WHERE id = ?");
    $stmt->execute([$closureId]);
    return true;
  }

  // ─── PUBLIC SEEDING METHOD (Manual/Optional) ──────────────────────────────
  public function seedDatabase() {
    $this->seedLookupTables();
    $this->seedDoctorsTable();
    $this->seedClinicHours();
    return true;
  }

  public function close() {
    $this->db = null;
  }
}