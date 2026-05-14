<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================
session_start();
@include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}

$toastMessage = "";
$toastType    = "";

// ========================================================================
// 2. FETCH ANALYTICS DATA
// ========================================================================
$totalRevenue        = 0;
$activeDealsCount    = 0;
$wonDealsCount       = 0;
$lostDealsCount      = 0;
$totalTasksCount     = 0;
$completedTasksCount = 0;
$totalCompaniesCount = 0;
$totalUsersCount     = 0;
$totalCampaigns      = 0;
$activeCampaigns     = 0;
$totalContacts       = 0;

$recentDeals      = [];
$taskStatusData   = ['todo' => 0, 'progress' => 0, 'done' => 0, 'overdue' => 0];
$dealsByStage     = [];
$usersByRole      = [];
$campaignsByType  = [];
$recentCampaigns  = [];

if (isset($conn)) {
    try {
        // Deal Analytics
        $deal_query = mysqli_query($conn, "SELECT deal_name, deal_value, stage, created_at FROM deals ORDER BY id DESC");
        if ($deal_query) {
            while ($row = mysqli_fetch_assoc($deal_query)) {
                $val   = (float)$row['deal_value'];
                $stage = strtolower($row['stage']);
                if ($stage == 'won') {
                    $totalRevenue += $val;
                    $wonDealsCount++;
                } elseif ($stage == 'lost') {
                    $lostDealsCount++;
                } else {
                    $activeDealsCount++;
                }
                $stageLabel = ucfirst($row['stage']);
                $dealsByStage[$stageLabel] = ($dealsByStage[$stageLabel] ?? 0) + 1;
                if (count($recentDeals) < 5) $recentDeals[] = $row;
            }
        }

        // Task Analytics
        $task_query = mysqli_query($conn, "SELECT status FROM tasks");
        if ($task_query) {
            while ($row = mysqli_fetch_assoc($task_query)) {
                $totalTasksCount++;
                $t = strtolower($row['status']);
                if (strpos($t, 'done') !== false || strpos($t, 'complete') !== false) {
                    $completedTasksCount++;
                    $taskStatusData['done']++;
                } elseif (strpos($t, 'progress') !== false) {
                    $taskStatusData['progress']++;
                } elseif (strpos($t, 'overdue') !== false) {
                    $taskStatusData['overdue']++;
                } else {
                    $taskStatusData['todo']++;
                }
            }
        }

        // Company & User Counts
        $comp_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM companies");
        if ($comp_q) $totalCompaniesCount = mysqli_fetch_assoc($comp_q)['c'] ?? 0;

        $user_q = mysqli_query($conn, "SELECT role, COUNT(*) as c FROM users WHERE status='active' GROUP BY role");
        if ($user_q) {
            while ($row = mysqli_fetch_assoc($user_q)) {
                $totalUsersCount += $row['c'];
                $usersByRole[ucfirst($row['role'])] = $row['c'];
            }
        }

        // Contacts
        $cont_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM contacts");
        if ($cont_q) $totalContacts = mysqli_fetch_assoc($cont_q)['c'] ?? 0;

        // Campaigns
        $camp_q = mysqli_query($conn, "SELECT campaign_name, campaign_type, status, budget, currency, start_date, end_date FROM campaigns ORDER BY id DESC");
        if ($camp_q) {
            while ($row = mysqli_fetch_assoc($camp_q)) {
                $totalCampaigns++;
                if (strtolower($row['status']) === 'active') $activeCampaigns++;
                $type = $row['campaign_type'];
                $campaignsByType[$type] = ($campaignsByType[$type] ?? 0) + 1;
                if (count($recentCampaigns) < 5) $recentCampaigns[] = $row;
            }
        }

    } catch (mysqli_sql_exception $e) {
        // Use fallbacks
    }
}

// Calculate percentages
$winRate            = ($wonDealsCount + $lostDealsCount > 0) ? round(($wonDealsCount / ($wonDealsCount + $lostDealsCount)) * 100) : 0;
$taskCompletionRate = ($totalTasksCount > 0) ? round(($completedTasksCount / $totalTasksCount) * 100) : 0;

// Dummy data if DB is empty
if ($totalRevenue == 0 && $totalTasksCount == 0 && empty($recentDeals)) {
    $totalRevenue        = 45200;
    $activeDealsCount    = 12;
    $wonDealsCount       = 17;
    $lostDealsCount      = 8;
    $winRate             = 68;
    $taskCompletionRate  = 85;
    $totalCompaniesCount = 24;
    $totalUsersCount     = 8;
    $totalContacts       = 36;
    $totalCampaigns      = 5;
    $activeCampaigns     = 2;
    $taskStatusData      = ['todo' => 15, 'progress' => 8, 'done' => 45, 'overdue' => 3];
    $dealsByStage        = ['Lead' => 5, 'Proposal' => 4, 'Negotiation' => 3, 'Won' => 17, 'Lost' => 8];
    $usersByRole         = ['Super_admin' => 1, 'Admin' => 2, 'Manager' => 2, 'Agent' => 3];
    $campaignsByType     = ['Email' => 2, 'Social Media' => 1, 'Paid Ads' => 1, 'Content Marketing' => 1];
    $recentCampaigns     = [
        ['campaign_name' => 'Q2 Email Blast', 'campaign_type' => 'Email', 'status' => 'Active', 'budget' => '500', 'currency' => 'USD', 'start_date' => date('Y-m-d', strtotime('-5 days')), 'end_date' => date('Y-m-d', strtotime('+10 days'))],
        ['campaign_name' => 'Social Spring', 'campaign_type' => 'Social Media', 'status' => 'Planning', 'budget' => '1200', 'currency' => 'USD', 'start_date' => date('Y-m-d', strtotime('+3 days')), 'end_date' => date('Y-m-d', strtotime('+20 days'))],
    ];
    $recentDeals = [
        ['deal_name' => 'Enterprise CRM Upgrade',    'deal_value' => '12000', 'stage' => 'Won',         'created_at' => date('Y-m-d', strtotime('-2 days'))],
        ['deal_name' => 'Website Redesign Phase 1',  'deal_value' => '4500',  'stage' => 'Negotiation', 'created_at' => date('Y-m-d', strtotime('-4 days'))],
        ['deal_name' => 'SEO Optimization Q3',       'deal_value' => '2100',  'stage' => 'Proposal',    'created_at' => date('Y-m-d', strtotime('-5 days'))],
        ['deal_name' => 'Cloud Migration',           'deal_value' => '8500',  'stage' => 'Lead',        'created_at' => date('Y-m-d', strtotime('-1 week'))],
    ];
}

// JSON encode for JS charts / export
$taskStatusJson   = json_encode($taskStatusData);
$dealsByStageJson = json_encode($dealsByStage);
$usersByRoleJson  = json_encode($usersByRole);
$campByTypeJson   = json_encode($campaignsByType);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="img/favicon.png">
    <title>Analytics & Reports — Systellio CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ================================================================
           GLOBAL — matches all other CRM pages (Inter, same token set)
        ================================================================ */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            height: 100vh;
            overflow: hidden;
            color: #111827;
            transition: background-color .3s, color .3s;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background-color: #f3f4f6;
            transition: background-color .3s;
        }

        /* ── TOP NAVBAR ── */
        
        
        .toggle-btn:hover { color: #111827; }
        
        
        .nav-icon-btn:hover { color: #3b82f6; }
        .notification-badge { position: absolute; top: -4px; right: -4px; background: #ef4444; color: #fff; font-size: 9px; font-weight: 700; padding: 2px 5px; border-radius: 50%; border: 2px solid #fff; }
        
        .user-profile i { font-size: 24px; color: #3b82f6; }

        /* ── PAGE WRAPPER ── */
        .analytics-container { padding: 30px; }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -.5px;
            margin-bottom: 4px;
            transition: color .3s;
        }
        .page-header p {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }
        .btn-export {
            background: #10b981;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background .25s, transform .15s;
            white-space: nowrap;
        }
        .btn-export:hover { background: #059669; transform: translateY(-1px); }
        .btn-export:active { transform: translateY(0); }

        /* ── SECTION LABEL ── */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #9ca3af;
            margin-bottom: 14px;
            margin-top: 8px;
        }

        /* ── METRIC CARDS — 4 col ── */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 10px;
        }
        .metric-card {
            background: #fff;
            padding: 22px 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 6px rgba(0,0,0,.03);
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
        }
        .metric-card:hover { box-shadow: 0 6px 18px rgba(0,0,0,.07); transform: translateY(-2px); }
        .metric-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; }
        .metric-card.c-green::before  { background:#10b981; }
        .metric-card.c-blue::before   { background:#3b82f6; }
        .metric-card.c-amber::before  { background:#f59e0b; }
        .metric-card.c-violet::before { background:#8b5cf6; }
        .metric-card.c-rose::before   { background:#f43f5e; }
        .metric-card.c-cyan::before   { background:#06b6d4; }

        .metric-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .9px;
            color: #6b7280;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .metric-value {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 5px;
            line-height: 1;
            transition: color .3s;
        }
        .metric-sub { font-size: 12px; font-weight: 600; }
        .metric-sub.positive { color:#10b981; }
        .metric-sub.neutral  { color:#6b7280; }
        .metric-sub.warning  { color:#f59e0b; }

        /* ── SECOND ROW — 3 col metrics ── */
        .metrics-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
            margin-top: 18px;
        }

        /* ── PANELS GRID ── */
        .panels-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .panels-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }

        .dash-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 22px 24px;
            box-shadow: 0 2px 6px rgba(0,0,0,.03);
            transition: .3s;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .panel-title {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color .3s;
        }
        .panel-link { font-size: 12px; color:#3b82f6; text-decoration:none; font-weight:600; }
        .panel-link:hover { text-decoration: underline; }

        /* ── SIMPLE TABLE ── */
        .simple-table { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
        .simple-table th {
            padding: 10px 10px;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: .6px;
            border-bottom: 2px solid #f3f4f6;
        }
        .simple-table td {
            padding: 13px 10px;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            border-bottom: 1px solid #f9fafb;
            transition: color .3s;
        }
        .simple-table tbody tr:last-child td { border-bottom: none; }
        .simple-table tbody tr:hover td { background: #f9fafb; }

        /* ── PILLS ── */
        .pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            display: inline-block;
        }
        .pill.won         { background:#d1fae5; color:#059669; }
        .pill.lost        { background:#fee2e2; color:#dc2626; }
        .pill.lead        { background:#e5e7eb; color:#4b5563; }
        .pill.proposal    { background:#dbeafe; color:#2563eb; }
        .pill.negotiation { background:#fef3c7; color:#d97706; }
        .pill.active      { background:#d1fae5; color:#059669; }
        .pill.planning    { background:#dbeafe; color:#2563eb; }
        .pill.on-hold     { background:#fef3c7; color:#d97706; }
        .pill.completed   { background:#ede9fe; color:#7c3aed; }

        /* ── PROGRESS BARS ── */
        .progress-item { margin-bottom: 16px; }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 6px;
            transition: color .3s;
        }
        .progress-label .pct { color: #9ca3af; font-weight: 500; }
        .progress-track { width:100%; height:7px; background:#f3f4f6; border-radius:10px; overflow:hidden; }
        .progress-fill  { height:100%; border-radius:10px; transition: width .6s ease; }
        .fill-done { background:#10b981; }
        .fill-prog { background:#3b82f6; }
        .fill-todo { background:#9ca3af; }
        .fill-over { background:#ef4444; }

        /* ── BAR CHART (CSS only) ── */
        .bar-chart { display:flex; align-items:flex-end; gap:10px; height:110px; margin-top:12px; }
        .bar-wrap  { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; }
        .bar       { width:100%; border-radius:6px 6px 0 0; transition:height .5s ease; min-height:4px; }
        .bar-label { font-size:10px; color:#6b7280; font-weight:600; text-align:center; line-height:1.3; }
        .bar-count { font-size:11px; font-weight:700; color:#374151; }

        /* ── DONUT RING (SVG) ── */
        .donut-wrap { display:flex; align-items:center; gap:20px; margin-top:8px; }
        .donut-legend { flex:1; }
        .legend-item  { display:flex; align-items:center; gap:8px; margin-bottom:10px; font-size:12px; color:#4b5563; font-weight:600; }
        .legend-dot   { width:9px; height:9px; border-radius:50%; flex-shrink:0; }

        /* ── STAT MINI ROWS ── */
        .stat-row { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f3f4f6; }
        .stat-row:last-child { border-bottom:none; }
        .stat-row-label { font-size:13px; font-weight:600; color:#374151; display:flex; align-items:center; gap:8px; }
        .stat-row-val   { font-size:14px; font-weight:800; color:#111827; }

        /* ── DARK MODE ── */
        body.dark-mode { background:#0f172a; color:#f8fafc; }
        body.dark-mode .main-content { background:#0f172a; }
        body.dark-mode 
        body.dark-mode 
        body.dark-mode .nav-icon-btn:hover { color:#f8fafc; }
        body.dark-mode .page-header h1 { color:#f8fafc; }
        body.dark-mode .metric-card,
        body.dark-mode .dash-panel { background:#1e293b; border-color:#334155; }
        body.dark-mode .metric-value,
        body.dark-mode .panel-title { color:#f8fafc; }
        body.dark-mode .metric-title,
        body.dark-mode .metric-sub.neutral { color:#94a3b8; }
        body.dark-mode .panel-header { border-bottom-color:#334155; }
        body.dark-mode .simple-table th { border-bottom-color:#334155; color:#94a3b8; }
        body.dark-mode .simple-table td { border-bottom-color:#1e293b; color:#cbd5e1; }
        body.dark-mode .simple-table tbody tr:hover td { background:#263348; }
        body.dark-mode .progress-label { color:#cbd5e1; }
        body.dark-mode .progress-track { background:#334155; }
        body.dark-mode .pill.lead  { background:#334155; color:#94a3b8; }
        body.dark-mode .bar-label  { color:#94a3b8; }
        body.dark-mode .bar-count  { color:#cbd5e1; }
        body.dark-mode .stat-row   { border-bottom-color:#334155; }
        body.dark-mode .stat-row-label { color:#cbd5e1; }
        body.dark-mode .stat-row-val   { color:#f8fafc; }
        body.dark-mode .section-label  { color:#475569; }
        body.dark-mode .notification-badge { border-color:#1e293b; }
        body.dark-mode .donut-legend .legend-item { color:#94a3b8; }
    </style>
</head>
<body>

<?php $activePage = 'analytics'; include 'sidebar.php'; ?>

<div class="main-content">

    <!-- TOP NAVBAR -->
    <?php include 'topbar.php'; ?>

    <div class="analytics-container">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div>
                <h1>Analytics &amp; Reports</h1>
                <p>Performance metrics and business intelligence at a glance.</p>
            </div>
            <button class="btn-export" onclick="exportFullReport()">
                <i class="fa-solid fa-file-arrow-down"></i> Export Full Report
            </button>
        </div>

        <!-- ── ROW 1: 4 Key KPI Cards ── -->
        <p class="section-label">Key Metrics</p>
        <div class="metrics-grid">
            <div class="metric-card c-green">
                <div class="metric-title">Total Won Revenue <i class="fa-solid fa-sack-dollar" style="color:#10b981;"></i></div>
                <div class="metric-value">$<?php echo number_format($totalRevenue, 0); ?></div>
                <div class="metric-sub positive"><i class="fa-solid fa-arrow-trend-up"></i> From <?php echo $wonDealsCount; ?> won deal<?php echo $wonDealsCount != 1 ? 's' : ''; ?></div>
            </div>
            <div class="metric-card c-blue">
                <div class="metric-title">Deal Win Rate <i class="fa-solid fa-trophy" style="color:#3b82f6;"></i></div>
                <div class="metric-value"><?php echo $winRate; ?>%</div>
                <div class="metric-sub neutral">Based on <?php echo ($wonDealsCount + $lostDealsCount); ?> closed deals</div>
            </div>
            <div class="metric-card c-amber">
                <div class="metric-title">Task Completion <i class="fa-solid fa-check-double" style="color:#f59e0b;"></i></div>
                <div class="metric-value"><?php echo $taskCompletionRate; ?>%</div>
                <div class="metric-sub neutral"><?php echo $completedTasksCount; ?> / <?php echo $totalTasksCount; ?> tasks done</div>
            </div>
            <div class="metric-card c-violet">
                <div class="metric-title">Total Entities <i class="fa-solid fa-database" style="color:#8b5cf6;"></i></div>
                <div class="metric-value"><?php echo ($totalCompaniesCount + $totalUsersCount + $totalContacts); ?></div>
                <div class="metric-sub neutral"><?php echo $totalCompaniesCount; ?> Co · <?php echo $totalUsersCount; ?> Users · <?php echo $totalContacts; ?> Contacts</div>
            </div>
        </div>

        <!-- ── ROW 2: 3 Secondary Cards ── -->
        <div class="metrics-grid-3">
            <div class="metric-card c-rose">
                <div class="metric-title">Active Deals <i class="fa-solid fa-handshake" style="color:#f43f5e;"></i></div>
                <div class="metric-value"><?php echo $activeDealsCount; ?></div>
                <div class="metric-sub warning"><?php echo $lostDealsCount; ?> deal<?php echo $lostDealsCount != 1 ? 's' : ''; ?> lost</div>
            </div>
            <div class="metric-card c-cyan">
                <div class="metric-title">Campaigns <i class="fa-solid fa-bullhorn" style="color:#06b6d4;"></i></div>
                <div class="metric-value"><?php echo $totalCampaigns; ?></div>
                <div class="metric-sub positive"><?php echo $activeCampaigns; ?> currently active</div>
            </div>
            <div class="metric-card c-green">
                <div class="metric-title">Active Users <i class="fa-solid fa-user-group" style="color:#10b981;"></i></div>
                <div class="metric-value"><?php echo $totalUsersCount; ?></div>
                <div class="metric-sub neutral">Across all roles</div>
            </div>
        </div>

        <!-- ── ROW 3: Deal Table + Task Progress ── -->
        <p class="section-label">Deals &amp; Tasks</p>
        <div class="panels-grid">

            <!-- Recent Deals -->
            <div class="dash-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-handshake" style="color:#3b82f6;"></i> Recent Deal Activities</div>
                    <a href="deal_pipeline.php" class="panel-link">View Pipeline &rarr;</a>
                </div>
                <table class="simple-table">
                    <thead>
                        <tr>
                            <th>Deal Name</th>
                            <th>Date</th>
                            <th>Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDeals as $deal):
                            $dStage  = htmlspecialchars($deal['stage']);
                            $pillCls = strtolower($dStage);
                        ?>
                        <tr>
                            <td style="font-weight:700;"><?php echo htmlspecialchars($deal['deal_name']); ?></td>
                            <td style="color:#6b7280; font-size:12px;"><?php echo date('M d, Y', strtotime($deal['created_at'])); ?></td>
                            <td style="font-weight:700;">$<?php echo number_format((float)$deal['deal_value'], 0); ?></td>
                            <td><span class="pill <?php echo $pillCls; ?>"><?php echo $dStage; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Task Status Progress -->
            <div class="dash-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-list-check" style="color:#f59e0b;"></i> Global Task Status</div>
                    <a href="task_manager.php" class="panel-link">View All &rarr;</a>
                </div>
                <?php
                    $tTotal = max(array_sum($taskStatusData), 1);
                    $pDone  = round(($taskStatusData['done']     / $tTotal) * 100);
                    $pProg  = round(($taskStatusData['progress'] / $tTotal) * 100);
                    $pTodo  = round(($taskStatusData['todo']     / $tTotal) * 100);
                    $pOver  = round(($taskStatusData['overdue']  / $tTotal) * 100);
                ?>
                <div class="progress-item">
                    <div class="progress-label"><span>Completed</span><span><?php echo $taskStatusData['done']; ?> <span class="pct">(<?php echo $pDone; ?>%)</span></span></div>
                    <div class="progress-track"><div class="progress-fill fill-done" style="width:<?php echo $pDone; ?>%;"></div></div>
                </div>
                <div class="progress-item">
                    <div class="progress-label"><span>In Progress</span><span><?php echo $taskStatusData['progress']; ?> <span class="pct">(<?php echo $pProg; ?>%)</span></span></div>
                    <div class="progress-track"><div class="progress-fill fill-prog" style="width:<?php echo $pProg; ?>%;"></div></div>
                </div>
                <div class="progress-item">
                    <div class="progress-label"><span>To-Do</span><span><?php echo $taskStatusData['todo']; ?> <span class="pct">(<?php echo $pTodo; ?>%)</span></span></div>
                    <div class="progress-track"><div class="progress-fill fill-todo" style="width:<?php echo $pTodo; ?>%;"></div></div>
                </div>
                <div class="progress-item">
                    <div class="progress-label"><span>Overdue</span><span><?php echo $taskStatusData['overdue']; ?> <span class="pct">(<?php echo $pOver; ?>%)</span></span></div>
                    <div class="progress-track"><div class="progress-fill fill-over" style="width:<?php echo $pOver; ?>%;"></div></div>
                </div>
            </div>
        </div>

        <!-- ── ROW 4: Deals by Stage (bar) + Users by Role + Campaigns ── -->
        <p class="section-label">Breakdown &amp; Campaigns</p>
        <div class="panels-grid-3">

            <!-- Deals by Stage Bar Chart -->
            <div class="dash-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-chart-bar" style="color:#3b82f6;"></i> Deals by Stage</div>
                </div>
                <?php
                $stageColors = ['Lead'=>'#9ca3af','Proposal'=>'#3b82f6','Negotiation'=>'#f59e0b','Won'=>'#10b981','Lost'=>'#ef4444'];
                $maxStage    = max(array_values($dealsByStage) ?: [1]);
                ?>
                <div class="bar-chart" id="barDeals">
                    <?php foreach ($dealsByStage as $s => $cnt):
                        $h   = round(($cnt / $maxStage) * 90);
                        $clr = $stageColors[$s] ?? '#3b82f6';
                    ?>
                    <div class="bar-wrap">
                        <span class="bar-count"><?php echo $cnt; ?></span>
                        <div class="bar" style="height:<?php echo $h; ?>px; background:<?php echo $clr; ?>;"></div>
                        <span class="bar-label"><?php echo $s; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Users by Role -->
            <div class="dash-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-users-gear" style="color:#8b5cf6;"></i> Users by Role</div>
                    <a href="user_list.php" class="panel-link">Manage &rarr;</a>
                </div>
                <?php
                $roleIcons  = ['Super_admin'=>'fa-crown','Admin'=>'fa-user-shield','Manager'=>'fa-user-tie','Agent'=>'fa-headset'];
                $roleColors = ['Super_admin'=>'#f59e0b','Admin'=>'#3b82f6','Manager'=>'#8b5cf6','Agent'=>'#10b981'];
                foreach ($usersByRole as $role => $cnt):
                    $ic  = $roleIcons[$role]  ?? 'fa-user';
                    $clr = $roleColors[$role] ?? '#6b7280';
                ?>
                <div class="stat-row">
                    <div class="stat-row-label">
                        <i class="fa-solid <?php echo $ic; ?>" style="color:<?php echo $clr; ?>; width:16px; text-align:center;"></i>
                        <?php echo str_replace('_', ' ', $role); ?>
                    </div>
                    <div class="stat-row-val"><?php echo $cnt; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Campaigns -->
            <div class="dash-panel">
                <div class="panel-header">
                    <div class="panel-title"><i class="fa-solid fa-bullhorn" style="color:#06b6d4;"></i> Recent Campaigns</div>
                    <a href="campaigns.php" class="panel-link">View All &rarr;</a>
                </div>
                <?php if (empty($recentCampaigns)): ?>
                    <p style="font-size:13px; color:#9ca3af; text-align:center; margin-top:20px;">No campaigns yet.</p>
                <?php else: ?>
                    <table class="simple-table">
                        <thead><tr><th>Name</th><th>Type</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentCampaigns as $camp):
                                $cStatus = strtolower(str_replace(' ', '-', $camp['status']));
                            ?>
                            <tr>
                                <td style="font-weight:700;"><?php echo htmlspecialchars($camp['campaign_name']); ?></td>
                                <td style="color:#6b7280; font-size:12px;"><?php echo htmlspecialchars($camp['campaign_type']); ?></td>
                                <td><span class="pill <?php echo $cStatus; ?>"><?php echo htmlspecialchars($camp['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /analytics-container -->
</div><!-- /main-content -->

<script>
/* Dark mode & sidebar toggle are handled by sidebar.php's built-in script */

/* ════════════════════════════════════════════════════════════════
   EXPORT FULL REPORT — generates a self-contained HTML file
   and triggers a browser download (no server-side library needed)
═══════════════════════════════════════════════════════════════════ */
function exportFullReport() {
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { year:'numeric', month:'long', day:'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });

    /* ── Collect live data from the page ── */
    const metrics = [];
    document.querySelectorAll('.metric-card').forEach(card => {
        const title = card.querySelector('.metric-title')?.firstChild?.textContent?.trim() ?? '';
        const value = card.querySelector('.metric-value')?.textContent?.trim() ?? '';
        const sub   = card.querySelector('.metric-sub')?.textContent?.trim() ?? '';
        metrics.push({ title, value, sub });
    });

    /* Deal rows */
    let dealRows = '';
    document.querySelectorAll('.simple-table tbody tr').forEach(tr => {
        const cells = tr.querySelectorAll('td');
        if (cells.length >= 4) {
            dealRows += `<tr>
                <td>${cells[0].textContent.trim()}</td>
                <td>${cells[1].textContent.trim()}</td>
                <td>${cells[2].textContent.trim()}</td>
                <td>${cells[3].textContent.trim()}</td>
            </tr>`;
        }
    });

    /* Progress data */
    let progressRows = '';
    document.querySelectorAll('.progress-item').forEach(item => {
        const labels = item.querySelectorAll('.progress-label span');
        const fill   = item.querySelector('.progress-fill');
        if (labels.length >= 2 && fill) {
            const pct   = fill.style.width;
            const bg    = fill.style.backgroundColor || fill.className.includes('done') ? '#10b981'
                        : fill.className.includes('prog') ? '#3b82f6'
                        : fill.className.includes('over') ? '#ef4444' : '#9ca3af';
            progressRows += `<tr>
                <td>${labels[0].textContent.trim()}</td>
                <td>${labels[1].textContent.trim()}</td>
                <td><div style="width:120px;height:7px;background:#e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="width:${pct};height:100%;background:${bg};border-radius:8px;"></div></div></td>
            </tr>`;
        }
    });

    /* Metric card HTML for report */
    const metricHTML = metrics.map(m => `
        <div class="r-card">
            <div class="r-card-title">${m.title}</div>
            <div class="r-card-value">${m.value}</div>
            <div class="r-card-sub">${m.sub}</div>
        </div>`).join('');

    /* Build full HTML report */
    const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Analytics Report — Systellio CRM — ${dateStr}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
  * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
  body { background:#f3f4f6; color:#111827; padding:40px; }

  /* Cover */
  .cover { text-align:center; padding:60px 40px; background:#fff; border-radius:14px; margin-bottom:36px; border:1px solid #e5e7eb; }
  .cover h1 { font-size:28px; font-weight:800; color:#111827; margin-bottom:6px; }
  .cover p  { font-size:14px; color:#6b7280; }
  .cover .badge { display:inline-block; margin-top:18px; background:#10b981; color:#fff; padding:6px 18px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:.5px; }

  /* Sections */
  h2 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:1.2px; color:#9ca3af; margin-bottom:14px; margin-top:36px; }

  /* Metric cards */
  .r-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:10px; }
  .r-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:10px; }
  .r-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:18px 16px; }
  .r-card-title { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin-bottom:8px; }
  .r-card-value { font-size:26px; font-weight:800; color:#111827; margin-bottom:4px; }
  .r-card-sub   { font-size:11px; color:#6b7280; font-weight:600; }

  /* Tables */
  .r-table-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:20px; margin-bottom:24px; }
  .r-table-wrap h3 { font-size:14px; font-weight:700; color:#111827; margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #f3f4f6; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th { padding:8px 10px; text-align:left; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#6b7280; border-bottom:2px solid #f3f4f6; }
  td { padding:11px 10px; border-bottom:1px solid #f9fafb; color:#374151; font-weight:500; }
  tr:last-child td { border-bottom:none; }

  /* Footer */
  footer { text-align:center; font-size:11px; color:#9ca3af; margin-top:40px; padding-top:24px; border-top:1px solid #e5e7eb; }

  @media print {
    body { background:#fff; padding:20px; }
    .cover { box-shadow:none; }
    @page { margin:1cm; }
  }
</style>
</head>
<body>

<div class="cover">
  <h1>📊 Analytics &amp; Reports</h1>
  <p>Systellio CRM · Generated on ${dateStr} at ${timeStr}</p>
  <span class="badge">Full Report Export</span>
</div>

<h2>Key Metrics</h2>
<div class="r-grid">${metricHTML.split('</div>').slice(0,4).join('</div>')}
</div>
<div class="r-grid-3">${metricHTML.split('</div>').slice(4).join('</div>')}
</div>

<div class="r-table-wrap">
  <h3>📌 Recent Deal Activities</h3>
  <table>
    <thead><tr><th>Deal Name</th><th>Date</th><th>Value</th><th>Status</th></tr></thead>
    <tbody>${dealRows || '<tr><td colspan="4" style="text-align:center;color:#9ca3af;">No deals found</td></tr>'}</tbody>
  </table>
</div>

<div class="r-table-wrap">
  <h3>✅ Task Status Breakdown</h3>
  <table>
    <thead><tr><th>Status</th><th>Count</th><th>Progress</th></tr></thead>
    <tbody>${progressRows || '<tr><td colspan="3" style="text-align:center;color:#9ca3af;">No task data</td></tr>'}</tbody>
  </table>
</div>

<footer>
  Systellio CRM · Confidential · Exported ${dateStr} at ${timeStr}
</footer>

</body>
</html>`;

    /* Trigger download */
    const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `systellio-analytics-${now.toISOString().slice(0,10)}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>