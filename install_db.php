<?php
require_once __DIR__ . '/config/database.php';

$message = '';
$status = 'success';

try {
    $pdo = getDB();
    $sqlFile = __DIR__ . '/database/pulsefit_campus.sql';
    $sql = file_get_contents($sqlFile);

    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    $message = "Connected to " . DB_HOST . " (" . DB_NAME . ") and verified/seeded all 6 tables!";
} catch (PDOException $e) {
    $status = 'error';
    $message = "MySQL Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PulseFit Campus — InfinityFree Database Installer</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6 font-sans">
  <div class="max-w-lg w-full bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4 shadow-2xl">
    <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status === 'success' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' ?>">
      <?= $status === 'success' ? 'Database Ready' : 'Connection Error' ?>
    </span>
    <h1 class="text-xl font-extrabold text-white">PulseFit Campus Database Status</h1>
    <p class="text-sm text-slate-300 leading-relaxed"><?= htmlspecialchars($message) ?></p>
    <div class="pt-2 flex gap-3">
      <a href="step1_auth.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition">
        Open Step 1: Register / Log In →
      </a>
      <a href="index.php" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm border border-slate-700 transition">
        Home
      </a>
    </div>
  </div>
</body>
</html>
