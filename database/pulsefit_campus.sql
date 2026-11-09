SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `weight_logs`;
DROP TABLE IF EXISTS `meals`;
DROP TABLE IF EXISTS `workouts`;
DROP TABLE IF EXISTS `food_catalog`;
DROP TABLE IF EXISTS `fitness_goals`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(32) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone_number` varchar(32) NOT NULL DEFAULT '+63 917 555 0142',
  `password_hash` varchar(255) NOT NULL,
  `program` varchar(100) DEFAULT 'Undergraduate Student',
  `experience_level` varchar(50) DEFAULT 'Intermediate (1-3 yrs)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fitness_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `current_weight_kg` decimal(5,2) NOT NULL DEFAULT 74.50,
  `target_weight_kg` decimal(5,2) NOT NULL DEFAULT 78.00,
  `height_cm` int(11) NOT NULL DEFAULT 176,
  `age` int(11) NOT NULL DEFAULT 21,
  `primary_objective` varchar(80) NOT NULL DEFAULT 'Lean Muscle Gain (+250 kcal)',
  `activity_level` decimal(4,3) NOT NULL DEFAULT 1.550,
  `daily_calories` int(11) NOT NULL DEFAULT 2450,
  `daily_protein_g` int(11) NOT NULL DEFAULT 165,
  `daily_carbs_g` int(11) NOT NULL DEFAULT 285,
  `daily_fat_g` int(11) NOT NULL DEFAULT 72,
  `weekly_workouts` int(11) NOT NULL DEFAULT 4,
  `daily_water_liters` decimal(4,2) NOT NULL DEFAULT 3.25,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `food_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `food_key` varchar(50) NOT NULL,
  `food_name` varchar(120) NOT NULL,
  `cal_per_100g` decimal(6,1) NOT NULL,
  `protein_per_100g` decimal(5,1) NOT NULL,
  `carbs_per_100g` decimal(5,1) NOT NULL,
  `fat_per_100g` decimal(5,1) NOT NULL,
  `default_grams` int(11) NOT NULL DEFAULT 180,
  `default_desc` varchar(100) NOT NULL DEFAULT '1 standard serving',
  PRIMARY KEY (`id`),
  UNIQUE KEY `food_key` (`food_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `workouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `workout_date` date NOT NULL,
  `session_time` time NOT NULL,
  `exercise_name` varchar(120) NOT NULL,
  `muscle_group` varchar(80) NOT NULL,
  `sets` int(11) NOT NULL,
  `reps` int(11) NOT NULL,
  `weight_kg` decimal(6,2) NOT NULL,
  `volume_kg` decimal(10,2) NOT NULL,
  `est_calories_burned` int(11) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_workouts_user` (`user_id`),
  CONSTRAINT `fk_workouts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `meals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `meal_time` time NOT NULL,
  `meal_category` varchar(50) NOT NULL DEFAULT 'Lunch',
  `food_name` varchar(120) NOT NULL,
  `portion_grams` int(11) NOT NULL,
  `portion_desc` varchar(120) NOT NULL,
  `cal_per_100g` decimal(6,1) NOT NULL,
  `protein_per_100g` decimal(5,1) NOT NULL,
  `carbs_per_100g` decimal(5,1) NOT NULL,
  `fat_per_100g` decimal(5,1) NOT NULL,
  `est_calories` int(11) NOT NULL,
  `est_protein_g` decimal(6,1) NOT NULL,
  `est_carbs_g` decimal(6,1) NOT NULL,
  `est_fat_g` decimal(6,1) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_meals_user` (`user_id`),
  CONSTRAINT `fk_meals_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `weight_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `week_label` varchar(32) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `weekly_volume_kg` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_weight_user` (`user_id`),
  CONSTRAINT `fk_weight_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `student_id`, `full_name`, `email`, `phone_number`, `password_hash`, `program`, `experience_level`) VALUES
(1, '#2026-841', 'Alex Rivera', 'alex.rivera@university.edu', '+63 917 555 0142', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BS Kinesiology - Junior', 'Intermediate (1-3 yrs)'),
(2, '#2026-309', 'Maya Chen', 'maya.chen@university.edu', '+63 918 555 0198', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'BS Biomedical Engineering - Senior', 'Advanced Athlete (3+ yrs)');

INSERT INTO `fitness_goals` (`user_id`, `current_weight_kg`, `target_weight_kg`, `height_cm`, `age`, `primary_objective`, `activity_level`, `daily_calories`, `daily_protein_g`, `daily_carbs_g`, `daily_fat_g`, `weekly_workouts`, `daily_water_liters`) VALUES
(1, 74.50, 78.00, 176, 21, 'Lean Muscle Gain (+250 kcal)', 1.550, 2450, 165, 285, 72, 4, 3.25),
(2, 59.00, 56.50, 165, 22, 'Athletic Fat Loss (-400 kcal)', 1.725, 1950, 140, 210, 58, 5, 3.00);

INSERT INTO `food_catalog` (`food_key`, `food_name`, `cal_per_100g`, `protein_per_100g`, `carbs_per_100g`, `fat_per_100g`, `default_grams`, `default_desc`) VALUES
('grilled_chicken', 'Grilled Chicken Breast', 165.0, 31.0, 0.0, 3.6, 200, '1 large breast fillet (200g)'),
('salmon_fillet', 'Atlantic Salmon Fillet', 208.0, 20.4, 0.0, 13.4, 180, '1 baked fillet (180g)'),
('whey_shake', 'Whey Isolate Protein Shake', 375.0, 78.0, 6.0, 3.5, 40, '1 heaping scoop in water (40g)'),
('eggs_whole', 'Scrambled Whole Eggs + Egg Whites', 143.0, 12.6, 0.8, 9.5, 180, '3 eggs scrambled (180g)'),
('greek_yogurt', '0% Greek Yogurt Bowl with Berries', 73.0, 9.8, 7.2, 0.4, 250, '1 large bowl (250g)'),
('brown_rice', 'Steamed Jasmine / Brown Rice', 123.0, 2.7, 25.6, 1.0, 220, '1.5 cooked cups (220g)'),
('rolled_oats', 'Rolled Oats with Chia & Banana', 158.0, 5.5, 27.0, 3.1, 260, '1 hearty breakfast bowl (260g)'),
('avocado_toast', 'Sourdough Avocado & Poached Egg Toast', 215.0, 8.4, 19.5, 11.8, 190, '2 slices loaded (190g)'),
('tofu_stirfry', 'Crispy Tofu & Broccoli Teriyaki Bowl', 138.0, 10.2, 12.4, 5.4, 280, '1 dining hall bowl (280g)'),
('tuna_wrap', 'High-Protein Whole Wheat Tuna Wrap', 172.0, 18.5, 15.0, 4.1, 210, '1 full wrap (210g)');

INSERT INTO `workouts` (`user_id`, `workout_date`, `session_time`, `exercise_name`, `muscle_group`, `sets`, `reps`, `weight_kg`, `volume_kg`, `est_calories_burned`, `notes`) VALUES
(1, CURDATE(), '17:15:00', 'Barbell Bench Press', 'Chest & Triceps', 4, 8, 80.00, 2560.00, 150, 'RPE 8.5, controlled pause'),
(1, CURDATE(), '17:32:00', 'Incline Dumbbell Press', 'Upper Chest', 3, 10, 30.00, 900.00, 98, 'Full stretch at bottom'),
(1, CURDATE(), '17:50:00', 'Weighted Pull-Ups / Lat Pulldown', 'Back & Biceps', 4, 10, 68.00, 2720.00, 153, 'Controlled eccentric'),
(1, CURDATE(), '18:05:00', 'Seated Dumbbell Shoulder Press', 'Shoulders', 3, 10, 22.00, 660.00, 94, 'Clean lockout');

INSERT INTO `meals` (`user_id`, `meal_date`, `meal_time`, `meal_category`, `food_name`, `portion_grams`, `portion_desc`, `cal_per_100g`, `protein_per_100g`, `carbs_per_100g`, `fat_per_100g`, `est_calories`, `est_protein_g`, `est_carbs_g`, `est_fat_g`, `is_verified`) VALUES
(1, CURDATE(), '08:00:00', 'Breakfast', 'Rolled Oats with Chia & Banana', 280, '1 large bowl (280g)', 158.0, 5.5, 27.0, 3.1, 442, 15.4, 75.6, 8.7, 1),
(1, CURDATE(), '08:15:00', 'Breakfast', 'Scrambled Whole Eggs + Egg Whites', 190, '3 eggs + whites (190g)', 143.0, 12.6, 0.8, 9.5, 272, 23.9, 1.5, 18.1, 1),
(1, CURDATE(), '12:45:00', 'Lunch', 'Grilled Chicken Breast', 220, '1 large breast (220g)', 165.0, 31.0, 0.0, 3.6, 363, 68.2, 0.0, 7.9, 1),
(1, CURDATE(), '12:50:00', 'Lunch', 'Steamed Jasmine / Brown Rice', 240, '1.5 cups cooked (240g)', 123.0, 2.7, 25.6, 1.0, 295, 6.5, 61.4, 2.4, 1),
(1, CURDATE(), '18:30:00', 'Pre/Post-Workout', 'Whey Isolate Protein Shake', 45, '1.25 scoops post-gym (45g)', 375.0, 78.0, 6.0, 3.5, 169, 35.1, 2.7, 1.6, 1),
(1, CURDATE(), '20:00:00', 'Dinner', 'Atlantic Salmon Fillet', 180, '1 baked fillet (180g)', 208.0, 20.4, 0.0, 13.4, 374, 36.7, 0.0, 24.1, 0);

INSERT INTO `weight_logs` (`user_id`, `week_label`, `weight_kg`, `weekly_volume_kg`) VALUES
(1, 'Week 1', 73.00, 5400),
(1, 'Week 2', 73.30, 5850),
(1, 'Week 3', 73.60, 6100),
(1, 'Week 4', 73.90, 6450),
(1, 'Week 5', 74.20, 6680),
(1, 'Week 6 (Current)', 74.50, 6840);

COMMIT;
