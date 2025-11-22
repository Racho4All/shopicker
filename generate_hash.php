<?php
// ============================================
// SHOPICKER - Lista zakupów
// generate_hash.php - AUTOMATYCZNY SETUP
// Wersja: 2.4.4 
// Ten plik usunie się sam po wygenerowaniu config.php
//                       (jeżeli nie - usuń go ręcznie)
// ============================================

// === AUTO-WYKRYWANIE ŚCIEŻKI ===
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// === KONIEC ===

// === BEZPIECZNE PARAMETRY SESJI (potrzebne dla CSRF / rate-limiting) ===
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

// include shared security helpers (CSRF and escaping)
require_once __DIR__ . '/inc/security.php';

$config_file = __DIR__ . '/config.php';

// Sprawdź czy config już istnieje
if (file_exists($config_file)) {
    die('
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shopicker - Setup zakończony</title>
        <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:linear-gradient(135deg,#4CAF50 0%,#45a049 100%);} .box{background:white;padding:40px;border-radius:16px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-width:500px;} h1{margin:0 0 20px 0;font-size:2.5em} p{color:#666;line-height:1.6} .success{color:#4CAF50;font-weight:600;font-size:1.2em} a{display:inline-block;margin-top:20px;padding:15px 30px;background:#667eea;color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.2s ease} a:hover{background:#5568d3;transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4)} a:active{transform:translateY(0);box-shadow:0 2px 4px rgba(102,126,234,0.3)}</style>
    </head>
    <body>
        <div class="box">
            <h1>✅ Setup zakończony</h1>
            <p class="success">Konfiguracja już istnieje!</p>
            <p>Możesz bezpiecznie usunąć ten plik (generate_hash.php)</p>
            <a href="' . h($base_path) . '/">Przejdź do Shopicker</a>
        </div>
    </body>
    </html>
    ');
}

// Obsługa formularza
$errors = [];
// Simple rate-limiting for setup attempts
if (!isset($_SESSION['setup_failed'])) $_SESSION['setup_failed'] = 0;
if (!isset($_SESSION['setup_last_failed'])) $_SESSION['setup_last_failed'] = 0;
$setup_block_seconds = 300; // 5 minutes
$setup_blocked = ($_SESSION['setup_failed'] >= 10 && (time() - $_SESSION['setup_last_failed']) < $setup_block_seconds);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($setup_blocked) {
        $errors[] = 'Zbyt wiele nieudanych prób. Spróbuj ponownie później.';
    } else {
        $pin = $_POST['pin'] ?? '';
        $pin_confirm = $_POST['pin_confirm'] ?? '';

        // CSRF validation
        if (!validate_csrf()) {
            $errors[] = 'Nieprawidłowy token CSRF.';
        }

        // Walidacja PINu
        if (empty($pin)) {
            $errors[] = 'Wprowadź PIN';
        } elseif (strlen($pin) < 4) {
            $errors[] = 'PIN musi mieć minimum 4 znaki';
        } elseif ($pin !== $pin_confirm) {
            $errors[] = 'PIN i potwierdzenie nie są identyczne';
        } elseif (!preg_match('/^[0-9]+$/', $pin)) {
            $errors[] = 'PIN może zawierać tylko cyfry';
        }

        if (empty($errors)) {
            // Generuj hash
            $hash = password_hash($pin, PASSWORD_DEFAULT);

            // Utwórz config.php (bez define, jako zwracana tablica)
            $config_lines = [];
            $config_lines[] = "<?php";
            $config_lines[] = "// config.php - Wygenerowany automatycznie";
            $config_lines[] = "// Data: " . date('Y-m-d H:i:s');
            $config_lines[] = "";
            $config_lines[] = "return [";
            $config_lines[] = "    'pin_hash' => '" . str_replace("'", "\\'", $hash) . "'";
            $config_lines[] = "];";
            $config_content = implode("\n", $config_lines) . "\n";

            // Atomic write: zapisz do pliku tymczasowego, potem rename
            $tmp = $config_file . '.tmp';
            $ok = false;
            if (@file_put_contents($tmp, $config_content, LOCK_EX) !== false) {
                @chmod($tmp, 0600);
                if (@rename($tmp, $config_file)) {
                    @chmod($config_file, 0600);
                    $ok = true;
                } else {
                    if (@file_put_contents($config_file, $config_content, LOCK_EX) !== false) {
                        @chmod($config_file, 0600);
                        $ok = true;
                        @unlink($tmp);
                    } else {
                        $errors[] = 'Błąd zapisu pliku config.php - sprawdź uprawnienia.';
                    }
                }
            } else {
                $errors[] = 'Błąd zapisu pliku tymczasowego - sprawdź uprawnienia katalogu.';
            }

            if ($ok) {
                // Sukces - pokaż komunikat i usuń ten plik
                ?>
                <!DOCTYPE html>
                <html lang="pl">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Shopicker - Setup zakończony!</title>
                    <style>
                        body { font-family: sans-serif; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; background:linear-gradient(135deg,#4CAF50 0%,#45a049 100%); padding:20px; }
                        .box { background:white; padding:40px; border-radius:16px; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,0.2); max-width:500px; animation:slideIn 0.5s ease; }
                        @keyframes slideIn { from {opacity:0; transform:translateY(20px)} to {opacity:1; transform:translateY(0)} }
                        h1{margin:0 0 20px 0;font-size:2.5em}
                        .success{color:#4CAF50;font-weight:600;font-size:1.3em;margin:20px 0}
                        .info{background:#f5f5f5;padding:20px;border-radius:8px;margin:20px 0;text-align:left}
                        .info strong{color:#667eea}
                        code{background:#ffe0b2;padding:2px 8px;border-radius:4px;font-family:monospace}
                        a{display:inline-block;margin-top:20px;padding:15px 40px;background:#667eea;color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:1.1em;transition:all 0.2s ease}
                        a:hover{background:#5568d3;transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4)}
                        a:active{transform:translateY(0);box-shadow:0 2px 4px rgba(102,126,234,0.3)}
                        .warning{color:#ff9800;font-size:0.9em;margin-top:15px}
                    </style>
                </head>
                <body>
                    <div class="box">
                        <h1>🎉 Setup zakończony!</h1>
                        <p class="success">✅ Konfiguracja została utworzona</p>

                        <div class="info">
                            <p><strong>✓</strong> Plik <code>config.php</code> zapisany</p>
                            <p><strong>✓</strong> PIN zahaszowany bezpiecznie</p>
                            <p><strong>✓</strong> Ten plik zaraz się usunie</p>
                        </div>

                        <a href="<?php echo h($base_path); ?>/">Przejdź do Shopicker 🛒</a>

                        <p class="warning">
                            ⚠️ Jeśli plik generate_hash.php nadal istnieje, usuń go ręcznie
                        </p>
                    </div>
                </body>
                </html>
                <?php
                @unlink(__FILE__);
                exit;
            } else {
                $_SESSION['setup_failed']++;
                $_SESSION['setup_last_failed'] = time();
            }
        } else {
            $_SESSION['setup_failed']++;
            $_SESSION['setup_last_failed'] = time();
        }
    }
}

// Wyświetl formularz (GET albo błąd POST)
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopicker - Setup PIN</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",system-ui,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:20px}
        .setup-box{background:white;padding:40px;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,0.2);max-width:450px;width:100%}
        h1{font-size:2.5em;margin-bottom:10px;color:#333;text-align:center}
        .subtitle{color:#666;text-align:center;margin-bottom:30px;line-height:1.5}
        .form-group{margin-bottom:20px}
        label{display:block;margin-bottom:8px;font-weight:600;color:#333}
        .input-wrapper{display:flex;gap:10px;align-items:center;justify-content:center;max-width:400px;margin:0 auto}
        input[type="password"],input[type="text"]{font-size:1.5em;flex:1;min-width:0;padding:15px;border:2px solid #ddd;border-radius:8px;text-align:center;transition:border-color .3s}
        input:focus{outline:none;border-color:#667eea}
        input.valid{border-color:#4CAF50}
        input.invalid{border-color:#f44336}
        .toggle-pin{background:none;border:none;cursor:pointer;font-size:1.5em;padding:10px;opacity:0.6;transition:opacity 0.2s;flex-shrink:0;width:50px;align-self:center;line-height:1;display:flex;align-items:center;justify-content:center}
        .toggle-pin:hover{opacity:1}
        .hint{font-size:0.85em;color:#666;margin-top:6px;font-style:italic}
        .validation-message{font-size:0.9em;margin-top:6px;font-weight:500;display:flex;align-items:center;gap:5px}
        .validation-message.success{color:#4CAF50}
        .validation-message.error{color:#f44336}
        button{font-size:1.2em;padding:15px;background:#4CAF50;color:white;border:none;border-radius:8px;cursor:pointer;transition:all .2s;font-weight:600;width:100%;margin-top:10px}
        button:hover{background:#45a049;transform:translateY(-2px);box-shadow:0 4px 12px rgba(76,175,80,0.3)}
        button:active{transform:translateY(0);box-shadow:0 2px 4px rgba(76,175,80,0.2)}
        button:disabled{background:#ccc;cursor:not-allowed;opacity:0.6}
        button:disabled:hover{transform:none;box-shadow:none}
        .errors{background:#ffebee;color:#c62828;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid #c62828}
        .errors ul{list-style:none;padding:0}
        .errors li{padding:5px 0}
        .errors li:before{content:"❌ "}
        .info-box{background:#e3f2fd;padding:15px;border-radius:8px;margin-bottom:20px;border-left:4px solid #2196f3;font-size:.95em}
        .info-box strong{color:#1976d2}
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>🔐 Shopicker Setup</h1>
        <p class="subtitle">Ustaw PIN zabezpieczający dostęp do listy zakupów</p>

        <div class="info-box">
            <strong>ℹ️ Jednorazowa konfiguracja</strong><br>
            PIN będzie zahaszowany i bezpiecznie zapisany.<br>
            Ten formularz usunie się automatycznie.
        </div>

        <?php if (!empty($errors)): ?>
            <div class="errors" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo h($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($setup_blocked): ?>
            <div class="errors" role="alert">
                <ul><li>Panel tymczasowo zablokowany z powodu wielokrotnych nieudanych prób. Spróbuj ponownie później.</li></ul>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate id="setupForm">
            <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
            
            <div class="form-group">
                <label for="pin">PIN (minimum 4 cyfry)</label>
                <div class="input-wrapper">
                    <input type="password"
                           id="pin"
                           name="pin"
                           placeholder="••••"
                           autofocus
                           pattern="[0-9]*"
                           inputmode="numeric"
                           autocomplete="off"
                           minlength="4"
                           required>
                    <button type="button" class="toggle-pin" onclick="togglePin('pin')" title="Pokaż/Ukryj PIN">👁️</button>
                </div>
                <div class="hint">Zapamiętaj ten PIN - będzie potrzebny do logowania</div>
            </div>

            <div class="form-group">
                <label for="pin_confirm">Potwierdź PIN</label>
                <div class="input-wrapper">
                    <input type="password"
                           id="pin_confirm"
                           name="pin_confirm"
                           placeholder="••••"
                           pattern="[0-9]*"
                           inputmode="numeric"
                           autocomplete="off"
                           minlength="4"
                           required>
                    <button type="button" class="toggle-pin" onclick="togglePin('pin_confirm')" title="Pokaż/Ukryj PIN">👁️</button>
                </div>
                <div id="validationMessage"></div>
            </div>

            <button type="submit" id="submitBtn" <?php echo $setup_blocked ? 'disabled' : ''; ?>>🚀 Wygeneruj konfigurację</button>
        </form>
    </div>

    <script>
        const pinInput = document.getElementById('pin');
        const pinConfirmInput = document.getElementById('pin_confirm');
        const validationMessage = document.getElementById('validationMessage');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle pokazywania/ukrywania PINu
        function togglePin(inputId) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
            } else {
                input.type = 'password';
            }
        }

        // Live walidacja
        function validatePins() {
            const pin = pinInput.value;
            const pinConfirm = pinConfirmInput.value;

            // Wyczyść poprzednie stany
            pinInput.classList.remove('valid', 'invalid');
            pinConfirmInput.classList.remove('valid', 'invalid');
            validationMessage.innerHTML = '';

            // Jeśli oba puste, nie pokazuj komunikatu
            if (!pin && !pinConfirm) {
                return;
            }

            // Sprawdź długość PIN
            if (pin && pin.length < 4) {
                pinInput.classList.add('invalid');
                validationMessage.innerHTML = '<span class="validation-message error">❌ PIN musi mieć minimum 4 cyfry</span>';
                return;
            }

            // Sprawdź czy PIN zawiera tylko cyfry
            if (pin && !/^[0-9]+$/.test(pin)) {
                pinInput.classList.add('invalid');
                validationMessage.innerHTML = '<span class="validation-message error">❌ PIN może zawierać tylko cyfry</span>';
                return;
            }

            // Jeśli PIN OK i nie ma potwierdzenia, nie pokazuj błędu
            if (pin && pin.length >= 4 && !pinConfirm) {
                pinInput.classList.add('valid');
                return;
            }

            // Porównaj PINy
            if (pin && pinConfirm) {
                if (pin === pinConfirm) {
                    pinInput.classList.add('valid');
                    pinConfirmInput.classList.add('valid');
                    validationMessage.innerHTML = '<span class="validation-message success">✓ PINy są identyczne</span>';
                } else {
                    pinConfirmInput.classList.add('invalid');
                    validationMessage.innerHTML = '<span class="validation-message error">❌ PINy nie są identyczne</span>';
                }
            }
        }

        // Dodaj listenery
        pinInput.addEventListener('input', validatePins);
        pinConfirmInput.addEventListener('input', validatePins);

        // Walidacja przed submitem
        document.getElementById('setupForm').addEventListener('submit', function(e) {
            const pin = pinInput.value;
            const pinConfirm = pinConfirmInput.value;

            if (pin.length < 4) {
                e.preventDefault();
                alert('PIN musi mieć minimum 4 cyfry');
                return false;
            }

            if (!/^[0-9]+$/.test(pin)) {
                e.preventDefault();
                alert('PIN może zawierać tylko cyfry');
                return false;
            }

            if (pin !== pinConfirm) {
                e.preventDefault();
                alert('PIN i potwierdzenie nie są identyczne');
                return false;
            }
        });
    </script>
</body>
</html>