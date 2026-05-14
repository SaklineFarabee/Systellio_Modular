<<<<<<< Updated upstream
<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================

// Start the session to manage user login states
session_start();

// Include database connection file (suppressed errors with @ if file is missing during tests)
@include 'config.php'; 

// ========================================================================
// CSV EXPORT LOGIC FOR COMPANIES (MUST BE BEFORE ANY HTML OUTPUT)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_companies_csv'])) {
    if (isset($conn)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=companies_export_' . date('Y-m-d') . '.csv');
        $output = fopen("php://output", "w");
        fputcsv($output, array('ID', 'Company Name', 'Assigned Agent', 'Total Contacts'));
        
        $query = mysqli_query($conn, "SELECT * FROM companies ORDER BY id DESC");
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                fputcsv($output, array($row['id'], $row['company_name'], $row['assigned_agent'], $row['total_contacts']));
            }
        }
        fclose($output);
        exit(); // Stop script execution so HTML is not added to CSV
    }
}

// Security Check: If the user is not logged in OR is not a 'super_admin', redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}

// Variables to hold Toast Notification messages and types (success/error)
$toastMessage = "";
$toastType = "";
$activeTabAfterSubmit = "users"; // Determines which tab stays open after page reload

// ========================================================================
// 2. USER MANAGEMENT LOGIC (CREATE, UPDATE, DELETE)
// ========================================================================

// A. CREATE NEW USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? ''); 
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
        $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
        $raw_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if($raw_password !== $confirm_password) {
            $toastMessage = "Passwords do not match!";
            $toastType = "error";
        } else {
            $password = password_hash($raw_password, PASSWORD_DEFAULT); 
            $status = 'active'; 
            $insert_sql = "INSERT INTO users (name, username, email, password, role, designation, status) VALUES ('$name', '$username', '$email', '$password', '$role', '$designation', '$status')";
            try {
                if(mysqli_query($conn, $insert_sql)){
                    $toastMessage = "User created successfully!";
                    $toastType = "success";
                }
            } catch (mysqli_sql_exception $e) {
                $toastMessage = "Database Error! Ensure all columns exist.";
                $toastType = "error";
            }
        }
    }
}

// B. UPDATE/EDIT EXISTING USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $id = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
        $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active'); 
        $raw_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if(!empty($raw_password) && $raw_password !== $confirm_password) {
            $toastMessage = "Passwords do not match! User not updated.";
            $toastType = "error";
        } else {
            $update_sql = "UPDATE users SET name='$name', username='$username', email='$email', role='$role', designation='$designation', status='$status'";
            if (!empty($raw_password)) {
                $new_password = password_hash($raw_password, PASSWORD_DEFAULT);
                $update_sql .= ", password='$new_password'";
            }
            $update_sql .= " WHERE id='$id'";
            try {
                if(mysqli_query($conn, $update_sql)){
                    $toastMessage = "User updated successfully!";
                    $toastType = "success";
                }
            } catch (mysqli_sql_exception $e) {
                $toastMessage = "Database Error! Could not update user.";
                $toastType = "error";
            }
        }
    }
}

// C. DELETE USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_user_id'] ?? '');
        $delete_sql = "DELETE FROM users WHERE id='$del_id'";
        try {
            if(mysqli_query($conn, $delete_sql)){
                $toastMessage = "User deleted successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Error deleting user!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 3. DESIGNATION MANAGEMENT LOGIC
// ========================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $designation_title = mysqli_real_escape_string($conn, $_POST['designation_title'] ?? '');
        $insert_desig = "INSERT INTO designations (title) VALUES ('$designation_title')";
        try {
            if(mysqli_query($conn, $insert_desig)){
                $toastMessage = "Designation added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Error adding designation!";
            $toastType = "error";
        }
    }
    
    if (isset($_POST['update_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $desig_id = mysqli_real_escape_string($conn, $_POST['desig_id'] ?? '');
        $designation_title = mysqli_real_escape_string($conn, $_POST['designation_title'] ?? '');
        $update_desig = "UPDATE designations SET title='$designation_title' WHERE id='$desig_id'";
        if(mysqli_query($conn, $update_desig)){
            $toastMessage = "Designation updated successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error updating designation!";
            $toastType = "error";
        }
    }

    if (isset($_POST['delete_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $desig_id = mysqli_real_escape_string($conn, $_POST['desig_id'] ?? '');
        $delete_desig = "DELETE FROM designations WHERE id='$desig_id'";
        if(mysqli_query($conn, $delete_desig)){
            $toastMessage = "Designation deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting designation!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 4. TASK MANAGEMENT LOGIC
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_task'])) {
    $activeTabAfterSubmit = "tasks";
    if(isset($conn)){
        $task_title = mysqli_real_escape_string($conn, $_POST['task_title'] ?? '');
        $task_description = mysqli_real_escape_string($conn, $_POST['task_description'] ?? '');
        $assigned_to = mysqli_real_escape_string($conn, $_POST['assigned_to'] ?? 'Unassigned'); 
        $priority = 'Medium';
        $status = 'To-Do';
        $due_date = date('Y-m-d', strtotime('+7 days'));

        $insert_task_sql = "INSERT INTO tasks (title, description, assigned_to, priority, status, due_date) VALUES ('$task_title', '$task_description', '$assigned_to', '$priority', '$status', '$due_date')";
        
        try {
            if(mysqli_query($conn, $insert_task_sql)){
                $toastMessage = "Task created successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Make sure 'tasks' table exists.";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 5. COMPANY & ORGANIZATION LOGIC (CREATE, BULK UPLOAD, DELETE)
// ========================================================================

// A. Create Single Company
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_company'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn)){
        $comp_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
        $assigned_agent = mysqli_real_escape_string($conn, $_POST['assigned_agent'] ?? 'Unassigned');
        $total_contacts = (int)($_POST['total_contacts'] ?? 0);

        $insert_sql = "INSERT INTO companies (company_name, assigned_agent, total_contacts) VALUES ('$comp_name', '$assigned_agent', '$total_contacts')";
        try {
            if(mysqli_query($conn, $insert_sql)){
                $toastMessage = "Company added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Create 'companies' table.";
            $toastType = "error";
        }
    }
}

// B. Bulk Upload Companies via CSV
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload_companies'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn) && isset($_FILES['company_csv']) && $_FILES['company_csv']['error'] == 0){
        $file = $_FILES['company_csv']['tmp_name'];
        $handle = fopen($file, "r");
        $row_count = 0;
        
        try {
            while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
                $row_count++;
                if($row_count == 1) continue; // Skip header row
                
                $c_name = mysqli_real_escape_string($conn, $data[0] ?? '');
                $c_agent = mysqli_real_escape_string($conn, $data[1] ?? 'Unassigned');
                $c_contacts = (int)($data[2] ?? 0);
                
                if(!empty($c_name)){
                    mysqli_query($conn, "INSERT INTO companies (company_name, assigned_agent, total_contacts) VALUES ('$c_name', '$c_agent', '$c_contacts')");
                }
            }
            fclose($handle);
            $toastMessage = "CSV uploaded! Added " . ($row_count - 1) . " companies.";
            $toastType = "success";
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Upload Failed! Check CSV format & DB Table.";
            $toastType = "error";
        }
    } else {
        $toastMessage = "Please select a valid CSV file.";
        $toastType = "error";
    }
}

// C. Delete Company
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_company'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_company_id'] ?? '');
        $delete_sql = "DELETE FROM companies WHERE id='$del_id'";
        if(mysqli_query($conn, $delete_sql)){
            $toastMessage = "Company deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting company!";
            $toastType = "error";
        }
    }
}


// ========================================================================
// 6. FETCH DATA FOR UI (Designations, Users, Tasks, Companies)
// ========================================================================

$designationsList = ""; 
$designationTableRows = ""; 
$assigneeOptions = ""; 

if(isset($conn)){
    // Fetch Designations
    try {
        $desig_query = mysqli_query($conn, "SELECT * FROM designations ORDER BY title ASC");
        if($desig_query && mysqli_num_rows($desig_query) > 0){
            while($row = mysqli_fetch_assoc($desig_query)){
                $designationsList .= "<option value='{$row['title']}'>{$row['title']}</option>";
                $desigData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $designationTableRows .= "
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; font-weight: 500;' class='desig-text'>{$row['title']}</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right;'>
                            <button type='button' style='background:none; border:none; color:#3b82f6; cursor:pointer; margin-right:15px; font-size:14px;' onclick='openEditDesignationModal({$desigData})'><i class='fa-solid fa-pen-to-square'></i></button>
                            <form method='POST' id='delete-desig-{$row['id']}' style='display:inline;'>
                                <input type='hidden' name='desig_id' value='{$row['id']}'>
                                <input type='hidden' name='delete_designation' value='1'>
                                <button type='button' onclick='confirmDelete(\"delete-desig-{$row['id']}\", \"designation\")' style='background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px;'><i class='fa-solid fa-trash'></i></button>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
            $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center; color:#6b7280;'>No designations found.</td></tr>";
        }
    } catch (mysqli_sql_exception $e) {
        $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
        $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center; color:#ef4444;'>Table 'designations' not found.</td></tr>";
    }

    // Fetch Users
    try {
        $user_query = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
        if($user_query && mysqli_num_rows($user_query) > 0){
            while($uRow = mysqli_fetch_assoc($user_query)){
                $userIdStr = "038H" . $uRow['id'];
                $assigneeOptions .= "<option value='{$uRow['name']}'>{$uRow['name']} ({$userIdStr})</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}

} else {
    $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
    $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center;'>No DB Connection</td></tr>";
}


// Fetch Tasks Variables
$hasTasks = false;
$taskTableRows = "";
$totalTasks = "1,284";
$pendingTasks = "43";
$overdueTasks = "07";
$completedTasks = "18";

// Fetch Companies Variables
$hasCompanies = false;
$companyTableRows = "";
$totalCompanies = "4";

if(isset($conn)){
    // Fetch Tasks Data
    try {
        $task_query = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id DESC");
        if($task_query && mysqli_num_rows($task_query) > 0){
            $hasTasks = true;
            $totalTasks = mysqli_num_rows($task_query);
            $pendingTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status LIKE '%To-Do%' OR status LIKE '%Progress%'"))['count'] ?? 0;
            $overdueTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status = 'Overdue'"))['count'] ?? 0;
            $completedTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status = 'Done'"))['count'] ?? 0;
            
            while($row = mysqli_fetch_assoc($task_query)){
                $title = htmlspecialchars($row['title']);
                $desc = htmlspecialchars(mb_substr($row['description'], 0, 40)) . '...';
                $date = date('M d, Y', strtotime($row['due_date']));
                $assigned = htmlspecialchars($row['assigned_to']);
                
                $avatar_initials = strtoupper(substr($assigned, 0, 1)); 
                if(strpos($assigned, ' ') !== false){
                    $parts = explode(' ', $assigned);
                    $avatar_initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                }

                $pri = strtolower($row['priority']);
                $pri_class = 'medium';
                if($pri == 'high') $pri_class = 'high';
                if($pri == 'low') $pri_class = 'low';
                if($pri == 'emergency') $pri_class = 'emergency';

                $stat = strtolower($row['status']);
                $stat_class = 'todo';
                $stat_display = 'To-Do';
                if(strpos($stat, 'progress') !== false) { $stat_class = 'progress'; $stat_display = 'In-Progress'; }
                if($stat == 'done') { $stat_class = 'done'; $stat_display = 'Done'; }
                if($stat == 'overdue') { $stat_class = 'over'; $stat_display = 'Overdue'; }

                $taskTableRows .= "<tr>
                    <td><div class='tt-name'>{$title}</div><div class='tt-desc'>{$desc}</div></td>
                    <td><div class='tt-assignee'><div class='avatar'>{$avatar_initials}</div><span>{$assigned}</span></div></td>
                    <td><span class='pill {$pri_class}'>" . strtoupper($row['priority']) . "</span></td>
                    <td><span class='status-dot {$stat_class}'></span> <span class='status-text-t {$stat_class}'>{$stat_display}</span></td>
                    <td><span class='date-text'>{$date}</span></td>
                    <td style='width: 130px; text-align:right;'><button class='tt-action-btn'>Edit & Manage Task</button><button class='tt-rule-btn'>Task Rules & Auth</button></td>
                </tr>";
            }
        }
    } catch(mysqli_sql_exception $e) {}

    // Fetch Companies Data
    try {
        $comp_query = mysqli_query($conn, "SELECT * FROM companies ORDER BY id DESC");
        if($comp_query && mysqli_num_rows($comp_query) > 0){
            $hasCompanies = true;
            $totalCompanies = mysqli_num_rows($comp_query);
            
            while($row = mysqli_fetch_assoc($comp_query)){
                $c_name = htmlspecialchars($row['company_name']);
                $c_agent = htmlspecialchars($row['assigned_agent']);
                $c_contacts = htmlspecialchars($row['total_contacts']);
                $c_id = $row['id'];
                
                $companyTableRows .= "<tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><span class='comp-name'>{$c_name}</span></td>
                    <td><div class='comp-agent'><i class='fa-solid fa-user'></i><span>{$c_agent}</span></div></td>
                    <td><span class='comp-contacts-pill'>{$c_contacts} Contacts</span></td>
                    <td style='text-align: right;'>
                        <button class='comp-icon-btn view' title='View' onclick=\"showToast('View Feature Coming Soon','success')\"><i class='fa-regular fa-eye'></i></button>
                        <form method='POST' id='delete-comp-{$c_id}' style='display:inline;'>
                            <input type='hidden' name='delete_company_id' value='{$c_id}'>
                            <input type='hidden' name='delete_company' value='1'>
                            <button type='button' class='comp-icon-btn delete' onclick='confirmDelete(\"delete-comp-{$c_id}\", \"company\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                        </form>
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
    <title>Super Admin Dashboard - Systellio CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ========================================================================
           GLOBAL STYLES & RESET
        ======================================================================== */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; height: 100vh; overflow: hidden; transition: background-color 0.3s, color 0.3s; color: #111827; }

        /* ========================================================================
           TOAST NOTIFICATION STYLES
        ======================================================================== */
        #toastBox { visibility: hidden; min-width: 250px; background-color: #333; color: #fff; text-align: center; border-radius: 8px; padding: 16px; position: fixed; z-index: 9999; right: 30px; top: 30px; font-size: 14px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px; transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55), visibility 0.4s; }
        #toastBox.show { visibility: visible; transform: translateX(0); }
        #toastBox.success { background-color: #10b981; }
        #toastBox.error { background-color: #ef4444; }

        /* ========================================================================
           SIDEBAR STYLES
        ======================================================================== */
        .sidebar { width: 260px; background-color: #0b1524; color: #ffffff; display: flex; flex-direction: column; transition: margin-left 0.3s ease; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; }
        .sidebar.collapsed { margin-left: -260px; }
        .sidebar-header { padding: 25px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .sidebar-logo { width: 100px; height: auto; margin-bottom: 10px; }
        .brand-role { font-size: 10px; font-weight: 700; color: #60a5fa; letter-spacing: 1.5px; text-transform: uppercase; }
        
        .sidebar-menu { list-style: none; padding: 10px 0; flex-grow: 1; overflow-y: auto; }
        .sidebar-menu > li { padding: 14px 20px 14px 21px; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.3s; color: #94a3b8; border-left: 3px solid transparent; }
        .sidebar-menu > li:hover { background-color: #162235; color: #ffffff; }
        .sidebar-menu > li i { font-size: 15px; width: 20px; text-align: center; }
        .sidebar-menu > li a { color: inherit; text-decoration: none; font-size: 13px; font-weight: 500; width: 100%; }

        /* Active Sidebar Indicator Styles */
        .sidebar-menu > li.active { background-color: #1e293b; color: #3b82f6; border-left: 3px solid #3b82f6; }
        .sidebar-menu > li.active i { color: #3b82f6; }
        .dropdown-title.active-main { color: #3b82f6; }
        .dropdown-title.active-main i { color: #3b82f6; }
        .submenu li.active-sub a { color: #3b82f6; font-weight: 600; }
        .submenu li.active-sub { position: relative; }
        .submenu li.active-sub::before { content: ""; position: absolute; left: 35px; top: 50%; transform: translateY(-50%); width: 6px; height: 6px; background-color: #3b82f6; border-radius: 50%; }

        /* Sidebar Dropdown & Sub-menu Styles */
        .dropdown-item { padding: 0 !important; display: block !important; border-left: none !important; }
        .dropdown-title { padding: 14px 20px 14px 24px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; color: #94a3b8; transition: 0.3s; border-left: 3px solid transparent; }
        .dropdown-title:hover { background-color: #162235; color: #ffffff; }
        .dropdown-title-left { display: flex; align-items: center; gap: 15px; }
        .dropdown-title-left i { font-size: 15px; width: 20px; text-align: center; }
        .dropdown-title-left span { font-size: 13px; font-weight: 500; }
        .dropdown-icon { font-size: 10px !important; transition: transform 0.3s ease; }
        
        .submenu { list-style: none; display: none; background-color: #0b1524; padding-top: 5px; padding-bottom: 10px; }
        .submenu li { padding: 10px 20px 10px 59px !important; border-left: none !important; background-color: transparent !important; }
        .submenu li a { color: #64748b; text-decoration: none; font-size: 12px; transition: 0.3s; cursor: pointer; }
        .submenu li a:hover { color: #ffffff; }
        .dropdown-item.open .submenu { display: block; }
        .dropdown-item.open .dropdown-icon { transform: rotate(180deg); }

        /* ========================================================================
           MAIN CONTENT & TOP NAVBAR STYLES
        ======================================================================== */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto; transition: background-color 0.3s ease; background-color: #f3f4f6; }
        .top-navbar { background-color: #ffffff; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.03); transition: 0.3s; }
        .toggle-btn { font-size: 20px; color: #4b5563; cursor: pointer; transition: color 0.3s; }
        .toggle-btn:hover { color: #111827; }
        
        /* Navbar Actions (Icons & Profile) */
        .navbar-actions { display: flex; align-items: center; gap: 20px; }
        .nav-icon-btn { cursor: pointer; font-size: 20px; color: #6b7280; transition: 0.3s; position: relative; }
        .nav-icon-btn:hover { color: #3b82f6; }
        
        /* Notification Badge */
        .notification-badge { position: absolute; top: -4px; right: -4px; background-color: #ef4444; color: white; font-size: 9px; font-weight: bold; padding: 2px 5px; border-radius: 50%; border: 2px solid #ffffff; }
        body.dark-mode .notification-badge { border-color: #1e293b; } /* Dark mode border fix */

        .user-profile { display: flex; align-items: center; gap: 10px; font-weight: 600; color: inherit; font-size: 14px; }
        .user-profile i { font-size: 24px; color: #3b82f6; }

        /* Overview Dashboard Cards */
        .dashboard-container, .task-container, .company-container { padding: 30px; }
        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; transition: 0.3s;}
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.03); display: flex; align-items: center; justify-content: space-between; border: 1px solid #e5e7eb; transition: 0.3s;}
        .card-info h4 { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .card-info h2 { font-size: 28px; transition: 0.3s;}
        .card-icon { background-color: #eff6ff; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; color: #3b82f6; transition: 0.3s;}

        /* ========================================================================
           USER LIST SECTION & TABLE STYLES
        ======================================================================== */
        #userListSection { display: none; padding: 30px; }
        .user-list-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .user-list-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s;}
        .user-list-title p { font-size: 11px; color: #6b7280; font-weight: 500; }
        
        .header-buttons { display: flex; gap: 10px; }
        .create-btn { background-color: #000000; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;}
        .create-btn:hover { background-color: #1f2937; }
        .desig-btn { background-color: #3b82f6; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;}
        .desig-btn:hover { background-color: #2563eb; }

        /* Filtering Tabs */
        .tabs-wrapper { margin-bottom: 20px; width: max-content; }
        .tab-top-line { height: 3px; width: 100%; background: linear-gradient(to right, #3b82f6 33%, #ef4444 33%, #ef4444 66%, #d1d5db 66%); border-radius: 3px 3px 0 0; }
        .tabs-container { display: flex; background: #ffffff; padding: 5px; border-radius: 0 0 6px 6px; gap: 5px; transition: 0.3s; border: 1px solid #e5e7eb; border-top: none;}
        .tab-btn { padding: 8px 18px; font-size: 12px; font-weight: 700; border: none; background: transparent; cursor: pointer; border-radius: 4px; color: #6b7280; display: flex; align-items: center; gap: 6px; transition: 0.3s;}
        .tab-btn.active { background: #f3f4f6; color: #111827; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Data Table */
        .table-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db; transition: 0.3s; background: #ffffff;}
        .custom-table { width: 100%; border-collapse: collapse; text-align: center; font-size: 12px; }
        .custom-table th { background-color: #c4f042; padding: 14px 10px; font-weight: 700; color: #000000; border-bottom: 1px solid #d1d5db; transition: 0.3s;}
        .custom-table td { padding: 14px 10px; color: #374151; font-weight: 500; vertical-align: middle; border-right: 1px solid rgba(0,0,0,0.05); transition: 0.3s;}
        .custom-table td:last-child { border-right: none; }

        /* Pastel Row Styling */
        .custom-table tbody tr:nth-child(4n+1) { background-color: #e6fced; } 
        .custom-table tbody tr:nth-child(4n+2) { background-color: #fcedf6; } 
        .custom-table tbody tr:nth-child(4n+3) { background-color: #fceddb; } 
        .custom-table tbody tr:nth-child(4n+4) { background-color: #e6edff; } 

        .status-text { font-weight: 600; }
        .action-btns { display: flex; justify-content: center; gap: 6px; }
        .btn-view { background-color: #60a5fa; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-view:hover { background-color: #3b82f6; }
        .btn-edit { background-color: #4ade80; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-edit:hover { background-color: #22c55e; }
        .btn-delete { background-color: #f87171; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-delete:hover { background-color: #ef4444; }


        /* ========================================================================
           COMPANY & ORGANIZATION STYLES (NEW DESIGN)
        ======================================================================== */
        #companySection { display: none; }
        
        .comp-header-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s; color: #111827;}
        .comp-header-title p { font-size: 13px; color: #6b7280; font-weight: 500; }
        
        /* Company Header Buttons */
        .btn-export { background-color: #10b981; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-export:hover { background-color: #059669; }
        
        .btn-upload { background-color: #1e293b; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-upload:hover { background-color: #0f172a; }
        
        .btn-add-client { background-color: #3b82f6; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-add-client:hover { background-color: #2563eb; }

        /* Company Search Bar Area */
        .comp-table-wrapper { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 25px; transition: 0.3s;}
        
        .comp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .comp-search { position: relative; width: 300px; }
        .comp-search i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;}
        .comp-search input { width: 100%; padding: 10px 15px 10px 38px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: 0.3s; color: #374151;}
        .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #eff6ff;}
        
        .comp-total { font-size: 13px; font-weight: 600; color: #4b5563; background: #ffffff; border: 1px solid #d1d5db; padding: 8px 15px; border-radius: 20px;}

        /* Company Table Styles */
        .comp-table { width: 100%; border-collapse: collapse; text-align: left; }
        .comp-table th { font-size: 11px; text-transform: uppercase; color: #4b5563; letter-spacing: 0.5px; padding: 15px; border-bottom: 1px solid #e5e7eb; font-weight: 700;}
        .comp-table td { padding: 18px 15px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; transition: 0.3s;}
        
        .comp-table th:first-child, .comp-table td:first-child { width: 50px; text-align: center; }
        
        /* Generic checkbox styling for table */
        .tbl-checkbox { width: 16px; height: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; accent-color: #3b82f6;}
        
        .comp-name { font-size: 14px; font-weight: 700; color: #111827; }
        
        .comp-agent { display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 13px; font-weight: 500;}
        .comp-agent i { color: #9ca3af; }
        
        .comp-contacts-pill { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; display: inline-block;}
        
        /* View / Delete Icon Buttons */
        .comp-icon-btn { width: 32px; height: 32px; border-radius: 4px; border: 1px solid #e5e7eb; background: transparent; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; transition: 0.3s; margin-right: 5px;}
        .comp-icon-btn.view { color: #3b82f6; }
        .comp-icon-btn.view:hover { background: #eff6ff; border-color: #3b82f6; }
        .comp-icon-btn.delete { color: #ef4444; }
        .comp-icon-btn.delete:hover { background: #fef2f2; border-color: #ef4444; }

        /* ========================================================================
           TASK MANAGEMENT STYLES
        ======================================================================== */
        #taskManagementSection { display: none; }
        
        .task-header-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s; color: #111827;}
        .task-header-title p { font-size: 13px; color: #6b7280; font-weight: 500; }

        /* Statistics Cards */
        .task-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 25px; margin-bottom: 30px;}
        .task-card { background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 10px rgba(0,0,0,0.02); border-left: 4px solid #3b82f6; display: flex; flex-direction: column; justify-content: space-between; transition: 0.3s;}
        .task-card.pending { border-left-color: #f59e0b; }
        .task-card.overdue { border-left-color: #ef4444; }
        .task-card.completed { border-left-color: #10b981; }

        .tc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .tc-header h4 { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .tc-header i { font-size: 16px; color: #9ca3af; }
        
        .tc-body h2 { font-size: 32px; color: #111827; font-weight: 800; line-height: 1; margin-bottom: 8px; transition: 0.3s;}
        .tc-body p { font-size: 11px; color: #10b981; font-weight: 600; }
        .tc-body p.warn { color: #9ca3af; }
        .tc-body p.danger { color: #ef4444; }

        /* Task Table Area */
        .task-table-wrapper { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: 0.3s;}
        
        .tt-controls { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 15px; transition: 0.3s;}
        .tt-tabs { display: flex; gap: 20px; }
        .tt-tab { font-size: 14px; font-weight: 600; color: #6b7280; cursor: pointer; position: relative; padding-bottom: 15px; transition: 0.3s;}
        .tt-tab.active { color: #3b82f6; }
        .tt-tab.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 3px; background: #3b82f6; border-radius: 3px 3px 0 0; }
        
        .tt-actions { display: flex; gap: 15px; font-size: 12px; font-weight: 600; color: #6b7280; transition: 0.3s;}
        .tt-actions span { cursor: pointer; display: flex; align-items: center; gap: 5px; transition: 0.3s;}
        .tt-actions span:hover { color: #111827; }

        .task-table { width: 100%; border-collapse: collapse; text-align: left; }
        .task-table th { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; padding: 15px 10px; border-bottom: 1px solid #e5e7eb; font-weight: 700; background: #f9fafb; transition: 0.3s;}
        .task-table td { padding: 15px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; transition: 0.3s;}
        
        .tt-name { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 4px; transition: 0.3s;}
        .tt-desc { font-size: 12px; color: #9ca3af; transition: 0.3s;}
        
        .tt-assignee { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #3b82f6; display: flex; justify-content: center; align-items: center; font-size: 11px; font-weight: 700; }
        .tt-assignee span { font-size: 13px; font-weight: 600; color: #374151; transition: 0.3s;}

        .pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .pill.high { background: #fee2e2; color: #ef4444; }
        .pill.medium { background: #fef3c7; color: #f59e0b; }
        .pill.low { background: #d1fae5; color: #10b981; }
        .pill.emergency { background: #ef4444; color: #ffffff; }

        .status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot.progress { background: #3b82f6; }
        .status-dot.todo { background: #9ca3af; }
        .status-dot.done { background: #10b981; }
        .status-dot.over { background: #ef4444; }

        .status-text-t { font-size: 12px; font-weight: 600; }
        .status-text-t.progress { color: #3b82f6; }
        .status-text-t.todo { color: #6b7280; }
        .status-text-t.done { color: #10b981; }
        .status-text-t.over { color: #ef4444; }

        .date-text { font-size: 13px; font-weight: 600; color: #6b7280; }
        .date-text.overdue { color: #ef4444; }

        /* Task Action Buttons */
        .tt-action-btn { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: block; margin-bottom: 5px; width: 100%; transition: 0.3s;}
        .tt-action-btn:hover { background: #bbf7d0; }
        .tt-rule-btn { background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: block; width: 100%; transition: 0.3s;}
        .tt-rule-btn:hover { background: #fecaca; }

        /* Pagination Styling */
        .pagination { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; font-size: 12px; color: #6b7280; }
        .page-numbers { display: flex; gap: 5px; }
        .page-no { width: 28px; height: 28px; display: flex; justify-content: center; align-items: center; border-radius: 4px; cursor: pointer; transition: 0.3s;}
        .page-no:hover { background: #f3f4f6; }
        .page-no.active { background: #1e3a8a; color: white; }
        body.dark-mode .page-no:hover { background: #334155; }

        /* ========================================================================
           GENERAL MODALS (POPUPS) STYLES
        ======================================================================== */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 10px; width: 100%; max-width: 650px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; transition: 0.3s;}
        .small-modal { max-width: 450px; }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; font-weight: 700; transition: 0.3s;}
        .close-btn { font-size: 20px; cursor: pointer; color: #6b7280; border: none; background: none; transition: 0.3s;}
        .close-btn:hover { color: #ef4444; }

        /* Form Grid within General Modals */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 5px; position: relative; } 
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; transition: 0.3s;}
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; font-family: 'Inter', sans-serif; background-color: #f9fafb; transition: 0.3s;}
        .form-group input[type="file"] { background: transparent; border: none; padding: 5px 0;}
        .form-group input:focus, .form-group select:focus { border-color: #3b82f6; background-color: #fff; }
        
        /* Show/Hide Password Toggle Style */
        .password-toggle { position: absolute; right: 12px; top: 32px; cursor: pointer; color: #6b7280; }
        .password-error { color: #ef4444; font-size: 10px; font-weight: 600; margin-top: 4px; display: none; }

        .submit-btn { background-color: #000000; color: #ffffff; padding: 12px; border: none; border-radius: 6px; width: 100%; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background-color: #1f2937; }
        .submit-btn:disabled { background-color: #9ca3af; cursor: not-allowed; }

        /* Read-only data box for View Modal */
        .view-data-box { background: #f9fafb; padding: 10px 12px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; font-weight: 500; word-break: break-all; min-height: 40px; display: flex; align-items: center; transition: 0.3s;}
        .desig-text { color: #374151; }

        /* Coming Soon Fallback Page */
        .coming-soon-container { display: none; flex-direction: column; justify-content: center; align-items: center; height: 70vh; text-align: center; }
        .coming-soon-icon { font-size: 60px; color: #9ca3af; margin-bottom: 20px; }
        .coming-soon-title { font-size: 28px; font-weight: 700; margin-bottom: 10px; transition: 0.3s;}
        .coming-soon-text { color: #6b7280; font-size: 15px; max-width: 400px; }

        /* ========================================================================
           CUSTOM CREATE TASK MODAL STYLES (NEW - DARK THEME)
        ======================================================================== */
        .task-modal-content { background-color: #0b0f19 !important; color: #f8fafc; border: 1px solid #1e293b; max-width: 750px !important; padding: 25px 30px !important; border-radius: 12px !important; overflow: visible;}
        
        .task-modal-label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; color: #f8fafc; }
        .task-modal-label span.req { color: #ef4444; }
        
        /* Title Input */
        .task-input-dark { background-color: #0b0f19; border: 1px solid #3b82f6; color: #f8fafc; width: 100%; padding: 12px 15px; border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 0 0 1px #3b82f6; }
        .task-input-dark::placeholder { color: #475569; }
        
        /* Textarea Toolbar */
        .task-toolbar { display: flex; justify-content: space-between; align-items: center; background-color: #1a1d24; border: 1px solid #2d3748; border-bottom: none; border-radius: 8px 8px 0 0; padding-right: 15px;}
        .task-tab { background-color: #0d1117; color: #f8fafc; font-size: 13px; font-weight: 600; padding: 12px 20px; border-right: 1px solid #2d3748; border-radius: 8px 0 0 0; }
        .task-tools { display: flex; gap: 15px; color: #94a3b8; font-size: 14px; }
        .task-tools i { cursor: pointer; transition: 0.3s; }
        .task-tools i:hover { color: #f8fafc; }
        
        /* Textarea */
        .task-textarea { background-color: #0d1117; border: 1px solid #2d3748; color: #f8fafc; width: 100%; padding: 15px; border-radius: 0 0 8px 8px; font-size: 14px; outline: none; resize: vertical; min-height: 200px; margin-bottom: 20px;}
        .task-textarea::placeholder { color: #475569; }

        /* Footer Buttons Container */
        .task-modal-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px;}
        .task-footer-left { display: flex; gap: 10px; align-items: center; }
        
        .task-tag-wrapper { position: relative; display: inline-flex; align-items: center; background-color: #1e293b; border: 1px dashed #475569; border-radius: 6px; transition: 0.3s; height: 32px; cursor: pointer; }
        .task-tag-wrapper:hover { background-color: #334155; border-color: #64748b; }
        .task-tag-wrapper i { position: absolute; left: 10px; color: #94a3b8; font-size: 12px; pointer-events: none; transition: 0.3s; }
        .task-tag-wrapper:hover i { color: #f8fafc; }
        
        .task-tag-select { background: transparent !important; color: #cbd5e1 !important; border: none !important; padding: 0 26px 0 28px !important; font-size: 12px !important; font-weight: 500 !important; cursor: pointer; outline: none !important; font-family: 'Inter', sans-serif; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="%2394a3b8" viewBox="0 0 320 512"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"/></svg>') !important; background-repeat: no-repeat !important; background-position: right 10px center !important; height: 100%; }
        .task-tag-select:focus { color: #f8fafc !important; }
        .task-tag-select option { background-color: #1e293b; color: #f8fafc; }

        .task-tag-btn { background-color: #1e293b; color: #cbd5e1; border: 1px dashed #475569; padding: 0 14px 0 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; height: 32px; font-family: 'Inter', sans-serif; }
        .task-tag-btn i { color: #94a3b8; font-size: 12px; transition: 0.3s;}
        .task-tag-btn:hover { background-color: #334155; border-color: #64748b; color: #f8fafc; }
        .task-tag-btn:hover i { color: #f8fafc; }
        
        .task-footer-right { display: flex; align-items: center; gap: 15px; }
        
        .task-checkbox-label { font-size: 13px; color: #cbd5e1; display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500; user-select: none; }
        .task-checkbox { appearance: none; -webkit-appearance: none; width: 16px; height: 16px; background-color: transparent; border: 2px solid #475569; border-radius: 4px; cursor: pointer; position: relative; transition: all 0.2s ease; margin: 0; }
        .task-checkbox:checked { background-color: #16a34a; border-color: #16a34a; }
        .task-checkbox:checked::after { content: ''; position: absolute; left: 4px; top: 1px; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }
        .task-checkbox-label:hover .task-checkbox:not(:checked) { border-color: #64748b; }
        
        .task-cancel-btn { background: transparent; color: #f8fafc; border: none; font-size: 13px; font-weight: 600; cursor: pointer; padding: 8px 12px; border-radius: 6px; transition: 0.3s;}
        .task-cancel-btn:hover { background: #334155; }
        
        .task-create-btn { background: #16a34a; color: #ffffff; border: none; font-size: 13px; font-weight: 700; cursor: pointer; padding: 0 12px; border-radius: 6px; display: flex; align-items: center; height: 36px; transition: 0.3s;}
        .task-create-btn:hover { background: #15803d; }
        .task-create-btn .btn-divider { width: 1px; height: 18px; background-color: rgba(255,255,255,0.4); margin: 0 10px; }

        /* ========================================================================
           ADVANCED PREMIUM DARK MODE STYLES 
        ======================================================================== */
        body.dark-mode { background-color: #0f172a; color: #f8fafc; }
        body.dark-mode .main-content { background-color: #0f172a; }
        body.dark-mode .top-navbar { background-color: #1e293b; border-bottom: 1px solid #334155; box-shadow: none; }
        body.dark-mode .nav-icon-btn { color: #cbd5e1; }
        body.dark-mode .nav-icon-btn:hover { color: #f8fafc; }
        
        body.dark-mode .card, body.dark-mode .task-card, body.dark-mode .task-table-wrapper, body.dark-mode .comp-table-wrapper { background-color: #1e293b; border-color: #334155; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        body.dark-mode .card-icon { background-color: #334155; color: #3b82f6; }
        body.dark-mode .tc-body h2 { color: #f8fafc; }
        body.dark-mode .comp-header-title h1 { color: #f8fafc; }

        body.dark-mode .tabs-container { background: #1e293b; border-color: #334155; }
        body.dark-mode .tab-btn { color: #94a3b8; }
        body.dark-mode .tab-btn:hover { color: #f8fafc; }
        body.dark-mode .tab-btn.active { background: #0f172a; color: #f8fafc; }

        body.dark-mode .table-wrapper { border-color: #334155; background: #1e293b; }
        body.dark-mode .custom-table th, body.dark-mode .task-table th, body.dark-mode .comp-table th { background-color: #334155; color: #f8fafc; border-color: #475569; }
        body.dark-mode .custom-table td, body.dark-mode .task-table td, body.dark-mode .comp-table td { color: #cbd5e1; border-color: #334155; }
        body.dark-mode .tt-name, body.dark-mode .comp-name { color: #f8fafc; }
        body.dark-mode .tt-assignee span { color: #cbd5e1; }
        
        body.dark-mode .custom-table tbody tr:nth-child(even) { background-color: #1e293b; } 
        body.dark-mode .custom-table tbody tr:nth-child(odd) { background-color: #0f172a; } 
        body.dark-mode .custom-table tbody tr:hover, body.dark-mode .task-table tr:hover, body.dark-mode .comp-table tr:hover { background-color: #334155; }

        body.dark-mode .tt-controls { border-color: #334155; }
        body.dark-mode .tt-tab { color: #94a3b8; }
        body.dark-mode .tt-actions { color: #94a3b8; }
        body.dark-mode .tt-actions span:hover { color: #f8fafc; }
        body.dark-mode .page-no:hover { background: #334155; }

        body.dark-mode .comp-search input { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #1e3a8a;}
        body.dark-mode .comp-total { background-color: #0f172a; color: #cbd5e1; border-color: #334155; }
        body.dark-mode .comp-contacts-pill { background: #1e3a8a; border-color: #3b82f6; color: #93c5fd; }
        body.dark-mode .comp-icon-btn { border-color: #475569; color: #cbd5e1; }
        body.dark-mode .comp-icon-btn.view:hover { background: #1e3a8a; border-color: #3b82f6; color: #60a5fa;}
        body.dark-mode .comp-icon-btn.delete:hover { background: #7f1d1d; border-color: #ef4444; color: #fca5a5;}

        body.dark-mode .modal-content:not(.task-modal-content) { background-color: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        body.dark-mode .form-group label { color: #cbd5e1; }
        body.dark-mode .form-group input:not(.task-input-dark):not([type="file"]), 
        body.dark-mode .form-group select:not(.task-tag-select) { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .form-group input:focus:not(.task-input-dark):not([type="file"]), 
        body.dark-mode .form-group select:focus:not(.task-tag-select) { border-color: #3b82f6; background-color: #1e293b; }
        body.dark-mode .form-group input[type="file"] { color: #cbd5e1; }
        
        body.dark-mode .view-data-box { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .create-btn, body.dark-mode .submit-btn, body.dark-mode .desig-btn { background-color: #3b82f6; }
        body.dark-mode .create-btn:hover, body.dark-mode .submit-btn:hover, body.dark-mode .desig-btn:hover { background-color: #2563eb; }

        body.dark-mode .desig-text { color: #cbd5e1 !important; }
        
        /* SweetAlert Component Dark Mode Fix */
        .swal2-container { z-index: 9999 !important; }
        body.dark-mode .swal2-popup { background-color: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        body.dark-mode .swal2-title, body.dark-mode .swal2-html-container { color: #f8fafc; }
    </style>
</head>
<body>

    <div id="toastBox">
        <i id="toastIcon" class="fa-solid fa-circle-check"></i>
        <span id="toastMsg">Action Successful!</span>
    </div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="img/logo.png" alt="Systellio Logo" class="sidebar-logo">
            <span class="brand-role">SUPER ADMIN</span>
        </div>

        <ul class="sidebar-menu">
            <li class="active" onclick="showDashboard(this)">
                <i class="fa-solid fa-table-cells-large"></i>
                <a href="#">Dashboard</a>
            </li>

            <li class="dropdown-item" id="userMenu">
                <div class="dropdown-title" onclick="toggleSubMenu('userMenu', 'userListSection', this)">
                    <div class="dropdown-title-left"><i class="fa-solid fa-user-group"></i><span>User Management</span></div>
                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </div>
                <ul class="submenu">
                    <li onclick="showUserList(this)"><a href="#">User List</a></li>
                    <li onclick="showComingSoon('User Tasks', this)"><a href="#">User Tasks</a></li>
                    <li onclick="showComingSoon('User Activity', this)"><a href="#">User Activity</a></li>
                </ul>
            </li>

            <li class="dropdown-item" id="leadsMenu">
                <div class="dropdown-title" onclick="toggleSubMenu('leadsMenu', 'companySection', this)">
                    <div class="dropdown-title-left"><i class="fa-solid fa-briefcase"></i><span>Leads & Accounts</span></div>
                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </div>
                <ul class="submenu">
                    <li onclick="showCompanyOrg(this)"><a href="#">Company & Organization</a></li>
                    <li onclick="showComingSoon('Accounts & Clients', this)"><a href="#">Accounts & Clients</a></li>
                </ul>
            </li>
            
            <li class="dropdown-item" id="dealsMenu">
                <div class="dropdown-title" onclick="toggleSubMenu('dealsMenu', 'comingSoonSection', this)">
                    <div class="dropdown-title-left"><i class="fa-solid fa-bullhorn"></i><span>Deals & Campaign</span></div>
                    <i class="fa-solid fa-chevron-down dropdown-icon"></i>
                </div>
                <ul class="submenu">
                    <li onclick="showComingSoon('Campaigns', this)"><a href="#">Campaigns</a></li>
                    <li onclick="showComingSoon('Deal Pipeline', this)"><a href="#">Deal Pipeline</a></li>
                </ul>
            </li>

            <li onclick="showTaskManagement(this)"><i class="fa-solid fa-clipboard-list"></i><a href="#">Task Management</a></li>
            <li onclick="showComingSoon('Analytics & Reports', this)"><i class="fa-solid fa-chart-column"></i><a href="#">Analytics & Reports</a></li>
            <li onclick="showComingSoon('Settings', this)"><i class="fa-solid fa-gear"></i><a href="#">Settings</a></li>
            
            <li style="margin-top: 20px; border-top: 1px solid #1e293b; padding-top: 20px;" onclick="window.location.href='logout.php'"><i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i><a href="#" style="color: #ef4444;">Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="top-navbar">
            <div><i class="fa-solid fa-bars toggle-btn" id="outerToggle"></i></div>
            
            <div class="navbar-actions">
                <i class="fa-solid fa-moon nav-icon-btn" id="darkModeToggle" title="Toggle Dark Mode"></i>
                
                <div class="nav-icon-btn" title="Notifications" onclick="showToast('No new notifications', 'success')">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>

                <div class="user-profile"><i class="fa-solid fa-circle-user" style="color: #3b82f6;"></i><span><?php echo $_SESSION['name']; ?></span></div>
            </div>
        </div>

        <div class="dashboard-container" id="mainDashboardContent">
            <h1 class="page-title">Overview</h1>
            <div class="cards-grid">
                <div class="card"><div class="card-info"><h4>Total Admins</h4><h2>05</h2></div><div class="card-icon"><i class="fa-solid fa-user-shield"></i></div></div>
                <div class="card"><div class="card-info"><h4>Total Managers</h4><h2>12</h2></div><div class="card-icon"><i class="fa-solid fa-briefcase"></i></div></div>
                <div class="card"><div class="card-info"><h4>Active Agents</h4><h2>48</h2></div><div class="card-icon"><i class="fa-solid fa-headset"></i></div></div>
            </div>
        </div>

        <div id="userListSection">
            <div class="user-list-header">
                <div class="user-list-title">
                    <h1>User List</h1>
                    <p>Logged in as <?php echo $_SESSION['name']; ?> • Session ID: <?php echo substr(session_id(), 0, 10); ?>-XA</p>
                </div>
                <div class="header-buttons">
                    <button class="desig-btn" onclick="openModal('createDesignationModal')"><i class="fa-solid fa-medal"></i> Designations</button>
                    <button class="create-btn" onclick="openModal('createUserModal')"><i class="fa-solid fa-user-plus"></i> Create New User</button>
                </div>
            </div>

            <div class="tabs-wrapper">
                <div class="tab-top-line"></div>
                <div class="tabs-container">
                    <button class="tab-btn active" onclick="filterUsers('all', this)"><i class="fa-regular fa-circle-dot"></i> All Users</button>
                    <button class="tab-btn" onclick="filterUsers('active', this)"><i class="fa-regular fa-circle-dot"></i> Active Users</button>
                    <button class="tab-btn" onclick="filterUsers('inactive', this)"><i class="fa-regular fa-circle-dot"></i> In-Active Users</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID No</th><th>User ID</th><th>Name</th><th>Role</th>
                            <th>Designation</th><th>Contact</th><th>Email</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(isset($conn)){
                            $sql = "SELECT * FROM users ORDER BY id DESC";
                            $res = mysqli_query($conn, $sql);
                            if($res && mysqli_num_rows($res) > 0){
                                while($row = mysqli_fetch_assoc($res)) {
                                    $role = ucfirst(str_replace('_', ' ', $row['role']));
                                    $rawStatus = strtolower($row['status']);
                                    $statusText = ($rawStatus == 'active') ? 'Active' : 'In-Active';
                                    $statusColor = ($rawStatus == 'active') ? '#10b981' : '#ef4444'; 
                                    $userData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    echo "<tr class='user-row' data-status='$rawStatus'>
                                            <td>038H{$row['id']}</td>
                                            <td><b>{$row['username']}</b></td>
                                            <td>{$row['name']}</td>
                                            <td>$role</td>
                                            <td>" . ($row['designation'] ?? 'COO') . "</td>
                                            <td>" . ($row['phone'] ?? '+880 018...') . "</td>
                                            <td>" . ($row['email'] ?? 'N/A') . "</td>
                                            <td class='status-text' style='color:$statusColor;'>$statusText</td>
                                            <td>
                                                <div class='action-btns'>
                                                    <button class='btn-view' onclick='openViewModal($userData)' title='View'><i class='fa-regular fa-eye'></i></button>
                                                    <button class='btn-edit' onclick='openEditModal($userData)' title='Edit'><i class='fa-solid fa-pen'></i></button>
                                                    <form method='POST' id='delete-user-{$row['id']}' style='display:inline;'>
                                                        <input type='hidden' name='delete_user_id' value='{$row['id']}'>
                                                        <input type='hidden' name='delete_user' value='1'>
                                                        <button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-{$row['id']}\", \"user\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                          </tr>";
                                }
                            }
                        } else {
                            // Dummy Data Fallback for frontend viewing if DB fails
                            $dummyDataActive = htmlspecialchars(json_encode(['id'=>'1', 'name'=>'MD. Farabee', 'username'=>'sakline01', 'email'=>'sakline.business@gmail.com', 'role'=>'super_admin', 'status'=>'active', 'phone'=>'+880 018xxxx-xxx96', 'dob'=>'1998-05-12', 'emp_id'=>'EMP-101', 'designation'=>'COO', 'reporting_manager'=>'None', 'address'=>'House 12, Road 5, Dhaka']), ENT_QUOTES, 'UTF-8');
                            $dummyDataInactive = htmlspecialchars(json_encode(['id'=>'2', 'name'=>'John Doe', 'username'=>'johndoe', 'email'=>'john@example.com', 'role'=>'manager', 'status'=>'inactive', 'phone'=>'+880 017xxxx-xxx99', 'dob'=>'1995-08-22', 'emp_id'=>'EMP-102', 'designation'=>'Sales', 'reporting_manager'=>'Mr. Sakline', 'address'=>'Block C, Bashundhara']), ENT_QUOTES, 'UTF-8');
                            
                            echo "<tr class='user-row' data-status='active'>
                                    <td>038H1</td><td><b>sakline01</b></td><td>MD. Farabee</td><td>Super Admin</td><td>COO</td><td>+880 018xxxx-xxx96</td><td>sakline.business@gmail.com</td><td class='status-text' style='color:#10b981;'>Active</td>
                                    <td><div class='action-btns'>
                                        <button class='btn-view' onclick='openViewModal($dummyDataActive)'><i class='fa-regular fa-eye'></i></button>
                                        <button class='btn-edit' onclick='openEditModal($dummyDataActive)'><i class='fa-solid fa-pen'></i></button>
                                        <form id='delete-user-dummy1' style='display:inline;'><button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-dummy1\", \"user\")'><i class='fa-solid fa-trash'></i></button></form>
                                    </div></td></tr>";
                            
                            echo "<tr class='user-row' data-status='inactive'>
                                    <td>038H2</td><td><b>johndoe</b></td><td>John Doe</td><td>Manager</td><td>Sales</td><td>+880 017xxxx-xxx99</td><td>john@example.com</td><td class='status-text' style='color:#ef4444;'>In-Active</td>
                                    <td><div class='action-btns'>
                                        <button class='btn-view' onclick='openViewModal($dummyDataInactive)'><i class='fa-regular fa-eye'></i></button>
                                        <button class='btn-edit' onclick='openEditModal($dummyDataInactive)'><i class='fa-solid fa-pen'></i></button>
                                        <form id='delete-user-dummy2' style='display:inline;'><button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-dummy2\", \"user\")'><i class='fa-solid fa-trash'></i></button></form>
                                    </div></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-container" id="taskManagementSection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 0;">
                <div class="task-header-title">
                    <h1>Task Management</h1>
                    <p>Coordinate enterprise projects and monitor team delivery speeds.</p>
                </div>
                <div class="header-buttons">
                    <button class="desig-btn" style="background-color: #f3f4f6; color: #111827; border: 1px solid #d1d5db;" onclick="showToast('Categories Coming Soon', 'success')"><i class="fa-solid fa-folder-open" style="color: #4b5563;"></i> Task Categories</button>
                    <button class="create-btn" style="background-color: #0056d2;" onclick="openModal('createTaskModal')"><i class="fa-solid fa-plus"></i> Create New Task</button>
                </div>
            </div>

            <div class="task-cards">
                <div class="task-card">
                    <div class="tc-header"><h4>Total Tasks</h4><i class="fa-solid fa-file-lines" style="color:#60a5fa;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $totalTasks : "1,284"; ?></h2>
                        <p>+12% from last week</p>
                    </div>
                </div>
                <div class="task-card pending">
                    <div class="tc-header"><h4>Pending Tasks</h4><i class="fa-regular fa-clipboard" style="color:#fcd34d;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $pendingTasks : "43"; ?></h2>
                        <p class="warn">Requires attention</p>
                    </div>
                </div>
                <div class="task-card overdue">
                    <div class="tc-header"><h4>Overdue</h4><i class="fa-solid fa-asterisk" style="color:#fca5a5;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $overdueTasks : "07"; ?></h2>
                        <p class="danger">Critical threshold</p>
                    </div>
                </div>
                <div class="task-card completed">
                    <div class="tc-header"><h4>Completed Today</h4><i class="fa-regular fa-circle-check" style="color:#6ee7b7;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $completedTasks : "18"; ?></h2>
                        <p>Daily goal reached</p>
                    </div>
                </div>
            </div>

            <div class="task-table-wrapper">
                <div class="tt-controls">
                    <div class="tt-tabs">
                        <div class="tt-tab active">All Tasks</div>
                        <div class="tt-tab">My Tasks</div>
                        <div class="tt-tab">Team Tasks</div>
                    </div>
                    <div class="tt-actions">
                        <span><i class="fa-solid fa-filter"></i> Advanced Filter</span>
                        <span><i class="fa-solid fa-arrow-down-short-wide"></i> Sort: Due Date</span>
                    </div>
                </div>

                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Task Name & Details</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasTasks) && $hasTasks): ?>
                            <?php echo $taskTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <div class="tt-name">Q4 Financial Audit Preparation</div>
                                    <div class="tt-desc">Review all ledger entries for Q4 an...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar">SC</div>
                                        <span>Sarah Connor</span>
                                    </div>
                                </td>
                                <td><span class="pill high">HIGH</span></td>
                                <td><span class="status-dot progress"></span> <span class="status-text-t progress">In-Progress</span></td>
                                <td><span class="date-text">Oct 24,<br>2023</span></td>
                                <td style="width: 130px; text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tt-name">CRM Database Migration</div>
                                    <div class="tt-desc">Transfer legacy account data to th...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar" style="background:#e0e7ff;">MT</div>
                                        <span>Marcus T.</span>
                                    </div>
                                </td>
                                <td><span class="pill medium">MEDIUM</span></td>
                                <td><span class="status-dot todo"></span> <span class="status-text-t todo">To-Do</span></td>
                                <td><span class="date-text">Oct 28,<br>2023</span></td>
                                <td style="text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tt-name">UI/UX Review - Project Zenith</div>
                                    <div class="tt-desc">Final approval on bento-grid layout...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar" style="background:#c7d2fe;">AL</div>
                                        <span>Anna Lee</span>
                                    </div>
                                </td>
                                <td><span class="pill low">LOW</span></td>
                                <td><span class="status-dot done"></span> <span class="status-text-t done">Done</span></td>
                                <td><span class="date-text">Oct 20,<br>2023</span></td>
                                <td style="text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <span>Showing 1-10 of 1,284 entries</span>
                    <div class="page-numbers">
                        <div class="page-no"><i class="fa-solid fa-chevron-left"></i></div>
                        <div class="page-no active">1</div>
                        <div class="page-no">2</div>
                        <div class="page-no">3</div>
                        <div class="page-no" style="cursor: default; background:none;">...</div>
                        <div class="page-no">128</div>
                        <div class="page-no"><i class="fa-solid fa-chevron-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="company-container" id="companySection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 20px;">
                <div class="comp-header-title">
                    <h1>Client Database</h1>
                    <p>Welcome back, <?php echo $_SESSION['name']; ?></p>
                </div>
                <div class="header-buttons">
                    <form method="POST" style="display:inline-block;">
                        <button type="submit" name="export_companies_csv" class="btn-export" style="height: 100%;"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                    </form>
                    <button class="btn-upload" onclick="openModal('bulkUploadCompanyModal')"><i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload</button>
                    <button class="btn-add-client" onclick="openModal('addCompanyModal')"><i class="fa-solid fa-plus"></i> Add Company</button>
                </div>
            </div>

            <div class="comp-table-wrapper">
                <div class="comp-toolbar">
                    <div class="comp-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search company...">
                    </div>
                    <div class="comp-total">Total Companies: <?php echo (isset($hasCompanies) && $hasCompanies) ? $totalCompanies : "4"; ?></div>
                </div>

                <table class="comp-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="tbl-checkbox" title="Select All"></th>
                            <th>COMPANY NAME</th>
                            <th>ASSIGNED AGENT</th>
                            <th>TOTAL CONTACTS</th>
                            <th style="text-align: right;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasCompanies) && $hasCompanies): ?>
                            <?php echo $companyTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><span class="comp-name">Blue Point Accounting</span></td>
                                <td><div class="comp-agent"><i class="fa-solid fa-user"></i><span><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></span></div></td>
                                <td><span class="comp-contacts-pill">9 Contacts</span></td>
                                <td style="text-align: right;">
                                    <button class="comp-icon-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                                    <button class="comp-icon-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><span class="comp-name">course plus</span></td>
                                <td><div class="comp-agent"><i class="fa-solid fa-user"></i><span>mh, <?php echo explode(' ', trim($_SESSION['name']))[0]; ?></span></div></td>
                                <td><span class="comp-contacts-pill">12 Contacts</span></td>
                                <td style="text-align: right;">
                                    <button class="comp-icon-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                                    <button class="comp-icon-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><span class="comp-name">kk</span></td>
                                <td><div class="comp-agent"><i class="fa-solid fa-user"></i><span><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></span></div></td>
                                <td><span class="comp-contacts-pill">1 Contacts</span></td>
                                <td style="text-align: right;">
                                    <button class="comp-icon-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                                    <button class="comp-icon-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><span class="comp-name">Peer solution</span></td>
                                <td><div class="comp-agent"><i class="fa-solid fa-user"></i><span><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></span></div></td>
                                <td><span class="comp-contacts-pill">18 Contacts</span></td>
                                <td style="text-align: right;">
                                    <button class="comp-icon-btn view" title="View"><i class="fa-regular fa-eye"></i></button>
                                    <button class="comp-icon-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="coming-soon-container" id="comingSoonSection">
            <i class="fa-solid fa-person-digging coming-soon-icon"></i>
            <h1 class="coming-soon-title" id="comingSoonTitle">Feature Coming Soon</h1>
            <p class="coming-soon-text">Stay tuned!</p>
        </div>
    </div>

    <div id="createTaskModal" class="modal" style="z-index: 3000;">
        <div class="modal-content task-modal-content">
            <button type="button" class="close-btn" style="position:absolute; right:20px; top:20px; font-size:16px;" onclick="closeModal('createTaskModal')"><i class="fa-solid fa-xmark"></i></button>

            <form action="super_admin_dashboard.php" method="POST" id="createTaskForm">
                
                <label class="task-modal-label">Add a title <span class="req">*</span></label>
                <input type="text" name="task_title" class="task-input-dark" required placeholder="Title">

                <label class="task-modal-label" style="margin-top: 10px;">Add a description</label>
                
                <div class="task-toolbar">
                    <div class="task-tab">Write</div>
                    <div class="task-tools">
                        <i class="fa-solid fa-h"></i>
                        <i class="fa-solid fa-bold"></i>
                        <i class="fa-solid fa-italic"></i>
                        <i class="fa-solid fa-code"></i>
                        <i class="fa-solid fa-link"></i>
                        <i class="fa-solid fa-list-ul"></i>
                        <i class="fa-solid fa-list-ol"></i>
                        <i class="fa-regular fa-square-check"></i>
                        <i class="fa-solid fa-at"></i>
                        <i class="fa-regular fa-comment"></i>
                        <i class="fa-regular fa-image"></i>
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                </div>
                
                <textarea name="task_description" class="task-textarea" placeholder="Type your description here..."></textarea>

                <div class="task-modal-footer">
                    <div class="task-footer-left">
                        
                        <div class="task-tag-wrapper">
                            <i class="fa-solid fa-user-plus"></i>
                            <select name="assigned_to" class="task-tag-select">
                                <option value="Unassigned" selected>Assignee</option>
                                <?php echo $assigneeOptions; ?>
                            </select>
                        </div>
                        
                        <div class="task-tag-wrapper">
                            <i class="fa-solid fa-tag"></i>
                            <select name="task_label" class="task-tag-select">
                                <option value="" disabled selected>Label</option>
                                <option value="Label 1">Label 1</option>
                                <option value="Label 2">Label 2</option>
                                <option value="Label 3">Label 3</option>
                            </select>
                        </div>
                        
                        <button type="button" class="task-tag-btn" onclick="showToast('Deal & Campaign coming soon!', 'success')">
                            <i class="fa-solid fa-briefcase"></i> Deal & Campaign
                        </button>
                        
                    </div>
                    
                    <div class="task-footer-right">
                        <label class="task-checkbox-label">
                            <input type="checkbox" id="createMoreCheckbox" class="task-checkbox"> Create more
                        </label>
                        <button type="button" class="task-cancel-btn" onclick="closeModal('createTaskModal')">Cancel</button>
                        
                        <button type="submit" name="create_task" class="task-create-btn" onclick="handleTaskSubmit(event)">
                            <span>Create</span>
                            <span class="btn-divider"></span>
                            <i class="fa-solid fa-arrow-turn-down fa-rotate-90" style="font-size: 11px;"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    
    <div id="addCompanyModal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Add New Company</h2>
                <button type="button" class="close-btn" onclick="closeModal('addCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Company Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="company_name" required placeholder="e.g. Acme Corp">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Assigned Agent</label>
                    <select name="assigned_agent">
                        <option value="Unassigned" selected>Select Agent</option>
                        <?php echo $assigneeOptions; ?>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Total Contacts</label>
                    <input type="number" name="total_contacts" min="0" placeholder="0">
                </div>
                <button type="submit" name="create_company" class="submit-btn" style="background-color: #3b82f6;">Add Company</button>
            </form>
        </div>
    </div>
    
    <div id="bulkUploadCompanyModal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Bulk Upload Companies (CSV)</h2>
                <button type="button" class="close-btn" onclick="closeModal('bulkUploadCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST" enctype="multipart/form-data">
                <p style="font-size: 12px; color: #6b7280; margin-bottom: 15px;">Please upload a CSV file with columns: <b>Company Name, Assigned Agent, Total Contacts</b></p>
                <div class="form-group" style="margin-bottom: 20px;">
                    <input type="file" name="company_csv" accept=".csv" required>
                </div>
                <button type="submit" name="bulk_upload_companies" class="submit-btn" style="background-color: #10b981;">Upload CSV Data</button>
            </form>
        </div>
    </div>

    <div id="createDesignationModal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Manage Designations</h2>
                <button type="button" class="close-btn" onclick="closeModal('createDesignationModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form action="super_admin_dashboard.php" method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="designation_title" required placeholder="New Designation Title" style="flex-grow: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; background-color: transparent;">
                <button type="submit" name="create_designation" class="submit-btn" style="margin-top: 0; width: auto; padding: 10px 18px; background-color: #3b82f6;"><i class="fa-solid fa-plus"></i> Add</button>
            </form>

            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: transparent;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tbody>
                        <?php echo $designationTableRows; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="editDesignationModal" class="modal" style="z-index: 2500;">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Edit Designation</h2>
                <button type="button" class="close-btn" onclick="closeModal('editDesignationModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <input type="hidden" name="desig_id" id="edit_desig_id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Designation Title</label>
                    <input type="text" name="designation_title" id="edit_desig_title" required>
                </div>
                <button type="submit" name="update_designation" class="submit-btn" style="background-color: #22c55e;">Update Designation</button>
            </form>
        </div>
    </div>

    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <button type="button" class="close-btn" onclick="closeModal('createUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required placeholder="e.g. MD. Farabee"></div>
                    <div class="form-group"><label>User ID</label><input type="text" name="username" required placeholder="e.g. sakline01"></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" placeholder="e.g. +88018..."></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="e.g. example@gmail.com"></div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" id="create_pass" required placeholder="********" onkeyup="checkPasswordMatch('create_pass', 'create_confirm_pass', 'create_error_msg', 'create_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('create_pass', this)"></i>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="create_confirm_pass" required placeholder="********" onkeyup="checkPasswordMatch('create_pass', 'create_confirm_pass', 'create_error_msg', 'create_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('create_confirm_pass', this)"></i>
                        <span id="create_error_msg" class="password-error">Passwords do not match!</span>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob"></div>
                    <div class="form-group"><label>Employee ID</label><input type="text" name="emp_id" placeholder="e.g. EMP-101"></div>
                    
                    <div class="form-group">
                        <label>Designation</label>
                        <select name="designation">
                            <option value="" disabled selected>Select Designation</option>
                            <?php echo $designationsList; ?>
                        </select>
                    </div>

                    <div class="form-group"><label>Reporting Manager</label><select name="reporting_manager"><option value="None">None</option><option value="Mr. Sakline">Mr. Sakline</option></select></div>
                    <div class="form-group full-width"><label>Address</label><input type="text" name="address" placeholder="e.g. House 12, Dhaka"></div>
                </div>
                <button type="submit" name="create_user" id="create_submit_btn" class="submit-btn" style="margin-top: 20px;">Save User</button>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User Details</h2>
                <button type="button" class="close-btn" onclick="closeModal('editUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required></div>
                    <div class="form-group"><label>User ID</label><input type="text" name="username" id="edit_username" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" id="edit_phone"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" required></div>
                    
                    <div class="form-group">
                        <label>Password <span style="font-weight: 400; font-size: 10px; opacity:0.7;">(Leave blank to keep same)</span></label>
                        <input type="password" name="password" id="edit_password" placeholder="********" onkeyup="checkPasswordMatch('edit_password', 'edit_confirm_password', 'edit_error_msg', 'edit_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('edit_password', this)"></i>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="edit_confirm_password" placeholder="********" onkeyup="checkPasswordMatch('edit_password', 'edit_confirm_password', 'edit_error_msg', 'edit_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('edit_confirm_password', this)"></i>
                        <span id="edit_error_msg" class="password-error">Passwords do not match!</span>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_role" required>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">In-Active</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" id="edit_dob"></div>
                    <div class="form-group"><label>Employee ID</label><input type="text" name="emp_id" id="edit_emp_id"></div>
                    <div class="form-group">
                        <label>Designation</label>
                        <select name="designation" id="edit_designation">
                            <?php echo $designationsList; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Reporting Manager</label><select name="reporting_manager" id="edit_reporting"><option value="None">None</option><option value="Mr. Sakline">Mr. Sakline</option></select></div>
                    <div class="form-group full-width"><label>Address</label><input type="text" name="address" id="edit_address"></div>
                </div>
                <button type="submit" name="update_user" id="edit_submit_btn" class="submit-btn" style="background-color: #22c55e; margin-top: 20px;">Update User</button>
            </form>
        </div>
    </div>

    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>User Profile View</h2>
                <button type="button" class="close-btn" onclick="closeModal('viewUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><div class="view-data-box" id="view_name">-</div></div>
                <div class="form-group"><label>User ID</label><div class="view-data-box" id="view_username">-</div></div>
                <div class="form-group"><label>Phone Number</label><div class="view-data-box" id="view_phone">-</div></div>
                <div class="form-group"><label>Email ID</label><div class="view-data-box" id="view_email">-</div></div>
                <div class="form-group"><label>Password</label><div class="view-data-box" style="color: #6b7280; font-family: monospace;">******** (Encrypted)</div></div>
                <div class="form-group"><label>Role</label><div class="view-data-box" id="view_role">-</div></div>
                <div class="form-group"><label>Account Status</label><div class="view-data-box" id="view_status" style="font-weight: 700;">-</div></div>
                <div class="form-group"><label>Date of Birth</label><div class="view-data-box" id="view_dob">-</div></div>
                <div class="form-group"><label>Employee ID</label><div class="view-data-box" id="view_emp_id">-</div></div>
                <div class="form-group"><label>Designation</label><div class="view-data-box" id="view_designation">-</div></div>
                <div class="form-group"><label>Reporting Manager</label><div class="view-data-box" id="view_reporting">-</div></div>
                <div class="form-group full-width"><label>Address</label><div class="view-data-box" id="view_address">-</div></div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="submit-btn" onclick="switchToEditMode()" style="background-color: #22c55e; margin-top: 0;"><i class="fa-solid fa-pen-to-square"></i> Edit User</button>
                <button class="submit-btn" onclick="closeModal('viewUserModal')" style="background-color: #6b7280; margin-top: 0;">Close</button>
            </div>
        </div>
    </div>

    <script>
        // --- CREATE TASK 'CREATE MORE' LOGIC ---
        function handleTaskSubmit(e) {
            const isCreateMoreChecked = document.getElementById('createMoreCheckbox').checked;
            
            if(isCreateMoreChecked) {
                const titleInput = document.querySelector('input[name="task_title"]');
                if(titleInput.value.trim() === '') return;
                
                e.preventDefault(); 
                showToast("Task created successfully!", "success");
                
                titleInput.value = '';
                document.querySelector('textarea[name="task_description"]').value = '';
                
                document.querySelector('select[name="assigned_to"]').value = 'Unassigned';
                document.querySelector('select[name="task_label"]').selectedIndex = 0;
            }
        }

        // --- REAL-TIME PASSWORD MATCH VALIDATION LOGIC ---
        function checkPasswordMatch(passId, confirmPassId, errorMsgId, submitBtnId) {
            const password = document.getElementById(passId).value;
            const confirmPassword = document.getElementById(confirmPassId).value;
            const errorMsg = document.getElementById(errorMsgId);
            const submitBtn = document.getElementById(submitBtnId);

            if (confirmPassword !== "") {
                if (password !== confirmPassword) {
                    errorMsg.style.display = "block";
                    submitBtn.disabled = true; 
                } else {
                    errorMsg.style.display = "none";
                    submitBtn.disabled = false; 
                }
            } else {
                errorMsg.style.display = "none";
                submitBtn.disabled = false;
            }
        }

        // --- PASSWORD SHOW/HIDE LOGIC ---
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // --- DARK MODE LOGIC ---
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.classList.replace('fa-moon', 'fa-sun');
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('darkMode', 'disabled');
                darkModeToggle.classList.replace('fa-sun', 'fa-moon');
            }
        });

        // --- SWEETALERT CONFIRMATION FOR DELETION ---
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

        // --- TOAST NOTIFICATION LOGIC ---
        window.onload = function() {
            <?php if($toastMessage != ""): ?>
                showToast("<?php echo $toastMessage; ?>", "<?php echo $toastType; ?>");
                
                let activeTabAfterLoad = "<?php echo $activeTabAfterSubmit; ?>";
                
                if(activeTabAfterLoad === "tasks") {
                    const taskBtn = document.querySelector('li[onclick="showTaskManagement(this)"]');
                    if(taskBtn) showTaskManagement(taskBtn);
                } else if(activeTabAfterLoad === "companies") {
                    const compBtn = document.querySelector('li[onclick="showCompanyOrg(this)"]');
                    if(compBtn) showCompanyOrg(compBtn);
                } else if(document.getElementById('userListSection')) {
                    hideAllSections();
                    document.getElementById('userListSection').style.display = 'block';
                    
                    document.querySelectorAll('.sidebar-menu .active, .sidebar-menu .active-main, .sidebar-menu .active-sub').forEach(el => {
                        el.classList.remove('active', 'active-main', 'active-sub');
                    });
                    const userListBtn = document.querySelector('li[onclick="showUserList(this)"]');
                    if(userListBtn) setActiveSidebar(userListBtn);
                }
            <?php endif; ?>
        };

        function showToast(message, type) {
            const toast = document.getElementById("toastBox");
            const toastMsg = document.getElementById("toastMsg");
            const toastIcon = document.getElementById("toastIcon");

            toastMsg.innerText = message;
            toast.className = "show " + type;

            if (type === 'success') {
                toastIcon.className = "fa-solid fa-circle-check";
            } else {
                toastIcon.className = "fa-solid fa-circle-xmark";
            }

            setTimeout(function(){ 
                toast.className = toast.className.replace("show", ""); 
            }, 3000);
        }

        // --- SIDEBAR TOGGLE LOGIC ---
        const outerToggle = document.getElementById('outerToggle');
        const sidebar = document.getElementById('sidebar');
        outerToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));

        // --- SUBMENU TOGGLE & ROUTING LOGIC ---
        function setActiveSidebar(el) {
            document.querySelectorAll('.sidebar-menu > li.active').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.dropdown-title.active-main').forEach(item => item.classList.remove('active-main'));
            document.querySelectorAll('.submenu li.active-sub').forEach(item => item.classList.remove('active-sub'));

            if(el) {
                if(el.parentElement && el.parentElement.classList.contains('submenu')) {
                    el.classList.add('active-sub');
                    const parentDropdown = el.closest('.dropdown-item');
                    if(parentDropdown) {
                        parentDropdown.querySelector('.dropdown-title').classList.add('active-main');
                    }
                } else {
                    el.classList.add('active');
                }
            }
        }

        function toggleSubMenu(menuId, targetSectionId = null, el = null) { 
            const menu = document.getElementById(menuId);
            menu.classList.toggle('open'); 

            if(menu.classList.contains('open') && targetSectionId) {
                const firstSubItem = menu.querySelector('.submenu li');
                
                if(targetSectionId === 'userListSection') {
                    showUserList(firstSubItem);
                } else if(targetSectionId === 'companySection') {
                    showCompanyOrg(firstSubItem);
                } else if(targetSectionId === 'comingSoonSection') {
                    const pageName = firstSubItem ? firstSubItem.innerText.trim() : 'Module';
                    showComingSoon(pageName, firstSubItem);
                }
            }
        }

        // --- SECTION VISIBILITY TOGGLES (SAFE MODE) ---
        function hideAllSections() {
            const sections = ['mainDashboardContent', 'userListSection', 'taskManagementSection', 'companySection', 'comingSoonSection'];
            sections.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }

        function showDashboard(el = null) {
            hideAllSections();
            const dashboardSec = document.getElementById('mainDashboardContent');
            if(dashboardSec) dashboardSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showUserList(el = null) {
            hideAllSections();
            const userList = document.getElementById('userListSection');
            if(userList) userList.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showTaskManagement(el = null) {
            hideAllSections();
            const taskSec = document.getElementById('taskManagementSection');
            if(taskSec) taskSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showCompanyOrg(el = null) {
            hideAllSections();
            const compSec = document.getElementById('companySection');
            if(compSec) compSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showComingSoon(pageName, el = null) {
            hideAllSections();
            const comingSoonSec = document.getElementById('comingSoonSection');
            if(comingSoonSec) {
                comingSoonSec.style.display = 'flex';
                if(pageName !== 'Module') {
                    document.getElementById('comingSoonTitle').innerText = pageName + ' - Coming Soon!';
                } else {
                    document.getElementById('comingSoonTitle').innerText = 'Feature Coming Soon!';
                }
            }
            if(el) setActiveSidebar(el);
        }

        // --- DATA FILTERING LOGIC (All, Active, Inactive Tabs) ---
        function filterUsers(status, btnElement) {
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            const rows = document.querySelectorAll('.user-row');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    if (row.getAttribute('data-status') === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        // --- MODAL CONTROL LOGIC ---
        function openModal(id) { document.getElementById(id).style.display = "flex"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }

        function openEditDesignationModal(desig) {
            closeModal('createDesignationModal');
            document.getElementById('edit_desig_id').value = desig.id;
            document.getElementById('edit_desig_title').value = desig.title;
            openModal('editDesignationModal');
        }

        let currentUserData = null; 

        function openViewModal(user) {
            currentUserData = user; 
            document.getElementById('view_name').innerText = user.name || 'N/A';
            document.getElementById('view_username').innerText = user.username || 'N/A';
            document.getElementById('view_email').innerText = user.email || 'N/A';
            document.getElementById('view_phone').innerText = user.phone || 'N/A';
            document.getElementById('view_role').innerText = user.role ? user.role.toUpperCase() : 'N/A';
            document.getElementById('view_dob').innerText = user.dob || 'N/A';
            document.getElementById('view_emp_id').innerText = user.emp_id || 'N/A';
            document.getElementById('view_designation').innerText = user.designation || 'N/A';
            document.getElementById('view_reporting').innerText = user.reporting_manager || 'N/A';
            document.getElementById('view_address').innerText = user.address || 'N/A';
            
            const statusText = (user.status == 'active') ? 'Active' : 'In-Active';
            document.getElementById('view_status').innerText = statusText;
            document.getElementById('view_status').style.color = (user.status == 'active' || user.status == 'Active') ? '#10b981' : '#ef4444';

            openModal('viewUserModal');
        }

        function switchToEditMode() {
            closeModal('viewUserModal');
            if(currentUserData) {
                openEditModal(currentUserData);
            }
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_username').value = user.username || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_dob').value = user.dob || '';
            document.getElementById('edit_emp_id').value = user.emp_id || '';
            document.getElementById('edit_address').value = user.address || '';
            document.getElementById('edit_designation').value = user.designation || 'COO';
            document.getElementById('edit_reporting').value = user.reporting_manager || 'None';
            
            const statusVal = user.status ? user.status.toLowerCase() : 'active';
            document.getElementById('edit_status').value = statusVal;
            
            document.getElementById('edit_password').value = ''; 
            document.getElementById('edit_confirm_password').value = '';
            document.getElementById('edit_error_msg').style.display = 'none';
            document.getElementById('edit_submit_btn').disabled = false;
            
            openModal('editUserModal');
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
=======
<?php
// ========================================================================
// 1. INITIALIZATION & SECURITY CHECK
// ========================================================================

// Start the session to manage user login states
session_start();

// Include database connection file (suppressed errors with @ if file is missing during tests)
@include 'config.php'; 

// ========================================================================
// CSV EXPORT LOGIC FOR COMPANIES (MUST BE BEFORE ANY HTML OUTPUT)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['export_companies_csv'])) {
    if (isset($conn)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=companies_export_' . date('Y-m-d') . '.csv');
        $output = fopen("php://output", "w");
        fputcsv($output, array('ID', 'Company Name', 'Assigned Agent', 'Total Contacts'));
        
        $query = mysqli_query($conn, "SELECT * FROM companies ORDER BY id DESC");
        if ($query) {
            while ($row = mysqli_fetch_assoc($query)) {
                fputcsv($output, array($row['id'], $row['company_name'], $row['assigned_agent'], $row['total_contacts']));
            }
        }
        fclose($output);
        exit(); // Stop script execution so HTML is not added to CSV
    }
}

// Security Check: If the user is not logged in OR is not a 'super_admin', redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

// Variables to hold Toast Notification messages and types (success/error)
$toastMessage = "";
$toastType = "";
$activeTabAfterSubmit = "users"; // Determines which tab stays open after page reload

// ========================================================================
// 2. USER MANAGEMENT LOGIC (CREATE, UPDATE, DELETE)
// ========================================================================

// A. CREATE NEW USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? ''); 
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
        $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
        $raw_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if($raw_password !== $confirm_password) {
            $toastMessage = "Passwords do not match!";
            $toastType = "error";
        } else {
            $password = password_hash($raw_password, PASSWORD_DEFAULT); 
            $status = 'active'; 
            $insert_sql = "INSERT INTO users (name, username, email, password, role, designation, status) VALUES ('$name', '$username', '$email', '$password', '$role', '$designation', '$status')";
            try {
                if(mysqli_query($conn, $insert_sql)){
                    $toastMessage = "User created successfully!";
                    $toastType = "success";
                }
            } catch (mysqli_sql_exception $e) {
                $toastMessage = "Database Error! Ensure all columns exist.";
                $toastType = "error";
            }
        }
    }
}

// B. UPDATE/EDIT EXISTING USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $id = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
        $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
        $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
        $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'active'); 
        $raw_password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if(!empty($raw_password) && $raw_password !== $confirm_password) {
            $toastMessage = "Passwords do not match! User not updated.";
            $toastType = "error";
        } else {
            $update_sql = "UPDATE users SET name='$name', username='$username', email='$email', role='$role', designation='$designation', status='$status'";
            if (!empty($raw_password)) {
                $new_password = password_hash($raw_password, PASSWORD_DEFAULT);
                $update_sql .= ", password='$new_password'";
            }
            $update_sql .= " WHERE id='$id'";
            try {
                if(mysqli_query($conn, $update_sql)){
                    $toastMessage = "User updated successfully!";
                    $toastType = "success";
                }
            } catch (mysqli_sql_exception $e) {
                $toastMessage = "Database Error! Could not update user.";
                $toastType = "error";
            }
        }
    }
}

// C. DELETE USER LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $activeTabAfterSubmit = "users";
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_user_id'] ?? '');
        $delete_sql = "DELETE FROM users WHERE id='$del_id'";
        try {
            if(mysqli_query($conn, $delete_sql)){
                $toastMessage = "User deleted successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Error deleting user!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 3. DESIGNATION MANAGEMENT LOGIC
// ========================================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $designation_title = mysqli_real_escape_string($conn, $_POST['designation_title'] ?? '');
        $insert_desig = "INSERT INTO designations (title) VALUES ('$designation_title')";
        try {
            if(mysqli_query($conn, $insert_desig)){
                $toastMessage = "Designation added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Error adding designation!";
            $toastType = "error";
        }
    }
    
    if (isset($_POST['update_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $desig_id = mysqli_real_escape_string($conn, $_POST['desig_id'] ?? '');
        $designation_title = mysqli_real_escape_string($conn, $_POST['designation_title'] ?? '');
        $update_desig = "UPDATE designations SET title='$designation_title' WHERE id='$desig_id'";
        if(mysqli_query($conn, $update_desig)){
            $toastMessage = "Designation updated successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error updating designation!";
            $toastType = "error";
        }
    }

    if (isset($_POST['delete_designation']) && isset($conn)) {
        $activeTabAfterSubmit = "users";
        $desig_id = mysqli_real_escape_string($conn, $_POST['desig_id'] ?? '');
        $delete_desig = "DELETE FROM designations WHERE id='$desig_id'";
        if(mysqli_query($conn, $delete_desig)){
            $toastMessage = "Designation deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting designation!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 4. TASK MANAGEMENT LOGIC
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_task'])) {
    $activeTabAfterSubmit = "tasks";
    if(isset($conn)){
        $task_title = mysqli_real_escape_string($conn, $_POST['task_title'] ?? '');
        $task_description = mysqli_real_escape_string($conn, $_POST['task_description'] ?? '');
        $assigned_to = mysqli_real_escape_string($conn, $_POST['assigned_to'] ?? 'Unassigned'); 
        $priority = 'Medium';
        $status = 'To-Do';
        $due_date = date('Y-m-d', strtotime('+7 days'));

        $insert_task_sql = "INSERT INTO tasks (title, description, assigned_to, priority, status, due_date) VALUES ('$task_title', '$task_description', '$assigned_to', '$priority', '$status', '$due_date')";
        
        try {
            if(mysqli_query($conn, $insert_task_sql)){
                $toastMessage = "Task created successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Make sure 'tasks' table exists.";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 5. COMPANY & ORGANIZATION LOGIC
// ========================================================================

// A. Create Single Company
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_company'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn)){
        $comp_name = mysqli_real_escape_string($conn, $_POST['company_name'] ?? '');
        $assigned_agent = mysqli_real_escape_string($conn, $_POST['assigned_agent'] ?? 'Unassigned');
        $total_contacts = (int)($_POST['total_contacts'] ?? 0);
        $comp_email = mysqli_real_escape_string($conn, $_POST['company_email'] ?? '');
        $comp_number = mysqli_real_escape_string($conn, $_POST['company_number'] ?? '');
        $comp_website = mysqli_real_escape_string($conn, $_POST['company_website'] ?? '');
        $fb_url = mysqli_real_escape_string($conn, $_POST['fb_url'] ?? '');
        $linkedin_url = mysqli_real_escape_string($conn, $_POST['linkedin_url'] ?? '');
        $insta_url = mysqli_real_escape_string($conn, $_POST['insta_url'] ?? '');
        $twitter_url = mysqli_real_escape_string($conn, $_POST['twitter_url'] ?? '');

        $insert_sql = "INSERT INTO companies (company_name, assigned_agent, total_contacts, company_email, company_number, company_website, fb_url, linkedin_url, insta_url, twitter_url) 
                       VALUES ('$comp_name', '$assigned_agent', '$total_contacts', '$comp_email', '$comp_number', '$comp_website', '$fb_url', '$linkedin_url', '$insta_url', '$twitter_url')";
        try {
            if(mysqli_query($conn, $insert_sql)){
                $toastMessage = "Company added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Ensure all columns exist in 'companies' table.";
            $toastType = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_upload_companies'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn) && isset($_FILES['company_csv']) && $_FILES['company_csv']['error'] == 0){
        $file = $_FILES['company_csv']['tmp_name'];
        $handle = fopen($file, "r");
        $row_count = 0;
        
        try {
            while(($data = fgetcsv($handle, 1000, ",")) !== FALSE){
                $row_count++;
                if($row_count == 1) continue;
                
                $c_name = mysqli_real_escape_string($conn, $data[0] ?? '');
                $c_agent = mysqli_real_escape_string($conn, $data[1] ?? 'Unassigned');
                $c_contacts = (int)($data[2] ?? 0);
                
                if(!empty($c_name)){
                    mysqli_query($conn, "INSERT INTO companies (company_name, assigned_agent, total_contacts) VALUES ('$c_name', '$c_agent', '$c_contacts')");
                }
            }
            fclose($handle);
            $toastMessage = "CSV uploaded! Added " . ($row_count - 1) . " companies.";
            $toastType = "success";
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Upload Failed! Check CSV format & DB Table.";
            $toastType = "error";
        }
    } else {
        $toastMessage = "Please select a valid CSV file.";
        $toastType = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_company'])) {
    $activeTabAfterSubmit = "companies";
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_company_id'] ?? '');
        $delete_sql = "DELETE FROM companies WHERE id='$del_id'";
        if(mysqli_query($conn, $delete_sql)){
            $toastMessage = "Company deleted successfully!";
            $toastType = "success";
        } else {
            $toastMessage = "Error deleting company!";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 6. ACCOUNTS & CLIENTS LOGIC (CREATE, DELETE)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_client'])) {
    $activeTabAfterSubmit = "clients";
    if(isset($conn)){
        $client_name = mysqli_real_escape_string($conn, $_POST['client_name'] ?? '');
        $client_email = mysqli_real_escape_string($conn, $_POST['client_email'] ?? '');
        $client_phone = mysqli_real_escape_string($conn, $_POST['client_phone'] ?? '');
        $client_designation = mysqli_real_escape_string($conn, $_POST['client_designation'] ?? '');
        
        // Handle optional company assignment
        $company_id = $_POST['company_id'] ?? '';
        $comp_insert_val = !empty($company_id) ? "'".mysqli_real_escape_string($conn, $company_id)."'" : "NULL";

        $insert_client_sql = "INSERT INTO contacts (name, email, phone, designation, company_id) VALUES ('$client_name', '$client_email', '$client_phone', '$client_designation', $comp_insert_val)";
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
    $activeTabAfterSubmit = "clients";
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
// 6.5 DEAL MANAGEMENT LOGIC (CREATE, DELETE)
// ========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_deal'])) {
    $activeTabAfterSubmit = "deals";
    if(isset($conn)){
        $project_name = mysqli_real_escape_string($conn, $_POST['project_name'] ?? '');
        $service_required = mysqli_real_escape_string($conn, $_POST['service_required'] ?? '');
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
        $project_status = mysqli_real_escape_string($conn, $_POST['project_status'] ?? '');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? '');
        $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount'] ?? '0');
        $platform = mysqli_real_escape_string($conn, $_POST['platform'] ?? '');
        $sales_officer = mysqli_real_escape_string($conn, $_POST['sales_officer'] ?? '');
        $company_id = mysqli_real_escape_string($conn, $_POST['company_id'] ?? '');

        $insert_deal_sql = "INSERT INTO deals (project_name, service_required, start_date, end_date, status, currency, total_amount, platform, sales_officer, company_id) 
                            VALUES ('$project_name', '$service_required', '$start_date', '$end_date', '$project_status', '$currency', '$total_amount', '$platform', '$sales_officer', '$company_id')";
        try {
            if(mysqli_query($conn, $insert_deal_sql)){
                $toastMessage = "Deal added successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Ensure 'deals' table exists.";
            $toastType = "error";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_deal'])) {
    $activeTabAfterSubmit = "deals";
    if(isset($conn)){
        $del_id = mysqli_real_escape_string($conn, $_POST['delete_deal_id'] ?? '');
        $delete_sql = "DELETE FROM deals WHERE id='$del_id'";
        if(mysqli_query($conn, $delete_sql)){
            $toastMessage = "Deal deleted successfully!";
            $toastType = "success";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_deal'])) {
    $activeTabAfterSubmit = "deals";
    if(isset($conn)){
        $id = mysqli_real_escape_string($conn, $_POST['deal_id'] ?? '');
        $project_name = mysqli_real_escape_string($conn, $_POST['project_name'] ?? '');
        $service_required = mysqli_real_escape_string($conn, $_POST['service_required'] ?? '');
        $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
        $end_date = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
        $project_status = mysqli_real_escape_string($conn, $_POST['project_status'] ?? '');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? '');
        $total_amount = mysqli_real_escape_string($conn, $_POST['total_amount'] ?? '0');
        $platform = mysqli_real_escape_string($conn, $_POST['platform'] ?? '');
        $sales_officer = mysqli_real_escape_string($conn, $_POST['sales_officer'] ?? '');
        $company_id = mysqli_real_escape_string($conn, $_POST['company_id'] ?? '');

        $update_deal_sql = "UPDATE deals SET project_name='$project_name', service_required='$service_required', start_date='$start_date', end_date='$end_date', status='$project_status', currency='$currency', total_amount='$total_amount', platform='$platform', sales_officer='$sales_officer', company_id='$company_id' WHERE id='$id'";
        try {
            if(mysqli_query($conn, $update_deal_sql)){
                $toastMessage = "Deal updated successfully!";
                $toastType = "success";
            }
        } catch (mysqli_sql_exception $e) {
            $toastMessage = "Database Error! Could not update deal.";
            $toastType = "error";
        }
    }
}

// ========================================================================
// 7. FETCH DATA FOR UI (Designations, Users, Tasks, Companies, Clients)
// ========================================================================

$designationsList = ""; 
$designationTableRows = ""; 
$assigneeOptions = ""; 
$companyOptions = "";

if(isset($conn)){
    // Fetch Designations
    try {
        $desig_query = mysqli_query($conn, "SELECT * FROM designations ORDER BY title ASC");
        if($desig_query && mysqli_num_rows($desig_query) > 0){
            while($row = mysqli_fetch_assoc($desig_query)){
                $designationsList .= "<option value='{$row['title']}'>{$row['title']}</option>";
                $desigData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                $designationTableRows .= "
                    <tr>
                        <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: left; font-weight: 500;' class='desig-text'>{$row['title']}</td>
                        <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right;'>
                            <button type='button' style='background:none; border:none; color:#3b82f6; cursor:pointer; margin-right:15px; font-size:14px;' onclick='openEditDesignationModal({$desigData})'><i class='fa-solid fa-pen-to-square'></i></button>
                            <form method='POST' id='delete-desig-{$row['id']}' style='display:inline;'>
                                <input type='hidden' name='desig_id' value='{$row['id']}'>
                                <input type='hidden' name='delete_designation' value='1'>
                                <button type='button' onclick='confirmDelete(\"delete-desig-{$row['id']}\", \"designation\")' style='background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px;'><i class='fa-solid fa-trash'></i></button>
                            </form>
                        </td>
                    </tr>";
            }
        } else {
            $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
            $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center; color:#6b7280;'>No designations found.</td></tr>";
        }
    } catch (mysqli_sql_exception $e) {
        $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
        $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center; color:#ef4444;'>Table 'designations' not found.</td></tr>";
    }

    // Fetch Users
    try {
        $user_query = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
        if($user_query && mysqli_num_rows($user_query) > 0){
            while($uRow = mysqli_fetch_assoc($user_query)){
                $userIdStr = "038H" . $uRow['id'];
                $assigneeOptions .= "<option value='{$uRow['name']}'>{$uRow['name']} ({$userIdStr})</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}

    // Fetch Companies for Dropdown
    try {
        $comp_drp_query = mysqli_query($conn, "SELECT id, company_name FROM companies ORDER BY company_name ASC");
        if($comp_drp_query && mysqli_num_rows($comp_drp_query) > 0){
            while($cRow = mysqli_fetch_assoc($comp_drp_query)){
                $companyOptions .= "<option value='{$cRow['id']}'>{$cRow['company_name']}</option>";
            }
        }
    } catch (mysqli_sql_exception $e) {}

} else {
    $designationsList .= "<option value='COO'>COO</option><option value='CTO'>CTO</option><option value='Manager'>Manager</option>";
    $designationTableRows .= "<tr><td colspan='2' style='padding: 10px; text-align:center;'>No DB Connection</td></tr>";
}


// Fetch Tasks Variables
$hasTasks = false;
$taskTableRows = "";
$totalTasks = "1,284";
$pendingTasks = "43";
$overdueTasks = "07";
$completedTasks = "18";

// Fetch Companies Variables
$hasCompanies = false;
$companyTableRows = "";
$totalCompanies = "0";

// Fetch Clients Variables
$hasClients = false;
$clientTableRows = "";
$totalClients = "0";

$hasDeals = false;
$dealTableRows = "";
$totalDeals = "0";

if(isset($conn)){
    // Fetch Tasks Data
    try {
        $task_query = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id DESC");
        if($task_query && mysqli_num_rows($task_query) > 0){
            $hasTasks = true;
            $totalTasks = mysqli_num_rows($task_query);
            $pendingTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status LIKE '%To-Do%' OR status LIKE '%Progress%'"))['count'] ?? 0;
            $overdueTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status = 'Overdue'"))['count'] ?? 0;
            $completedTasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks WHERE status = 'Done'"))['count'] ?? 0;
            
            while($row = mysqli_fetch_assoc($task_query)){
                $title = htmlspecialchars($row['title']);
                $desc = htmlspecialchars(mb_substr($row['description'], 0, 40)) . '...';
                $date = date('M d, Y', strtotime($row['due_date']));
                $assigned = htmlspecialchars($row['assigned_to']);
                
                $avatar_initials = strtoupper(substr($assigned, 0, 1)); 
                if(strpos($assigned, ' ') !== false){
                    $parts = explode(' ', $assigned);
                    $avatar_initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                }

                $pri = strtolower($row['priority']);
                $pri_class = 'medium';
                if($pri == 'high') $pri_class = 'high';
                if($pri == 'low') $pri_class = 'low';
                if($pri == 'emergency') $pri_class = 'emergency';

                $stat = strtolower($row['status']);
                $stat_class = 'todo';
                $stat_display = 'To-Do';
                if(strpos($stat, 'progress') !== false) { $stat_class = 'progress'; $stat_display = 'In-Progress'; }
                if($stat == 'done') { $stat_class = 'done'; $stat_display = 'Done'; }
                if($stat == 'overdue') { $stat_class = 'over'; $stat_display = 'Overdue'; }

                $taskTableRows .= "<tr>
                    <td><div class='tt-name'>{$title}</div><div class='tt-desc'>{$desc}</div></td>
                    <td><div class='tt-assignee'><div class='avatar'>{$avatar_initials}</div><span>{$assigned}</span></div></td>
                    <td><span class='pill {$pri_class}'>" . strtoupper($row['priority']) . "</span></td>
                    <td><span class='status-dot {$stat_class}'></span> <span class='status-text-t {$stat_class}'>{$stat_display}</span></td>
                    <td><span class='date-text'>{$date}</span></td>
                    <td style='width: 130px; text-align:right;'><button class='tt-action-btn'>Edit & Manage Task</button><button class='tt-rule-btn'>Task Rules & Auth</button></td>
                </tr>";
            }
        }
    } catch(mysqli_sql_exception $e) {}

   // Fetch Companies Data
try {
    $query_string = "
        SELECT c.id, c.company_name, c.assigned_agent, 
               (SELECT COUNT(*) FROM contacts WHERE company_id = c.id) AS total_dynamic_contacts 
        FROM companies c 
        ORDER BY c.id DESC
    ";
    
    $comp_query = mysqli_query($conn, $query_string);
    
    if($comp_query && mysqli_num_rows($comp_query) > 0){
        $hasCompanies = true;
        $totalCompanies = mysqli_num_rows($comp_query);
        
        while($row = mysqli_fetch_assoc($comp_query)){
            $c_name = htmlspecialchars($row['company_name']);
            $c_agent = htmlspecialchars($row['assigned_agent']);
            $c_contacts = htmlspecialchars($row['total_dynamic_contacts']); 
            $c_id = $row['id'];
            
            $companyTableRows .= "<tr>
                <td><input type='checkbox' class='tbl-checkbox'></td>
                <td><b>{$c_name}</b></td>
                <td>{$c_agent}</td>
                <td>{$c_contacts}</td>
                <td>
                    <div class='action-btns'>
                        <button class='btn-view' title='View' onclick=\"showToast('View Feature Coming Soon','success')\"><i class='fa-regular fa-eye'></i></button>
                        <form method='POST' id='delete-comp-{$c_id}' style='display:inline;'>
                            <input type='hidden' name='delete_company_id' value='{$c_id}'>
                            <input type='hidden' name='delete_company' value='1'>
                            <button type='button' class='btn-delete' onclick='confirmDelete(\"delete-comp-{$c_id}\", \"company\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                        </form>
                    </div>
                </td>
            </tr>";
        }
    }
} catch(mysqli_sql_exception $e) {}

    // Fetch Clients Data
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
                $cl_name = htmlspecialchars($row['name']);
                $cl_email = htmlspecialchars($row['email']);
                $cl_phone = htmlspecialchars($row['phone']);
                $cl_company = htmlspecialchars($row['company_name'] ?? 'N/A');
                $cl_id = $row['id'];
                
                $clientTableRows .= "<tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><b>{$cl_name}</b></td>
                    <td>{$cl_email}</td>
                    <td>{$cl_phone}</td>
                    <td>{$cl_company}</td>
                    <td>
                        <div class='action-btns'>
                            <button class='btn-view' title='View' onclick=\"showToast('View Client Coming Soon','success')\"><i class='fa-regular fa-eye'></i></button>
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

    // Fetch Deals Data
    try {
        $deal_query_str = "
            SELECT deals.*, companies.company_name 
            FROM deals 
            LEFT JOIN companies ON deals.company_id = companies.id 
            ORDER BY deals.id DESC
        ";
        $deal_query = mysqli_query($conn, $deal_query_str);
        if($deal_query && mysqli_num_rows($deal_query) > 0){
            $hasDeals = true;
            $totalDeals = mysqli_num_rows($deal_query);
            
            while($row = mysqli_fetch_assoc($deal_query)){
                $d_project = htmlspecialchars($row['project_name']);
                $d_company = htmlspecialchars($row['company_name'] ?? 'N/A');
                $d_amount = htmlspecialchars($row['currency'] . ' ' . number_format($row['total_amount'], 2));
                $d_status = htmlspecialchars($row['status']);
                $d_id = $row['id'];
                $dealData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                
                $dealTableRows .= "<tr>
                    <td><input type='checkbox' class='tbl-checkbox'></td>
                    <td><b>{$d_project}</b></td>
                    <td>{$d_company}</td>
                    <td>{$d_amount}</td>
                    <td><span class='pill' style='background:#f3f4f6; color:#374151;'>{$d_status}</span></td>
                    <td>
                        <div class='action-btns'>
                            <button class='btn-view' title='View' onclick='openViewDealModal({$dealData})'><i class='fa-regular fa-eye'></i></button>
                            <button class='btn-edit' title='Edit' onclick='openEditDealModal({$dealData})'><i class='fa-solid fa-pen'></i></button>
                            <form method='POST' id='delete-deal-{$d_id}' style='display:inline;'>
                                <input type='hidden' name='delete_deal_id' value='{$d_id}'>
                                <input type='hidden' name='delete_deal' value='1'>
                                <button type='button' class='btn-delete' onclick='confirmDelete(\"delete-deal-{$d_id}\", \"deal\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                            </form>
                        </div>
                    </td>
                </tr>";
            }
        }
    } catch(mysqli_sql_exception $e) {}
}

// Notification logic moved to notifications.php (included in navbar)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Systellio CRM</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* ========================================================================
           GLOBAL STYLES & RESET
        ======================================================================== */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: #f3f4f6; display: flex; height: 100vh; overflow: hidden; transition: background-color 0.3s, color 0.3s; color: #111827; }

        /* ========================================================================
           TOAST NOTIFICATION STYLES
        ======================================================================== */
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
        
        /* Navbar Actions (Icons & Profile) */
        
        
        .nav-icon-btn:hover { color: #3b82f6; }
        
        /* Notification Badge */

        /* ── Notification Panel ── */
        .notif-wrapper { position: relative; overflow: visible !important; }
        .notif-panel {
            display: none; position: fixed; top: 70px; right: 20px;
            width: 340px; background: #ffffff; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.18); border: 1px solid #e5e7eb;
            z-index: 9999; overflow: hidden;
        }
        .notif-panel.open { display: block; }
        .notif-panel-header {
            padding: 16px 20px; border-bottom: 1px solid #f3f4f6;
            display: flex; justify-content: space-between; align-items: center;
        }
        .notif-panel-header h3 { font-size: 15px; font-weight: 700; color: #111827; }
        .notif-panel-header span { font-size: 11px; color: #6b7280; cursor: pointer; font-weight: 600; }
        .notif-panel-header span:hover { color: #3b82f6; }
        .notif-list { max-height: 360px; overflow-y: auto; }
        .notif-item {
            display: flex; gap: 14px; padding: 14px 20px;
            border-bottom: 1px solid #f9fafb; cursor: pointer; transition: background 0.2s;
        }
        .notif-item:hover { background: #f9fafb; }
        .notif-item:last-child { border-bottom: none; }
        .notif-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .notif-body { flex: 1; }
        .notif-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 2px; }
        .notif-text { font-size: 13px; font-weight: 500; color: #111827; margin-bottom: 3px; line-height: 1.4; }
        .notif-time { font-size: 11px; color: #9ca3af; font-weight: 500; }
        .notif-empty { padding: 30px 20px; text-align: center; color: #9ca3af; font-size: 13px; }
        .notif-panel-footer { padding: 12px 20px; border-top: 1px solid #f3f4f6; text-align: center; }
        .notif-panel-footer a { font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; }

        /* Dark mode */
        body.dark-mode .notif-panel { background: #1e293b; border-color: #334155; box-shadow: 0 8px 30px rgba(0,0,0,0.4); }
        body.dark-mode .notif-panel-header { border-color: #334155; }
        body.dark-mode .notif-panel-header h3 { color: #f8fafc; }
        body.dark-mode .notif-item { border-color: #1e293b; }
        body.dark-mode .notif-item:hover { background: #0f172a; }
        body.dark-mode .notif-text { color: #e2e8f0; }
        body.dark-mode .notif-panel-footer { border-color: #334155; }

        .notification-badge { position: absolute; top: -4px; right: -4px; background-color: #ef4444; color: white; font-size: 9px; font-weight: bold; padding: 2px 5px; border-radius: 50%; border: 2px solid #ffffff; }
        body.dark-mode .notification-badge { border-color: #1e293b; } /* Dark mode border fix */

        
        .user-profile i { font-size: 24px; color: #3b82f6; }

        /* Overview Dashboard Cards */
        .dashboard-container, .task-container, .company-container { padding: 30px; }
        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; transition: 0.3s;}
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background-color: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.03); display: flex; align-items: center; justify-content: space-between; border: 1px solid #e5e7eb; transition: 0.3s;}
        .card-info h4 { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .card-info h2 { font-size: 28px; transition: 0.3s;}
        .card-icon { background-color: #eff6ff; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; color: #3b82f6; transition: 0.3s;}

        /* ========================================================================
           USER LIST SECTION & TABLE STYLES
        ======================================================================== */
        #userListSection { display: none; padding: 30px; }
        .user-list-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .user-list-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s;}
        .user-list-title p { font-size: 11px; color: #6b7280; font-weight: 500; }
        
        .header-buttons { display: flex; gap: 10px; }
        .create-btn { background-color: #000000; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;}
        .create-btn:hover { background-color: #1f2937; }
        .desig-btn { background-color: #3b82f6; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s;}
        .desig-btn:hover { background-color: #2563eb; }

        /* Filtering Tabs */
        .tabs-wrapper { margin-bottom: 20px; width: max-content; }
        .tab-top-line { height: 3px; width: 100%; background: linear-gradient(to right, #3b82f6 33%, #ef4444 33%, #ef4444 66%, #d1d5db 66%); border-radius: 3px 3px 0 0; }
        .tabs-container { display: flex; background: #ffffff; padding: 5px; border-radius: 0 0 6px 6px; gap: 5px; transition: 0.3s; border: 1px solid #e5e7eb; border-top: none;}
        .tab-btn { padding: 8px 18px; font-size: 12px; font-weight: 700; border: none; background: transparent; cursor: pointer; border-radius: 4px; color: #6b7280; display: flex; align-items: center; gap: 6px; transition: 0.3s;}
        .tab-btn.active { background: #f3f4f6; color: #111827; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        /* Data Table */
        .table-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db; transition: 0.3s; background: #ffffff;}
        .custom-table { width: 100%; border-collapse: collapse; text-align: center; font-size: 12px; }
        .custom-table th { background-color: #c4f042; padding: 14px 10px; font-weight: 700; color: #000000; border-bottom: 1px solid #d1d5db; transition: 0.3s;}
        .custom-table td { padding: 14px 10px; color: #374151; font-weight: 500; vertical-align: middle; border-right: 1px solid rgba(0,0,0,0.05); transition: 0.3s;}
        .custom-table td:last-child { border-right: none; }

        /* Pastel Row Styling */
        .custom-table tbody tr:nth-child(4n+1) { background-color: #e6fced; } 
        .custom-table tbody tr:nth-child(4n+2) { background-color: #fcedf6; } 
        .custom-table tbody tr:nth-child(4n+3) { background-color: #fceddb; } 
        .custom-table tbody tr:nth-child(4n+4) { background-color: #e6edff; } 

        .status-text { font-weight: 600; }
        .action-btns { display: flex; justify-content: center; gap: 6px; }
        .btn-view { background-color: #60a5fa; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-view:hover { background-color: #3b82f6; }
        .btn-edit { background-color: #4ade80; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-edit:hover { background-color: #22c55e; }
        .btn-delete { background-color: #f87171; color: white; padding: 6px 10px; border-radius: 4px; font-size: 11px; border: none; cursor: pointer; transition: 0.3s;}
        .btn-delete:hover { background-color: #ef4444; }


        /* ========================================================================
           COMPANY & ORGANIZATION STYLES (NEW DESIGN)
        ======================================================================== */
        #companySection, #accountsClientsSection { display: none; }
        
        .comp-header-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s; color: #111827;}
        .comp-header-title p { font-size: 13px; color: #6b7280; font-weight: 500; }
        
        /* Company Header Buttons */
        .btn-export { background-color: #10b981; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-export:hover { background-color: #059669; }
        
        .btn-upload { background-color: #1e293b; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-upload:hover { background-color: #0f172a; }
        
        .btn-add-client { background-color: #3b82f6; color: #ffffff; padding: 10px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s;}
        .btn-add-client:hover { background-color: #2563eb; }
        
        .comp-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .comp-search { position: relative; width: 300px; }
        .comp-search i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px;}
        .comp-search input { width: 100%; padding: 10px 15px 10px 38px; border: 1px solid #d1d5db; border-radius: 20px; font-size: 13px; font-family: 'Inter', sans-serif; outline: none; transition: 0.3s; color: #374151;}
        .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #eff6ff;}
        
        .comp-total { font-size: 13px; font-weight: 600; color: #4b5563; background: #ffffff; border: 1px solid #d1d5db; padding: 8px 15px; border-radius: 20px;}
        
        /* Generic checkbox styling for table */
        .tbl-checkbox { width: 16px; height: 16px; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer; accent-color: #3b82f6;}
        
        .comp-name { font-size: 14px; font-weight: 700; color: #111827; }
        
        .comp-agent { display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 13px; font-weight: 500;}
        .comp-agent i { color: #9ca3af; }
        
        .comp-contacts-pill { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; display: inline-block;}
        
        /* View / Delete Icon Buttons */
        .comp-icon-btn { width: 32px; height: 32px; border-radius: 4px; border: 1px solid #e5e7eb; background: transparent; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; transition: 0.3s; margin-right: 5px;}
        .comp-icon-btn.view { color: #3b82f6; }
        .comp-icon-btn.view:hover { background: #eff6ff; border-color: #3b82f6; }
        .comp-icon-btn.delete { color: #ef4444; }
        .comp-icon-btn.delete:hover { background: #fef2f2; border-color: #ef4444; }


        /* ========================================================================
   COMPANY MODAL STYLES (MATCHING SCREENSHOT)
======================================================================== */
.comp-modal-content { max-width: 600px; border-radius: 12px; padding: 35px 40px; }

/* Hide traditional outline on input fields, add nice bottom border */
.comp-input { width: 100%; border: none; border-bottom: 2px solid #e5e7eb; padding: 10px 0; font-size: 14px; outline: none; transition: 0.3s; background: transparent;}
.comp-input:focus { border-bottom-color: #3b82f6; }

.comp-select { width: 100%; border: none; border-bottom: 2px solid #e5e7eb; padding: 10px 0; font-size: 14px; outline: none; transition: 0.3s; background: transparent; cursor: pointer;}
.comp-select:focus { border-bottom-color: #3b82f6; }

.comp-input-label { font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; display: block;}

/* Company Info Separator Header */
.comp-info-header { display: flex; align-items: center; gap: 10px; color: #3b82f6; font-weight: 700; font-size: 15px; margin-top: 15px; margin-bottom: 5px;}
.comp-info-header::after { content: ''; flex-grow: 1; height: 1px; background: #bfdbfe; }
.comp-info-subtext { font-size: 11px; color: #6b7280; margin-bottom: 20px;}

.comp-save-btn { background-color: #3b82f6; color: white; border: none; padding: 12px 0; border-radius: 8px; width: 100%; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 15px;}
.comp-save-btn:hover { background-color: #2563eb; }

/* For 4 column grid (Social Links) */
.form-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }

/* Dark Mode Support for these fields */
body.dark-mode .comp-input { border-bottom-color: #475569; color: #f8fafc; }
body.dark-mode .comp-input:focus { border-bottom-color: #3b82f6; }
body.dark-mode .comp-select { border-bottom-color: #475569; color: #f8fafc; }
body.dark-mode .comp-select:focus { border-bottom-color: #3b82f6; }
body.dark-mode .comp-select option { background-color: #1e293b; color: #f8fafc; }
body.dark-mode .comp-info-header { color: #60a5fa; }
body.dark-mode .comp-info-header::after { background: #334155; }
body.dark-mode .comp-info-subtext { color: #94a3b8; }
        /* ========================================================================
           TASK MANAGEMENT STYLES
        ======================================================================== */
        #taskManagementSection { display: none; }
        
        .task-header-title h1 { font-size: 26px; font-weight: 800; margin-bottom: 4px; letter-spacing: -0.5px; transition: 0.3s; color: #111827;}
        .task-header-title p { font-size: 13px; color: #6b7280; font-weight: 500; }

        /* Statistics Cards */
        .task-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 25px; margin-bottom: 30px;}
        .task-card { background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; box-shadow: 0 2px 10px rgba(0,0,0,0.02); border-left: 4px solid #3b82f6; display: flex; flex-direction: column; justify-content: space-between; transition: 0.3s;}
        .task-card.pending { border-left-color: #f59e0b; }
        .task-card.overdue { border-left-color: #ef4444; }
        .task-card.completed { border-left-color: #10b981; }

        .tc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .tc-header h4 { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
        .tc-header i { font-size: 16px; color: #9ca3af; }
        
        .tc-body h2 { font-size: 32px; color: #111827; font-weight: 800; line-height: 1; margin-bottom: 8px; transition: 0.3s;}
        .tc-body p { font-size: 11px; color: #10b981; font-weight: 600; }
        .tc-body p.warn { color: #9ca3af; }
        .tc-body p.danger { color: #ef4444; }

        /* Task Table Area */
        .task-table-wrapper { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; transition: 0.3s;}
        
        .tt-controls { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 15px; transition: 0.3s;}
        .tt-tabs { display: flex; gap: 20px; }
        .tt-tab { font-size: 14px; font-weight: 600; color: #6b7280; cursor: pointer; position: relative; padding-bottom: 15px; transition: 0.3s;}
        .tt-tab.active { color: #3b82f6; }
        .tt-tab.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 3px; background: #3b82f6; border-radius: 3px 3px 0 0; }
        
        .tt-actions { display: flex; gap: 15px; font-size: 12px; font-weight: 600; color: #6b7280; transition: 0.3s;}
        .tt-actions span { cursor: pointer; display: flex; align-items: center; gap: 5px; transition: 0.3s;}
        .tt-actions span:hover { color: #111827; }

        .task-table { width: 100%; border-collapse: collapse; text-align: left; }
        .task-table th { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; padding: 15px 10px; border-bottom: 1px solid #e5e7eb; font-weight: 700; background: #f9fafb; transition: 0.3s;}
        .task-table td { padding: 15px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; transition: 0.3s;}
        
        .tt-name { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 4px; transition: 0.3s;}
        .tt-desc { font-size: 12px; color: #9ca3af; transition: 0.3s;}
        
        .tt-assignee { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #3b82f6; display: flex; justify-content: center; align-items: center; font-size: 11px; font-weight: 700; }
        .tt-assignee span { font-size: 13px; font-weight: 600; color: #374151; transition: 0.3s;}

        .pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .pill.high { background: #fee2e2; color: #ef4444; }
        .pill.medium { background: #fef3c7; color: #f59e0b; }
        .pill.low { background: #d1fae5; color: #10b981; }
        .pill.emergency { background: #ef4444; color: #ffffff; }

        .status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; margin-right: 6px; }
        .status-dot.progress { background: #3b82f6; }
        .status-dot.todo { background: #9ca3af; }
        .status-dot.done { background: #10b981; }
        .status-dot.over { background: #ef4444; }

        .status-text-t { font-size: 12px; font-weight: 600; }
        .status-text-t.progress { color: #3b82f6; }
        .status-text-t.todo { color: #6b7280; }
        .status-text-t.done { color: #10b981; }
        .status-text-t.over { color: #ef4444; }

        .date-text { font-size: 13px; font-weight: 600; color: #6b7280; }
        .date-text.overdue { color: #ef4444; }

        /* Task Action Buttons */
        .tt-action-btn { background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: block; margin-bottom: 5px; width: 100%; transition: 0.3s;}
        .tt-action-btn:hover { background: #bbf7d0; }
        .tt-rule-btn { background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; display: block; width: 100%; transition: 0.3s;}
        .tt-rule-btn:hover { background: #fecaca; }

        /* Pagination Styling */
        .pagination { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; font-size: 12px; color: #6b7280; }
        .page-numbers { display: flex; gap: 5px; }
        .page-no { width: 28px; height: 28px; display: flex; justify-content: center; align-items: center; border-radius: 4px; cursor: pointer; transition: 0.3s;}
        .page-no:hover { background: #f3f4f6; }
        .page-no.active { background: #1e3a8a; color: white; }
        body.dark-mode .page-no:hover { background: #334155; }

        /* ========================================================================
           GENERAL MODALS (POPUPS) STYLES
        ======================================================================== */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; padding: 30px; border-radius: 10px; width: 100%; max-width: 650px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; transition: 0.3s;}
        .small-modal { max-width: 450px; }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { font-size: 20px; font-weight: 700; transition: 0.3s;}
        .close-btn { font-size: 20px; cursor: pointer; color: #6b7280; border: none; background: none; transition: 0.3s;}
        .close-btn:hover { color: #ef4444; }

        /* Form Grid within General Modals */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 5px; position: relative; } 
        .full-width { grid-column: span 2; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; transition: 0.3s;}
        
        /* General form inputs (White background for light mode) */
        .form-group input:not(.task-input-dark):not(.comp-input), 
        .form-group select:not(.task-tag-select):not(.comp-select) { 
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; font-family: 'Inter', sans-serif; background-color: #f9fafb; transition: 0.3s;
        }
        .form-group input[type="file"] { background: transparent; border: none; padding: 5px 0;}
        .form-group input:focus:not(.task-input-dark):not(.comp-input), 
        .form-group select:focus:not(.task-tag-select):not(.comp-select) { border-color: #3b82f6; background-color: #fff; }
        
        /* Show/Hide Password Toggle Style */
        .password-toggle { position: absolute; right: 12px; top: 32px; cursor: pointer; color: #6b7280; }
        .password-error { color: #ef4444; font-size: 10px; font-weight: 600; margin-top: 4px; display: none; }

        .submit-btn { background-color: #000000; color: #ffffff; padding: 12px; border: none; border-radius: 6px; width: 100%; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background-color: #1f2937; }
        .submit-btn:disabled { background-color: #9ca3af; cursor: not-allowed; }

        /* Read-only data box for View Modal */
        .view-data-box { background: #f9fafb; padding: 10px 12px; border-radius: 6px; border: 1px solid #e5e7eb; font-size: 13px; font-weight: 500; word-break: break-all; min-height: 40px; display: flex; align-items: center; transition: 0.3s;}
        .desig-text { color: #374151; }

        /* Coming Soon Fallback Page */
        .coming-soon-container { display: none; flex-direction: column; justify-content: center; align-items: center; height: 70vh; text-align: center; }
        .coming-soon-icon { font-size: 60px; color: #9ca3af; margin-bottom: 20px; }
        .coming-soon-title { font-size: 28px; font-weight: 700; margin-bottom: 10px; transition: 0.3s;}
        .coming-soon-text { color: #6b7280; font-size: 15px; max-width: 400px; }

        /* ========================================================================
           CUSTOM CREATE TASK MODAL STYLES (NEW - DARK THEME)
        ======================================================================== */
        .task-modal-content { background-color: #0b0f19 !important; color: #f8fafc; border: 1px solid #1e293b; max-width: 750px !important; padding: 25px 30px !important; border-radius: 12px !important; overflow: visible;}
        
        .task-modal-label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; color: #f8fafc; }
        .task-modal-label span.req { color: #ef4444; }
        
        /* Title Input */
        .task-input-dark { background-color: #0b0f19; border: 1px solid #3b82f6; color: #f8fafc; width: 100%; padding: 12px 15px; border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; margin-bottom: 20px; box-shadow: 0 0 0 1px #3b82f6; }
        .task-input-dark::placeholder { color: #475569; }
        
        /* Textarea Toolbar */
        .task-toolbar { display: flex; justify-content: space-between; align-items: center; background-color: #1a1d24; border: 1px solid #2d3748; border-bottom: none; border-radius: 8px 8px 0 0; padding-right: 15px;}
        .task-tab { background-color: #0d1117; color: #f8fafc; font-size: 13px; font-weight: 600; padding: 12px 20px; border-right: 1px solid #2d3748; border-radius: 8px 0 0 0; }
        .task-tools { display: flex; gap: 15px; color: #94a3b8; font-size: 14px; }
        .task-tools i { cursor: pointer; transition: 0.3s; }
        .task-tools i:hover { color: #f8fafc; }
        
        /* Textarea */
        .task-textarea { background-color: #0d1117; border: 1px solid #2d3748; color: #f8fafc; width: 100%; padding: 15px; border-radius: 0 0 8px 8px; font-size: 14px; outline: none; resize: vertical; min-height: 200px; margin-bottom: 20px;}
        .task-textarea::placeholder { color: #475569; }

        /* Footer Buttons Container */
        .task-modal-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px;}
        .task-footer-left { display: flex; gap: 10px; align-items: center; }
        
        /* Custom Task Tag Selectors */
        .task-tag-wrapper { position: relative; display: inline-flex; align-items: center; background-color: #1e293b; border: 1px dashed #475569; border-radius: 6px; transition: 0.3s; height: 32px; cursor: pointer; }
        .task-tag-wrapper:hover { background-color: #334155; border-color: #64748b; }
        .task-tag-wrapper i { position: absolute; left: 10px; color: #94a3b8; font-size: 12px; pointer-events: none; transition: 0.3s; }
        .task-tag-wrapper:hover i { color: #f8fafc; }
        
        .task-tag-select { background: transparent !important; color: #cbd5e1 !important; border: none !important; padding: 0 26px 0 28px !important; font-size: 12px !important; font-weight: 500 !important; cursor: pointer; outline: none !important; font-family: 'Inter', sans-serif; appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="%2394a3b8" viewBox="0 0 320 512"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"/></svg>') !important; background-repeat: no-repeat !important; background-position: right 10px center !important; height: 100%; }
        .task-tag-select:focus { color: #f8fafc !important; }
        .task-tag-select option { background-color: #1e293b; color: #f8fafc; }

        .task-tag-btn { background-color: #1e293b; color: #cbd5e1; border: 1px dashed #475569; padding: 0 14px 0 10px; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.3s; height: 32px; font-family: 'Inter', sans-serif; }
        .task-tag-btn i { color: #94a3b8; font-size: 12px; transition: 0.3s;}
        .task-tag-btn:hover { background-color: #334155; border-color: #64748b; color: #f8fafc; }
        .task-tag-btn:hover i { color: #f8fafc; }
        
        .task-footer-right { display: flex; align-items: center; gap: 15px; }
        
        .task-checkbox-label { font-size: 13px; color: #cbd5e1; display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500; user-select: none; }
        .task-checkbox { appearance: none; -webkit-appearance: none; width: 16px; height: 16px; background-color: transparent; border: 2px solid #475569; border-radius: 4px; cursor: pointer; position: relative; transition: all 0.2s ease; margin: 0; }
        .task-checkbox:checked { background-color: #16a34a; border-color: #16a34a; }
        .task-checkbox:checked::after { content: ''; position: absolute; left: 4px; top: 1px; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg); }
        .task-checkbox-label:hover .task-checkbox:not(:checked) { border-color: #64748b; }
        
        .task-cancel-btn { background: transparent; color: #f8fafc; border: none; font-size: 13px; font-weight: 600; cursor: pointer; padding: 8px 12px; border-radius: 6px; transition: 0.3s;}
        .task-cancel-btn:hover { background: #334155; }
        
        .task-create-btn { background: #16a34a; color: #ffffff; border: none; font-size: 13px; font-weight: 700; cursor: pointer; padding: 0 12px; border-radius: 6px; display: flex; align-items: center; height: 36px; transition: 0.3s;}
        .task-create-btn:hover { background: #15803d; }
        .task-create-btn .btn-divider { width: 1px; height: 18px; background-color: rgba(255,255,255,0.4); margin: 0 10px; }

        /* ========================================================================
           ADVANCED PREMIUM DARK MODE STYLES 
        ======================================================================== */
        body.dark-mode { background-color: #0f172a; color: #f8fafc; }
        body.dark-mode .main-content { background-color: #0f172a; }
        body.dark-mode 
        body.dark-mode 
        body.dark-mode .nav-icon-btn:hover { color: #f8fafc; }
        
        body.dark-mode .card, body.dark-mode .task-card, body.dark-mode .task-table-wrapper, body.dark-mode .comp-table-wrapper { background-color: #1e293b; border-color: #334155; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
        body.dark-mode .card-icon { background-color: #334155; color: #3b82f6; }
        body.dark-mode .tc-body h2 { color: #f8fafc; }
        body.dark-mode .comp-header-title h1 { color: #f8fafc; }

        body.dark-mode .tabs-container { background: #1e293b; border-color: #334155; }
        body.dark-mode .tab-btn { color: #94a3b8; }
        body.dark-mode .tab-btn:hover { color: #f8fafc; }
        body.dark-mode .tab-btn.active { background: #0f172a; color: #f8fafc; }

        body.dark-mode .table-wrapper { border-color: #334155; background: #1e293b; }
        body.dark-mode .custom-table th, body.dark-mode .task-table th { background-color: #334155; color: #f8fafc; border-color: #475569; }
        body.dark-mode .custom-table td, body.dark-mode .task-table td { color: #cbd5e1; border-color: #334155; }
        body.dark-mode .tt-name { color: #f8fafc; }
        body.dark-mode .tt-assignee span { color: #cbd5e1; }
        
        body.dark-mode .custom-table tbody tr:nth-child(even) { background-color: #1e293b; } 
        body.dark-mode .custom-table tbody tr:nth-child(odd) { background-color: #0f172a; } 
        body.dark-mode .custom-table tbody tr:hover, body.dark-mode .task-table tr:hover { background-color: #334155; }

        body.dark-mode .tt-controls { border-color: #334155; }
        body.dark-mode .tt-tab { color: #94a3b8; }
        body.dark-mode .tt-actions { color: #94a3b8; }
        body.dark-mode .tt-actions span:hover { color: #f8fafc; }
        body.dark-mode .page-no:hover { background: #334155; }

        body.dark-mode .comp-search input { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .comp-search input:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px #1e3a8a;}
        body.dark-mode .comp-total { background-color: #0f172a; color: #cbd5e1; border-color: #334155; }
        
        body.dark-mode .modal-content:not(.task-modal-content) { background-color: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
        body.dark-mode .form-group label { color: #cbd5e1; }
        body.dark-mode .form-group input:not(.task-input-dark):not([type="file"]), 
        body.dark-mode .form-group select:not(.task-tag-select) { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .form-group input:focus:not(.task-input-dark):not([type="file"]), 
        body.dark-mode .form-group select:focus:not(.task-tag-select) { border-color: #3b82f6; background-color: #1e293b; }
        body.dark-mode .form-group input[type="file"] { color: #cbd5e1; }
        
        body.dark-mode .view-data-box { background-color: #0f172a; color: #f8fafc; border-color: #334155; }
        body.dark-mode .create-btn, body.dark-mode .submit-btn, body.dark-mode .desig-btn { background-color: #3b82f6; }
        body.dark-mode .create-btn:hover, body.dark-mode .submit-btn:hover, body.dark-mode .desig-btn:hover { background-color: #2563eb; }

        body.dark-mode .desig-text { color: #cbd5e1 !important; }
        
        /* SweetAlert Component Dark Mode Fix */
        .swal2-container { z-index: 9999 !important; }
        body.dark-mode .swal2-popup { background-color: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        body.dark-mode .swal2-title, body.dark-mode .swal2-html-container { color: #f8fafc; }

        /* ================================================================
           DASHBOARD OVERVIEW — Professional CRM Style
           ================================================================ */
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&display=swap');

        #mainDashboardContent { padding: 28px 30px 36px; }

        .ov-heading { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px; }
        .ov-heading-left h1 { font-size:22px; font-weight:800; color:#0f172a; letter-spacing:-0.4px; margin-bottom:2px; }
        .ov-heading-left p   { font-size:12px; color:#94a3b8; font-weight:500; }
        .ov-date-badge { background:#f1f5f9; border:1px solid #e2e8f0; color:#64748b; font-size:11px; font-weight:600; padding:6px 14px; border-radius:20px; font-family:'DM Mono',monospace; }

        /* KPI Strip */
        .kpi-strip { display:grid; grid-template-columns:repeat(6,1fr); gap:14px; margin-bottom:20px; }
        .kpi-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 16px; position:relative; overflow:hidden; transition:transform .2s,box-shadow .2s; cursor:default; }
        .kpi-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.07); }
        .kpi-card::before { content:''; position:absolute; top:0;left:0;right:0; height:3px; border-radius:12px 12px 0 0; }
        .kpi-card.kpi-blue::before   { background:#3b82f6; }
        .kpi-card.kpi-violet::before { background:#8b5cf6; }
        .kpi-card.kpi-green::before  { background:#10b981; }
        .kpi-card.kpi-amber::before  { background:#f59e0b; }
        .kpi-card.kpi-rose::before   { background:#f43f5e; }
        .kpi-card.kpi-cyan::before   { background:#06b6d4; }
        .kpi-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:12px; }
        .kpi-blue   .kpi-icon { background:#eff6ff;color:#3b82f6; }
        .kpi-violet .kpi-icon { background:#ede9fe;color:#8b5cf6; }
        .kpi-green  .kpi-icon { background:#ecfdf5;color:#10b981; }
        .kpi-amber  .kpi-icon { background:#fffbeb;color:#f59e0b; }
        .kpi-rose   .kpi-icon { background:#fff1f2;color:#f43f5e; }
        .kpi-cyan   .kpi-icon { background:#ecfeff;color:#06b6d4; }
        .kpi-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;margin-bottom:4px; }
        .kpi-value { font-size:28px;font-weight:800;color:#0f172a;line-height:1;letter-spacing:-1px;font-family:'DM Mono',monospace;margin-bottom:6px; }
        .kpi-value.kpi-value-sm { font-size:18px; margin-top:4px; }
        .kpi-sub   { font-size:11px;color:#64748b;font-weight:500; }
        .kpi-sub b { color:#0f172a; }

        /* Middle & Bottom panels */
        .ov-mid-row    { display:grid; grid-template-columns:1.6fr 1fr 1.4fr; gap:16px; margin-bottom:20px; }
        .ov-bottom-row { display:grid; grid-template-columns:1.6fr 1.2fr 1.2fr; gap:16px; }
        .ov-panel { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; }
        .ov-panel-title { font-size:13px;font-weight:700;color:#111827;margin-bottom:16px;display:flex;align-items:center;gap:8px; }
        .ov-panel-title i { font-size:13px;color:#94a3b8; }

        /* Deal Funnel */
        .funnel-row   { display:flex;align-items:center;gap:10px;margin-bottom:12px; }
        .funnel-label { width:95px;font-size:11px;font-weight:600;color:#374151;flex-shrink:0; }
        .funnel-bar-wrap { flex:1;background:#f1f5f9;border-radius:20px;height:8px;overflow:hidden; }
        .funnel-bar   { height:100%;border-radius:20px;transition:width .6s ease; }
        .funnel-count { font-size:11px;font-weight:700;color:#374151;font-family:'DM Mono',monospace;width:24px;text-align:right;flex-shrink:0; }
        .funnel-deal-total { margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center; }
        .funnel-deal-total span { font-size:11px;color:#94a3b8;font-weight:500; }
        .funnel-deal-total strong { font-size:18px;font-weight:800;color:#0f172a;font-family:'DM Mono',monospace; }

        /* Task Donut */
        .task-ring-wrap { display:flex;align-items:center;justify-content:center;flex-direction:column;gap:16px; }
        .donut-svg { transform:rotate(-90deg); }
        .donut-bg  { fill:none;stroke:#f1f5f9; }
        .trl-row   { display:flex;align-items:center;justify-content:space-between;padding:5px 0;font-size:12px;border-bottom:1px solid #f8fafc; }
        .trl-dot   { width:8px;height:8px;border-radius:50%;flex-shrink:0; }
        .trl-name  { flex:1;margin-left:8px;color:#374151;font-weight:500; }
        .trl-num   { font-weight:700;color:#0f172a;font-family:'DM Mono',monospace; }

        /* User Breakdown */
        .ubl-row    { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8fafc; }
        .ubl-row:last-child { border-bottom:none; }
        .ubl-avatar { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0; }
        .ubl-info   { flex:1; }
        .ubl-role   { font-size:12px;font-weight:700;color:#111827; }
        .ubl-stat   { font-size:11px;color:#94a3b8;font-weight:500; }
        .ubl-count  { font-size:22px;font-weight:800;color:#0f172a;font-family:'DM Mono',monospace; }

        /* Mini tables */
        .mini-table { width:100%;border-collapse:collapse;font-size:12px; }
        .mini-table th { background:#f8fafc;padding:8px 10px;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;text-align:left;border-bottom:1px solid #f1f5f9; }
        .mini-table td { padding:10px;border-bottom:1px solid #f8fafc;color:#374151;font-weight:500;vertical-align:middle; }
        .mini-table tr:last-child td { border-bottom:none; }
        .mini-deal-name { font-weight:700;color:#111827;max-width:130px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .ov-panel-footer { margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;text-align:center; }
        .ov-panel-footer a { font-size:11px;font-weight:700;color:#3b82f6;text-decoration:none; }
        .ov-panel-footer a:hover { text-decoration:underline; }
        .ov-empty { text-align:center;padding:24px 10px;color:#cbd5e1;font-size:12px; }
        .ov-empty i { font-size:28px;display:block;margin-bottom:8px; }

        /* Dark mode */
        body.dark-mode #mainDashboardContent .ov-heading-left h1 { color:#f8fafc; }
        body.dark-mode .ov-date-badge { background:#0f172a;border-color:#334155;color:#64748b; }
        body.dark-mode .kpi-card, body.dark-mode .ov-panel { background:#1e293b;border-color:#334155; }
        body.dark-mode .kpi-value, body.dark-mode .ov-panel-title,
        body.dark-mode .ubl-count, body.dark-mode .funnel-deal-total strong,
        body.dark-mode .mini-deal-name { color:#f8fafc; }
        body.dark-mode .kpi-label, body.dark-mode .kpi-sub, body.dark-mode .ov-empty { color:#64748b; }
        body.dark-mode .kpi-sub b { color:#e2e8f0; }
        body.dark-mode .funnel-bar-wrap { background:#0f172a; }
        body.dark-mode .funnel-label, body.dark-mode .funnel-count { color:#94a3b8; }
        body.dark-mode .funnel-deal-total { border-color:#334155; }
        body.dark-mode .mini-table th { background:#0f172a;color:#64748b;border-color:#1e293b; }
        body.dark-mode .mini-table td { border-color:#1e293b;color:#94a3b8; }
        body.dark-mode .trl-row, body.dark-mode .ubl-row { border-color:#1e293b; }
        body.dark-mode .trl-name, body.dark-mode .ubl-role { color:#e2e8f0; }
        body.dark-mode .trl-num { color:#94a3b8; }
        body.dark-mode .ov-panel-footer { border-color:#334155; }
        body.dark-mode .kpi-blue   .kpi-icon { background:#1e3a5f; }
        body.dark-mode .kpi-violet .kpi-icon { background:#2e1065; }
        body.dark-mode .kpi-green  .kpi-icon { background:#052e16; }
        body.dark-mode .kpi-amber  .kpi-icon { background:#2d1a00; }
        body.dark-mode .kpi-rose   .kpi-icon { background:#2d0a16; }
        body.dark-mode .kpi-cyan   .kpi-icon { background:#082f49; }
        @media(max-width:1280px){
            .kpi-strip{grid-template-columns:repeat(3,1fr);}
            .ov-mid-row,.ov-bottom-row{grid-template-columns:1fr 1fr;}
        }
        @media(max-width:900px){
            .kpi-strip{grid-template-columns:repeat(2,1fr);}
            .ov-mid-row,.ov-bottom-row{grid-template-columns:1fr;}
        }
    </style>
</head>
<body>

    <div id="toastBox">
        <i id="toastIcon" class="fa-solid fa-circle-check"></i>
        <span id="toastMsg">Action Successful!</span>
    </div>

    <?php $activePage = 'dashboard'; include 'sidebar.php'; ?>

    <div class="main-content">
        
        <?php include 'topbar.php'; ?>

        <?php
        /* ── Overview: fetch all data from DB ── */
        $ov = ['total_users'=>0,'active_users'=>0,'inactive_users'=>0,'admins'=>0,'managers'=>0,'agents'=>0,
               'total_companies'=>0,'total_contacts'=>0,'total_deals'=>0,'deal_value'=>0,
               'deals_won'=>0,'deals_lost'=>0,'total_tasks'=>0,'tasks_todo'=>0,'tasks_progress'=>0,
               'tasks_done'=>0,'tasks_overdue'=>0,'total_campaigns'=>0,'camp_active'=>0,'camp_planning'=>0,
               'recent_deals'=>[],'recent_tasks'=>[],'recent_users'=>[]];
        if(isset($conn)){
            $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t,SUM(status='active') a,SUM(status='inactive') i,SUM(role='admin') adm,SUM(role='manager') mgr,SUM(role='agent') agt FROM users"));
            if($r){$ov['total_users']=(int)$r['t'];$ov['active_users']=(int)$r['a'];$ov['inactive_users']=(int)$r['i'];$ov['admins']=(int)$r['adm'];$ov['managers']=(int)$r['mgr'];$ov['agents']=(int)$r['agt'];}
            $r2=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM companies")); if($r2) $ov['total_companies']=(int)$r2['c'];
            $r3=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM contacts"));  if($r3) $ov['total_contacts']=(int)$r3['c'];
            $r4=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t,COALESCE(SUM(deal_value),0) v,SUM(stage='Won') w,SUM(stage='Lost') l FROM deals"));
            if($r4){$ov['total_deals']=(int)$r4['t'];$ov['deal_value']=(float)$r4['v'];$ov['deals_won']=(int)$r4['w'];$ov['deals_lost']=(int)$r4['l'];}
            $r5=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t,SUM(status='To-Do') td,SUM(status='In-Progress') ip,SUM(status='Done') d,SUM(due_date<CURDATE() AND status!='Done') ov FROM tasks"));
            if($r5){$ov['total_tasks']=(int)$r5['t'];$ov['tasks_todo']=(int)$r5['td'];$ov['tasks_progress']=(int)$r5['ip'];$ov['tasks_done']=(int)$r5['d'];$ov['tasks_overdue']=(int)$r5['ov'];}
            $r6=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) t,SUM(status='Active') a,SUM(status='Planning') p FROM campaigns"));
            if($r6){$ov['total_campaigns']=(int)$r6['t'];$ov['camp_active']=(int)$r6['a'];$ov['camp_planning']=(int)$r6['p'];}
            $dq=mysqli_query($conn,"SELECT deal_name,deal_value,currency,stage FROM deals ORDER BY id DESC LIMIT 5"); if($dq) while($row=mysqli_fetch_assoc($dq)) $ov['recent_deals'][]=$row;
            $tq=mysqli_query($conn,"SELECT title,status,priority,due_date FROM tasks ORDER BY id DESC LIMIT 4"); if($tq) while($row=mysqli_fetch_assoc($tq)) $ov['recent_tasks'][]=$row;
            $uq=mysqli_query($conn,"SELECT name,role,status FROM users ORDER BY id DESC LIMIT 4"); if($uq) while($row=mysqli_fetch_assoc($uq)) $ov['recent_users'][]=$row;
        }
        function ovFmt($v,$c='USD'){if($v>=1000000)return $c.' '.number_format($v/1000000,1).'M';if($v>=1000)return $c.' '.number_format($v/1000,1).'K';return $c.' '.number_format($v,0);}
        function ovStage($s){$m=['Lead'=>['#dbeafe','#1d4ed8'],'Proposal'=>['#fef9c3','#a16207'],'Negotiation'=>['#fff7ed','#c2410c'],'Won'=>['#dcfce7','#15803d'],'Lost'=>['#fee2e2','#b91c1c']];$c=$m[$s]??['#f3f4f6','#374151'];return "<span style='background:{$c[0]};color:{$c[1]};padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;'>$s</span>";}
        function ovTask($s){$m=['To-Do'=>['#f3f4f6','#6b7280'],'In-Progress'=>['#dbeafe','#1d4ed8'],'Done'=>['#dcfce7','#15803d']];$c=$m[$s]??['#f3f4f6','#374151'];return "<span style='background:{$c[0]};color:{$c[1]};padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;'>$s</span>";}
        function ovPrio($p){$m=['High'=>['#fee2e2','#b91c1c'],'Medium'=>['#fef3c7','#b45309'],'Low'=>['#dcfce7','#15803d']];$c=$m[$p]??['#f3f4f6','#374151'];return "<span style='background:{$c[0]};color:{$c[1]};padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;'>$p</span>";}
        function ovRole($r){$l=ucfirst(str_replace('_',' ',$r));$m=['super_admin'=>['#ede9fe','#6d28d9'],'admin'=>['#dbeafe','#1d4ed8'],'manager'=>['#fef3c7','#b45309'],'agent'=>['#dcfce7','#15803d']];$c=$m[$r]??['#f3f4f6','#374151'];return "<span style='background:{$c[0]};color:{$c[1]};padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700;'>$l</span>";}
        ?>

        <div id="mainDashboardContent">

            <!-- Heading -->
            <div class="ov-heading">
                <div class="ov-heading-left">
                    <h1>CRM Overview</h1>
                    <p>Welcome back, <b><?php echo htmlspecialchars($_SESSION['name']??'Super Admin'); ?></b> — Here’s a full breakdown of your CRM.</p>
                </div>
                <div class="ov-date-badge"><i class="fa-regular fa-calendar" style="margin-right:6px;"></i><?php echo date('D, d M Y'); ?></div>
            </div>

            <!-- ROW 1: 6 KPI Cards -->
            <div class="kpi-strip">
                <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="kpi-label">Total Users</div>
                    <div class="kpi-value"><?php echo $ov['total_users']; ?></div>
                    <div class="kpi-sub"><b><?php echo $ov['active_users']; ?></b> active &nbsp;·&nbsp; <b><?php echo $ov['inactive_users']; ?></b> inactive</div>
                </div>
                <div class="kpi-card kpi-violet">
                    <div class="kpi-icon"><i class="fa-solid fa-building"></i></div>
                    <div class="kpi-label">Companies</div>
                    <div class="kpi-value"><?php echo $ov['total_companies']; ?></div>
                    <div class="kpi-sub"><b><?php echo $ov['total_contacts']; ?></b> total contacts</div>
                </div>
                <div class="kpi-card kpi-green">
                    <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                    <div class="kpi-label">Total Deal Value</div>
                    <div class="kpi-value kpi-value-sm"><?php echo ovFmt($ov['deal_value']); ?></div>
                    <div class="kpi-sub"><b><?php echo $ov['total_deals']; ?></b> deals &nbsp;·&nbsp; <b><?php echo $ov['deals_won']; ?></b> won</div>
                </div>
                <div class="kpi-card kpi-amber">
                    <div class="kpi-icon"><i class="fa-solid fa-list-check"></i></div>
                    <div class="kpi-label">Tasks</div>
                    <div class="kpi-value"><?php echo $ov['total_tasks']; ?></div>
                    <div class="kpi-sub"><b style="color:#ef4444;"><?php echo $ov['tasks_overdue']; ?></b> overdue &nbsp;·&nbsp; <b><?php echo $ov['tasks_progress']; ?></b> in progress</div>
                </div>
                <div class="kpi-card kpi-rose">
                    <div class="kpi-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="kpi-label">Campaigns</div>
                    <div class="kpi-value"><?php echo $ov['total_campaigns']; ?></div>
                    <div class="kpi-sub"><b><?php echo $ov['camp_active']; ?></b> active &nbsp;·&nbsp; <b><?php echo $ov['camp_planning']; ?></b> planning</div>
                </div>
                <div class="kpi-card kpi-cyan">
                    <div class="kpi-icon"><i class="fa-solid fa-address-book"></i></div>
                    <div class="kpi-label">Clients</div>
                    <div class="kpi-value"><?php echo $ov['total_contacts']; ?></div>
                    <div class="kpi-sub">across <b><?php echo $ov['total_companies']; ?></b> companies</div>
                </div>
            </div>

            <!-- ROW 2: Deal Funnel | Task Donut | Team Breakdown -->
            <div class="ov-mid-row">

                <!-- Deal Stage Funnel -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-filter"></i> Deal Pipeline — Stage Breakdown</div>
                    <?php
                    $stages=['Lead'=>['#60a5fa',0],'Proposal'=>['#a78bfa',0],'Negotiation'=>['#f97316',0],'Won'=>['#34d399',0],'Lost'=>['#f87171',0]];
                    if(isset($conn)){$sq=mysqli_query($conn,"SELECT stage,COUNT(*) c FROM deals GROUP BY stage");if($sq)while($sr=mysqli_fetch_assoc($sq))if(isset($stages[$sr['stage']]))$stages[$sr['stage']][1]=(int)$sr['c'];}
                    $mx=max(1,max(array_column($stages,1)));
                    foreach($stages as $lbl=>[$col,$cnt]):$pct=round($cnt/$mx*100);
                    ?>
                    <div class="funnel-row">
                        <div class="funnel-label"><?php echo $lbl; ?></div>
                        <div class="funnel-bar-wrap"><div class="funnel-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $col; ?>;"></div></div>
                        <div class="funnel-count"><?php echo $cnt; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div class="funnel-deal-total">
                        <span>Total pipeline value</span>
                        <strong><?php echo ovFmt($ov['deal_value']); ?></strong>
                    </div>
                </div>

                <!-- Task Donut -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-chart-pie"></i> Task Status</div>
                    <?php
                    $tTot=max(1,$ov['total_tasks']); $r2=$ov['tasks_progress']; $r3=$ov['tasks_todo']; $r4t=$ov['tasks_done']; $r5t=$ov['tasks_overdue'];
                    $rv=46;$circ=2*M_PI*$rv;
                    $segs=[[$r4t,'#34d399'],[$r2,'#60a5fa'],[$r3,'#d1d5db'],[$r5t,'#f87171']];
                    $off=0;$svgp='';
                    foreach($segs as [$sv,$sc]){$frac=$sv/$tTot;$dash=$frac*$circ;$gap=$circ-$dash;
                        $svgp.="<circle class='donut-bg' cx='60' cy='60' r='46' stroke-width='12'/>";
                        $svgp.="<circle cx='60' cy='60' r='46' fill='none' stroke='{$sc}' stroke-width='12' stroke-dasharray='{$dash} {$gap}' stroke-dashoffset='-{$off}' stroke-linecap='round'/>";
                        $off+=$dash;}
                    ?>
                    <div class="task-ring-wrap">
                        <svg class="donut-svg" width="120" height="120" viewBox="0 0 120 120">
                            <?php echo $svgp; ?>
                            <g transform="rotate(90,60,60)">
                                <text x="60" y="56" text-anchor="middle" font-size="20" font-weight="800" fill="#111827" font-family="DM Mono,monospace"><?php echo $ov['total_tasks']; ?></text>
                                <text x="60" y="70" text-anchor="middle" font-size="9"  font-weight="600" fill="#94a3b8">TASKS</text>
                            </g>
                        </svg>
                        <div class="task-ring-legend" style="width:100%;">
                            <?php foreach([['Done','#34d399',$ov['tasks_done']],['In Progress','#60a5fa',$ov['tasks_progress']],['To-Do','#d1d5db',$ov['tasks_todo']],['Overdue','#f87171',$ov['tasks_overdue']]] as [$n,$c,$v]): ?>
                            <div class="trl-row">
                                <div class="trl-dot" style="background:<?php echo $c; ?>;"></div>
                                <div class="trl-name"><?php echo $n; ?></div>
                                <div class="trl-num"><?php echo $v; ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Team Breakdown -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-user-group"></i> Team Breakdown</div>
                    <?php foreach([
                        ['Super Admin','super_admin','#ede9fe','#8b5cf6','fa-user-shield',1],
                        ['Admins','admin','#dbeafe','#3b82f6','fa-user-tie',$ov['admins']],
                        ['Managers','manager','#fef3c7','#f59e0b','fa-briefcase',$ov['managers']],
                        ['Agents','agent','#dcfce7','#10b981','fa-headset',$ov['agents']],
                    ] as [$lbl,$key,$bg,$col,$ico,$cnt]):
                    $desc=['super_admin'=>'Full system access','admin'=>'Manage users & data','manager'=>'Team supervision','agent'=>'Client handling'];
                    ?>
                    <div class="ubl-row">
                        <div class="ubl-avatar" style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;"><i class="fa-solid <?php echo $ico; ?>"></i></div>
                        <div class="ubl-info">
                            <div class="ubl-role"><?php echo $lbl; ?></div>
                            <div class="ubl-stat"><?php echo $desc[$key]; ?></div>
                        </div>
                        <div class="ubl-count"><?php echo $cnt; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <div class="ov-panel-footer" style="margin-top:14px;"><a href="#" onclick="showUserList(this)">View all users →</a></div>
                </div>

            </div><!-- /ov-mid-row -->

            <!-- ROW 3: Recent Deals | Recent Tasks | Recent Users -->
            <div class="ov-bottom-row">

                <!-- Recent Deals -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-handshake"></i> Recent Deals</div>
                    <?php if(empty($ov['recent_deals'])): ?>
                        <div class="ov-empty"><i class="fa-solid fa-inbox"></i>No deals available. Create a new deal.</div>
                    <?php else: ?>
                    <table class="mini-table">
                        <thead><tr><th>Deal Name</th><th>Value</th><th>Stage</th></tr></thead>
                        <tbody>
                        <?php foreach($ov['recent_deals'] as $d): ?>
                        <tr>
                            <td class="mini-deal-name" title="<?php echo htmlspecialchars($d['deal_name']); ?>"><?php echo htmlspecialchars($d['deal_name']); ?></td>
                            <td style="font-family:'DM Mono',monospace;font-weight:700;color:#059669;white-space:nowrap;"><?php echo htmlspecialchars($d['currency']); ?> <?php echo number_format((float)$d['deal_value'],0); ?></td>
                            <td><?php echo ovStage($d['stage']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <div class="ov-panel-footer"><a href="#" onclick="showDealPipeline(this)">All deals →</a></div>
                </div>

                <!-- Recent Tasks -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-clipboard-list"></i> Recent Tasks</div>
                    <?php if(empty($ov['recent_tasks'])): ?>
                        <div class="ov-empty"><i class="fa-solid fa-inbox"></i>কোনো task নেই।</div>
                    <?php else: ?>
                    <table class="mini-table">
                        <thead><tr><th>Title</th><th>Priority</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach($ov['recent_tasks'] as $t): ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($t['title']); ?>"><?php echo htmlspecialchars($t['title']); ?></td>
                            <td><?php echo ovPrio($t['priority']); ?></td>
                            <td><?php echo ovTask($t['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <div class="ov-panel-footer"><a href="task_manager.php">All tasks →</a></div>
                </div>

                <!-- Recent Users -->
                <div class="ov-panel">
                    <div class="ov-panel-title"><i class="fa-solid fa-user-plus"></i> Recently Added Users</div>
                    <?php if(empty($ov['recent_users'])): ?>
                        <div class="ov-empty"><i class="fa-solid fa-inbox"></i>কোনো user নেই।</div>
                    <?php else: ?>
                    <table class="mini-table">
                        <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach($ov['recent_users'] as $u): ?>
                        <tr>
                            <td style="font-weight:600;color:#111827;"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo ovRole($u['role']); ?></td>
                            <td><?php echo strtolower($u['status'])==='active' ? "<span style='color:#10b981;font-size:11px;font-weight:700;'>● Active</span>" : "<span style='color:#ef4444;font-size:11px;font-weight:700;'>● Inactive</span>"; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <div class="ov-panel-footer"><a href="#" onclick="showUserList(this)">All users →</a></div>
                </div>

            </div><!-- /ov-bottom-row -->

        </div><!-- /mainDashboardContent -->

        <div id="userListSection">
            <div class="user-list-header">
                <div class="user-list-title">
                    <h1>User List</h1>
                    <p>Logged in as <?php echo $_SESSION['name']; ?> • Session ID: <?php echo substr(session_id(), 0, 10); ?>-XA</p>
                </div>
                <div class="header-buttons">
                    <button class="desig-btn" onclick="openModal('createDesignationModal')"><i class="fa-solid fa-medal"></i> Designations</button>
                    <button class="create-btn" onclick="openModal('createUserModal')"><i class="fa-solid fa-user-plus"></i> Create New User</button>
                </div>
            </div>

            <div class="tabs-wrapper">
                <div class="tab-top-line"></div>
                <div class="tabs-container">
                    <button class="tab-btn active" onclick="filterUsers('all', this)"><i class="fa-regular fa-circle-dot"></i> All Users</button>
                    <button class="tab-btn" onclick="filterUsers('active', this)"><i class="fa-regular fa-circle-dot"></i> Active Users</button>
                    <button class="tab-btn" onclick="filterUsers('inactive', this)"><i class="fa-regular fa-circle-dot"></i> In-Active Users</button>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID No</th><th>User ID</th><th>Name</th><th>Role</th>
                            <th>Designation</th><th>Contact</th><th>Email</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if(isset($conn)){
                            $sql = "SELECT * FROM users ORDER BY id DESC";
                            $res = mysqli_query($conn, $sql);
                            if($res && mysqli_num_rows($res) > 0){
                                while($row = mysqli_fetch_assoc($res)) {
                                    $role = ucfirst(str_replace('_', ' ', $row['role']));
                                    $rawStatus = strtolower($row['status']);
                                    $statusText = ($rawStatus == 'active') ? 'Active' : 'In-Active';
                                    $statusColor = ($rawStatus == 'active') ? '#10b981' : '#ef4444'; 
                                    $userData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                    echo "<tr class='user-row' data-status='$rawStatus'>
                                            <td>038H{$row['id']}</td>
                                            <td><b>{$row['username']}</b></td>
                                            <td>{$row['name']}</td>
                                            <td>$role</td>
                                            <td>" . ($row['designation'] ?? 'COO') . "</td>
                                            <td>" . ($row['phone'] ?? '+880 018...') . "</td>
                                            <td>" . ($row['email'] ?? 'N/A') . "</td>
                                            <td class='status-text' style='color:$statusColor;'>$statusText</td>
                                            <td>
                                                <div class='action-btns'>
                                                    <button class='btn-view' onclick='openViewModal($userData)' title='View'><i class='fa-regular fa-eye'></i></button>
                                                    <button class='btn-edit' onclick='openEditModal($userData)' title='Edit'><i class='fa-solid fa-pen'></i></button>
                                                    <form method='POST' id='delete-user-{$row['id']}' style='display:inline;'>
                                                        <input type='hidden' name='delete_user_id' value='{$row['id']}'>
                                                        <input type='hidden' name='delete_user' value='1'>
                                                        <button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-{$row['id']}\", \"user\")' title='Delete'><i class='fa-solid fa-trash'></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                          </tr>";
                                }
                            }
                        } else {
                            // Dummy Data Fallback for frontend viewing if DB fails
                            $dummyDataActive = htmlspecialchars(json_encode(['id'=>'1', 'name'=>'MD. Farabee', 'username'=>'sakline01', 'email'=>'sakline.business@gmail.com', 'role'=>'super_admin', 'status'=>'active', 'phone'=>'+880 018xxxx-xxx96', 'dob'=>'1998-05-12', 'emp_id'=>'EMP-101', 'designation'=>'COO', 'reporting_manager'=>'None', 'address'=>'House 12, Road 5, Dhaka']), ENT_QUOTES, 'UTF-8');
                            $dummyDataInactive = htmlspecialchars(json_encode(['id'=>'2', 'name'=>'John Doe', 'username'=>'johndoe', 'email'=>'john@example.com', 'role'=>'manager', 'status'=>'inactive', 'phone'=>'+880 017xxxx-xxx99', 'dob'=>'1995-08-22', 'emp_id'=>'EMP-102', 'designation'=>'Sales', 'reporting_manager'=>'Mr. Sakline', 'address'=>'Block C, Bashundhara']), ENT_QUOTES, 'UTF-8');
                            
                            echo "<tr class='user-row' data-status='active'>
                                    <td>038H1</td><td><b>sakline01</b></td><td>MD. Farabee</td><td>Super Admin</td><td>COO</td><td>+880 018xxxx-xxx96</td><td>sakline.business@gmail.com</td><td class='status-text' style='color:#10b981;'>Active</td>
                                    <td><div class='action-btns'>
                                        <button class='btn-view' onclick='openViewModal($dummyDataActive)'><i class='fa-regular fa-eye'></i></button>
                                        <button class='btn-edit' onclick='openEditModal($dummyDataActive)'><i class='fa-solid fa-pen'></i></button>
                                        <form id='delete-user-dummy1' style='display:inline;'><button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-dummy1\", \"user\")'><i class='fa-solid fa-trash'></i></button></form>
                                    </div></td></tr>";
                            
                            echo "<tr class='user-row' data-status='inactive'>
                                    <td>038H2</td><td><b>johndoe</b></td><td>John Doe</td><td>Manager</td><td>Sales</td><td>+880 017xxxx-xxx99</td><td>john@example.com</td><td class='status-text' style='color:#ef4444;'>In-Active</td>
                                    <td><div class='action-btns'>
                                        <button class='btn-view' onclick='openViewModal($dummyDataInactive)'><i class='fa-regular fa-eye'></i></button>
                                        <button class='btn-edit' onclick='openEditModal($dummyDataInactive)'><i class='fa-solid fa-pen'></i></button>
                                        <form id='delete-user-dummy2' style='display:inline;'><button type='button' class='btn-delete' onclick='confirmDelete(\"delete-user-dummy2\", \"user\")'><i class='fa-solid fa-trash'></i></button></form>
                                    </div></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-container" id="taskManagementSection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 0;">
                <div class="task-header-title">
                    <h1>Task Management</h1>
                    <p>Coordinate enterprise projects and monitor team delivery speeds.</p>
                </div>
                <div class="header-buttons">
                    <button class="desig-btn" style="background-color: #f3f4f6; color: #111827; border: 1px solid #d1d5db;" onclick="showToast('Categories Coming Soon', 'success')"><i class="fa-solid fa-folder-open" style="color: #4b5563;"></i> Task Categories</button>
                    <button class="create-btn" style="background-color: #0056d2;" onclick="openModal('createTaskModal')"><i class="fa-solid fa-plus"></i> Create New Task</button>
                </div>
            </div>

            <div class="task-cards">
                <div class="task-card">
                    <div class="tc-header"><h4>Total Tasks</h4><i class="fa-solid fa-file-lines" style="color:#60a5fa;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $totalTasks : "1,284"; ?></h2>
                        <p>+12% from last week</p>
                    </div>
                </div>
                <div class="task-card pending">
                    <div class="tc-header"><h4>Pending Tasks</h4><i class="fa-regular fa-clipboard" style="color:#fcd34d;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $pendingTasks : "43"; ?></h2>
                        <p class="warn">Requires attention</p>
                    </div>
                </div>
                <div class="task-card overdue">
                    <div class="tc-header"><h4>Overdue</h4><i class="fa-solid fa-asterisk" style="color:#fca5a5;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $overdueTasks : "07"; ?></h2>
                        <p class="danger">Critical threshold</p>
                    </div>
                </div>
                <div class="task-card completed">
                    <div class="tc-header"><h4>Completed Today</h4><i class="fa-regular fa-circle-check" style="color:#6ee7b7;"></i></div>
                    <div class="tc-body">
                        <h2><?php echo (isset($hasTasks) && $hasTasks) ? $completedTasks : "18"; ?></h2>
                        <p>Daily goal reached</p>
                    </div>
                </div>
            </div>

            <div class="task-table-wrapper">
                <div class="tt-controls">
                    <div class="tt-tabs">
                        <div class="tt-tab active">All Tasks</div>
                        <div class="tt-tab">My Tasks</div>
                        <div class="tt-tab">Team Tasks</div>
                    </div>
                    <div class="tt-actions">
                        <span><i class="fa-solid fa-filter"></i> Advanced Filter</span>
                        <span><i class="fa-solid fa-arrow-down-short-wide"></i> Sort: Due Date</span>
                    </div>
                </div>

                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Task Name & Details</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasTasks) && $hasTasks): ?>
                            <?php echo $taskTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <div class="tt-name">Q4 Financial Audit Preparation</div>
                                    <div class="tt-desc">Review all ledger entries for Q4 an...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar">SC</div>
                                        <span>Sarah Connor</span>
                                    </div>
                                </td>
                                <td><span class="pill high">HIGH</span></td>
                                <td><span class="status-dot progress"></span> <span class="status-text-t progress">In-Progress</span></td>
                                <td><span class="date-text">Oct 24,<br>2023</span></td>
                                <td style="width: 130px; text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tt-name">CRM Database Migration</div>
                                    <div class="tt-desc">Transfer legacy account data to th...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar" style="background:#e0e7ff;">MT</div>
                                        <span>Marcus T.</span>
                                    </div>
                                </td>
                                <td><span class="pill medium">MEDIUM</span></td>
                                <td><span class="status-dot todo"></span> <span class="status-text-t todo">To-Do</span></td>
                                <td><span class="date-text">Oct 28,<br>2023</span></td>
                                <td style="text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="tt-name">UI/UX Review - Project Zenith</div>
                                    <div class="tt-desc">Final approval on bento-grid layout...</div>
                                </td>
                                <td>
                                    <div class="tt-assignee">
                                        <div class="avatar" style="background:#c7d2fe;">AL</div>
                                        <span>Anna Lee</span>
                                    </div>
                                </td>
                                <td><span class="pill low">LOW</span></td>
                                <td><span class="status-dot done"></span> <span class="status-text-t done">Done</span></td>
                                <td><span class="date-text">Oct 20,<br>2023</span></td>
                                <td style="text-align:right;">
                                    <button class="tt-action-btn">Edit & Manage Task</button>
                                    <button class="tt-rule-btn">Task Rules & Auth</button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <span>Showing 1-10 of 1,284 entries</span>
                    <div class="page-numbers">
                        <div class="page-no"><i class="fa-solid fa-chevron-left"></i></div>
                        <div class="page-no active">1</div>
                        <div class="page-no">2</div>
                        <div class="page-no">3</div>
                        <div class="page-no" style="cursor: default; background:none;">...</div>
                        <div class="page-no">128</div>
                        <div class="page-no"><i class="fa-solid fa-chevron-right"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="company-container" id="companySection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 20px;">
                <div class="comp-header-title">
                    <h1>Company Database</h1>
                    <p>Welcome back, <?php echo $_SESSION['name']; ?></p>
                </div>
                <div class="header-buttons">
                    <form method="POST" style="display:inline-block;">
                        <button type="submit" name="export_companies_csv" class="btn-export" style="height: 100%;"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                    </form>
                    <button class="btn-upload" onclick="openModal('bulkUploadCompanyModal')"><i class="fa-solid fa-cloud-arrow-up"></i> Bulk Upload</button>
                    <button class="btn-add-client" onclick="openModal('addCompanyModal')"><i class="fa-solid fa-plus"></i> Add Company</button>
                </div>
            </div>

            <div class="comp-toolbar" style="margin-bottom: 20px;">
                <div class="comp-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search company...">
                </div>
                <div class="comp-total">Total Companies: <?php echo (isset($hasCompanies) && $hasCompanies) ? $totalCompanies : "4"; ?></div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="tbl-checkbox" title="Select All"></th>
                            <th>Company Name</th>
                            <th>Assigned Agent</th>
                            <th>Total Contacts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasCompanies) && $hasCompanies): ?>
                            <?php echo $companyTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><b>Blue Point Accounting</b></td>
                                <td><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></td>
                                <td>9</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" title="View"><i class="fa-regular fa-eye"></i></button>
                                        <button class="btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><b>course plus</b></td>
                                <td>mh, <?php echo explode(' ', trim($_SESSION['name']))[0]; ?></td>
                                <td>12</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" title="View"><i class="fa-regular fa-eye"></i></button>
                                        <button class="btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><b>kk</b></td>
                                <td><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></td>
                                <td>1</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" title="View"><i class="fa-regular fa-eye"></i></button>
                                        <button class="btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" class="tbl-checkbox"></td>
                                <td><b>Peer solution</b></td>
                                <td><?php echo explode(' ', trim($_SESSION['name']))[0]; ?></td>
                                <td>18</td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" title="View"><i class="fa-regular fa-eye"></i></button>
                                        <button class="btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="company-container" id="accountsClientsSection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 20px;">
                <div class="comp-header-title">
                    <h1>Accounts & Clients</h1>
                    <p>Manage all individual contacts and clients here.</p>
                </div>
                <div class="header-buttons">
                    <button class="btn-add-client" onclick="openModal('addClientModal')"><i class="fa-solid fa-user-plus"></i> Add Client</button>
                </div>
            </div>

            <div class="comp-toolbar" style="margin-bottom: 20px;">
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
                            <th>Associated Company</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($hasClients) && $hasClients): ?>
                            <?php echo $clientTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 20px; text-align: center; color: #6b7280;">No clients found. Click "Add Client" to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="company-container" id="dealPipelineSection" style="display:none;">
            <div class="user-list-header" style="margin-bottom: 20px;">
                <div class="comp-header-title">
                    <h1>Deal Pipeline</h1>
                    <p>Track and manage your sales projects.</p>
                </div>
                <div class="header-buttons">
                    <button class="btn-add-client" onclick="openModal('addDealModal')"><i class="fa-solid fa-plus"></i> Add New Deal</button>
                </div>
            </div>

            <div class="comp-toolbar" style="margin-bottom: 20px;">
                <div class="comp-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search project...">
                </div>
                <div class="comp-total">Total Deals: <?php echo $totalDeals; ?></div>
            </div>

            <div class="table-wrapper">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="tbl-checkbox" title="Select All"></th>
                            <th>Project Name</th>
                            <th>Company</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($hasDeals): ?>
                            <?php echo $dealTableRows; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="padding: 20px; text-align: center; color: #6b7280;">No deals found. Click "Add New Deal" to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="coming-soon-container" id="comingSoonSection">
            <i class="fa-solid fa-person-digging coming-soon-icon"></i>
            <h1 class="coming-soon-title" id="comingSoonTitle">Feature Coming Soon</h1>
            <p class="coming-soon-text">Stay tuned!</p>
        </div>
    </div>

    <div id="createTaskModal" class="modal" style="z-index: 3000;">
        <div class="modal-content task-modal-content">
            <button type="button" class="close-btn" style="position:absolute; right:20px; top:20px; font-size:16px;" onclick="closeModal('createTaskModal')"><i class="fa-solid fa-xmark"></i></button>

            <form action="super_admin_dashboard.php" method="POST" id="createTaskForm">
                
                <label class="task-modal-label">Add a title <span class="req">*</span></label>
                <input type="text" name="task_title" class="task-input-dark" required placeholder="Title">

                <label class="task-modal-label" style="margin-top: 10px;">Add a description</label>
                
                <div class="task-toolbar">
                    <div class="task-tab">Write</div>
                    <div class="task-tools">
                        <i class="fa-solid fa-h"></i>
                        <i class="fa-solid fa-bold"></i>
                        <i class="fa-solid fa-italic"></i>
                        <i class="fa-solid fa-code"></i>
                        <i class="fa-solid fa-link"></i>
                        <i class="fa-solid fa-list-ul"></i>
                        <i class="fa-solid fa-list-ol"></i>
                        <i class="fa-regular fa-square-check"></i>
                        <i class="fa-solid fa-at"></i>
                        <i class="fa-regular fa-comment"></i>
                        <i class="fa-regular fa-image"></i>
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                </div>
                
                <textarea name="task_description" class="task-textarea" placeholder="Type your description here..."></textarea>

                <div class="task-modal-footer">
                    <div class="task-footer-left">
                        
                        <div class="task-tag-wrapper">
                            <i class="fa-solid fa-user-plus"></i>
                            <select name="assigned_to" class="task-tag-select">
                                <option value="Unassigned" selected>Assignee</option>
                                <?php echo $assigneeOptions; ?>
                            </select>
                        </div>
                        
                        <div class="task-tag-wrapper">
                            <i class="fa-solid fa-tag"></i>
                            <select name="task_label" class="task-tag-select">
                                <option value="" disabled selected>Label</option>
                                <option value="Label 1">Label 1</option>
                                <option value="Label 2">Label 2</option>
                                <option value="Label 3">Label 3</option>
                            </select>
                        </div>
                        
                        <button type="button" class="task-tag-btn" onclick="showToast('Deal & Campaign coming soon!', 'success')">
                            <i class="fa-solid fa-briefcase"></i> Deal & Campaign
                        </button>
                        
                    </div>
                    
                    <div class="task-footer-right">
                        <label class="task-checkbox-label">
                            <input type="checkbox" id="createMoreCheckbox" class="task-checkbox"> Create more
                        </label>
                        <button type="button" class="task-cancel-btn" onclick="closeModal('createTaskModal')">Cancel</button>
                        
                        <button type="submit" name="create_task" class="task-create-btn" onclick="handleTaskSubmit(event)">
                            <span>Create</span>
                            <span class="btn-divider"></span>
                            <i class="fa-solid fa-arrow-turn-down fa-rotate-90" style="font-size: 11px;"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
    
    <style>
        /* Compact 2-Step Wizard Styles */
        .co-modal-content { max-width: 500px !important; padding: 20px !important; border-radius: 12px !important; overflow: hidden !important; }
        .co-progress-container { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; position: relative; padding: 0 10px; }
        .co-progress-line { position: absolute; top: 50%; left: 10%; right: 10%; height: 2px; background: #e5e7eb; z-index: 1; transform: translateY(-50%); }
        .co-progress-line-fill { position: absolute; top: 50%; left: 10%; width: 0%; height: 2px; background: #3b82f6; z-index: 2; transform: translateY(-50%); transition: width 0.3s ease; }
        .co-step-indicator { width: 30px; height: 30px; border-radius: 50%; background: #fff; border: 2px solid #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #6b7280; z-index: 3; transition: all 0.3s ease; }
        .co-step-indicator.co-active { border-color: #3b82f6; color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .co-step-indicator.co-completed { background: #3b82f6; border-color: #3b82f6; color: #fff; }
        
        .co-step-content { display: none; animation: coFadeIn 0.3s ease; }
        .co-step-content.co-active { display: block; }
        @keyframes coFadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .co-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .co-form-group { margin-bottom: 12px; }
        .co-form-group.co-full { grid-column: span 2; }
        .co-label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
        .co-input, .co-select { width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; transition: border-color 0.2s; }
        .co-input:focus, .co-select:focus { outline: none; border-color: #3b82f6; ring: 2px rgba(59, 130, 246, 0.2); }
        .co-invalid { border-color: #ef4444 !important; background-color: #fef2f2; }
        
        .co-phone-row { display: flex; gap: 8px; }
        .co-phone-code { flex: 0 0 90px; }
        .co-social-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .co-social-input { display: flex; align-items: center; gap: 8px; background: #f9fafb; padding: 6px 10px; border: 1px solid #e5e7eb; border-radius: 6px; }
        .co-social-input i { font-size: 14px; width: 16px; text-align: center; }
        .co-social-input input { border: none; background: transparent; font-size: 12px; width: 100%; outline: none; }

        .co-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f3f4f6; }
        .co-btn { padding: 8px 18px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; border: none; }
        .co-btn-prev { background: #f3f4f6; color: #4b5563; }
        .co-btn-prev:hover { background: #e5e7eb; }
        .co-btn-next, .co-btn-submit { background: #3b82f6; color: #fff; }
        .co-btn-next:hover, .co-btn-submit:hover { background: #2563eb; }
    </style>

    <div id="addCompanyModal" class="modal">
        <div class="modal-content co-modal-content">
            <div class="modal-header" style="margin-bottom: 15px; border-bottom: none; padding-bottom: 0;">
                <h2 style="font-size: 18px;">Add New Company</h2>
                <button type="button" class="close-btn" onclick="closeModal('addCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <div class="co-progress-container">
                <div class="co-progress-line"></div>
                <div id="coProgressFill" class="co-progress-line-fill"></div>
                <div class="co-step-indicator co-active" id="coStepInd1">1</div>
                <div class="co-step-indicator" id="coStepInd2">2</div>
            </div>

            <form id="coAddCompanyForm" action="super_admin_dashboard.php" method="POST">
                <!-- Step 1: Primary Company Info -->
                <div class="co-step-content co-active" id="coStep1">
                    <div class="co-form-group co-full">
                        <label class="co-label">Company Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="company_name" class="co-input" id="co_company_name" placeholder="Enter company name" required>
                    </div>
                    <div class="co-form-group co-full">
                        <label class="co-label">Company Email <span style="color:#ef4444;">*</span></label>
                        <input type="email" name="company_email" class="co-input" id="co_company_email" placeholder="info@company.com" required>
                    </div>
                    <div class="co-form-grid">
                        <div class="co-form-group co-full">
                            <label class="co-label">Company Number <span style="color:#ef4444;">*</span></label>
                            <div class="co-phone-row">
                                <select name="company_country_code" class="co-select co-phone-code">
                                    <option value="+880">🇧🇩 +880</option>
                                    <option value="+1">🇺🇸 +1</option>
                                    <option value="+44">🇬🇧 +44</option>
                                    <option value="+91">🇮🇳 +91</option>
                                    <option value="+971">🇦🇪 +971</option>
                                </select>
                                <input type="text" name="company_number" class="co-input" id="co_company_number" placeholder="234 567 8900" required>
                            </div>
                        </div>
                    </div>
                    <div class="co-form-group co-full">
                        <label class="co-label">Company Website</label>
                        <input type="url" name="company_website" class="co-input" placeholder="https://...">
                    </div>
                </div>

                <!-- Step 2: Assignment & Social Links -->
                <div class="co-step-content" id="coStep2">
                    <div class="co-form-group co-full">
                        <label class="co-label">Assigned Agent</label>
                        <select name="assigned_agent" class="co-select">
                            <option value="Unassigned">Select Agent...</option>
                            <?php echo $assigneeOptions; ?>
                        </select>
                    </div>
                    <label class="co-label" style="margin-top: 15px;">Social Links</label>
                    <div class="co-social-grid">
                        <div class="co-social-input">
                            <i class="fa-brands fa-facebook" style="color: #1877F2;"></i>
                            <input type="url" name="fb_url" placeholder="Facebook URL">
                        </div>
                        <div class="co-social-input">
                            <i class="fa-brands fa-linkedin" style="color: #0A66C2;"></i>
                            <input type="url" name="linkedin_url" placeholder="LinkedIn URL">
                        </div>
                        <div class="co-social-input">
                            <i class="fa-brands fa-instagram" style="color: #E4405F;"></i>
                            <input type="url" name="insta_url" placeholder="Instagram URL">
                        </div>
                        <div class="co-social-input">
                            <i class="fa-brands fa-x-twitter" style="color: #000000;"></i>
                            <input type="url" name="twitter_url" placeholder="Twitter URL">
                        </div>
                    </div>
                    <input type="hidden" name="create_company" value="1">
                    <input type="hidden" name="total_contacts" value="0">
                </div>

                <div class="co-footer">
                    <button type="button" class="co-btn co-btn-prev" id="coBtnPrev" style="display:none;" onclick="coChangeStep(1)">Previous</button>
                    <button type="button" class="co-btn co-btn-next" id="coBtnNext" onclick="coValidateAndNext()">Next</button>
                    <button type="submit" class="co-btn co-btn-submit" id="coBtnSubmit" style="display:none;">Add Company</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function coChangeStep(step) {
            const step1 = document.getElementById('coStep1');
            const step2 = document.getElementById('coStep2');
            const ind1 = document.getElementById('coStepInd1');
            const ind2 = document.getElementById('coStepInd2');
            const fill = document.getElementById('coProgressFill');
            const btnPrev = document.getElementById('coBtnPrev');
            const btnNext = document.getElementById('coBtnNext');
            const btnSubmit = document.getElementById('coBtnSubmit');

            if (step === 1) {
                step1.classList.add('co-active');
                step2.classList.remove('co-active');
                ind1.classList.add('co-active');
                ind1.classList.remove('co-completed');
                ind2.classList.remove('co-active');
                fill.style.width = '0%';
                btnPrev.style.display = 'none';
                btnNext.style.display = 'block';
                btnSubmit.style.display = 'none';
            } else {
                step1.classList.remove('co-active');
                step2.classList.add('co-active');
                ind1.classList.add('co-completed');
                ind2.classList.add('co-active');
                fill.style.width = '100%';
                btnPrev.style.display = 'block';
                btnNext.style.display = 'none';
                btnSubmit.style.display = 'block';
            }
        }

        function coValidateAndNext() {
            const name = document.getElementById('co_company_name');
            const email = document.getElementById('co_company_email');
            const number = document.getElementById('co_company_number');
            let isValid = true;

            [name, email, number].forEach(el => {
                if (!el.value.trim() || (el.type === 'email' && !el.checkValidity())) {
                    el.classList.add('co-invalid');
                    isValid = false;
                } else {
                    el.classList.remove('co-invalid');
                }
            });

            if (isValid) coChangeStep(2);
        }

        // Real-time validation clear
        document.querySelectorAll('.co-input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) this.classList.remove('co-invalid');
            });
        });
    </script>

    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client</h2>
                <button type="button" class="close-btn" onclick="closeModal('addClientModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
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
                </div>
                <button type="submit" name="create_client" class="submit-btn" style="background-color: #3b82f6; margin-top: 20px;">Save Client</button>
            </form>
        </div>
    </div>
    
    <div id="bulkUploadCompanyModal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Bulk Upload Companies (CSV)</h2>
                <button type="button" class="close-btn" onclick="closeModal('bulkUploadCompanyModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST" enctype="multipart/form-data">
                <p style="font-size: 12px; color: #6b7280; margin-bottom: 15px;">Please upload a CSV file with columns: <b>Company Name, Assigned Agent, Total Contacts</b></p>
                <div class="form-group" style="margin-bottom: 20px;">
                    <input type="file" name="company_csv" accept=".csv" required>
                </div>
                <button type="submit" name="bulk_upload_companies" class="submit-btn" style="background-color: #10b981;">Upload CSV Data</button>
            </form>
        </div>
    </div>

    <div id="addDealModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Deal</h2>
                <button type="button" class="close-btn" onclick="closeModal('addDealModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <div class="form-group full-width" style="margin-bottom: 20px;">
                    <label class="comp-input-label">Project Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="project_name" class="comp-input" required placeholder="Enter Project Name">
                </div>

                <div class="form-grid">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Service Required</label>
                        <input type="text" name="service_required" class="comp-input" placeholder="e.g. Web Development">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Project Status</label>
                        <select name="project_status" class="comp-select">
                            <option value="New">New</option>
                            <option value="Discussion">Discussion</option>
                            <option value="Negotiation">Negotiation</option>
                            <option value="Won">Won</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Start Date</label>
                        <input type="date" name="start_date" class="comp-input">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">End Date</label>
                        <input type="date" name="end_date" class="comp-input">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Currency</label>
                        <select name="currency" class="comp-select">
                            <option value="BDT">BDT</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Total Amount <span style="color:#ef4444;">*</span></label>
                        <input type="number" step="0.01" name="total_amount" class="comp-input" required placeholder="0.00">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Platform (Source)</label>
                        <input type="text" name="platform" class="comp-input" placeholder="e.g. Upwork, Direct">
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="comp-input-label">Sales Officer <span style="color:#ef4444;">*</span></label>
                        <select name="sales_officer" class="comp-select" required>
                            <option value="" disabled selected>Select Sales Officer</option>
                            <?php echo $assigneeOptions; ?>
                        </select>
                    </div>
                    <div class="form-group full-width" style="margin-bottom: 25px;">
                        <label class="comp-input-label">Link Company <span style="color:#ef4444;">*</span></label>
                        <select name="company_id" class="comp-select" required>
                            <option value="" disabled selected>Select Company</option>
                            <?php echo $companyOptions; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_deal" class="comp-save-btn">Save Deal</button>
            </form>
        </div>
    </div>

    <div id="editDealModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Deal</h2>
                <button type="button" class="close-btn" onclick="closeModal('editDealModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <input type="hidden" name="deal_id" id="edit_deal_id">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Project Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="project_name" id="edit_deal_project_name" required placeholder="Enter Project Name">
                    </div>
                    <div class="form-group">
                        <label>Service Required</label>
                        <input type="text" name="service_required" id="edit_deal_service_required" placeholder="e.g. Web Development">
                    </div>
                    <div class="form-group">
                        <label>Project Status</label>
                        <select name="project_status" id="edit_deal_project_status">
                            <option value="New">New</option>
                            <option value="Discussion">Discussion</option>
                            <option value="Negotiation">Negotiation</option>
                            <option value="Won">Won</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="edit_deal_start_date">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="edit_deal_end_date">
                    </div>
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" id="edit_deal_currency">
                            <option value="BDT">BDT</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Total Amount <span style="color:#ef4444;">*</span></label>
                        <input type="number" step="0.01" name="total_amount" id="edit_deal_total_amount" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Platform (Source)</label>
                        <input type="text" name="platform" id="edit_deal_platform" placeholder="e.g. Upwork, Direct">
                    </div>
                    <div class="form-group">
                        <label>Sales Officer <span style="color:#ef4444;">*</span></label>
                        <select name="sales_officer" id="edit_deal_sales_officer" required>
                            <option value="" disabled selected>Select Sales Officer</option>
                            <?php echo $assigneeOptions; ?>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Link Company <span style="color:#ef4444;">*</span></label>
                        <select name="company_id" id="edit_deal_company_id" required>
                            <option value="" disabled selected>Select Company</option>
                            <?php echo $companyOptions; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="update_deal" class="submit-btn" style="background-color: #22c55e; margin-top: 20px;">Update Deal</button>
            </form>
        </div>
    </div>

    <div id="viewDealModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Deal Details View</h2>
                <button type="button" class="close-btn" onclick="closeModal('viewDealModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="form-grid">
                <div class="form-group full-width"><label>Project Name</label><div class="view-data-box" id="view_deal_project_name">-</div></div>
                <div class="form-group"><label>Service Required</label><div class="view-data-box" id="view_deal_service">-</div></div>
                <div class="form-group"><label>Project Status</label><div class="view-data-box" id="view_deal_status">-</div></div>
                <div class="form-group"><label>Start Date</label><div class="view-data-box" id="view_deal_start_date">-</div></div>
                <div class="form-group"><label>End Date</label><div class="view-data-box" id="view_deal_end_date">-</div></div>
                <div class="form-group"><label>Total Amount</label><div class="view-data-box" id="view_deal_amount">-</div></div>
                <div class="form-group"><label>Platform (Source)</label><div class="view-data-box" id="view_deal_platform">-</div></div>
                <div class="form-group"><label>Sales Officer</label><div class="view-data-box" id="view_deal_sales_officer">-</div></div>
                <div class="form-group full-width"><label>Linked Company</label><div class="view-data-box" id="view_deal_company">-</div></div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="submit-btn" onclick="switchToEditDealMode()" style="background-color: #22c55e; margin-top: 0;"><i class="fa-solid fa-pen-to-square"></i> Edit Deal</button>
                <button class="submit-btn" onclick="closeModal('viewDealModal')" style="background-color: #6b7280; margin-top: 0;">Close</button>
            </div>
        </div>
    </div>

    <div id="createDesignationModal" class="modal">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Manage Designations</h2>
                <button type="button" class="close-btn" onclick="closeModal('createDesignationModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <form action="super_admin_dashboard.php" method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="designation_title" required placeholder="New Designation Title" style="flex-grow: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; outline: none; background-color: transparent;">
                <button type="submit" name="create_designation" class="submit-btn" style="margin-top: 0; width: auto; padding: 10px 18px; background-color: #3b82f6;"><i class="fa-solid fa-plus"></i> Add</button>
            </form>

            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: transparent;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tbody>
                        <?php echo $designationTableRows; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="editDesignationModal" class="modal" style="z-index: 2500;">
        <div class="modal-content small-modal">
            <div class="modal-header">
                <h2>Edit Designation</h2>
                <button type="button" class="close-btn" onclick="closeModal('editDesignationModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <input type="hidden" name="desig_id" id="edit_desig_id">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Designation Title</label>
                    <input type="text" name="designation_title" id="edit_desig_title" required>
                </div>
                <button type="submit" name="update_designation" class="submit-btn" style="background-color: #22c55e;">Update Designation</button>
            </form>
        </div>
    </div>

    <div id="createUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <button type="button" class="close-btn" onclick="closeModal('createUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" required placeholder="e.g. MD. Farabee"></div>
                    <div class="form-group"><label>User ID</label><input type="text" name="username" required placeholder="e.g. sakline01"></div>
                    <div class="form-group">
    <label>Phone Number</label>
    <div style="display: flex; gap: 10px;">
        <select name="country_code" style="padding: 5px; max-width: 120px;">
            <option value="+880">🇧🇩 +880</option>
            <option value="+1">🇺🇸 +1</option>
            <option value="+44">🇬🇧 +44</option>
            <option value="+91">🇮🇳 +91</option>
            <option value="+971">🇦🇪 +971</option>
            </select>
        
        <input type="text" name="phone" placeholder="1812345678" style="flex: 1; padding: 5px;">
    </div>
</div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required placeholder="e.g. example@gmail.com"></div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" id="create_pass" required placeholder="********" onkeyup="checkPasswordMatch('create_pass', 'create_confirm_pass', 'create_error_msg', 'create_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('create_pass', this)"></i>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="create_confirm_pass" required placeholder="********" onkeyup="checkPasswordMatch('create_pass', 'create_confirm_pass', 'create_error_msg', 'create_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('create_confirm_pass', this)"></i>
                        <span id="create_error_msg" class="password-error">Passwords do not match!</span>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="" disabled selected>Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob"></div>
                    <div class="form-group"><label>Employee ID</label><input type="text" name="emp_id" placeholder="e.g. EMP-101"></div>
                    
                    <div class="form-group">
                        <label>Designation</label>
                        <select name="designation">
                            <option value="" disabled selected>Select Designation</option>
                            <?php echo $designationsList; ?>
                        </select>
                    </div>

                    <div class="form-group"><label>Reporting Manager</label><select name="reporting_manager"><option value="None">None</option><option value="Mr. Sakline">Mr. Sakline</option></select></div>
                    <div class="form-group full-width"><label>Address</label><input type="text" name="address" placeholder="e.g. House 12, Dhaka"></div>
                </div>
                <button type="submit" name="create_user" id="create_submit_btn" class="submit-btn" style="margin-top: 20px;">Save User</button>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User Details</h2>
                <button type="button" class="close-btn" onclick="closeModal('editUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="super_admin_dashboard.php" method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-grid">
                    <div class="form-group"><label>Full Name</label><input type="text" name="name" id="edit_name" required></div>
                    <div class="form-group"><label>User ID</label><input type="text" name="username" id="edit_username" required></div>
                    <div class="form-group"><label>Phone Number</label><input type="text" name="phone" id="edit_phone"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email" required></div>
                    
                    <div class="form-group">
                        <label>Password <span style="font-weight: 400; font-size: 10px; opacity:0.7;">(Leave blank to keep same)</span></label>
                        <input type="password" name="password" id="edit_password" placeholder="********" onkeyup="checkPasswordMatch('edit_password', 'edit_confirm_password', 'edit_error_msg', 'edit_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('edit_password', this)"></i>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="edit_confirm_password" placeholder="********" onkeyup="checkPasswordMatch('edit_password', 'edit_confirm_password', 'edit_error_msg', 'edit_submit_btn')">
                        <i class="fa-solid fa-eye password-toggle" onclick="togglePassword('edit_confirm_password', this)"></i>
                        <span id="edit_error_msg" class="password-error">Passwords do not match!</span>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="edit_role" required>
                            <option value="super_admin">Super Admin</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="agent">Agent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="status" id="edit_status" required>
                            <option value="active">Active</option>
                            <option value="inactive">In-Active</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Date of Birth</label><input type="date" name="dob" id="edit_dob"></div>
                    <div class="form-group"><label>Employee ID</label><input type="text" name="emp_id" id="edit_emp_id"></div>
                    <div class="form-group">
                        <label>Designation</label>
                        <select name="designation" id="edit_designation">
                            <?php echo $designationsList; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Reporting Manager</label><select name="reporting_manager" id="edit_reporting"><option value="None">None</option><option value="Mr. Sakline">Mr. Sakline</option></select></div>
                    <div class="form-group full-width"><label>Address</label><input type="text" name="address" id="edit_address"></div>
                </div>
                <button type="submit" name="update_user" id="edit_submit_btn" class="submit-btn" style="background-color: #22c55e; margin-top: 20px;">Update User</button>
            </form>
        </div>
    </div>

    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>User Profile View</h2>
                <button type="button" class="close-btn" onclick="closeModal('viewUserModal')"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="form-grid">
                <div class="form-group"><label>Full Name</label><div class="view-data-box" id="view_name">-</div></div>
                <div class="form-group"><label>User ID</label><div class="view-data-box" id="view_username">-</div></div>
                <div class="form-group"><label>Phone Number</label><div class="view-data-box" id="view_phone">-</div></div>
                <div class="form-group"><label>Email ID</label><div class="view-data-box" id="view_email">-</div></div>
                <div class="form-group"><label>Password</label><div class="view-data-box" style="color: #6b7280; font-family: monospace;">******** (Encrypted)</div></div>
                <div class="form-group"><label>Role</label><div class="view-data-box" id="view_role">-</div></div>
                <div class="form-group"><label>Account Status</label><div class="view-data-box" id="view_status" style="font-weight: 700;">-</div></div>
                <div class="form-group"><label>Date of Birth</label><div class="view-data-box" id="view_dob">-</div></div>
                <div class="form-group"><label>Employee ID</label><div class="view-data-box" id="view_emp_id">-</div></div>
                <div class="form-group"><label>Designation</label><div class="view-data-box" id="view_designation">-</div></div>
                <div class="form-group"><label>Reporting Manager</label><div class="view-data-box" id="view_reporting">-</div></div>
                <div class="form-group full-width"><label>Address</label><div class="view-data-box" id="view_address">-</div></div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="submit-btn" onclick="switchToEditMode()" style="background-color: #22c55e; margin-top: 0;"><i class="fa-solid fa-pen-to-square"></i> Edit User</button>
                <button class="submit-btn" onclick="closeModal('viewUserModal')" style="background-color: #6b7280; margin-top: 0;">Close</button>
            </div>
        </div>
    </div>

    <script>
        // --- CREATE TASK 'CREATE MORE' LOGIC ---
        function handleTaskSubmit(e) {
            const isCreateMoreChecked = document.getElementById('createMoreCheckbox').checked;
            
            if(isCreateMoreChecked) {
                const titleInput = document.querySelector('input[name="task_title"]');
                if(titleInput.value.trim() === '') return;
                
                e.preventDefault(); 
                showToast("Task created successfully!", "success");
                
                titleInput.value = '';
                document.querySelector('textarea[name="task_description"]').value = '';
                
                document.querySelector('select[name="assigned_to"]').value = 'Unassigned';
                document.querySelector('select[name="task_label"]').selectedIndex = 0;
            }
        }

        // --- REAL-TIME PASSWORD MATCH VALIDATION LOGIC ---
        function checkPasswordMatch(passId, confirmPassId, errorMsgId, submitBtnId) {
            const password = document.getElementById(passId).value;
            const confirmPassword = document.getElementById(confirmPassId).value;
            const errorMsg = document.getElementById(errorMsgId);
            const submitBtn = document.getElementById(submitBtnId);

            if (confirmPassword !== "") {
                if (password !== confirmPassword) {
                    errorMsg.style.display = "block";
                    submitBtn.disabled = true; 
                } else {
                    errorMsg.style.display = "none";
                    submitBtn.disabled = false; 
                }
            } else {
                errorMsg.style.display = "none";
                submitBtn.disabled = false;
            }
        }

        // --- PASSWORD SHOW/HIDE LOGIC ---
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }

        // --- DARK MODE LOGIC ---
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            darkModeToggle.classList.replace('fa-moon', 'fa-sun');
        }

        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
                darkModeToggle.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('darkMode', 'disabled');
                darkModeToggle.classList.replace('fa-sun', 'fa-moon');
            }
        });

        // --- SWEETALERT CONFIRMATION FOR DELETION ---
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

        // --- TOAST NOTIFICATION LOGIC ---
        window.onload = function() {
            <?php if($toastMessage != ""): ?>
                showToast("<?php echo $toastMessage; ?>", "<?php echo $toastType; ?>");
                
                let activeTabAfterLoad = "<?php echo $activeTabAfterSubmit; ?>";
                
                if(activeTabAfterLoad === "tasks") {
                    const taskBtn = document.querySelector('li[onclick="showTaskManagement(this)"]');
                    if(taskBtn) showTaskManagement(taskBtn);
                } else if(activeTabAfterLoad === "companies") {
                    const compBtn = document.querySelector('li[onclick="showCompanyOrg(this)"]');
                    if(compBtn) showCompanyOrg(compBtn);
                } else if(activeTabAfterLoad === "clients") {
                    const clientBtn = document.querySelector('li[onclick="showAccountsClients(this)"]');
                    if(clientBtn) showAccountsClients(clientBtn);
                } else if(activeTabAfterLoad === "deals") {
                    const dealBtn = document.querySelector('li[onclick="showDealPipeline(this)"]');
                    if(dealBtn) showDealPipeline(dealBtn);
                } else if(document.getElementById('userListSection')) {
                    hideAllSections();
                    document.getElementById('userListSection').style.display = 'block';
                    
                    document.querySelectorAll('.sidebar-menu .active, .sidebar-menu .active-main, .sidebar-menu .active-sub').forEach(el => {
                        el.classList.remove('active', 'active-main', 'active-sub');
                    });
                    const userListBtn = document.querySelector('li[onclick="showUserList(this)"]');
                    if(userListBtn) setActiveSidebar(userListBtn);
                }
            <?php endif; ?>
        };

        function showToast(message, type) {
            const toast = document.getElementById("toastBox");
            const toastMsg = document.getElementById("toastMsg");
            const toastIcon = document.getElementById("toastIcon");

            toastMsg.innerText = message;
            toast.className = "show " + type;

            if (type === 'success') {
                toastIcon.className = "fa-solid fa-circle-check";
            } else {
                toastIcon.className = "fa-solid fa-circle-xmark";
            }

            setTimeout(function(){ 
                toast.className = toast.className.replace("show", ""); 
            }, 3000);
        }

        // --- SIDEBAR TOGGLE LOGIC ---
        const outerToggle = document.getElementById('outerToggle');
        const sidebar = document.getElementById('sidebar');
        outerToggle.addEventListener('click', () => sidebar.classList.toggle('collapsed'));

        // --- SUBMENU TOGGLE & ROUTING LOGIC ---
        function setActiveSidebar(el) {
            document.querySelectorAll('.sidebar-menu > li.active').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('.dropdown-title.active-main').forEach(item => item.classList.remove('active-main'));
            document.querySelectorAll('.submenu li.active-sub').forEach(item => item.classList.remove('active-sub'));

            if(el) {
                if(el.parentElement && el.parentElement.classList.contains('submenu')) {
                    el.classList.add('active-sub');
                    const parentDropdown = el.closest('.dropdown-item');
                    if(parentDropdown) {
                        parentDropdown.querySelector('.dropdown-title').classList.add('active-main');
                    }
                } else {
                    el.classList.add('active');
                }
            }
        }

        else if(targetSectionId === 'companySection') {
                    showCompanyOrg(firstSubItem);
                } else if(targetSectionId === 'accountsClientsSection') {
                    showAccountsClients(firstSubItem);
                } else if(targetSectionId === 'dealPipelineSection') {
                    showDealPipeline(firstSubItem);
                } else if(targetSectionId === 'comingSoonSection') {
                    const pageName = firstSubItem ? firstSubItem.innerText.trim() : 'Module';
                    showComingSoon(pageName, firstSubItem);
                }
            }
        }

        // --- SECTION VISIBILITY TOGGLES (SAFE MODE) ---
        function hideAllSections() {
            const sections = ['mainDashboardContent', 'userListSection', 'taskManagementSection', 'companySection', 'accountsClientsSection', 'dealPipelineSection', 'comingSoonSection'];
            sections.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
        }

        function showDashboard(el = null) {
            hideAllSections();
            const dashboardSec = document.getElementById('mainDashboardContent');
            if(dashboardSec) dashboardSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showUserList(el = null) {
            hideAllSections();
            const userList = document.getElementById('userListSection');
            if(userList) userList.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showTaskManagement(el = null) {
            hideAllSections();
            const taskSec = document.getElementById('taskManagementSection');
            if(taskSec) taskSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showCompanyOrg(el = null) {
            hideAllSections();
            const compSec = document.getElementById('companySection');
            if(compSec) compSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showAccountsClients(el = null) {
            hideAllSections();
            const clSec = document.getElementById('accountsClientsSection');
            if(clSec) clSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showDealPipeline(el = null) {
            hideAllSections();
            const dealSec = document.getElementById('dealPipelineSection');
            if(dealSec) dealSec.style.display = 'block';
            if(el) setActiveSidebar(el);
        }

        function showComingSoon(pageName, el = null) {
            hideAllSections();
            const comingSoonSec = document.getElementById('comingSoonSection');
            if(comingSoonSec) {
                comingSoonSec.style.display = 'flex';
                if(pageName !== 'Module') {
                    document.getElementById('comingSoonTitle').innerText = pageName + ' - Coming Soon!';
                } else {
                    document.getElementById('comingSoonTitle').innerText = 'Feature Coming Soon!';
                }
            }
            if(el) setActiveSidebar(el);
        }

        // --- DATA FILTERING LOGIC (All, Active, Inactive Tabs) ---
        function filterUsers(status, btnElement) {
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            const rows = document.querySelectorAll('.user-row');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    if (row.getAttribute('data-status') === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        // --- MODAL CONTROL LOGIC ---
        function openModal(id) { document.getElementById(id).style.display = "flex"; }
        function closeModal(id) { document.getElementById(id).style.display = "none"; }

        function openEditDesignationModal(desig) {
            closeModal('createDesignationModal');
            document.getElementById('edit_desig_id').value = desig.id;
            document.getElementById('edit_desig_title').value = desig.title;
            openModal('editDesignationModal');
        }

        let currentUserData = null; 
        let currentDealData = null;

        function openViewModal(user) {
            currentUserData = user; 
            document.getElementById('view_name').innerText = user.name || 'N/A';
            document.getElementById('view_username').innerText = user.username || 'N/A';
            document.getElementById('view_email').innerText = user.email || 'N/A';
            document.getElementById('view_phone').innerText = user.phone || 'N/A';
            document.getElementById('view_role').innerText = user.role ? user.role.toUpperCase() : 'N/A';
            document.getElementById('view_dob').innerText = user.dob || 'N/A';
            document.getElementById('view_emp_id').innerText = user.emp_id || 'N/A';
            document.getElementById('view_designation').innerText = user.designation || 'N/A';
            document.getElementById('view_reporting').innerText = user.reporting_manager || 'N/A';
            document.getElementById('view_address').innerText = user.address || 'N/A';
            
            const statusText = (user.status == 'active') ? 'Active' : 'In-Active';
            document.getElementById('view_status').innerText = statusText;
            document.getElementById('view_status').style.color = (user.status == 'active' || user.status == 'Active') ? '#10b981' : '#ef4444';

            openModal('viewUserModal');
        }

        function switchToEditMode() {
            closeModal('viewUserModal');
            if(currentUserData) {
                openEditModal(currentUserData);
            }
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_name').value = user.name || '';
            document.getElementById('edit_username').value = user.username || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_dob').value = user.dob || '';
            document.getElementById('edit_emp_id').value = user.emp_id || '';
            document.getElementById('edit_address').value = user.address || '';
            document.getElementById('edit_designation').value = user.designation || 'COO';
            document.getElementById('edit_reporting').value = user.reporting_manager || 'None';
            
            const statusVal = user.status ? user.status.toLowerCase() : 'active';
            document.getElementById('edit_status').value = statusVal;
            
            document.getElementById('edit_password').value = ''; 
            document.getElementById('edit_confirm_password').value = '';
            document.getElementById('edit_error_msg').style.display = 'none';
            document.getElementById('edit_submit_btn').disabled = false;
            
            openModal('editUserModal');
        }

        function openEditDealModal(deal) {
            document.getElementById('edit_deal_id').value = deal.id;
            document.getElementById('edit_deal_project_name').value = deal.project_name || '';
            document.getElementById('edit_deal_service_required').value = deal.service_required || '';
            document.getElementById('edit_deal_project_status').value = deal.status || 'New';
            document.getElementById('edit_deal_start_date').value = deal.start_date || '';
            document.getElementById('edit_deal_end_date').value = deal.end_date || '';
            document.getElementById('edit_deal_currency').value = deal.currency || 'BDT';
            document.getElementById('edit_deal_total_amount').value = deal.total_amount || '0.00';
            document.getElementById('edit_deal_platform').value = deal.platform || '';
            document.getElementById('edit_deal_sales_officer').value = deal.sales_officer || '';
            document.getElementById('edit_deal_company_id').value = deal.company_id || '';
            
            openModal('editDealModal');
        }

        function openViewDealModal(deal) {
            currentDealData = deal;
            document.getElementById('view_deal_project_name').innerText = deal.project_name || 'N/A';
            document.getElementById('view_deal_service').innerText = deal.service_required || 'N/A';
            document.getElementById('view_deal_status').innerText = deal.status || 'N/A';
            document.getElementById('view_deal_start_date').innerText = deal.start_date || 'N/A';
            document.getElementById('view_deal_end_date').innerText = deal.end_date || 'N/A';
            document.getElementById('view_deal_amount').innerText = (deal.currency || '') + ' ' + (deal.total_amount || '0');
            document.getElementById('view_deal_platform').innerText = deal.platform || 'N/A';
            document.getElementById('view_deal_sales_officer').innerText = deal.sales_officer || 'N/A';
            document.getElementById('view_deal_company').innerText = deal.company_name || 'N/A';
            
            openModal('viewDealModal');
        }

        function switchToEditDealMode() {
            closeModal('viewDealModal');
            if(currentDealData) {
                openEditDealModal(currentDealData);
            }
        }

        // Notification system moved to notifications.php

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
                // Reset wizard if Add Company modal is closed
                if (event.target.id === 'addCompanyModal') {
                    setTimeout(() => coChangeStep(1), 300);
                }
            }
        }
    </script>
</body>
>>>>>>> Stashed changes
</html>