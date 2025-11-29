<?php
/**
 * User Login Page
 * Handles authentication with password verification
 */

require_once '../config/db.php';

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        try {
            // Fetch user by email
            $stmt = $pdo->prepare("SELECT user_id, name, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Verify password
            if ($user && password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $email;
                
                // Redirect to home page
                header('Location: ../public/index.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Review System</title>
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <h1>Login</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-link">
                Don't have an account? <a href="signup.php">Sign up here</a>
            </p>
            <p class="auth-link">
                <a href="../public/index.php">Back to Home</a>
            </p>
            
            <div class="demo-credentials">
                <p><strong>Demo Credentials:</strong></p>
                <p>Email: john@example.com<br>Password: password123</p>
            </div>
        </div>
    </div>
</body>
</html>