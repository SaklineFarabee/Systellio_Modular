<?php
// ========================================================================
// client_profile.php — Systellio CRM
// Usage: client_profile.php?id=5
// ========================================================================
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$client_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($client_id <= 0) { header("Location: client_list.php"); exit(); }

// ── Toast vars ─────────────────────────────────────────────────────────
$toastMessage = "";
$toastType    = "";

// ── Handle: Submit Note ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_note'])) {
    $note_text = mysqli_real_escape_string($conn, trim($_POST['note_text'] ?? ''));
    $author    = mysqli_real_escape_string($conn, $_SESSION['name']);
    if ($note_text !== '') {
        // Ensure table + columns exist before inserting
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            author VARCHAR(100),
            note TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $_ec = []; $_cr = mysqli_query($conn, "SHOW COLUMNS FROM client_notes");
        if ($_cr) { while ($_c = mysqli_fetch_assoc($_cr)) $_ec[] = $_c['Field']; }
        if (!in_array('client_id',  $_ec)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN client_id INT NOT NULL DEFAULT 0 AFTER id");
        if (!in_array('author',     $_ec)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN author VARCHAR(100) AFTER client_id");
        if (!in_array('note',       $_ec)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN note TEXT NOT NULL AFTER author");
        if (!in_array('created_at', $_ec)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER note");

        $note_sql = "INSERT INTO client_notes (client_id, author, note, created_at)
                     VALUES ($client_id, '$author', '$note_text', NOW())";
        if (mysqli_query($conn, $note_sql)) {
            $toastMessage = "Note saved successfully!";
            $toastType    = "success";
        } else {
            $toastMessage = "Error saving note: " . mysqli_error($conn);
            $toastType    = "error";
        }
    } else {
        $toastMessage = "Note cannot be empty.";
        $toastType    = "error";
    }
    header("Location: client_profile.php?id=$client_id");
    exit();
}

// ── Fetch client + company ──────────────────────────────────────────────
$client = null;
$company = null;
$q = mysqli_query($conn, "SELECT c.*, co.company_name, co.company_email, co.company_number,
                                  co.company_website, co.fb_url, co.linkedin_url,
                                  co.insta_url, co.twitter_url, co.assigned_agent
                           FROM contacts c
                           LEFT JOIN companies co ON c.company_id = co.id
                           WHERE c.id = $client_id LIMIT 1");
if ($q && mysqli_num_rows($q) > 0) {
    $client = mysqli_fetch_assoc($q);
} else {
    header("Location: client_list.php");
    exit();
}

// ── Fetch notes ──────────────────────────────────────────────────────────
$notes = [];
$note_count = 0;

// 1) Create table if it does not exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS client_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    author VARCHAR(100),
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2) If table existed with wrong/missing columns, patch them safely
$_existing_cols = [];
$_col_res = mysqli_query($conn, "SHOW COLUMNS FROM client_notes");
if ($_col_res) {
    while ($_col = mysqli_fetch_assoc($_col_res)) $_existing_cols[] = $_col['Field'];
}
if (!in_array('client_id',  $_existing_cols)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN client_id INT NOT NULL DEFAULT 0 AFTER id");
if (!in_array('author',     $_existing_cols)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN author VARCHAR(100) AFTER client_id");
if (!in_array('note',       $_existing_cols)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN note TEXT NOT NULL AFTER author");
if (!in_array('created_at', $_existing_cols)) mysqli_query($conn, "ALTER TABLE client_notes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER note");

// 3) Now safely fetch notes
$nq = mysqli_query($conn, "SELECT * FROM client_notes WHERE client_id=$client_id ORDER BY created_at DESC");
if ($nq) {
    $note_count = mysqli_num_rows($nq);
    while ($row = mysqli_fetch_assoc($nq)) $notes[] = $row;
}

// ── Fetch linked tasks ───────────────────────────────────────────────────
$tasks = [];
$task_count = 0;
$tq = mysqli_query($conn, "SELECT * FROM tasks WHERE assigned_to = '" . mysqli_real_escape_string($conn, $client['name']) . "' ORDER BY due_date ASC");
if ($tq) {
    $task_count = mysqli_num_rows($tq);
    while ($row = mysqli_fetch_assoc($tq)) $tasks[] = $row;
}

// ── Helpers ───────────────────────────────────────────────────────────────
function h($v) { return htmlspecialchars($v ?? ''); }
function orNA($v) { return (!empty($v)) ? htmlspecialchars($v) : 'N/A'; }

$avatar_letter = strtoupper(substr($client['name'], 0, 1));
$company_name  = !empty($client['company_name']) ? $client['company_name'] : 'Independent';
$assigned_agent = !empty($client['assigned_agent']) ? $client['assigned_agent'] : 'Unassigned';

// Last contacted = latest note date
$last_contacted = 'N/A';
if (!empty($notes)) {
    $last_contacted = date('d M, Y', strtotime($notes[0]['created_at']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($client['name']) ?> — Client Profile | Systellio CRM</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }

        /* ── Toast ─────────────────────────────────────────── */
        #toastBox { visibility:hidden; min-width:250px; background:#333; color:#fff; text-align:center;
            border-radius:8px; padding:16px; position:fixed; z-index:9999; right:30px; top:30px;
            font-size:14px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,.15);
            display:flex; align-items:center; gap:10px;
            transform:translateX(120%); transition:transform .4s cubic-bezier(.68,-.55,.265,1.55), visibility .4s; }
        #toastBox.show { visibility:visible; transform:translateX(0); }
        #toastBox.success { background:#10b981; }
        #toastBox.error   { background:#ef4444; }

        /* ── Layout ─────────────────────────────────────────── */
        body { background:#f3f4f6; display:flex; height:100vh; overflow:hidden; color:#111827; transition:.3s; }
        .main-content { flex-grow:1; display:flex; flex-direction:column; overflow-y:auto; background:#f3f4f6; transition:.3s; }

        /* ── Navbar ─────────────────────────────────────────── */
        
        
        .breadcrumb a { color:#6b7280; text-decoration:none; transition:.2s; }
        .breadcrumb a:hover { color:#3b82f6; }
        .breadcrumb .current { color:#3b82f6; font-weight:700; }
        .breadcrumb .sep { color:#d1d5db; }
        
        
        
        
        .nav-icon-btn:hover { color:#3b82f6; }
        
        .user-profile i { font-size:24px; color:#3b82f6; }
        .back-btn { background:#0f172a; color:#fff; padding:9px 20px; border-radius:8px;
            font-size:13px; font-weight:700; text-decoration:none; display:flex; align-items:center;
            gap:8px; transition:.25s; }
        .back-btn:hover { background:#1e293b; transform:translateY(-1px); }

        /* ── Page wrapper ──────────────────────────────────── */
        .profile-page { padding:28px; display:flex; flex-direction:column; gap:22px; }

        /* ── Hero Banner ────────────────────────────────────── */
        .hero-banner {
            background: linear-gradient(135deg, #3b3fa5 0%, #5c5fe8 45%, #7c5ce8 100%);
            border-radius:14px; padding:28px 32px;
            display:flex; flex-direction:column; gap:18px;
            position:relative; overflow:hidden; color:#fff;
            box-shadow:0 8px 30px rgba(91,92,232,.3);
        }
        .hero-banner::before {
            content:''; position:absolute; right:-40px; top:-40px;
            width:260px; height:260px;
            background:rgba(255,255,255,.06); border-radius:50%;
        }
        .hero-banner::after {
            content:''; position:absolute; right:80px; bottom:-60px;
            width:180px; height:180px;
            background:rgba(255,255,255,.04); border-radius:50%;
        }
        /* Top row: avatar + name/tags */
        .hero-top-row { display:flex; align-items:center; gap:20px; z-index:1; }
        .hero-top-row .hero-info { flex:1; }
        .avatar-circle {
            width:68px; height:68px; background:rgba(255,255,255,.22);
            border-radius:14px; display:flex; align-items:center; justify-content:center;
            font-size:28px; font-weight:800; color:#fff;
            box-shadow:0 4px 15px rgba(0,0,0,.2); flex-shrink:0;
        }
        .hero-info h2 { font-size:24px; font-weight:800; margin-bottom:8px; }
        .hero-tags { display:flex; gap:8px; flex-wrap:wrap; }
        .tag-badge { font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px;
            background:rgba(255,255,255,.18); backdrop-filter:blur(4px); letter-spacing:.5px; }
        /* Bottom row: contact buttons + social icons */
        .hero-bottom-row {
            display:flex; align-items:center; justify-content:space-between;
            flex-wrap:wrap; gap:12px; z-index:1;
        }
        .hero-contact-btns { display:flex; gap:10px; flex-wrap:wrap; }
        .hero-contact-btn {
            background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
            color:#fff; padding:8px 16px; border-radius:8px; font-size:12px; font-weight:600;
            display:flex; align-items:center; gap:8px; backdrop-filter:blur(4px);
            transition:.25s; text-decoration:none; cursor:default;
        }
        .hero-contact-btn i { font-size:13px; }
        .hero-social-row { display:flex; gap:10px; align-items:center; z-index:1; }
        .social-circle {
            width:36px; height:36px; background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.25); border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:14px; color:#fff; cursor:pointer;
            transition:.25s; text-decoration:none;
        }
        .social-circle:hover { background:rgba(255,255,255,.3); transform:translateY(-2px); }

        /* ── Cards grid ─────────────────────────────────────── */
        .cards-row { display:grid; grid-template-columns:1fr 2fr; gap:22px; }
        .card { background:#fff; border-radius:14px; border:1px solid #e5e7eb;
            box-shadow:0 2px 12px rgba(0,0,0,.04); overflow:hidden; }
        .card-header { padding:18px 22px 14px; display:flex; align-items:center;
            justify-content:space-between; border-bottom:1px solid #f3f4f6; }
        .card-header h3 { font-size:15px; font-weight:800; color:#111827;
            display:flex; align-items:center; gap:10px; }
        .card-header h3 i { color:#3b82f6; font-size:15px; }
        .badge-count { background:#3b82f6; color:#fff; font-size:11px;
            font-weight:700; padding:3px 10px; border-radius:20px; }
        .card-body { padding:20px 22px; }

        /* ── Key Contact ────────────────────────────────────── */
        .contact-row { display:flex; justify-content:space-between; align-items:center;
            padding:11px 0; border-bottom:1px solid #f9fafb; }
        .contact-row:last-child { border-bottom:none; }
        .contact-label { font-size:11px; font-weight:700; color:#9ca3af;
            text-transform:uppercase; letter-spacing:.6px; }
        .contact-val { font-size:13px; font-weight:600; color:#111827; text-align:right; }
        .contact-val.blue { color:#3b82f6; }
        .designation-tag { background:#f3f4f6; border:1px solid #e5e7eb; color:#374151;
            font-size:11px; font-weight:700; padding:3px 10px; border-radius:6px; }
        .agent-chip { display:flex; align-items:center; gap:6px; }
        .agent-chip i { color:#3b82f6; }
        .date-chip { display:flex; align-items:center; gap:6px; color:#059669; }
        .date-chip i { color:#059669; }

        /* Social row inside card */
        .social-row { display:flex; gap:8px; }
        .soc-btn { width:32px; height:32px; border-radius:8px; background:#f3f4f6;
            display:flex; align-items:center; justify-content:center;
            color:#374151; font-size:14px; text-decoration:none; transition:.2s; }
        .soc-btn:hover { background:#dbeafe; color:#3b82f6; }

        /* WhatsApp button */
        .wa-btn { margin-top:16px; width:100%; background:#25D366; color:#fff;
            border:none; padding:12px; border-radius:10px; font-size:14px;
            font-weight:700; cursor:pointer; display:flex; align-items:center;
            justify-content:center; gap:10px; transition:.25s; text-decoration:none; }
        .wa-btn:hover { background:#1ebe5d; transform:translateY(-1px); }

        /* ── Notes ──────────────────────────────────────────── */
        .note-label { font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:.6px; margin-bottom:10px; }
        .note-textarea { width:100%; min-height:110px; border:1px solid #e5e7eb;
            border-radius:10px; padding:14px 16px; font-size:14px; color:#374151;
            background:#f9fafb; outline:none; resize:vertical; transition:.25s;
            font-family:'Inter',sans-serif; }
        .note-textarea:focus { border-color:#3b82f6; background:#fff;
            box-shadow:0 0 0 3px rgba(59,130,246,.1); }
        .submit-note-btn { margin-top:10px; float:right; background:#3b82f6; color:#fff;
            border:none; padding:10px 22px; border-radius:8px; font-size:13px;
            font-weight:700; cursor:pointer; display:flex; align-items:center;
            gap:8px; transition:.25s; }
        .submit-note-btn:hover { background:#2563eb; }
        .notes-divider { clear:both; margin-top:20px; padding-top:18px;
            border-top:1px solid #f3f4f6; }
        .history-label { font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:.6px; margin-bottom:12px; }
        .note-item { background:#f9fafb; border:1px solid #f3f4f6;
            border-radius:10px; padding:14px 16px; margin-bottom:10px; }
        .note-item-header { display:flex; justify-content:space-between;
            align-items:center; margin-bottom:8px; }
        .note-author { font-size:12px; font-weight:700; color:#3b82f6; }
        .note-date { font-size:11px; color:#9ca3af; }
        .note-text { font-size:13px; color:#374151; line-height:1.6; }
        .empty-state { display:flex; flex-direction:column; align-items:center;
            justify-content:center; padding:40px 20px; color:#9ca3af; }
        .empty-state i { font-size:36px; margin-bottom:12px; color:#d1d5db; }
        .empty-state p { font-size:13px; font-weight:500; }
        .empty-state small { font-size:12px; margin-top:4px; }

        /* ── Bottom row ──────────────────────────────────────── */
        .bottom-row { display:grid; grid-template-columns:1fr 2fr; gap:22px; }

        /* ── System Summary ──────────────────────────────────── */
        .system-summary-text { font-size:13px; color:#374151; line-height:1.7; }
        .system-summary-text strong { color:#111827; }

        /* ── Tasks ───────────────────────────────────────────── */
        .tasks-table { width:100%; border-collapse:collapse; }
        .tasks-table th { font-size:11px; font-weight:700; color:#6b7280;
            text-transform:uppercase; letter-spacing:.6px; padding:10px 12px;
            border-bottom:1px solid #f3f4f6; text-align:left; }
        .tasks-table td { padding:12px 12px; border-bottom:1px solid #f9fafb;
            font-size:13px; color:#374151; }
        .tasks-table tr:last-child td { border-bottom:none; }
        .status-pill { font-size:11px; font-weight:700; padding:3px 10px;
            border-radius:20px; display:inline-block; }
        .status-pill.todo      { background:#fef3c7; color:#92400e; }
        .status-pill.inprog    { background:#dbeafe; color:#1d4ed8; }
        .status-pill.done      { background:#d1fae5; color:#065f46; }
        .status-pill.onhold    { background:#f3f4f6; color:#374151; }

        /* ── Dark mode ───────────────────────────────────────── */
        body.dark-mode { background:#0f172a; color:#f8fafc; }
        body.dark-mode .main-content { background:#0f172a; }
        body.dark-mode 
        body.dark-mode .breadcrumb, body.dark-mode .breadcrumb a { color:#94a3b8; }
        body.dark-mode .card { background:#1e293b; border-color:#334155; }
        body.dark-mode .card-header { border-color:#334155; }
        body.dark-mode .card-header h3 { color:#f8fafc; }
        body.dark-mode .contact-row { border-color:#334155; }
        body.dark-mode .contact-val { color:#f8fafc; }
        body.dark-mode .designation-tag { background:#334155; border-color:#475569; color:#f8fafc; }
        body.dark-mode .soc-btn { background:#334155; color:#94a3b8; }
        body.dark-mode .soc-btn:hover { background:#1e40af; color:#fff; }
        body.dark-mode .note-textarea { background:#0f172a; border-color:#334155; color:#f8fafc; }
        body.dark-mode .note-textarea:focus { background:#1e293b; border-color:#3b82f6; }
        body.dark-mode .note-item { background:#0f172a; border-color:#334155; }
        body.dark-mode .note-text { color:#cbd5e1; }
        body.dark-mode .tasks-table th { color:#94a3b8; border-color:#334155; }
        body.dark-mode .tasks-table td { color:#cbd5e1; border-color:#334155; }
        body.dark-mode .system-summary-text { color:#cbd5e1; }
        body.dark-mode .system-summary-text strong { color:#f8fafc; }
        body.dark-mode 
    </style>
</head>
<body>

<div id="toastBox">
    <i id="toastIcon" class="fa-solid fa-circle-check"></i>
    <span id="toastMsg">Action Successful!</span>
</div>

<?php $activePage = 'client_list'; $sidebarRole = strtoupper(str_replace('_',' ',$_SESSION['role'])); include 'sidebar.php'; ?>

<div class="main-content">

    <!-- Navbar -->
    <?php include 'topbar.php'; ?>

    <div class="profile-page">

        <!-- ── Hero Banner ──────────────────────────────────── -->
        <div class="hero-banner">
            <!-- Top row: avatar + name + tags -->
            <div class="hero-top-row">
                <div class="avatar-circle"><?= $avatar_letter ?></div>
                <div class="hero-info">
                    <h2><?= h($client['name']) ?></h2>
                    <div class="hero-tags">
                        <span class="tag-badge"><i class="fa-solid fa-building" style="font-size:10px;margin-right:5px;"></i><?= h($company_name) ?></span>
                        <span class="tag-badge"><i class="fa-solid fa-id-badge" style="font-size:10px;margin-right:4px;"></i>ID: #<?= str_pad($client_id, 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                </div>
            </div>
            <!-- Bottom row: contact buttons + social icons -->
            <div class="hero-bottom-row">
                <div class="hero-contact-btns">
                    <div class="hero-contact-btn">
                        <i class="fa-solid fa-phone"></i>
                        <?= !empty($client['phone']) ? h($client['phone']) : 'N/A' ?>
                    </div>
                    <div class="hero-contact-btn">
                        <i class="fa-solid fa-envelope"></i>
                        <?= !empty($client['email']) ? h($client['email']) : 'N/A' ?>
                    </div>
                </div>
                <div class="hero-social-row">
                    <?php
                    $socials = [
                        ['fa-brands fa-facebook-f', $client['fb_url'] ?? ''],
                        ['fa-brands fa-linkedin-in', $client['linkedin_url'] ?? ''],
                        ['fa-brands fa-twitter', $client['twitter_url'] ?? ''],
                        ['fa-brands fa-instagram', $client['insta_url'] ?? ''],
                    ];
                    foreach ($socials as $s):
                        $href   = !empty($s[1]) ? $s[1] : '#';
                        $target = !empty($s[1]) ? 'target="_blank"' : '';
                    ?>
                    <a href="<?= $href ?>" <?= $target ?> class="social-circle"><i class="<?= $s[0] ?>"></i></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Top Cards Row ─────────────────────────────────── -->
        <div class="cards-row">

            <!-- Key Contact Person -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-user"></i> Key Contact Person</h3>
                </div>
                <div class="card-body">
                    <div class="contact-row">
                        <span class="contact-label">Full Name</span>
                        <span class="contact-val blue"><?= h($client['name']) ?></span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Designation</span>
                        <span class="contact-val">
                            <?php if(!empty($client['designation'])): ?>
                                <span class="designation-tag"><?= h($client['designation']) ?></span>
                            <?php else: ?>
                                <span class="designation-tag">N/A</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Direct Phone</span>
                        <span class="contact-val"><?= orNA($client['phone']) ?></span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Direct Email</span>
                        <span class="contact-val"><?= orNA($client['email']) ?></span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Assigned Agent(s)</span>
                        <span class="contact-val">
                            <span class="agent-chip"><i class="fa-solid fa-user-group"></i><?= h($assigned_agent) ?></span>
                        </span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Last Contacted</span>
                        <span class="contact-val">
                            <span class="date-chip"><i class="fa-regular fa-calendar-check"></i><?= $last_contacted ?></span>
                        </span>
                    </div>
                    <div class="contact-row">
                        <span class="contact-label">Personal Socials</span>
                        <span class="contact-val">
                            <div class="social-row">
                                <?php foreach ($socials as $s):
                                    $href = !empty($s[1]) ? $s[1] : '#';
                                    $target = !empty($s[1]) ? 'target="_blank"' : '';
                                ?>
                                <a href="<?= $href ?>" <?= $target ?> class="soc-btn"><i class="<?= $s[0] ?>"></i></a>
                                <?php endforeach; ?>
                            </div>
                        </span>
                    </div>

                    <!-- WhatsApp button -->
                    <?php
                    $wa_phone = preg_replace('/[^0-9]/', '', $client['phone'] ?? '');
                    $wa_href = !empty($wa_phone) ? "https://wa.me/$wa_phone" : '#';
                    ?>
                    <a href="<?= $wa_href ?>" target="_blank" class="wa-btn">
                        <i class="fa-brands fa-whatsapp" style="font-size:18px;"></i> Open WhatsApp Chat
                    </a>
                </div>
            </div>

            <!-- Conversation Notes & Logs -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-comments"></i> Conversation Notes &amp; Logs</h3>
                    <span class="badge-count"><?= $note_count ?> Notes</span>
                </div>
                <div class="card-body">
                    <!-- Submit new note -->
                    <form method="POST" action="client_profile.php?id=<?= $client_id ?>">
                        <p class="note-label">Log New Interaction</p>
                        <textarea class="note-textarea" name="note_text"
                            placeholder="Type your meeting summary, call details or updates here..."></textarea>
                        <button type="submit" name="submit_note" class="submit-note-btn">
                            <i class="fa-solid fa-paper-plane"></i> Submit Note
                        </button>
                    </form>

                    <!-- History -->
                    <div class="notes-divider">
                        <p class="history-label">History Log</p>
                        <?php if (empty($notes)): ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open"></i>
                            <p>No conversation history found.</p>
                            <small>Be the first to log an interaction!</small>
                        </div>
                        <?php else: ?>
                            <?php foreach ($notes as $note): ?>
                            <div class="note-item">
                                <div class="note-item-header">
                                    <span class="note-author"><i class="fa-solid fa-user-pen"></i> <?= h($note['author']) ?></span>
                                    <span class="note-date"><?= date('d M Y, h:i A', strtotime($note['created_at'])) ?></span>
                                </div>
                                <p class="note-text"><?= nl2br(h($note['note'])) ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div><!-- /cards-row -->

        <!-- ── Bottom Cards Row ───────────────────────────────── -->
        <div class="bottom-row">

            <!-- System Summary -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-robot"></i> System Summary</h3>
                </div>
                <div class="card-body">
                    <p class="system-summary-text">
                        Profile established for <strong><?= h($client['name']) ?></strong>,
                        acting as a representative from
                        <strong><?= h($company_name) ?></strong>.
                        Account is actively managed by
                        <strong><?= h($assigned_agent) ?></strong>.
                        <?php if ($last_contacted !== 'N/A'): ?>
                            Last logged interaction was on <strong><?= $last_contacted ?></strong>.
                        <?php else: ?>
                            No interaction has been logged yet.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Linked Tasks -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fa-solid fa-list-check"></i> Linked Tasks</h3>
                    <span class="badge-count" style="background:#111827;"><?= $task_count ?> Tasks</span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($tasks)): ?>
                    <div class="empty-state" style="padding:30px 20px;">
                        <p style="font-size:13px; color:#9ca3af;">No active tasks linked to this company yet.</p>
                    </div>
                    <?php else: ?>
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>Task Objective</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task):
                                $statusClass = match(strtolower($task['status'])) {
                                    'in progress' => 'inprog',
                                    'done', 'completed' => 'done',
                                    'on hold' => 'onhold',
                                    default => 'todo'
                                };
                            ?>
                            <tr>
                                <td><?= h($task['title']) ?></td>
                                <td><span class="status-pill <?= $statusClass ?>"><?= h($task['status']) ?></span></td>
                                <td><?= !empty($task['due_date']) ? date('d M Y', strtotime($task['due_date'])) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /bottom-row -->

    </div><!-- /profile-page -->
</div><!-- /main-content -->

<script>
    function showToast(message, type) {
        const toast = document.getElementById("toastBox");
        document.getElementById("toastMsg").innerText = message;
        toast.className = "show " + type;
        document.getElementById("toastIcon").className =
            (type === 'success') ? "fa-solid fa-circle-check" : "fa-solid fa-circle-xmark";
        setTimeout(() => { toast.className = toast.className.replace("show", "").trim(); }, 3500);
    }

    window.onload = function () {
        <?php if ($toastMessage !== ''): ?>
        showToast("<?= $toastMessage ?>", "<?= $toastType ?>");
        <?php endif; ?>
    };
</script>
</body>
</html>