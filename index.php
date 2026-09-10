<?php
session_start();
$message = "";
$message_type = "";

// Path to text file
$file_path = "users.txt";

// Handle Form Submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // REGISTER LOGIC
    if ($action === 'register') {
        $username = trim($_POST['username']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $message = "Passwords do not match!";
            $message_type = "error";
        } else {
            // Check if username already exists in users.txt
            $user_exists = false;
            if (file_exists($file_path)) {
                $file = fopen($file_path, "r");
                while (($line = fgets($file)) !== false) {
                    $data = explode("|", trim($line));
                    if (isset($data[0]) && $data[0] === $username) {
                        $user_exists = true;
                        break;
                    }
                }
                fclose($file);
            }

            if ($user_exists) {
                $message = "Username is already taken!";
                $message_type = "error";
            } else {
                // Save user details to users.txt
                // Format: username|password|first_name|middle_name|last_name
                $record = "$username|$password|$first_name|$middle_name|$last_name\n";
                file_put_contents($file_path, $record, FILE_APPEND);
                
                $message = "Account created successfully! You can now log in.";
                $message_type = "success";
            }
        }
    }

    // LOGIN LOGIC
    elseif ($action === 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $authenticated = false;

        if (file_exists($file_path)) {
            $file = fopen($file_path, "r");
            while (($line = fgets($file)) !== false) {
                $data = explode("|", trim($line));
                if (count($data) >= 5 && $data[0] === $username && $data[1] === $password) {
                    $authenticated = true;
                    $_SESSION['user'] = [
                        'username' => $data[0],
                        'first_name' => $data[2],
                        'middle_name' => $data[3],
                        'last_name' => $data[4]
                    ];
                    break;
                }
            }
            fclose($file);
        }

        if (!$authenticated) {
            $message = "Invalid username or password!";
            $message_type = "error";
        }
    }

    // LOGOUT LOGIC
    elseif ($action === 'logout') {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .card {
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            width: 360px;
        }
        .tab-btn {
            width: 48%;
            padding: 10px;
            cursor: pointer;
            border: none;
            background: #e0e0e0;
            font-weight: bold;
        }
        .tab-btn.active {
            background: #007bff;
            color: white;
        }
        .tabs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 12px;
        }
        label {
            display: block;
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .msg {
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 15px;
            text-align: center;
        }
        .msg.error { background: #f8d7da; color: #721c24; }
        .msg.success { background: #d4edda; color: #155724; }
        .hidden { display: none; }
        .welcome-box {
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">

    <?php if (isset($_SESSION['user'])): ?>
        <!-- WELCOME SCREEN -->
        <div class="welcome-box">
            <h2>Welcome!</h2>
            <p><strong>Full Name:</strong><br> 
                <?= htmlspecialchars($_SESSION['user']['first_name']) . " " . 
                    htmlspecialchars($_SESSION['user']['middle_name']) . " " . 
                    htmlspecialchars($_SESSION['user']['last_name']) ?>
            </p>
            <p><strong>Username:</strong> <?= htmlspecialchars($_SESSION['user']['username']) ?></p>
            
            <form method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-submit" style="background-color: #dc3545;">Logout</button>
            </form>
        </div>

    <?php else: ?>
        <!-- LOGIN / REGISTER TABS -->
        <div class="tabs">
            <button class="tab-btn active" id="loginTab" onclick="switchTab('login')">Login</button>
            <button class="tab-btn" id="registerTab" onclick="switchTab('register')">Create Account</button>
        </div>

        <?php if ($message): ?>
            <div class="msg <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form id="loginForm" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>

        <!-- CREATE ACCOUNT FORM -->
        <form id="registerForm" class="hidden" method="POST" onsubmit="return validatePasswords()">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" required>
            </div>
            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="middle_name">
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="reg_password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" id="reg_confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn-submit">Create Account</button>
        </form>
    <?php endif; ?>

</div>

<script>
function switchTab(tab) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');

    if (tab === 'login') {
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
    } else {
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        registerTab.classList.add('active');
        loginTab.classList.remove('active');
    }
}

function validatePasswords() {
    const pass = document.getElementById('reg_password').value;
    const confirmPass = document.getElementById('reg_confirm_password').value;
    if (pass !== confirmPass) {
        alert("Passwords do not match!");
        return false;
    }
    return true;
}
</script>

</body>
</html>
