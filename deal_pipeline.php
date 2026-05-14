<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================

session_start();
@include 'config.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    // header("Location: login.php");
    // exit();
}

$toastMessage = "";
$toastType = "";

// ========================================================================
// 2. DEAL PIPELINE LOGIC (CREATE & DELETE)
// ========================================================================

// A. Create Deal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_deal'])) {
    if(isset($conn)){
        // Map new form fields to database columns
        $deal_name = mysqli_real_escape_string($conn, $_POST['project_name'] ?? '');
        $link_company = mysqli_real_escape_string($conn, $_POST['link_company'] ?? '');
        $service_required = mysqli_real_escape_string($conn, $_POST['service_required'] ?? '');
        
        $deal_value = (float)($_POST['total_amount'] ?? 0);
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? 'USD');
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
        
        $stage = mysqli_real_escape_string($conn, $_POST['project_status'] ?? 'Lead');
        $platform = mysqli_real_escape_string($conn, $_POST['platform'] ?? '');
        $sales_officer = mysqli_real_escape_string($conn, $_POST['sales_officer'] ?? '');
        $notes = mysqli_real_escape_string($conn, $_POST['additional_notes'] ?? '');

        // Note: Make sure your 'deals' database table has these new columns added if you want to store all of them.
        $insert_deal_sql = "INSERT INTO deals (deal_name, deal_value, stage, link_company, service_required, currency, start_date, end_date, platform, sales_officer, additional_notes) 
                            VALUES ('$deal_name', '$deal_value', '$stage', '$link_company', '$service_required', '$currency', '$start_date', '$end_date', '$platform', '$sales_officer', '$notes')";
        try {
            if(mysqli_query($conn, $insert_deal_sql)){
                $toastMessage = "Deal added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "DB Error! Ensure new columns exist in table.";
            $toastType = "error";
        }
    }
}

// B. Delete Deal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_deal'])) {
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_deal_id'] ?? '');
        $delete_sql = "DELETE FROM deals WHERE id='$del_id'";
        if(mysqli_query($conn, $delete_sql)){
            $toastMessage = "Deal deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting deal!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 3. FETCH DEALS DATA FOR UI
// ========================================================================
$hasDeals = false;
$dealTableRows = "";
$totalDeals = "0";
$totalPipelineValue = 0;

// Fetch companies for dropdown
$companyOptions = "";
if(isset($conn)){
    try {
        $comp_q = mysqli_query($conn, "SELECT id, company_name FROM companies ORDER BY company_name ASC");
        if($comp_q && mysqli_num_rows($comp_q) > 0){
            while($cRow = mysqli_fetch_assoc($comp_q)){
                $companyOptions .= "<option value='" . htmlspecialchars($cRow['company_name']) . "'>" . htmlspecialchars($cRow['company_name']) . "</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}
}

if(isset($conn)){
    try {
        $deal_query = mysqli_query($conn, "SELECT * FROM deals ORDER BY id DESC");
        if($deal_query && mysqli_num_rows($deal_query) > 0){
            $hasDeals = true;
            $totalDeals = mysqli_num_rows($deal_query);
            
            while($row = mysqli_fetch_assoc($deal_query)){
                $d_name     = htmlspecialchars($row['deal_name']);
                $d_company  = htmlspecialchars($row['link_company'] ?? '-');
                $d_service  = htmlspecialchars($row['service_required'] ?? '-');
                $d_platform = htmlspecialchars($row['platform'] ?? '-');
                $d_officer  = htmlspecialchars($row['sales_officer'] ?? '-');
                $d_notes    = htmlspecialchars($row['additional_notes'] ?? '');
                $d_currency = htmlspecialchars($row['currency'] ?? 'USD');
                $d_start    = !empty($row['start_date']) ? date('d M Y', strtotime($row['start_date'])) : '-';
                $d_end      = !empty($row['end_date'])   ? date('d M Y', strtotime($row['end_date']))   : '-';
                $d_value_raw = $row['deal_value'];
                $totalPipelineValue += $d_value_raw;
                $d_value = number_format($d_value_raw, 2);
                $d_stage = htmlspecialchars($row['stage']);
                $d_id = $row['id'];
                
                // Color badges based on stage
                $stage_class = 'todo';
                if($d_stage == 'Lead')        $stage_class = 'todo';
                if($d_stage == 'Proposal')    $stage_class = 'progress';
                if($d_stage == 'Negotiation') $stage_class = 'medium';
                if($d_stage == 'Won')         $stage_class = 'low';
                if($d_stage == 'Lost')        $stage_class = 'emergency';

                $dealTableRows .= "<tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><b>{$d_name}</b><br><small style='color:#9ca3af;font-weight:400;'>{$d_company}</small></td>
                    <td style='font-weight: 700; color: #10b981;'>{$d_currency} {$d_value}</td>
                    <td><span class='pill {$stage_class}'>{$d_stage}</span></td>
                    <td>{$d_service}</td>
                    <td>{$d_platform}</td>
                    <td>{$d_officer}</td>
                    <td>{$d_start}<br><small style='color:#9ca3af;'>→ {$d_end}</small></td>
                    <td style='text-align: right;'>
                        <div class='action-btns' style='justify-content: flex-end;'>
                            <button class='comp-icon-btn view' title='View' onclick=\"openViewModal({$d_id}, '{$d_name}', '{$d_company}', '{$d_service}', '{$d_platform}', '{$d_officer}', '{$d_currency} {$d_value}', '{$d_stage}', '{$d_start}', '{$d_end}', `{$d_notes}`)\"><i class='fa-regular fa-eye'></i></button>
                            <form method='POST' id='delete-deal-{$d_id}' style='display:inline;'>
                                <input type='hidden' name='delete_deal_id' value='{$d_id}'>
                                <input type='hidden' name='delete_deal' value='1'>
                                <button type='button' class='comp-icon-btn delete' onclick='confirmDelete(\"delete-deal-{$d_id}\", \"deal\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deal Pipeline - Systellio CRM</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ========================================================================
           GLOBAL STYLES & RESET (Same as Main Dashboard)
        ======================================================================== */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; height: 100vh; overflow: hidden; transition: background-color 0.3s, color 0.3s; color: #111827; }

        #toastBox { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; top: 30px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55), visibility 0.4s; }
        #toastBox.show { visibility: visible; transform: translateX(0); }
        #toastBox.success { background-color: #10b981; }
        #toastBox.error { background-color: #ef4444; }

                /* Sidebar CSS → see sidebar.php */
/* ========================================================================
           MAIN CONTENT & TOP NAVBAR STYLES
        ======================================================================== */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; transition: background-color 0.3s ease; background-color: #f3f4f6; }
        
        
        .toggle-btn:hover { color: #111827; }
        
        
        
        .nav-icon-btn:hover { color: #3b82f6; }
        
        .notification-badge { position: absolute; top: -4px; right: -4px; background-color: #ef4444; color: white; font-size: 9px; font-weight: bold; padding: 2px 5px; border-radius: 50%; border: 2px solid #ffffff; }
        body.dark-mode .notification-badge { border-color: #1e293b; }

        
        .user-profile i { font-size: 24px; color: #3b82f6; }

        /* ========================================================================
           DEAL PIPELINE SPECIFIC STYLES
        ======================================================================== */
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

        .comp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .comp-search { position: relative; width: 300px; }
        .comp-search i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;}
        .comp-search input { width: 100%; padding: 10px 15px 10px 38px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: 0.3s; color: #374151;}
        .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #eff6ff;}
        
        .comp-total { font-size: 13px; font-weight: 600; color: #4b5563; background: #ffffff; border: 1px solid #d1d5db; padding: 8px 15px; border-radius: 20px;}

        /* Statistics Cards for Deals */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .card { background-color: #ffffff; padding: 14px 16px; border-radius: 8px; box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.04); display: flex; align-items: center; justify-content: space-between; border: 1px solid #e5e7eb; transition: 0.3s;}
        .card-info h4 { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 4px; font-weight: 700; }
        .card-info h2 { font-size: 20px; font-weight: 800; transition: 0.3s;}
        .card-icon { background-color: #eff6ff; width: 36px; height: 36px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 15px; color: #3b82f6; transition: 0.3s; flex-shrink: 0;}

        /* Table Styles */
        .table-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db; transition: 0.3s; background: #ffffff;}
        .custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12px; }
        .custom-table th { background-color: #f9fafb; padding: 9px 12px; font-weight: 700; color: #374151; border-bottom: 1px solid #e5e7eb; transition: 0.3s; text-transform: uppercase; letter-spacing: 0.5px; font-size: 10px;}
        .custom-table td { padding: 9px 12px; color: #374151; font-weight: 500; vertical-align: middle; border-right: 1px solid rgba(0,0,0,0.05); transition: 0.3s; font-size: 12px;}
        .custom-table td:last-child { border-right: none; }

        .custom-table th:first-child, .custom-table td:first-child { width: 50px; text-align: center; }
        .custom-table tbody tr { background-color: #ffffff; }
        .custom-table tbody tr:nth-child(4n+1) { background-color: #e6fced; }
        .custom-table tbody tr:nth-child(4n+2) { background-color: #fcedf6; }
        .custom-table tbody tr:nth-child(4n+3) { background-color: #fceddb; }
        .custom-table tbody tr:nth-child(4n+4) { background-color: #e6edff; }
        .custom-table tbody tr:hover { background-color: #f0f7ff; }

        .tbl-checkbox { width: 16px; height: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; accent-color: #3b82f6;}

        /* Badges */
        .pill { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;}
        .pill.high { background: #fee2e2; color: #ef4444; } 
        .pill.medium { background: #fef3c7; color: #f59e0b; } 
        .pill.low { background: #d1fae5; color: #10b981; } 
        .pill.emergency { background: #ef4444; color: #ffffff; } 
        .pill.todo { background: #e5e7eb; color: #4b5563; } 
        .pill.progress { background: #dbeafe; color: #2563eb; } 

        /* Icon Buttons */
        .comp-icon-btn { width: 32px; height: 32px; border-radius: 4px; border: 1px solid #e5e7eb; background: transparent; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; transition: 0.3s; margin-right: 5px;}
        .comp-icon-btn.view { color: #3b82f6; }
        .comp-icon-btn.view:hover { background: #eff6ff; border-color: #3b82f6; }
        .comp-icon-btn.delete { color: #ef4444; }
        .comp-icon-btn.delete:hover { background: #fef2f2; border-color: #ef4444; }

        /* ========================================================================
           ADVANCED MULTI-STEP MODAL STYLES
        ======================================================================== */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 0; border-radius: 12px; width: 100%; max-width: 550px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); transition: 0.3s; overflow: hidden; display: flex; flex-direction: column;}
        
        .modal-header-container { background-color: #f4f6fb; padding: 25px 30px; border-bottom: 1px solid #e5e7eb; }
        .modal-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .modal-header h2 { font-size: 20px; font-weight: 800; color: #111827; margin-bottom: 5px; }
        .modal-header p { font-size: 12px; color: #6b7280; }
        .close-btn { font-size: 18px; cursor: pointer; color: #6b7280; border: none; background: none; transition: 0.3s; }
        .close-btn:hover { color: #ef4444; }

        /* Stepper UI */
        .stepper-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 25px; position: relative; z-index: 1;}
        .stepper-line { position: absolute; top: 12px; left: 10%; right: 10%; height: 2px; background-color: #d1d5db; z-index: -1; }
        .stepper-line-progress { position: absolute; top: 12px; left: 10%; height: 2px; background-color: #10b981; z-index: -1; transition: width 0.3s ease; }
        
        .step-item { display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
        .step-circle { width: 26px; height: 26px; border-radius: 50%; background-color: #e5e7eb; color: #6b7280; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; transition: 0.3s; }
        .step-item.active .step-circle { background-color: #10b981; color: #fff; }
        .step-item.completed .step-circle { background-color: #10b981; color: #fff; }
        .step-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #9ca3af; letter-spacing: 0.5px; text-align: center; }
        .step-item.active .step-label { color: #10b981; }

        /* Step Body */
        .modal-body { padding: 25px 30px; background-color: #ffffff; }
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .step-indicator-bar { display: flex; align-items: center; justify-content: space-between; background-color: #f8fafc; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; font-weight: 600; color: #4b5563; border: 1px solid #f1f5f9;}
        .step-indicator-bar i { color: #10b981; margin-right: 5px; }
        .progress-mini-bar { width: 60px; height: 6px; background-color: #e2e8f0; border-radius: 10px; overflow: hidden; display: inline-block; vertical-align: middle; margin-left: 10px;}
        .progress-mini-fill { height: 100%; background-color: #10b981; transition: width 0.3s; }

        /* Modern Form Inputs */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-grid.full { grid-template-columns: 1fr; }
        .form-group { margin-bottom: 15px; position: relative; } 
        .form-group label { display: block; font-size: 11px; color: #4b5563; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; transition: 0.3s;}
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: none; background-color: #f4f6fb; border-radius: 6px; font-size: 13px; outline: none; font-family: 'Inter', sans-serif; color: #1f2937; transition: 0.3s; box-shadow: inset 0 0 0 1px transparent;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { box-shadow: inset 0 0 0 1px #3b82f6; background-color: #fff; }
        .form-group textarea { resize: vertical; min-height: 80px; }

        /* Modal Footer */
        .modal-footer { padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e5e7eb; background-color: #ffffff; }
        .btn-back, .btn-cancel { background: transparent; border: none; color: #6b7280; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-back:hover, .btn-cancel:hover { color: #111827; }
        .btn-next { background-color: #4ade80; background-image: linear-gradient(180deg, #4ade80 0%, #22c55e 100%); color: #ffffff; padding: 10px 24px; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2); }
        .btn-next:hover { filter: brightness(1.05); }

        /* ========================================================================
           ADVANCED PREMIUM DARK MODE STYLES 
        ======================================================================== */
        body.dark-mode { background-color: #0f172a; color: #f8fafc; }
        body.dark-mode .main-content { background-color: #0f172a; }
        body.dark-mode 
        body.dark-mode 
        body.dark-mode .nav-icon-btn:hover { color: #f8fafc; }
        
        body.dark-mode .card, body.dark-mode .table-wrapper { background-color: #1e293b; border-color: #334155; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        body.dark-mode .card-icon { background-color: #334155; color: #3b82f6; }
        body.dark-mode .card-info h2, body.dark-mode .comp-header-title h1 { color: #f8fafc; }

        body.dark-mode .custom-table th { background-color: #1e293b; color: #cbd5e1; border-color: #334155; }
        body.dark-mode .custom-table td { color: #cbd5e1; border-color: #334155; }
        body.dark-mode .custom-table tbody tr { background-color: #0f172a; }
        body.dark-mode .custom-table tbody tr:nth-child(even) { background-color: #1e293b; }
        body.dark-mode .custom-table tbody tr:hover { background-color: #334155; }

        body.dark-mode .comp-search input { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #1e3a8a;}
        body.dark-mode .comp-total { background-color: #0f172a; color: #cbd5e1; border-color: #334155; }
        
        body.dark-mode .comp-icon-btn { border-color: #475569; color: #cbd5e1; }
        body.dark-mode .comp-icon-btn.view:hover { background: #1e3a8a; border-color: #3b82f6; color: #60a5fa;}
        body.dark-mode .comp-icon-btn.delete:hover { background: #7f1d1d; border-color: #ef4444; color: #fca5a5;}

        /* Dark Mode Modal Overlay */
        body.dark-mode .modal-content { background-color: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 1px solid #334155;}
        body.dark-mode .modal-header-container { background-color: #0f172a; border-bottom-color: #334155; }
        body.dark-mode .modal-header h2 { color: #f8fafc; }
        body.dark-mode .modal-body { background-color: #1e293b; }
        body.dark-mode .modal-footer { background-color: #1e293b; border-top-color: #334155; }
        body.dark-mode .step-circle { background-color: #334155; color: #94a3b8; }
        body.dark-mode .stepper-line { background-color: #334155; }
        body.dark-mode .step-indicator-bar { background-color: #0f172a; border-color: #334155; color: #cbd5e1; }
        body.dark-mode .progress-mini-bar { background-color: #334155; }
        body.dark-mode .form-group label { color: #cbd5e1; }
        body.dark-mode .form-group input, body.dark-mode .form-group select, body.dark-mode .form-group textarea { background-color: #0f172a; color: #f8fafc; }
        body.dark-mode .form-group input:focus, body.dark-mode .form-group select:focus, body.dark-mode .form-group textarea:focus { box-shadow: inset 0 0 0 1px #3b82f6; }
        body.dark-mode .btn-back, body.dark-mode .btn-cancel { color: #94a3b8; }
        body.dark-mode .btn-back:hover, body.dark-mode .btn-cancel:hover { color: #f8fafc; }

        /* SweetAlert Dark Mode */
        .swal2-container { z-index: 9999 !important; }
        body.dark-mode .swal2-popup { background-color: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        body.dark-mode .swal2-title, body.dark-mode .swal2-html-container { color: #f8fafc; }

        /* ===== VIEW DEAL MODAL ===== */
        .view-modal-overlay { display:none; position:fixed; z-index:3000; inset:0; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; }
        .view-modal-overlay.open { display:flex; }
        .view-modal-box { background:#fff; border-radius:12px; width:100%; max-width:480px; box-shadow:0 16px 40px rgba(0,0,0,0.18); overflow:hidden; animation: vmSlide .25s ease; }
        @keyframes vmSlide { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        .vm-header { background:#0f172a; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; }
        .vm-header h3 { font-size:15px; font-weight:800; color:#fff; letter-spacing:0.3px; }
        .vm-header button { background:none; border:none; color:#94a3b8; font-size:18px; cursor:pointer; line-height:1; transition:.2s; }
        .vm-header button:hover { color:#fff; }
        .vm-body { padding:18px 22px; display:grid; grid-template-columns:1fr 1fr; gap:10px 16px; }
        .vm-field { display:flex; flex-direction:column; gap:3px; }
        .vm-field.full { grid-column:1/-1; }
        .vm-field label { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#9ca3af; }
        .vm-field span { font-size:13px; font-weight:600; color:#111827; word-break:break-word; }
        .vm-footer { padding:12px 22px; border-top:1px solid #f1f5f9; text-align:right; }
        .vm-close-btn { background:#0f172a; color:#fff; border:none; padding:9px 22px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; transition:.2s; }
        .vm-close-btn:hover { background:#1e293b; }
        body.dark-mode .view-modal-box { background:#1e293b; border:1px solid #334155; }
        body.dark-mode .vm-header { background:#0b1524; }
        body.dark-mode .vm-field span { color:#f1f5f9; }
        body.dark-mode .vm-footer { border-color:#334155; background:#1e293b; }
    </style>
</head>
<body>

    <div id="toastBox">
        <i id="toastIcon" class="fa-solid fa-circle-check"></i>
        <span id="toastMsg">Action Successful!</span>
    </div>

        <?php $activePage = 'deal_pipeline'; include 'sidebar.php'; ?>

    <div class="main-content">
        <?php include 'topbar.php'; ?>

        <div class="company-container">
            <div class="user-list-header">
                <div class="comp-header-title">
                    <h1>Deal Pipeline</h1>
                    <p>Track and manage your sales pipeline efficiently.</p>
                </div>
                <div class="header-buttons">
                    <button class="btn-add-client" onclick="openModal('addDealModal')"><i class="fa-solid fa-handshake"></i> Add Deal</button>
                </div>
            </div>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-info"><h4>Total Deals</h4><h2><?php echo $totalDeals; ?></h2></div>
                    <div class="card-icon" style="background:#eff6ff; color:#3b82f6;"><i class="fa-solid fa-briefcase"></i></div>
                </div>
                <div class="card">
                    <div class="card-info"><h4>Pipeline Value</h4><h2>$<?php echo number_format($totalPipelineValue, 2); ?></h2></div>
                    <div class="card-icon" style="background:#fef3c7; color:#f59e0b;"><i class="fa-solid fa-sack-dollar"></i></div>
                </div>
                <div class="card">
                    <div class="card-info"><h4>Conversion Rate</h4><h2>68%</h2></div>
                    <div class="card-icon" style="background:#d1fae5; color:#10b981;"><i class="fa-solid fa-chart-line"></i></div>
                </div>
            </div>

            <div class="comp-toolbar" style="margin-bottom: 20px;">
                <div class="comp-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search deals...">
                </div>
                <div class="comp-total">Total Records: <?php echo (isset($hasDeals) && $hasDeals) ? $totalDeals : "0"; ?></div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="tbl-checkbox" title="Select All"></th>
                            <th>Deal / Company</th>
                            <th>Value</th>
                            <th>Stage</th>
                            <th>Service</th>
                            <th>Platform</th>
                            <th>Sales Officer</th>
                            <th>Timeline</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasDeals) && $hasDeals): ?>
                            <?php echo $dealTableRows; ?>
                        <?php else: ?>
                          
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="addDealModal" class="modal">
        <div class="modal-content">
            
            <div class="modal-header-container">
                <div class="modal-header">
                    <div>
                        <h2>Add New Deal</h2>
                        <p>Create a new project profile in your CRM</p>
                    </div>
                    <button type="button" class="close-btn" onclick="closeModal('addDealModal')"><i class="fa-solid fa-xmark"></i></button>
                </div>
                
                <div class="stepper-wrapper">
                    <div class="stepper-line"></div>
                    <div class="stepper-line-progress" id="stepperProgress" style="width: 0%;"></div>
                    
                    <div class="step-item active" id="stepIndicator1">
                        <div class="step-circle"><i class="fa-solid fa-check" id="checkIcon1" style="display:none;"></i><span id="numIcon1">1</span></div>
                        <span class="step-label">Basic Info</span>
                    </div>
                    <div class="step-item" id="stepIndicator2">
                        <div class="step-circle"><i class="fa-solid fa-check" id="checkIcon2" style="display:none;"></i><span id="numIcon2">2</span></div>
                        <span class="step-label">Financials</span>
                    </div>
                    <div class="step-item" id="stepIndicator3">
                        <div class="step-circle"><i class="fa-solid fa-check" id="checkIcon3" style="display:none;"></i><span id="numIcon3">3</span></div>
                        <span class="step-label">Status & Details</span>
                    </div>
                </div>
            </div>

            <form action="" method="POST" id="multiStepForm">
                <input type="hidden" name="create_deal" value="1">
                
                <div class="modal-body">
                    
                    <div class="step-content active" id="step1">
                        <div class="step-indicator-bar">
                            <div><i class="fa-solid fa-circle-info"></i> Step 1 of 3: Primary Details</div>
                            <div><span id="perc1">33%</span> Complete <div class="progress-mini-bar"><div class="progress-mini-fill" style="width:33%;"></div></div></div>
                        </div>

                        <div class="form-grid full">
                            <div class="form-group">
                                <label>Project Name <span style="color:#ef4444;">*</span></label>
                                <input type="text" id="project_name" name="project_name" placeholder="e.g. Lumina Architectural Firm" required>
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-group">
                                <label>Link Company <span style="color:#ef4444;">*</span></label>
                                <select id="link_company" name="link_company" required>
                                    <option value="" disabled selected>Select an associated company...</option>
                                    <?php if(!empty($companyOptions)): ?>
                                        <?php echo $companyOptions; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No companies found — add one first</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-group">
                                <label>Service Required</label>
                                <input type="text" name="service_required" placeholder="e.g. Commercial Design, Web Development">
                            </div>
                        </div>
                    </div>

                    <div class="step-content" id="step2">
                        <div class="step-indicator-bar">
                            <div><i class="fa-solid fa-circle-info"></i> Step 2 of 3: Financials & Timeline</div>
                            <div><span id="perc2">66%</span> Complete <div class="progress-mini-bar"><div class="progress-mini-fill" style="width:66%;"></div></div></div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Total Amount</label>
                                <input type="number" step="0.01" min="0" name="total_amount" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>Currency</label>
                                <select name="currency">
                                    <option value="USD" selected>USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="GBP">GBP (£)</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date">
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date">
                            </div>
                        </div>
                    </div>

                    <div class="step-content" id="step3">
                        <div class="step-indicator-bar">
                            <div><i class="fa-solid fa-circle-info"></i> Step 3 of 3: Status & Details</div>
                            <div><span id="perc3">100%</span> Complete <div class="progress-mini-bar"><div class="progress-mini-fill" style="width:100%;"></div></div></div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Project Status</label>
                                <select name="project_status">
                                    <option value="Lead" selected>Lead / Qualified</option>
                                    <option value="Proposal">Proposal / Quote</option>
                                    <option value="Negotiation">Negotiation</option>
                                    <option value="Won">Closed Won</option>
                                    <option value="Lost">Closed Lost</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Platform (Source)</label>
                                <select name="platform">
                                    <option value="" disabled selected>Select Source...</option>
                                    <option value="Website">Website</option>
                                    <option value="Referral">Referral</option>
                                    <option value="LinkedIn">LinkedIn</option>
                                    <option value="Cold Call">Cold Call</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-group">
                                <label>Sales Officer</label>
                                <input type="text" name="sales_officer" placeholder="Name of assigned officer">
                            </div>
                        </div>
                        <div class="form-grid full">
                            <div class="form-group">
                                <label>Additional Notes</label>
                                <textarea name="additional_notes" placeholder="Enter any extra details or comments..."></textarea>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <div>
                        <button type="button" class="btn-cancel" id="btnCancel" onclick="closeModal('addDealModal')">Cancel</button>
                        <button type="button" class="btn-back" id="btnPrev" style="display:none;" onclick="prevStep()"><i class="fa-solid fa-arrow-left"></i> Back</button>
                    </div>
                    <button type="button" class="btn-next" id="btnNext" onclick="nextStep()">Next Step <i class="fa-solid fa-arrow-right"></i></button>
                    <button type="submit" class="btn-next" id="btnSubmit" name="create_deal" style="display:none;">Save Deal <i class="fa-solid fa-check"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW DEAL MODAL -->
    <div class="view-modal-overlay" id="viewDealModal">
        <div class="view-modal-box">
            <div class="vm-header">
                <h3><i class="fa-solid fa-briefcase" style="margin-right:8px;color:#60a5fa;"></i> Deal Details</h3>
                <button onclick="closeViewModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="vm-body">
                <div class="vm-field full">
                    <label>Deal Name</label>
                    <span id="vm_name">-</span>
                </div>
                <div class="vm-field">
                    <label>Company</label>
                    <span id="vm_company">-</span>
                </div>
                <div class="vm-field">
                    <label>Stage</label>
                    <span id="vm_stage">-</span>
                </div>
                <div class="vm-field">
                    <label>Deal Value</label>
                    <span id="vm_value" style="color:#10b981;font-weight:800;">-</span>
                </div>
                <div class="vm-field">
                    <label>Platform / Source</label>
                    <span id="vm_platform">-</span>
                </div>
                <div class="vm-field full">
                    <label>Service Required</label>
                    <span id="vm_service">-</span>
                </div>
                <div class="vm-field">
                    <label>Sales Officer</label>
                    <span id="vm_officer">-</span>
                </div>
                <div class="vm-field">
                    <label>Start Date</label>
                    <span id="vm_start">-</span>
                </div>
                <div class="vm-field full">
                    <label>End Date</label>
                    <span id="vm_end">-</span>
                </div>
                <div class="vm-field full">
                    <label>Additional Notes</label>
                    <span id="vm_notes" style="color:#6b7280;font-weight:500;font-style:italic;">-</span>
                </div>
            </div>
            <div class="vm-footer">
                <button class="vm-close-btn" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        // --- VIEW DEAL MODAL ---
        function openViewModal(id, name, company, service, platform, officer, value, stage, start, end, notes) {
            document.getElementById('vm_name').textContent    = name    || '-';
            document.getElementById('vm_company').textContent = company || '-';
            document.getElementById('vm_service').textContent = service || '-';
            document.getElementById('vm_platform').textContent= platform|| '-';
            document.getElementById('vm_officer').textContent = officer || '-';
            document.getElementById('vm_value').textContent   = value   || '-';
            document.getElementById('vm_stage').textContent   = stage   || '-';
            document.getElementById('vm_start').textContent   = start   || '-';
            document.getElementById('vm_end').textContent     = end     || '-';
            document.getElementById('vm_notes').textContent   = notes   || 'No notes added.';
            document.getElementById('viewDealModal').classList.add('open');
        }
        function closeViewModal() {
            document.getElementById('viewDealModal').classList.remove('open');
        }
        document.getElementById('viewDealModal').addEventListener('click', function(e){
            if(e.target === this) closeViewModal();
        });

        // --- TOAST NOTIFICATION LOGIC ---
        window.onload = function() {
            <?php if($toastMessage != ""): ?>
                showToast("<?php echo $toastMessage; ?>", "<?php echo $toastType; ?>");
            <?php endif; ?>
        };

        function showToast(message, type) {
            const toast = document.getElementById("toastBox");
            const toastMsg = document.getElementById("toastMsg");
            const toastIcon = document.getElementById("toastIcon");

            toastMsg.innerText = message;
            toast.className = "show " + type;
            toastIcon.className = (type === 'success') ? "fa-solid fa-circle-check" : "fa-solid fa-circle-xmark";

            setTimeout(() => { toast.className = toast.className.replace("show", ""); }, 3000);
        }

        // --- SIDEBAR TOGGLE & DARK MODE handled by sidebar.php ---

        // --- SUBMENU TOGGLE ---

        // --- MODAL & MULTI-STEP LOGIC ---
        let currentStep = 1;

        function openModal(id) { 
            document.getElementById(id).style.display = "flex"; 
            currentStep = 1;
            updateFormSteps();
            document.getElementById("multiStepForm").reset();
        }
        
        function closeModal(id) { document.getElementById(id).style.display = "none"; }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }

        function updateFormSteps() {
            // Update step content visibility
            document.querySelectorAll('.step-content').forEach((el, index) => {
                el.classList.toggle('active', index === currentStep - 1);
            });

            // Update footer buttons
            document.getElementById('btnCancel').style.display = currentStep === 1 ? 'inline-block' : 'none';
            document.getElementById('btnPrev').style.display = currentStep > 1 ? 'inline-block' : 'none';
            
            if (currentStep === 3) {
                document.getElementById('btnNext').style.display = 'none';
                document.getElementById('btnSubmit').style.display = 'inline-block';
            } else {
                document.getElementById('btnNext').style.display = 'inline-block';
                document.getElementById('btnSubmit').style.display = 'none';
            }

            // Update Progress UI
            const progressLine = document.getElementById('stepperProgress');
            if(currentStep === 1) progressLine.style.width = '0%';
            if(currentStep === 2) progressLine.style.width = '50%';
            if(currentStep === 3) progressLine.style.width = '100%';

            // Update Nodes
            for (let i = 1; i <= 3; i++) {
                const stepInd = document.getElementById(`stepIndicator${i}`);
                const numIcon = document.getElementById(`numIcon${i}`);
                const checkIcon = document.getElementById(`checkIcon${i}`);

                if (i < currentStep) {
                    stepInd.classList.add('completed');
                    stepInd.classList.remove('active');
                    numIcon.style.display = 'none';
                    checkIcon.style.display = 'inline-block';
                } else if (i === currentStep) {
                    stepInd.classList.add('active');
                    stepInd.classList.remove('completed');
                    numIcon.style.display = 'inline-block';
                    checkIcon.style.display = 'none';
                } else {
                    stepInd.classList.remove('active', 'completed');
                    numIcon.style.display = 'inline-block';
                    checkIcon.style.display = 'none';
                }
            }
        }

        function validateStep() {
            if (currentStep === 1) {
                const projName = document.getElementById('project_name').value;
                const linkComp = document.getElementById('link_company').value;
                if (!projName || !linkComp) {
                    showToast("Please fill all required (*) fields.", "error");
                    return false;
                }
            }
            return true;
        }

        function nextStep() {
            if (validateStep() && currentStep < 3) {
                currentStep++;
                updateFormSteps();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                updateFormSteps();
            }
        }

        // --- SWEETALERT DELETE CONFIRMATION ---
        function confirmDelete(formId, typeName) {
            const isDark = document.body.classList.contains('dark-mode');
            const bgColor = isDark ? '#1e293b' : '#fff';
            const textColor = isDark ? '#f8fafc' : '#111827';

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this " + typeName + "!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                background: bgColor,
                color: textColor
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
</body>
</html>