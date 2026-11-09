<?php
require_once __DIR__ . '/functions.php';
$pdo = getDB();

// Check if secret code "mikedev" was submitted via PHP form or query
if (isset($_REQUEST['secret_code'])) {
    if (strtolower(trim($_REQUEST['secret_code'])) === 'mikedev') {
        $_SESSION['mikedev_unlocked'] = true;
    }
}

$currentUser = getCurrentUser($pdo);
$activeStep = $activeStep ?? 1;
$pageTitle = $pageTitle ?? 'PulseFit Campus — Student Fitness & Nutrition System';

$headerGoals = $currentUser ? getUserGoals($pdo, (int)$currentUser['id']) : null;
$headerSummary = $currentUser ? getDailySummary($pdo, (int)$currentUser['id']) : null;
$flash = getFlash();
$isSecretUnlocked = !empty($_SESSION['mikedev_unlocked']);

$stepsNav = [
    1 => ['url' => 'step1_auth.php',      'title' => 'Register / Log In', 'sub' => 'Student Account'],
    2 => ['url' => 'step2_goals.php',     'title' => 'Set Fitness Goals', 'sub' => 'Targets & Macros'],
    3 => ['url' => 'step3_workouts.php',  'title' => 'Record Workout',    'sub' => 'Sets, Reps & Weight'],
    4 => ['url' => 'step4_meals.php',     'title' => 'Record Meals',      'sub' => 'Food & Portions'],
    5 => ['url' => 'step5_estimates.php', 'title' => 'Review Estimates',  'sub' => 'Auto-Calc Macros'],
    6 => ['url' => 'step6_dashboard.php', 'title' => 'Check Dashboard',   'sub' => 'Daily & Long-Term'],
];
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen flex flex-col bg-slate-950 text-slate-100 antialiased">

  <!-- Top Navigation Bar -->
  <header class="sticky top-0 z-40 bg-slate-900/95 backdrop-blur-md border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
      <a href="index.php" class="flex items-center gap-3 group">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
          <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
          </svg>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="font-extrabold text-lg tracking-tight text-white group-hover:text-emerald-400 transition">PulseFit</span>
            <span class="text-xs font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">XAMPP PHP + MySQL</span>
          </div>
          <p class="text-xs text-slate-400 hidden sm:block">6-Step Student Fitness, Workout & Automated Nutrition System</p>
        </div>
      </a>

      <div class="flex items-center gap-2.5 flex-wrap">
        <!-- Secret Code Input Box ("mikedev") -->
        <form id="secretCodeForm" method="GET" action="" class="flex items-center gap-1.5 bg-slate-950/90 border border-slate-700/80 rounded-xl px-2.5 py-1">
          <span class="text-[11px] font-semibold text-slate-400">Secret Code:</span>
          <input type="text" id="secretCodeInput" name="secret_code" placeholder="Enter code..."
            autocomplete="off"
            class="w-24 sm:w-28 bg-transparent text-xs text-emerald-300 font-mono focus:outline-none placeholder:text-slate-600">
          <button type="submit" class="px-2 py-0.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 text-[11px] font-bold border border-emerald-500/30 transition">
            Unlock
          </button>
        </form>

        <?php if ($currentUser && $headerGoals && $headerSummary): ?>
          <div class="hidden xl:flex items-center gap-3 bg-slate-800/80 border border-slate-700/70 rounded-xl px-3.5 py-1.5 text-xs">
            <div>
              <span class="text-slate-400">Calories:</span>
              <span class="font-bold text-emerald-400 ml-1"><?= number_format($headerSummary['calories']) ?> / <?= number_format($headerGoals['daily_calories']) ?> kcal</span>
            </div>
            <span class="text-slate-600">|</span>
            <div>
              <span class="text-slate-400">Protein:</span>
              <span class="font-bold text-sky-400 ml-1"><?= $headerSummary['protein'] ?> / <?= $headerGoals['daily_protein_g'] ?> g</span>
            </div>
          </div>

          <div class="flex items-center gap-2.5 bg-slate-800/90 border border-slate-700 rounded-xl px-3 py-1.5">
            <div class="w-7 h-7 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 flex items-center justify-center font-bold text-xs">
              <?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
            </div>
            <div class="text-left">
              <p class="text-xs font-semibold text-white leading-tight"><?= htmlspecialchars($currentUser['full_name']) ?></p>
              <p class="text-[10px] text-emerald-400 leading-tight"><?= htmlspecialchars($currentUser['student_id']) ?></p>
            </div>
            <a href="logout.php" class="ml-1 text-xs text-rose-300 hover:text-white px-2 py-1 rounded-lg hover:bg-rose-500/20 transition">Log Out</a>
          </div>
        <?php else: ?>
          <a href="step1_auth.php" class="px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition">
            Sign In / Register (Step 1)
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Secret Code "mikedev" Google Drive Link Banner (Hidden until "mikedev" is entered) -->
    <div id="secretDriveBanner" class="<?= $isSecretUnlocked ? '' : 'hidden' ?> bg-gradient-to-r from-emerald-950 via-teal-900/90 to-slate-900 border-t border-emerald-500/50 px-4 py-3">
      <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase bg-emerald-400 text-slate-950">
            🔓 Secret Code "mikedev" Unlocked
          </span>
          <span class="text-xs sm:text-sm font-semibold text-emerald-200">
            Developer Google Drive Resource Link:
          </span>
          <a id="secretDriveLink"
             href="https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link"
             target="_blank"
             rel="noopener noreferrer"
             class="text-xs sm:text-sm font-bold text-white underline decoration-emerald-400 hover:text-emerald-300 break-all">
            https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link
          </a>
        </div>
        <a href="https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link"
           target="_blank"
           rel="noopener noreferrer"
           class="px-3.5 py-1.5 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-extrabold text-xs transition shadow-lg">
          Open Google Drive File ↗
        </a>
      </div>
    </div>

    <!-- 6-Step Workflow Bar -->
    <div class="bg-slate-950/90 border-t border-slate-800/80">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 py-2.5">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
          <?php foreach ($stepsNav as $num => $stepInfo):
            $isActive = ($activeStep === $num);
          ?>
            <a href="<?= $stepInfo['url'] ?>"
               class="flex items-center gap-2.5 px-3 py-2 rounded-xl border text-left transition-all text-xs font-semibold <?= $isActive ? 'step-btn-active' : 'bg-slate-900/90 hover:bg-slate-800 text-slate-300 border-slate-800' ?>">
              <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 <?= $isActive ? 'bg-slate-950/30 text-white' : 'bg-slate-800 text-emerald-400' ?>">
                <?= $num ?>
              </span>
              <div class="truncate">
                <div class="font-bold truncate"><?= htmlspecialchars($stepInfo['title']) ?></div>
                <div class="text-[10px] opacity-80 truncate"><?= htmlspecialchars($stepInfo['sub']) ?></div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 py-6 space-y-6">
    <?php if ($flash): ?>
      <div class="p-4 rounded-xl border text-xs sm:text-sm font-semibold flex items-center justify-between <?= $flash['type'] === 'error' ? 'bg-rose-500/15 border-rose-500/40 text-rose-200' : 'bg-emerald-500/15 border-emerald-500/40 text-emerald-200' ?>">
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>
