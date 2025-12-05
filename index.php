<?php
// ============================================
// SHOPICKER - Lista zakupów / Shopping List
// Wersja / Version: 2.5.1
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

// Dołącz helpery bezpieczeństwa i i18n / Include security helpers and i18n
require_once __DIR__ . '/inc/security.php';
require_once __DIR__ . '/inc/i18n.php';

// Zainicjalizuj system tłumaczeń / Initialize translation system
initI18n();

// === SPRAWDZENIE KONFIGURACJI / CONFIG CHECK ===
$config_file = __DIR__ . '/config.php';
$setup_file = __DIR__ . '/generate_hash.php';

if (!file_exists($config_file)) {
    // Brak konfiguracji / No config
    if (file_exists($setup_file)) {
        // Przekieruj na setup / Redirect to setup
        header('Location: ' . $base_path . '/generate_hash.php');
        exit;
    } else {
        // Brak pliku setup - pokaż komunikat błędu / No setup file - show error
        http_response_code(500);
        die('
        <!DOCTYPE html>
        <html lang="' . h(getCurrentLang()) . '">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . h(__('config.error_title')) . '</title>
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    min-height: 100vh; 
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    padding: 20px;
                }
                .error-box {
                    background: white;
                    padding: 40px;
                    border-radius: 16px;
                    text-align: center;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                    max-width: 600px;
                    width: 100%;
                }
                h1 { 
                    font-size: 2.5em; 
                    margin-bottom: 10px;
                    color: #c62828;
                }
                h2 {
                    font-size: 1.5em;
                    margin-bottom: 20px;
                    color: #666;
                }
                p {
                    color: #666;
                    margin-bottom: 15px;
                    line-height: 1.6;
                    text-align: left;
                }
                .code-box {
                    background: #f5f5f5;
                    border-left: 4px solid #ff6b6b;
                    padding: 15px;
                    margin: 20px 0;
                    text-align: left;
                    border-radius: 4px;
                    font-family: monospace;
                    font-size: 0.9em;
                }
                .steps {
                    background: #fff3e0;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    text-align: left;
                }
                .steps ol {
                    margin: 10px 0 0 20px;
                }
                .steps li {
                    margin: 8px 0;
                    line-height: 1.6;
                }
                strong {
                    color: #c62828;
                }
                a {
                    color: #2196F3;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h1>' . h(__('config.error_heading')) . '</h1>
                <h2>' . h(__('config.error_subheading')) . '</h2>
                
                <div class="code-box">
                    <strong>' . h(__('config.missing_files')) . '</strong><br>
                    • ' . h(__('config.file_config')) . '<br>
                    • ' . h(__('config.file_setup')) . '
                </div>
                
                <div class="steps">
                    <strong>' . h(__('config.how_to_fix')) . '</strong>
                    <ol>
                        <li>' . __('config.step_1') . '</li>
                        <li>' . h(__('config.step_2')) . '</li>
                        <li>' . h(__('config.step_3')) . '</li>
                        <li>' . h(__('config.step_4')) . '</li>
                    </ol>
                </div>
                
                <p style="margin-top: 20px; text-align: center; font-size: 0.9em; color: #999;">
                    ' . h(__('config.contact_admin')) . ' 
                    <a href="https://github.com/Racho4All/shopicker" target="_blank">' . h(__('config.documentation')) . '</a>
                </p>
            </div>
        </body>
        </html>
        ');
    }
}
// === KONIEC / END ===

// === AUTENTYKACJA & CSRF & RATE LIMITING ===
$config = require $config_file;

// Podstawowa ochrona przed brute-force / Basic brute-force protection
if (!isset($_SESSION['pin_failed'])) {
    $_SESSION['pin_failed'] = 0;
}
if (!isset($_SESSION['pin_last_failed'])) {
    $_SESSION['pin_last_failed'] = 0;
}

$pin_blocked = false;
$pin_block_seconds = 300; // blokada po >=5 nieudanych na 5 minut / block after >=5 fails for 5 min
if ($_SESSION['pin_failed'] >= 5 && (time() - $_SESSION['pin_last_failed']) < $pin_block_seconds) {
    $pin_blocked = true;
}

// Obsługa POST logowania / Handle login POST
if (isset($_POST['pin'])) {
    // Sprawdź CSRF / Check CSRF
    if (!validate_csrf()) {
        http_response_code(400);
        $error = 'csrf';
    } elseif ($pin_blocked) {
        $error = 'blocked';
    } else {
        // Walidacja PIN / Validate PIN
        $pin_input = (string)$_POST['pin'];
        if (password_verify($pin_input, $config['pin_hash'])) {
            // Udane logowanie / Successful login
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
            $_SESSION['pin_failed'] = 0;
            $_SESSION['pin_last_failed'] = 0;
            header('Location: ' . $base_path . '/');
            exit;
        } else {
            // Nieudana próba / Failed attempt
            $_SESSION['pin_failed']++;
            $_SESSION['pin_last_failed'] = time();
            $error = true;
        }
    }
}

// Wylogowanie / Logout
if (isset($_GET['logout'])) {
    $saved_lang = $_SESSION['lang'] ?? null;
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? true,
                'samesite' => 'Lax'
            ]);
        } else {
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    session_destroy();
    
    // Zachowaj wybór języka po wylogowaniu / Keep language after logout
    session_start();
    if ($saved_lang) {
        $_SESSION['lang'] = $saved_lang;
    }
    
    header('Location: ' . $base_path . '/');
    exit;
}

if (empty($_SESSION['auth'])) {
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo h(getCurrentLang()); ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php _e('login.title'); ?></title>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
                display: flex; 
                justify-content: center; 
                align-items: center; 
                min-height: 100vh; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                text-align: center;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                max-width: 400px;
                width: 100%;
            }
            h1 { 
                font-size: 2.5em; 
                margin-bottom: 10px;
                color: #333;
            }
            p {
                color: #666;
                margin-bottom: 30px;
            }
            input[type="password"] {
                font-size: 2em;
                width: 100%;
                max-width: 200px;
                padding: 15px;
                border: 2px solid #ddd;
                border-radius: 8px;
                text-align: center;
                margin: 20px 0;
                transition: border-color 0.3s ease;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                font-size: 1.2em;
                padding: 15px 50px;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                transition: background 0.2s ease;
                font-weight: 600;
            }
            button:active {
                background: #45a049;
                transform: scale(0.98);
            }
            .error { 
                color: #f44336; 
                margin-top: 15px;
                font-weight: 500;
                animation: shake 0.3s ease;
            }
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }
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
        
        <div class="login-box">
            <h1><?php _e('login.heading'); ?></h1>
            <p><?php _e('login.prompt'); ?></p>
            <form method="POST">
                <input type="password" 
                       name="pin" 
                       placeholder="<?php _e('login.placeholder'); ?>" 
                       autofocus 
                       pattern="[0-9]*" 
                       inputmode="numeric"
                       maxlength="6"
                       autocomplete="off">
                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                <br>
                <button type="submit"><?php _e('login.submit'); ?></button>
                <?php if (isset($error) && $error === 'csrf'): ?>
                    <div class="error"><?php _e('login.error_csrf'); ?></div>
                <?php elseif (isset($error) && $error === 'blocked'): ?>
                    <div class="error"><?php _e('login.error_blocked'); ?></div>
                <?php elseif (isset($error) && $error === true): ?>
                    <div class="error"><?php _e('login.error_invalid_pin'); ?></div>
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// === KONIEC AUTENTYKACJI / END AUTH ===

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Używaj absolutnej ścieżki dla pliku danych / Use absolute path for data file
$data_file = __DIR__ . '/store_orders.txt';
$products_by_store = require __DIR__ . '/products_stores.php';

if (!is_array($products_by_store)) {
    die(h(__('config.error_products_file')));
}

// ============================================
// FUNKCJE POMOCNICZE / HELPER FUNCTIONS
// ============================================

/**
 * Wczytuje ilości z pliku JSON / Load quantities from JSON file
 */
function loadQuantities($file) {
    if (!file_exists($file)) return [];
    $json = @file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Zapisuje ilości do pliku JSON (atomic write) / Save quantities to JSON (atomic)
 */
function saveQuantities($file, $quantities) {
    foreach ($quantities as $store => $products) {
        if (empty($products)) {
            unset($quantities[$store]);
        }
    }
    
    $json = json_encode($quantities, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("JSON write error: " . json_last_error_msg());
        return false;
    }
    
    $tmp = $file . '.tmp';
    $written = @file_put_contents($tmp, $json, LOCK_EX);
    if ($written === false) {
        error_log('Failed to write temp file: ' . $tmp);
        return false;
    }
    if (!@rename($tmp, $file)) {
        $result = @file_put_contents($file, $json, LOCK_EX);
        if ($result === false) {
            error_log('Failed to write target file: ' . $file);
            return false;
        }
    }
    return true;
}

/**
 * Generuje ID kotwicy dla elementu listy / Generate anchor ID for list item
 */
function generateAnchorId($store, $product) {
    return urlencode($store) . '_' . urlencode($product);
}

/**
 * Przekierowuje z zachowaniem filtrów sklepów / Redirect with store filters
 */
function redirectWithFilters() {
    global $base_path;
    
    $params = [];
    
    if (!empty($_POST['visible_stores'])) {
        $params['sklepy'] = $_POST['visible_stores'];
    }
    
    if (!empty($_POST['visible_mode']) && $_POST['visible_mode'] === 'hidden') {
        $params['tryb'] = 'ukryte';
    }
    
    $query_string = $params ? '?' . http_build_query($params) : '';
    header('Location: ' . $base_path . '/' . $query_string);
    exit();
}

// ============================================
// OBSŁUGA USTAWIANIA ILOŚCI (POST)
// ============================================

if (isset($_POST['set_quantity']) && isset($_POST['product']) && isset($_POST['quantity']) && isset($_POST['store'])) {
    if (!validate_csrf()) {
        http_response_code(400);
        die(h(__('errors.csrf_invalid')));
    }
    
    $global_quantities = loadQuantities($data_file);
    $product = (string)$_POST['product'];
    $store = (string)$_POST['store'];
    
    if (trim((string)$_POST['quantity']) === '') {
        $quantity_input = 1;
    } elseif (is_numeric($_POST['quantity']) && (int)$_POST['quantity'] > 0) {
        $quantity_input = (int)$_POST['quantity'];
    } else {
        $quantity_input = '';
    }
    
    $product_exists = false;
    if (isset($products_by_store[$store])) {
        foreach ($products_by_store[$store] as $item) {
            if ($item['name'] === $product) {
                $product_exists = true;
                break;
            }
        }
    }
    
    if ($product_exists) {
        if (!isset($global_quantities[$store])) {
            $global_quantities[$store] = [];
        }
        
        if ($quantity_input === '' || (int)$quantity_input <= 0) {
            unset($global_quantities[$store][$product]);
        } else {
            $global_quantities[$store][$product] = (int)$quantity_input;
        }
        
        saveQuantities($data_file, $global_quantities);
    }
    
    redirectWithFilters();
}

// ============================================
// OBSŁUGA "KUPIONE!" (POST)
// ============================================

if (isset($_POST['mark_as_bought']) && isset($_POST['product']) && isset($_POST['store'])) {
    if (!validate_csrf()) {
        http_response_code(400);
        die(h(__('errors.csrf_invalid')));
    }
    
    $global_quantities = loadQuantities($data_file);
    $product = (string)$_POST['product'];
    $store = (string)$_POST['store'];
    
    $product_exists = false;
    if (isset($products_by_store[$store])) {
        foreach ($products_by_store[$store] as $item) {
            if ($item['name'] === $product) {
                $product_exists = true;
                break;
            }
        }
    }
    
    if ($product_exists) {
        if (isset($global_quantities[$store][$product])) {
            unset($global_quantities[$store][$product]);
        }
        
        if (!isset($global_quantities[$store])) {
            $global_quantities[$store] = [];
        }
        
        saveQuantities($data_file, $global_quantities);
    }
    
    redirectWithFilters();
}

// ============================================
// WCZYTANIE AKTUALNYCH ILOŚCI
// ============================================

$current_quantities = loadQuantities($data_file);

// ============================================
// FILTROWANIE SKLEPÓW Z GET
// ============================================

$filter_stores = null;

if (isset($_GET['sklepy'])) {
    if ($_GET['sklepy'] === '') {
        $filter_stores = [];
    } else {
        $filter_stores = explode(',', $_GET['sklepy']);
    }
}

// ============================================
// STATYSTYKI
// ============================================

$stores_with_products = [];
foreach ($products_by_store as $store_name => $store_products) {
    foreach ($store_products as $item) {
        $product = $item['name'];
        $current_qty = isset($current_quantities[$store_name][$product]) 
            ? $current_quantities[$store_name][$product] 
            : null;
        
        if ($current_qty !== null && $current_qty > 0) {
            $stores_with_products[] = $store_name;
            break;
        }
    }
}

$total_to_buy = 0;
foreach ($products_by_store as $store_name => $store_products) {
    if (!empty($filter_stores) && !in_array($store_name, $filter_stores)) continue;
    
    foreach ($store_products as $item) {
        $product = $item['name'];
        $current_qty = isset($current_quantities[$store_name][$product]) 
            ? $current_quantities[$store_name][$product] 
            : null;
        
        if ($current_qty !== null && $current_qty > 0) {
            $total_to_buy++;
        }
    }
}

// Przygotuj tłumaczenia dla JavaScript / Prepare translations for JavaScript
$js_translations = I18n::getInstance()->get('js');
if (!is_array($js_translations)) {
    // Fallback na puste tłumaczenia jeśli sekcja 'js' nie istnieje
    $js_translations = [
        'show_all' => 'Show all',
        'cart_only' => 'Cart only',
        'select_all' => 'select all',
        'deselect_all' => 'deselect all',
        'have' => '✓ Have',
    ];
}

?>
<!DOCTYPE html>
<html lang="<?php echo h(getCurrentLang()); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php _e('app.title'); ?></title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo h($base_path); ?>/assets/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?php echo h($base_path); ?>/assets/favicon.svg" />
    <link rel="shortcut icon" href="<?php echo h($base_path); ?>/assets/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo h($base_path); ?>/assets/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Shopicker" />
    <link rel="manifest" href="<?php echo h($base_path); ?>/assets/site.webmanifest" />
    
    <!-- Ładowanie fontów / Font loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">    
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <style>
        /* ========================================
           RESET I PODSTAWY
           ======================================== */
        
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: sans-serif; 
            max-width: 675px; 
            margin: 20px auto; 
            padding: 0 10px; 
            line-height: 1.4;
            padding-bottom: 80px;
        }
        
        .logo-text {
            font-family: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
            font-optical-sizing: auto;
            font-weight: 600;
            font-style: normal;
            margin: 0;
            font-size: 1.8em;
        }
        
        /* ========================================
           STYLE PRZYCISKÓW
           ======================================== */
        
        .btn-edit { 
            background-color: #2196F3 !important; 
        }
        
        .btn-edit:hover { 
            background-color: #1976D2 !important; 
        }
        
        .header-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        
        .btn-header { 
            padding: 8px 12px; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
        }
        
        .btn-refresh { 
            background-color: #007bff; 
        }
        
        .btn-hide { 
            background-color: #5d6a7a; 
        }
        
        .btn-refresh:hover { 
            background-color: #0056b3; 
        }
        
        .btn-hide:hover { 
            background-color: #434d58; 
        }
		.buycoffee {
			text-decoration: none;
		}
        
        /* ========================================
           STICKY TOP BAR
           ======================================== */

        .top-bar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 10px 15px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .counter-badge {
            background: #FF9800;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1em;
            white-space: nowrap;
            order: 1;
            text-decoration: none;
            transition: transform 0.2s ease;
        }

        .counter-badge:active {
            transform: scale(0.95);
        }

        .counter-badge.zero {
            background: #4CAF50;
        }

        .logo-text {
            margin: 0;
            font-size: 1.8em;
            order: 2;
        }

        .top-actions {
            display: flex;
            gap: 8px;
            order: 3;
        }
        
        .btn-top {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .btn-toggle {
            background: #2196F3;
            color: white;
        }
        
        .btn-toggle:active {
            background: #1976D2;
            transform: scale(0.95);
        }
        
        .btn-refresh {
            background: #FF9800;
            color: white;
        }

        .btn-refresh:active {
            background: #F57C00;
            transform: scale(0.95);
        }        
        
        .btn-edit {
            background: #9C27B0;
            color: white;
        }
        
        .btn-edit:active {
            background: #7B1FA2;
            transform: scale(0.95);
        }
        
        .btn-lang {
            background: #607D8B;
            color: white;
            font-size: 1.1em;
            padding: 6px 10px;
            position: relative;
        }
        
        .btn-lang:active {
            background: #455A64;
            transform: scale(0.95);
        }
        
        /* ========================================
           PRZEŁĄCZNIK JĘZYKÓW / LANGUAGE SWITCHER
           ======================================== */
        
        .lang-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .lang-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 140px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            z-index: 200;
            overflow: hidden;
            margin-top: 4px;
        }
        
        .lang-dropdown-content.show {
            display: block;
        }
        
        .lang-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            color: #333;
            text-decoration: none;
            font-size: 0.95em;
            transition: background 0.2s ease;
        }
        
        .lang-dropdown-content a:hover {
            background: #f5f5f5;
        }
        
        .lang-dropdown-content a.active {
            background: #E3F2FD;
            font-weight: 600;
        }
        
        .lang-dropdown-content a .lang-flag {
            font-size: 1.2em;
        }
        
        /* ========================================
           WYBÓR SKLEPÓW
           ======================================== */
        
        .stores-picker {
            background: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        
        .store-chip {
            display: flex;
            align-items: center;
            padding: 8px 10px;
            background: white;
            border-radius: 6px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.95em;
        }
        
        .store-chip input {
            margin: 0 6px 0 0;
            width: 18px;
            height: 18px;
        }
        
        .store-chip:has(input:checked) {
            background: #E3F2FD;
            border-color: #2196F3;
            font-weight: 600;
        }
        
        .stores-label {
            font-weight: 600;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-all-stores {
            background: none;
            border: none;
            color: #2196F3;
            font-size: 0.85em;
            cursor: pointer;
            padding: 4px 8px;
        }
        
        /* ========================================
           LISTA PRODUKTÓW
           ======================================== */
        
        .store-section {
            margin-bottom: 20px;
        }
        
        .store-section.hidden {
            display: none;
        }
        
        .store-name {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 15px;
            margin: 0 0 10px 0;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 50px;
            z-index: 50;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        
        .store-counter {
            background: rgba(255,255,255,0.3);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
        }
        
        .product-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .product-list li {
            background: white;
            margin-bottom: 8px;
            padding: 14px 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .product-list li.status-need {
            border-left: 5px solid #FF9800;
            background: #FFF8E1;
        }
        
        .product-list li.status-have {
            border-left: 5px solid #4CAF50;
            opacity: 0.6;
        }
        
        .product-list li.hidden {
            display: none;
        }
        
        .product-name {
            flex: 1;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .quantity-text {
            display: block;
            font-size: 0.9em;
            font-weight: 500;
            color: #FF9800;
            margin-top: 4px;
        }
        
        .status-have .quantity-text {
            color: #4CAF50;
        }
        
        .quantity-form {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        /* Przyciski w liście */
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .btn-bought {
            background: #4CAF50;
            color: white;
            min-width: 100px;
        }
        
        .btn-bought:active {
            background: #45a049;
            transform: scale(0.95);
        }
        
        .btn-change {
            background: #2196F3;
            color: white;
        }
        
        .btn-change:active {
            background: #1976D2;
            transform: scale(0.95);
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            text-align: center;
            font-weight: 600;
        }
        
        .unit-label {
            font-size: 0.9em;
            color: #666;
            font-weight: 500;
        }
        
        /* ========================================
           RESPONSYWNOŚĆ MOBILE
           ======================================== */

        @media (max-width: 600px) {
            .top-bar {
                padding: 8px 12px;
                gap: 8px;
            }
            
            .logo-text {
                order: 0;
                width: 100%;
                text-align: center;
                font-size: 1.8em;
                margin-bottom: 4px;
            }
            .buycoffee {
				text-decoration: none;
				padding-left: 10px;
			}
            .counter-badge {
                order: 1;
                font-size: 1em;
                padding: 6px 12px;
            }
            
            .top-actions {
                order: 2;
            }
            
            .btn-top {
                padding: 8px 10px;
                font-size: 0.9em;
            }
            
            .stores-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .store-chip {
                font-size: 0.9em;
                padding: 6px 8px;
            }
            
            .store-name {
                font-size: 1.1em;
                padding: 10px 12px;
                top: 44px;
            }
            
            .product-list li {
                display: flex;
                flex-direction: column;
                align-items: stretch;
                padding: 12px;
                gap: 10px;
                min-height: auto;
                max-height: none;
                height: auto;
            }
            
            .product-name {
                font-size: 1.05em;
                width: 100%;
                display: block;
                word-wrap: break-word;
                overflow-wrap: break-word;
                line-height: 1.4;
            }
            
            .product-name .quantity-text {
                display: none;
            }
            
            .quantity-form {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
                flex-wrap: nowrap;
                flex-shrink: 0;
            }
            
            .quantity-form::before {
                font-size: 0.9em;
                font-weight: 500;
                flex-shrink: 0;
            }
            
            .status-need .quantity-form::before {
                content: attr(data-quantity);
                color: #FF9800;
            }
            
            .status-have .quantity-form::before {
                content: attr(data-have-text);
                color: #4CAF50;
            }
            
            .quantity-form form {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-shrink: 0;
            }
            
            .status-need .btn-bought {
                padding: 10px 16px;
                white-space: nowrap;
            }
            
            .quantity-input {
                width: 60px;
                font-size: 1em;
                flex-shrink: 0;
            }
            
            .unit-label {
                min-width: auto;
                flex-shrink: 0;
            }
            
            .btn-change {
                padding: 10px 14px;
                white-space: nowrap;
                flex-shrink: 0;
            }
            
            label { 
                white-space: nowrap; 
                font-size: larger; 
            }
            
            .status-need { 
                font-size: larger; 
            }
        }
        
        /* ========================================
           ANIMACJE
           ======================================== */
        
        @keyframes bought-animation {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .bought-anim {
            animation: bought-animation 0.3s ease;
        }

    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- STICKY TOP BAR -->
    <!-- ============================================ -->
    
    <div class="top-bar">
        <a href="#" 
           onclick="resetStores(event)" 
           title="<?php _e('ui.refresh'); ?>" 
           class="counter-badge <?php echo $total_to_buy === 0 ? 'zero' : ''; ?>">
            <?php if ($total_to_buy > 0): ?>
                <?php echo h(__('counter.cart_icon')); ?> <?php echo $total_to_buy; ?>
            <?php else: ?>
                <?php _e('counter.done'); ?>
            <?php endif; ?>
        </a>        
        <h1 class="logo-text">
            <img src="<?php echo h($base_path); ?>/assets/favicon.svg" 
                 alt="Logo" 
                 style="height: 1.5em; vertical-align: middle; margin-right: -0.2em">
            <?php echo h(__('app.name')); ?><a class="buycoffee" href="https://buycoffee.to/racho" title="<?php _e('ui.buycoffee'); ?>" >☕️</a>
        </h1>
        <div class="top-actions">
            <button class="btn-top btn-toggle" onclick="toggleHide()" id="btnToggle">
                👁️
            </button>
            <button class="btn-top btn-refresh" onclick="refreshList()" title="<?php _e('ui.refresh'); ?>">
                🔄
            </button>
            <a href="<?php echo h($base_path); ?>/edit.php" class="btn-top btn-edit" id="btnEdit" title="<?php _e('ui.edit'); ?>">
                ✏️
			</a>
            <div class="lang-dropdown">
                <button class="btn-top btn-lang" onclick="toggleLangDropdown(event)" title="<?php _e('ui.language'); ?>">
                    <?php echo I18n::getInstance()->getLangMeta(getCurrentLang(), 'flag') ?? '🌐'; ?>
                </button>
                <div class="lang-dropdown-content" id="langDropdown">
                    <?php foreach (getAvailableLangs() as $lang): 
                        $lang_flag = I18n::getInstance()->getLangMeta($lang, 'flag') ?? $lang;
                        $lang_name = I18n::getInstance()->getLangMeta($lang, 'native_name') ?? $lang;
                    ?>
                        <a href="?lang=<?php echo h($lang); ?>" 
                           class="<?php echo $lang === getCurrentLang() ? 'active' : ''; ?>">
                            <span class="lang-flag"><?php echo $lang_flag; ?></span>
                            <span><?php echo h($lang_name); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?php echo h($base_path); ?>/?logout" class="btn-top btn-logout" style="background: #f44336;" title="<?php _e('ui.logout'); ?>">
                🚪
            </a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- WYBÓR SKLEPÓW -->
    <!-- ============================================ -->
    
    <div class="stores-picker">
        <div class="stores-label">
            <?php _e('ui.stores'); ?>
            <button class="btn-all-stores" onclick="toggleAllStores()"><?php _e('ui.all_stores'); ?></button>
        </div>
        <div class="stores-grid">
            <?php foreach (array_keys($products_by_store) as $store_name): ?>
                <label class="store-chip" for="store_<?php echo h(urlencode($store_name)); ?>">
                    <input type="checkbox" 
                           id="store_<?php echo h(urlencode($store_name)); ?>"
                           class="store-checkbox" 
                           value="<?php echo h($store_name); ?>">
                    <span><?php echo h($store_name); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- LISTY PRODUKTÓW -->
    <!-- ============================================ -->

    <?php foreach ($products_by_store as $store_name => $store_products): ?>
        <?php 
        if ($filter_stores !== null) {
            if (empty($filter_stores)) {
                continue;
            } elseif (!in_array($store_name, $filter_stores)) {
                continue;
            }
        }
        ?>
        
        <?php
        $store_to_buy = 0;
        foreach ($store_products as $item) {
            $product = $item['name'];
            $current_qty = isset($current_quantities[$store_name][$product]) 
                ? $current_quantities[$store_name][$product] 
                : null;
            if ($current_qty !== null && $current_qty > 0) {
                $store_to_buy++;
            }
        }
        ?>
        
        <div class="store-section" data-store="<?php echo h($store_name); ?>">
            <h2 class="store-name">
                <span><?php echo h($store_name); ?></span>
                <?php if ($store_to_buy > 0): ?>
                    <span class="store-counter"><?php echo $store_to_buy; ?></span>
                <?php endif; ?>
            </h2>
            <ul class="product-list">
                <?php foreach ($store_products as $item): 
                    $product = $item['name'];
                    $unit = $item['unit'];
                    
                    $current_qty = isset($current_quantities[$store_name][$product]) 
                        ? $current_quantities[$store_name][$product] 
                        : null;
                    
                    $is_needed = ($current_qty !== null && $current_qty > 0);
                    $css_class = $is_needed ? 'status-need' : 'status-have';
                    $quantity_text = $is_needed 
                        ? "$current_qty $unit" 
                        : __('product.have');
                    $input_value = $is_needed ? $current_qty : '';
                    $element_id = generateAnchorId($store_name, $product);
                ?>
                
                <li id="<?php echo h($element_id); ?>" class="<?php echo $css_class; ?>">
                    <span class="product-name">
                        <?php echo h($product); ?>
                        <span class="quantity-text"><?php echo h($quantity_text); ?></span>
                    </span>
                    
                    <div class="quantity-form" 
                         data-quantity="<?php echo $is_needed ? h($quantity_text) : ''; ?>"
                         data-have-text="<?php echo h(__('product.have')); ?>">
                        <?php if ($is_needed): ?>
                            <form method="POST" style="display:inline;" onsubmit="animateBought(this)">
                                <input type="hidden" name="product" value="<?php echo h($product); ?>">
                                <input type="hidden" name="store" value="<?php echo h($store_name); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                                <button type="submit" name="mark_as_bought" class="btn btn-bought">
                                    <?php _e('product.bought'); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;" onsubmit="saveScroll()">
                                <input type="number" 
                                       name="quantity" 
                                       value="<?php echo h($input_value); ?>" 
                                       min="0" 
                                       class="quantity-input"
                                       placeholder="1">
                                <span class="unit-label"><?php echo h($unit); ?></span>
                                <input type="hidden" name="product" value="<?php echo h($product); ?>">
                                <input type="hidden" name="store" value="<?php echo h($store_name); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                                <button type="submit" name="set_quantity" class="btn btn-change">
                                    <?php _e('product.buy'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
                
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>

    <script>
        // ========================================
        // KONFIGURACJA Z PHP / CONFIG FROM PHP
        // ========================================
        
        const BASE_PATH = <?php echo json_encode($base_path); ?>;
        const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
        const STORES_WITH_PRODUCTS = <?php echo json_encode($stores_with_products); ?>;
        const CURRENT_LANG = <?php echo json_encode(getCurrentLang()); ?>;
        
        // Tłumaczenia dla JS / Translations for JS
        const T = <?php echo json_encode($js_translations); ?>;

        // Przywróć pozycję scrolla / Restore scroll position
        (function() {
            const pos = sessionStorage.getItem('shoppingList_scrollPos');
            if (pos) {
                document.documentElement.scrollTop = parseInt(pos);
                document.body.scrollTop = parseInt(pos);
            }
        })();

        // Klucze storage / Storage keys
        const STORAGE_HIDE = 'shoppingList_hidden';
        const STORAGE_SCROLL = 'shoppingList_scrollPos';
        const STORAGE_STORES = 'shoppingList_selectedStores';
        
        const checkboxes = document.querySelectorAll('.store-checkbox');
        
        // ========================================
        // Toggle ukrywania kupionych / Toggle hiding bought
        // ========================================
        
        function toggleHide() {
            const haveItems = document.querySelectorAll('.status-have');
            const anyVisible = Array.from(haveItems).some(el => !el.classList.contains('hidden'));
            
            haveItems.forEach(el => {
                if (anyVisible) {
                    el.classList.add('hidden');
                } else {
                    el.classList.remove('hidden');
                }
            });
            
            localStorage.setItem(STORAGE_HIDE, anyVisible ? 'hidden' : 'visible');
            hideEmptyStores();
            updateToggleIcon();
        }
        
        function hideEmptyStores() {
            document.querySelectorAll('.store-section').forEach(section => {
                const visibleItems = section.querySelectorAll('li:not(.hidden)');
                section.classList.toggle('hidden', visibleItems.length === 0);
            });
        }
        
        // ========================================
        // Aktualizacja ikony Toggle / Update toggle icon
        // ========================================
        
        function updateToggleIcon() {
            const btn = document.getElementById('btnToggle');
            if (!btn) return;
            
            const hideState = localStorage.getItem(STORAGE_HIDE);
            
            if (hideState === 'hidden') {
                btn.textContent = '👁️';
                btn.title = T.show_all || 'Show all';
            } else {
                btn.textContent = '🛒';
                btn.title = T.cart_only || 'Cart only';
            }
        }
        
        // ========================================
        // Obsługa sklepów / Store handling
        // ========================================

        function loadStores() {
            const urlParams = new URLSearchParams(window.location.search);
            const fromUrl = urlParams.get('sklepy');
            
            if (fromUrl !== null) {
                localStorage.setItem(STORAGE_STORES, fromUrl);
                const list = fromUrl.split(',').filter(s => s.trim() !== '');
                checkboxes.forEach(ch => ch.checked = list.includes(ch.value));
            } else {
                const saved = localStorage.getItem(STORAGE_STORES);
                if (saved !== null) {
                    const list = saved.split(',').filter(s => s.trim() !== '');
                    checkboxes.forEach(ch => ch.checked = list.includes(ch.value));
                } else {
                    checkboxes.forEach(ch => ch.checked = true);
                }
            }
            
            updateStoresToggleButton();
        }

        function saveStores() {
            const selected = Array.from(checkboxes)
                .filter(ch => ch.checked)
                .map(ch => ch.value);
            
            const storesParam = selected.join(',');
            localStorage.setItem(STORAGE_STORES, storesParam);
            
            const url = BASE_PATH + '/?sklepy=' + encodeURIComponent(storesParam);
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
            window.location.href = url;
        }

        function toggleAllStores() {
            const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
            
            if (anyChecked) {
                checkboxes.forEach(ch => ch.checked = false);
            } else {
                checkboxes.forEach(ch => ch.checked = true);
            }
            
            updateStoresToggleButton();
            saveStores();
        }
        
        function refreshList() {
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
            location.reload();
        }        

        function updateStoresToggleButton() {
            const btnToggle = document.querySelector('.btn-all-stores');
            if (!btnToggle) return;
            
            const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
            btnToggle.textContent = anyChecked ? (T.deselect_all || 'deselect all') : (T.select_all || 'select all');
        }

        checkboxes.forEach(ch => {
            ch.addEventListener('change', () => {
                updateStoresToggleButton();
                saveStores();
            });
        });

        // ========================================
        // Reset sklepów (badge click) / Reset stores
        // ========================================

        function resetStores(event) {
            event.preventDefault();
            
            const storesParam = STORES_WITH_PRODUCTS.join(',');
            localStorage.setItem(STORAGE_STORES, storesParam);
            localStorage.setItem(STORAGE_HIDE, 'hidden');
            sessionStorage.setItem(STORAGE_SCROLL, 0);
            
            window.location.href = BASE_PATH + '/?sklepy=' + encodeURIComponent(storesParam);
        }
        
        // ========================================
        // Przełączanie języka / Language dropdown
        // ========================================
        
        function toggleLangDropdown(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('langDropdown');
            dropdown.classList.toggle('show');
        }
        
        // Zamknij dropdown po kliknięciu gdziekolwiek / Close dropdown on click outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('langDropdown');
            const langBtn = document.querySelector('.btn-lang');
            
            if (dropdown && !dropdown.contains(event.target) && event.target !== langBtn) {
                dropdown.classList.remove('show');
            }
        });
        
        // ========================================
        // Scroll & animacje / Scroll & animations
        // ========================================
        
        function saveScroll() {
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
        }
        
        function restoreScroll() {
            const pos = sessionStorage.getItem(STORAGE_SCROLL);
            if (pos) {
                const scrollPos = parseInt(pos);
                
                window.scrollTo({
                    top: scrollPos,
                    behavior: 'instant'
                });
                
                setTimeout(() => {
                    sessionStorage.removeItem(STORAGE_SCROLL);
                }, 100);
            }
        }
        
        function animateBought(form) {
            const li = form.closest('li');
            if (li) li.classList.add('bought-anim');
            saveScroll();
        }
        
        window.addEventListener('scroll', () => {
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
        });
        
        // ========================================
        // Ukryte pola w formularzach / Hidden form fields
        // ========================================
        
        function addHiddenFields() {
            const stores = localStorage.getItem(STORAGE_STORES) || '';
            document.querySelectorAll('form').forEach(f => {
                if (!f.querySelector('input[name="visible_stores"]')) {
                    const h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'visible_stores';
                    h.value = stores;
                    f.appendChild(h);
                }
                if (!f.querySelector('input[name="_csrf"]')) {
                    const c = document.createElement('input');
                    c.type = 'hidden';
                    c.name = '_csrf';
                    c.value = CSRF_TOKEN || '';
                    f.appendChild(c);
                }
            });
        }
        
        // ========================================
        // Inicjalizacja / Initialization
        // ========================================
        
        document.addEventListener('DOMContentLoaded', () => {
            loadStores();
            
            const hideState = localStorage.getItem(STORAGE_HIDE);
            if (hideState === 'hidden') {
                document.querySelectorAll('.status-have').forEach(el => el.classList.add('hidden'));
            }
            hideEmptyStores();
            updateToggleIcon();
            
            addHiddenFields();
            restoreScroll();
        });
		
		// ========================================
		// Przekazanie sklepów do edytora / Pass stores to editor
		// ========================================

		document.getElementById('btnEdit')?.addEventListener('click', function(e) {
			e.preventDefault();
			sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
			const stores = localStorage.getItem(STORAGE_STORES) || '';
			const url = BASE_PATH + '/edit.php' + (stores ? '?expand=' + encodeURIComponent(stores) : '');
			window.location.href = url;
		});
		
		
    </script>

</body>
</html>
