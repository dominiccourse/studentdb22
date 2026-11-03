
<?php
session_start();
include 'db.php';

// Protect dashboard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function clean($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$error = "";
$success = "";

// ADD USER
if (isset($_POST['add'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid request.");
    }

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $age = (int) $_POST['age'];

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $age < 1 || $age > 120) {
        $error = "Please enter valid user information.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, age) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssi", $name, $email, $age);

        if ($stmt->execute()) {
            header("Location: index.php?status=added");
            exit();
        } else {
            $error = "Unable to add user. Please check the email.";
        }
    }
}

// UPDATE USER
if (isset($_POST['update'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid request.");
    }

    $id = (int) $_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $age = (int) $_POST['age'];

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $age < 1 || $age > 120) {
        $error = "Please enter valid user information.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE users SET name=?, email=?, age=? WHERE id=?"
        );
        $stmt->bind_param("ssii", $name, $email, $age, $id);

        if ($stmt->execute()) {
            header("Location: index.php?status=updated");
            exit();
        } else {
            $error = "Unable to update user.";
        }
    }
}

// DELETE USER
if (isset($_POST['delete'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Invalid request.");
    }

    $id = (int) $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: index.php?status=deleted");
        exit();
    } else {
        $error = "Unable to delete user.";
    }
}

// FETCH USER FOR EDITING
$data = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $data = $stmt->get_result()->fetch_assoc();
}

// FETCH ALL USERS
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");

// SUCCESS MESSAGES
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'added':
            $success = "User added successfully!";
            break;
        case 'updated':
            $success = "User updated successfully!";
            break;
        case 'deleted':
            $success = "User deleted successfully!";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Esmeringhoy</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="dashboard">

    <!-- HEADER -->
    <div class="dashboard-header">

        <div>
            <h2>Esmeringhoy Dashboard</h2>
            <p class="welcome">
                Welcome back, <?= clean($_SESSION['name'] ?? 'User') ?>!
            </p>
        </div>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

    <div class="dashboard-divider"></div>

    <!-- MESSAGES -->
    <?php if ($success): ?>
        <div class="message success">
            <?= clean($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <?= clean($error) ?>
        </div>
    <?php endif; ?>


    <!-- ADD / EDIT USER FORM -->
    <div class="form-card">

        <?php if (isset($data) && $data): ?>

            <h3>✏️ Edit User</h3>
            <p class="form-description">Update the user's information below.</p>

            <form method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= clean($_SESSION['csrf_token']) ?>">

                <input type="hidden" name="id"
                       value="<?= clean($data['id']) ?>">

                <div class="form-grid">

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name"
                               value="<?= clean($data['name']) ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email"
                               value="<?= clean($data['email']) ?>" required>
                    </div>

                    <div class="input-group">
                        <label>Age</label>
                        <input type="number" name="age"
                               value="<?= clean($data['age']) ?>"
                               min="1" max="120" required>
                    </div>

                </div>

                <button type="submit" name="update" class="btn">
                    Update User
                </button>

                <a href="index.php" class="cancel-btn">
                    Cancel
                </a>

            </form>

        <?php else: ?>

            <h3>➕ Add New User</h3>
            <p class="form-description">Enter the details to add a new user.</p>

            <form method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= clean($_SESSION['csrf_token']) ?>">

                <div class="form-grid">

                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="name"
                               placeholder="Enter full name" required>
                    </div>

                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="email"
                               placeholder="Enter email address" required>
                    </div>

                    <div class="input-group">
                        <label>Age</label>
                        <input type="number" name="age"
                               placeholder="Enter age"
                               min="1" max="120" required>
                    </div>

                </div>

                <button type="submit" name="add" class="btn">
                    Add User
                </button>

            </form>

        <?php endif; ?>

    </div>


    <!-- USER LIST -->
    <div class="table-card">

        <div class="table-header">
            <div>
                <h3>👥 User List</h3>
                <p class="form-description">
                    Manage all registered users.
                </p>
            </div>

            <span class="user-count">
                <?= $result->num_rows ?> Users
            </span>
        </div>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?= clean($row['id']) ?></td>
                            <td><?= clean($row['name']) ?></td>
                            <td><?= clean($row['email']) ?></td>
                            <td><?= clean($row['age']) ?></td>

                            <td>
                                <div class="action-buttons">

                                    <a href="?edit=<?= (int)$row['id'] ?>"
                                       class="edit-btn">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this user?');">

                                        <input type="hidden" name="csrf_token"
                                               value="<?= clean($_SESSION['csrf_token']) ?>">

                                        <input type="hidden" name="id"
                                               value="<?= (int)$row['id'] ?>">

                                        <button type="submit" name="delete"
                                                class="delete-btn">
                                            Delete
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="empty-message">
                            No users found. Add your first user above!
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <p class="dashboard-footer">
        © <?= date('Y') ?> Esmeringhoy Application. All rights reserved.
    </p>

</div>

</body>
</html>
