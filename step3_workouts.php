<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = requireLogin($pdo);
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_workout') {
        $exerciseName = trim($_POST['exercise_name'] ?? 'Barbell Bench Press');
        $muscleGroup  = trim($_POST['muscle_group'] ?? 'Chest & Triceps');
        $sessionTime  = $_POST['session_time'] ?? '17:30';
        $sets         = max(1, (int)($_POST['sets'] ?? 4));
        $reps         = max(1, (int)($_POST['reps'] ?? 8));
        $weightKg     = max(0, (float)($_POST['weight_kg'] ?? 80));
        $notes        = trim($_POST['notes'] ?? '');

        $volumeKg = round($sets * $reps * $weightKg, 2);
        $estBurn  = (int)round($sets * 28 + $volumeKg * 0.015);

        $ins = $pdo->prepare("INSERT INTO workouts
            (user_id, workout_date, session_time, exercise_name, muscle_group, sets, reps, weight_kg, volume_kg, est_calories_burned, notes)
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$userId, $sessionTime, $exerciseName, $muscleGroup, $sets, $reps, $weightKg, $volumeKg, $estBurn, $notes]);

        setFlash("Logged $exerciseName ($sets × $reps @ {$weightKg}kg = {$volumeKg}kg volume) to MySQL!");
        header("Location: step3_workouts.php");
        exit;
    } elseif ($action === 'delete_workout') {
        $wId = (int)($_POST['workout_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM workouts WHERE id = ? AND user_id = ?");
        $del->execute([$wId, $userId]);
        setFlash("Removed workout entry.", "info");
        header("Location: step3_workouts.php");
        exit;
    }
}

$summary = getDailySummary($pdo, $userId);
$workouts = $summary['workouts'];

$activeStep = 3;
$pageTitle = 'Step 3: Record Workout — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <!-- Left: Workout Entry Form -->
  <div class="lg:col-span-5 glass-card rounded-2xl p-6 space-y-5">
    <div class="border-b border-slate-800 pb-4">
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Step 3 • Training Log</span>
      <h1 class="text-xl font-bold text-white mt-0.5">Record Workout (Exercises, Sets, Reps & Weight)</h1>
    </div>

    <form method="POST" action="step3_workouts.php" class="space-y-4">
      <input type="hidden" name="action" value="add_workout">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Quick Preset Exercise</label>
        <select id="exercise_preset" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white">
          <option value="Barbell Bench Press|Chest & Triceps|80">Barbell Bench Press (Chest & Triceps)</option>
          <option value="Back Squat (Barbell)|Legs & Glutes|100">Back Squat — Barbell (Legs & Glutes)</option>
          <option value="Romanian Deadlift|Hamstrings & Back|95">Romanian Deadlift (Hamstrings & Back)</option>
          <option value="Weighted Pull-Ups / Lat Pulldown|Back & Biceps|68">Lat Pulldown / Pull-Ups (Back & Biceps)</option>
          <option value="Seated Dumbbell Shoulder Press|Shoulders|24">Seated Dumbbell Shoulder Press (Shoulders)</option>
          <option value="Incline Dumbbell Press|Upper Chest|30">Incline Dumbbell Press (Upper Chest)</option>
          <option value="Bulgarian Split Squat|Quadriceps & Glutes|20">Bulgarian Split Squat (Quadriceps & Glutes)</option>
          <option value="custom">-- Type Custom Exercise Below --</option>
        </select>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Exercise Name</label>
          <input type="text" id="exercise_name" name="exercise_name" value="Barbell Bench Press" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Muscle Group</label>
          <input type="text" id="muscle_group" name="muscle_group" value="Chest & Triceps" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
      </div>

      <div class="grid grid-cols-4 gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Time</label>
          <input type="time" name="session_time" value="17:30" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-2.5 py-2 text-xs text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Sets</label>
          <input type="number" id="sets" name="sets" min="1" max="20" value="4" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Reps</label>
          <input type="number" id="reps" name="reps" min="1" max="100" value="8" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Weight (kg)</label>
          <input type="number" step="0.5" id="weight_kg" name="weight_kg" min="0" max="500" value="80" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Training Notes / RPE</label>
        <input type="text" name="notes" placeholder="e.g., RPE 8.5, controlled tempo"
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white">
      </div>

      <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between text-xs">
        <span class="text-slate-400">Volume Load Preview:</span>
        <span id="liveVolumePreview" class="font-extrabold text-amber-400">2,560 kg total (4 sets × 8 reps × 80 kg)</span>
      </div>

      <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20">
        + Save Workout Entry to Database
      </button>
    </form>
  </div>

  <!-- Right: Today's Logged Workouts Table -->
  <div class="lg:col-span-7 glass-card rounded-2xl p-6 flex flex-col justify-between space-y-5">
    <div class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-amber-400">MySQL Workouts Table</span>
          <h2 class="text-lg font-bold text-white mt-0.5">Today's Recorded Training Session</h2>
        </div>
        <div class="flex gap-2">
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
            Total Volume: <?= number_format($summary['total_volume']) ?> kg
          </span>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30">
            Est. Burn: <?= number_format($summary['est_burn']) ?> kcal
          </span>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl border border-slate-800">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-900/95 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
              <th class="py-3 px-3.5">Exercise & Muscle</th>
              <th class="py-3 px-3 text-center">Time</th>
              <th class="py-3 px-3 text-right">Sets × Reps</th>
              <th class="py-3 px-3 text-right">Weight</th>
              <th class="py-3 px-3 text-right">Volume</th>
              <th class="py-3 px-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/70 text-xs">
            <?php if (empty($workouts)): ?>
              <tr>
                <td colspan="6" class="py-8 text-center text-slate-400">No workouts logged today yet. Use the form on the left to add an exercise!</td>
              </tr>
            <?php else: ?>
              <?php foreach ($workouts as $w): ?>
                <tr class="hover:bg-slate-900/50">
                  <td class="py-3 px-3.5">
                    <div class="font-bold text-white"><?= htmlspecialchars($w['exercise_name']) ?></div>
                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($w['muscle_group']) ?><?= $w['notes'] ? ' • ' . htmlspecialchars($w['notes']) : '' ?></div>
                  </td>
                  <td class="py-3 px-3 text-center text-slate-300"><?= substr($w['session_time'], 0, 5) ?></td>
                  <td class="py-3 px-3 text-right font-semibold text-emerald-300"><?= (int)$w['sets'] ?> × <?= (int)$w['reps'] ?></td>
                  <td class="py-3 px-3 text-right font-semibold text-white"><?= (float)$w['weight_kg'] ?> kg</td>
                  <td class="py-3 px-3 text-right font-bold text-amber-400"><?= number_format((float)$w['volume_kg']) ?> kg</td>
                  <td class="py-3 px-3 text-center">
                    <form method="POST" action="step3_workouts.php" class="inline">
                      <input type="hidden" name="action" value="delete_workout">
                      <input type="hidden" name="workout_id" value="<?= (int)$w['id'] ?>">
                      <button type="submit" class="text-slate-400 hover:text-rose-400 px-2 py-1 rounded hover:bg-slate-800">✕</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
      <a href="step2_goals.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">← Step 2: Goals</a>
      <a href="step4_meals.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs sm:text-sm transition">
        Next Step 4: Record Meals →
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
