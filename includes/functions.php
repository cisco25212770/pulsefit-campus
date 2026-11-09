<?php
require_once __DIR__ . '/../config/database.php';

define('SECRET_CODE_VALUE', 'mikedev');
define('SECRET_DRIVE_URL', 'https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link');

function setFlash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function getCurrentUser(PDO $pdo): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function requireLogin(PDO $pdo): array {
    $user = getCurrentUser($pdo);
    if (!$user) {
        setFlash("Please sign in or register your student account in Step 1 first.", "info");
        header("Location: step1_auth.php");
        exit;
    }
    return $user;
}

function getUserGoals(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("SELECT * FROM fitness_goals WHERE user_id = ?");
    $stmt->execute([$userId]);
    $goals = $stmt->fetch();

    if (!$goals) {
        $ins = $pdo->prepare("INSERT INTO fitness_goals 
            (user_id, current_weight_kg, target_weight_kg, height_cm, age, primary_objective, activity_level, daily_calories, daily_protein_g, daily_carbs_g, daily_fat_g, weekly_workouts, daily_water_liters)
            VALUES (?, 74.5, 78.0, 176, 21, 'Lean Muscle Gain (+250 kcal)', 1.550, 2450, 165, 285, 72, 4, 3.25)");
        $ins->execute([$userId]);
        return getUserGoals($pdo, $userId);
    }
    return $goals;
}

function getDailySummary(PDO $pdo, int $userId): array {
    $stmtMeals = $pdo->prepare("SELECT * FROM meals WHERE user_id = ? AND meal_date = CURDATE() ORDER BY meal_time ASC, id ASC");
    $stmtMeals->execute([$userId]);
    $meals = $stmtMeals->fetchAll();

    $totalCal = 0;
    $totalProt = 0.0;
    $totalCarbs = 0.0;
    $totalFat = 0.0;
    $verifiedCount = 0;

    foreach ($meals as $m) {
        $totalCal += (int)$m['est_calories'];
        $totalProt += (float)$m['est_protein_g'];
        $totalCarbs += (float)$m['est_carbs_g'];
        $totalFat += (float)$m['est_fat_g'];
        if ((int)$m['is_verified'] === 1) {
            $verifiedCount++;
        }
    }

    $stmtWorkouts = $pdo->prepare("SELECT * FROM workouts WHERE user_id = ? AND workout_date = CURDATE() ORDER BY session_time ASC, id ASC");
    $stmtWorkouts->execute([$userId]);
    $workouts = $stmtWorkouts->fetchAll();

    $totalVolume = 0.0;
    $totalSets = 0;
    $totalBurn = 0;

    foreach ($workouts as $w) {
        $totalVolume += (float)$w['volume_kg'];
        $totalSets += (int)$w['sets'];
        $totalBurn += (int)$w['est_calories_burned'];
    }

    return [
        'meals' => $meals,
        'workouts' => $workouts,
        'calories' => $totalCal,
        'protein' => round($totalProt, 1),
        'carbs' => round($totalCarbs, 1),
        'fat' => round($totalFat, 1),
        'verified_count' => $verifiedCount,
        'meal_count' => count($meals),
        'workout_count' => count($workouts),
        'total_volume' => round($totalVolume, 1),
        'total_sets' => $totalSets,
        'est_burn' => $totalBurn,
    ];
}
