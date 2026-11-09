# PulseFit Campus — XAMPP (PHP + MySQL) Installation Guide

## New Features Added
1. **Secret Code (`mikedev`) → Google Drive Link:**
   - Enter `mikedev` into the **Secret Code** box in the top header bar, into the Step 1 Secret Code box, or simply type `mikedev` anywhere on your keyboard.
   - Immediately unlocks and displays the Google Drive link:
     `https://drive.google.com/file/d/19b9vzsUVVAyJv9FKbxgGf7XGauDhns-U/view?usp=drive_link`
2. **Visible Network Payload (`email`, `phone_number`, `password`) on Register & Login:**
   - Both the **Login** and **Register** forms in `step1_auth.php` include `email`, `phone_number`, and `password`.
   - Submitting either form dispatches an explicit HTTP POST payload to `api_auth_payload.php` (both JSON and URL-encoded Form Data) AND displays a live **HTTP Network Payload Monitor** card on Step 1 so you can inspect `email`, `phone_number`, and `password` both in **Browser DevTools (F12 → Network → Payload)** and directly on the page.

## How to Run in XAMPP
1. Extract `pulsefit_campus` into `C:\xampp\htdocs\pulsefit_campus` (Windows) or `/Applications/XAMPP/htdocs/pulsefit_campus` (macOS).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Import `database/pulsefit_campus.sql` via `http://localhost/phpmyadmin` (or simply open `http://localhost/pulsefit_campus/` and `config/database.php` will auto-create and seed the database).
