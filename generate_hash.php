<?php
// ============================================
// SHOPICKER - Setup / Konfiguracja PIN
// Wersja / Version: 2.5.2
// ============================================

// === AUTO-WYKRYWANIE ŚCIEŻKI / AUTO-DETECT PATH ===
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// === KONIEC / END ===

// === BEZPIECZNE PARAMETRY SESJI / SECURE SESSION PARAMS ===
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

// Dołącz helpery bezpieczeństwa / Include security helpers
require_once __DIR__ . '/inc/security.php';

// Sprawdź czy istnieje system i18n / Check if i18n system exists
$i18n_file = __DIR__ . '/inc/i18n.php';
$has_i18n = file_exists($i18n_file);

if ($has_i18n) {
    require_once $i18n_file;
    initI18n();
} else {
    // Fallback - podstawowe funkcje gdy brak i18n / Fallback when no i18n
    function __($key, $params = []) {
        // Podstawowe polskie tłumaczenia / Basic Polish translations
        $translations = [
            'setup.page_title' => 'Shopicker - Setup PIN',
            'setup.heading' => '🔐 Shopicker Setup',
            'setup.subtitle' => 'Ustaw PIN zabezpieczający dostęp do listy zakupów',
            'setup.info_title' => 'ℹ️ Jednorazowa konfiguracja',
            'setup.info_text' => 'PIN będzie zahaszowany i bezpiecznie zapisany.<br>Ten formularz usunie się automatycznie.',
            'setup.pin_label' => 'PIN (minimum 4 cyfry)',
            'setup.pin_placeholder' => '••••',
            'setup.pin_hint' => 'Zapamiętaj ten PIN - będzie potrzebny do logowania',
            'setup.pin_confirm_label' => 'Potwierdź PIN',
            'setup.submit_button' => '🚀 Wygeneruj konfigurację',
            'setup.toggle_pin' => 'Pokaż/Ukryj PIN',
            'setup.success_title' => 'Shopicker - Setup zakończony!',
            'setup.success_heading' => '🎉 Setup zakończony!',
            'setup.success_message' => '✅ Konfiguracja została utworzona',
            'setup.success_config_saved' => 'Plik config.php zapisany',
            'setup.success_pin_hashed' => 'PIN zahaszowany bezpiecznie',
            'setup.success_file_delete' => 'Ten plik zaraz się usunie',
            'setup.success_go_to_app' => 'Przejdź do Shopicker 🛒',
            'setup.success_warning' => '⚠️ Jeśli plik generate_hash.php nadal istnieje, usuń go ręcznie',
            'setup.already_configured_title' => 'Shopicker - Setup zakończony',
            'setup.already_configured_heading' => '✅ Setup zakończony',
            'setup.already_configured_message' => 'Konfiguracja już istnieje!',
            'setup.already_configured_hint' => 'Możesz bezpiecznie usunąć ten plik (generate_hash.php)',
            'setup.error_blocked' => 'Zbyt wiele nieudanych prób. Spróbuj ponownie później.',
            'setup.error_csrf' => 'Nieprawidłowy token CSRF.',
            'setup.error_pin_empty' => 'Wprowadź PIN',
            'setup.error_pin_min_length' => 'PIN musi mieć minimum 4 znaki',
            'setup.error_pin_mismatch' => 'PIN i potwierdzenie nie są identyczne',
            'setup.error_pin_digits_only' => 'PIN może zawierać tylko cyfry',
            'setup.error_write_config' => 'Błąd zapisu pliku config.php - sprawdź uprawnienia.',
            'setup.error_write_temp' => 'Błąd zapisu pliku tymczasowego - sprawdź uprawnienia katalogu.',
            'setup.blocked_message' => 'Panel tymczasowo zablokowany z powodu wielokrotnych nieudanych prób. Spróbuj ponownie później.',
        ];
        
        $text = $translations[$key] ?? $key;
        
        foreach ($params as $param_key => $param_value) {
            $text = str_replace('{' . $param_key . '}', $param_value, $text);
        }
        
        return $text;
    }
    
    function _e($key, $params = []) {
        echo htmlspecialchars(__($key, $params), ENT_QUOTES, 'UTF-8');
    }
    
    function getCurrentLang() {
        return 'pl';
    }
}

// === SPRAWDZENIE CZY CONFIG JUŻ ISTNIEJE / CHECK IF CONFIG EXISTS ===
$config_file = __DIR__ . '/config.php';

if (file_exists($config_file)) {
    // Konfiguracja już istnieje / Config already exists
    die('
    <!DOCTYPE html>
    <html lang="' . getCurrentLang() . '">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . h(__('setup.already_configured_title')) . '</title>
        <style>
            body {
                font-family: sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            }
            .box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                max-width: 500px;
            }
            h1 { margin: 0 0 20px 0; font-size: 2.5em; }
            p { color: #666; line-height: 1.6; }
            .success { color: #4CAF50; font-weight: 600; font-size: 1.2em; }
            a {
                display: inline-block;
                margin-top: 20px;
                padding: 15px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.2s ease;
            }
            a:hover {
                background: #5568d3;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102,126,234,0.4);
            }
            a:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(102,126,234,0.3);
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>' . h(__('setup.already_configured_heading')) . '</h1>
            <p class="success">' . h(__('setup.already_configured_message')) . '</p>
            <p>' . h(__('setup.already_configured_hint')) . '</p>
            <a href="' . h($base_path) . '/">' . h(__('setup.success_go_to_app')) . '</a>
        </div>
    </body>
    </html>
    ');
}
// === KONIEC / END ===

// === OBSŁUGA FORMULARZA / FORM HANDLING ===
$errors = [];

// Proste rate-limiting / Simple rate-limiting
if (!isset($_SESSION['setup_failed'])) {
    $_SESSION['setup_failed'] = 0;
}
if (!isset($_SESSION['setup_last_failed'])) {
    $_SESSION['setup_last_failed'] = 0;
}

$block_seconds = 300; // 5 minut / 5 minutes
$is_blocked = ($_SESSION['setup_failed'] >= 10 && (time() - $_SESSION['setup_last_failed']) < $block_seconds);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_blocked) {
        $errors[] = __('setup.error_blocked');
    } else {
        $pin = $_POST['pin'] ?? '';
        $pin_confirm = $_POST['pin_confirm'] ?? '';

        // Walidacja CSRF / CSRF validation
        if (!validate_csrf()) {
            $errors[] = __('setup.error_csrf');
        }

        // Walidacja PIN / PIN validation
        if (empty($pin)) {
            $errors[] = __('setup.error_pin_empty');
        } elseif (strlen($pin) < 4) {
            $errors[] = __('setup.error_pin_min_length');
        } elseif ($pin !== $pin_confirm) {
            $errors[] = __('setup.error_pin_mismatch');
        } elseif (!preg_match('/^[0-9]+$/', $pin)) {
            $errors[] = __('setup.error_pin_digits_only');
        }

        if (empty($errors)) {
            // Generuj hash / Generate hash
            $hash = password_hash($pin, PASSWORD_DEFAULT);

            // Utwórz zawartość config.php / Create config.php content
            $config_content = "<?php\n";
            $config_content .= "// config.php - Wygenerowany automatycznie / Auto-generated\n";
            $config_content .= "// Data / Date: " . date('Y-m-d H:i:s') . "\n";
            $config_content .= "\n";
            $config_content .= "return [\n";
            $config_content .= "    'pin_hash' => '" . str_replace("'", "\\'", $hash) . "'\n";
            $config_content .= "];\n";

            // Atomic write: zapisz do tmp, potem rename
            $tmp_file = $config_file . '.tmp';
            $write_success = false;
            
            if (@file_put_contents($tmp_file, $config_content, LOCK_EX) !== false) {
                @chmod($tmp_file, 0600);
                if (@rename($tmp_file, $config_file)) {
                    @chmod($config_file, 0600);
                    $write_success = true;
                } else {
                    // Fallback - bezpośredni zapis / Fallback - direct write
                    if (@file_put_contents($config_file, $config_content, LOCK_EX) !== false) {
                        @chmod($config_file, 0600);
                        $write_success = true;
                        @unlink($tmp_file);
                    } else {
                        $errors[] = __('setup.error_write_config');
                    }
                }
            } else {
                $errors[] = __('setup.error_write_temp');
            }

            if ($write_success) {
                // Sukces - pokaż komunikat / Success - show message
                ?>
                <!DOCTYPE html>
                <html lang="<?php echo getCurrentLang(); ?>">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title><?php _e('setup.success_title'); ?></title>
                    <style>
                        body {
                            font-family: sans-serif;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                            margin: 0;
                            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                            padding: 20px;
                        }
                        .box {
                            background: white;
                            padding: 40px;
                            border-radius: 16px;
                            text-align: center;
                            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                            max-width: 500px;
                            animation: slideIn 0.5s ease;
                        }
                        @keyframes slideIn {
                            from { opacity: 0; transform: translateY(20px); }
                            to { opacity: 1; transform: translateY(0); }
                        }
                        h1 { margin: 0 0 20px 0; font-size: 2.5em; }
                        .success { color: #4CAF50; font-weight: 600; font-size: 1.3em; margin: 20px 0; }
                        .info {
                            background: #f5f5f5;
                            padding: 20px;
                            border-radius: 8px;
                            margin: 20px 0;
                            text-align: left;
                        }
                        .info strong { color: #667eea; }
                        code {
                            background: #ffe0b2;
                            padding: 2px 8px;
                            border-radius: 4px;
                            font-family: monospace;
                        }
                        a {
                            display: inline-block;
                            margin-top: 20px;
                            padding: 15px 40px;
                            background: #667eea;
                            color: white;
                            text-decoration: none;
                            border-radius: 8px;
                            font-weight: 600;
                            font-size: 1.1em;
                            transition: all 0.2s ease;
                        }
                        a:hover {
                            background: #5568d3;
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
                        }
                        a:active {
                            transform: translateY(0);
                            box-shadow: 0 2px 4px rgba(102,126,234,0.3);
                        }
                        .warning { color: #ff9800; font-size: 0.9em; margin-top: 15px; }
                    </style>
                </head>
                <body>
                    <div class="box">
                        <h1><?php _e('setup.success_heading'); ?></h1>
                        <p class="success"><?php _e('setup.success_message'); ?></p>

                        <div class="info">
                            <p><strong>✓</strong> <?php _e('setup.success_config_saved'); ?></p>
                            <p><strong>✓</strong> <?php _e('setup.success_pin_hashed'); ?></p>
                            <p><strong>✓</strong> <?php _e('setup.success_file_delete'); ?></p>
                        </div>

                        <a href="<?php echo h($base_path); ?>/"><?php _e('setup.success_go_to_app'); ?></a>

                        <p class="warning"><?php _e('setup.success_warning'); ?></p>
                    </div>
                </body>
                </html>
                <?php
                // Usuń plik setup / Delete setup file
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

// Pobierz tłumaczenia dla JS / Get translations for JS
$js_translations = [
    'pins_match' => '✓ PIN-y są zgodne',
    'pins_mismatch' => '✗ PIN-y nie są zgodne',
    'pin_too_short' => 'Minimum 4 cyfry',
    'toggle_pin' => __('setup.toggle_pin'),
];

?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('setup.page_title'); ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .setup-box {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .form-group { margin-bottom: 20px; }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            max-width: 400px;
            margin: 0 auto;
        }
        
        input[type="password"],
        input[type="text"] {
            font-size: 1.5em;
            flex: 1;
            min-width: 0;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            transition: border-color 0.3s;
        }
        
        input:focus { outline: none; border-color: #667eea; }
        input.valid { border-color: #4CAF50; }
        input.invalid { border-color: #f44336; }
        
        .toggle-pin {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5em;
            padding: 10px;
            opacity: 0.6;
            transition: opacity 0.2s;
            flex-shrink: 0;
            width: 50px;
            align-self: center;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-pin:hover { opacity: 1; }
        
        .hint {
            font-size: 0.85em;
            color: #666;
            margin-top: 6px;
            font-style: italic;
            text-align: center;
        }
        
        .validation-message {
            font-size: 0.9em;
            margin-top: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 1.5em;
        }
        
        .validation-message.success { color: #4CAF50; }
        .validation-message.error { color: #f44336; }
        
        button[type="submit"] {
            font-size: 1.2em;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        button[type="submit"]:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(76,175,80,0.3);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(76,175,80,0.2);
        }
        
        button[type="submit"]:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        button[type="submit"]:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .errors {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .errors ul { list-style: none; padding: 0; }
        .errors li { padding: 5px 0; }
        .errors li:before { content: "❌ "; }
        
        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
            font-size: 0.95em;
        }
        
        .info-box strong { color: #1976d2; }
        
        .lang-switcher {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
        }
        
        .lang-btn {
            padding: 8px 12px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            text-decoration: none;
            transition: transform 0.2s ease;
        }
        
        .lang-btn:hover {
            transform: scale(1.1);
        }
        
        .lang-btn.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <?php if ($has_i18n): ?>
        <div class="lang-switcher">
            <?php foreach (getAvailableLangs() as $lang): 
                $lang_flag = I18n::getInstance()->getLangMeta($lang, 'flag') ?? $lang;
            ?>
                <a href="?lang=<?php echo h($lang); ?>" 
                   class="lang-btn <?php echo $lang === getCurrentLang() ? 'active' : ''; ?>"
                   title="<?php echo h(I18n::getInstance()->getLangMeta($lang, 'native_name') ?? $lang); ?>">
                    <?php echo $lang_flag; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="setup-box">
        <h1><?php _e('setup.heading'); ?></h1>
        <p class="subtitle"><?php _e('setup.subtitle'); ?></p>

        <div class="info-box">
            <strong><?php _e('setup.info_title'); ?></strong><br>
            <?php echo __('setup.info_text'); ?>
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

        <?php if ($is_blocked): ?>
            <div class="errors" role="alert">
                <ul><li><?php _e('setup.blocked_message'); ?></li></ul>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate id="setupForm">
            <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
            
            <div class="form-group">
                <label for="pin"><?php _e('setup.pin_label'); ?></label>
                <div class="input-wrapper">
                    <input type="password"
                           id="pin"
                           name="pin"
                           placeholder="<?php _e('setup.pin_placeholder'); ?>"
                           autofocus
                           pattern="[0-9]*"
                           inputmode="numeric"
                           autocomplete="off"
                           minlength="4"
						   maxlength="6"
                           required>
                    <button type="button" class="toggle-pin" onclick="togglePin('pin')" title="<?php _e('setup.toggle_pin'); ?>">👁️</button>
                </div>
                <div class="hint"><?php _e('setup.pin_hint'); ?></div>
            </div>

            <div class="form-group">
                <label for="pin_confirm"><?php _e('setup.pin_confirm_label'); ?></label>
                <div class="input-wrapper">
                    <input type="password"
                           id="pin_confirm"
                           name="pin_confirm"
                           placeholder="<?php _e('setup.pin_placeholder'); ?>"
                           pattern="[0-9]*"
                           inputmode="numeric"
                           autocomplete="off"
                           minlength="4"
						   maxlength="6"
                           required>
                    <button type="button" class="toggle-pin" onclick="togglePin('pin_confirm')" title="<?php _e('setup.toggle_pin'); ?>">👁️</button>
                </div>
                <div id="validationMessage" class="validation-message"></div>
            </div>

            <button type="submit" id="submitBtn" <?php echo $is_blocked ? 'disabled' : ''; ?>>
                <?php _e('setup.submit_button'); ?>
            </button>
        </form>
    </div>

    <script>
        // Tłumaczenia dla JS / Translations for JS
        const T = <?php echo json_encode($js_translations); ?>;
        
        const pinInput = document.getElementById('pin');
        const pinConfirmInput = document.getElementById('pin_confirm');
        const validationMessage = document.getElementById('validationMessage');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle pokazywania/ukrywania PIN / Toggle PIN visibility
        function togglePin(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        // Walidacja zgodności PIN-ów / Validate PIN match
        function validatePins() {
            const pin = pinInput.value;
            const pinConfirm = pinConfirmInput.value;
            
            // Reset klas / Reset classes
            pinInput.classList.remove('valid', 'invalid');
            pinConfirmInput.classList.remove('valid', 'invalid');
            validationMessage.textContent = '';
            validationMessage.className = 'validation-message';
            
            // Sprawdź minimalną długość / Check minimum length
            if (pin.length > 0 && pin.length < 4) {
                pinInput.classList.add('invalid');
                validationMessage.textContent = T.pin_too_short;
                validationMessage.classList.add('error');
                submitBtn.disabled = true;
                return false;
            }
            
            // Sprawdź czy oba pola wypełnione / Check if both filled
            if (pin.length >= 4 && pinConfirm.length > 0) {
                if (pin === pinConfirm) {
                    // Zgodne / Match
                    pinInput.classList.add('valid');
                    pinConfirmInput.classList.add('valid');
                    validationMessage.textContent = T.pins_match;
                    validationMessage.classList.add('success');
                    submitBtn.disabled = false;
                    return true;
                } else {
                    // Niezgodne / Mismatch
                    pinConfirmInput.classList.add('invalid');
                    validationMessage.textContent = T.pins_mismatch;
                    validationMessage.classList.add('error');
                    submitBtn.disabled = true;
                    return false;
                }
            }
            
            // Domyślnie wyłącz przycisk jeśli nie ma pełnej walidacji
            if (pin.length < 4 || pinConfirm.length === 0) {
                submitBtn.disabled = true;
            }
            
            return false;
        }

        // Event listenery / Event listeners
        pinInput.addEventListener('input', validatePins);
        pinConfirmInput.addEventListener('input', validatePins);

        // Walidacja przy submit / Validate on submit
        document.getElementById('setupForm').addEventListener('submit', function(e) {
            if (!validatePins()) {
                e.preventDefault();
            }
        });

        // Początkowa walidacja / Initial validation
        validatePins();
    </script>
</body>
</html>