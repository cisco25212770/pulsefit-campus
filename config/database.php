<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * PulseFit Campus — Pre-Configured InfinityFree MySQL Credentials
 * Hostname: sql100.infinityfree.com
 * Username: if0_43369623
 * Database: if0_43369623_pulsefit
 */
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)
               || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') === 0;

if ($isLocalhost) {
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'pulsefit_campus');
} else {
    define('DB_HOST', 'sql100.infinityfree.com');
    define('DB_USER', 'if0_43369623');
    define('DB_PASS', 'Mikes1731');
    define('DB_NAME', 'if0_43369623_pulsefit');
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    global $isLocalhost;

    try {
        if ($isLocalhost && DB_HOST === '127.0.0.1') {
            $dsnServer = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $serverPdo = new PDO($dsnServer, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        }

        $dsnDb = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Automatically create and seed tables from database/pulsefit_campus.sql if `users` table does not exist yet
        $tblCheck = $pdo->query("SHOW TABLES LIKE 'users'");
        if (!$tblCheck->fetch()) {
            $sqlFile = __DIR__ . '/../database/pulsefit_campus.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $q) {
                    if ($q !== '') {
                        $pdo->exec($q);
                    }
                }
            }
        }

        return $pdo;
    } catch (PDOException $e) {
        die("<div style='font-family:sans-serif;max-width:680px;margin:40px auto;padding:24px;background:#1e293b;color:#f8fafc;border-radius:12px;border:1px solid #ef4444;'>
            <h2 style='margin-top:0;color:#f87171;'>MySQL Database Connection Error</h2>
            <p><strong>Error Detail:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p style='font-size:13px;color:#cbd5e1;'>Verify that the database <code>if0_43369623_pulsefit</code> has been created in your InfinityFree Control Panel &rarr; MySQL Databases.</p>
        </div>");
    }
}
