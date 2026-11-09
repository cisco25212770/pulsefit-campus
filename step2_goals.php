<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = requireLogin($pdo);
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentWeight = (float)($_POST['current_weight_kg'] ?? 74.5);
    $targetWeight  = (float)($_POST['target_weight_kg'] ?? 78.0);
    $heightCm      = (int)($_POST['height_cm'] ?? 176);
    $age           = (int)($_POST['age'] ?? 21);
    $objective     = trim($_POST['primary_objective'] ?? 'Lean Muscle Gain (+250 kcal)');
    $activity      = (float)($_POST['activity_level'] ?? 1.55);
    $calories      = (int)($_POST['daily_calories'] ?? 2450);
    $protein       = (int)($_POST['daily_protein_g'] ?? 165);
    $carbs         = (int)($_POST['daily_carbs_g'] ?? 285);
    $fat           = (int)($_POST['daily_fat_g'] ?? 72);
    $workoutsWk    = (int)($_POST['weekly_workouts'] ?? 4);
    $waterLiters   = (float)($_POST['daily_water_liters'] ?? 3.25);

    $upd = $pdo->prepare("UPDATE fitness_goals SET
        current_weight_kg = ?, target_weight_kg = ?, height_cm = ?, age = ?,
        primary_objective = ?, activity_level = ?, daily_calories = ?,
        daily_protein_g = ?, daily_carbs_g = ?, daily_fat_g = ?,
        weekly_workouts = ?, daily_water_liters = ?
        WHERE user_id = ?");
    $upd->execute([
        $currentWeight, $targetWeight, $heightCm, $age,
        $objective, $activity, $calories,
        $protein, $carbs, $fat,
        $workoutsWk, $waterLiters, $userId
    ]);

    setFlash("Step 2 fitness and nutrition targets saved to MySQL! Proceed to Step 3 to record your workout.");
    header("Location: step3_workouts.php");
    exit;
}

$goals = getUserGoals($pdo, $userId);
$activeStep = 2;
$pageTitle = 'Step 2: Set Fitness Goals — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <div class="lg:col-span-8 glass-card rounded-2xl p-6 space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-4">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Step 2 • Personal Targets</span>
        <h1 class="text-xl font-bold text-white mt-0.5">Set Personal Fitness & Nutrition Targets</h1>
      </div>
      <button type="button" id="autoCalcGoalsBtn" class="px-3.5 py-2 rounded-xl bg-sky-500/15 hover:bg-sky-500/25 text-sky-300 border border-sky-500/30 text-xs font-semibold transition">
        ⚡ Auto-Calculate BMR / TDEE Targets
      </button>
    </div>

    <form method="POST" action="step2_goals.php" class="space-y-5">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Current Weight (kg)</label>
          <input type="number" step="0.5" id="current_weight_kg" name="current_weight_kg" value="<?= htmlspecialchars($goals['current_weight_kg']) ?>" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Target Weight (kg)</label>
          <input type="number" step="0.5" id="target_weight_kg" name="target_weight_kg" value="<?= htmlspecialchars($goals['target_weight_kg']) ?>" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Height (cm)</label>
          <input type="number" id="height_cm" name="height_cm" value="<?= htmlspecialchars($goals['height_cm']) ?>" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Age (Years)</label>
          <input type="number" id="age" name="age" value="<?= htmlspecialchars($goals['age']) ?>" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Fitness Objective</label>
          <select id="primary_objective" name="primary_objective" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            <?php
            $objectives = [
                'Lean Muscle Gain (+250 kcal)',
                'Body Recomposition (Maintenance)',
                'Athletic Fat Loss (-400 kcal)',
                'Strength & Power Peaking (+350 kcal)'
            ];
            foreach ($objectives as $obj):
                $sel = ($goals['primary_objective'] === $obj) ? 'selected' : '';
            ?>
              <option value="<?= htmlspecialchars($obj) ?>" <?= $sel ?>><?= htmlspecialchars($obj) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Weekly Activity Multiplier</label>
          <select id="activity_level" name="activity_level" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            <option value="1.375" <?= (float)$goals['activity_level'] == 1.375 ? 'selected' : '' ?>>Lightly Active (1–2 workouts/week)</option>
            <option value="1.550" <?= (float)$goals['activity_level'] == 1.550 ? 'selected' : '' ?>>Moderately Active (3–5 workouts/week)</option>
            <option value="1.725" <?= (float)$goals['activity_level'] == 1.725 ? 'selected' : '' ?>>Very Active (6–7 sessions/week)</option>
          </select>
        </div>
      </div>

      <div class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-semibold text-emerald-400 mb-1">Daily Calorie Target (kcal)</label>
          <input type="number" id="daily_calories" name="daily_calories" value="<?= htmlspecialchars($goals['daily_calories']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-sky-400 mb-1">Daily Protein Target (g)</label>
          <input type="number" id="daily_protein_g" name="daily_protein_g" value="<?= htmlspecialchars($goals['daily_protein_g']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-amber-400 mb-1">Daily Carbs Target (g)</label>
          <input type="number" id="daily_carbs_g" name="daily_carbs_g" value="<?= htmlspecialchars($goals['daily_carbs_g']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-rose-400 mb-1">Daily Dietary Fat (g)</label>
          <input type="number" id="daily_fat_g" name="daily_fat_g" value="<?= htmlspecialchars($goals['daily_fat_g']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Weekly Workouts Goal</label>
          <input type="number" name="weekly_workouts" value="<?= htmlspecialchars($goals['weekly_workouts']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-teal-400 mb-1">Daily Water Goal (L)</label>
          <input type="number" step="0.25" name="daily_water_liters" value="<?= htmlspecialchars($goals['daily_water_liters']) ?>" required
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <a href="step1_auth.php" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">← Step 1: Account</a>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20">
          Save Targets & Continue to Step 3 →
        </button>
      </div>
    </form>
  </div>

  <!-- Right Column: Active MySQL Target Snapshot -->
  <div class="lg:col-span-4 glass-card rounded-2xl p-6 space-y-4">
    <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Saved Database Profile</span>
    <h2 class="text-lg font-bold text-white">Current Targets in MySQL</h2>
    <div class="space-y-3 text-xs">
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex justify-between">
        <span class="text-slate-400">Objective:</span>
        <span class="font-bold text-white"><?= htmlspecialchars($goals['primary_objective']) ?></span>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex justify-between">
        <span class="text-slate-400">Daily Calorie Target:</span>
        <span class="font-bold text-emerald-400"><?= number_format($goals['daily_calories']) ?> kcal</span>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex justify-between">
        <span class="text-slate-400">Daily Protein Target:</span>
        <span class="font-bold text-sky-400"><?= (int)$goals['daily_protein_g'] ?> g (<?= round($goals['daily_protein_g'] / max(1, $goals['current_weight_kg']), 2) ?> g/kg)</span>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex justify-between">
        <span class="text-slate-400">Carbs / Fat Split:</span>
        <span class="font-bold text-amber-300"><?= (int)$goals['daily_carbs_g'] ?>g C • <?= (int)$goals['daily_fat_g'] ?>g F</span>
      </div>
      <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 flex justify-between">
        <span class="text-slate-400">Body Weight Goal:</span>
        <span class="font-bold text-white"><?= $goals['current_weight_kg'] ?> kg → <?= $goals['target_weight_kg'] ?> kg</span>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
