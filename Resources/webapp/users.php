<?php
// Handle deletes BEFORE any HTML so header() works cleanly
$servername = "DB_ENDPOINT_PLACEHOLDER";
$username   = "DB_USERNAME_PLACEHOLDER";
$password   = "DB_PASSWORD_PLACEHOLDER";
$dbname     = "DB_NAME_PLACEHOLDER";

$status_added   = !empty($_GET["added"]);
$status_deleted = !empty($_GET["deleted"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["delete_id"])) {
    $delete_id = (int) $_POST["delete_id"];
    if ($delete_id > 0) {
        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if (!$conn->connect_error) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                $stmt->close();
                $conn->close();
            }
        } catch (Exception $e) {
            // Swallow delete errors; page will just reload without the success banner
        }
        header("Location: users.php?deleted=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - 3-Tier App</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        nav a { margin-right: 10px; color: #667eea; text-decoration: none; }
        nav { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #667eea; color: white; }
        tr:nth-child(even) { background: #f8f9fa; }
        .success { color: #28a745; margin-bottom: 15px; }
        .error { color: #dc3545; }
        .btn-delete { padding: 6px 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-delete:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>All Users</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="users.php">View Users</a>
            <a href="add-user.php">Add User</a>
        </nav>
        <?php
        if ($status_added) {
            echo '<p class="success">User added successfully.</p>';
        }
        if ($status_deleted) {
            echo '<p class="success">User deleted successfully.</p>';
        }

        try {
            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }
            $result = $conn->query("SELECT id, name, email FROM users ORDER BY id");
            if ($result && $result->num_rows > 0) {
                echo '<table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>';
                while ($row = $result->fetch_assoc()) {
                    $id = (int) $row["id"];
                    echo '<tr><td>' . $id . '</td><td>' . htmlspecialchars($row["name"]) . '</td><td>' . htmlspecialchars($row["email"]) . '</td><td>';
                    echo '<form method="post" action="users.php" style="display:inline;" onsubmit="return confirm(\'Delete this user?\');">';
                    echo '<input type="hidden" name="delete_id" value="' . $id . '">';
                    echo '<button type="submit" class="btn-delete">Delete</button></form></td></tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No users yet. <a href="add-user.php">Add a user</a>.</p>';
            }
            $conn->close();
        } catch (Exception $e) {
            echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</body>
</html>

