<?php
// Handle form submission before any HTML is sent so we can safely use header()
$servername = "DB_ENDPOINT_PLACEHOLDER";
$username   = "DB_USERNAME_PLACEHOLDER";
$password   = "DB_PASSWORD_PLACEHOLDER";
$dbname     = "DB_NAME_PLACEHOLDER";

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name  = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if ($name === "" || $email === "") {
        $error_message = 'Name and Email are required.';
    } else {
        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }
            $stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $email);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                header("Location: users.php?added=1");
                exit;
            }
            $stmt->close();
            $conn->close();
            $error_message = 'Could not add user. Please try again.';
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - 3-Tier App</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        nav a { margin-right: 10px; color: #667eea; text-decoration: none; }
        nav { margin-bottom: 20px; }
        label { font-weight: bold; color: #555; display: block; margin-top: 15px; }
        input[type="text"], input[type="email"] { width: 100%; max-width: 400px; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 15px; }
        button:hover { background: #5568d3; }
        .success { color: #28a745; margin-top: 15px; }
        .error { color: #dc3545; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New User</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="users.php">View Users</a>
            <a href="add-user.php">Add User</a>
        </nav>
        <?php if ($error_message !== ''): ?>
            <p class="error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" action="add-user.php">
            <label>Name:</label>
            <input type="text" name="name" required><br>
            <label>Email:</label>
            <input type="email" name="email" required><br>
            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html>

