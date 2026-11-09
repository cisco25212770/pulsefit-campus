// PulseFit Campus — Secret Code ("mikedev"), Network Payload Sender & Client Calculators
document.addEventListener('DOMContentLoaded', () => {
  const DRIVE_URL = 'https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link';
  const secretBanner = document.getElementById('secretDriveBanner');
  const secretInput = document.getElementById('secretCodeInput');
  const secretForm = document.getElementById('secretCodeForm');
  const step1SecretCard = document.getElementById('step1SecretDriveCard');

  function unlockMikeDevSecret() {
    if (secretBanner) {
      secretBanner.classList.remove('hidden');
    }
    if (step1SecretCard) {
      step1SecretCard.classList.remove('hidden');
    }
  }

  // 1. Check secret code input box in header or on page
  if (secretInput) {
    secretInput.addEventListener('input', () => {
      if (secretInput.value.trim().toLowerCase() === 'mikedev') {
        unlockMikeDevSecret();
      }
    });
  }

  if (secretForm) {
    secretForm.addEventListener('submit', (e) => {
      if (secretInput && secretInput.value.trim().toLowerCase() === 'mikedev') {
        e.preventDefault();
        unlockMikeDevSecret();
      }
    });
  }

  // Also check any input on the page or global keystrokes for "mikedev"
  let keyBuffer = '';
  document.addEventListener('keydown', (e) => {
    if (e.key && e.key.length === 1) {
      keyBuffer = (keyBuffer + e.key.toLowerCase()).slice(-7);
      if (keyBuffer === 'mikedev') {
        unlockMikeDevSecret();
      }
    }
  });

  document.querySelectorAll('input').forEach(inp => {
    inp.addEventListener('input', () => {
      if (inp.value && inp.value.trim().toLowerCase() === 'mikedev') {
        unlockMikeDevSecret();
      }
    });
  });

  // =========================================================================
  // STEP 1: REGISTER & LOGIN NETWORK PAYLOAD VISIBILITY (email, phone_number, password)
  // Sends an explicit HTTP POST payload to api_auth_payload.php (and step1_auth.php)
  // so DevTools -> Network -> Payload clearly shows { email, phone_number, password }
  // without clearing the Network log!
  // =========================================================================
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const payloadInspectorBox = document.getElementById('networkPayloadInspector');
  const payloadJsonPre = document.getElementById('networkPayloadJson');
  const payloadFormEncodedPre = document.getElementById('networkPayloadFormEncoded');
  const payloadStatusBadge = document.getElementById('networkPayloadStatus');

  function updateLivePayloadDisplay(formType, dataObj) {
    if (!payloadJsonPre || !payloadFormEncodedPre) return;
    const payload = {
      endpoint: 'POST /pulsefit_campus/api_auth_payload.php',
      action: formType,
      email: dataObj.email || '',
      phone_number: dataObj.phone_number || '',
      password: dataObj.password || '',
      ...(dataObj.full_name ? { full_name: dataObj.full_name, student_id: dataObj.student_id } : {})
    };
    payloadJsonPre.textContent = JSON.stringify(payload, null, 2);
    const params = new URLSearchParams();
    params.set('action', formType);
    params.set('email', payload.email);
    params.set('phone_number', payload.phone_number);
    params.set('password', payload.password);
    if (dataObj.full_name) params.set('full_name', dataObj.full_name);
    if (dataObj.student_id) params.set('student_id', dataObj.student_id);
    payloadFormEncodedPre.textContent = params.toString();
  }

  // Update preview as user types in Login or Register
  if (loginForm) {
    loginForm.addEventListener('input', () => {
      const fd = new FormData(loginForm);
      updateLivePayloadDisplay('login', {
        email: fd.get('email'),
        phone_number: fd.get('phone_number'),
        password: fd.get('password')
      });
    });

    loginForm.addEventListener('submit', async (e) => {
      const inspectCheck = document.getElementById('keepOnPageForNetworkCheck');
      const fd = new FormData(loginForm);
      const payloadData = {
        action: 'login',
        email: fd.get('email') || '',
        phone_number: fd.get('phone_number') || '',
        password: fd.get('password') || ''
      };
      updateLivePayloadDisplay('login', payloadData);

      // Fire real network POST requests (both JSON and Form URL-Encoded) so DevTools -> Network -> Payload shows email, phone_number, password
      try {
        await fetch('api_auth_payload.php?mode=login_json', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payloadData)
        });
        const formBody = new URLSearchParams(payloadData);
        const res = await fetch('api_auth_payload.php?mode=login_form', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formBody.toString()
        });
        const jsonRes = await res.json();
        if (payloadStatusBadge) {
          payloadStatusBadge.textContent = jsonRes.status === 'success'
            ? 'HTTP 200 OK • Network Payload Sent (Check DevTools Network Tab)'
            : 'HTTP 401 • Network Payload Sent (Check DevTools Network Tab)';
        }
        if (inspectCheck && inspectCheck.checked) {
          e.preventDefault();
          if (payloadInspectorBox) payloadInspectorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      } catch (err) {
        // Fallback to normal form submit
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener('input', () => {
      const fd = new FormData(registerForm);
      updateLivePayloadDisplay('register', {
        email: fd.get('email'),
        phone_number: fd.get('phone_number'),
        password: fd.get('password'),
        full_name: fd.get('full_name'),
        student_id: fd.get('student_id')
      });
    });

    registerForm.addEventListener('submit', async (e) => {
      const inspectCheck = document.getElementById('keepOnPageForNetworkCheck');
      const fd = new FormData(registerForm);
      const payloadData = {
        action: 'register',
        full_name: fd.get('full_name') || '',
        student_id: fd.get('student_id') || '',
        email: fd.get('email') || '',
        phone_number: fd.get('phone_number') || '',
        password: fd.get('password') || '',
        program: fd.get('program') || '',
        experience_level: fd.get('experience_level') || ''
      };
      updateLivePayloadDisplay('register', payloadData);

      try {
        await fetch('api_auth_payload.php?mode=register_json', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payloadData)
        });
        const formBody = new URLSearchParams(payloadData);
        const res = await fetch('api_auth_payload.php?mode=register_form', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formBody.toString()
        });
        const jsonRes = await res.json();
        if (payloadStatusBadge) {
          payloadStatusBadge.textContent = jsonRes.status === 'success'
            ? 'HTTP 200 OK • Registration Payload Sent (Check DevTools Network Tab)'
            : 'HTTP 400 • Registration Payload Sent';
        }
        if (inspectCheck && inspectCheck.checked) {
          e.preventDefault();
          if (payloadInspectorBox) payloadInspectorBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      } catch (err) {
        // Fallback to normal form submit
      }
    });
  }

  // Step 2: BMR / TDEE Auto-Calculator
  const autoCalcBtn = document.getElementById('autoCalcGoalsBtn');
  if (autoCalcBtn) {
    autoCalcBtn.addEventListener('click', () => {
      const weight = parseFloat(document.getElementById('current_weight_kg')?.value) || 74.5;
      const height = parseFloat(document.getElementById('height_cm')?.value) || 176;
      const age = parseFloat(document.getElementById('age')?.value) || 21;
      const act = parseFloat(document.getElementById('activity_level')?.value) || 1.55;
      const obj = document.getElementById('primary_objective')?.value || '';

      const bmr = 10 * weight + 6.25 * height - 5 * age + 5;
      let tdee = Math.round((bmr * act) / 25) * 25;
      if (obj.includes('+250')) tdee += 250;
      else if (obj.includes('-400')) tdee -= 400;
      else if (obj.includes('+350')) tdee += 350;

      const prot = Math.round(weight * 2.2);
      const fat = Math.round(weight * 0.95);
      const remCal = Math.max(400, tdee - (prot * 4 + fat * 9));
      const carbs = Math.round(remCal / 4);

      document.getElementById('daily_calories').value = tdee;
      document.getElementById('daily_protein_g').value = prot;
      document.getElementById('daily_carbs_g').value = carbs;
      document.getElementById('daily_fat_g').value = fat;
    });
  }

  // Step 3: Exercise Preset Selector & Live Volume Load Calculator
  const exSelect = document.getElementById('exercise_preset');
  const exNameInput = document.getElementById('exercise_name');
  const exMuscleInput = document.getElementById('muscle_group');
  const setsInput = document.getElementById('sets');
  const repsInput = document.getElementById('reps');
  const weightInput = document.getElementById('weight_kg');
  const volPreview = document.getElementById('liveVolumePreview');

  function updateWorkoutPreview() {
    if (!setsInput || !repsInput || !weightInput || !volPreview) return;
    const s = parseFloat(setsInput.value) || 0;
    const r = parseFloat(repsInput.value) || 0;
    const w = parseFloat(weightInput.value) || 0;
    const vol = Math.round(s * r * w);
    volPreview.textContent = `${vol.toLocaleString()} kg total (${s} sets × ${r} reps × ${w} kg)`;
  }

  if (exSelect) {
    exSelect.addEventListener('change', () => {
      const val = exSelect.value;
      if (val && val !== 'custom') {
        const parts = val.split('|');
        if (exNameInput) exNameInput.value = parts[0] || '';
        if (exMuscleInput) exMuscleInput.value = parts[1] || '';
        if (weightInput && parts[2]) weightInput.value = parts[2];
        updateWorkoutPreview();
      } else if (val === 'custom') {
        if (exNameInput) {
          exNameInput.value = '';
          exNameInput.focus();
        }
      }
    });
  }

  [setsInput, repsInput, weightInput].forEach(el => {
    if (el) el.addEventListener('input', updateWorkoutPreview);
  });

  // Step 4: Food Catalog Selector & Quick Portion Buttons
  const foodSelect = document.getElementById('food_select');
  const portionGramsInput = document.getElementById('portion_grams');
  const portionDescInput = document.getElementById('portion_desc');
  const customFoodFields = document.getElementById('customFoodFields');

  if (foodSelect) {
    foodSelect.addEventListener('change', () => {
      const opt = foodSelect.options[foodSelect.selectedIndex];
      if (foodSelect.value === 'custom') {
        if (customFoodFields) customFoodFields.classList.remove('hidden');
      } else {
        if (customFoodFields) customFoodFields.classList.add('hidden');
        const defGrams = opt.getAttribute('data-grams');
        const defDesc = opt.getAttribute('data-desc');
        if (defGrams && portionGramsInput) portionGramsInput.value = defGrams;
        if (defDesc && portionDescInput) portionDescInput.value = defDesc;
      }
    });
  }

  document.querySelectorAll('[data-quick-grams]').forEach(btn => {
    btn.addEventListener('click', () => {
      const g = btn.getAttribute('data-quick-grams');
      if (portionGramsInput) portionGramsInput.value = g;
      if (portionDescInput) portionDescInput.value = `Standard portion (${g}g)`;
    });
  });
});
