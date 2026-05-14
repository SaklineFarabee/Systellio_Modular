<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================
session_start();
@include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$toastMessage = "";
$toastType    = "";

// ========================================================================
// AJAX: Get contacts by company_id
// ========================================================================
if (isset($_GET['get_contacts']) && isset($_GET['company_id']) && isset($conn)) {
    header('Content-Type: application/json');
    $cid = (int)$_GET['company_id'];
    $res = mysqli_query($conn, "SELECT id, name, email, phone, designation FROM contacts WHERE company_id = $cid ORDER BY id ASC");
    $out = [];
    if ($res) while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
    echo json_encode($out);
    exit();
}

// ========================================================================
// CSV EXPORT
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_companies_csv'])) {
    if (isset($conn)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=companies_export_' . date('Y-m-d') . '.csv');
        $out = fopen("php://output", "w");
        fputcsv($out, ['ID','Company Name','Assigned Agent','Total Contacts','Email','Phone','Website']);
        $q = mysqli_query($conn, "SELECT * FROM companies ORDER BY id DESC");
        if ($q) while ($r = mysqli_fetch_assoc($q))
            fputcsv($out, [$r['id'],$r['company_name'],$r['assigned_agent'],$r['total_contacts'],$r['company_email']??'',$r['company_number']??'',$r['company_website']??'']);
        fclose($out);
        exit();
    }
}

// ========================================================================
// CREATE COMPANY
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_company'])) {
    if (isset($conn)) {
        $comp_name     = mysqli_real_escape_string($conn, $_POST['company_name']    ?? '');
        $assigned      = mysqli_real_escape_string($conn, $_POST['assigned_agent'] ?? 'Unassigned');
        $country_code  = mysqli_real_escape_string($conn, $_POST['company_country_code'] ?? '');
        $raw_number    = mysqli_real_escape_string($conn, $_POST['company_number'] ?? '');
        $comp_number   = trim($country_code . ' ' . $raw_number);
        $comp_email    = mysqli_real_escape_string($conn, $_POST['company_email']   ?? '');
        $comp_website  = mysqli_real_escape_string($conn, $_POST['company_website'] ?? '');
        $fb_url        = mysqli_real_escape_string($conn, $_POST['fb_url']          ?? '');
        $linkedin_url  = mysqli_real_escape_string($conn, $_POST['linkedin_url']    ?? '');
        $insta_url     = mysqli_real_escape_string($conn, $_POST['insta_url']       ?? '');
        $twitter_url   = mysqli_real_escape_string($conn, $_POST['twitter_url']     ?? '');

        $sql = "INSERT INTO companies (company_name, assigned_agent, total_contacts, company_email, company_number, company_website, fb_url, linkedin_url, insta_url, twitter_url)
                VALUES ('$comp_name', '$assigned', 0, '$comp_email', '$comp_number', '$comp_website', '$fb_url', '$linkedin_url', '$insta_url', '$twitter_url')";
        try {
            if (mysqli_query($conn, $sql)) {
                $toastMessage = "Company added successfully!"; $toastType = "success";
            } else {
                $toastMessage = "Failed: " . mysqli_error($conn); $toastType = "error";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "DB Error: " . $e->getMessage(); $toastType = "error";
        }
    }
}

// ========================================================================
// UPDATE COMPANY
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_company'])) {
    if (isset($conn)) {
        $id       = (int)($_POST['edit_company_id'] ?? 0);
        $name     = mysqli_real_escape_string($conn, $_POST['edit_company_name']    ?? '');
        $agent    = mysqli_real_escape_string($conn, $_POST['edit_assigned_agent']  ?? 'Unassigned');
        $sql = "UPDATE companies SET company_name='$name', assigned_agent='$agent' WHERE id=$id";
        try {
            if (mysqli_query($conn, $sql)) {
                $toastMessage = "Company updated successfully!"; $toastType = "success";
            } else {
                $toastMessage = "Update failed: " . mysqli_error($conn); $toastType = "error";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "DB Error: " . $e->getMessage(); $toastType = "error";
        }
    }
}

// ========================================================================
// BULK UPLOAD
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload_companies'])) {
    if (isset($conn) && isset($_FILES['company_csv']) && $_FILES['company_csv']['error'] == 0) {
        $handle = fopen($_FILES['company_csv']['tmp_name'], "r");
        $cnt = 0;
        try {
            $first = true;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($first) { $first = false; continue; }
                $cn = mysqli_real_escape_string($conn, $data[0] ?? '');
                $ca = mysqli_real_escape_string($conn, $data[1] ?? 'Unassigned');
                $cc = (int)($data[2] ?? 0);
                if (!empty($cn)) { mysqli_query($conn, "INSERT INTO companies (company_name, assigned_agent, total_contacts) VALUES ('$cn','$ca','$cc')"); $cnt++; }
            }
            fclose($handle);
            $toastMessage = "CSV uploaded! Added $cnt companies."; $toastType = "success";
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Upload Failed!"; $toastType = "error";
        }
    } else {
        $toastMessage = "Please select a valid CSV file."; $toastType = "error";
    }
}

// ========================================================================
// DELETE COMPANY
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_company'])) {
    if (isset($conn)) {
        $del_id = (int)($_POST['delete_company_id'] ?? 0);
        if (mysqli_query($conn, "DELETE FROM companies WHERE id=$del_id")) {
            $toastMessage = "Company deleted successfully!"; $toastType = "success";
        } else {
            $toastMessage = "Error deleting company!"; $toastType = "error";
        }
    }
}

// ========================================================================
// FETCH: Assignee Options
// ========================================================================
$assigneeOptions = "";
if (isset($conn)) {
    $uq = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
    if ($uq) while ($ur = mysqli_fetch_assoc($uq))
        $assigneeOptions .= "<option value='{$ur['name']}'>{$ur['name']} (038H{$ur['id']})</option>";
}

// ========================================================================
// FETCH: Company Table Rows
// ========================================================================
$hasCompanies    = false;
$companyTableRows = "";
$totalCompanies  = "0";

if (isset($conn)) {
    try {
        $cq = mysqli_query($conn, "
            SELECT c.id, c.company_name, c.assigned_agent,
                   c.company_email, c.company_number, c.company_website, c.created_at,
                   (SELECT COUNT(*) FROM contacts WHERE company_id = c.id) AS total_dynamic_contacts
            FROM companies c ORDER BY c.id DESC
        ");
        if ($cq && mysqli_num_rows($cq) > 0) {
            $hasCompanies   = true;
            $totalCompanies = mysqli_num_rows($cq);
            while ($row = mysqli_fetch_assoc($cq)) {
                $c_name     = htmlspecialchars($row['company_name']);
                $c_agent    = htmlspecialchars($row['assigned_agent']);
                $c_contacts = (int)$row['total_dynamic_contacts'];
                $c_id       = (int)$row['id'];
                $c_email    = htmlspecialchars($row['company_email'] ?? '');
                $c_phone    = htmlspecialchars($row['company_number'] ?? '');
                $c_website  = htmlspecialchars($row['company_website'] ?? '');
                $c_date     = !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '—';
                $rowData    = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                $email_html   = !empty($c_email)   ? "<a href='mailto:{$c_email}'   style='color:#3b82f6;text-decoration:none;'>{$c_email}</a>"       : "<span style='color:#9ca3af;'>—</span>";
                $phone_html   = !empty($c_phone)   ? "<a href='tel:{$c_phone}'      style='color:#374151;text-decoration:none;'>{$c_phone}</a>"        : "<span style='color:#9ca3af;'>—</span>";
                $website_html = !empty($c_website) ? "<a href='{$c_website}' target='_blank' style='color:#8b5cf6;text-decoration:none;'><i class='fa-solid fa-arrow-up-right-from-square' style='font-size:10px;margin-right:3px;'></i>Visit</a>" : "<span style='color:#9ca3af;'>—</span>";

                $companyTableRows .= "
                <tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><b>{$c_name}</b></td>
                    <td>
                        <div style='display:flex;justify-content:center;align-items:center;gap:8px;'>
                            <i class='fa-solid fa-user' style='color:#9ca3af;'></i> {$c_agent}
                        </div>
                    </td>
                    <td>{$email_html}</td>
                    <td>{$phone_html}</td>
                    <td>{$website_html}</td>
                    <td><span class='comp-contacts-pill'>{$c_contacts} Contacts</span></td>
                    <td style='color:#6b7280;font-size:11px;'><i class='fa-regular fa-calendar' style='margin-right:4px;'></i>{$c_date}</td>
                    <td>
                        <div class='action-btns'>
                            <a href='company_profile.php?id={$c_id}' class='btn-view' title='View Profile' style='display:inline-flex;align-items:center;justify-content:center;'><i class='fa-regular fa-eye'></i></a>
                            <button class='btn-edit'  title='Edit'   onclick='openEditModal({$rowData})'><i class='fa-solid fa-pen'></i></button>
                            <form method='POST' id='delete-comp-{$c_id}' style='display:inline;'>
                                <input type='hidden' name='delete_company_id' value='{$c_id}'>
                                <input type='hidden' name='delete_company'    value='1'>
                                <button type='button' class='btn-delete' title='Delete'
                                    onclick='confirmDelete(\"delete-comp-{$c_id}\",\"company\")'><i class='fa-solid fa-trash'></i></button>
                            </form>
                        </div>
                    </td>
                </tr>";
            }
        }
    } catch (mysqli_sql_exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company & Organization - Systellio CRM</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background:#f3f4f6; display:flex; height:100vh; overflow:hidden; transition:background-color .3s,color .3s; color:#111827; }

        /* Toast */
        #toastBox { visibility:hidden; min-width:250px; background:#333; color:#fff; text-align:center; border-radius:8px; padding:16px; position:fixed; z-index:9999; right:30px; top:30px; font-size:14px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,.15); display:flex; align-items:center; gap:10px; transform:translateX(100%); transition:transform .4s cubic-bezier(.68,-.55,.265,1.55),visibility .4s; }
        #toastBox.show  { visibility:visible; transform:translateX(0); }
        #toastBox.success { background:#10b981; }
        #toastBox.error   { background:#ef4444; }

        /* Layout */
        .main-content { flex-grow:1; display:flex; flex-direction:column; overflow-y:auto; background:#f3f4f6; transition:background-color .3s; }
        
        
        
        
        .nav-icon-btn:hover { color:#3b82f6; }
        .notification-badge { position:absolute; top:-4px; right:-4px; background:#ef4444; color:#fff; font-size:9px; font-weight:700; padding:2px 5px; border-radius:50%; border:2px solid #fff; }
        

        /* Page container */
        .company-container { padding:30px; }

        /* ── Header bar ── */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px; }
        .comp-header-title h1 { font-size:26px; font-weight:800; letter-spacing:-.5px; color:#111827; }
        .comp-header-title p  { font-size:13px; color:#6b7280; margin-top:2px; }

        /* Header buttons — matches client_list.php style */
        .header-buttons { display:flex; gap:10px; }
        .btn-export {
            background-color:#16a34a; color:#ffffff; padding:10px 18px;
            border-radius:6px; font-size:13px; font-weight:700; border:none;
            cursor:pointer; display:flex; align-items:center; gap:8px;
            box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:background-color .2s, transform .1s;
        }
        .btn-export:hover { background-color:#15803d; transform:translateY(-1px); }
        .btn-bulk {
            background-color:#1e293b; color:#ffffff; padding:10px 18px;
            border-radius:6px; font-size:13px; font-weight:700; border:none;
            cursor:pointer; display:flex; align-items:center; gap:8px;
            box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:background-color .2s, transform .1s;
        }
        .btn-bulk:hover { background-color:#334155; transform:translateY(-1px); }
        .btn-add-company {
            background-color:#0f172a; color:#ffffff; padding:10px 18px;
            border-radius:6px; font-size:13px; font-weight:700; border:none;
            cursor:pointer; display:flex; align-items:center; gap:8px;
            box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:background-color .2s, transform .1s;
        }
        .btn-add-company:hover { background-color:#1e293b; transform:translateY(-1px); }

        /* Toolbar */
        .comp-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
        .comp-search  { position:relative; width:300px; }
        .comp-search i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:13px; }
        .comp-search input { width:100%; padding:10px 14px 10px 38px; border:1px solid #d1d5db; border-radius:20px; font-size:13px; outline:none; transition:.3s; color:#374151; background:#fff; }
        .comp-search input:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
        .comp-total { font-size:13px; font-weight:600; color:#4b5563; background:#fff; border:1px solid #d1d5db; padding:8px 15px; border-radius:20px; }

        /* Table */
        .table-wrapper { border-radius:8px; overflow:hidden; border:1px solid #d1d5db; background:#fff; }
        .custom-table  { width:100%; border-collapse:collapse; text-align:center; font-size:12px; }
        .custom-table th { background:#c4f042; padding:14px 10px; font-weight:700; color:#000; border-bottom:1px solid #d1d5db; }
        .custom-table td { padding:13px 10px; color:#374151; font-weight:500; vertical-align:middle; border-right:1px solid rgba(0,0,0,.05); }
        .custom-table td:last-child { border-right:none; }
        .custom-table tbody tr:nth-child(4n+1) { background:#e6fced; }
        .custom-table tbody tr:nth-child(4n+2) { background:#fcedf6; }
        .custom-table tbody tr:nth-child(4n+3) { background:#fceddb; }
        .custom-table tbody tr:nth-child(4n+4) { background:#e6edff; }

        .comp-contacts-pill { background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; font-size:11px; font-weight:600; padding:4px 12px; border-radius:20px; display:inline-block; }
        .tbl-checkbox { width:16px; height:16px; accent-color:#3b82f6; cursor:pointer; }

        /* Action buttons */
        .action-btns { display:flex; justify-content:center; gap:5px; }
        .btn-view   { background:#60a5fa; color:#fff; padding:6px 10px; border-radius:4px; font-size:11px; border:none; cursor:pointer; transition:.2s; }
        .btn-edit   { background:#34d399; color:#fff; padding:6px 10px; border-radius:4px; font-size:11px; border:none; cursor:pointer; transition:.2s; }
        .btn-delete { background:#f87171; color:#fff; padding:6px 10px; border-radius:4px; font-size:11px; border:none; cursor:pointer; transition:.2s; }
        .btn-view:hover   { background:#3b82f6; }
        .btn-edit:hover   { background:#10b981; }
        .btn-delete:hover { background:#ef4444; }

        /* ── Modals (shared) ── */
        .modal { display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,.5); align-items:center; justify-content:center; }

        /* ── View / Edit small modals ── */
        .modal-content { background:#fff; padding:28px; border-radius:10px; width:100%; max-width:700px; box-shadow:0 10px 25px rgba(0,0,0,.15); max-height:90vh; overflow-y:auto; }
        .small-modal   { max-width:450px; }
        .modal-header  { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-header h2 { font-size:18px; font-weight:700; }
        .close-btn { font-size:20px; cursor:pointer; color:#6b7280; border:none; background:none; transition:.2s; }
        .close-btn:hover { color:#ef4444; }

        /* View data boxes */
        .view-grid  { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:8px; }
        .view-item  { background:#f9fafb; border:1px solid #e5e7eb; border-radius:7px; padding:12px 14px; }
        .view-label { font-size:10px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; }
        .view-value { font-size:13px; font-weight:600; color:#111827; }
        .view-full  { grid-column:span 2; }
        .view-badge { display:inline-flex; align-items:center; gap:6px; background:#dbeafe; color:#1d4ed8; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }

        /* Sub-contacts table */
        .sub-contacts-table { width:100%; border-collapse:collapse; font-size:12px; }
        .sub-contacts-table th { background:#f1f5f9; padding:9px 12px; font-weight:700; color:#374151; text-align:left; border-bottom:1px solid #e2e8f0; }
        .sub-contacts-table td { padding:10px 12px; color:#374151; font-weight:500; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        .sub-contacts-table tr:last-child td { border-bottom:none; }
        .sub-contacts-table tr:hover td { background:#f8fafc; }
        .sub-contacts-table .pill { display:inline-block; padding:3px 10px; border-radius:20px; font-size:10px; font-weight:700; }
        .sub-contacts-table .pill-blue { background:#eff6ff; color:#3b82f6; border:1px solid #bfdbfe; }
        .sub-contacts-table .pill-yellow { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
        .sub-contacts-no { text-align:center; padding:20px; color:#9ca3af; font-size:12px; font-style:italic; }
        body.dark-mode .sub-contacts-table th { background:#1e293b; color:#94a3b8; border-color:#334155; }
        body.dark-mode .sub-contacts-table td { color:#cbd5e1; border-color:#1e293b; }
        body.dark-mode .sub-contacts-table tr:hover td { background:#0f172a; }

        /* Edit form */
        .edit-grid  { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:4px; }
        .edit-group { margin-bottom:0; }
        .edit-group.full { grid-column:span 2; }
        .edit-group label { display:block; font-size:11px; font-weight:700; color:#4b5563; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
        .edit-group input,
        .edit-group select { width:100%; padding:10px 13px; border:1.5px solid #e5e7eb; border-radius:7px; font-size:13px; font-family:'Inter',sans-serif; color:#1f2937; outline:none; transition:.2s; background:#f9fafb; }
        .edit-group input:focus,
        .edit-group select:focus { border-color:#3b82f6; background:#fff; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
        .edit-footer { display:flex; gap:10px; margin-top:20px; }
        .btn-save-edit { flex:1; background:#3b82f6; color:#fff; padding:12px; border:none; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer; transition:.2s; }
        .btn-save-edit:hover { background:#2563eb; }
        .btn-cancel-edit { flex:1; background:#f3f4f6; color:#374151; padding:12px; border:none; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; transition:.2s; }
        .btn-cancel-edit:hover { background:#e5e7eb; }

        /* ── Add Company Wizard ── */
        .modal-content.comp-modal-content { max-width:650px; padding:0; overflow:hidden; }
        .comp-modal-header-wrap { background:#f4f6fb; padding:24px 28px 20px; border-bottom:1px solid #e5e7eb; }
        .comp-modal-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; }
        .comp-modal-top h2 { font-size:20px; font-weight:800; color:#111827; margin-bottom:3px; }
        .comp-modal-top p  { font-size:12px; color:#6b7280; }
        .camp-progress-bar { display:flex; justify-content:space-between; position:relative; padding:0 10px; }
        .camp-progress-bar::before { content:''; position:absolute; top:15px; left:0; width:100%; height:2px; background:#e5e7eb; z-index:1; }
        .camp-progress-step { width:32px; height:32px; background:#fff; border:2px solid #e5e7eb; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#9ca3af; z-index:2; position:relative; transition:all .3s; }
        .camp-progress-step.active    { border-color:#2563eb; color:#2563eb; background:#eff6ff; }
        .camp-progress-step.completed { background:#2563eb; border-color:#2563eb; color:#fff; }
        .step-label-row { display:flex; justify-content:space-between; padding:6px 4px 0; }
        .step-label-row span { font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.4px; text-align:center; flex:1; }
        .step-label-row span.active-lbl { color:#2563eb; }
        .comp-modal-body { padding:24px 28px; background:#fff; }
        .comp-step-container { display:none; }
        .comp-step-container.comp-step-active { display:block; animation:compFade .35s ease; }
        @keyframes compFade { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .step-section-title { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; color:#2563eb; text-transform:uppercase; letter-spacing:.6px; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #dbeafe; }
        .comp-form-grid     { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .comp-form-grid.full { grid-template-columns:1fr; }
        .comp-form-group    { margin-bottom:4px; }
        .comp-form-group label { display:block; font-size:11px; font-weight:700; color:#4b5563; text-transform:uppercase; letter-spacing:.5px; margin-bottom:6px; }
        .comp-form-group input,
        .comp-form-group select { width:100%; padding:11px 13px; border:none; background:#f4f6fb; border-radius:6px; font-size:13px; font-family:'Inter',sans-serif; color:#1f2937; outline:none; transition:.25s; box-shadow:inset 0 0 0 1px transparent; }
        .comp-form-group input:focus,
        .comp-form-group select:focus { box-shadow:inset 0 0 0 1.5px #2563eb; background:#fff; }
        .comp-phone-wrap { display:flex; gap:8px; }
        .comp-phone-wrap select { max-width:110px; }
        .comp-modal-footer { display:flex; justify-content:space-between; align-items:center; padding:18px 28px; border-top:1px solid #e5e7eb; background:#fff; }
        .comp-btn-cancel,.comp-btn-back { background:transparent; border:none; color:#6b7280; font-size:13px; font-weight:600; cursor:pointer; transition:.2s; padding:0; }
        .comp-btn-cancel:hover,.comp-btn-back:hover { color:#111827; }
        .comp-btn-next { background:#2563eb; color:#fff; padding:10px 22px; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.2s; }
        .comp-btn-next:hover { background:#1d4ed8; }
        .comp-btn-save { background:#10b981; color:#fff; padding:10px 22px; border:none; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.2s; }
        .comp-btn-save:hover { background:#059669; }

        /* ── DARK MODE ── */
        body.dark-mode { background:#0f172a; color:#f8fafc; }
        body.dark-mode .main-content  { background:#0f172a; }
        body.dark-mode 
        body.dark-mode 
        body.dark-mode .comp-header-title h1 { color:#f8fafc; }
        body.dark-mode .btn-export { background-color:#15803d; }
        body.dark-mode .btn-bulk   { background-color:#334155; }
        body.dark-mode .btn-add-company { background-color:#1e293b; border:1px solid #334155; }
        body.dark-mode .comp-search input { background:#0f172a; color:#f8fafc; border-color:#334155; }
        body.dark-mode .comp-total    { background:#0f172a; color:#cbd5e1; border-color:#334155; }
        body.dark-mode .table-wrapper { border-color:#334155; background:#1e293b; }
        body.dark-mode .custom-table th { background:#334155; color:#f8fafc; border-color:#475569; }
        body.dark-mode .custom-table td { color:#cbd5e1; border-color:#334155; }
        body.dark-mode .custom-table tbody tr:nth-child(n) { background:#1e293b; }
        body.dark-mode .custom-table tbody tr:nth-child(odd) { background:#0f172a; }
        body.dark-mode .modal-content { background:#1e293b; }
        body.dark-mode .view-item     { background:#0f172a; border-color:#334155; }
        body.dark-mode .view-label    { color:#94a3b8; }
        body.dark-mode .view-value    { color:#f8fafc; }
        body.dark-mode .edit-group label { color:#cbd5e1; }
        body.dark-mode .edit-group input,
        body.dark-mode .edit-group select { background:#0f172a; color:#f8fafc; border-color:#334155; }
        body.dark-mode .btn-cancel-edit { background:#334155; color:#cbd5e1; }
        body.dark-mode .comp-modal-header-wrap { background:#0f172a; border-color:#334155; }
        body.dark-mode .comp-modal-top h2 { color:#f8fafc; }
        body.dark-mode .camp-progress-step { background:#1e293b; border-color:#334155; color:#94a3b8; }
        body.dark-mode .camp-progress-bar::before { background:#334155; }
        body.dark-mode .comp-modal-body   { background:#1e293b; }
        body.dark-mode .comp-modal-footer { background:#1e293b; border-color:#334155; }
        body.dark-mode .comp-form-group label { color:#cbd5e1; }
        body.dark-mode .comp-form-group input,
        body.dark-mode .comp-form-group select { background:#0f172a; color:#f8fafc; }
        body.dark-mode .step-section-title { color:#60a5fa; border-color:#1e3a8a; }
        body.dark-mode .step-label-row span { color:#475569; }
        body.dark-mode .step-label-row span.active-lbl { color:#60a5fa; }
        body.dark-mode .comp-btn-cancel,
        body.dark-mode .comp-btn-back { color:#94a3b8; }
        .swal2-container { z-index:9999 !important; }
        body.dark-mode .swal2-popup { background:#1e293b; color:#f8fafc; border:1px solid #334155; }
        body.dark-mode .swal2-title,
        body.dark-mode .swal2-html-container { color:#f8fafc; }
    </style>
</head>
<body>

<div id="toastBox"><i id="toastIcon" class="fa-solid fa-circle-check"></i><span id="toastMsg">Done!</span></div>

<?php $activePage = 'company_list'; include 'sidebar.php'; ?>

<div class="main-content">
    <!-- Navbar -->
    <?php include 'topbar.php'; ?>

    <div class="company-container">

        <!-- ── Page Header ── -->
        <div class="page-header">
            <div class="comp-header-title">
                <h1>Company Database</h1>
                <p>Manage companies, contacts and assignments</p>
            </div>

            <!-- Header Buttons — matches client_list.php style -->
            <div class="header-buttons">
                <form method="POST" style="margin:0;">
                    <button type="submit" name="export_companies_csv" class="btn-export">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </button>
                </form>

                <button class="btn-bulk" onclick="openModal('bulkUploadCompanyModal')">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload
                </button>

                <button class="btn-add-company" onclick="openModal('addCompanyModal')">
                    <i class="fa-solid fa-plus"></i> Add Company
                </button>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="comp-toolbar">
            <div class="comp-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="compSearchInput" placeholder="Search company or agent..." oninput="searchTable()">
            </div>
            <div class="comp-total">Total: <b><?php echo $hasCompanies ? $totalCompanies : '0'; ?></b> Companies</div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table class="custom-table" id="companyTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="tbl-checkbox" title="Select All" onchange="toggleAll(this)"></th>
                        <th>Company Name</th>
                        <th>Assigned Agent</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Website</th>
                        <th>Total Contacts</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hasCompanies): ?>
                        <?php echo $companyTableRows; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="padding:30px;color:#9ca3af;font-style:italic;">
                                <i class="fa-solid fa-building" style="font-size:24px;margin-bottom:8px;display:block;"></i>
                                No companies found. Click <b>Add Company</b> to get started.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /company-container -->
</div><!-- /main-content -->


<!-- ══════════════════════════════════════════
     VIEW COMPANY MODAL
══════════════════════════════════════════ -->
<div id="viewCompanyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-building" style="color:#3b82f6;margin-right:8px;"></i>Company Details</h2>
            <button type="button" class="close-btn" onclick="closeModal('viewCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="view-grid">
            <div class="view-item view-full">
                <div class="view-label">Company Name</div>
                <div class="view-value" id="view_comp_name" style="font-size:16px;">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Assigned Agent</div>
                <div class="view-value" id="view_comp_agent">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Total Contacts</div>
                <div class="view-value" id="view_comp_contacts">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Email</div>
                <div class="view-value" id="view_comp_email">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Phone</div>
                <div class="view-value" id="view_comp_phone">—</div>
            </div>
            <div class="view-item view-full">
                <div class="view-label">Website</div>
                <div class="view-value" id="view_comp_website">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Company ID</div>
                <div class="view-value" id="view_comp_id">—</div>
            </div>
            <div class="view-item">
                <div class="view-label">Date Added</div>
                <div class="view-value" id="view_comp_date">—</div>
            </div>
        </div>

        <!-- Contacts Sub-Table -->
        <div style="margin-top:24px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;padding-bottom:10px;border-bottom:2px solid #e5e7eb;">
                <i class="fa-solid fa-users" style="color:#3b82f6;font-size:14px;"></i>
                <span style="font-size:13px;font-weight:800;color:#111827;text-transform:uppercase;letter-spacing:.5px;">Accounts & Clients</span>
                <span id="view_contacts_count_badge" style="background:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:4px;">0</span>
            </div>
            <div id="view_contacts_table_wrap">
                <div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Loading contacts...
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:20px;">
            <button class="btn-save-edit" style="background:#34d399;" onclick="switchViewToEdit()">
                <i class="fa-solid fa-pen"></i> Edit Company
            </button>
            <button class="btn-cancel-edit" onclick="closeModal('viewCompanyModal')">Close</button>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════
     EDIT COMPANY MODAL
══════════════════════════════════════════ -->
<div id="editCompanyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fa-solid fa-pen" style="color:#10b981;margin-right:8px;"></i>Edit Company</h2>
            <button type="button" class="close-btn" onclick="closeModal('editCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="company_list.php" method="POST" id="editCompanyForm">
            <input type="hidden" name="edit_company_id" id="edit_company_id">
            <input type="hidden" name="update_company"  value="1">

            <div class="edit-grid">
                <div class="edit-group full">
                    <label>Company Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="edit_company_name" id="edit_company_name" required placeholder="e.g. Acme Corporation">
                </div>
                <div class="edit-group full">
                    <label>Assigned Agent</label>
                    <select name="edit_assigned_agent" id="edit_assigned_agent">
                        <option value="Unassigned">Unassigned</option>
                        <?php echo $assigneeOptions; ?>
                    </select>
                </div>
            </div>

            <div class="edit-footer">
                <button type="submit" class="btn-save-edit">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <button type="button" class="btn-cancel-edit" onclick="closeModal('editCompanyModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════
     ADD COMPANY — 3-Step Wizard
══════════════════════════════════════════ -->
<div id="addCompanyModal" class="modal">
    <div class="modal-content comp-modal-content">

        <div class="comp-modal-header-wrap">
            <div class="comp-modal-top">
                <div>
                    <h2>Add New Company</h2>
                    <p>Fill in the details to create a new company record</p>
                </div>
                <button type="button" class="close-btn" onclick="closeCompanyModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="camp-progress-bar">
                <div class="camp-progress-step active" id="compStep1">1</div>
                <div class="camp-progress-step"        id="compStep2">2</div>
                <div class="camp-progress-step"        id="compStep3">3</div>
            </div>
            <div class="step-label-row">
                <span class="active-lbl" id="compLbl1">Basic Info</span>
                <span id="compLbl2">Contact</span>
                <span id="compLbl3">Social Media</span>
            </div>
        </div>

        <form action="company_list.php" method="POST" id="addCompanyForm">

            <div class="comp-modal-body">

                <!-- Step 1 -->
                <div class="comp-step-container comp-step-active" id="compStepBody1">
                    <div class="step-section-title"><i class="fa-solid fa-building"></i> Company Identity</div>
                    <div class="comp-form-grid full">
                        <div class="comp-form-group">
                            <label>Company Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="company_name" id="comp_name_input" placeholder="e.g. Acme Corporation" required>
                        </div>
                    </div>
                    <div class="comp-form-grid" style="margin-top:14px;">
                        <div class="comp-form-group">
                            <label>Assigned Agent</label>
                            <select name="assigned_agent">
                                <option value="Unassigned" selected>Select Agent...</option>
                                <?php echo $assigneeOptions; ?>
                            </select>
                        </div>
                        <div class="comp-form-group">
                            <label>Company Website</label>
                            <input type="url" name="company_website" placeholder="https://company.com">
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="comp-step-container" id="compStepBody2">
                    <div class="step-section-title"><i class="fa-solid fa-address-card"></i> Contact Details</div>
                    <div class="comp-form-grid full">
                        <div class="comp-form-group">
                            <label>Company Email</label>
                            <input type="email" name="company_email" placeholder="info@company.com">
                        </div>
                    </div>
                    <div class="comp-form-grid full" style="margin-top:14px;">
                        <div class="comp-form-group">
                            <label>Phone Number</label>
                            <div class="comp-phone-wrap">
                                <select name="company_country_code">
                                    <option value="+880">🇧🇩 +880</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+91">🇮🇳 +91</option>
                                    <option value="+971">🇦🇪 +971</option>
                                    <option value="+65">🇸🇬 +65</option>
                                    <option value="+61">🇦🇺 +61</option>
                                    <option value="+49">🇩🇪 +49</option>
                                </select>
                                <input type="text" name="company_number" placeholder="017XX XXX XXX" style="flex:1;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="comp-step-container" id="compStepBody3">
                    <div class="step-section-title"><i class="fa-solid fa-share-nodes"></i> Social Media Profiles</div>
                    <div class="comp-form-grid" style="margin-bottom:14px;">
                        <div class="comp-form-group">
                            <label><i class="fa-brands fa-facebook" style="color:#1877F2;"></i> Facebook URL</label>
                            <input type="url" name="fb_url" placeholder="https://facebook.com/...">
                        </div>
                        <div class="comp-form-group">
                            <label><i class="fa-brands fa-linkedin" style="color:#0A66C2;"></i> LinkedIn URL</label>
                            <input type="url" name="linkedin_url" placeholder="https://linkedin.com/company/...">
                        </div>
                        <div class="comp-form-group">
                            <label><i class="fa-brands fa-instagram" style="color:#E4405F;"></i> Instagram URL</label>
                            <input type="url" name="insta_url" placeholder="https://instagram.com/...">
                        </div>
                        <div class="comp-form-group">
                            <label><i class="fa-brands fa-x-twitter"></i> Twitter / X URL</label>
                            <input type="url" name="twitter_url" placeholder="https://x.com/...">
                        </div>
                    </div>
                    <p style="font-size:11px;color:#9ca3af;"><i class="fa-solid fa-circle-info"></i> Social fields are optional.</p>
                </div>
            </div>

            <!-- Footer nav (inside form) -->
            <div class="comp-modal-footer">
                <div>
                    <button type="button" class="comp-btn-cancel" id="compBtnCancel" onclick="closeCompanyModal()">Cancel</button>
                    <button type="button" class="comp-btn-back"   id="compBtnBack"   style="display:none;margin-left:14px;" onclick="compPrevStep()">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </button>
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="button" class="comp-btn-next" id="compBtnNext" onclick="compNextStep()">
                        Next Step <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <button type="submit" name="create_company" value="1" class="comp-btn-save" id="compBtnSave" style="display:none;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Company
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════
     BULK UPLOAD MODAL
══════════════════════════════════════════ -->
<div id="bulkUploadCompanyModal" class="modal">
    <div class="modal-content small-modal">
        <div class="modal-header">
            <h2>Bulk Upload (CSV)</h2>
            <button type="button" class="close-btn" onclick="closeModal('bulkUploadCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="company_list.php" method="POST" enctype="multipart/form-data">
            <p style="font-size:12px;color:#6b7280;margin-bottom:15px;">
                Columns: <b>Company Name, Assigned Agent, Total Contacts</b>
            </p>
            <div style="margin-bottom:20px;">
                <input type="file" name="company_csv" accept=".csv" required style="font-size:13px;">
            </div>
            <button type="submit" name="bulk_upload_companies"
                style="width:100%;background:#10b981;color:#fff;padding:12px;border:none;border-radius:7px;font-size:14px;font-weight:700;cursor:pointer;">
                <i class="fa-solid fa-upload"></i> Upload CSV Data
            </button>
        </form>
    </div>
</div>


<script>
// ── Modal helpers ──────────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        if (e.target.id === 'addCompanyModal') compResetWizard();
    }
};

// ── Current row data (for view→edit switch) ────────────────────────────────
let _currentCompany = null;

// ── VIEW modal ─────────────────────────────────────────────────────────────
function openViewModal(data) {
    _currentCompany = data;
    document.getElementById('view_comp_name').textContent     = data.company_name      || '—';
    document.getElementById('view_comp_agent').textContent    = data.assigned_agent    || '—';
    document.getElementById('view_comp_contacts').innerHTML   = `<span class="view-badge"><i class="fa-solid fa-users"></i> ${data.total_dynamic_contacts || 0} Contacts</span>`;
    document.getElementById('view_comp_id').textContent       = '#' + (data.id || '—');
    document.getElementById('view_comp_date').textContent     = data.created_at
        ? new Date(data.created_at).toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'})
        : '—';
    // New fields
    const email = data.company_email || '';
    const phone = data.company_number || '';
    const web   = data.company_website || '';
    document.getElementById('view_comp_email').innerHTML   = email ? `<a href="mailto:${email}" style="color:#3b82f6;">${email}</a>` : '—';
    document.getElementById('view_comp_phone').innerHTML   = phone ? `<a href="tel:${phone}" style="color:#374151;">${phone}</a>` : '—';
    document.getElementById('view_comp_website').innerHTML = web   ? `<a href="${web}" target="_blank" style="color:#8b5cf6;">${web}</a>` : '—';

    // Load contacts sub-table
    const wrap = document.getElementById('view_contacts_table_wrap');
    const badge = document.getElementById('view_contacts_count_badge');
    wrap.innerHTML = '<div style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>';
    badge.textContent = '…';

    fetch('company_list.php?get_contacts=1&company_id=' + encodeURIComponent(data.id))
        .then(r => r.json())
        .then(contacts => {
            badge.textContent = contacts.length;
            if (contacts.length === 0) {
                wrap.innerHTML = '<div class="sub-contacts-no"><i class="fa-solid fa-user-slash" style="margin-right:6px;"></i>No contacts linked to this company yet.</div>';
                return;
            }
            let rows = contacts.map(c => {
                const desig = c.designation ? `<span class="pill pill-yellow">${c.designation}</span>` : '<span style="color:#9ca3af;">—</span>';
                const emailCell = c.email ? `<a href="mailto:${c.email}" style="color:#3b82f6;text-decoration:none;">${c.email}</a>` : '<span style="color:#9ca3af;">—</span>';
                const phoneCell = c.phone ? `<a href="tel:${c.phone}" style="color:#374151;text-decoration:none;">${c.phone}</a>` : '<span style="color:#9ca3af;">—</span>';
                return `<tr>
                    <td><b>${c.name}</b></td>
                    <td>${emailCell}</td>
                    <td>${phoneCell}</td>
                    <td>${desig}</td>
                    <td><a href="client_profile.php?id=${c.id}" style="display:inline-flex;align-items:center;gap:4px;background:#60a5fa;color:#fff;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;"><i class="fa-regular fa-eye" style="font-size:10px;"></i> View</a></td>
                </tr>`;
            }).join('');
            wrap.innerHTML = `<div style="border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">
                <table class="sub-contacts-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Designation</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
        })
        .catch(() => {
            wrap.innerHTML = '<div class="sub-contacts-no">Could not load contacts.</div>';
        });

    openModal('viewCompanyModal');
}

function switchViewToEdit() {
    closeModal('viewCompanyModal');
    if (_currentCompany) openEditModal(_currentCompany);
}

// ── EDIT modal ─────────────────────────────────────────────────────────────
function openEditModal(data) {
    _currentCompany = data;
    document.getElementById('edit_company_id').value    = data.id            || '';
    document.getElementById('edit_company_name').value  = data.company_name  || '';

    // Set the select to the current agent (fallback to Unassigned)
    const sel = document.getElementById('edit_assigned_agent');
    sel.value = data.assigned_agent || 'Unassigned';
    if (sel.value !== (data.assigned_agent || 'Unassigned')) sel.value = 'Unassigned';

    openModal('editCompanyModal');
}

// ── ADD COMPANY wizard ─────────────────────────────────────────────────────
let compCurrentStep = 1;
const compTotalSteps = 3;

function compUpdateUI() {
    for (let i = 1; i <= compTotalSteps; i++)
        document.getElementById('compStepBody' + i).classList.toggle('comp-step-active', i === compCurrentStep);

    const circles = [document.getElementById('compStep1'), document.getElementById('compStep2'), document.getElementById('compStep3')];
    const labels  = [document.getElementById('compLbl1'),  document.getElementById('compLbl2'),  document.getElementById('compLbl3')];
    circles.forEach((c, idx) => {
        c.classList.remove('active','completed');
        labels[idx].classList.remove('active-lbl');
        if      (idx + 1 <  compCurrentStep) { c.classList.add('completed'); c.innerHTML = '<i class="fa-solid fa-check"></i>'; }
        else if (idx + 1 === compCurrentStep) { c.classList.add('active');    c.innerHTML = idx + 1; labels[idx].classList.add('active-lbl'); }
        else                                  { c.innerHTML = idx + 1; }
    });

    document.getElementById('compBtnCancel').style.display = compCurrentStep === 1 ? 'inline-block' : 'none';
    document.getElementById('compBtnBack').style.display   = compCurrentStep > 1   ? 'inline-block' : 'none';
    document.getElementById('compBtnNext').style.display   = compCurrentStep < compTotalSteps ? 'flex' : 'none';
    document.getElementById('compBtnSave').style.display   = compCurrentStep === compTotalSteps ? 'flex' : 'none';
}

function compValidateStep() {
    if (compCurrentStep === 1) {
        const n = document.getElementById('comp_name_input');
        if (!n.value.trim()) {
            n.style.boxShadow = 'inset 0 0 0 1.5px #ef4444';
            showToast('Company Name is required!', 'error');
            return false;
        }
        n.style.boxShadow = '';
    }
    return true;
}

function compNextStep() { if (!compValidateStep()) return; if (compCurrentStep < compTotalSteps) { compCurrentStep++; compUpdateUI(); } }
function compPrevStep() { if (compCurrentStep > 1) { compCurrentStep--; compUpdateUI(); } }

function compResetWizard() {
    compCurrentStep = 1;
    const f = document.getElementById('addCompanyForm');
    if (f) { f.reset(); f.querySelectorAll('input,select').forEach(el => el.style.boxShadow = ''); }
    compUpdateUI();
}

function closeCompanyModal() { closeModal('addCompanyModal'); compResetWizard(); }

const _origOpen = openModal;
openModal = function(id) { if (id === 'addCompanyModal') compResetWizard(); _origOpen(id); };

// ── Live search ────────────────────────────────────────────────────────────
function searchTable() {
    const q = document.getElementById('compSearchInput').value.toLowerCase();
    document.querySelectorAll('#companyTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// ── Select all checkboxes ──────────────────────────────────────────────────
function toggleAll(master) {
    document.querySelectorAll('.tbl-checkbox').forEach(cb => cb.checked = master.checked);
}

// ── Toast ──────────────────────────────────────────────────────────────────
function showToast(msg, type) {
    const t = document.getElementById('toastBox');
    document.getElementById('toastMsg').innerText = msg;
    t.className = 'show ' + type;
    document.getElementById('toastIcon').className = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark';
    setTimeout(() => t.className = t.className.replace('show',''), 3500);
}

// ── Confirm delete ─────────────────────────────────────────────────────────
function confirmDelete(formId, typeName) {
    const dark = document.body.classList.contains('dark-mode');
    Swal.fire({
        title:'Are you sure?', text:"This action cannot be undone!", icon:'warning',
        showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#6b7280',
        confirmButtonText:'Yes, delete!', background: dark ? '#1e293b' : '#fff', color: dark ? '#f8fafc' : '#111827'
    }).then(r => { if (r.isConfirmed) document.getElementById(formId).submit(); });
}

// ── Show toast on page load if PHP message ─────────────────────────────────
window.onload = function() {
    <?php if ($toastMessage): ?>
    showToast("<?php echo addslashes($toastMessage); ?>", "<?php echo $toastType; ?>");
    <?php endif; ?>
};
</script>
</body>
</html>