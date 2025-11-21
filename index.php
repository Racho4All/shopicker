<?php
// ============================================
// SHOPICKER - Lista zakupów
// Wersja: 2.4.3
// ============================================

// === AUTO-WYKRYWANIE ŚCIEŻKI ===
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// === KONIEC ===

// === BEZPIECZNE PARAMETRY SESJI ===
// Ustaw cookie params zanim wywołasz session_start()
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

// include security helpers (CSRF + escaping)
require_once __DIR__ . '/inc/security.php';

// === SPRAWDZENIE KONFIGURACJI ===
$config_file = __DIR__ . '/config.php';
$setup_file = __DIR__ . '/generate_hash.php';

if (!file_exists($config_file)) {
    // Brak konfiguracji
    if (file_exists($setup_file)) {
        // Przekieruj na setup
        header('Location: ' . $base_path . '/generate_hash.php');
        exit;
    } else {
        // Brak pliku setup - pokaż komunikat błędu
        http_response_code(500);
        die('
        <!DOCTYPE html>
        <html lang="pl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Shopicker - Błąd konfiguracji</title>
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
                <h1>⚠️ Błąd konfiguracji</h1>
                <h2>Brak wymaganych plików</h2>
                
                <div class="code-box">
                    <strong>Brakujące pliki:</strong><br>
                    • config.php (konfiguracja)<br>
                    • generate_hash.php (instalator)
                </div>
                
                <div class="steps">
                    <strong>🔧 Jak to naprawić:</strong>
                    <ol>
                        <li>Wgraj plik <strong>generate_hash.php</strong> do katalogu aplikacji</li>
                        <li>Odśwież tę stronę</li>
                        <li>Zostaniesz przekierowany na formularz konfiguracji</li>
                        <li>Ustaw PIN i gotowe!</li>
                    </ol>
                </div>
                
                <p style="margin-top: 20px; text-align: center; font-size: 0.9em; color: #999;">
                    Jeśli problem się powtarza, skontaktuj się z administratorem lub sprawdź 
                    <a href="https://github.com/Racho4All/shopicker" target="_blank">dokumentację</a>
                </p>
            </div>
        </body>
        </html>
        ');
    }
}
// === KONIEC ===

// === AUTENTYKACJA & CSRF & RATE LIMITING ===
$config = require $config_file;

// Basic PIN brute-force protection (session based)
if (!isset($_SESSION['pin_failed'])) {
    $_SESSION['pin_failed'] = 0;
}
if (!isset($_SESSION['pin_last_failed'])) {
    $_SESSION['pin_last_failed'] = 0;
}

$pin_blocked = false;
$pin_block_seconds = 300; // blokada po >=5 nieudanych na 5 minut
if ($_SESSION['pin_failed'] >= 5 && (time() - $_SESSION['pin_last_failed']) < $pin_block_seconds) {
    $pin_blocked = true;
}

// Helper to validate CSRF token is provided by inc/security.php (validate_csrf, csrf_token, etc.)

// Handle login POST
if (isset($_POST['pin'])) {
    // Check CSRF
    if (!validate_csrf()) {
        http_response_code(400);
        $error = 'csrf';
    } elseif ($pin_blocked) {
        $error = 'blocked';
    } else {
        // Validate pin - backend validation only
        $pin_input = (string)$_POST['pin'];
        if (password_verify($pin_input, $config['pin_hash'])) {
            // Successful login
            session_regenerate_id(true);
            $_SESSION['auth'] = true;
            // Reset failed attempts
            $_SESSION['pin_failed'] = 0;
            $_SESSION['pin_last_failed'] = 0;
            header('Location: ' . $base_path . '/');
            exit;
        } else {
            // Failed attempt
            $_SESSION['pin_failed']++;
            $_SESSION['pin_last_failed'] = time();
            $error = true;
        }
    }
}

// Wylogowanie (opcjonalne)
if (isset($_GET['logout'])) {
    // usuń dane sesji
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        // Use setcookie with options array (PHP >= 7.3) to ensure SameSite is also cleared.
        // Fallback to legacy signature for older PHP versions.
        if (PHP_VERSION_ID >= 70300) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? true,
                // Explicitly include samesite so the cookie is removed regardless of its previous attribute
                'samesite' => 'Lax'
            ]);
        } else {
            // Older PHP: same as before (will not explicitly clear SameSite attribute)
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
   }
    session_destroy();
    header('Location: ' . $base_path . '/');
    exit;
}

if (empty($_SESSION['auth'])) {
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shopicker - Logowanie</title>
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
            input {
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
            input:focus {
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
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🛒 Shopicker</h1>
            <p>Wpisz PIN aby kontynuować</p>
            <form method="POST">
                <input type="password" 
                       name="pin" 
                       placeholder="••••" 
                       autofocus 
                       pattern="[0-9]*" 
                       inputmode="numeric"
                       maxlength="6"
                       autocomplete="off">
                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                <br>
                <button type="submit">Wejdź</button>
                <?php if (isset($error) && $error === 'csrf'): ?>
                    <div class="error">❌ Nieprawidłowy token CSRF</div>
                <?php elseif (isset($error) && $error === 'blocked'): ?>
                    <div class="error">❌ Zbyt wiele nieudanych prób. Spróbuj ponownie później.</div>
                <?php elseif (isset($error) && $error === true): ?>
                    <div class="error">❌ Nieprawidłowy PIN</div>
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// === KONIEC AUTENTYKACJI ===

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Używaj absolutnej ścieżki dla pliku danych
$plik_danych = __DIR__ . '/statusy_sklepy.txt';
$produkty_sklepy = require __DIR__ . '/produkty_sklepy.php';

if (!is_array($produkty_sklepy)) {
    die('Błąd: plik produkty_sklepy.php nie zwrócił poprawnej tablicy.');
}

// ============================================
// FUNKCJE POMOCNICZE
// ============================================

function wczytajIlosci($plik) {
    if (!file_exists($plik)) return [];
    $json = @file_get_contents($plik);
    $dane = json_decode($json, true);
    return is_array($dane) ? $dane : [];
}

function zapiszIlosci($plik, $ilosci) {
    foreach ($ilosci as $sklep => $produkty) {
        if (empty($produkty)) {
            unset($ilosci[$sklep]);
        }
    }
    
    $json = json_encode($ilosci, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        error_log("Błąd zapisu JSON: " . json_last_error_msg());
        return false;
    }
    
    // Atomic write: zapisz do pliku tymczasowego, potem rename
    $tmp = $plik . '.tmp';
    $written = @file_put_contents($tmp, $json, LOCK_EX);
    if ($written === false) {
        error_log('Nie udało się zapisać pliku tymczasowego: ' . $tmp);
        return false;
    }
    if (!@rename($tmp, $plik)) {
        // Jeśli rename się nie uda, spróbuj bezpiecznego zapisu
        $result = @file_put_contents($plik, $json, LOCK_EX);
        if ($result === false) {
            error_log('Nie udało się zapisać pliku docelowego: ' . $plik);
            return false;
        }
    }
    return true;
}

function generuj_id_kotwicy($sklep, $produkt) {
    return urlencode($sklep) . '_' . urlencode($produkt);
}

function przekierujZFiltrami() {
    global $base_path;
    
    $parametry = [];
    
    if (!empty($_POST['widoczne_sklepy'])) {
        $parametry['sklepy'] = $_POST['widoczne_sklepy'];
    }
    
    if (!empty($_POST['widoczne_tryb']) && $_POST['widoczne_tryb'] === 'ukryte') {
        $parametry['tryb'] = 'ukryte';
    }
    
    $qs = $parametry ? '?' . http_build_query($parametry) : '';
    header('Location: ' . $base_path . '/' . $qs);
    exit();
}

// ============================================
// OBSŁUGA USTAWIANIA ILOŚCI (POST)
// ============================================

if (isset($_POST['ustaw_ilosc']) && isset($_POST['produkt']) && isset($_POST['ilosc']) && isset($_POST['sklep'])) {
    // CSRF check
    if (!validate_csrf()) {
        http_response_code(400);
        die('Nieprawidłowy token CSRF');
    }
    
    $ilosci_globalne = wczytajIlosci($plik_danych);
    // Do logiki używaj surowych wartości (bez htmlspecialchars)
    $produkt = (string)$_POST['produkt'];
    $sklep = (string)$_POST['sklep'];
    
    if (trim((string)$_POST['ilosc']) === '') {
        $ilosc_input = 1;
    } elseif (is_numeric($_POST['ilosc']) && (int)$_POST['ilosc'] > 0) {
        $ilosc_input = (int)$_POST['ilosc'];
    } else {
        $ilosc_input = '';
    }
    
    $product_exists = false;
    if (isset($produkty_sklepy[$sklep])) {
        foreach ($produkty_sklepy[$sklep] as $item) {
            if ($item['name'] === $produkt) {
                $product_exists = true;
                break;
            }
        }
    }
    
    if ($product_exists) {
        if (!isset($ilosci_globalne[$sklep])) {
            $ilosci_globalne[$sklep] = [];
        }
        
        if ($ilosc_input === '' || (int)$ilosc_input <= 0) {
            unset($ilosci_globalne[$sklep][$produkt]);
        } else {
            $ilosci_globalne[$sklep][$produkt] = (int)$ilosc_input;
        }
        
        zapiszIlosci($plik_danych, $ilosci_globalne);
    }
    
    przekierujZFiltrami();
}

// ============================================
// OBSŁUGA "KUPIONE!" (POST)
// ============================================

if (isset($_POST['oznacz_jako_mam']) && isset($_POST['produkt']) && isset($_POST['sklep'])) {
    // CSRF check
    if (!validate_csrf()) {
        http_response_code(400);
        die('Nieprawidłowy token CSRF');
    }
    
    $ilosci_globalne = wczytajIlosci($plik_danych);
    $produkt = (string)$_POST['produkt'];
    $sklep = (string)$_POST['sklep'];
    
    $product_exists = false;
    if (isset($produkty_sklepy[$sklep])) {
        foreach ($produkty_sklepy[$sklep] as $item) {
            if ($item['name'] === $produkt) {
                $product_exists = true;
                break;
            }
        }
    }
    
    if ($product_exists) {
        if (isset($ilosci_globalne[$sklep][$produkt])) {
            unset($ilosci_globalne[$sklep][$produkt]);
        }
        
        if (!isset($ilosci_globalne[$sklep])) {
            $ilosci_globalne[$sklep] = [];
        }
        
        zapiszIlosci($plik_danych, $ilosci_globalne);
    }
    
    przekierujZFiltrami();
}

// ============================================
// WCZYTANIE AKTUALNYCH ILOŚCI
// ============================================

$aktualne_ilosci = wczytajIlosci($plik_danych);

// ============================================
// FILTROWANIE SKLEPÓW Z GET
// ============================================

$filtr_sklepy = null; // null = pokaż wszystkie (brak parametru)

if (isset($_GET['sklepy'])) {
    // Parametr istnieje
    if ($_GET['sklepy'] === '') {
        $filtr_sklepy = []; // Pusta tablica = ukryj wszystkie
    } else {
        $filtr_sklepy = explode(',', $_GET['sklepy']);
    }
}

// ============================================
// STATYSTYKI (lekkie)
// ============================================

// Najpierw oblicz listę sklepów z produktami (ignorując filtry URL)
$sklepy_z_produktami = [];
foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie) {
    foreach ($produkty_w_sklepie as $item) {
        $produkt = $item['name'];
        $ilosc_obecna = isset($aktualne_ilosci[$sklep_nazwa][$produkt]) 
            ? $aktualne_ilosci[$sklep_nazwa][$produkt] 
            : null;
        
        if ($ilosc_obecna !== null && $ilosc_obecna > 0) {
            $sklepy_z_produktami[] = $sklep_nazwa;
            break; // Ten sklep ma już jakiś produkt, przejdź do następnego
        }
    }
}

// Potem zlicz total z uwzględnieniem filtrów
$do_kupienia_total = 0;
foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie) {
    if (!empty($filtr_sklepy) && !in_array($sklep_nazwa, $filtr_sklepy)) continue;
    
    foreach ($produkty_w_sklepie as $item) {
        $produkt = $item['name'];
        $ilosc_obecna = isset($aktualne_ilosci[$sklep_nazwa][$produkt]) 
            ? $aktualne_ilosci[$sklep_nazwa][$produkt] 
            : null;
        
        if ($ilosc_obecna !== null && $ilosc_obecna > 0) {
            $do_kupienia_total++;
        }
    }
}

?>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Shopicker - lista zakupów</title>

	<!-- Favicons -->
	<link rel="icon" type="image/png" href="<?php echo h($base_path); ?>/assets/favicon-96x96.png" sizes="96x96" />
	<link rel="icon" type="image/svg+xml" href="<?php echo h($base_path); ?>/assets/favicon.svg" />
	<link rel="shortcut icon" href="<?php echo h($base_path); ?>/assets/favicon.ico" />
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo h($base_path); ?>/assets/apple-touch-icon.png" />
	<meta name="apple-mobile-web-app-title" content="Shopicker" />
	<link rel="manifest" href="<?php echo h($base_path); ?>/assets/site.webmanifest" />
	
    <!-- FONT LOADING - dodaj tutaj -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">	
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <style>
        /* ========================================
           IMPORT FONTÓW
           ======================================== */
        
        /*@import url('https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap');*/
        
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
        
		.montserrat-logo {
			font-family: "Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
			font-optical-sizing: auto;
			font-weight: 600;
			font-style: normal;
			margin: 0;
			font-size: 1.8em;
		}
        
        /* ========================================
           STYLE ZE STAREGO CSS (niekonfliktowe)
           ======================================== */
        
        /* Przycisk edycji */
        .przycisk-edytuj { 
            background-color: #2196F3 !important; 
        }
        
        .przycisk-edytuj:hover { 
            background-color: #1976D2 !important; 
        }
        
        .naglowek-kontener { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        
        .przycisk-naglowek { 
            padding: 8px 12px; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block; 
        }
        
        .przycisk-odswiez { 
            background-color: #007bff; 
        }
        
        .przycisk-ukryj { 
            background-color: #5d6a7a; 
        }
        
        .przycisk-odswiez:hover { 
            background-color: #0056b3; 
        }
        
        .przycisk-ukryj:hover { 
            background-color: #434d58; 
        }
        
		/* ========================================
		   STICKY TOP BAR - minimalistyczny
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
			text-decoration: none;  /* ← DODAJ */
			transition: transform 0.2s ease;  /* ← DODAJ */
		}

		.counter-badge:active {  /* ← DODAJ CAŁĄ REGUŁĘ */
			transform: scale(0.95);
		}

		.counter-badge.zero {
			background: #4CAF50;
		}

		.montserrat-logo {
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
        
        /* ========================================
           WYBÓR SKLEPÓW - kompaktowy
           ======================================== */
        
        .sklepy-picker {
            background: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        
        .sklepy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
            margin-top: 8px;
        }
        
        .sklep-chip {
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
        
        .sklep-chip input {
            margin: 0 6px 0 0;
            width: 18px;
            height: 18px;
        }
        
        .sklep-chip:has(input:checked) {
            background: #E3F2FD;
            border-color: #2196F3;
            font-weight: 600;
        }
        
        .sklepy-label {
            font-weight: 600;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-all-shops {
            background: none;
            border: none;
            color: #2196F3;
            font-size: 0.85em;
            cursor: pointer;
            padding: 4px 8px;
        }
        
        /* ========================================
           LISTA PRODUKTÓW - maksymalnie czytelna
           ======================================== */
        
        .sklep-sekcja {
            margin-bottom: 20px;
        }
        
        .sklep-sekcja.ukryty {
            display: none;
        }
        
        .sklep-nazwa {
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
        
        .sklep-counter {
            background: rgba(255,255,255,0.3);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
        }
        
        .lista {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .lista li {
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
        
        .lista li.status-need {
            border-left: 5px solid #FF9800;
            background: #FFF8E1;
        }
        
        .lista li.status-have {
            border-left: 5px solid #4CAF50;
            opacity: 0.6;
        }
        
        .lista li.ukryty {
            display: none;
        }
        
        .nazwa-produktu {
            flex: 1;
            font-size: 1.1em;
            font-weight: 600;
        }
        
        .ilosc-tekst {
            display: block;
            font-size: 0.9em;
            font-weight: 500;
            color: #FF9800;
            margin-top: 4px;
        }
        
        .status-have .ilosc-tekst {
            color: #4CAF50;
        }
        
        .formularz-ilosc {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        /* Przyciski w liście */
        .przycisk {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .przycisk-mam {
            background: #4CAF50;
            color: white;
            min-width: 100px;
        }
        
        .przycisk-mam:active {
            background: #45a049;
            transform: scale(0.95);
        }
        
        .przycisk-zmien {
            background: #2196F3;
            color: white;
        }
        
        .przycisk-zmien:active {
            background: #1976D2;
            transform: scale(0.95);
        }
        
        .wejscie-ilosc {
            width: 60px;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            text-align: center;
            font-weight: 600;
        }
        
        .jednostka-miary {
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
			
			/* H1 w pierwszej linii - całą szerokość */
			.montserrat-logo {
				order: 0;
				width: 100%;
				text-align: center;
				font-size: 1.8em;
				margin-bottom: 4px;
			}
			
			/* Counter i przyciski w drugiej linii */
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
			
			.sklepy-grid {
				grid-template-columns: 1fr 1fr;
			}
			
			.sklep-chip {
				font-size: 0.9em;
				padding: 6px 8px;
			}
			
			.sklep-nazwa {
				font-size: 1.1em;
				padding: 10px 12px;
				top: 44px;
			}
			
			/* NOWY UKŁAD DLA PRODUKTÓW */
			.lista li {
				display: flex;
				flex-direction: column;
				align-items: stretch;
				padding: 12px;
				gap: 10px;
				min-height: auto;
				max-height: none;
				height: auto;
			}
			
			/* Nazwa produktu w pierwszej linii - SAMA, może się zawijać */
			.nazwa-produktu {
				font-size: 1.05em;
				width: 100%;
				display: block;
				word-wrap: break-word;
				overflow-wrap: break-word;
				line-height: 1.4;
			}
			
			/* Ukryj .ilosc-tekst w górnej linii */
			.nazwa-produktu .ilosc-tekst {
				display: none;
			}
			
			/* Formularz w drugiej linii - ZAWSZE widoczny */
			.formularz-ilosc {
				width: 100%;
				display: flex;
				justify-content: space-between;
				align-items: center;
				gap: 8px;
				flex-wrap: nowrap;
				flex-shrink: 0;
			}
			
			/* Tekst ilości/status na początku linii */
			.formularz-ilosc::before {
				font-size: 0.9em;
				font-weight: 500;
				flex-shrink: 0;
			}
			
			.status-need .formularz-ilosc::before {
				content: attr(data-ilosc);
				color: #FF9800;
			}
			
			.status-have .formularz-ilosc::before {
				content: "✓ Mam";
				color: #4CAF50;
			}
			
			.formularz-ilosc form {
				display: flex;
				align-items: center;
				gap: 6px;
				flex-shrink: 0;
			}
			
			/* Przycisk "Kupione" */
			.status-need .przycisk-mam {
				padding: 10px 16px;
				white-space: nowrap;
			}
			
			/* Input i przycisk "Kup" */
			.wejscie-ilosc {
				width: 60px;
				font-size: 1em;
				flex-shrink: 0;
			}
			
			.jednostka-miary {
				min-width: auto;
				flex-shrink: 0;
			}
			
			.przycisk-zmien {
				padding: 10px 14px;
				white-space: nowrap;
				flex-shrink: 0;
			}
			
			/* Style ze starego CSS dla mobile */
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
        
        @keyframes kupiono {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .kupiono-anim {
            animation: kupiono 0.3s ease;
        }

    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- STICKY TOP BAR -->
    <!-- ============================================ -->
    
    <div class="top-bar">
		<a href="#" 
		   onclick="resetSklepy(event)" 
		   title="Sprawdź" 
		   class="counter-badge <?php echo $do_kupienia_total === 0 ? 'zero' : ''; ?>">
			<?php if ($do_kupienia_total > 0): ?>
				🛒 <?php echo $do_kupienia_total; ?>
			<?php else: ?>
				✓ Gotowe!
			<?php endif; ?>
		</a>		
		<h1 class="montserrat-logo">
			<img src="<?php echo h($base_path); ?>/assets/favicon.svg" 
				 alt="Logo" 
				 style="height: 1.5em; vertical-align: middle; margin-right: -0.2em">
			Shopicker
		</h1>
		<div class="top-actions">
			<button class="btn-top btn-toggle" onclick="toggleUkryj()" id="btnToggle">
				👁️
			</button>
			<button class="btn-top btn-refresh" onclick="odswiezListe()" title="Odśwież listę">
				🔄
			</button>
			<a href="<?php echo h($base_path); ?>/edytuj.php" class="btn-top btn-edit">
				✏️
			</a>
			<a href="<?php echo h($base_path); ?>/?logout" class="btn-top btn-logout" style="background: #f44336;">
				🚪
			</a>
		</div>
    </div>

    <!-- ============================================ -->
    <!-- WYBÓR SKLEPÓW -->
    <!-- ============================================ -->
    
    <div class="sklepy-picker">
        <div class="sklepy-label">
            🏪 Sklepy
            <button class="btn-all-shops" onclick="toggleAllShops()">wszystkie</button>
        </div>
        <div class="sklepy-grid">
            <?php foreach (array_keys($produkty_sklepy) as $sklep_nazwa): ?>
                <label class="sklep-chip">
                    <input type="checkbox" 
                           class="checkboxSklep" 
                           value="<?php echo h($sklep_nazwa); ?>">
                    <span><?php echo h($sklep_nazwa); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- LISTY PRODUKTÓW -->
    <!-- ============================================ -->

	<?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie): ?>
		<?php 
		if ($filtr_sklepy !== null) {
			// Parametr sklepy istnieje w URL
			if (empty($filtr_sklepy)) {
				// Pusta lista = ukryj wszystko
				continue;
			} elseif (!in_array($sklep_nazwa, $filtr_sklepy)) {
				// Sklep nie jest na liście wybranych
				continue;
			}
		}
		// $filtr_sklepy === null -> pokaż wszystko (brak parametru)
		?>
        
        <?php
        $do_kupienia_sklep = 0;
        foreach ($produkty_w_sklepie as $item) {
            $produkt = $item['name'];
            $ilosc_obecna = isset($aktualne_ilosci[$sklep_nazwa][$produkt]) 
                ? $aktualne_ilosci[$sklep_nazwa][$produkt] 
                : null;
            if ($ilosc_obecna !== null && $ilosc_obecna > 0) {
                $do_kupienia_sklep++;
            }
        }
        ?>
        
        <div class="sklep-sekcja" data-sklep="<?php echo h($sklep_nazwa); ?>">
            <h2 class="sklep-nazwa">
                <span><?php echo h($sklep_nazwa); ?></span>
                <?php if ($do_kupienia_sklep > 0): ?>
                    <span class="sklep-counter"><?php echo $do_kupienia_sklep; ?></span>
                <?php endif; ?>
            </h2>
            <ul class="lista">
                <?php foreach ($produkty_w_sklepie as $item): 
                    $produkt = $item['name'];
                    $jednostka = $item['unit'];
                    
                    $ilosc_obecna = isset($aktualne_ilosci[$sklep_nazwa][$produkt]) 
                        ? $aktualne_ilosci[$sklep_nazwa][$produkt] 
                        : null;
                    
                    $czy_potrzebny = ($ilosc_obecna !== null && $ilosc_obecna > 0);
                    $klasa_css = $czy_potrzebny ? 'status-need' : 'status-have';
                    $ilosc_tekst = $czy_potrzebny 
                        ? "$ilosc_obecna $jednostka" 
                        : "✓ Mam";
                    $wartosc_input = $czy_potrzebny ? $ilosc_obecna : '';
                    $id_elementu = generuj_id_kotwicy($sklep_nazwa, $produkt);
                ?>
                
                <li id="<?php echo h($id_elementu); ?>" class="<?php echo $klasa_css; ?>">
                    <span class="nazwa-produktu">
                        <?php echo h($produkt); ?>
                        <span class="ilosc-tekst"><?php echo h($ilosc_tekst); ?></span>
                    </span>
                    
                    <div class="formularz-ilosc" data-ilosc="<?php echo $czy_potrzebny ? h($ilosc_tekst) : ''; ?>">
                        <?php if ($czy_potrzebny): ?>
                            <form method="POST" style="display:inline;" onsubmit="animKupiono(this)">
                                <input type="hidden" name="produkt" value="<?php echo h($produkt); ?>">
                                <input type="hidden" name="sklep" value="<?php echo h($sklep_nazwa); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                                <button type="submit" name="oznacz_jako_mam" class="przycisk przycisk-mam">
                                    ✓ Kupione
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;" onsubmit="saveScroll()">
                                <input type="number" 
                                       name="ilosc" 
                                       value="<?php echo h($wartosc_input); ?>" 
                                       min="0" 
                                       class="wejscie-ilosc"
                                       placeholder="1">
                                <span class="jednostka-miary"><?php echo h($jednostka); ?></span>
                                <input type="hidden" name="produkt" value="<?php echo h($produkt); ?>">
                                <input type="hidden" name="sklep" value="<?php echo h($sklep_nazwa); ?>">
                                <input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
                                <button type="submit" name="ustaw_ilosc" class="przycisk przycisk-zmien">
                                    Kup
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

		// Ścieżka bazowa (z PHP) - bezpiecznie enkodowana
		const BASE_PATH = <?php echo json_encode($base_path); ?>;
		
		// Token CSRF z sesji (do addHiddenFields jeśli potrzeba)
		const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
		
		// Sklepy z produktami do kupienia (z PHP)
		const SKLEPY_Z_PRODUKTAMI = <?php echo json_encode($sklepy_z_produktami); ?>;

		(function() {
			const pos = sessionStorage.getItem('shoppingList_scrollPos');
			if (pos) {
				document.documentElement.scrollTop = parseInt(pos);
				document.body.scrollTop = parseInt(pos);
			}
		})();

        const STORAGE_HIDE = 'listaZakupow_ukryte';
        const STORAGE_SCROLL = 'shoppingList_scrollPos';
        const STORAGE_SKLEPY = 'karteczka_wybrane_sklepy';
        
        const checkboxes = document.querySelectorAll('.checkboxSklep');
        
        // ========================================
        // Toggle ukrywania
        // ========================================
        
        function toggleUkryj() {
            const mam = document.querySelectorAll('.status-have');
            const anyVisible = Array.from(mam).some(el => !el.classList.contains('ukryty'));
            
            mam.forEach(el => {
                if (anyVisible) {
                    el.classList.add('ukryty');
                } else {
                    el.classList.remove('ukryty');
                }
            });
            
            localStorage.setItem(STORAGE_HIDE, anyVisible ? 'ukryte' : 'pokazane');
            ukryjPusteSklepy();
        }
        
        function ukryjPusteSklepy() {
            document.querySelectorAll('.sklep-sekcja').forEach(sekcja => {
                const widoczne = sekcja.querySelectorAll('li:not(.ukryty)');
                sekcja.classList.toggle('ukryty', widoczne.length === 0);
            });
        }
        
		// ========================================
		// Sklepy
		// ========================================

		function loadSklepy() {
			const urlParams = new URLSearchParams(window.location.search);
			const fromUrl = urlParams.get('sklepy');
			
			if (fromUrl !== null) {
				// Parametr istnieje w URL (nawet jeśli pusty)
				localStorage.setItem(STORAGE_SKLEPY, fromUrl);
				const lista = fromUrl.split(',').filter(s => s.trim() !== '');
				checkboxes.forEach(ch => ch.checked = lista.includes(ch.value));
			} else {
				// Brak parametru w URL - sprawdź localStorage
				const saved = localStorage.getItem(STORAGE_SKLEPY);
				if (saved !== null) {
					// Jest w localStorage
					const lista = saved.split(',').filter(s => s.trim() !== '');
					checkboxes.forEach(ch => ch.checked = lista.includes(ch.value));
				} else {
					// Pierwsze uruchomienie - zaznacz wszystkie
					checkboxes.forEach(ch => ch.checked = true);
				}
			}
			
			updateToggleButton();
		}

		function saveSklepy() {
			const wybrane = Array.from(checkboxes)
				.filter(ch => ch.checked)
				.map(ch => ch.value);
			
			const sklepyParam = wybrane.join(',');
			localStorage.setItem(STORAGE_SKLEPY, sklepyParam);
			
			// ZAWSZE przekazuj parametr sklepy (nawet pusty)
			const url = BASE_PATH + '/?sklepy=' + encodeURIComponent(sklepyParam);
			sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
			window.location.href = url;
		}

		function toggleAllShops() {
			const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
			
			if (anyChecked) {
				// Są jakieś zaznaczone - ODZNACZ wszystkie
				checkboxes.forEach(ch => ch.checked = false);
			} else {
				// Żadne nie zaznaczone - ZAZNACZ wszystkie
				checkboxes.forEach(ch => ch.checked = true);
			}
			
			updateToggleButton();
			saveSklepy();
		}
		
		function odswiezListe() {
			sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
			location.reload();
		}		

		function updateToggleButton() {
			const btnToggle = document.querySelector('.btn-all-shops');
			if (!btnToggle) return;
			
			const anyChecked = Array.from(checkboxes).some(ch => ch.checked);
			btnToggle.textContent = anyChecked ? 'odznacz wszystkie' : 'zaznacz wszystkie';
		}

		checkboxes.forEach(ch => {
			ch.addEventListener('change', () => {
				updateToggleButton();
				saveSklepy();
			});
		});


		// ========================================
		// Reset sklepów (badge click)
		// ========================================

		function resetSklepy(event) {
			event.preventDefault();
			
			// Zaznacz TYLKO sklepy z produktami do kupienia
			const sklepyParam = SKLEPY_Z_PRODUKTAMI.join(',');
			localStorage.setItem(STORAGE_SKLEPY, sklepyParam);
			
			// Ustaw widok na "ukryte" (tylko potrzebne)
			localStorage.setItem(STORAGE_HIDE, 'ukryte');
			
			// Scroll na górę
			sessionStorage.setItem(STORAGE_SCROLL, 0);
			
			// Przekieruj z parametrem sklepy
			window.location.href = BASE_PATH + '/?sklepy=' + encodeURIComponent(sklepyParam);
		}
        
        // ========================================
        // Scroll & animacje
        // ========================================
        
        function saveScroll() {
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
        }
        
		function restoreScroll() {
			const pos = sessionStorage.getItem(STORAGE_SCROLL);
			if (pos) {
				const scrollPos = parseInt(pos);
				
				// Dodatkowe wymuszenie na wszelki wypadek
				window.scrollTo({
					top: scrollPos,
					behavior: 'instant'
				});
				
				setTimeout(() => {
					sessionStorage.removeItem(STORAGE_SCROLL);
				}, 100);
			}
		}
        
        function animKupiono(form) {
            const li = form.closest('li');
            if (li) li.classList.add('kupiono-anim');
            saveScroll();
        }
        
        window.addEventListener('scroll', () => {
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
        });
        
        // ========================================
        // Ukryte pola w formularzach (dodaj widoczne_sklepy i CSRF jeśli nie ma)
        // ========================================
        
        function addHiddenFields() {
            const sklepy = localStorage.getItem(STORAGE_SKLEPY) || '';
            document.querySelectorAll('form').forEach(f => {
                if (!f.querySelector('input[name="widoczne_sklepy"]')) {
                    const h = document.createElement('input');
                    h.type = 'hidden';
                    h.name = 'widoczne_sklepy';
                    h.value = sklepy;
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
        // Init
        // ========================================
        
        document.addEventListener('DOMContentLoaded', () => {
            loadSklepy();
            
            const hideState = localStorage.getItem(STORAGE_HIDE);
            if (hideState === 'ukryte') {
                document.querySelectorAll('.status-have').forEach(el => el.classList.add('ukryty'));
            }
            ukryjPusteSklepy();
            
            addHiddenFields();
            restoreScroll();
        });
    </script>

</body>
</html>