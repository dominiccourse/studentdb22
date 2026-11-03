<?php include 'db.php';

$error = "";
$success = "";

if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = "Email already exists. Please use another.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Esmeringhoy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <div class="logo">✨</div>

    <h2>Create Account</h2>
    <p class="subtitle">Join the Esmeringhoy Application</p>

    <?php if ($error): ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
            <br><br>
            <a href="login.php" style="color:#a78bfa;">
                Login here
            </a>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="name"
                   placeholder="Enter your full name" required>
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email"
                   placeholder="Enter your email address" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password"
                   placeholder="Create a password"
                   minlength="8" required>
        </div>

        <button type="submit" name="register" class="btn">
            Create My Account
        </button>

    </form>

    <p class="footer-text">
        Already have an account?
        <a href="login.php">Login here</a>
    </p>

</div>

</body>
</html>
