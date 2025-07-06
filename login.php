<?php
require "includes/security.php";
session_start();
require "includes/db.php";

// Set no-cache headers for login page
noCacheHeaders();

$error_message = "";
$success_message = "";

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"]) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Rate limiting for login attempts
    if (!checkRateLimit('login', 5, 300)) { // 5 attempts per 5 minutes
        $error_message = "Too many login attempts. Please try again in 5 minutes.";
        logSecurityEvent('Rate limit exceeded', 'Login attempts');
    } else {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error_message = "Security validation failed. Please try again.";
            logSecurityEvent('CSRF token validation failed', 'Login attempt');
        } else {
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $error_message = "Please fill in all fields.";
            } elseif (!validateEmail($email)) {
                $error_message = "Please enter a valid email address.";
            } else {
                try {
                    $stmt = $conn->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password'])) {
                            // Regenerate session ID for security
                            session_regenerate_id(true);
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['name'] = $user['name'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                            $_SESSION['login_time'] = time();
                            $_SESSION['show_welcome'] = true;
                            
                            // Log successful login
                            logSecurityEvent('Successful login', "User ID: {$user['id']}, Email: {$user['email']}");
                            
                            // Redirect based on user type
                            if ($user['is_admin']) {
                                header("Location: admin/dashboard.php");
                            } else {
                                header("Location: index.php");
                            }
                            exit();
                        } else {
                            $error_message = "Invalid email or password.";
                            logSecurityEvent('Failed login attempt', "Email: $email - Invalid password");
                        }
                    } else {
                        $error_message = "Invalid email or password.";
                        logSecurityEvent('Failed login attempt', "Email: $email - User not found");
                    }
                } catch (Exception $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error_message = "An error occurred. Please try again.";
                }
            }
        }
    }
}

// Check for success messages from registration
if (isset($_GET['registered'])) {
    $success_message = "Registration successful! Please log in with your credentials.";
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Missing Items Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        
        .input-group-text {
            background: transparent;
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-right: none;
            border-radius: 12px 0 0 12px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        
        .divider {
            position: relative;
            text-align: center;
            margin: 2rem 0;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(0, 0, 0, 0.1);
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .social-btn {
            border: 2px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            background: white;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #374151;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .social-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="login-card p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-search-location fa-3x text-primary mb-3"></i>
                        </a>
                        <h2 class="fw-bold mb-2">Welcome Back</h2>
                        <p class="text-muted">Sign in to your account to continue</p>
                    </div>

                    <!-- Success/Error Messages -->
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="post" id="loginForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="passwordToggle"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="text-center">
                        <p class="text-muted mb-0">
                            Don't have an account? 
                            <a href="register.php" class="text-decoration-none fw-bold">
                                Sign up here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });
    </script>
</body>
</html> 