<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = requireLogin($pdo);
$userId = (int)$user['id'];

$catalogStmt = $pdo->query("SELECT * FROM food_catalog ORDER BY food_name ASC");
$catalogList = $catalogStmt->fetchAll();
$catalogByKey = [];
foreach ($catalogList as $item) {
    $catalogByKey[$item['food_key']] = $item;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_meal') {
        $foodKey      = $_POST['food_key'] ?? 'grilled_chicken';
        $mealCategory = trim($_POST['meal_category'] ?? 'Lunch');
        $mealTime     = $_POST['meal_time'] ?? '12:45';
        $grams        = max(5, (int)($_POST['portion_grams'] ?? 200));
        $desc         = trim($_POST['portion_desc'] ?? ($grams . 'g serving'));

        if ($foodKey === 'custom') {
            $foodName   = trim($_POST['custom_food_name'] ?? 'Custom Campus Meal');
            $calPer100  = max(10, (float)($_POST['custom_cal_100'] ?? 180));
            $protPer100 = max(0, (float)($_POST['custom_prot_100'] ?? 14));
            $carbPer100 = max(0, round(($calPer100 - $protPer100 * 4) / 6, 1));
            $fatPer100  = 6.0;
        } else {
            $ref = $catalogByKey[$foodKey] ?? reset($catalogList);
            $foodName   = $ref['food_name'];
            $calPer100  = (float)$ref['cal_per_100g'];
            $protPer100 = (float)$ref['protein_per_100g'];
            $carbPer100 = (float)$ref['carbs_per_100g'];
            $fatPer100  = (float)$ref['fat_per_100g'];
        }

        // System automatically computes calorie & protein estimates based on portion grams
        $factor   = $grams / 100.0;
        $estCal   = (int)round($calPer100 * $factor);
        $estProt  = round($protPer100 * $factor, 1);
        $estCarbs = round($carbPer100 * $factor, 1);
        $estFat   = round($fatPer100 * $factor, 1);

        $ins = $pdo->prepare("INSERT INTO meals
            (user_id, meal_date, meal_time, meal_category, food_name, portion_grams, portion_desc,
             cal_per_100g, protein_per_100g, carbs_per_100g, fat_per_100g,
             est_calories, est_protein_g, est_carbs_g, est_fat_g, is_verified)
            VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $ins->execute([
            $userId, $mealTime, $mealCategory, $foodName, $grams, $desc,
            $calPer100, $protPer100, $carbPer100, $fatPer100,
            $estCal, $estProt, $estCarbs, $estFat
        ]);

        setFlash("Recorded $foodName ({$grams}g) — System estimated {$estCal} kcal & {$estProt}g protein. Review in Step 5!");
        header("Location: step4_meals.php");
        exit;
    } elseif ($action === 'delete_meal') {
        $mId = (int)($_POST['meal_id'] ?? 0);
        $del = $pdo->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
        $del->execute([$mId, $userId]);
        setFlash("Deleted meal entry.", "info");
        header("Location: step4_meals.php");
        exit;
    }
}

$summary = getDailySummary($pdo, $userId);
$meals = $summary['meals'];

$activeStep = 4;
$pageTitle = 'Step 4: Record Meals — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <!-- Left: Food Intake Logger -->
  <div class="lg:col-span-5 glass-card rounded-2xl p-6 space-y-5">
    <div class="border-b border-slate-800 pb-4">
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Step 4 • Daily Food Intake</span>
      <h1 class="text-xl font-bold text-white mt-0.5">Record Meals, Portion Sizes & Meal Times</h1>
      <p class="text-xs text-slate-400 mt-1">Select a food item, enter your portion in grams, and log the meal time.</p>
    </div>

    <form method="POST" action="step4_meals.php" class="space-y-4">
      <input type="hidden" name="action" value="add_meal">

      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Food Item (From MySQL <code>food_catalog</code>)</label>
        <select id="food_select" name="food_key" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
          <?php foreach ($catalogList as $item): ?>
            <option value="<?= htmlspecialchars($item['food_key']) ?>"
                    data-grams="<?= (int)$item['default_grams'] ?>"
                    data-desc="<?= htmlspecialchars($item['default_desc']) ?>">
              <?= htmlspecialchars($item['food_name']) ?> (<?= (int)$item['cal_per_100g'] ?> kcal / <?= $item['protein_per_100g'] ?>g P per 100g)
            </option>
          <?php endforeach; ?>
          <option value="custom">-- Enter Custom Food Item --</option>
        </select>
      </div>

      <div id="customFoodFields" class="hidden p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Custom Food Name</label>
          <input type="text" name="custom_food_name" placeholder="e.g., Campus Chicken Burrito Bowl"
            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Calories per 100g</label>
            <input type="number" name="custom_cal_100" value="185" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-1.5 text-xs text-white">
          </div>
          <div>
            <label class="block text-[11px] text-slate-400 mb-1">Protein (g) per 100g</label>
            <input type="number" step="0.5" name="custom_prot_100" value="14.5" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-1.5 text-xs text-white">
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Meal Category</label>
          <select name="meal_category" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
            <option value="Breakfast">Breakfast</option>
            <option value="Lunch" selected>Lunch</option>
            <option value="Pre/Post-Workout">Pre/Post-Workout</option>
            <option value="Dinner">Dinner</option>
            <option value="Evening Snack">Evening Snack</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Meal Time</label>
          <input type="time" name="meal_time" value="12:45" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Portion Size (grams)</label>
          <input type="number" id="portion_grams" name="portion_grams" min="10" max="1500" step="5" value="190" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm font-bold text-white">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Portion Label</label>
          <input type="text" id="portion_desc" name="portion_desc" value="1 standard serving (190g)" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
        </div>
      </div>

      <div class="flex items-center justify-between gap-2 pt-1">
        <span class="text-xs text-slate-400">Quick Portion Presets:</span>
        <div class="flex gap-1.5">
          <button type="button" data-quick-grams="100" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700">100g</button>
          <button type="button" data-quick-grams="180" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700">180g</button>
          <button type="button" data-quick-grams="250" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 border border-slate-700">250g</button>
        </div>
      </div>

      <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20">
        + Record Meal & Calculate Estimate
      </button>
    </form>
  </div>

  <!-- Right: Recorded Meals List -->
  <div class="lg:col-span-7 glass-card rounded-2xl p-6 flex flex-col justify-between space-y-5">
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <span class="text-xs font-bold uppercase tracking-wider text-sky-400">MySQL Meals Log</span>
          <h2 class="text-lg font-bold text-white mt-0.5">Today's Recorded Food Intake</h2>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
          <?= count($meals) ?> Meals Logged
        </span>
      </div>

      <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1">
        <?php if (empty($meals)): ?>
          <div class="p-8 rounded-xl bg-slate-900/60 border border-slate-800 text-center text-xs text-slate-400">
            No meals recorded today yet. Use the form on the left to log your first meal!
          </div>
        <?php else: ?>
          <?php foreach ($meals as $m): ?>
            <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex flex-wrap items-center justify-between gap-3">
              <div class="flex items-center gap-3">
                <div class="px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-center">
                  <div class="text-[10px] font-bold uppercase text-emerald-400"><?= htmlspecialchars($m['meal_category']) ?></div>
                  <div class="text-xs font-bold text-white"><?= substr($m['meal_time'], 0, 5) ?></div>
                </div>
                <div>
                  <div class="font-bold text-sm text-white"><?= htmlspecialchars($m['food_name']) ?></div>
                  <div class="text-xs text-slate-400">Portion: <strong class="text-slate-200"><?= (int)$m['portion_grams'] ?>g</strong> (<?= htmlspecialchars($m['portion_desc']) ?>)</div>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <div class="text-right">
                  <div class="text-xs font-bold text-emerald-400"><?= (int)$m['est_calories'] ?> kcal</div>
                  <div class="text-[11px] font-semibold text-sky-400"><?= (float)$m['est_protein_g'] ?>g protein</div>
                </div>
                <form method="POST" action="step4_meals.php">
                  <input type="hidden" name="action" value="delete_meal">
                  <input type="hidden" name="meal_id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="text-slate-400 hover:text-rose-400 p-1.5 rounded hover:bg-slate-800">✕</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
      <a href="step3_workouts.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">← Step 3: Workouts</a>
      <a href="step5_estimates.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs sm:text-sm transition">
        Next Step 5: Review Calorie & Protein Estimates →
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
