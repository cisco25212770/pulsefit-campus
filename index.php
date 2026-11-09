<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = getCurrentUser($pdo);

$activeStep = $user ? 6 : 1;
$pageTitle = 'PulseFit Campus — 6-Step Student Fitness & Nutrition System';
require_once __DIR__ . '/includes/header.php';
?>

<section class="glass-card rounded-2xl p-6 sm:p-8 space-y-6">
  <div class="max-w-3xl space-y-3">
    <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
      XAMPP PHP + MySQL Full-Stack Web Application
    </span>
    <h1 class="text-2xl sm:text-4xl font-extrabold text-white tracking-tight">
      PulseFit Campus: 6-Step Student Fitness & Automated Nutrition Tracker
    </h1>
    <p class="text-sm sm:text-base text-slate-300 leading-relaxed">
      Welcome! This complete PHP & MySQL application implements all 6 steps of the student fitness workflow backed by the <code>pulsefit_campus</code> relational database. Enter the secret code <code>mikedev</code> in the top bar at any time to unlock the developer Google Drive link.
    </p>
    <div class="pt-2 flex flex-wrap gap-3">
      <a href="step1_auth.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition">
        Go to Step 1: Register or Log In (with Network Payload Inspector) →
      </a>
      <?php if ($user): ?>
        <a href="step6_dashboard.php" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-semibold text-sm border border-slate-700 transition">
          Open Step 6: Student Dashboard
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
