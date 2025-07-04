<?php
require "includes/security.php";
require "includes/sendbird.php";
session_start();

$error_message = "";
$success_message = "";

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// Try to connect to database
try {
    require "includes/db.php";
    $db_connected = true;
} catch (Exception $e) {
    $db_connected = false;
    $error_message = "Database connection failed. Please try again later.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $db_connected) {
    // Rate limiting for registration attempts
    if (!checkRateLimit('register', 3, 600)) { // 3 attempts per 10 minutes
        $error_message = "Too many registration attempts. Please try again in 10 minutes.";
        logSecurityEvent('Rate limit exceeded', 'Registration attempts');
    } else {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error_message = "Security validation failed. Please try again.";
            logSecurityEvent('CSRF token validation failed', 'Registration attempt');
        } else {
            $name = sanitizeInput($_POST["name"]);
            $email = sanitizeInput($_POST["email"]);
            $password = $_POST["password"];
            $confirm_password = $_POST["confirm_password"];

            // Enhanced validation
            if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                $error_message = "Please fill in all fields.";
            } elseif (strlen($name) < 2 || strlen($name) > 100) {
                $error_message = "Name must be between 2 and 100 characters long.";
            } elseif (!validateEmail($email)) {
                $error_message = "Please enter a valid email address.";
            } elseif (!validatePassword($password)) {
                $error_message = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.";
            } elseif ($password !== $confirm_password) {
                $error_message = "Passwords do not match.";
            } else {
                try {
                    // Check if email already exists
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error_message = "An account with this email already exists.";
                        logSecurityEvent('Registration attempt with existing email', "Email: $email");
                    } else {
                        // Create new user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                        $stmt->bind_param("sss", $name, $email, $hashed_password);
                        
                        if ($stmt->execute()) {
                            logSecurityEvent('Successful registration', "Email: $email");
                            // Sendbird integration: create user in Sendbird
                            $new_user_id = $conn->insert_id;
                            sendbird_create_user($new_user_id, $name);
                            header("Location: login.php?registered=1");
                            exit();
                        } else {
                            $error_message = "Registration failed. Please try again.";
                            logSecurityEvent('Registration failed', "Email: $email - Database error");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $error_message = "Database error. Please try again later.";
                }
            }
        }
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "POST" && !$db_connected) {
    $error_message = "Database connection failed. Please try again later.";
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Missing Items Tracker</title>
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
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .register-card {
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
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: var(--danger-color); }
        .strength-medium { background: var(--warning-color); }
        .strength-strong { background: var(--success-color); }
        
        .requirements {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .requirement.met {
            color: var(--success-color);
        }
        
        .requirement i {
            font-size: 0.75rem;
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
            <div class="col-lg-6 col-md-8">
                <div class="register-card p-5">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-search-location fa-3x text-primary mb-3"></i>
                        </a>
                        <h2 class="fw-bold mb-2">Create Account</h2>
                        <p class="text-muted">Join our community to report and find missing items</p>
                    </div>

                    <!-- Error Messages -->
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Registration Form -->
                    <form method="post" id="registerForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               placeholder="Enter your full name" 
                                               value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope text-muted"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email address" 
                                       value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Create a strong password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="passwordToggle"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="requirements" id="passwordRequirements">
                                <div class="requirement" id="req-length">
                                    <i class="fas fa-circle"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="req-uppercase">
                                    <i class="fas fa-circle"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="requirement" id="req-lowercase">
                                    <i class="fas fa-circle"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="requirement" id="req-number">
                                    <i class="fas fa-circle"></i>
                                    <span>One number</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirmPasswordToggle"></i>
                                </button>
                            </div>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="text-center">
                        <p class="text-muted mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-decoration-none fw-bold">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleId = fieldId === "password" ? "passwordToggle" : "confirmPasswordToggle";
            const passwordToggle = document.getElementById(toggleId);
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                passwordToggle.classList.remove("fa-eye");
                passwordToggle.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                passwordToggle.classList.remove("fa-eye-slash");
                passwordToggle.classList.add("fa-eye");
            }
        }

        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById("passwordStrength");
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };

            // Update requirement indicators
            document.getElementById("req-length").className = `requirement ${requirements.length ? "met" : ""}`;
            document.getElementById("req-uppercase").className = `requirement ${requirements.uppercase ? "met" : ""}`;
            document.getElementById("req-lowercase").className = `requirement ${requirements.lowercase ? "met" : ""}`;
            document.getElementById("req-number").className = `requirement ${requirements.number ? "met" : ""}`;

            // Update strength bar
            const metCount = Object.values(requirements).filter(Boolean).length;
            strengthBar.className = "password-strength";
            
            if (metCount <= 2) {
                strengthBar.classList.add("strength-weak");
            } else if (metCount === 3) {
                strengthBar.classList.add("strength-medium");
            } else {
                strengthBar.classList.add("strength-strong");
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            const matchDiv = document.getElementById("passwordMatch");
            
            if (confirmPassword === "") {
                matchDiv.innerHTML = "";
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = "<small class=\"text-success\"><i class=\"fas fa-check-circle me-1\"></i>Passwords match</small>";
            } else {
                matchDiv.innerHTML = "<small class=\"text-danger\"><i class=\"fas fa-times-circle me-1\"></i>Passwords do not match</small>";
            }
        }

        // Event listeners
        document.getElementById("password").addEventListener("input", function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });

        document.getElementById("confirm_password").addEventListener("input", checkPasswordMatch);

        // Form validation
        document.getElementById("registerForm").addEventListener("submit", function(e) {
            const name = document.getElementById("name").value.trim();
            const email = document.getElementById("email").value.trim();
            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm_password").value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert("Please fill in all fields.");
                return false;
            }
            
            if (name.length < 2) {
                e.preventDefault();
                alert("Name must be at least 2 characters long.");
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert("Please enter a valid email address.");
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert("Password must be at least 8 characters long.");
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert("Passwords do not match.");
                return false;
            }
        });
    </script>
</body>
</html> 