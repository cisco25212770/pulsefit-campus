<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = requireLogin($pdo);
$userId = (int)$user['id'];

$goals = getUserGoals($pdo, $userId);
$summary = getDailySummary($pdo, $userId);

// Fetch 6-week long-term weight & workout volume logs from MySQL
$wLogsStmt = $pdo->prepare("SELECT * FROM weight_logs WHERE user_id = ? ORDER BY id ASC");
$wLogsStmt->execute([$userId]);
$weightLogs = $wLogsStmt->fetchAll();

$weekLabels = [];
$weightSeries = [];
$volumeSeries = [];
foreach ($weightLogs as $wl) {
    $weekLabels[] = $wl['week_label'];
    $weightSeries[] = (float)$wl['weight_kg'];
    $volumeSeries[] = (int)$wl['weekly_volume_kg'];
}
if (empty($weekLabels)) {
    $weekLabels = ['Week 1', 'Week 2', 'Week 3', 'Today'];
    $weightSeries = [(float)$goals['current_weight_kg'], (float)$goals['current_weight_kg'], (float)$goals['current_weight_kg'], (float)$goals['current_weight_kg']];
    $volumeSeries = [4500, 5000, 5200, (int)$summary['total_volume']];
} else {
    // Update last point with today's live weight and volume
    $lastIdx = count($weekLabels) - 1;
    $weightSeries[$lastIdx] = (float)$goals['current_weight_kg'];
    $volumeSeries[$lastIdx] = max($volumeSeries[$lastIdx], (int)$summary['total_volume']);
}

$calPct = min(100, (int)round(($summary['calories'] / max(1, (int)$goals['daily_calories'])) * 100));
$protPct = min(100, (int)round(($summary['protein'] / max(1, (int)$goals['daily_protein_g'])) * 100));
$workoutPct = min(100, (int)round(($summary['workout_count'] / max(1, (int)$goals['weekly_workouts'])) * 100));

$activeStep = 6;
$pageTitle = 'Step 6: Check Dashboard — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Top KPI Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="glass-card rounded-2xl p-5 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Daily Calorie Intake</span>
      <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-500/15 text-emerald-300"><?= $calPct ?>%</span>
    </div>
    <div class="text-2xl font-extrabold text-white">
      <?= number_format($summary['calories']) ?> <span class="text-sm font-normal text-slate-400">/ <?= number_format($goals['daily_calories']) ?> kcal</span>
    </div>
    <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
      <div class="bg-emerald-500 h-full rounded-full" style="width: <?= $calPct ?>%"></div>
    </div>
    <p class="text-xs text-slate-400">Net after workout burn (<?= $summary['est_burn'] ?> kcal): <?= number_format($summary['calories'] - $summary['est_burn']) ?> kcal</p>
  </div>

  <div class="glass-card rounded-2xl p-5 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Daily Protein Target</span>
      <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-500/15 text-sky-300"><?= $protPct ?>%</span>
    </div>
    <div class="text-2xl font-extrabold text-white">
      <?= $summary['protein'] ?> <span class="text-sm font-normal text-slate-400">/ <?= (int)$goals['daily_protein_g'] ?> g</span>
    </div>
    <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
      <div class="bg-sky-400 h-full rounded-full" style="width: <?= $protPct ?>%"></div>
    </div>
    <p class="text-xs text-slate-400">Carbs: <?= $summary['carbs'] ?>g • Fat: <?= $summary['fat'] ?>g</p>
  </div>

  <div class="glass-card rounded-2xl p-5 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Today's Workout Volume</span>
      <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-500/15 text-amber-300"><?= $summary['workout_count'] ?> Exercises</span>
    </div>
    <div class="text-2xl font-extrabold text-white"><?= number_format($summary['total_volume']) ?> kg</div>
    <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
      <div class="bg-amber-400 h-full rounded-full" style="width: <?= $workoutPct ?>%"></div>
    </div>
    <p class="text-xs text-slate-400"><?= $summary['total_sets'] ?> working sets completed today</p>
  </div>

  <div class="glass-card rounded-2xl p-5 space-y-3">
    <div class="flex items-center justify-between">
      <span class="text-xs font-bold uppercase tracking-wider text-teal-400">Weight & Goal Status</span>
      <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-teal-500/15 text-teal-300">Active</span>
    </div>
    <div class="text-2xl font-extrabold text-white">
      <?= $goals['current_weight_kg'] ?> <span class="text-sm font-normal text-slate-400">→ <?= $goals['target_weight_kg'] ?> kg</span>
    </div>
    <p class="text-xs text-emerald-400 font-medium"><?= htmlspecialchars($goals['primary_objective']) ?></p>
    <p class="text-[11px] text-slate-400">Hydration Goal: <?= $goals['daily_water_liters'] ?> Liters/day</p>
  </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <div class="lg:col-span-6 glass-card rounded-2xl p-6 space-y-4">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Daily Target vs. Actual</span>
      <h2 class="text-lg font-bold text-white">Macro & Calorie Adherence Overview</h2>
    </div>
    <div class="relative h-72 w-full">
      <canvas id="macroComparisonChart"></canvas>
    </div>
  </div>

  <div class="lg:col-span-6 glass-card rounded-2xl p-6 space-y-4">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Long-Term Progress Monitoring</span>
      <h2 class="text-lg font-bold text-white">6-Week Body Weight & Training Volume Trend</h2>
    </div>
    <div class="relative h-72 w-full">
      <canvas id="longTermTrendChart"></canvas>
    </div>
  </div>
</div>

<!-- Bottom Audit & Quick Links -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <div class="lg:col-span-7 glass-card rounded-2xl p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-bold text-white">Today's Verified Meals & Workout Log</h3>
      <div class="flex gap-2">
        <a href="step3_workouts.php" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 border border-slate-700">+ Log Workout</a>
        <a href="step4_meals.php" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 border border-slate-700">+ Log Meal</a>
      </div>
    </div>

    <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
      <?php foreach ($summary['meals'] as $m): ?>
        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
          <div>
            <span class="font-bold text-emerald-400">[Meal • <?= substr($m['meal_time'], 0, 5) ?>]</span>
            <span class="font-bold text-white ml-1"><?= htmlspecialchars($m['food_name']) ?></span>
            <span class="text-slate-400 ml-1">(<?= (int)$m['portion_grams'] ?>g)</span>
          </div>
          <div class="font-bold text-emerald-300"><?= (int)$m['est_calories'] ?> kcal • <?= (float)$m['est_protein_g'] ?>g P</div>
        </div>
      <?php endforeach; ?>

      <?php foreach ($summary['workouts'] as $w): ?>
        <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between text-xs">
          <div>
            <span class="font-bold text-amber-400">[Workout • <?= substr($w['session_time'], 0, 5) ?>]</span>
            <span class="font-bold text-white ml-1"><?= htmlspecialchars($w['exercise_name']) ?></span>
            <span class="text-slate-400 ml-1">(<?= (int)$w['sets'] ?>×<?= (int)$w['reps'] ?> @ <?= (float)$w['weight_kg'] ?>kg)</span>
          </div>
          <div class="font-bold text-amber-300"><?= number_format((float)$w['volume_kg']) ?> kg vol</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="lg:col-span-5 glass-card rounded-2xl p-6 space-y-4">
    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Automated Student Coach</span>
    <h3 class="text-lg font-bold text-white">Daily Summary & Recommendations</h3>
    <div class="space-y-3 text-xs text-slate-300">
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="font-bold text-emerald-400 mb-1">Calorie Budget Check</div>
        <?php $calRem = (int)$goals['daily_calories'] - $summary['calories']; ?>
        <?= $calRem >= 0
            ? "You have <strong>" . number_format($calRem) . " kcal</strong> remaining today to reach your " . number_format($goals['daily_calories']) . " kcal target."
            : "You are currently <strong>" . number_format(abs($calRem)) . " kcal</strong> above your daily target." ?>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="font-bold text-sky-400 mb-1">Protein Synthesis Status</div>
        <?php $protRem = round($goals['daily_protein_g'] - $summary['protein'], 1); ?>
        <?= $protRem > 0
            ? "You need <strong>{$protRem}g more protein</strong> today to hit your {$goals['daily_protein_g']}g goal."
            : "Awesome work—you have reached your daily protein target ({$summary['protein']}g / {$goals['daily_protein_g']}g)!" ?>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
        <div class="font-bold text-amber-400 mb-1">Estimate Verification</div>
        <?= $summary['verified_count'] ?> of <?= $summary['meal_count'] ?> recorded meals have been verified in <a href="step5_estimates.php" class="text-emerald-400 underline">Step 5</a>.
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctxMacro = document.getElementById('macroComparisonChart');
  if (ctxMacro && typeof Chart !== 'undefined') {
    new Chart(ctxMacro, {
      type: 'bar',
      data: {
        labels: ['Protein (g)', 'Carbs (g)', 'Fat (g)'],
        datasets: [
          {
            label: 'Today Actual',
            data: [<?= (float)$summary['protein'] ?>, <?= (float)$summary['carbs'] ?>, <?= (float)$summary['fat'] ?>],
            backgroundColor: 'rgba(16, 185, 129, 0.75)',
            borderColor: '#10b981',
            borderWidth: 1.5,
            borderRadius: 8
          },
          {
            label: 'Step 2 Target',
            data: [<?= (int)$goals['daily_protein_g'] ?>, <?= (int)$goals['daily_carbs_g'] ?>, <?= (int)$goals['daily_fat_g'] ?>],
            backgroundColor: 'rgba(56, 189, 248, 0.35)',
            borderColor: '#38bdf8',
            borderWidth: 1.5,
            borderRadius: 8
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#cbd5e1' } } },
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.3)' } },
          y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.3)' } }
        }
      }
    });
  }

  const ctxTrend = document.getElementById('longTermTrendChart');
  if (ctxTrend && typeof Chart !== 'undefined') {
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: <?= json_encode($weekLabels) ?>,
        datasets: [
          {
            label: 'Workout Volume (kg)',
            data: <?= json_encode($volumeSeries) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.15)',
            yAxisID: 'y',
            fill: true,
            tension: 0.35
          },
          {
            label: 'Body Weight (kg)',
            data: <?= json_encode($weightSeries) ?>,
            borderColor: '#38bdf8',
            backgroundColor: '#38bdf8',
            yAxisID: 'y1',
            tension: 0.35
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#cbd5e1' } } },
        scales: {
          x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(51,65,85,0.3)' } },
          y: { type: 'linear', position: 'left', ticks: { color: '#10b981' }, grid: { color: 'rgba(51,65,85,0.3)' } },
          y1: { type: 'linear', position: 'right', ticks: { color: '#38bdf8' }, grid: { drawOnChartArea: false } }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
