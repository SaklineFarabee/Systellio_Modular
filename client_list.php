<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================
if (session_status() === PHP_SESSION_NONE) session_start();
@include 'config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}

$toastMessage = "";
$toastType = "";

// ========================================================================
// 2. CLIENT LOGIC (CREATE, DELETE)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload_clients'])) {
    if(isset($conn)){
        $rows = json_decode($_POST['bulk_rows'] ?? '[]', true);
        $inserted = 0; $skipped = 0;
        if(is_array($rows)){
            // Ensure assigned_agents column exists
            $_cols=[]; $_cr=mysqli_query($conn,"SHOW COLUMNS FROM contacts");
            if($_cr){while($_c=mysqli_fetch_assoc($_cr))$_cols[]=$_c['Field'];}
            if(!in_array('assigned_agents',$_cols)) mysqli_query($conn,"ALTER TABLE contacts ADD COLUMN assigned_agents TEXT DEFAULT NULL");

            foreach($rows as $row){
                $n = trim($row['name'] ?? '');
                if(empty($n)){ $skipped++; continue; }
                $n  = mysqli_real_escape_string($conn, $n);
                $e  = mysqli_real_escape_string($conn, trim($row['email'] ?? ''));
                $p  = mysqli_real_escape_string($conn, trim($row['phone'] ?? ''));
                $d  = mysqli_real_escape_string($conn, trim($row['designation'] ?? ''));
                $c  = mysqli_real_escape_string($conn, trim($row['company'] ?? ''));
                // resolve company name → id
                $cid = 'NULL';
                if(!empty($c)){
                    $cr = mysqli_query($conn,"SELECT id FROM companies WHERE company_name='$c' LIMIT 1");
                    if($cr && mysqli_num_rows($cr)>0){ $cid = mysqli_fetch_assoc($cr)['id']; }
                }
                $ok = mysqli_query($conn,"INSERT INTO contacts (name,email,phone,designation,company_id,assigned_agents) VALUES ('$n','$e','$p','$d',$cid,NULL)");
                $ok ? $inserted++ : $skipped++;
            }
        }
        $toastMessage = "$inserted client(s) uploaded successfully!" . ($skipped ? " ($skipped skipped)" : "");
        $toastType = "success";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_client'])) {
    if(isset($conn)){
        $client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
        $client_email = mysqli_real_escape_string($conn, $_POST['client_email'] ?? '');
        $client_phone = mysqli_real_escape_string($conn, $_POST['client_phone'] ?? '');
        $client_designation = mysqli_real_escape_string($conn, $_POST['client_designation'] ?? '');
        
        $company_id = $_POST['company_id'] ?? '';
        $comp_insert_val = !empty($company_id) ? "'".mysqli_real_escape_string($conn, $company_id)."'" : "NULL";

        $assigned_agents_raw = $_POST['assigned_agents'] ?? [];
        $assigned_agents_val = !empty($assigned_agents_raw) ? "'".mysqli_real_escape_string($conn, implode(',', $assigned_agents_raw))."'": "NULL";

        // Ensure column exists
        $_cols2=[]; $_cr2=mysqli_query($conn,"SHOW COLUMNS FROM contacts");
        if($_cr2){while($_c2=mysqli_fetch_assoc($_cr2))$_cols2[]=$_c2['Field'];}
        if(!in_array('assigned_agents',$_cols2)) mysqli_query($conn,"ALTER TABLE contacts ADD COLUMN assigned_agents TEXT DEFAULT NULL");

        $insert_client_sql = "INSERT INTO contacts (name, email, phone, designation, company_id, assigned_agents) VALUES ('$client_name', '$client_email', '$client_phone', '$client_designation', $comp_insert_val, $assigned_agents_val)";
        try {
            if(mysqli_query($conn, $insert_client_sql)){
                $toastMessage = "Client added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Create 'contacts' table.";
            $toastType = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_client'])) {
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_client_id'] ?? '');
        $delete_sql = "DELETE FROM contacts WHERE id='$del_id'";
        if(mysqli_query($conn, $delete_sql)){
            $toastMessage = "Client deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting client!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 3. FETCH DATA FOR UI
// ========================================================================
$companyOptions = "";
if(isset($conn)){
    try {
        $comp_drp_query = mysqli_query($conn, "SELECT id, company_name FROM companies ORDER BY company_name ASC");
        if($comp_drp_query && mysqli_num_rows($comp_drp_query) > 0){
            while($cRow = mysqli_fetch_assoc($comp_drp_query)){
                $companyOptions .= "<option value='{$cRow['id']}'>{$cRow['company_name']}</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}
}

// Ensure assigned_agents column exists in contacts
if(isset($conn)){
    $cols = []; $cr = mysqli_query($conn, "SHOW COLUMNS FROM contacts");
    if($cr){ while($c = mysqli_fetch_assoc($cr)) $cols[] = $c['Field']; }
    if(!in_array('assigned_agents', $cols)){
        mysqli_query($conn, "ALTER TABLE contacts ADD COLUMN assigned_agents TEXT DEFAULT NULL");
    }
}

// Fetch agents for dropdown
$agentOptions = "";
if(isset($conn)){
    try {
        $ag_query = mysqli_query($conn, "SELECT username, name FROM users WHERE role IN ('agent','manager','admin') AND status='active' ORDER BY name ASC");
        if($ag_query && mysqli_num_rows($ag_query) > 0){
            while($aRow = mysqli_fetch_assoc($ag_query)){
                $agentOptions .= "<option value='{$aRow['username']}'>{$aRow['name']} ({$aRow['username']})</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}
}

$hasClients = false;
$clientTableRows = "";
$totalClients = "0";

if(isset($conn)){
    try {
        $client_query_str = "
            SELECT contacts.*, companies.company_name 
            FROM contacts 
            LEFT JOIN companies ON contacts.company_id = companies.id 
            ORDER BY contacts.id DESC
        ";
        $client_query = mysqli_query($conn, $client_query_str);
        if($client_query && mysqli_num_rows($client_query) > 0){
            $hasClients = true;
            $totalClients = mysqli_num_rows($client_query);
            
            while($row = mysqli_fetch_assoc($client_query)){
                $cl_name        = htmlspecialchars($row['name']);
                $cl_email       = htmlspecialchars($row['email'] ?? '');
                $cl_phone       = htmlspecialchars($row['phone'] ?? '');
                $cl_designation = htmlspecialchars($row['designation'] ?? '');
                $cl_company     = htmlspecialchars($row['company_name'] ?? 'N/A');
                $cl_agents_raw  = $row['assigned_agents'] ?? '';
                $cl_date        = !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—';
                $cl_id          = $row['id'];

                // Agent badges
                $agent_badges = '';
                if (!empty($cl_agents_raw)) {
                    foreach (explode(',', $cl_agents_raw) as $ag) {
                        $ag = trim($ag);
                        if ($ag) $agent_badges .= "<span class='agent-badge'>{$ag}</span>";
                    }
                } else {
                    $agent_badges = "<span style='color:#9ca3af;font-size:11px;'>—</span>";
                }

                $email_html = !empty($cl_email) ? "<a href='mailto:{$cl_email}' style='color:#3b82f6;text-decoration:none;'>{$cl_email}</a>" : "<span style='color:#9ca3af;'>—</span>";
                $phone_html = !empty($cl_phone) ? "<a href='tel:{$cl_phone}' style='color:#374151;text-decoration:none;'>{$cl_phone}</a>" : "<span style='color:#9ca3af;'>—</span>";
                $desig_html = !empty($cl_designation) ? "<span class='desig-badge'>{$cl_designation}</span>" : "<span style='color:#9ca3af;'>—</span>";

                $clientTableRows .= "<tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><b>{$cl_name}</b></td>
                    <td>{$email_html}</td>
                    <td>{$phone_html}</td>
                    <td>{$desig_html}</td>
                    <td><span class='comp-contacts-pill'>{$cl_company}</span></td>
                    <td><div class='agent-badges-wrap'>{$agent_badges}</div></td>
                    <td style='color:#6b7280;font-size:11px;'><i class='fa-regular fa-calendar' style='margin-right:4px;'></i>{$cl_date}</td>
                    <td>
                        <div class='action-btns'>
                            <a href='client_profile.php?id={$cl_id}' class='btn-view' title='View Profile' style='display:inline-flex;align-items:center;justify-content:center;'><i class='fa-regular fa-eye'></i></a>
                            <form method='POST' id='delete-client-{$cl_id}' style='display:inline;'>
                                <input type='hidden' name='delete_client_id' value='{$cl_id}'>
                                <input type='hidden' name='delete_client' value='1'>
                                <button type='button' class='btn-delete' onclick='confirmDelete(\"delete-client-{$cl_id}\", \"client\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                            </form>
                        </div>
                    </td>
                </tr>";
            }
        }
    } catch(mysqli_sql_exception $e) {}
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts & Clients - Systellio CRM</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; height: 100vh; overflow: hidden; transition: background-color 0.3s, color 0.3s; color: #111827; }
        
        #toastBox { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; top: 30px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55), visibility 0.4s; }
        #toastBox.show { visibility: visible; transform: translateX(0); }
        #toastBox.success { background-color: #10b981; }
        #toastBox.error { background-color: #ef4444; }

        /* Sidebar CSS → see sidebar.php */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; transition: background-color 0.3s ease; background-color: #f3f4f6; }
        
        
        
        
        .nav-icon-btn:hover { color: #3b82f6; }
        .notification-badge { position: absolute; top: -4px; right: -4px; background-color: #ef4444; color: white; font-size: 9px; font-weight: bold; padding: 2px 5px; border-radius: 50%; border: 2px solid #ffffff; }
        
        .user-profile i { font-size: 24px; color: #3b82f6; }

        .company-container { padding: 30px; display: block; }
        .comp-header-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s; color: #111827;}
        .comp-header-title p { font-size: 13px; color: #6b7280; font-weight: 500; }

        .user-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-buttons { display: flex; gap: 10px; }
        .btn-add-client {
            background-color: #0f172a;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-add-client:hover { background-color: #1e293b; transform: translateY(-1px); }
        .btn-add-client i { font-size: 13px; }
        .btn-export {
            background-color: #16a34a; color: #ffffff; padding: 10px 18px;
            border-radius: 6px; font-size: 13px; font-weight: 700; border: none;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12); transition: background-color 0.2s, transform 0.1s;
        }
        .btn-export:hover { background-color: #15803d; transform: translateY(-1px); }
        .btn-bulk {
            background-color: #1e293b; color: #ffffff; padding: 10px 18px;
            border-radius: 6px; font-size: 13px; font-weight: 700; border: none;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12); transition: background-color 0.2s, transform 0.1s;
        }
        .btn-bulk:hover { background-color: #334155; transform: translateY(-1px); }

        .comp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .comp-search { position: relative; width: 300px; }
        .comp-search i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;}
        .comp-search input { width: 100%; padding: 10px 15px 10px 38px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: 0.3s; color: #374151;}
        .comp-total { font-size: 13px; font-weight: 600; color: #4b5563; background: #ffffff; border: 1px solid #d1d5db; padding: 8px 15px; border-radius: 20px;}

        .table-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db; transition: 0.3s; background: #ffffff;}
        .custom-table { width: 100%; border-collapse: collapse; text-align: center; font-size: 12px; }
        .custom-table th { background-color: #c4f042; padding: 14px 10px; font-weight: 700; color: #000000; border-bottom: 1px solid #d1d5db; transition: 0.3s;}
        .custom-table td { padding: 14px 10px; color: #374151; font-weight: 500; vertical-align: middle; border-right: 1px solid rgba(0,0,0,0.05); transition: 0.3s;}
        .custom-table tbody tr:nth-child(4n+1) { background-color: #e6fced; } 
        .custom-table tbody tr:nth-child(4n+2) { background-color: #fcedf6; } 
        .custom-table tbody tr:nth-child(4n+3) { background-color: #fceddb; } 
        .custom-table tbody tr:nth-child(4n+4) { background-color: #e6edff; } 

        .comp-contacts-pill { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; display: inline-block;}
        .tbl-checkbox { width: 16px; height: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; accent-color: #3b82f6;}
        .agent-badge { display: inline-block; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 20px; margin: 2px; }
        .agent-badges-wrap { display: flex; flex-wrap: wrap; justify-content: center; gap: 2px; }
        .desig-badge { display: inline-block; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .action-btns { display: flex; justify-content: center; gap: 6px; }
        .btn-view { background-color: #60a5fa; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-delete { background-color: #f87171; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}

        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 20px 22px; border-radius: 10px; width: 100%; max-width: 500px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);}
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .modal-header h2 { font-size: 15px; font-weight: 700; color: #111827; }
        .close-btn { font-size: 17px; cursor: pointer; color: #6b7280; border: none; background: none; transition: 0.2s; }
        .close-btn:hover { color: #ef4444; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-group { margin-bottom: 0; }
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: #374151; margin-bottom: 4px; }
        .form-group input, .form-group select { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; outline: none; background-color: #f9fafb; transition: 0.2s; color: #111827; font-family: 'Inter', sans-serif; }
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; background-color: #fff; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
        .form-group input::placeholder { color: #9ca3af; }
        .submit-btn { background-color: #0f172a; color: #ffffff; padding: 10px; border: none; border-radius: 6px; width: 100%; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-top: 12px; }
        .submit-btn:hover { background-color: #1e293b; }

        body.dark-mode { background-color: #0f172a; color: #f8fafc; }
        body.dark-mode .main-content { background-color: #0f172a; }
        body.dark-mode 
        body.dark-mode 
        body.dark-mode .comp-header-title h1 { color: #f8fafc; }
        body.dark-mode .table-wrapper { border-color: #334155; background: #1e293b; }
        body.dark-mode .custom-table th { background-color: #334155; color: #f8fafc; border-color: #475569; }
        body.dark-mode .custom-table td { color: #cbd5e1; border-color: #334155; }
        body.dark-mode .custom-table tbody tr:nth-child(even) { background-color: #1e293b; } 
        body.dark-mode .custom-table tbody tr:nth-child(odd) { background-color: #0f172a; } 
        body.dark-mode .custom-table tbody tr:hover { background-color: #334155; }
        body.dark-mode .comp-search input { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .comp-total { background-color: #0f172a; color: #cbd5e1; border-color: #334155; }
        body.dark-mode .modal-content { background-color: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155;}
        body.dark-mode .form-group input, body.dark-mode .form-group select { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .form-group input:focus, body.dark-mode .form-group select:focus { border-color: #3b82f6; background-color: #1e293b; }
        
        .form-group select[multiple] { padding: 6px 4px; cursor: pointer; }
        .form-group select[multiple] option { padding: 7px 10px; border-radius: 4px; margin-bottom: 2px; }
        .form-group select[multiple] option:checked { background: #3b82f6 linear-gradient(0deg,#3b82f6 0%,#3b82f6 100%); color:#fff; }
        body.dark-mode .form-group select[multiple] option { color:#f8fafc; }
        .swal2-container { z-index: 9999 !important; }
        body.dark-mode .swal2-popup { background-color: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        body.dark-mode .swal2-title, body.dark-mode .swal2-html-container { color: #f8fafc; }
    </style>
</head>
<body>

    <div id="toastBox"><i id="toastIcon" class="fa-solid fa-circle-check"></i><span id="toastMsg">Action Successful!</span></div>

        <?php $activePage = 'client_list'; include_once 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'topbar.php'; ?>

        <div class="company-container">
            <div class="user-list-header">
                <div class="comp-header-title">
                    <h1>Accounts & Clients</h1>
                    <p>Manage all individual contacts and clients here.</p>
                </div>
                <div class="header-buttons">
                    <button class="btn-export" onclick="exportCSV()"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                    <button class="btn-bulk" onclick="openModal('bulkUploadModal')"><i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload</button>
                    <button class="btn-add-client" onclick="openModal('addClientModal')"><i class="fa-solid fa-user-plus"></i> Add Client</button>
                </div>
            </div>

            <div class="comp-toolbar">
                <div class="comp-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search client...">
                </div>
                <div class="comp-total">Total Clients: <?php echo (isset($hasClients) && $hasClients) ? $totalClients : "0"; ?></div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="tbl-checkbox" title="Select All"></th>
                            <th>Client Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Associated Company</th>
                            <th>Assigned Agents</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasClients) && $hasClients): ?>
                            <?php echo $clientTableRows; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="padding: 20px; text-align: center; color: #6b7280;">No clients found. Click "Add Client" to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client</h2>
                <button type="button" class="close-btn" onclick="closeModal('addClientModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="client_list.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Client Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="client_name" required placeholder="e.g. Jane Doe">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="client_email" placeholder="jane@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="client_phone" placeholder="+1 234 567 8900">
                    </div>
                    <div class="form-group">
                        <label>Designation / Title</label>
                        <input type="text" name="client_designation" placeholder="e.g. Marketing Director">
                    </div>
                    <div class="form-group full-width">
                        <label>Associated Company</label>
                        <select name="company_id">
                            <option value="" selected>No Company (Independent Client)</option>
                            <?php echo $companyOptions; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Assign to Agent <span style="font-size:10px;font-weight:500;color:#9ca3af;">(Ctrl/Cmd = multiple)</span></label>
                        <select name="assigned_agents[]" multiple style="height:72px;">
                            <?php echo $agentOptions; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_client" class="submit-btn">Save Client</button>
            </form>
        </div>
    </div>

    <!-- BULK UPLOAD MODAL -->
    <div id="bulkUploadModal" class="modal">
        <div class="modal-content" style="max-width:560px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-cloud-arrow-up" style="color:#1e293b;margin-right:8px;"></i>Bulk Upload Clients</h2>
                <button type="button" class="close-btn" onclick="closeModal('bulkUploadModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <!-- Template download -->
            <div style="background:#f0fdf4;border:1px dashed #86efac;border-radius:8px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <p style="font-size:13px;font-weight:700;color:#15803d;margin-bottom:2px;"><i class="fa-solid fa-file-csv" style="margin-right:6px;"></i>Need a template?</p>
                    <p style="font-size:11px;color:#6b7280;">Download, fill in and upload the CSV below.</p>
                </div>
                <button onclick="downloadTemplate()" style="padding:8px 14px;background:#0f172a;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fa-solid fa-download"></i> Download Template
                </button>
            </div>

            <!-- File input -->
            <div class="form-group" style="margin-bottom:12px;">
                <label>Select CSV File</label>
                <input type="file" id="bulkFileInput" accept=".csv" style="background:#f9fafb;padding:8px 10px;">
            </div>

            <!-- Preview -->
            <div id="bulkPreview" style="max-height:200px;overflow-y:auto;margin-bottom:12px;border-radius:6px;font-size:12px;"></div>

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="closeModal('bulkUploadModal')" style="padding:9px 18px;border:1px solid #d1d5db;background:#fff;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;color:#374151;">Cancel</button>
                <button id="bulkSubmitBtn" style="display:none;padding:9px 18px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;align-items:center;gap:6px;">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Clients
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) { document.getElementById(id).style.display = "flex"; }
        function closeModal(id) {
            document.getElementById(id).style.display = "none";
            if(id === 'bulkUploadModal'){
                document.getElementById('bulkFileInput').value = '';
                document.getElementById('bulkPreview').innerHTML = '';
                document.getElementById('bulkSubmitBtn').style.display = 'none';
                window._bulkAllRows = [];
            }
        }
        window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = "none"; }

        function showToast(message, type) {
            const toast = document.getElementById("toastBox");
            document.getElementById("toastMsg").innerText = message;
            toast.className = "show " + type;
            document.getElementById("toastIcon").className = (type === 'success') ? "fa-solid fa-circle-check" : "fa-solid fa-circle-xmark";
            setTimeout(() => toast.className = toast.className.replace("show", ""), 3000);
        }

        window.onload = function() {
            <?php if($toastMessage != ""): ?> showToast("<?php echo $toastMessage; ?>", "<?php echo $toastType; ?>"); <?php endif; ?>
        };

        function confirmDelete(formId, typeName) {
            const isDark = document.body.classList.contains('dark-mode');
            Swal.fire({
                title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280', confirmButtonText: 'Yes, delete it!',
                background: isDark ? '#1e293b' : '#fff', color: isDark ? '#f8fafc' : '#111827'
            }).then((result) => { if (result.isConfirmed) { document.getElementById(formId).submit(); } });
        }

        /* ── Search ── */
        document.querySelector('.comp-search input').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('.custom-table tbody tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });

        /* ── Select All checkbox ── */
        document.querySelector('thead .tbl-checkbox').addEventListener('change', function() {
            document.querySelectorAll('tbody .tbl-checkbox').forEach(cb => cb.checked = this.checked);
        });

        /* ── Export CSV ── */
        function exportCSV() {
            const rows = [['Client Name','Email','Phone','Designation','Associated Company','Assigned Agents','Date Added']];
            document.querySelectorAll('.custom-table tbody tr').forEach(tr => {
                if (tr.style.display === 'none') return;
                const tds = tr.querySelectorAll('td');
                if (tds.length < 8) return;
                rows.push([
                    tds[1]?.textContent.trim(),
                    tds[2]?.textContent.trim(),
                    tds[3]?.textContent.trim(),
                    tds[4]?.textContent.trim(),
                    tds[5]?.textContent.trim(),
                    tds[6]?.textContent.trim(),
                    tds[7]?.textContent.trim(),
                ].map(v => `"${(v||'').replace(/"/g,'""')}"`));
            });
            const blob = new Blob(['\uFEFF' + rows.map(r => r.join(',')).join('\r\n')], {type:'text/csv;charset=utf-8;'});
            const a = Object.assign(document.createElement('a'), {
                href: URL.createObjectURL(blob), download: 'clients_export.csv'
            });
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            showToast('CSV exported successfully!', 'success');
        }

        /* ── Download Template ── */
        function downloadTemplate() {
            const csv = 'name,email,phone,designation,company\nJohn Doe,john@example.com,01700000000,Manager,courseplus\nJane Smith,jane@example.com,01800000000,Developer,Peersolution';
            const a = Object.assign(document.createElement('a'), {
                href: URL.createObjectURL(new Blob([csv], {type:'text/csv'})),
                download: 'client_upload_template.csv'
            });
            a.click();
        }

        /* ── Bulk Upload — CSV parse & preview ── */
        document.getElementById('bulkFileInput').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const lines = e.target.result.trim().split('\n');
                const headers = lines[0].split(',').map(h => h.trim().replace(/^"|"$/g,''));
                window._bulkHeaders  = headers;
                window._bulkAllRows  = [];
                for (let i = 1; i < lines.length; i++) {
                    const cols = lines[i].split(',').map(c => c.trim().replace(/^"|"$/g,''));
                    if (cols.some(c => c)) window._bulkAllRows.push(cols);
                }
                /* Preview table (max 5 rows) */
                let html = `<table style="width:100%;border-collapse:collapse;">
                    <thead><tr>${headers.map(h=>`<th style="padding:6px 10px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:11px;font-weight:700;text-align:left;">${h}</th>`).join('')}</tr></thead><tbody>`;
                const previewRows = window._bulkAllRows.slice(0, 5);
                previewRows.forEach(cols => {
                    html += '<tr>' + cols.map(c=>`<td style="padding:6px 10px;border:1px solid #e5e7eb;font-size:11px;">${c}</td>`).join('') + '</tr>';
                });
                if (window._bulkAllRows.length > 5) {
                    html += `<tr><td colspan="${headers.length}" style="padding:6px 10px;font-size:11px;color:#6b7280;font-style:italic;border:1px solid #e5e7eb;">... and ${window._bulkAllRows.length - 5} more rows</td></tr>`;
                }
                html += '</tbody></table>';
                document.getElementById('bulkPreview').innerHTML = html;
                const btn = document.getElementById('bulkSubmitBtn');
                btn.style.display = 'inline-flex';
            };
            reader.readAsText(file);
        });

        /* ── Bulk Upload — Submit ── */
        document.getElementById('bulkSubmitBtn').addEventListener('click', function() {
            if (!window._bulkAllRows || !window._bulkAllRows.length) return;
            const headers = window._bulkHeaders;
            const idx = (names) => { for(const n of names){ const i=headers.findIndex(h=>h.toLowerCase()===n); if(i>-1)return i; } return -1; };
            const ni = idx(['name','full name','client name','contact name']);
            if (ni === -1) { showToast('CSV must have a "name" column.', 'error'); return; }
            const ei = idx(['email','e-mail']);
            const pi = idx(['phone','mobile','number']);
            const di = idx(['designation','role','title','position']);
            const ci = idx(['company','company name','associated company']);

            const mapped = window._bulkAllRows.map(row => ({
                name:        row[ni]  ?? '',
                email:       ei>-1 ? (row[ei]  ?? '') : '',
                phone:       pi>-1 ? (row[pi]  ?? '') : '',
                designation: di>-1 ? (row[di]  ?? '') : '',
                company:     ci>-1 ? (row[ci]  ?? '') : '',
            }));

            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';

            const form = document.createElement('form');
            form.method = 'POST'; form.action = 'client_list.php';
            const addH = (n,v) => { const i=document.createElement('input'); i.type='hidden'; i.name=n; i.value=v; form.appendChild(i); };
            addH('bulk_upload_clients', '1');
            addH('bulk_rows', JSON.stringify(mapped));
            document.body.appendChild(form);
            form.submit();
        });
    </script>
</body>
</html>