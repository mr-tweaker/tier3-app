<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3-Tier Web Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin: 15px 0;
        }
        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .label {
            font-weight: bold;
            color: #666;
        }
        .value {
            color: #333;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 3-Tier Web Application</h1>
        <nav style="margin-bottom: 20px;">
            <a href="index.php" style="color: #667eea; text-decoration: none;">Home</a> |
            <a href="users.php" style="color: #667eea; text-decoration: none;">View Users</a> |
            <a href="add-user.php" style="color: #667eea; text-decoration: none;">Add User</a>
        </nav>
        <?php
        // Get instance/task metadata (works for both EC2 and Fargate)
        function getInstanceMetadata($path) {
            $token_opts = [
                'http' => [
                    'method' => 'PUT',
                    'header' => "X-aws-ec2-metadata-token-ttl-seconds: 21600\r\n",
                    'timeout' => 3
                ]
            ];
            $token_context = stream_context_create($token_opts);
            $token = @file_get_contents('http://169.254.169.254/latest/api/token', false, $token_context);

            if ($token === false) {
                return @file_get_contents('http://169.254.169.254/latest/meta-data/' . $path);
            }

            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "X-aws-ec2-metadata-token: $token\r\n",
                    'timeout' => 3
                ]
            ];
            $context = stream_context_create($opts);
            return @file_get_contents('http://169.254.169.254/latest/meta-data/' . $path, false, $context);
        }

        // Defaults
        $instance_id = 'Unknown';
        $availability_zone = 'Unknown';
        $private_ip = 'Unknown';

        // Prefer ECS/Fargate task metadata when available
        $metadata_uri = getenv('ECS_CONTAINER_METADATA_URI_V4');
        if ($metadata_uri) {
            $meta_raw = @file_get_contents($metadata_uri);
            if ($meta_raw !== false) {
                $meta = json_decode($meta_raw, true);
                if (is_array($meta)) {
                    if (!empty($meta['TaskARN'])) {
                        $parts = explode('/', $meta['TaskARN']);
                        $instance_id = end($parts);
                    }
                    if (!empty($meta['AvailabilityZone'])) {
                        $availability_zone = $meta['AvailabilityZone'];
                    }
                    if (!empty($meta['Networks'][0]['IPv4Addresses'][0])) {
                        $private_ip = $meta['Networks'][0]['IPv4Addresses'][0];
                    }
                }
            }
        }

        // Fallback to EC2 instance metadata (for EC2 launch type)
        if ($instance_id === 'Unknown') {
            $instance_id = getInstanceMetadata('instance-id') ?: 'Unknown';
        }
        if ($availability_zone === 'Unknown') {
            $availability_zone = getInstanceMetadata('placement/availability-zone') ?: 'Unknown';
        }
        if ($private_ip === 'Unknown') {
            $private_ip = getInstanceMetadata('local-ipv4') ?: 'Unknown';
        }

        echo '<h2>📊 Application Layer (This Instance)</h2>';
        echo '<div class="info-box">';
        echo '<p><span class="label">Instance ID:</span> <span class="value">' . htmlspecialchars($instance_id) . '</span></p>';
        echo '<p><span class="label">Availability Zone:</span> <span class="value">' . htmlspecialchars($availability_zone) . '</span></p>';
        echo '<p><span class="label">Private IP:</span> <span class="value">' . htmlspecialchars($private_ip) . '</span></p>';
        echo '<p><span class="label">Timestamp:</span> <span class="value">' . date('Y-m-d H:i:s') . '</span></p>';
        echo '</div>';

        // Database connection
        $servername = "DB_ENDPOINT_PLACEHOLDER";
        $username = "DB_USERNAME_PLACEHOLDER";
        $password = "DB_PASSWORD_PLACEHOLDER";
        $dbname = "DB_NAME_PLACEHOLDER";

        echo '<h2>💾 Database Layer (RDS)</h2>';

        try {
            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                throw new Exception($conn->connect_error);
            }

            echo '<div class="info-box success">';
            echo '<p><span class="label">✅ Database Status:</span> <span style="color: green; font-weight: bold;">Connected Successfully</span></p>';
            echo '<p><span class="label">Database Host:</span> <span class="value">' . htmlspecialchars($servername) . '</span></p>';
            echo '<p><span class="label">Database Name:</span> <span class="value">' . htmlspecialchars($dbname) . '</span></p>';
            echo '</div>';

            // Create tables
            $sql = "CREATE TABLE IF NOT EXISTS visitors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                instance_id VARCHAR(50),
                availability_zone VARCHAR(50),
                private_ip VARCHAR(15)
            )";
            $conn->query($sql);
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                email VARCHAR(255)
            )";
            $conn->query($sql);

            // Log visit
            $stmt = $conn->prepare("INSERT INTO visitors (instance_id, availability_zone, private_ip) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $instance_id, $availability_zone, $private_ip);
            $stmt->execute();
            $stmt->close();

            // Display statistics
            echo '<h2>📈 Application Statistics</h2>';
            $result = $conn->query("SELECT COUNT(*) as count FROM visitors");
            $row = $result->fetch_assoc();
            echo '<div class="info-box">';
            echo '<p><span class="label">Total Page Visits:</span> <span class="value" style="font-size: 1.5em; color: #667eea;">' . number_format($row['count']) . '</span></p>';
            echo '</div>';

            $conn->close();

        } catch (Exception $e) {
            echo '<div class="info-box error">';
            echo '<p><span class="label">❌ Database Status:</span> <span style="color: red; font-weight: bold;">Connection Failed</span></p>';
            echo '<p><span class="label">Error:</span> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <div style="margin-top: 30px; text-align: center; color: #666; font-size: 0.9em;">
            <p>🎓 CloudKeeper 3-Tier Application - CloudFormation Deployment</p>
            <p>Refresh this page to see load balancing in action!</p>
        </div>
    </div>
</body>
</html>

