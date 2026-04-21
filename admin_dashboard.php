<?php
session_start();

// সিকিউরিটি চেক: ইউজার লগইন করেছে কিনা এবং তার রোল 'admin' কিনা তা যাচাই করা
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f3f4f6;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Design */
        .sidebar {
            width: 260px;
            background-color: #1f2229;
            color: #ffffff;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 24px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            border-bottom: 1px solid #374151;
            text-align: center;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
            flex-grow: 1;
        }

        .sidebar-menu li {
            padding: 15px 24px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: 0.3s;
            color: #9ca3af;
        }

        .sidebar-menu li:hover, .sidebar-menu li.active {
            background-color: #111317;
            color: #ffffff;
            border-left: 4px solid #3b82f6; /* অ্যাডমিনদের জন্য নীল রঙের হাইলাইট */
        }

        .sidebar-menu li i {
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu li a {
            color: inherit;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            width: 100%;
        }

        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .top-navbar {
            background-color: #ffffff;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.03);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }

        .user-profile i {
            font-size: 24px;
            color: #3b82f6;
        }

        .dashboard-container {
            padding: 30px;
        }

        .page-title {
            font-size: 24px;
            color: #111827;
            font-weight: 700;
            margin-bottom: 24px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e5e7eb;
        }

        .card-info h4 {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .card-info h2 {
            font-size: 28px;
            color: #111827;
        }

        .card-icon {
            background-color: #eff6ff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            color: #3b82f6;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            CRM SYSTEM
        </div>
        <ul class="sidebar-menu">
            <li class="active">
                <i class="fa-solid fa-chart-line"></i>
                <a href="admin_dashboard.php">Admin Panel</a>
            </li>
            <li>
                <i class="fa-solid fa-briefcase"></i>
                <a href="#">Manage Managers</a>
            </li>
            <li>
                <i class="fa-solid fa-headset"></i>
                <a href="#">Manage Agents</a>
            </li>
            <li>
                <i class="fa-solid fa-file-invoice"></i>
                <a href="#">Reports</a>
            </li>
            <li>
                <i class="fa-solid fa-right-from-bracket"></i>
                <a href="logout.php">Logout</a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <div>
                <h3 style="color: #4b5563; font-size: 16px;">Welcome Back!</h3>
            </div>
            <div class="user-profile">
                <i class="fa-solid fa-circle-user"></i>
                <span><?php echo $_SESSION['name']; ?> (Admin)</span>
            </div>
        </div>

        <div class="dashboard-container">
            <h1 class="page-title">Admin Overview</h1>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-info">
                        <h4>My Managers</h4>
                        <h2>08</h2>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-briefcase"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h4>Total Agents</h4>
                        <h2>32</h2>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h4>Today's Tasks</h4>
                        <h2>15</h2>
                    </div>
                    <div class="card-icon"><i class="fa-solid fa-list-check"></i></div>
                </div>

                <div class="card">
                    <div class="card-info">
                        <h4>Pending Tickets</h4>
                        <h2 style="color: #dc2626;">04</h2>
                    </div>
                    <div class="card-icon" style="background-color: #fef2f2; color: #dc2626;"><i class="fa-solid fa-ticket"></i></div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>