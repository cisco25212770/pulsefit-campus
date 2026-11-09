<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();
$user = requireLogin($pdo);
$userId = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_portion') {
        $mealId = (int)($_POST['meal_id'] ?? 0);
        $grams  = max(5, (int)($_POST['portion_grams'] ?? 100));

        $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ? AND user_id = ?");
        $stmt->execute([$mealId, $userId]);
        $meal = $stmt->fetch();

        if ($meal) {
            $factor   = $grams / 100.0;
            $estCal   = (int)round($meal['cal_per_100g'] * $factor);
            $estProt  = round($meal['protein_per_100g'] * $factor, 1);
            $estCarbs = round($meal['carbs_per_100g'] * $factor, 1);
            $estFat   = round($meal['fat_per_100g'] * $factor, 1);

            $upd = $pdo->prepare("UPDATE meals SET portion_grams = ?, portion_desc = ?, est_calories = ?, est_protein_g = ?, est_carbs_g = ?, est_fat_g = ?, is_verified = 1 WHERE id = ? AND user_id = ?");
            $upd->execute([$grams, "{$grams}g reviewed portion", $estCal, $estProt, $estCarbs, $estFat, $mealId, $userId]);
            setFlash("Updated portion to {$grams}g → Re-estimated {$estCal} kcal & {$estProt}g protein (Verified)!");
        }
        header("Location: step5_estimates.php");
        exit;
    } elseif ($action === 'toggle_verify') {
        $mealId = (int)($_POST['meal_id'] ?? 0);
        $upd = $pdo->prepare("UPDATE meals SET is_verified = IF(is_verified = 1, 0, 1) WHERE id = ? AND user_id = ?");
        $upd->execute([$mealId, $userId]);
        setFlash("Updated meal estimate verification status.");
        header("Location: step5_estimates.php");
        exit;
    } elseif ($action === 'verify_all') {
        $upd = $pdo->prepare("UPDATE meals SET is_verified = 1 WHERE user_id = ? AND meal_date = CURDATE()");
        $upd->execute([$userId]);
        setFlash("All daily calorie and protein estimates verified! Opening Step 6 Student Dashboard.");
        header("Location: step6_dashboard.php");
        exit;
    }
}

$goals = getUserGoals($pdo, $userId);
$summary = getDailySummary($pdo, $userId);
$meals = $summary['meals'];

$activeStep = 5;
$pageTitle = 'Step 5: Review Estimates — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<div class="glass-card rounded-2xl p-6 space-y-6">
  <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Step 5 • Automated Nutrition Processing</span>
      <h1 class="text-xl font-bold text-white mt-0.5">Review System-Calculated Calorie & Protein Estimates</h1>
      <p class="text-xs text-slate-400 mt-1">
        The system automatically processes each food entry from Step 4 using nutrient-density formulas (<code>Portion Grams ÷ 100 × Base Macro Density</code>). Adjust any portion weight below or approve all estimates.
      </p>
    </div>
    <form method="POST" action="step5_estimates.php">
      <input type="hidden" name="action" value="verify_all">
      <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs sm:text-sm transition shadow-lg shadow-emerald-500/20">
        ✓ Approve All Estimates & Go to Dashboard →
      </button>
    </form>
  </div>

  <!-- Summary KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
      <div class="text-xs text-slate-400">Estimated Total Calories</div>
      <div class="text-2xl font-extrabold text-emerald-400 mt-1"><?= number_format($summary['calories']) ?> kcal</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Target: <?= number_format($goals['daily_calories']) ?> kcal</div>
    </div>
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
      <div class="text-xs text-slate-400">Estimated Total Protein</div>
      <div class="text-2xl font-extrabold text-sky-400 mt-1"><?= $summary['protein'] ?> g</div>
      <div class="text-[11px] text-slate-400 mt-0.5">Target: <?= (int)$goals['daily_protein_g'] ?> g</div>
    </div>
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
      <div class="text-xs text-slate-400">Estimated Carbs & Fats</div>
      <div class="text-xl font-extrabold text-amber-400 mt-1"><?= $summary['carbs'] ?>g C • <span class="text-rose-400"><?= $summary['fat'] ?>g F</span></div>
      <div class="text-[11px] text-slate-400 mt-0.5">Auto-computed from portions</div>
    </div>
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800">
      <div class="text-xs text-slate-400">Verification Status</div>
      <div class="text-2xl font-extrabold text-white mt-1"><?= $summary['verified_count'] ?> / <?= $summary['meal_count'] ?></div>
      <div class="text-[11px] text-emerald-400 mt-0.5">Meals Reviewed by Student</div>
    </div>
  </div>

  <!-- Detailed Estimates Table -->
  <div class="overflow-x-auto rounded-xl border border-slate-800">
    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-900/95 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
          <th class="py-3.5 px-4">Meal & Time</th>
          <th class="py-3.5 px-3">Adjust Portion (g)</th>
          <th class="py-3.5 px-3">System Formula Breakdown</th>
          <th class="py-3.5 px-3 text-right">Est. Calories</th>
          <th class="py-3.5 px-3 text-right">Est. Protein</th>
          <th class="py-3.5 px-3 text-right">Carbs / Fat</th>
          <th class="py-3.5 px-4 text-center">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-800/70 text-xs">
        <?php if (empty($meals)): ?>
          <tr>
            <td colspan="7" class="py-8 text-center text-slate-400">No meals recorded today. Add meals in Step 4 first!</td>
          </tr>
        <?php else: ?>
          <?php foreach ($meals as $m):
            $factor = round($m['portion_grams'] / 100, 2);
            $verified = ((int)$m['is_verified'] === 1);
          ?>
            <tr class="hover:bg-slate-900/50">
              <td class="py-3.5 px-4">
                <div class="font-bold text-white"><?= htmlspecialchars($m['food_name']) ?></div>
                <div class="text-[11px] text-slate-400"><?= htmlspecialchars($m['meal_category']) ?> • <?= substr($m['meal_time'], 0, 5) ?></div>
              </td>
              <td class="py-3.5 px-3">
                <form method="POST" action="step5_estimates.php" class="flex items-center gap-1.5">
                  <input type="hidden" name="action" value="update_portion">
                  <input type="hidden" name="meal_id" value="<?= (int)$m['id'] ?>">
                  <input type="number" name="portion_grams" value="<?= (int)$m['portion_grams'] ?>" min="10" max="1500" step="5"
                    class="w-20 rounded-lg bg-slate-950 border border-slate-700 px-2 py-1 text-xs font-bold text-white">
                  <button type="submit" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-emerald-300 border border-slate-700">Recalc</button>
                </form>
              </td>
              <td class="py-3.5 px-3 text-[11px] text-slate-300">
                <?= $factor ?> × (<?= (float)$m['cal_per_100g'] ?> kcal & <?= (float)$m['protein_per_100g'] ?>g P / 100g)
              </td>
              <td class="py-3.5 px-3 text-right font-extrabold text-emerald-400"><?= (int)$m['est_calories'] ?> kcal</td>
              <td class="py-3.5 px-3 text-right font-extrabold text-sky-400"><?= (float)$m['est_protein_g'] ?> g</td>
              <td class="py-3.5 px-3 text-right text-slate-300"><?= (float)$m['est_carbs_g'] ?>g C / <?= (float)$m['est_fat_g'] ?>g F</td>
              <td class="py-3.5 px-4 text-center">
                <form method="POST" action="step5_estimates.php">
                  <input type="hidden" name="action" value="toggle_verify">
                  <input type="hidden" name="meal_id" value="<?= (int)$m['id'] ?>">
                  <button type="submit" class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $verified ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40' ?>">
                    <?= $verified ? '✓ Verified' : 'Needs Review' ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="flex items-center justify-between pt-2">
    <a href="step4_meals.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold">← Step 4: Record Meals</a>
    <a href="step6_dashboard.php" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs sm:text-sm transition">
      Final Step 6: Check Student Dashboard →
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
