<?php 
require_once 'app/init.php';

if ($Ouser->is_login() != false) {
    header("location:index.php");
    exit();
}

// Check for login error from redirect
$login_error = '';
if (isset($_GET['error']) && $_GET['error'] == 1) {
    $login_error = 'Invalid email or password. Please try again.';
}
?>

<!DOCTYPE HTML>
<html lang="en-us">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-left {
            padding: 3rem 2rem;
            background: white;
        }

        .login-right {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(45deg);
            top: -50%;
            left: -50%;
            animation: shimmer 6s infinite linear;
        }

        @keyframes shimmer {
            from {
                transform: rotate(45deg) translateY(0);
            }
            to {
                transform: rotate(45deg) translateY(100%);
            }
        }

        .logo-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-wrapper img {
            height: 100px;
            width: auto;
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-wrapper img:hover {
            transform: scale(1.05);
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.95rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #999;
            z-index: 1;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #aaa;
            font-size: 0.9rem;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            color: #999;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .toggle-password:hover {
            color: #667eea;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .checkbox-wrapper label {
            color: #666;
            font-size: 0.95rem;
            cursor: pointer;
            margin: 0;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-right: 8px;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: none;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .alert-danger {
            background: #fff2f0;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .support-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .support-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .support-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .right-content {
            position: relative;
            z-index: 2;
        }

        .right-content i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .right-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .right-content p {
            font-size: 1rem;
            opacity: 0.9;
            max-width: 300px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .login-card {
                max-width: 500px;
                margin: 0 auto;
            }
            
            .login-right {
                display: none;
            }
            
            .login-left {
                padding: 2rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 0.5rem;
            }
            
            .login-left {
                padding: 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
            
            .logo-wrapper img {
                height: 80px;
            }
            
            .btn-login {
                padding: 12px 20px;
            }
            
            .form-control {
                padding: 10px 15px 10px 40px;
            }
            
            .checkbox-wrapper label {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 380px) {
            .login-left {
                padding: 1rem;
            }
            
            .login-title {
                font-size: 1.3rem;
            }
            
            .form-group label {
                font-size: 0.85rem;
            }
        }

        /* Landscape mode for mobile */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
            }
            
            .login-left {
                padding: 1rem;
            }
            
            .logo-wrapper {
                margin-bottom: 1rem;
            }
            
            .logo-wrapper img {
                height: 60px;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
        }

        /* Tablet specific */
        @media (min-width: 768px) and (max-width: 991px) and (orientation: portrait) {
            .login-card {
                max-width: 600px;
            }
            
            .login-left {
                padding: 3rem;
            }
        }

        /* Large screens */
        @media (min-width: 1400px) {
            .login-container {
                max-width: 1400px;
            }
            
            .login-left {
                padding: 4rem;
            }
            
            .login-right {
                padding: 4rem;
            }
            
            .login-title {
                font-size: 2rem;
            }
        }

        /* High DPI screens */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .logo-wrapper img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }
    </style>
    
    <title>Secure Login | Modern Authentication</title>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Column - Login Form -->
                <div class="col-lg-6 col-md-12">
                    <div class="login-left">
                        <div class="logo-wrapper">
                            <a href="/">
                                <img src="dist/img/log.jpg" alt="Company Logo" loading="lazy">
                            </a>
                        </div>
                        
                        <h2 class="login-title">Welcome Back</h2>
                        <p class="login-subtitle">Please enter your credentials to access your account</p>

                        <?php if (isset($login_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($login_error); ?>
                            </div>
                        <?php endif; ?>

                        <form action="app/action/login.php" method="post" id="loginForm" novalidate>
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email Address
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email"
                                        name="username" 
                                        placeholder="Enter your email"
                                        required
                                        pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                                        title="Please enter a valid email address"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password"
                                        name="password" 
                                        placeholder="Enter your password"
                                        required
                                        minlength="6"
                                    >
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="remember" name="remember">
                                    <label for="remember">Remember me for 30 days</label>
                                </div>
                                <a href="/forgot-password" class="text-decoration-none" style="color: #667eea; font-size: 0.9rem;">
                                    Forgot Password?
                                </a>
                            </div>

                            <button type="submit" name="admin_login" class="btn-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Sign In
                            </button>

                            <div class="support-link">
                                <b>Need help? <a href="https://mayurik.com" target="_blank" rel="noopener noreferrer">
                                    <i class="fas fa-headset me-1"></i>Contact Support
                                </a></b>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Hero Section -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="login-right">
                        <div class="right-content">
                            <i class="fas fa-shield-alt"></i>
                            <h3>Secure Access</h3>
                            <p>Your security is our priority. All connections are encrypted and monitored 24/7.</p>
                            
                            <div class="mt-5">
                                <i class="fas fa-check-circle me-2"></i>
                                <i class="fas fa-check-circle me-2"></i>
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <p class="mt-3" style="font-size: 0.9rem;">
                                <i class="fas fa-lock me-1"></i> SSL Encrypted<br>
                                <i class="fas fa-shield me-1"></i> 2FA Available<br>
                                <i class="fas fa-clock me-1"></i> 99.9% Uptime
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form validation and submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            // Basic client-side validation
            if (!email.value.match(/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                email.focus();
                return false;
            }
            
            if (password.value.length < 6) {
                e.preventDefault();
                showError('Password must be at least 6 characters long');
                password.focus();
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            submitBtn.disabled = true;
        });

        // Show error message
        function showError(message) {
            // Remove any existing alerts
            const existingAlert = document.querySelector('.alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger';
            alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i>' + message;
            
            // Insert at the top of the form
            const form = document.getElementById('loginForm');
            form.parentNode.insertBefore(alertDiv, form);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Add input focus effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#667eea';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon').style.color = '#999';
            });
        });

        // Remember checkbox enhancement
        document.getElementById('remember').addEventListener('change', function() {
            localStorage.setItem('rememberPreference', this.checked);
        });

        // Load saved preference
        const savedPreference = localStorage.getItem('rememberPreference');
        if (savedPreference === 'true') {
            document.getElementById('remember').checked = true;
        }

        // Prevent zoom on input focus for mobile
        if (/Mobi|Android/i.test(navigator.userAgent)) {
            document.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('focus', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
                });
                
                element.addEventListener('blur', function() {
                    document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes');
                });
            });
        }

        // Handle keyboard events
        document.addEventListener('keydown', function(e) {
            // Submit form with Ctrl+Enter or Cmd+Enter
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                document.getElementById('loginForm').requestSubmit();
            }
        });
    </script>
</body>
</html>