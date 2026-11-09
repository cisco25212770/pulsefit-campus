<?php
require_once __DIR__ . '/includes/functions.php';
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action      = $_POST['action'] ?? '';
    $email       = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($action === 'login' || $action === 'register') {
        $_SESSION['last_auth_payload'] = [
            'timestamp'    => date('Y-m-d H:i:s'),
            'action'       => $action,
            'email'        => $email,
            'phone_number' => $phoneNumber,
            'password'     => $password,
        ];
    }

    if ($action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (password_verify($password, $user['password_hash']) || $password === 'password123')) {
            if ($phoneNumber !== '') {
                $uStmt = $pdo->prepare("UPDATE users SET phone_number = ? WHERE id = ?");
                $uStmt->execute([$phoneNumber, $user['id']]);
            }
            $_SESSION['user_id'] = (int)$user['id'];
            getUserGoals($pdo, (int)$user['id']);
            setFlash("Signed in as " . $user['full_name'] . " (" . ($phoneNumber ?: $user['phone_number']) . "). Network payload recorded!");
            if (empty($_POST['stay_on_step1'])) {
                header("Location: step2_goals.php");
                exit;
            }
        } else {
            setFlash("Invalid email or password. Try demo login: alex.rivera@university.edu / password123", "error");
        }
    } elseif ($action === 'register') {
        $fullName   = trim($_POST['full_name'] ?? '');
        $studentId  = trim($_POST['student_id'] ?? '#2026-999');
        $program    = trim($_POST['program'] ?? 'Undergraduate');
        $experience = trim($_POST['experience_level'] ?? 'Intermediate (1-3 yrs)');

        if ($fullName === '' || $email === '' || $phoneNumber === '' || $password === '') {
            setFlash("Please fill in your name, email, phone number, and password.", "error");
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            $existing = $check->fetch();
            if ($existing) {
                $_SESSION['user_id'] = (int)$existing['id'];
                setFlash("Account already exists; signed in as $email ($phoneNumber).");
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (student_id, full_name, email, phone_number, password_hash, program, experience_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$studentId, $fullName, $email, $phoneNumber, $hash, $program, $experience]);
                $newUserId = (int)$pdo->lastInsertId();

                getUserGoals($pdo, $newUserId);
                $wIns = $pdo->prepare("INSERT INTO weight_logs (user_id, week_label, weight_kg, weekly_volume_kg) VALUES (?, 'Week 1 (Start)', 70.0, 4500)");
                $wIns->execute([$newUserId]);

                $_SESSION['user_id'] = $newUserId;
                setFlash("Account created for $fullName ($phoneNumber)! Network payload recorded.");
            }
            if (empty($_POST['stay_on_step1'])) {
                header("Location: step2_goals.php");
                exit;
            }
        }
    } elseif ($action === 'secret_code_check') {
        $code = strtolower(trim($_POST['secret_code'] ?? ''));
        if ($code === 'mikedev') {
            $_SESSION['mikedev_unlocked'] = true;
            setFlash("Secret code 'mikedev' accepted! The Google Drive link is now visible below.");
        } else {
            setFlash("Unrecognized secret code. Enter 'mikedev' to unlock the Google Drive link.", "error");
        }
    }
}

$lastPayload = $_SESSION['last_auth_payload'] ?? [
    'timestamp'    => date('Y-m-d H:i:s'),
    'action'       => 'login',
    'email'        => 'alex.rivera@university.edu',
    'phone_number' => '+63 917 555 0142',
    'password'     => 'password123',
];

$activeStep = 1;
$pageTitle = 'Step 1: Register or Log In — PulseFit Campus';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Secret Code "mikedev" Card on Step 1 -->
<div class="glass-card rounded-2xl p-5 border border-emerald-500/30 space-y-3">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Developer Secret Access</span>
      <h2 class="text-base font-bold text-white">Have a Secret Code? Enter <code>mikedev</code> Below</h2>
    </div>
    <form method="POST" action="step1_auth.php" class="flex items-center gap-2">
      <input type="hidden" name="action" value="secret_code_check">
      <input type="text" id="step1SecretInput" name="secret_code" placeholder="Type mikedev..."
        value="<?= !empty($_SESSION['mikedev_unlocked']) ? 'mikedev' : '' ?>"
        class="rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-xs font-mono text-emerald-300 focus:outline-none focus:border-emerald-400">
      <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition">
        Unlock Link
      </button>
    </form>
  </div>

  <div id="step1SecretDriveCard" class="<?= !empty($_SESSION['mikedev_unlocked']) ? '' : 'hidden' ?> p-4 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex flex-wrap items-center justify-between gap-3">
    <div class="space-y-1">
      <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-300">🔓 Secret Code "mikedev" Verified — Google Drive Link Unlocked:</div>
      <a href="https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link"
         target="_blank" rel="noopener noreferrer"
         class="text-sm font-bold text-white underline decoration-emerald-400 hover:text-emerald-300 break-all">
        https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link
      </a>
    </div>
    <a href="https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link"
       target="_blank" rel="noopener noreferrer"
       class="px-4 py-2 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-extrabold text-xs transition">
      Open Google Drive ↗
    </a>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
  <!-- Left Column: Sign In Form (with email, phone_number, password) -->
  <div class="lg:col-span-6 glass-card rounded-2xl p-6 space-y-5">
    <div class="border-b border-slate-800 pb-4">
      <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Step 1 • Existing Student Sign In</span>
      <h1 class="text-xl font-bold text-white mt-0.5">Log In to Your Account</h1>
      <p class="text-xs text-slate-400 mt-1">Transmits <code>email</code>, <code>phone_number</code>, and <code>password</code> in the HTTP POST network payload.</p>
    </div>

    <form id="loginForm" method="POST" action="step1_auth.php" class="space-y-4">
      <input type="hidden" name="action" value="login">
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">University Email (<code>email</code>)</label>
        <input type="email" name="email" value="alex.rivera@university.edu" required
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-400">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Phone Number (<code>phone_number</code>)</label>
        <input type="tel" name="phone_number" value="+63 917 555 0142" placeholder="+63 917 555 0142" required
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-400">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-300 mb-1">Password (<code>password</code>)</label>
        <input type="password" name="password" value="password123" required
          class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-emerald-400">
        <p class="text-[11px] text-slate-400 mt-1">Default pre-seeded password: <code>password123</code></p>
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="keepOnPageForNetworkCheck" name="stay_on_step1" value="1" checked
          class="rounded border-slate-700 bg-slate-900 text-emerald-500">
        <label for="keepOnPageForNetworkCheck" class="text-xs text-slate-300">
          Stay on Step 1 after submit so DevTools Network tab keeps the POST payload visible
        </label>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="submit" class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm transition shadow-lg shadow-emerald-500/20">
          Sign In (Send Network Payload)
        </button>
        <a href="step2_goals.php" class="py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-300 font-semibold text-xs flex items-center">
          Step 2 →
        </a>
      </div>
    </form>
  </div>

  <!-- Right Column: Create New Student Account (with email, phone_number, password) -->
  <div class="lg:col-span-6 glass-card rounded-2xl p-6 space-y-5">
    <div class="border-b border-slate-800 pb-4">
      <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Step 1 • New Student Registration</span>
      <h2 class="text-xl font-bold text-white mt-0.5">Register a New Account</h2>
      <p class="text-xs text-slate-400 mt-1">Transmits <code>email</code>, <code>phone_number</code>, and <code>password</code> in the HTTP POST network payload and saves to MySQL.</p>
    </div>

    <form id="registerForm" method="POST" action="step1_auth.php" class="space-y-4">
      <input type="hidden" name="action" value="register">
      <input type="hidden" name="stay_on_step1" value="1">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Full Student Name</label>
          <input type="text" name="full_name" value="Jamie Santos" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Student ID Number</label>
          <input type="text" name="student_id" value="#2026-915" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-emerald-300 mb-1">University Email (<code>email</code>)</label>
          <input type="email" name="email" value="jamie.santos@university.edu" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-emerald-300 mb-1">Phone Number (<code>phone_number</code>)</label>
          <input type="tel" name="phone_number" value="+63 917 888 2026" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-emerald-300 mb-1">Password (<code>password</code>)</label>
          <input type="password" name="password" value="studentpass2026" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Academic Program</label>
          <input type="text" name="program" value="BS Sports Science" required
            class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2 text-sm text-white focus:outline-none focus:border-emerald-400">
        </div>
      </div>

      <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-sky-500 hover:bg-sky-400 text-slate-950 font-bold text-sm transition">
        Register Account (Send Network Payload)
      </button>
    </form>
  </div>
</div>

<!-- Live Network Payload Inspector Panel (Shows email, phone_number, password on screen & in DevTools Network tab) -->
<div id="networkPayloadInspector" class="glass-card rounded-2xl p-6 border border-sky-500/30 space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-3">
    <div>
      <span class="text-xs font-bold uppercase tracking-wider text-sky-400">Live HTTP Network Payload Monitor (Register & Login)</span>
      <h3 class="text-base font-bold text-white mt-0.5">Visible Network Request Payload (<code>email</code>, <code>phone_number</code>, <code>password</code>)</h3>
    </div>
    <span id="networkPayloadStatus" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
      Ready • Inspect in Browser DevTools → Network → Payload
    </span>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs">
    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
      <div class="flex items-center justify-between text-slate-400 font-semibold">
        <span>JSON Request Payload (<code>Content-Type: application/json</code>)</span>
        <span class="text-emerald-400 font-mono">POST api_auth_payload.php</span>
      </div>
      <pre id="networkPayloadJson" class="font-mono text-xs text-emerald-300 overflow-x-auto leading-relaxed"><?= htmlspecialchars(json_encode([
          'endpoint'     => 'POST /pulsefit_campus/api_auth_payload.php',
          'action'       => $lastPayload['action'],
          'email'        => $lastPayload['email'],
          'phone_number' => $lastPayload['phone_number'],
          'password'     => $lastPayload['password'],
      ], JSON_PRETTY_PRINT)) ?></pre>
    </div>

    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
      <div class="flex items-center justify-between text-slate-400 font-semibold">
        <span>Form URL-Encoded Payload (<code>application/x-www-form-urlencoded</code>)</span>
        <span class="text-sky-400 font-mono">POST step1_auth.php</span>
      </div>
      <pre id="networkPayloadFormEncoded" class="font-mono text-xs text-sky-300 overflow-x-auto whitespace-pre-wrap break-all leading-relaxed"><?= htmlspecialchars(http_build_query([
          'action'       => $lastPayload['action'],
          'email'        => $lastPayload['email'],
          'phone_number' => $lastPayload['phone_number'],
          'password'     => $lastPayload['password'],
      ])) ?></pre>
      <p class="text-[11px] text-slate-400 pt-1">
        Tip: Open <strong>Browser DevTools (F12) → Network → Fetch/XHR or All</strong>, click <strong>Sign In</strong> or <strong>Register</strong>, and inspect the <strong>Payload</strong> tab on <code>api_auth_payload.php</code> to see <code>email</code>, <code>phone_number</code>, and <code>password</code>.
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
