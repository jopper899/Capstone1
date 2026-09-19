<?php
// ============================================================
//  Arandia College eLMS — Admin Panel
//  File: admin.php  |  Target: SHS & HS
// ============================================================
require_once __DIR__ . '/../../shared/middleware/admin.php';
require_once __DIR__ . '/../../config/conn.php';

$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

$stats = ['Student' => 0, 'Teacher' => 0, 'Admin' => 0, 'Total' => 0];
$result = $conn->query("SELECT role, COUNT(*) AS cnt FROM users GROUP BY role");
while ($r = $result->fetch_assoc()) {
  $stats[$r['role']] = (int) $r['cnt'];
  $stats['Total'] += (int) $r['cnt'];
}

$recentAccounts = [];
$res = $conn->query(
  "SELECT first_name, last_name, username, role, status,
     DATE_FORMAT(created_at,'%b %d, %Y') AS created_at
   FROM users ORDER BY created_at DESC LIMIT 5"
);
while ($r = $res->fetch_assoc())
  $recentAccounts[] = $r;

$allAccounts = [];
$res = $conn->query(
  "SELECT id, school_id, username, role, first_name, last_name, middle_name,
     email, contact, section_dept, status,
     DATE_FORMAT(created_at,'%b %d, %Y') AS created_at
   FROM users ORDER BY id DESC"
);
while ($r = $res->fetch_assoc())
  $allAccounts[] = $r;

$toast = $_GET['toast'] ?? '';
$isError = $_GET['error'] ?? '';
$panel = $_GET['panel'] ?? 'dashboard';

// ── Announcements ─────────────────────────────────────────────────────────
$allAnnouncements = [];
$annRes = $conn->query(
  "SELECT a.id, a.title, a.body, a.course_id,
          DATE_FORMAT(a.posted_at,'%b %d, %Y %h:%i %p') AS posted_at,
          CONCAT(u.first_name,' ',u.last_name) AS author,
          c.course_code, c.course_name
   FROM announcements a
   JOIN users u ON u.id = a.author_id
   LEFT JOIN courses c ON c.id = a.course_id
   ORDER BY a.posted_at DESC
   LIMIT 50"
);
while ($r = $annRes->fetch_assoc())
  $allAnnouncements[] = $r;

// For Assign Subjects panel
$allTeachers = [];
$res = $conn->query("SELECT id, CONCAT(last_name,', ',first_name) AS name FROM users WHERE role='Teacher' AND status='Active' ORDER BY last_name");
while ($r = $res->fetch_assoc())
  $allTeachers[] = $r;

$allCourses = [];
$res = $conn->query("SELECT id, course_code, course_name FROM courses WHERE status='Active' ORDER BY course_code");
while ($r = $res->fetch_assoc())
  $allCourses[] = $r;

// For Enroll Students panel
$allStudents = [];
$res = $conn->query(
  "SELECT id, school_id, section_dept, CONCAT(last_name,', ',first_name) AS name
   FROM users WHERE role='Student' AND status='Active' ORDER BY last_name, first_name"
);
while ($r = $res->fetch_assoc())
  $allStudents[] = $r;

$shsSections = [
  'Grade 11 - ABM-A',
  'Grade 11 - ABM-B',
  'Grade 11 - GAS-A',
  'Grade 11 - GAS-B',
  'Grade 11 - HUMSS-A',
  'Grade 11 - HUMSS-B',
  'Grade 12 - ABM-A',
  'Grade 12 - ABM-B',
  'Grade 12 - GAS-A',
  'Grade 12 - GAS-B',
  'Grade 12 - HUMSS-A',
  'Grade 12 - HUMSS-B',
];
$hsSections = [
  'Grade 7',
  'Grade 8',
  'Grade 9',
  'Grade 10',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <base href="../../">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel — Arandia College eLMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --sidebar-bg: #ffffff;
      --sidebar-border: #e8eaf0;
      --sidebar-text: #555555;
      --sidebar-hover: #eff6ff;
      --sidebar-active: rgba(0,48,135,0.1);
      --sidebar-accent: #003087;
      --sidebar-section: #aaaaaa;
      --primary: #003087;
      --primary-dark: #001a4d;
      --primary-hover: #0044cc;
      --primary-light: rgba(0, 48, 135, 0.1);
      --accent: #FFD700;
      --danger: #c0392b;
      --danger-light: rgba(192, 57, 43, 0.1);
      --success: #00875a;
      --success-light: rgba(0, 135, 90, 0.12);
      --warning: #b38600;
      --warning-light: rgba(179, 134, 0, 0.12);
      --purple: #7b2d8b;
      --purple-light: rgba(123, 45, 139, 0.12);
      --bg: #f8fafc;
      --surface: #ffffff;
      --surface-hover: #f8fafc;
      --border: #e2e8f0;
      --border-muted: #f0f2f5;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --text-muted: #94a3b8;
      --radius: 8px;
      --radius-lg: 14px;
      --shadow-sm: 0 2px 8px rgba(0,0,0,.05);
      --shadow: 0 4px 16px rgba(0,0,0,.08);
      --shadow-lg: 0 8px 28px rgba(0,0,0,.12);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }
    html, body { height: 100% }

    body {
      font-family: 'Open Sans', sans-serif;
      background: var(--bg);
      color: var(--text-primary);
      display: flex;
      min-height: 100vh;
      font-size: 14px;
      line-height: 1.5;
    }

    /* ── SIDEBAR ─────────────────────────────────────────── */
    .sidebar {
      width: 240px;
      min-width: 240px;
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      position: sticky;
      top: 0;
      z-index: 100;
      border-right: 1px solid var(--sidebar-border);
    }

    .sidebar-header {
      padding: 1.25rem 1.25rem 1rem;
      border-bottom: 1px solid var(--sidebar-border);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
      margin-bottom: 1rem;
    }

    .sidebar-logo {
      width: 50px;
      height: 50px;
      object-fit: contain;
      border-radius: 8px;
    }

    .sidebar-brand-text strong {
      display: block;
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      font-weight: 900;
      color: var(--primary);
      line-height: 1.3;
    }

    .sidebar-brand-text span {
      font-size: 0.72rem;
      color: #888;
      font-weight: 400;
    }

    .sidebar-admin-card {
      background: rgba(0,48,135,0.07);
      border: 1px solid rgba(0,48,135,0.12);
      border-radius: var(--radius);
      padding: 0.65rem 0.85rem;
      display: flex;
      align-items: center;
      gap: 0.65rem;
    }

    .sidebar-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), #7b2d8b);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.78rem;
      font-weight: 800;
      color: white;
      flex-shrink: 0;
      font-family: 'Nunito', sans-serif;
    }

    .sidebar-admin-info strong {
      display: block;
      font-family: 'Nunito', sans-serif;
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--primary);
      line-height: 1.3;
    }

    .sidebar-admin-info span {
      font-size: 0.68rem;
      color: var(--text-muted);
    }

    .sidebar-nav {
      flex: 1;
      padding: 1rem 0;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 0.15rem;
    }

    .sidebar-section {
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--sidebar-section);
      padding: 0.75rem 1.5rem 0.25rem;
      margin-top: 0.5rem;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.65rem 1.5rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--sidebar-text);
      text-decoration: none;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      cursor: pointer;
      transition: background .2s, color .2s;
      position: relative;
      border-left: 3px solid transparent;
    }

    .sidebar-link:hover {
      background: var(--sidebar-hover);
      color: var(--primary);
      border-left-color: transparent;
    }

    .sidebar-link.active {
      background: var(--sidebar-active);
      color: var(--primary);
      border-left-color: var(--primary);
      font-weight: 700;
    }

    .sidebar-icon {
      font-size: 1rem;
      width: 22px;
      text-align: center;
      flex-shrink: 0;
    }

    .sidebar-footer {
      padding: 0.75rem 0;
      border-top: 1px solid var(--sidebar-border);
    }

    .sidebar-link.danger {
      color: var(--danger);
    }

    .sidebar-link.danger:hover {
      background: var(--danger-light);
      color: var(--danger);
      border-left-color: transparent;
    }

    /* ── MAIN LAYOUT ─────────────────────────────────────── */
    .app-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    /* ── TOPBAR ──────────────────────────────────────────── */
    .topbar {
      background: var(--primary-dark);
      color: rgba(255,255,255,0.8);
      border-bottom: none;
      padding: 0 1.75rem;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 99;
      gap: 1rem;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .topbar-breadcrumb {
      font-size: 0.82rem;
      color: rgba(255,255,255,0.7);
    }

    .topbar-breadcrumb strong {
      color: var(--accent);
      font-weight: 700;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .topbar-badge {
      background: rgba(255,215,0,0.2);
      color: var(--accent);
      font-family: 'Nunito', sans-serif;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 0.25rem 0.75rem;
      border-radius: 100px;
      letter-spacing: 0.02em;
    }

    .btn-logout {
      font-size: 0.78rem;
      font-weight: 700;
      color: #ff8a80;
      text-decoration: none;
      padding: 0.35rem 0.85rem;
      border: 1.5px solid rgba(255,138,128,.4);
      border-radius: var(--radius);
      transition: all .15s;
      background: none;
      cursor: pointer;
    }

    .btn-logout:hover {
      background: rgba(255,138,128,.15);
      border-color: #ff8a80;
      color: white;
    }

    /* ── MAIN CONTENT ────────────────────────────────────── */
    .main-content {
      flex: 1;
      padding: 1.75rem 2rem;
      overflow-y: auto;
    }

    .page-header {
      margin-bottom: 1.5rem;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .page-header-text {}

    .page-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.6rem;
      font-weight: 900;
      color: var(--text-primary);
      line-height: 1.2;
    }

    .page-sub {
      font-size: 0.85rem;
      color: #888;
      margin-top: 0.2rem;
    }

    /* ── STAT CARDS ──────────────────────────────────────── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.75rem;
    }

    .stat-card {
      background: var(--surface);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
      border-left: 4px solid var(--accent-color);
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: box-shadow .2s, transform .2s;
    }

    .stat-card:hover {
      box-shadow: var(--shadow);
      transform: translateY(-2px);
    }

    .stat-card-1 { --accent-color: #003087 }
    .stat-card-2 { --accent-color: #00875a }
    .stat-card-3 { --accent-color: #FFB800 }
    .stat-card-4 { --accent-color: #7b2d8b }

    .stat-icon {
      font-size: 1.8rem;
      flex-shrink: 0;
    }

    .stat-num {
      font-family: 'Nunito', sans-serif;
      font-size: 1.9rem;
      font-weight: 900;
      color: var(--accent-color);
      letter-spacing: -0.03em;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.75rem;
      color: #888;
      font-weight: 600;
      margin-top: 0.25rem;
    }

    /* ── PANELS ──────────────────────────────────────────── */
    .section-panel { display: none }
    .section-panel.active { display: block }

    /* ── TABLE CARD ──────────────────────────────────────── */
    .table-card {
      background: var(--surface);
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      overflow: hidden;
    }

    .table-header {
      padding: 1.25rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border-muted);
      flex-wrap: wrap;
      gap: 0.75rem;
      background: var(--surface);
    }

    .table-header h3 {
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      color: var(--text-primary);
    }

    .table-actions {
      display: flex;
      gap: 0.5rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input {
      padding: 0.42rem 0.85rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-size: 0.82rem;
      outline: none;
      font-family: 'Open Sans', sans-serif;
      color: var(--text-primary);
      background: var(--surface);
      transition: border-color .15s, box-shadow .15s;
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--primary-light);
    }

    .btn {
      font-family: 'Nunito', sans-serif;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 0.42rem 1rem;
      border-radius: var(--radius);
      border: 1px solid transparent;
      cursor: pointer;
      transition: all .15s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      white-space: nowrap;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .btn-primary:hover {
      background: var(--primary-hover);
      border-color: var(--primary-hover);
    }

    .btn-danger {
      background: var(--danger-light);
      color: var(--danger);
      border-color: rgba(192,57,43,.2);
    }

    .btn-danger:hover {
      background: var(--danger);
      color: white;
      border-color: var(--danger);
    }

    .btn-edit {
      background: var(--primary-light);
      color: var(--primary);
      border-color: rgba(0,48,135,.15);
    }

    .btn-edit:hover {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .btn-reset-form {
      background: var(--bg);
      color: var(--text-secondary);
      border-color: var(--border);
    }

    .btn-reset-form:hover {
      background: var(--border-muted);
    }

    .btn-sm {
      padding: 0.28rem 0.65rem;
      font-size: 0.75rem;
    }

    /* ── TABLE ───────────────────────────────────────────── */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: #f6f8fa;
      border-bottom: 1px solid var(--border-muted);
    }

    th {
      padding: 0.65rem 1rem;
      text-align: left;
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-muted);
    }

    td {
      padding: 0.8rem 1rem;
      font-size: 0.83rem;
      color: var(--text-primary);
      border-bottom: 1px solid var(--border-muted);
      vertical-align: middle;
    }

    tr:last-child td { border-bottom: none }
    tr:hover td { background: #fafbff }

    /* ── BADGES ──────────────────────────────────────────── */
    .badge-role, .badge-status {
      display: inline-block;
      font-size: 0.68rem;
      font-weight: 600;
      padding: 0.2rem 0.6rem;
      border-radius: 100px;
      letter-spacing: 0.02em;
    }

    .role-student  { background: var(--primary-light);  color: var(--primary) }
    .role-teacher  { background: var(--success-light);  color: var(--success) }
    .role-admin    { background: var(--purple-light);   color: var(--purple) }
    .status-active   { background: var(--success-light); color: var(--success) }
    .status-inactive { background: #f6f8fa; color: var(--text-muted); }

    /* ── FORM CARD ───────────────────────────────────────── */
    .form-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      padding: 1.75rem;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.1rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .form-group.full { grid-column: 1/-1 }

    .form-label {
      font-size: 0.77rem;
      font-weight: 600;
      color: var(--text-secondary);
      letter-spacing: 0.01em;
    }

    .form-input {
      padding: 0.58rem 0.85rem;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: 'Open Sans', sans-serif;
      font-size: 0.84rem;
      color: var(--text-primary);
      background: var(--surface);
      outline: none;
      transition: border-color .15s, box-shadow .15s;
    }

    .form-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--primary-light);
    }

    .form-input:disabled {
      background: var(--bg);
      color: var(--text-muted);
    }

    select.form-input { cursor: pointer }

    .form-divider {
      grid-column: 1/-1;
      border: none;
      border-top: 1px solid var(--border-muted);
      margin: 0.1rem 0;
    }

    .form-actions {
      grid-column: 1/-1;
      display: flex;
      gap: 0.65rem;
      justify-content: flex-end;
      margin-top: 0.25rem;
    }

    .form-error {
      grid-column: 1/-1;
      background: var(--danger-light);
      border: 1px solid rgba(218,54,51,.25);
      color: var(--danger);
      font-size: 0.78rem;
      font-weight: 600;
      padding: 0.6rem 0.9rem;
      border-radius: var(--radius);
      display: none;
    }

    /* ── TOAST ───────────────────────────────────────────── */
    .toast {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      background: var(--text-primary);
      color: white;
      font-family: 'Nunito', sans-serif;
      font-size: 0.84rem;
      font-weight: 700;
      padding: 0.75rem 1.25rem;
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      z-index: 999;
      transition: opacity .3s, transform .3s;
      opacity: 0;
      transform: translateY(8px);
      pointer-events: none;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
      pointer-events: auto;
    }

    /* ── MODAL ───────────────────────────────────────────── */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15,23,42,.6);
      backdrop-filter: blur(3px);
      z-index: 500;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .modal-overlay.show { display: flex }

    .modal {
      background: var(--surface);
      border-radius: 16px;
      padding: 1.75rem;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 8px 40px rgba(0,0,0,.18);
    }

    .modal-title {
      font-family: 'Nunito', sans-serif;
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text-primary);
      margin-bottom: 1.25rem;
    }

    .modal-actions {
      display: flex;
      gap: 0.65rem;
      justify-content: flex-end;
      margin-top: 1.5rem;
    }

    /* ── FOOTER ──────────────────────────────────────────── */
    .footer-bar {
      background: var(--primary-dark);
      border-top: none;
      color: rgba(255,255,255,0.5);
      font-size: 0.72rem;
      text-align: center;
      padding: 0.75rem;
    }

    /* ── ASSIGN / ENROLL ─────────────────────────────────── */
    .assign-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }

    .assign-card {
      background: var(--surface);
      border-radius: var(--radius-lg);
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
      padding: 1.5rem;
    }

    .assign-card h3 {
      font-family: 'Nunito', sans-serif;
      font-size: 0.95rem;
      font-weight: 800;
      color: var(--text-primary);
      margin-bottom: 1.1rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border-muted);
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      background: var(--primary-light);
      color: var(--primary);
      font-size: 0.72rem;
      font-weight: 600;
      padding: 0.25rem 0.5rem 0.25rem 0.65rem;
      border-radius: 100px;
      margin: 0.18rem;
    }

    .chip button {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--danger);
      font-size: 0.85rem;
      line-height: 1;
      padding: 0;
      opacity: 0.6;
    }

    .chip button:hover { opacity: 1 }

    .assign-list-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.65rem 0;
      border-bottom: 1px solid var(--border-muted);
      font-size: 0.83rem;
    }

    .assign-list-item:last-child { border-bottom: none }

    .assign-item-info {
      display: flex;
      flex-direction: column;
      gap: 0.1rem;
    }

    .assign-item-name {
      font-weight: 600;
      color: var(--text-primary);
      font-size: 0.83rem;
    }

    .assign-item-meta {
      font-size: 0.72rem;
      color: var(--text-muted);
    }

    .assign-filter {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }

    .empty-assign {
      text-align: center;
      padding: 2rem;
      color: var(--text-muted);
      font-size: 0.82rem;
    }

    .empty-assign span {
      font-size: 2rem;
      display: block;
      margin-bottom: 0.5rem;
      opacity: 0.5;
    }

    /* ── RESPONSIVE ──────────────────────────────────────── */
    @media(max-width:1100px) {
      .stats-row { grid-template-columns: repeat(2, 1fr) }
    }

    @media(max-width:900px) {
      .assign-grid { grid-template-columns: 1fr }
      .sidebar { width: 200px; min-width: 200px }
    }

    @media(max-width:700px) {
      body { flex-direction: column }
      .sidebar {
        width: 100%;
        min-width: 100%;
        min-height: auto;
        position: relative;
      }
      .sidebar-nav {
        display: flex;
        flex-direction: row;
        overflow-x: auto;
        padding: 0.25rem;
        gap: 0.15rem;
      }
      .sidebar-section { display: none }
      .sidebar-link {
        border-radius: var(--radius);
        white-space: nowrap;
        padding: 0.45rem 0.85rem;
        font-size: 0.78rem;
      }
      .sidebar-link.active::before { display: none }
      .sidebar-header { padding: 0.75rem 1rem }
      .sidebar-admin-card { display: none }
      .main-content { padding: 1rem }
      .stats-row { grid-template-columns: 1fr 1fr }
    }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="index.php" class="sidebar-brand">
        <img src="picture/logo.jpg" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
        <div class="sidebar-brand-text">
          <strong>Arandia College</strong>
          <span>eLMS Admin</span>
        </div>
      </a>
      <div class="sidebar-admin-card">
        <div class="sidebar-avatar"><?= strtoupper(substr($first_name,0,1).substr($last_name,0,1)) ?></div>
        <div class="sidebar-admin-info">
          <strong><?= htmlspecialchars($first_name . ' ' . $last_name) ?></strong>
          <span>Administrator</span>
        </div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="sidebar-section">Overview</div>
      <button class="sidebar-link <?= $panel === 'dashboard' ? 'active' : '' ?>" onclick="showPanel('dashboard',this)">
        <span class="sidebar-icon">▦</span> Dashboard</button>

      <div class="sidebar-section">User Management</div>
      <button class="sidebar-link <?= $panel === 'create' ? 'active' : '' ?>" onclick="showPanel('create',this)">
        <span class="sidebar-icon">＋</span> Create Account</button>
      <button class="sidebar-link <?= $panel === 'accounts' ? 'active' : '' ?>" onclick="showPanel('accounts',this)">
        <span class="sidebar-icon">⊞</span> All Accounts</button>

      <div class="sidebar-section">Academic</div>
      <button class="sidebar-link <?= $panel === 'assign' ? 'active' : '' ?>"
        onclick="showPanel('assign',this);loadAssignments()">
        <span class="sidebar-icon">◈</span> Assign Subjects</button>
      <button class="sidebar-link <?= $panel === 'enroll' ? 'active' : '' ?>" onclick="showPanel('enroll',this)">
        <span class="sidebar-icon">◎</span> Enroll Students</button>

      <div class="sidebar-section">System</div>
      <button class="sidebar-link <?= $panel === 'announcements' ? 'active' : '' ?>" onclick="showPanel('announcements',this)">
        <span class="sidebar-icon">📢</span> Announcements</button>
      <button class="sidebar-link <?= $panel === 'settings' ? 'active' : '' ?>" onclick="showPanel('settings',this)">
        <span class="sidebar-icon">◉</span> Settings</button>
    </nav>

    <div class="sidebar-footer">
      <a href="logout.php" class="sidebar-link danger">
        <span class="sidebar-icon">⎋</span> Sign Out</a>
    </div>
  </aside>

  <!-- MAIN APP -->
  <div class="app-body">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-breadcrumb">SHS &amp; HS &nbsp;/&nbsp; <strong id="topbar-panel-name">Dashboard</strong></span>
      </div>
      <div class="topbar-right">
        <span class="topbar-badge">🛡️ Admin Panel</span>
        <a href="logout.php" class="btn-logout">Sign Out</a>
      </div>
    </header>

    <main class="main-content">

      <!-- DASHBOARD -->
      <div class="section-panel <?= $panel === 'dashboard' ? 'active' : '' ?>" id="panel-dashboard">
        <div class="page-header">
          <div>
            <div class="page-title">Dashboard</div>
            <div class="page-sub">Welcome, <?= htmlspecialchars($first_name) ?>. Here's the SHS &amp; HS system overview.</div>
          </div>
        </div>
        <div style="background:linear-gradient(135deg,#003087,#0044cc);color:white;border-radius:16px;padding:1.75rem 2.5rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;gap:1rem">
          <div>
            <div style="font-family:'Nunito',sans-serif;font-size:1.3rem;font-weight:900;margin-bottom:.3rem">Hello, <?= htmlspecialchars($first_name) ?>! 👋</div>
            <div style="font-size:.85rem;opacity:.85;line-height:1.5">You have full administrative control of Arandia College eLMS.</div>
            <div style="margin-top:.75rem;display:flex;gap:.5rem;flex-wrap:wrap">
              <span style="background:rgba(255,255,255,.18);font-family:'Nunito',sans-serif;font-size:.72rem;font-weight:700;padding:.25rem .7rem;border-radius:100px">🛡️ Administrator</span>
              <span style="background:rgba(255,215,0,.25);color:#FFD700;font-family:'Nunito',sans-serif;font-size:.72rem;font-weight:700;padding:.25rem .7rem;border-radius:100px">SHS &amp; HS</span>
            </div>
          </div>
          <div style="font-size:4rem;opacity:.55">🏫</div>
        </div>
        <div class="stats-row">
          <div class="stat-card stat-card-1">
            <span class="stat-icon">🎓</span>
            <div>
              <div class="stat-num"><?= $stats['Student'] ?></div>
              <div class="stat-label">Students</div>
            </div>
          </div>
          <div class="stat-card stat-card-2">
            <span class="stat-icon">👩‍🏫</span>
            <div>
              <div class="stat-num"><?= $stats['Teacher'] ?></div>
              <div class="stat-label">Teachers</div>
            </div>
          </div>
          <div class="stat-card stat-card-3">
            <span class="stat-icon">🛡️</span>
            <div>
              <div class="stat-num"><?= $stats['Admin'] ?></div>
              <div class="stat-label">Admins</div>
            </div>
          </div>
          <div class="stat-card stat-card-4">
            <span class="stat-icon">👥</span>
            <div>
              <div class="stat-num"><?= $stats['Total'] ?></div>
              <div class="stat-label">Total Accounts</div>
            </div>
          </div>
        </div>
        <div class="table-card">
          <div class="table-header">
            <h3>Recent Accounts</h3>
            <button class="btn btn-primary" onclick="showPanel('create',null)">+ Create Account</button>
          </div>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Role</th>
                <th>Date Created</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentAccounts)): ?>
                <tr>
                  <td colspan="5" style="text-align:center;color:#bbb;padding:2rem;">No accounts yet.</td>
                </tr>
              <?php else:
                foreach ($recentAccounts as $a): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?></strong></td>
                    <td><?= htmlspecialchars($a['username']) ?></td>
                    <td><span class="badge-role role-<?= strtolower($a['role']) ?>"><?= $a['role'] ?></span></td>
                    <td><?= $a['created_at'] ?></td>
                    <td><span class="badge-status status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                  </tr>
                <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CREATE ACCOUNT -->
      <div class="section-panel <?= $panel === 'create' ? 'active' : '' ?>" id="panel-create">
        <div class="page-header">
          <div class="page-title">Create Account</div>
          <div class="page-sub">Add a new student, teacher, or admin account.</div>
        </div>
        <div class="form-card">
          <div class="form-grid">
            <div class="form-error" id="form-error"></div>
            <div class="form-group">
              <label class="form-label">First Name *</label>
              <input class="form-input" type="text" id="f-firstname" placeholder="e.g. Juan">
            </div>
            <div class="form-group">
              <label class="form-label">Last Name *</label>
              <input class="form-input" type="text" id="f-lastname" placeholder="e.g. Dela Cruz">
            </div>
            <div class="form-group">
              <label class="form-label">Middle Name</label>
              <input class="form-input" type="text" id="f-middlename" placeholder="e.g. Santos">
            </div>
            <div class="form-group">
              <label class="form-label">Role *</label>
              <select class="form-input" id="f-role">
                <option value="">— Select Role —</option>
                <option value="Student">Student</option>
                <option value="Teacher">Teacher</option>
                <option value="Admin">Admin</option>
              </select>
            </div>
            <hr class="form-divider">
            <div class="form-group">
              <label class="form-label">LRN / Employee ID *</label>
              <input class="form-input" type="text" id="f-schoolid" placeholder="e.g. 123456789012" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </div>
            <div class="form-group">
              <label class="form-label">Username *</label>
              <input class="form-input" type="text" id="f-username" placeholder="e.g. jdelacruz">
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input class="form-input" type="email" id="f-email" placeholder="e.g. juan@arandia.edu.ph">
            </div>
            <div class="form-group">
              <label class="form-label">Contact Number</label>
              <input class="form-input" type="text" id="f-contact" placeholder="e.g. 09XXXXXXXXX" inputmode="numeric" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
            </div>
            <hr class="form-divider">
            <div class="form-group">
              <label class="form-label">Password * <span style="color:#aaa;font-weight:400;">(min. 6
                  characters)</span></label>
              <input class="form-input" type="password" id="f-password" placeholder="Create a password">
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password *</label>
              <input class="form-input" type="password" id="f-confirm" placeholder="Re-enter password">
            </div>
            <div class="form-group">
              <label class="form-label">Section / Grade Level</label>
              <select class="form-input" id="f-section">
                <option value="">— Select Section —</option>
                <optgroup label="── Senior High School ──">
                  <?php foreach ($shsSections as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                </optgroup>
                <optgroup label="── High School ──">
                  <?php foreach ($hsSections as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Status</label>
              <select class="form-input" id="f-status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>
            <div class="form-actions">
              <button class="btn btn-reset-form" onclick="resetForm()">Reset</button>
              <button class="btn btn-primary" onclick="submitCreate()">✓ Create Account</button>
            </div>
          </div>
        </div>
      </div>

      <!-- ALL ACCOUNTS -->
      <div class="section-panel <?= $panel === 'accounts' ? 'active' : '' ?>" id="panel-accounts">
        <div class="page-header">
          <div class="page-title">All Accounts</div>
          <div class="page-sub">Manage all SHS &amp; HS user accounts.</div>
        </div>
        <div class="table-card">
          <div class="table-header">
            <h3>User Accounts</h3>
            <div class="table-actions">
              <input class="search-input" type="text" id="search-input" placeholder="🔍 Search..."
                oninput="filterTable()">
              <select class="search-input" id="filter-role" onchange="filterTable()">
                <option value="">All Roles</option>
                <option value="Student">Student</option>
                <option value="Teacher">Teacher</option>
                <option value="Admin">Admin</option>
              </select>
              <select class="search-input" id="filter-section" onchange="filterTable()">
                <option value="">All Sections</option>
                <optgroup label="── Senior High School ──">
                  <option value="Grade 11 - ABM-A">Grade 11 - ABM-A</option>
                  <option value="Grade 11 - ABM-B">Grade 11 - ABM-B</option>
                  <option value="Grade 11 - GAS-A">Grade 11 - GAS-A</option>
                  <option value="Grade 11 - GAS-B">Grade 11 - GAS-B</option>
                  <option value="Grade 11 - HUMSS-A">Grade 11 - HUMSS-A</option>
                  <option value="Grade 11 - HUMSS-B">Grade 11 - HUMSS-B</option>
                  <option value="Grade 12 - ABM-A">Grade 12 - ABM-A</option>
                  <option value="Grade 12 - ABM-B">Grade 12 - ABM-B</option>
                  <option value="Grade 12 - GAS-A">Grade 12 - GAS-A</option>
                  <option value="Grade 12 - GAS-B">Grade 12 - GAS-B</option>
                  <option value="Grade 12 - HUMSS-A">Grade 12 - HUMSS-A</option>
                  <option value="Grade 12 - HUMSS-B">Grade 12 - HUMSS-B</option>
                </optgroup>
                <optgroup label="── High School ──">
                  <option value="Grade 7">Grade 7</option>
                  <option value="Grade 8">Grade 8</option>
                  <option value="Grade 9">Grade 9</option>
                  <option value="Grade 10">Grade 10</option>
                </optgroup>
              </select>
              <button class="btn btn-primary" onclick="showPanel('create',null)">+ Create Account</button>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Username</th>
                <th>LRN / Emp. ID</th>
                <th>Role</th>
                <th>Section / Grade</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="accounts-tbody">
              <?php if (empty($allAccounts)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;color:#bbb;padding:2rem;">No accounts yet.</td>
                </tr>
              <?php else:
                foreach ($allAccounts as $a): ?>
                  <tr data-name="<?= strtolower($a['last_name'] . ' ' . $a['first_name']) ?>"
                    data-username="<?= strtolower($a['username']) ?>" data-schoolid="<?= strtolower($a['school_id']) ?>"
                    data-role="<?= $a['role'] ?>"
                    data-section="<?= htmlspecialchars($a['section_dept'] ?? '') ?>">
                    <td>
                      <strong><?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?></strong>
                      <?= $a['middle_name'] ? ' ' . htmlspecialchars($a['middle_name'][0]) . '.' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($a['username']) ?></td>
                    <td><?= htmlspecialchars($a['school_id']) ?></td>
                    <td><span class="badge-role role-<?= strtolower($a['role']) ?>"><?= $a['role'] ?></span></td>
                    <td><?= htmlspecialchars($a['section_dept'] ?? '—') ?></td>
                    <td><span class="badge-status status-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                    <td style="display:flex;gap:0.4rem;">
                      <button class="btn btn-edit btn-sm" data-user="<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>" onclick="openEdit(JSON.parse(this.dataset.user))">Edit</button>
                      <button class="btn btn-danger btn-sm" onclick="openDelete(<?= $a['id'] ?>)">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ASSIGN SUBJECTS -->
      <div class="section-panel <?= $panel === 'assign' ? 'active' : '' ?>" id="panel-assign">
        <div class="page-header">
          <div class="page-title">Assign Subjects</div>
          <div class="page-sub">Assign a subject and section/grade level to a teacher.</div>
        </div>

        <div class="assign-grid">
          <!-- LEFT: Assignment Form -->
          <div class="assign-card">
            <h3>➕ New Assignment</h3>
            <div class="form-group" style="margin-bottom:1rem">
              <label class="form-label">Teacher *</label>
              <select class="form-input" id="a-teacher" onchange="loadTeacherAssignments()">
                <option value="">— Select Teacher —</option>
                <?php foreach ($allTeachers as $t): ?>
                  <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0.5rem">
              <label class="form-label">Subject * <span style="color:#aaa;font-weight:400">(check all that apply)</span></label>
            </div>
            <!-- Subject checkboxes grouped -->
            <div id="a-subjects-wrap"
              style="max-height:300px;overflow-y:auto;border:1.5px solid #e0e4ee;border-radius:10px;padding:0.75rem;margin-bottom:0.5rem">
              <?php
              $assignGroups = [
                'SHS Core'    => 'SHS',
                'ABM Strand'  => 'ABM',
                'GAS Strand'  => 'GAS',
                'HUMSS Strand'=> 'HUMSS',
                'High School' => 'HS',
              ];
              foreach ($assignGroups as $label => $prefix): ?>
                <div style="margin-bottom:0.6rem">
                  <div style="font-size:0.7rem;font-weight:800;color:#aaa;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.35rem;padding:0.2rem 0.35rem;background:#f8f9fc;border-radius:5px">
                    <?= $label ?>
                    <button type="button" onclick="selectAssignGroup('<?= $prefix ?>')"
                      style="float:right;font-size:0.68rem;font-weight:700;color:var(--primary);background:none;border:none;cursor:pointer">Select All</button>
                  </div>
                  <?php foreach ($allCourses as $c):
                    if (!str_starts_with($c['course_code'], $prefix)) continue; ?>
                    <label
                      style="display:flex;align-items:center;gap:0.5rem;padding:0.3rem 0.5rem;font-size:0.82rem;cursor:pointer;border-radius:6px;transition:background .15s"
                      onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background=''">
                      <input type="checkbox" class="a-chk a-<?= $prefix ?>" value="<?= $c['id'] ?>"
                        data-code="<?= htmlspecialchars($c['course_code']) ?>"
                        style="accent-color:var(--primary);width:15px;height:15px"
                        onchange="updateAssignCount()">
                      <span><strong><?= htmlspecialchars($c['course_code']) ?></strong> — <?= htmlspecialchars($c['course_name']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
              <span id="a-count" style="font-size:0.78rem;color:#aaa">0 subjects selected</span>
              <button class="btn btn-reset-form btn-sm" onclick="clearAssignSelection()">Clear All</button>
            </div>
            <div class="form-group" style="margin-bottom:1rem">
              <label class="form-label">Section / Grade Level *</label>
              <select class="form-input" id="a-section">
                <option value="">— Select Section —</option>
                <optgroup label="── Senior High School ──">
                  <?php foreach ($shsSections as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                  <?php endforeach; ?>
                </optgroup>
                <optgroup label="── High School ──">
                  <?php foreach ($hsSections as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                  <?php endforeach; ?>
                </optgroup>
              </select>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;margin-bottom:1rem">
              <div class="form-group" style="flex:1;margin:0">
                <label class="form-label">School Year</label>
                <input class="form-input" type="text" id="a-sy" value="2025-2026">
              </div>
              <div class="form-group" style="flex:1;margin:0">
                <label class="form-label">Semester</label>
                <select class="form-input" id="a-sem">
                  <option value="1st">1st Semester</option>
                  <option value="2nd">2nd Semester</option>
                  <option value="Summer">Summer</option>
                </select>
              </div>
            </div>
            <div id="a-error"
              style="display:none;background:#fff0ec;border:1px solid #ffcfbf;color:#c0392b;font-size:0.78rem;font-weight:600;padding:0.6rem 0.9rem;border-radius:8px;margin-bottom:0.75rem">
            </div>
            <button class="btn btn-primary" style="width:100%" onclick="submitAssign()">✓ Save Assignment</button>
          </div>

          <!-- RIGHT: Current Assignments for selected teacher -->
          <div class="assign-card">
            <h3>📋 Current Assignments <span id="assign-teacher-name"
                style="color:#888;font-weight:600;font-size:0.85rem"></span></h3>
            <div id="assign-list-wrap">
              <div class="empty-assign"><span>👩‍🏫</span>Select a teacher to view their assignments.</div>
            </div>
          </div>
        </div>

        <!-- ALL ASSIGNMENTS TABLE -->
        <div class="table-card">
          <div class="table-header">
            <h3>All Teacher Assignments</h3>
            <div class="table-actions">
              <input class="search-input" type="text" id="assign-search" placeholder="🔍 Search..."
                oninput="filterAssignTable()">
              <select class="search-input" id="assign-filter-teacher" onchange="filterAssignTable()">
                <option value="">All Teachers</option>
                <?php foreach ($allTeachers as $t): ?>
                  <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Teacher</th>
                <th>Subject</th>
                <th>Section / Grade</th>
                <th>School Year</th>
                <th>Semester</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="assign-all-tbody">
              <tr>
                <td colspan="6" style="text-align:center;color:#bbb;padding:2rem;">Loading...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ENROLL STUDENTS -->
      <div class="section-panel <?= $panel === 'enroll' ? 'active' : '' ?>" id="panel-enroll">
        <div class="page-header">
          <div class="page-title">Enroll Students</div>
          <div class="page-sub">Assign multiple subjects to a student.</div>
        </div>

        <div class="assign-grid">
          <!-- LEFT: Enrollment Form -->
          <div class="assign-card">
            <h3>📝 Enroll Student</h3>
            <div class="form-group" style="margin-bottom:1rem">
              <label class="form-label">Student *</label>
              <select class="form-input" id="enr-student" onchange="loadStudentEnrollments()">
                <option value="">— Select Student —</option>
                <?php foreach ($allStudents as $s): ?>
                  <option value="<?= $s['id'] ?>" data-section="<?= htmlspecialchars($s['section_dept']) ?>">
                    <?= htmlspecialchars($s['name']) ?>
                    <?= $s['section_dept'] ? ' (' . $s['section_dept'] . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:0.5rem">
              <label class="form-label">Select Subjects * <span style="color:#aaa;font-weight:400">(check all that
                  apply)</span></label>
            </div>
            <!-- Subject checkboxes grouped -->
            <div id="enr-subjects-wrap"
              style="max-height:340px;overflow-y:auto;border:1.5px solid #e0e4ee;border-radius:10px;padding:0.75rem">
              <?php
              $groups = [
                'SHS Core' => 'SHS',
                'ABM Strand' => 'ABM',
                'GAS Strand' => 'GAS',
                'HUMSS Strand' => 'HUMSS',
                'High School' => 'HS',
              ];
              foreach ($groups as $label => $prefix): ?>
                <div style="margin-bottom:0.6rem">
                  <div
                    style="font-size:0.7rem;font-weight:800;color:#aaa;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.35rem;padding:0.2rem 0.35rem;background:#f8f9fc;border-radius:5px">
                    <?= $label ?>
                    <button type="button" onclick="selectGroup('<?= $prefix ?>')"
                      style="float:right;font-size:0.68rem;font-weight:700;color:var(--primary);background:none;border:none;cursor:pointer">Select
                      All</button>
                  </div>
                  <?php foreach ($allCourses as $c):
                    if (!str_starts_with($c['course_code'], $prefix))
                      continue; ?>
                    <label
                      style="display:flex;align-items:center;gap:0.5rem;padding:0.3rem 0.5rem;font-size:0.82rem;cursor:pointer;border-radius:6px;transition:background .15s"
                      onmouseover="this.style.background='#f0f4ff'" onmouseout="this.style.background=''">
                      <input type="checkbox" class="enr-chk enr-<?= $prefix ?>" value="<?= $c['id'] ?>"
                        data-code="<?= htmlspecialchars($c['course_code']) ?>"
                        style="accent-color:var(--primary);width:15px;height:15px">
                      <span><strong><?= htmlspecialchars($c['course_code']) ?></strong> —
                        <?= htmlspecialchars($c['course_name']) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.6rem">
              <span id="enr-count" style="font-size:0.78rem;color:#aaa">0 subjects selected</span>
              <div style="display:flex;gap:0.5rem">
                <button class="btn btn-reset-form btn-sm" onclick="clearEnrSelection()">Clear All</button>
                <button class="btn btn-primary btn-sm" onclick="submitEnrollment()">✓ Enroll</button>
              </div>
            </div>
            <div id="enr-error"
              style="display:none;background:#fff0ec;border:1px solid #ffcfbf;color:#c0392b;font-size:0.78rem;font-weight:600;padding:0.6rem 0.9rem;border-radius:8px;margin-top:0.75rem">
            </div>
          </div>

          <!-- RIGHT: Current enrollments for selected student -->
          <div class="assign-card">
            <h3>📖 Current Enrollments <span id="enr-student-name"
                style="color:#888;font-weight:600;font-size:0.85rem"></span></h3>
            <div id="enr-list-wrap">
              <div class="empty-assign"><span>🎓</span>Select a student to view their enrolled subjects.</div>
            </div>
          </div>
        </div>

        <!-- ALL ENROLLMENTS TABLE -->
        <div class="table-card">
          <div class="table-header">
            <h3>All Student Enrollments</h3>
            <div class="table-actions">
              <input class="search-input" type="text" id="enr-search" placeholder="🔍 Search..."
                oninput="filterEnrTable()">
              <select class="search-input" id="enr-filter-student" onchange="filterEnrTable()">
                <option value="">All Students</option>
                <?php foreach ($allStudents as $s): ?>
                  <option value="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>LRN / School ID</th>
                <th>Section</th>
                <th>Subject</th>
                <th>Code</th>
                <th>Enrolled On</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="enr-all-tbody">
              <tr>
                <td colspan="7" style="text-align:center;color:#bbb;padding:2rem;">Click "Enroll Students" to load data.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ANNOUNCEMENTS -->
      <div class="section-panel <?= $panel === 'announcements' ? 'active' : '' ?>" id="panel-announcements">
        <div class="page-header">
          <div class="page-title">Announcements</div>
          <div class="page-sub">Post school-wide announcements visible to all students and teachers.</div>
        </div>

        <!-- Compose Form -->
        <div class="form-card" style="margin-bottom:1.5rem">
          <div style="font-size:.88rem;font-weight:700;color:var(--text-primary);margin-bottom:1rem">📢 New Announcement</div>
          <div class="form-grid">
            <div class="form-group full">
              <label class="form-label">Title *</label>
              <input class="form-input" type="text" id="ann-title" placeholder="e.g. Holiday Notice — March 25">
            </div>
            <div class="form-group full">
              <label class="form-label">Message *</label>
              <textarea class="form-input" id="ann-body" rows="4"
                placeholder="Type your announcement here. Leave course blank to broadcast to everyone."
                style="resize:vertical"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Target Subject <span style="color:#aaa;font-weight:400">(optional — blank = school-wide)</span></label>
              <select class="form-input" id="ann-course">
                <option value="">— School-wide (All Students) —</option>
                <?php foreach ($allCourses as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'] . ' — ' . $c['course_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="ann-err"
              style="display:none;background:var(--danger-light);border:1px solid rgba(218,54,51,.25);color:var(--danger);font-size:.78rem;font-weight:600;padding:.6rem .9rem;border-radius:var(--radius)"></div>
            <div class="form-actions">
              <button class="btn btn-primary" onclick="sendAnnouncement()" id="ann-send-btn">📢 Post Announcement</button>
            </div>
          </div>
        </div>

        <!-- Announcements List -->
        <div class="table-card">
          <div class="table-header">
            <h3>Posted Announcements</h3>
            <div class="table-actions">
              <input class="search-input" type="text" placeholder="🔍 Search..." oninput="filterAnn(this.value)">
            </div>
          </div>
          <div id="ann-list-wrap">
            <?php if (empty($allAnnouncements)): ?>
              <div style="text-align:center;padding:2rem;color:#bbb;font-size:.84rem">
                No announcements posted yet.
              </div>
            <?php else:
              foreach ($allAnnouncements as $a): ?>
                <div class="ann-item" data-id="<?= $a['id'] ?>"
                  style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-muted);display:flex;flex-direction:column;gap:.4rem">
                  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                    <div>
                      <div style="font-weight:700;font-size:.88rem;color:var(--text-primary)"><?= htmlspecialchars($a['title']) ?></div>
                      <div style="font-size:.78rem;color:var(--text-muted);margin-top:.15rem">
                        📅 <?= $a['posted_at'] ?>
                        &nbsp;·&nbsp; 👤 <?= htmlspecialchars($a['author']) ?>
                        <?php if ($a['course_code']): ?>
                          &nbsp;·&nbsp; <span class="badge-role role-student"><?= htmlspecialchars($a['course_code']) ?></span>
                        <?php else: ?>
                          &nbsp;·&nbsp; <span style="font-size:.68rem;background:var(--primary-light);color:var(--primary);padding:.15rem .5rem;border-radius:100px;font-weight:700">School-wide</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="deleteAnn(<?= $a['id'] ?>,this)">🗑️ Delete</button>
                  </div>
                  <div style="font-size:.83rem;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap"><?= htmlspecialchars($a['body']) ?></div>
                </div>
              <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

      <!-- SETTINGS -->
      <div class="section-panel <?= $panel === 'settings' ? 'active' : '' ?>" id="panel-settings">
        <div class="page-header">
          <div class="page-title">Settings</div>
          <div class="page-sub">System configuration and preferences.</div>
        </div>
        <div class="form-card" style="max-width:520px;">
          <div class="form-grid">
            <div class="form-group full">
              <label class="form-label">System Name</label>
              <input class="form-input" type="text" value="Arandia College eLMS">
            </div>
            <div class="form-group full">
              <label class="form-label">School Year</label>
              <input class="form-input" type="text" value="2025 – 2026">
            </div>
            <div class="form-group full">
              <label class="form-label">Semester</label>
              <select class="form-input">
                <option>1st Semester</option>
                <option>2nd Semester</option>
                <option>Summer</option>
              </select>
            </div>
            <div class="form-group full">
              <label class="form-label">School Level</label>
              <select class="form-input">
                <option>SHS &amp; HS</option>
                <option>Senior High School Only</option>
                <option>High School Only</option>
              </select>
            </div>
            <div class="form-actions">
              <button class="btn btn-primary" onclick="showToast('✅ Settings saved!')">Save Settings</button>
            </div>
          </div>
        </div>
      </div>

    </main>

    <div class="footer-bar">© 2026 Arandia College eLMS — SHS &amp; HS Admin Panel</div>
  </div>

  <!-- EDIT MODAL -->
  <div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width:600px;">
      <div class="modal-title">✏️ Edit Account</div>
      <input type="hidden" id="edit-id">
      <div class="form-grid">
        <div class="form-group"><label class="form-label">First Name *</label><input class="form-input"
            id="edit-firstname"></div>
        <div class="form-group"><label class="form-label">Last Name *</label><input class="form-input"
            id="edit-lastname"></div>
        <div class="form-group"><label class="form-label">Middle Name</label><input class="form-input"
            id="edit-middlename"></div>
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select class="form-input" id="edit-role">
            <option value="Student">Student</option>
            <option value="Teacher">Teacher</option>
            <option value="Admin">Admin</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">LRN / Employee ID *</label><input class="form-input"
            id="edit-schoolid"></div>
        <div class="form-group"><label class="form-label">Username</label><input class="form-input" id="edit-username"
            disabled style="background:#f5f5f5;"></div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="edit-email"
            type="email"></div>
        <div class="form-group"><label class="form-label">Contact</label><input class="form-input" id="edit-contact">
        </div>
        <div class="form-group">
          <label class="form-label">Section / Grade Level</label>
          <select class="form-input" id="edit-section">
            <option value="">— Select —</option>
            <optgroup label="── Senior High School ──">
              <?php foreach ($shsSections as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
            </optgroup>
            <optgroup label="── High School ──">
              <?php foreach ($hsSections as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
            </optgroup>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-input" id="edit-status">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div class="form-group full">
          <label class="form-label">New Password <span style="color:#aaa;font-weight:400;">(leave blank to keep
              current)</span></label>
          <input class="form-input" id="edit-password" type="password" placeholder="Enter new password">
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn btn-reset-form" onclick="closeModal('editModal')">Cancel</button>
        <button class="btn btn-primary" onclick="submitEdit()">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- DELETE MODAL -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal">
      <div class="modal-title">🗑️ Delete Account</div>
      <p style="font-size:0.88rem;color:#555;line-height:1.6;">Are you sure you want to delete this account? This action
        cannot be undone.</p>
      <input type="hidden" id="delete-id">
      <div class="modal-actions">
        <button class="btn btn-reset-form" onclick="closeModal('deleteModal')">Cancel</button>
        <button class="btn btn-danger" onclick="submitDelete()">Delete Account</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const panelNames = {
      dashboard: 'Dashboard',
      create: 'Create Account',
      accounts: 'All Accounts',
      assign: 'Assign Subjects',
      enroll: 'Enroll Students',
      announcements: 'Announcements',
      settings: 'Settings'
    };
    function showPanel(name, btn) {
      document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.getElementById('panel-' + name).classList.add('active');
      if (btn) btn.classList.add('active');
      const el = document.getElementById('topbar-panel-name');
      if (el) el.textContent = panelNames[name] || name;
    }
    function showToast(msg, isError = false) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.background = isError ? 'var(--danger)' : 'var(--text-primary)';
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3500);
    }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); }
    function filterTable() {
      const q = document.getElementById('search-input').value.toLowerCase();
      const role = document.getElementById('filter-role').value;
      const section = document.getElementById('filter-section').value;
      document.querySelectorAll('#accounts-tbody tr').forEach(tr => {
        const mQ = !q || tr.dataset.name?.includes(q) || tr.dataset.username?.includes(q) || tr.dataset.schoolid?.includes(q);
        const mR = !role || tr.dataset.role === role;
        const mS = !section || tr.dataset.section === section;
        tr.style.display = (mQ && mR && mS) ? '' : 'none';
      });
    }
    function resetForm() {
      ['f-firstname', 'f-lastname', 'f-middlename', 'f-schoolid', 'f-username',
        'f-email', 'f-contact', 'f-password', 'f-confirm'].forEach(id => document.getElementById(id).value = '');
      document.getElementById('f-role').value = '';
      document.getElementById('f-section').value = '';
      document.getElementById('f-status').value = 'Active';
      document.getElementById('form-error').style.display = 'none';
    }
    async function submitCreate() {
      if (!confirm('Are you sure you want to create this account?')) return;
      const err = document.getElementById('form-error');
      err.style.display = 'none';
      const payload = {
        first_name: document.getElementById('f-firstname').value.trim(),
        last_name: document.getElementById('f-lastname').value.trim(),
        middle_name: document.getElementById('f-middlename').value.trim(),
        role: document.getElementById('f-role').value,
        school_id: document.getElementById('f-schoolid').value.trim(),
        username: document.getElementById('f-username').value.trim(),
        email: document.getElementById('f-email').value.trim(),
        contact: document.getElementById('f-contact').value.trim(),
        password: document.getElementById('f-password').value,
        confirm_password: document.getElementById('f-confirm').value,
        section_dept: document.getElementById('f-section').value,
        status: document.getElementById('f-status').value,
      };
      try {
        const res = await fetch('/Capstone1/api/admin/accounts.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        const raw = await res.text();
        let data;
        try {
          data = JSON.parse(raw);
        } catch (parseError) {
          throw new Error(`Server returned an invalid response (${res.status}).`);
        }
        if (!res.ok) {
          throw new Error(data.message || `Request failed (${res.status}).`);
        }
        if (data.success) { showToast('✅ Account created successfully!'); resetForm(); setTimeout(() => location.reload(), 1500); }
        else { err.textContent = '⚠️ ' + data.message; err.style.display = 'block'; }
      } catch (e) { err.textContent = '⚠️ ' + (e.message || 'Request failed. Please try again.'); err.style.display = 'block'; }
    }
    function openEdit(a) {
      console.log('openEdit called, a.id =', a.id, 'parsed =', parseInt(a.id, 10));
      document.getElementById('edit-id').value = parseInt(a.id, 10) || 0;
      document.getElementById('edit-firstname').value = a.first_name;
      document.getElementById('edit-lastname').value = a.last_name;
      document.getElementById('edit-middlename').value = a.middle_name || '';
      document.getElementById('edit-role').value = a.role;
      document.getElementById('edit-schoolid').value = a.school_id;
      document.getElementById('edit-username').value = a.username;
      document.getElementById('edit-email').value = a.email || '';
      document.getElementById('edit-contact').value = a.contact || '';
      document.getElementById('edit-section').value = a.section_dept || '';
      document.getElementById('edit-status').value = a.status;
      document.getElementById('edit-password').value = '';
      document.getElementById('editModal').classList.add('show');
    }
    async function submitEdit() {
      const id = Number(document.getElementById('edit-id').value);
      if (!id || isNaN(id)) { showToast('⚠️ Could not read account ID. Please try again.', true); return; }
      const payload = {
        id: id,
        first_name: document.getElementById('edit-firstname').value.trim(),
        last_name: document.getElementById('edit-lastname').value.trim(),
        middle_name: document.getElementById('edit-middlename').value.trim(),
        role: document.getElementById('edit-role').value,
        school_id: document.getElementById('edit-schoolid').value.trim(),
        email: document.getElementById('edit-email').value.trim(),
        contact: document.getElementById('edit-contact').value.trim(),
        section_dept: document.getElementById('edit-section').value,
        status: document.getElementById('edit-status').value,
        password: document.getElementById('edit-password').value,
      };
      try {
        const res = await fetch('/Capstone1/api/admin/accounts.php?action=update', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        const raw = await res.text();
        let data;
        try {
          data = JSON.parse(raw);
        } catch (parseError) {
          throw new Error(`Server returned an invalid response (${res.status}).`);
        }
        if (!res.ok) {
          throw new Error(data.message || `Request failed (${res.status}).`);
        }
        if (data.success) { closeModal('editModal'); showToast('✅ Account updated!'); setTimeout(() => location.reload(), 1200); }
        else showToast('⚠️ ' + data.message, true);
      } catch (e) { showToast('⚠️ ' + (e.message || 'Request failed. Please try again.'), true); }
    }
    function openDelete(id) { document.getElementById('delete-id').value = id; document.getElementById('deleteModal').classList.add('show'); }
    async function submitDelete() {
      const id = document.getElementById('delete-id').value;
      try {
        const res = await fetch(`api/accounts.php?action=delete&id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) { closeModal('deleteModal'); showToast('🗑️ Account deleted.'); setTimeout(() => location.reload(), 1200); }
        else showToast('⚠️ ' + data.message, true);
      } catch (e) { showToast('⚠️ Network error. Please try again.', true); }
    }
    <?php if ($toast): ?>showToast('<?= addslashes($toast) ?>', <?= $isError ? 'true' : 'false' ?>); <?php endif; ?>

    // ── Assign Subjects ───────────────────────────────────────────────────
    let allAssignments = [];

    async function loadAssignments() {
      try {
        const res = await fetch('api/teacher_assignments.php?action=list');
        const data = await res.json();
        if (data.success) {
          allAssignments = data.data;
          renderAssignTable(allAssignments);
        }
      } catch (e) { }
    }

    async function loadTeacherAssignments() {
      const tid = document.getElementById('a-teacher').value;
      const name = document.getElementById('a-teacher').selectedOptions[0]?.text || '';
      const wrap = document.getElementById('assign-list-wrap');
      const nameEl = document.getElementById('assign-teacher-name');

      if (!tid) {
        nameEl.textContent = '';
        wrap.innerHTML = '<div class="empty-assign"><span>👩‍🏫</span>Select a teacher to view their assignments.</div>';
        return;
      }
      nameEl.textContent = '— ' + name;
      wrap.innerHTML = '<div style="padding:1rem;color:#aaa;font-size:0.82rem;">Loading...</div>';
      try {
        const res = await fetch(`api/teacher_assignments.php?action=list&teacher_id=${tid}`);
        const data = await res.json();
        if (!data.success || data.data.length === 0) {
          wrap.innerHTML = '<div class="empty-assign"><span>📭</span>No assignments yet for this teacher.</div>';
          return;
        }
        wrap.innerHTML = data.data.map(a => `
          <div class="assign-list-item">
            <div class="assign-item-info">
              <div class="assign-item-name">${a.course_code} — ${a.course_name}</div>
              <div class="assign-item-meta">${a.section} &nbsp;·&nbsp; ${a.school_year} ${a.semester} Sem</div>
            </div>
            <button class="btn btn-danger btn-sm" onclick="removeAssignment(${a.id})">Remove</button>
          </div>`).join('');
        // Also refresh global table
        allAssignments = allAssignments.filter(x => x.teacher_id != tid).concat(data.data);
        renderAssignTable(allAssignments);
      } catch (e) {
        wrap.innerHTML = '<div class="empty-assign"><span>⚠️</span>Error loading assignments.</div>';
      }
    }

    function renderAssignTable(rows) {
      const tbody = document.getElementById('assign-all-tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#bbb;padding:2rem;">No assignments yet.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(a => `
        <tr data-teacher="${a.teacher_name.toLowerCase()}" data-info="${(a.course_name + ' ' + a.section).toLowerCase()}">
          <td><strong>${a.teacher_name}</strong></td>
          <td>${a.course_code} — ${a.course_name}</td>
          <td>${a.section}</td>
          <td>${a.school_year}</td>
          <td>${a.semester} Sem</td>
          <td><button class="btn btn-danger btn-sm" onclick="removeAssignment(${a.id})">Remove</button></td>
        </tr>`).join('');
    }

    function filterAssignTable() {
      const q = document.getElementById('assign-search').value.toLowerCase();
      const t = document.getElementById('assign-filter-teacher').value.toLowerCase();
      document.querySelectorAll('#assign-all-tbody tr').forEach(tr => {
        const mQ = !q || tr.dataset.info?.includes(q) || tr.dataset.teacher?.includes(q);
        const mT = !t || tr.dataset.teacher?.includes(t);
        tr.style.display = (mQ && mT) ? '' : 'none';
      });
    }

    function updateAssignCount() {
      const n = document.querySelectorAll('.a-chk:checked').length;
      document.getElementById('a-count').textContent = n + ' subject' + (n !== 1 ? 's' : '') + ' selected';
    }
    function selectAssignGroup(prefix) {
      document.querySelectorAll('.a-' + prefix).forEach(cb => cb.checked = true);
      updateAssignCount();
    }
    function clearAssignSelection() {
      document.querySelectorAll('.a-chk').forEach(cb => cb.checked = false);
      updateAssignCount();
    }

    async function submitAssign() {
      const errEl = document.getElementById('a-error');
      errEl.style.display = 'none';
      const teacher_id = document.getElementById('a-teacher').value;
      const course_ids = [...document.querySelectorAll('.a-chk:checked')].map(cb => parseInt(cb.value));
      const section    = document.getElementById('a-section').value;
      const school_year = document.getElementById('a-sy').value.trim();
      const semester   = document.getElementById('a-sem').value;

      if (!teacher_id || !course_ids.length || !section) {
        errEl.textContent = '⚠️ Teacher, at least one subject, and section are required.';
        errEl.style.display = 'block'; return;
      }
      // Submit one assignment per checked subject
      let saved = 0, errors = [];
      for (const course_id of course_ids) {
        try {
          const res = await fetch('api/teacher_assignments.php?action=assign', {
            method: 'POST',
            body: JSON.stringify({ teacher_id, course_id, section, school_year, semester })
          });
          const data = await res.json();
          if (data.success) saved++;
          else errors.push(data.message);
        } catch (e) { errors.push('Network error'); }
      }
      if (saved > 0) {
        showToast('✅ ' + saved + ' assignment' + (saved !== 1 ? 's' : '') + ' saved!');
        clearAssignSelection();
        document.getElementById('a-section').value = '';
        loadTeacherAssignments();
        loadAssignments();
      }
      if (errors.length) {
        errEl.textContent = '⚠️ ' + errors.join('; ');
        errEl.style.display = 'block';
      }
    }

    async function removeAssignment(id) {
      if (!confirm('Remove this assignment?')) return;
      try {
        const res = await fetch(`api/teacher_assignments.php?action=remove&id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
          showToast('🗑️ Assignment removed.');
          allAssignments = allAssignments.filter(a => a.id != id);
          renderAssignTable(allAssignments);
          loadTeacherAssignments();
        } else showToast('⚠️ ' + data.message, true);
      } catch (e) { showToast('⚠️ Network error.', true); }
    }

    // Auto-load assignments table on page load if assign panel is active
    <?php if ($panel === 'assign'): ?>loadAssignments(); <?php endif; ?>
    <?php if ($panel === 'enroll'): ?>loadAllEnrollments(); <?php endif; ?>

    // ── Enroll Students ───────────────────────────────────────────────────
    let allEnrollments = [];

    // Track checkbox counts
    document.querySelectorAll('.enr-chk').forEach(cb => {
      cb.addEventListener('change', updateEnrCount);
    });
    function updateEnrCount() {
      const n = document.querySelectorAll('.enr-chk:checked').length;
      document.getElementById('enr-count').textContent = n + ' subject' + (n !== 1 ? 's' : '') + ' selected';
    }
    function selectGroup(prefix) {
      document.querySelectorAll('.enr-' + prefix).forEach(cb => cb.checked = true);
      updateEnrCount();
    }
    function clearEnrSelection() {
      document.querySelectorAll('.enr-chk').forEach(cb => cb.checked = false);
      updateEnrCount();
    }

    async function loadAllEnrollments() {
      try {
        const res = await fetch('api/enrollments.php?action=list');
        const data = await res.json();
        if (data.success) { allEnrollments = data.data; renderEnrTable(allEnrollments); }
      } catch (e) { }
    }

    async function loadStudentEnrollments() {
      const sid = document.getElementById('enr-student').value;
      const selEl = document.getElementById('enr-student');
      const name = selEl.selectedOptions[0]?.text || '';
      const wrap = document.getElementById('enr-list-wrap');
      const nameEl = document.getElementById('enr-student-name');

      if (!sid) {
        nameEl.textContent = '';
        wrap.innerHTML = '<div class="empty-assign"><span>🎓</span>Select a student to view their enrolled subjects.</div>';
        return;
      }
      nameEl.textContent = '— ' + name.split(' (')[0];
      wrap.innerHTML = '<div style="padding:1rem;color:#aaa;font-size:0.82rem;">Loading...</div>';

      // Pre-check boxes for already enrolled subjects
      document.querySelectorAll('.enr-chk').forEach(cb => cb.checked = false);

      try {
        const res = await fetch(`api/enrollments.php?action=list&student_id=${sid}`);
        const data = await res.json();

        if (!data.success || !data.data.length) {
          wrap.innerHTML = '<div class="empty-assign"><span>📭</span>No subjects enrolled yet.</div>';
          return;
        }

        // Mark enrolled checkboxes
        data.data.forEach(e => {
          const cb = document.querySelector(`.enr-chk[value="${e.course_id}"]`);
          if (cb) cb.checked = true;
        });
        updateEnrCount();

        wrap.innerHTML = data.data.map(e => `
          <div class="assign-list-item">
            <div class="assign-item-info">
              <div class="assign-item-name">${e.course_code} — ${e.course_name}</div>
              <div class="assign-item-meta">Enrolled: ${e.enrolled_at} &nbsp;·&nbsp;
                <span style="color:${e.enroll_status === 'Enrolled' ? '#00875a' : '#c0392b'}">${e.enroll_status}</span>
              </div>
            </div>
            <button class="btn btn-danger btn-sm" onclick="removeEnrollment(${e.id})">Remove</button>
          </div>`).join('');

        // Refresh global table
        allEnrollments = allEnrollments.filter(x => x.student_id != sid).concat(data.data);
        renderEnrTable(allEnrollments);
      } catch (e) {
        wrap.innerHTML = '<div class="empty-assign"><span>⚠️</span>Error loading enrollments.</div>';
      }
    }

    function renderEnrTable(rows) {
      const tbody = document.getElementById('enr-all-tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#bbb;padding:2rem;">No enrollments yet.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(e => `
        <tr data-student="${e.student_name.toLowerCase()}" data-info="${(e.course_name + ' ' + e.course_code).toLowerCase()}">
          <td><strong>${e.student_name}</strong></td>
          <td>${e.school_id}</td>
          <td>${e.section_dept || '—'}</td>
          <td>${e.course_name}</td>
          <td><span class="badge-role role-student">${e.course_code}</span></td>
          <td>${e.enrolled_at}</td>
          <td><button class="btn btn-danger btn-sm" onclick="removeEnrollment(${e.id})">Remove</button></td>
        </tr>`).join('');
    }

    function filterEnrTable() {
      const q = document.getElementById('enr-search').value.toLowerCase();
      const s = document.getElementById('enr-filter-student').value.toLowerCase();
      document.querySelectorAll('#enr-all-tbody tr').forEach(tr => {
        const mQ = !q || tr.dataset.info?.includes(q) || tr.dataset.student?.includes(q);
        const mS = !s || tr.dataset.student?.includes(s);
        tr.style.display = (mQ && mS) ? '' : 'none';
      });
    }

    async function submitEnrollment() {
      const errEl = document.getElementById('enr-error');
      errEl.style.display = 'none';
      const student_id = document.getElementById('enr-student').value;
      const course_ids = [...document.querySelectorAll('.enr-chk:checked')].map(cb => parseInt(cb.value));

      if (!student_id) {
        errEl.textContent = '⚠️ Please select a student.'; errEl.style.display = 'block'; return;
      }
      if (!course_ids.length) {
        errEl.textContent = '⚠️ Please select at least one subject.'; errEl.style.display = 'block'; return;
      }

      try {
        const res = await fetch('api/enrollments.php?action=enroll', {
          method: 'POST', body: JSON.stringify({ student_id, course_ids })
        });
        const data = await res.json();
        if (data.success) {
          showToast('✅ ' + data.message);
          loadStudentEnrollments();
          loadAllEnrollments();
        } else {
          errEl.textContent = '⚠️ ' + data.message; errEl.style.display = 'block';
        }
      } catch (e) { errEl.textContent = '⚠️ Network error.'; errEl.style.display = 'block'; }
    }

    async function removeEnrollment(id) {
      if (!confirm('Remove this enrollment?')) return;
      try {
        const res = await fetch(`api/enrollments.php?action=remove&id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
          showToast('🗑️ Enrollment removed.');
          allEnrollments = allEnrollments.filter(e => e.id != id);
          renderEnrTable(allEnrollments);
          loadStudentEnrollments();
        } else showToast('⚠️ ' + data.message, true);
      } catch (e) { showToast('⚠️ Network error.', true); }
    }

    // ── Announcements ────────────────────────────────────────────────────
    function escHtml(s) {
      return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    async function sendAnnouncement() {
      const errEl  = document.getElementById('ann-err');
      errEl.style.display = 'none';
      const title    = document.getElementById('ann-title').value.trim();
      const body     = document.getElementById('ann-body').value.trim();
      const courseId = document.getElementById('ann-course').value;

      if (!title) { errEl.textContent = '⚠️ Title is required.'; errEl.style.display = 'block'; return; }
      if (!body)  { errEl.textContent = '⚠️ Message is required.'; errEl.style.display = 'block'; return; }

      const btn = document.getElementById('ann-send-btn');
      btn.disabled = true; btn.textContent = '⏳ Posting...';

      try {
        const res  = await fetch('/Capstone1/api/announcements.php?action=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ title, body, course_id: courseId || null })
        });
        const data = await res.json();

        if (data.success) {
          showToast('✅ Announcement posted!');
          document.getElementById('ann-title').value = '';
          document.getElementById('ann-body').value  = '';
          document.getElementById('ann-course').value = '';

          // Prepend to list
          const wrap = document.getElementById('ann-list-wrap');
          const noMsg = wrap.querySelector('div[style*="text-align:center"]');
          if (noMsg) noMsg.remove();

          const courseOpt = document.getElementById('ann-course');
          const courseCode = courseId
            ? (courseOpt.options[courseOpt.selectedIndex].text.split(' — ')[0])
            : null;
          const courseBadge = courseCode
            ? `<span style="font-size:.68rem;background:var(--primary-light);color:var(--primary);padding:.15rem .5rem;border-radius:100px;font-weight:700">${escHtml(courseCode)}</span>`
            : `<span style="font-size:.68rem;background:var(--primary-light);color:var(--primary);padding:.15rem .5rem;border-radius:100px;font-weight:700">School-wide</span>`;

          const div = document.createElement('div');
          div.className = 'ann-item';
          div.dataset.id = data.id;
          div.style.cssText = 'padding:1rem 1.25rem;border-bottom:1px solid var(--border-muted);display:flex;flex-direction:column;gap:.4rem';
          div.innerHTML = `
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
              <div>
                <div style="font-weight:700;font-size:.88rem;color:var(--text-primary)">${escHtml(title)}</div>
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:.15rem">
                  📅 Just now &nbsp;·&nbsp; 👤 <?= htmlspecialchars($first_name . ' ' . $last_name) ?>
                  &nbsp;·&nbsp; ${courseBadge}
                </div>
              </div>
              <button class="btn btn-danger btn-sm" onclick="deleteAnn(${data.id},this)">🗑️ Delete</button>
            </div>
            <div style="font-size:.83rem;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap">${escHtml(body)}</div>`;
          wrap.insertBefore(div, wrap.firstChild);
        } else {
          errEl.textContent = '⚠️ ' + (data.message || 'Failed to post.');
          errEl.style.display = 'block';
        }
      } catch (e) {
        errEl.textContent = '⚠️ Network error. Please try again.';
        errEl.style.display = 'block';
      }
      btn.disabled = false; btn.textContent = '📢 Post Announcement';
    }

    async function deleteAnn(id, btn) {
      if (!confirm('Delete this announcement? Students will no longer see it.')) return;
      try {
        const res  = await fetch(`/Capstone1/api/announcements.php?action=delete&id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.success) {
          btn.closest('.ann-item').remove();
          showToast('🗑️ Announcement deleted.');
        } else {
          showToast('⚠️ ' + data.message, true);
        }
      } catch (e) {
        showToast('⚠️ Network error.', true);
      }
    }

    function filterAnn(q) {
      q = q.toLowerCase();
      document.querySelectorAll('.ann-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    }
  </script>
</body>

</html>