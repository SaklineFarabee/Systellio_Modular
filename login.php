<?php
session_start();
include 'config.php'; // আপনার ডেটাবেস কানেকশন ফাইল

$error_msg = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'super_admin') {
                header("Location: super_admin_dashboard.php");
                exit();
            } elseif ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($user['role'] == 'manager') {
                header("Location: manager_dashboard.php");
                exit();
            } elseif ($user['role'] == 'agent') {
                header("Location: agent_dashboard.php");
                exit();
            }
        } else {
            $error_msg = "আপনার দেওয়া পাসওয়ার্ডটি ভুল!";
        }
    } else {
        $error_msg = "এই ইউজারনেমের কোনো ইউজার পাওয়া যায়নি বা অ্যাকাউন্ট ইনঅ্যাকটিভ!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Systellio CRM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f9fafb; /* ব্যাকগ্রাউন্ড হালকা গ্রে করা হয়েছে যাতে বর্ডার ভালো বোঝা যায় */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* ----- ADVANCED BORDER & SHADOW ADDED HERE ----- */
        .login-card {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 40px 35px;
            border-radius: 12px; /* একটু রাউন্ডেড কর্নার */
            border: 1px solid rgba(229, 231, 235, 0.8); /* হালকা বর্ডার */
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); /* প্রিমিয়াম শ্যাডো */
        }

        /* Logo Section */
        .logo-container {
            text-align: center;
            margin-bottom: 0px; 
        }

        .logo-container img {
            max-width: 110px;
            height: auto;
        }

        .header-title {
            font-size: 28px;
            color: #111827;
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px; 
            letter-spacing: 0.5px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        label {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            display: block;
            margin-bottom: 8px;
        }

        .badge-encrypted {
            background-color: #e0e7ff; 
            color: #4f46e5; 
            font-size: 9px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            background-color: #f3f4f6; 
            border: 1px solid transparent; /* ফোকাস করার জন্য ট্রান্সপারেন্ট বর্ডার */
            border-radius: 6px;
            font-size: 14px;
            color: #1f2937;
            outline: none;
            transition: all 0.3s ease;
        }

        input::placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        input:focus {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            box-shadow: 0 0 0 3px rgba(209, 213, 219, 0.2); /* ফোকাস রিং */
        }

        .eye-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .eye-icon:hover {
            color: #111827;
        }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            margin-bottom: 30px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px; 
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            margin-bottom: 0;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            border-radius: 4px;
            display: grid;
            place-content: center;
            transition: 0.2s;
        }

        .remember-me input[type="checkbox"]::before {
            content: "";
            width: 9px;
            height: 9px;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em #ffffff;
            background-color: #ffffff;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }

        .remember-me input[type="checkbox"]:checked {
            background-color: #111827;
            border-color: #111827;
        }

        .remember-me input[type="checkbox"]:checked::before {
            transform: scale(1);
        }

        .forgot-pass {
            font-size: 13px; 
            font-weight: 600;
            color: #374151;
            text-decoration: none;
            transition: color 0.3s;
        }

        .forgot-pass:hover {
            color: #000000;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            background-color: #0f172a;
            color: #ffffff;
            padding: 15px;
            border: none;
            border-radius: 6px;
            font-size: 15px; 
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
        }

        .login-btn:hover {
            background-color: #1e293b;
        }

        .login-btn:active {
            transform: scale(0.98);
        }

        .footer {
            margin-top: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .footer .line {
            height: 1px;
            width: 50px;
            background-color: #e5e7eb;
        }

        .footer-text {
            font-size: 11px; 
            font-weight: 700;
            color: #6b7280;
        }

        .error {
            color: #dc2626;
            background-color: #fef2f2;
            border: 1px solid #f87171;
            padding: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 6px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <div class="logo-container">
            <img src="img/logo1.png" alt="Systellio Logo">
        </div>

        <h1 class="header-title">WELCOME</h1>

        <?php if($error_msg != "") { echo "<div class='error'><i class='fa-solid fa-circle-exclamation'></i> $error_msg</div>"; } ?>

        <form action="login.php" method="POST">
            <div class="input-group">
                <label>User ID</label>
                <input type="text" name="username" placeholder="Input User ID" required>
            </div>

            <div class="input-group" style="margin-bottom: 15px;">
                <div class="label-row">
                    <label style="margin-bottom: 0;">User Password</label>
                    <span class="badge-encrypted"><i class="fa-solid fa-lock" style="font-size: 8px;"></i> Encrypted</span>
                </div>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="**************" required>
                    <svg id="togglePassword" class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </div>
            </div>

            <div class="options-row">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Remember Me
                </label>
                <a href="#" class="forgot-pass">Forget Password?</a>
            </div>

            <button type="submit" name="login" class="login-btn">
                Log In
            </button>
        </form>

        <div class="footer">
            <div class="line"></div>
            <div class="footer-text">By Peer Solution With <span style="color: #ef4444; font-size: 13px;">❤️</span></div>
            <div class="line"></div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle eye icon design
            if(type === 'text'){
                this.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" y1="2" x2="22" y2="22"></line>';
            } else {
                this.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        });
    </script>

</body>
</html>