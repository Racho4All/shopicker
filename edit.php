<?php
// ============================================
// SHOPICKER - Edytor listy produktów / Product List Editor
// Wersja / Version: 2.5.0
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
                        <li>' . h(__('editor.go_to_main')) . '</li>
                        <li>' . h(__('config.step_3')) . '</li>
                        <li>' . h(__('config.step_4')) . '</li>
                    </ol>
                </div>
                
                <p style="margin-top: 20px; text-align: center; font-size: 0.9em; color: #999;">
                    ' . h(__('config.contact_admin')) . '
                </p>
            </div>
        </body>
        </html>
        ');
    }
}
// === KONIEC / END ===

// === SPRAWDZENIE AUTORYZACJI / AUTH CHECK ===
if (empty($_SESSION['auth'])) {
    // Nie zalogowany - przekieruj na główną stronę / Not logged in - redirect to main
    header('Location: ' . $base_path . '/');
    exit;
}
// === KONIEC AUTH / END AUTH ===

$expand_stores = '';
if (isset($_GET['expand'])) {
    $expand_stores = $_GET['expand'];
} elseif (isset($_POST['_expand_stores'])) {
    $expand_stores = $_POST['_expand_stores'];
}
$return_url = $base_path . '/' . ($expand_stores !== '' ? '?sklepy=' . rawurlencode($expand_stores) : '');

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Zachowaj sklepy do powrotu / Keep stores for return
$expand_stores = '';
if (isset($_GET['expand'])) {
    $expand_stores = $_GET['expand'];
} elseif (isset($_POST['_expand_stores'])) {
    $expand_stores = $_POST['_expand_stores'];
}
$return_url = $base_path . '/' . ($expand_stores !== '' ? '?sklepy=' . rawurlencode($expand_stores) : '');

$config_products_file = __DIR__ . '/produkty_sklepy.php';
$backup_version = __DIR__ . '/produkty_sklepy_backup_' . date('Y-m-d_His') . '.php';

// ============================================
// FUNKCJE POMOCNICZE / HELPER FUNCTIONS
// ============================================

/**
 * Zapisuje konfigurację produktów do pliku PHP / Save products config to PHP file
 */
function saveConfiguration($file, $data) {
    $php_code = "<?php\n";
    $php_code .= "// ============================================\n";
    $php_code .= "// KONFIGURACJA: Lista sklepów i produktów\n";
    $php_code .= "// CONFIG: Stores and products list\n";
    $php_code .= "// Ostatnia edycja / Last edit: " . date('Y-m-d H:i:s') . "\n";
    $php_code .= "// ============================================\n\n";
    $php_code .= "return [\n";
    
    foreach ($data as $store => $products) {
        // Użyj bezpiecznego enkodowania pojedynczych apostrofów
        $php_code .= "    '" . str_replace("'", "\\'", $store) . "' => [\n";
        foreach ($products as $product) {
            $name = str_replace("'", "\\'", $product['name']);
            $unit = str_replace("'", "\\'", $product['unit']);
            $php_code .= "        ['name' => '" . $name . "', 'unit' => '" . $unit . "'],\n";
        }
        $php_code .= "    ],\n\n";
    }
    
    $php_code .= "];\n";

    // Atomic write: zapisz do tmp, potem rename
    $tmp = $file . '.tmp';
    $written = @file_put_contents($tmp, $php_code, LOCK_EX);
    if ($written === false) {
        error_log("Failed to write temp file: $tmp");
        return false;
    }
    if (!@rename($tmp, $file)) {
        // Fallback
        $result = @file_put_contents($file, $php_code, LOCK_EX);
        if ($result === false) {
            error_log("Failed to write target file: $file");
            return false;
        }
    }
    return true;
}

/**
 * Waliduje dane formularza / Validate form data
 */
function validateData($post_data) {
    $errors = [];
    
    if (empty($post_data['stores']) || !is_array($post_data['stores'])) {
        $errors[] = __('editor.error_no_stores');
        return $errors;
    }
    
    foreach ($post_data['stores'] as $index => $store) {
        $number = $index + 1;
        
        if (empty(trim((string)$store['name']))) {
            $errors[] = __('editor.error_empty_store', ['number' => $number]);
        }
        
        if (isset($store['products']) && is_array($store['products'])) {
            foreach ($store['products'] as $p_index => $product) {
                $p_number = $p_index + 1;
                if (empty(trim((string)$product['name']))) {
                    $errors[] = __('editor.error_empty_product', ['store' => $store['name'], 'number' => $p_number]);
                }
                if (empty(trim((string)$product['unit']))) {
                    $errors[] = __('editor.error_empty_unit', ['store' => $store['name'], 'number' => $p_number]);
                }
            }
        }
    }
    
    return $errors;
}

// ============================================
// OBSŁUGA ZAPISU / SAVE HANDLING
// ============================================
$message = '';
$message_type = '';
$saved_successfully = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Sprawdź CSRF / Check CSRF
    if (!validate_csrf()) {
        $message = __('errors.csrf_invalid');
        $message_type = 'error';
    } else {
        $errors = validateData($_POST);
        
        if (empty($errors)) {
            // Utwórz backup jeśli istnieje / Create backup if exists
            if (file_exists($config_products_file)) {
                @copy($config_products_file, $backup_version);
            }
            
            // Przygotuj dane / Prepare data
            $new_data = [];
            foreach ($_POST['stores'] as $store) {
                $store_name = trim((string)$store['name']);
                if ($store_name === '') continue;
                
                $new_data[$store_name] = [];
                
                if (isset($store['products']) && is_array($store['products'])) {
                    foreach ($store['products'] as $product) {
                        $product_name = trim((string)$product['name']);
                        $unit = trim((string)$product['unit']);
                        
                        if ($product_name !== '' && $unit !== '') {
                            $new_data[$store_name][] = [
                                'name' => $product_name,
                                'unit' => $unit
                            ];
                        }
                    }
                }
            }
            
            // Zapisz / Save
            if (saveConfiguration($config_products_file, $new_data)) {
                $message = __('editor.save_success');
                $message_type = 'success';
                $saved_successfully = true;
            } else {
                $message = __('editor.save_error');
                $message_type = 'error';
            }
        } else {
            $message = implode("\n", $errors);
            $message_type = 'error';
        }
    }
}

// ============================================
// WCZYTANIE AKTUALNYCH DANYCH / LOAD CURRENT DATA
// ============================================
$products_by_store = require $config_products_file;
if (!is_array($products_by_store)) {
    die(h(__('config.error_products_file')));
}

// Przygotuj tłumaczenia dla JavaScript / Prepare translations for JavaScript
$js_translations = I18n::getInstance()->get('editor_js');
if (!is_array($js_translations)) {
    $js_translations = [];
}

?>
<!DOCTYPE html>
<html lang="<?php echo h(getCurrentLang()); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php _e('editor.title'); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo h($base_path); ?>/assets/favicon.svg" />
	
    <!-- Ładowanie fontów / Font loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">	

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		/* ========================================
		   ZMIENNE I RESET / VARIABLES AND RESET
		   ======================================== */
		
		:root {
			--primary-color: #4CAF50;
			--primary-hover: #45a049;
			--secondary-color: #2196F3;
			--secondary-hover: #1976D2;
			--accent-color: #FF9800;
			--accent-hover: #F57C00;
			--purple-color: #9C27B0;
			--purple-hover: #7B1FA2;
			--danger-color: #FF6B6B;
			--danger-hover: #EE5A52;
			--danger-bg: #FFE5E5;
			--warning-color: #FF9800;
			--warning-bg: #FFF3E0;
			--border-color: #ddd;
			--bg-light: #f9f9f9;
			--bg-lighter: #fafafa;
			--shadow: 0 2px 8px rgba(0,0,0,0.1);
			--shadow-hover: 0 4px 12px rgba(0,0,0,0.15);
			--radius: 8px;
			--transition: all 0.3s ease;
		}

		* {
			box-sizing: border-box;
			-webkit-tap-highlight-color: transparent;
		}

		body {
			font-family: sans-serif;
			margin: 0 auto;
			padding: 0;
			line-height: 1.4;
			padding-bottom: 100px;
			max-width: 670px;
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
		   STICKY TOP BAR
		   ======================================== */
		
		.header-container {
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
			margin-bottom: 20px;
		}
		
		.header-container h1 {
			order: 2;
		}
		
		.header-container > div {
			order: 3;
		}
		
		.btn-header {
			padding: 10px 16px;
			background: var(--secondary-color);
			color: white;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-size: 0.95em;
			font-weight: 500;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			transition: var(--transition);
			white-space: nowrap;
		}
		
		.btn-header:hover {
			background: var(--secondary-hover);
			transform: translateY(-1px);
			box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
		}
		
		.btn-header:active {
			transform: scale(0.95);
		}

		/* ========================================
		   WSKAŹNIK AKTUALNEGO SKLEPU / CURRENT STORE INDICATOR
		   ======================================== */
		
		#currentStoreIndicator {
			font-size: 1.1em;
			color: #333;
			font-weight: 600;
			transition: var(--transition);
			opacity: 0;
			visibility: hidden;
			min-height: 1.5em;
			background: rgba(0, 0, 0, 0.08);
			padding: 8px 16px;
			border-radius: 20px;
			display: inline-flex;
			align-items: center;
			gap: 8px;
			white-space: nowrap;
		}
		
		#currentStoreIndicator.visible {
			opacity: 1;
			visibility: visible;
		}
		
		/* Desktop - tytuł i przycisk w pierwszej linii, sklep w drugiej */
		@media (min-width: 769px) {
			.header-container h1 {
				order: 1;
				flex: 0 0 auto;
			}

			.header-container > div:last-child {
				order: 2;
				margin-left: auto;
			}

			#currentStoreIndicator {
				order: 3;
				width: 100%;
				margin-top: 0;
				margin-left: 0.7em;
				font-size: 1.3em;
				text-align: left;
			}
		}
		
		/* Mobile - pod tytułem */
		@media (max-width: 768px) {
			#currentStoreIndicator {
				order: 1;
				width: 100%;
				text-align: center;
				margin-bottom: 8px;
				font-size: 1.1em;
			}
		}

		/* ========================================
		   EDYTOR - GŁÓWNY KONTENER / EDITOR - MAIN CONTAINER
		   ======================================== */
		
		.editor-container {
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}
		
		/* ========================================
		   TOOLBAR - FILTR I AKCJE / TOOLBAR - FILTER AND ACTIONS
		   ======================================== */
		
		.toolbar {
			background: white;
			border-radius: var(--radius);
			padding: 16px;
			margin-bottom: 20px;
			box-shadow: var(--shadow);
			position: sticky;
			top: 102px;
			z-index: 90;
			display: flex;
			gap: 12px;
			flex-wrap: wrap;
			align-items: center;
		}
		
		.search-box {
			flex: 1;
			min-width: 200px;
			position: relative;
		}
		
		.search-box input {
			width: 100%;
			padding: 12px 44px 12px 44px;
			border: 2px solid var(--border-color);
			border-radius: var(--radius);
			font-size: 1em;
			transition: var(--transition);
		}
		
		.search-box input:focus {
			outline: none;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
		}
		
		.search-icon {
			position: absolute;
			left: 16px;
			top: 50%;
			transform: translateY(-50%);
			font-size: 1.2em;
			color: #999;
			pointer-events: none;
		}
		
		.search-clear {
			position: absolute;
			right: 12px;
			top: 50%;
			transform: translateY(-50%);
			background: #ddd;
			color: #666;
			border: none;
			width: 24px;
			height: 24px;
			border-radius: 50%;
			cursor: pointer;
			font-size: 0.9em;
			display: none;
			align-items: center;
			justify-content: center;
			transition: var(--transition);
			padding: 0;
			line-height: 1;
		}
		
		.search-clear:hover {
			background: #ccc;
			color: #333;
		}
		
		.search-clear.visible {
			display: flex;
		}
		
		.toolbar-actions {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}
		
		.btn-toolbar {
			padding: 10px 16px;
			background: var(--secondary-color);
			color: white;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-size: 0.95em;
			font-weight: 500;
			transition: var(--transition);
			white-space: nowrap;
		}
		
		.btn-toolbar:hover {
			background: var(--secondary-hover);
			transform: scale(1.02);
		}
		
		.btn-toolbar:active {
			transform: scale(0.95);
		}
		
		.btn-toolbar.active {
			background: var(--primary-color);
		}
		
		/* ========================================
		   SKLEPY - SKŁADANE / STORES - COLLAPSIBLE
		   ======================================== */
		
		.store-editor {
			background: white;
			border: 2px solid var(--border-color);
			border-radius: var(--radius);
			margin-bottom: 12px;
			transition: var(--transition);
			box-shadow: var(--shadow);
		}
		
		.store-editor.hidden {
			display: none;
		}
		
		.store-editor:hover {
			box-shadow: var(--shadow-hover);
		}
		
		.store-editor.dragging {
			opacity: 0.6;
			border-color: var(--primary-color);
			box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
		}
		
		.store-editor.drag-over {
			border-color: var(--secondary-color);
			background: #e3f2fd;
			border-style: dashed;
		}
		
		.store-editor.collapsed .store-content {
			display: none;
		}
		
		.store-editor.collapsed .store-header {
			border-bottom: none;
			border-radius: var(--radius);
		}
		
		.store-header {
			background: linear-gradient(135deg, #f8f8fc 0%, #e8e8f5 100%);
			color: #333;
			display: flex;
			align-items: center;
			gap: 8px;
			padding: 12px 16px;
			border-radius: var(--radius) var(--radius) 0 0;
			cursor: pointer;
			user-select: none;
			box-shadow: 0 2px 6px rgba(0,0,0,0.15);
		}
		
		.toggle-icon {
			font-size: 1.2em;
			color: #333;
			transition: transform 0.3s ease;
			cursor: pointer;
			padding: 4px;
		}
		
		.store-editor.collapsed .toggle-icon {
			transform: rotate(-90deg);
		}
		
		.store-header-drag {
			cursor: grab;
			font-size: 1.3em;
			color: #666;
			padding: 4px 8px;
			transition: var(--transition);
			border-radius: 4px;
		}

		.store-header-drag:hover {
			color: #333;
			background: rgba(0, 0, 0, 0.05);
		}

		.store-header-drag:active {
			cursor: grabbing;
		}
		
		.store-header input {
			flex: 1;
			font-size: 1.1em;
			font-weight: 600;
			padding: 8px 12px;
			border: 2px solid #ddd;
			border-radius: 6px;
			transition: var(--transition);
			background: white;
			min-width: 150px;
			color: #333;
		}

		.store-header input:focus {
			outline: none;
			background: white;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
		}

		.product-counter {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(0, 0, 0, 0.08);
			padding: 6px 12px;
			border-radius: 20px;
			font-size: 0.9em;
			color: #333;
			font-weight: 600;
			white-space: nowrap;
		}
		
		.store-actions {
			display: flex;
			gap: 6px;
		}
		
		.btn-delete-store {
			background: var(--danger-bg);
			color: var(--danger-color);
			border: 2px solid var(--danger-color);
			padding: 6px 12px;
			border-radius: 6px;
			cursor: pointer;
			font-size: 0.9em;
			font-weight: 600;
			transition: var(--transition);
			white-space: nowrap;
		}
		
		.btn-delete-store:hover {
			background: var(--danger-color);
			color: white;
			box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
			transform: scale(1.05);
		}
		
		.btn-delete-store:active {
			transform: scale(0.95);
		}
		
		/* ========================================
		   ZAWARTOŚĆ SKLEPU / STORE CONTENT
		   ======================================== */
		
		.store-content {
			padding: 16px;
			background: white;
		}
		
		.add-product-bottom {
			background: var(--bg-lighter);
			border-radius: var(--radius);
			padding: 12px;
			margin-top: 12px;
		}
		
		/* Ukryj przycisk na dole gdy lista jest pusta */
		.empty-store-info ~ .add-product-bottom {
			display: none;
		}
		
		.products-container {
			background: var(--bg-lighter);
			border-radius: var(--radius);
			padding: 12px;
			margin-bottom: 12px;
		}
		
		.product-editor {
			display: grid;
			grid-template-columns: auto 1fr 120px auto;
			gap: 8px;
			margin-bottom: 10px;
			align-items: center;
			background: white;
			padding: 10px;
			border-radius: 6px;
			border: 1px solid var(--border-color);
			transition: var(--transition);
			position: relative;
		}
		
		.product-editor:last-child {
			margin-bottom: 0;
		}
		
		.product-editor:hover {
			box-shadow: var(--shadow);
		}
		
		.product-editor.dragging {
			opacity: 0.6;
			box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
		}
		
		.product-editor.duplicate-warning {
			border-color: var(--warning-color);
			background: var(--warning-bg);
		}
		
		.product-drag-handle {
			cursor: grab;
			font-size: 1.1em;
			color: #bbb;
			padding: 4px 6px;
			user-select: none;
			transition: var(--transition);
			border-radius: 4px;
		}
		
		.product-drag-handle:hover {
			color: var(--primary-color);
			background: var(--bg-light);
		}
		
		.product-drag-handle:active {
			cursor: grabbing;
		}
		
		.product-editor input[type="text"] {
			padding: 8px 10px;
			border: 1px solid var(--border-color);
			border-radius: 4px;
			width: 100%;
			font-size: 0.95em;
			transition: var(--transition);
		}
		
		.product-editor input[type="text"]:focus {
			outline: none;
			border-color: var(--primary-color);
			box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
		}
		
		/* ========================================
		   PRZYCISKI AKCJI PRODUKTU / PRODUCT ACTION BUTTONS
		   ======================================== */

		.product-actions {
			display: flex;
			gap: 6px;
			align-items: center;
		}

		.btn-delete-product {
			background: var(--danger-bg);
			color: var(--danger-color);
			border: 2px solid var(--danger-color);
			padding: 8px 12px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 0.9em;
			font-weight: 600;
			transition: var(--transition);
			white-space: nowrap;
		}
		
		.btn-delete-product:hover {
			background: var(--danger-color);
			color: white;
			box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
			transform: scale(1.05);
		}

		.btn-delete-product:active {
			transform: scale(0.95);
		}

		.btn-add-below {
			background: var(--primary-color);
			color: white;
			border: 2px solid var(--primary-color);
			padding: 8px 12px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 0.9em;
			font-weight: 600;
			transition: var(--transition);
			white-space: nowrap;
		}

		.btn-add-below:hover {
			background: var(--primary-hover);
			box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
			transform: scale(1.05);
		}

		.btn-add-below:active {
			transform: scale(0.95);
		}
		
		.btn-add {
			background: var(--primary-color);
			color: white;
			width: 100%;
			padding: 10px;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-weight: 600;
			transition: var(--transition);
			font-size: 0.95em;
		}
		
		.btn-add:hover {
			background: var(--primary-hover);
			box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
			transform: scale(1.02);
		}
		
		.btn-add:active {
			transform: scale(0.95);
		}
		
		.empty-store-info {
			text-align: center;
			padding: 24px;
			background: white;
			border-radius: var(--radius);
			color: #999;
			font-style: italic;
		}
		
		/* ========================================
		   OSTRZEŻENIE O DUPLIKACIE / DUPLICATE WARNING
		   ======================================== */
		
		.duplicate-badge {
			position: absolute;
			top: -8px;
			right: -8px;
			background: var(--warning-color);
			color: white;
			font-size: 0.75em;
			padding: 3px 8px;
			border-radius: 12px;
			font-weight: 600;
			box-shadow: 0 2px 4px rgba(0,0,0,0.2);
			z-index: 10;
		}
		
		/* ========================================
		   PRZYCISKI GŁÓWNE / MAIN BUTTONS
		   ======================================== */
		
		.btn-add-store {
			background: var(--secondary-color);
			color: white;
			border: none;
			padding: 14px 24px;
			border-radius: var(--radius);
			cursor: pointer;
			font-size: 1.05em;
			font-weight: 600;
			margin: 20px 0;
			width: 100%;
			transition: var(--transition);
		}
		
		.btn-add-store:hover {
			background: var(--secondary-hover);
			box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
			transform: scale(1.02);
		}
		
		.btn-add-store:active {
			transform: scale(0.95);
		}
		
		.action-buttons {
			display: flex;
			gap: 12px;
			margin: 30px 0;
		}
		
		.btn-save {
			background: var(--primary-color);
			color: white;
			border: none;
			padding: 16px 32px;
			border-radius: var(--radius);
			cursor: pointer;
			font-size: 1.1em;
			font-weight: 600;
			flex: 1;
			transition: var(--transition);
		}
		
		.btn-save:hover {
			background: var(--primary-hover);
			box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
			transform: scale(1.02);
		}
		
		.btn-save:active {
			transform: scale(0.95);
		}
		
		.btn-cancel {
			background: #757575;
			color: white;
			border: none;
			padding: 16px 32px;
			border-radius: var(--radius);
			cursor: pointer;
			font-size: 1.1em;
			font-weight: 600;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex: 1;
			transition: var(--transition);
		}
		
		.btn-cancel:hover {
			background: #616161;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
			transform: scale(1.02);
		}
		
		.btn-cancel:active {
			transform: scale(0.95);
		}
		
		/* ========================================
		   KOMUNIKATY / MESSAGES
		   ======================================== */
		
		.message {
			padding: 20px;
			border-radius: var(--radius);
			margin-bottom: 24px;
			box-shadow: var(--shadow);
			animation: slideIn 0.3s ease-out;
		}
		
		@keyframes slideIn {
			from {
				opacity: 0;
				transform: translateY(-10px);
			}
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
		
		.message.success {
			background: #d4edda;
			border: 2px solid #c3e6cb;
			color: #155724;
		}
		
		.message.error {
			background: #f8d7da;
			border: 2px solid #f5c6cb;
			color: #721c24;
		}
		
		.btn-return-success {
			display: inline-block;
			background: var(--primary-color);
			color: white;
			padding: 12px 24px;
			border-radius: 6px;
			text-decoration: none;
			font-weight: 600;
			transition: var(--transition);
			margin-top: 12px;
		}
		
		.btn-return-success:hover {
			background: var(--primary-hover);
			box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
			transform: scale(1.05);
		}
		
		.btn-return-success:active {
			transform: scale(0.95);
		}
		
		/* ========================================
		   PŁYWAJĄCY PRZYCISK / FLOATING BUTTON
		   ======================================== */
		
		.floating-save {
			position: fixed;
			bottom: 20px;
			right: 20px;
			z-index: 1000;
			animation: fadeInUp 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
		}
		
		@keyframes fadeInUp {
			from {
				opacity: 0;
				transform: translateY(30px) scale(0.9);
			}
			to {
				opacity: 1;
				transform: translateY(0) scale(1);
			}
		}
		
		.btn-floating-save {
			background: var(--primary-color);
			color: white;
			border: none;
			padding: 16px 28px;
			border-radius: 50px;
			cursor: pointer;
			font-size: 1.1em;
			font-weight: 600;
			box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
			transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
			display: flex;
			align-items: center;
			gap: 10px;
		}
		
		.btn-floating-save:hover {
			background: var(--primary-hover);
			transform: translateY(-3px) scale(1.05);
			box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
		}
		
		.btn-floating-save:active {
			transform: translateY(-1px) scale(1.02);
		}
		
		/* ========================================
		   INFO O BRAKU WYNIKÓW / NO RESULTS INFO
		   ======================================== */
		
		.no-results {
			text-align: center;
			padding: 60px 20px;
			color: #999;
		}
		
		.no-results-icon {
			font-size: 4em;
			margin-bottom: 16px;
		}
		
		.no-results h3 {
			margin: 0 0 8px 0;
			color: #666;
		}
		
		.no-results p {
			margin: 0;
			font-size: 0.95em;
		}
		
		/* ========================================
		   RESPONSYWNOŚĆ MOBILE / MOBILE RESPONSIVENESS
		   ======================================== */
		
		@media (max-width: 768px) {
			/* NAGŁÓWEK RESPONSYWNY */
			.header-container {
				padding: 8px 12px;
			}
			
			.header-container h1 {
				order: 0;
				width: 100%;
				text-align: center;
				font-size: 1.8em;
				margin-bottom: 4px;
			}
			
			#currentStoreIndicator {
				order: 1;
				width: 100%;
				text-align: center;
				margin-bottom: 8px;
			}
			
			.header-container > div {
				order: 2;
				width: 100%;
			}
			
			.btn-header {
				width: 100%;
				justify-content: center;
				padding: 12px 16px;
				font-size: 1em;
			}
			
			/* EDYTOR */
			.editor-container {
				padding: 12px;
			}
			
			.toolbar {
				position: relative;
				padding: 14px;
				font-size: 1.05em;
				top: 0;
			}
			
			.search-box {
				width: 100%;
				min-width: auto;
			}
			
			.search-box input {
				font-size: 1.05em;
				padding: 14px 44px 14px 46px;
			}
			
			.search-icon {
				font-size: 1.3em;
			}
			
			.search-clear {
				width: 28px;
				height: 28px;
				font-size: 1em;
			}
			
			.toolbar-actions {
				width: 100%;
			}
			
			.btn-toolbar {
				flex: 1;
				padding: 12px 16px;
				font-size: 1em;
			}
			
			.store-header {
				flex-wrap: wrap;
				padding: 12px 14px;
			}
			
			.toggle-icon {
				font-size: 1.4em;
			}
			
			.store-header-drag {
				font-size: 1.5em;
			}
			
			.store-header input {
				order: 3;
				width: 100%;
				margin-top: 10px;
				font-size: 1.15em;
				padding: 10px 14px;
			}
			
			.product-counter {
				order: 2;
				font-size: 1em;
				padding: 7px 14px;
			}
			
			.store-actions {
				order: 4;
				width: 100%;
				margin-top: 10px;
			}
			
			.btn-delete-store {
				flex: 1;
				padding: 12px;
				font-size: 1.05em;
			}
			
			.products-container {
				padding: 12px;
			}
			
			.product-editor {
				grid-template-columns: auto 1fr;
				gap: 10px;
				padding: 12px;
			}
			
			.product-drag-handle {
				grid-row: 1 / 4;
				font-size: 1.3em;
			}
			
			.product-editor input[type="text"] {
				font-size: 1.05em;
				padding: 10px 12px;
			}
			
			.product-editor input[type="text"]:nth-of-type(1) {
				grid-column: 2;
			}
			
			.product-editor input[type="text"]:nth-of-type(2) {
				grid-column: 2;
			}
			
			.product-actions {
				grid-column: 1 / 3;
				width: 100%;
			}

			.product-actions button {
				flex: 1;
			}
			
			.btn-add {
				padding: 12px;
				font-size: 1.05em;
			}
			
			.btn-add-store {
				padding: 16px 24px;
				font-size: 1.1em;
			}
			
			.action-buttons {
				flex-direction: column;
			}
			
			.btn-save,
			.btn-cancel {
				width: 100%;
				padding: 18px 32px;
				font-size: 1.15em;
			}
			
			.floating-save {
				bottom: 12px;
				right: 12px;
				left: 12px;
			}
			
			.btn-floating-save {
				width: 100%;
				justify-content: center;
				padding: 20px 24px;
				font-size: 1.2em;
			}
			
			.empty-store-info {
				font-size: 1.05em;
				padding: 28px;
			}
			
			.no-results h3 {
				font-size: 1.2em;
			}
			
			.no-results p {
				font-size: 1.05em;
			}
		}
		
		@media (max-width: 480px) {
			.store-header input {
				font-size: 1.1em;
				padding: 10px 12px;
			}
			
			.product-editor input[type="text"] {
				font-size: 1em;
				padding: 9px 11px;
			}
			
			.btn-floating-save {
				padding: 18px 22px;
				font-size: 1.15em;
			}
		}
		
		/* ========================================
		   FOCUS VISIBLE (dostępność / accessibility)
		   ======================================== */
		
		button:focus-visible,
		input:focus-visible,
		a:focus-visible {
			outline: 3px solid var(--primary-color);
			outline-offset: 2px;
		}
		/* ========================================
		   TOAST NOTIFICATION
		   ======================================== */

		.toast {
			position: fixed;
			bottom: 30px;
			left: 50%;
			transform: translateX(-50%);
			background: #4CAF50;
			color: white;
			padding: 16px 32px;
			border-radius: 50px;
			font-weight: 600;
			font-size: 1.1em;
			box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
			z-index: 2000;
			display: flex;
			align-items: center;
			gap: 10px;
			animation: toastIn 0.4s ease, toastOut 0.4s ease 2.6s forwards;
		}

		@keyframes toastIn {
			from {
				opacity: 0;
				transform: translateX(-50%) translateY(20px) scale(0.9);
			}
			to {
				opacity: 1;
				transform: translateX(-50%) translateY(0) scale(1);
			}
		}

		@keyframes toastOut {
			from {
				opacity: 1;
				transform: translateX(-50%) translateY(0) scale(1);
			}
			to {
				opacity: 0;
				transform: translateX(-50%) translateY(-20px) scale(0.9);
			}
		}
	</style>
</head>
<body>

	<div class="header-container">
		<h1 class="logo-text">
			<img src="<?php echo h($base_path); ?>/assets/favicon.svg" 
				 alt="Logo" 
				 style="height: 1.5em; vertical-align: middle; margin-right: -0.2em">
			<?php _e('editor.heading'); ?>
		</h1>
		<div id="currentStoreIndicator">
			<span id="currentStoreName"></span> <span class="store-product-icon">📦</span> <span id="currentStoreProductCount"></span>
		</div>
		<div>
			<a href="<?php echo h($return_url); ?>" class="btn-header"><?php _e('editor.back_to_list'); ?></a>
		</div>
	</div>
		
    <div class="editor-container">
		<?php if ($message && $message_type === 'error'): ?>
			<div class="message error">
				<?php echo nl2br(h($message)); ?>
			</div>
		<?php endif; ?>

        <!-- TOOLBAR Z WYSZUKIWANIEM I AKCJAMI -->
        <div class="toolbar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       id="searchInput" 
                       placeholder="<?php _e('editor.search_placeholder'); ?>"
                       autocomplete="off">
                <button type="button" class="search-clear" id="searchClear" title="<?php _e('editor.clear_search'); ?>">✕</button>
            </div>
            <div class="toolbar-actions">
                <button type="button" class="btn-toolbar" id="btnExpandAll" title="<?php _e('editor.expand_all'); ?>">
                    📂 <?php _e('editor.expand'); ?>
                </button>
                <button type="button" class="btn-toolbar" id="btnCollapseAll" title="<?php _e('editor.collapse_all'); ?>">
                    📁 <?php _e('editor.collapse'); ?>
                </button>
            </div>
        </div>

		<form method="POST" id="editForm">
			<!-- CSRF token -->
			<input type="hidden" name="_csrf" value="<?php echo h(csrf_token()); ?>">
			<!-- Zachowaj sklepy do powrotu / Keep stores for return -->
			<input type="hidden" name="_expand_stores" value="<?php echo h($expand_stores); ?>">

            <div id="storesContainer">
                <?php $store_index = 0; ?>
                <?php foreach ($products_by_store as $store_name => $products): ?>
                    <div class="store-editor" data-store-index="<?php echo $store_index; ?>" draggable="true">
                        <div class="store-header" onclick="toggleStore(this)">
                            <span class="toggle-icon">▼</span>
                            <span class="store-header-drag" 
                                  draggable="true"
                                  onclick="event.stopPropagation()"
                                  title="<?php _e('editor.drag_to_reorder'); ?>">☰</span>
                            <input type="text" 
                                   name="stores[<?php echo $store_index; ?>][name]" 
                                   value="<?php echo h($store_name); ?>"
                                   placeholder="<?php _e('editor.store_name'); ?>"
                                   required
                                   onclick="event.stopPropagation()"
                                   aria-label="<?php _e('editor.store_name'); ?>">
                            <span class="product-counter">
                                📦 <span class="product-count"><?php echo count($products); ?></span>
                            </span>
                            <div class="store-actions" onclick="event.stopPropagation()">
                                <button type="button" class="btn-delete-store" onclick="deleteStore(this)" title="<?php _e('editor.delete_store'); ?>">
                                    🗑️ <?php _e('editor.delete'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="store-content">
                            <div class="products-container">
                                <?php if (empty($products)): ?>
                                    <div class="empty-store-info">
                                        <?php _e('editor.no_products'); ?>
                                    </div>
                                <?php else: ?>
                                    <?php $product_index = 0; ?>
                                    <?php foreach ($products as $product): ?>
                                        <div class="product-editor" draggable="true">
                                            <span class="product-drag-handle" title="<?php _e('editor.drag_to_reorder'); ?>">☰</span>
                                            <input type="text" 
                                                   name="stores[<?php echo $store_index; ?>][products][<?php echo $product_index; ?>][name]"
                                                   value="<?php echo h($product['name']); ?>"
                                                   placeholder="<?php _e('editor.product_name'); ?>"
                                                   required
                                                   oninput="checkDuplicates(this)"
                                                   aria-label="<?php _e('editor.product_name'); ?>">
                                            <input type="text" 
                                                   name="stores[<?php echo $store_index; ?>][products][<?php echo $product_index; ?>][unit]"
                                                   value="<?php echo h($product['unit']); ?>"
                                                   placeholder="<?php _e('editor.unit_placeholder'); ?>"
                                                   required
                                                   aria-label="<?php _e('editor.unit'); ?>">
                                            <div class="product-actions">
												<button type="button" class="btn-delete-product" onclick="deleteProduct(this)" title="<?php _e('editor.delete_product'); ?>">🗑️</button>
												<button type="button" class="btn-add-below" onclick="addProductBelow(this)" title="<?php _e('editor.add_product_below'); ?>">➕</button>
											</div>
                                        </div>
                                        <?php $product_index++; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- DODAJ PRODUKT NA DOLE -->
                            <div class="add-product-bottom">
                                <button type="button" class="btn-add" onclick="addProduct(this, false)">
                                    ➕ <?php _e('editor.add_product'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php $store_index++; ?>
                <?php endforeach; ?>
            </div>

            <!-- Info o braku wyników wyszukiwania -->
            <div id="noResults" class="no-results" style="display: none;">
                <div class="no-results-icon">🔍</div>
                <h3><?php _e('editor.no_results'); ?></h3>
                <p><?php _e('editor.try_different_keywords'); ?></p>
            </div>

            <button type="button" class="btn-add-store" onclick="addStore()">
                ➕ <?php _e('editor.add_new_store'); ?>
            </button>

			<div class="action-buttons">
				<button type="submit" name="save" class="btn-save">💾 <?php _e('editor.save_changes'); ?></button>
				<a href="<?php echo h($return_url); ?>" class="btn-cancel">❌ <?php _e('editor.cancel'); ?></a>
			</div>
        </form>
    </div>

	<script>
		// ========================================
		// KONFIGURACJA Z PHP / CONFIG FROM PHP
		// ========================================
		
		const BASE_PATH = <?php echo json_encode($base_path); ?>;
		const CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;
		
		// Tłumaczenia dla JS / Translations for JS
		const T = <?php echo json_encode($js_translations); ?>;
		
		let storeCounter = <?php echo (int)$store_index; ?>;
		let draggedElement = null;
		let draggedType = null;

		// ========================================
		// FUZZY DUPLICATE DETECTION
		// ========================================
	
        function levenshteinDistance(str1, str2) {
            const m = str1.length;
            const n = str2.length;
            const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));

            for (let i = 0; i <= m; i++) dp[i][0] = i;
            for (let j = 0; j <= n; j++) dp[0][j] = j;

            for (let i = 1; i <= m; i++) {
                for (let j = 1; j <= n; j++) {
                    if (str1[i - 1] === str2[j - 1]) {
                        dp[i][j] = dp[i - 1][j - 1];
                    } else {
                        dp[i][j] = Math.min(
                            dp[i - 1][j - 1] + 1,
                            dp[i - 1][j] + 1,
                            dp[i][j - 1] + 1
                        );
                    }
                }
            }

            return dp[m][n];
        }

        function normalizeString(str) {
            return str.toLowerCase()
                .trim()
                .replace(/\s+/g, ' ')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        }

        function checkDuplicates(input) {
            const productDiv = input.closest('.product-editor');
            const storeDiv = productDiv.closest('.store-editor');
            const inputValue = normalizeString(input.value);
            
            // Usuń poprzednie ostrzeżenie / Remove previous warning
            const oldBadge = productDiv.querySelector('.duplicate-badge');
            if (oldBadge) oldBadge.remove();
            productDiv.classList.remove('duplicate-warning');

            if (inputValue.length < 2) return;

            // Sprawdź duplikaty w tym samym sklepie / Check duplicates in same store
            const products = storeDiv.querySelectorAll('.product-editor');
            let foundDuplicate = false;

            products.forEach(otherProduct => {
                if (otherProduct === productDiv) return;
                
                const otherInput = otherProduct.querySelector('input[name*="[name]"]');
                const otherValue = normalizeString(otherInput.value);
                
                if (otherValue.length < 2) return;

                // Dokładne dopasowanie / Exact match
                if (inputValue === otherValue) {
                    foundDuplicate = true;
                    return;
                }

                // Fuzzy matching - próg 80% podobieństwa / 80% similarity threshold
                const distance = levenshteinDistance(inputValue, otherValue);
                const maxLen = Math.max(inputValue.length, otherValue.length);
                const similarity = 1 - (distance / maxLen);

                if (similarity >= 0.8) {
                    foundDuplicate = true;
                }
            });

            if (foundDuplicate) {
                productDiv.classList.add('duplicate-warning');
                const badge = document.createElement('span');
                badge.className = 'duplicate-badge';
                badge.textContent = '⚠️';
                badge.title = T.possible_duplicate || 'Possible duplicate';
                productDiv.appendChild(badge);
            }
        }

        // ========================================
        // WYSZUKIWANIE / SEARCH
        // ========================================

        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');
        
        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            
            // Pokaż/ukryj przycisk X / Show/hide X button
            if (e.target.value) {
                searchClear.classList.add('visible');
            } else {
                searchClear.classList.remove('visible');
            }
            
            searchTimeout = setTimeout(() => {
                filterStores(e.target.value);
            }, 300);
        });

        searchClear?.addEventListener('click', () => {
            searchInput.value = '';
            searchClear.classList.remove('visible');
            filterStores('');
            searchInput.focus();
        });

        function filterStores(query) {
            const search = query.toLowerCase().trim();
            const stores = document.querySelectorAll('.store-editor');
            let visibleCount = 0;

            if (!search) {
                stores.forEach(store => {
                    store.classList.remove('hidden');
                    store.querySelectorAll('.product-editor').forEach(p => {
                        p.style.display = '';
                    });
                });
                document.getElementById('noResults').style.display = 'none';
                restoreCollapsedStates();
                return;
            }

            stores.forEach(store => {
                const storeName = store.querySelector('input[name*="[name]"]').value.toLowerCase();
                const products = store.querySelectorAll('.product-editor');
                let storeVisible = false;

                if (storeName.includes(search)) {
                    storeVisible = true;
                    products.forEach(p => p.style.display = '');
                } else {
                    let visibleProducts = 0;
                    products.forEach(product => {
                        const productName = product.querySelector('input[name*="[name]"]').value.toLowerCase();
                        if (productName.includes(search)) {
                            product.style.display = '';
                            visibleProducts++;
                            storeVisible = true;
                        } else {
                            product.style.display = 'none';
                        }
                    });
                }

                if (storeVisible) {
                    store.classList.remove('hidden');
                    store.classList.remove('collapsed');
                    visibleCount++;
                } else {
                    store.classList.add('hidden');
                }
            });

            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // ========================================
        // ZWIJANIE/ROZWIJANIE SKLEPÓW / COLLAPSE/EXPAND STORES
        // ========================================

        function toggleStore(header) {
            const store = header.closest('.store-editor');
            store.classList.toggle('collapsed');
            
            const storeIndex = store.dataset.storeIndex;
            const collapsedStates = getCollapsedStates();
            collapsedStates[storeIndex] = store.classList.contains('collapsed');
            localStorage.setItem('shopicker_collapsed', JSON.stringify(collapsedStates));
        }

        function getCollapsedStates() {
            const saved = localStorage.getItem('shopicker_collapsed');
            return saved ? JSON.parse(saved) : {};
        }

		function restoreCollapsedStates() {
			// Sprawdź czy mamy parametr expand z listy zakupów
			const urlParams = new URLSearchParams(window.location.search);
			const expandParam = urlParams.get('expand');
			
			if (expandParam !== null) {
				// Przyszliśmy z listy zakupów - rozwiń tylko wybrane sklepy
				const storesToExpand = expandParam.split(',')
					.map(s => s.trim().toLowerCase())
					.filter(s => s.length > 0);
				
				document.querySelectorAll('.store-editor').forEach(store => {
					const storeInput = store.querySelector('.store-header input[type="text"]');
					const storeName = storeInput ? storeInput.value.trim().toLowerCase() : '';
					
					if (storesToExpand.length === 0) {
						// Pusty parametr = zwiń wszystkie
						store.classList.add('collapsed');
					} else if (storesToExpand.includes(storeName)) {
						// Ten sklep był wybrany - rozwiń
						store.classList.remove('collapsed');
					} else {
						// Ten sklep nie był wybrany - zwiń
						store.classList.add('collapsed');
					}
				});
				
				// Przewiń do pierwszego rozwiniętego sklepu
				setTimeout(() => {
					const firstExpanded = document.querySelector('.store-editor:not(.collapsed)');
					if (firstExpanded) {
						firstExpanded.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				}, 100);
				
			} else {
				// Normalne wejście do edytora - użyj zapisanych stanów
				const states = getCollapsedStates();
				document.querySelectorAll('.store-editor').forEach(store => {
					const index = store.dataset.storeIndex;
					if (states[index]) {
						store.classList.add('collapsed');
					}
				});
			}
		}

        document.getElementById('btnExpandAll')?.addEventListener('click', () => {
            document.querySelectorAll('.store-editor').forEach(store => {
                store.classList.remove('collapsed');
            });
            localStorage.removeItem('shopicker_collapsed');
        });

        document.getElementById('btnCollapseAll')?.addEventListener('click', () => {
            document.querySelectorAll('.store-editor').forEach(store => {
                store.classList.add('collapsed');
            });
            const states = {};
            document.querySelectorAll('.store-editor').forEach(store => {
                states[store.dataset.storeIndex] = true;
            });
            localStorage.setItem('shopicker_collapsed', JSON.stringify(states));
        });

        // ========================================
        // DRAG AND DROP - SKLEPY / STORES
        // ========================================

        function setupStoreDragAndDrop() {
            const stores = document.querySelectorAll('.store-editor');
            
            stores.forEach(store => {
                store.addEventListener('dragstart', handleStoreDragStart);
                store.addEventListener('dragover', handleStoreDragOver);
                store.addEventListener('drop', handleStoreDrop);
                store.addEventListener('dragend', handleStoreDragEnd);
                store.addEventListener('dragleave', handleStoreDragLeave);
            });
        }

        function handleStoreDragStart(e) {
            draggedElement = this;
            draggedType = 'store';
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleStoreDragOver(e) {
            if (draggedType !== 'store') return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = getDragAfterElement(this.parentElement, e.clientY);
            if (afterElement == null) {
                this.parentElement.appendChild(draggedElement);
            } else {
                this.parentElement.insertBefore(draggedElement, afterElement);
            }
            
            this.classList.add('drag-over');
        }

        function handleStoreDrop(e) {
            if (draggedType !== 'store') return;
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            updateStoreIndexes();
            formChanged = true;
        }

        function handleStoreDragEnd(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.store-editor').forEach(s => s.classList.remove('drag-over'));
        }

        function handleStoreDragLeave(e) {
            this.classList.remove('drag-over');
        }

        // ========================================
        // DRAG AND DROP - PRODUKTY / PRODUCTS
        // ========================================

        function setupProductDragAndDrop() {
            const products = document.querySelectorAll('.product-editor');
            
            products.forEach(product => {
                product.addEventListener('dragstart', handleProductDragStart);
                product.addEventListener('dragover', handleProductDragOver);
                product.addEventListener('drop', handleProductDrop);
                product.addEventListener('dragend', handleProductDragEnd);
            });
        }

        function handleProductDragStart(e) {
            draggedElement = this;
            draggedType = 'product';
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.stopPropagation();
        }

        function handleProductDragOver(e) {
            if (draggedType !== 'product') return;
            if (this.parentElement !== draggedElement.parentElement) return;
            
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = getDragAfterElement(this.parentElement, e.clientY);
            if (afterElement == null) {
                this.parentElement.appendChild(draggedElement);
            } else {
                this.parentElement.insertBefore(draggedElement, afterElement);
            }
        }

        function handleProductDrop(e) {
            if (draggedType !== 'product') return;
            e.preventDefault();
            e.stopPropagation();
            
            const storeDiv = this.closest('.store-editor');
            const storeIndex = storeDiv.dataset.storeIndex;
            updateProductIndexes(storeDiv, storeIndex);
            formChanged = true;
        }

        function handleProductDragEnd(e) {
            this.classList.remove('dragging');
            e.stopPropagation();
        }

        // ========================================
        // FUNKCJE POMOCNICZE / HELPER FUNCTIONS
        // ========================================

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.store-editor:not(.dragging), .product-editor:not(.dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        function updateStoreIndexes() {
            const stores = document.querySelectorAll('.store-editor');
            stores.forEach((store, index) => {
                store.dataset.storeIndex = index;
                const nameInput = store.querySelector('input[name^="stores["]');
                nameInput.name = `stores[${index}][name]`;
                
                updateProductIndexes(store, index);
            });
        }

        function updateProductIndexes(storeDiv, storeIndex) {
            const products = storeDiv.querySelectorAll('.product-editor');
            products.forEach((product, pIndex) => {
                const inputs = product.querySelectorAll('input[type="text"]');
                inputs[0].name = `stores[${storeIndex}][products][${pIndex}][name]`;
                inputs[1].name = `stores[${storeIndex}][products][${pIndex}][unit]`;
            });
            
            updateProductCounter(storeDiv);
        }

        function updateProductCounter(storeDiv) {
            const counter = storeDiv.querySelector('.product-count');
            const productCount = storeDiv.querySelectorAll('.product-editor').length;
            if (counter) {
                counter.textContent = productCount;
            }
            
            // Aktualizuj licznik w górnym pasku / Update counter in top bar
            updateCurrentStoreProductCount(storeDiv);
        }

        function updateCurrentStoreProductCount(storeDiv) {
            const currentProductCount = document.getElementById('currentStoreProductCount');
            const rect = storeDiv.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            // Sprawdź czy ten sklep jest w centrum ekranu / Check if store is in center
            if (rect.top < windowHeight / 2 && rect.bottom > windowHeight / 2) {
                const productsCount = storeDiv.querySelectorAll('.product-editor').length;
                currentProductCount.textContent = productsCount;
            }
        }

        // ========================================
        // DODAWANIE/USUWANIE ELEMENTÓW / ADD/DELETE ELEMENTS
        // ========================================

        function addStore() {
            const container = document.getElementById('storesContainer');
            const newIndex = storeCounter++;
            
            const storeHTML = `
                <div class="store-editor" data-store-index="${newIndex}" draggable="true">
                    <div class="store-header" onclick="toggleStore(this)">
                        <span class="toggle-icon">▼</span>
                        <span class="store-header-drag" 
                              draggable="true"
                              onclick="event.stopPropagation()"
                              title="${T.drag_to_reorder || 'Drag to reorder'}">☰</span>
                        <input type="text" 
                               name="stores[${newIndex}][name]" 
                               placeholder="${T.store_name || 'Store name'}"
                               required
                               onclick="event.stopPropagation()"
                               aria-label="${T.store_name || 'Store name'}">
                        <span class="product-counter">
                            📦 <span class="product-count">0</span>
                        </span>
                        <div class="store-actions" onclick="event.stopPropagation()">
                            <button type="button" class="btn-delete-store" onclick="deleteStore(this)" title="${T.delete_store || 'Delete store'}">
                                🗑️ ${T.delete || 'Delete'}
                            </button>
                        </div>
                    </div>
                    
                    <div class="store-content">
                        <div class="products-container">
                            <div class="empty-store-info">
                                ${T.no_products || 'No products. Add your first product below.'}
                            </div>
                        </div>
                        
                        <div class="add-product-bottom">
                            <button type="button" class="btn-add" onclick="addProduct(this, false)">
                                ➕ ${T.add_product || 'Add product'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', storeHTML);
            setupStoreDragAndDrop();
            setupProductDragAndDrop();
            formChanged = true;
            
            setTimeout(() => {
                const newElement = container.lastElementChild;
                newElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                newElement.querySelector('input').focus();
            }, 100);
        }

        function addProduct(button, atTop = false) {
            const storeDiv = button.closest('.store-editor');
            const storeIndex = storeDiv.dataset.storeIndex;
            const productsContainer = storeDiv.querySelector('.products-container');
            
            const emptyInfo = productsContainer.querySelector('.empty-store-info');
            if (emptyInfo) {
                emptyInfo.remove();
            }
            
            const currentCount = productsContainer.querySelectorAll('.product-editor').length;
            
            const productHTML = `
                <div class="product-editor" draggable="true">
                    <span class="product-drag-handle" title="${T.drag_to_reorder || 'Drag to reorder'}">☰</span>
                    <input type="text" 
                           name="stores[${storeIndex}][products][${currentCount}][name]"
                           placeholder="${T.product_name || 'Product name'}"
                           required
                           oninput="checkDuplicates(this)"
                           aria-label="${T.product_name || 'Product name'}">
                    <input type="text" 
                           name="stores[${storeIndex}][products][${currentCount}][unit]"
                           placeholder="${T.unit_placeholder || 'e.g. kg, pcs, l'}"
                           required
                           aria-label="${T.unit || 'Unit'}">
                    <div class="product-actions">
                        <button type="button" class="btn-delete-product" onclick="deleteProduct(this)" title="${T.delete_product || 'Delete product'}">🗑️</button>
                        <button type="button" class="btn-add-below" onclick="addProductBelow(this)" title="${T.add_product_below || 'Add product below'}">➕</button>
                    </div>
                </div>
`;
            
            if (atTop) {
                productsContainer.insertAdjacentHTML('afterbegin', productHTML);
            } else {
                productsContainer.insertAdjacentHTML('beforeend', productHTML);
            }
            
            setupProductDragAndDrop();
            updateProductIndexes(storeDiv, storeIndex);
            formChanged = true;
            
            setTimeout(() => {
                const newProduct = atTop ? productsContainer.firstElementChild : productsContainer.lastElementChild;
                newProduct.querySelector('input').focus();
            }, 100);
        }

		function addProductBelow(button) {
			const productDiv = button.closest('.product-editor');
			const storeDiv = productDiv.closest('.store-editor');
			const storeIndex = storeDiv.dataset.storeIndex;
			const productsContainer = storeDiv.querySelector('.products-container');
			
			const currentCount = productsContainer.querySelectorAll('.product-editor').length;
			
			const productHTML = `
				<div class="product-editor" draggable="true">
					<span class="product-drag-handle" title="${T.drag_to_reorder || 'Drag to reorder'}">☰</span>
					<input type="text" 
						   name="stores[${storeIndex}][products][${currentCount}][name]"
						   placeholder="${T.product_name || 'Product name'}"
						   required
						   oninput="checkDuplicates(this)"
						   aria-label="${T.product_name || 'Product name'}">
					<input type="text" 
						   name="stores[${storeIndex}][products][${currentCount}][unit]"
						   placeholder="${T.unit_placeholder || 'e.g. kg, pcs, l'}"
						   required
						   aria-label="${T.unit || 'Unit'}">
					<div class="product-actions">
						<button type="button" class="btn-delete-product" onclick="deleteProduct(this)" title="${T.delete_product || 'Delete product'}">🗑️</button>
						<button type="button" class="btn-add-below" onclick="addProductBelow(this)" title="${T.add_product_below || 'Add product below'}">➕</button>
					</div>
				</div>
			`;
			
			// Wstaw nowy produkt bezpośrednio po aktualnym / Insert after current
			productDiv.insertAdjacentHTML('afterend', productHTML);
			
			setupProductDragAndDrop();
			updateProductIndexes(storeDiv, storeIndex);
			formChanged = true;
			
			// Focus na nowym produkcie / Focus on new product
			setTimeout(() => {
				const newProduct = productDiv.nextElementSibling;
				newProduct.scrollIntoView({ behavior: 'smooth', block: 'center' });
				newProduct.querySelector('input').focus();
			}, 100);
		}

        function deleteProduct(button) {
            if (confirm(T.confirm_delete_product || 'Are you sure you want to delete this product?')) {
                const productDiv = button.closest('.product-editor');
                const storeDiv = productDiv.closest('.store-editor');
                const storeIndex = storeDiv.dataset.storeIndex;
                const productsContainer = storeDiv.querySelector('.products-container');
                
                productDiv.remove();
                updateProductIndexes(storeDiv, storeIndex);
                
                if (productsContainer.querySelectorAll('.product-editor').length === 0) {
                    productsContainer.innerHTML = `<div class="empty-store-info">${T.no_products || 'No products. Add your first product below.'}</div>`;
                    updateProductCounter(storeDiv);
                }
                
                formChanged = true;
            }
        }

        function deleteStore(button) {
            if (confirm(T.confirm_delete_store || 'Are you sure you want to delete this entire store with all products?')) {
                button.closest('.store-editor').remove();
                updateStoreIndexes();
                formChanged = true;
            }
        }

        // ========================================
        // PŁYWAJĄCY PRZYCISK ZAPISZ / FLOATING SAVE BUTTON
        // ========================================

        let floatingButtonElement = null;
        let lastScrollTop = 0;

        function showFloatingButton() {
            if (!floatingButtonElement) return;
            
            const scrollPos = window.scrollY || document.documentElement.scrollTop;
            
            if (scrollPos > 200 && scrollPos > lastScrollTop) {
                floatingButtonElement.style.display = 'block';
            } else if (scrollPos < 100) {
                floatingButtonElement.style.display = 'none';
            }
            
            lastScrollTop = scrollPos <= 0 ? 0 : scrollPos;
        }

        function submitFromFloating() {
            const editForm = document.getElementById('editForm');
            const saveButton = editForm.querySelector('button[name="save"]');
            
            if (saveButton) {
                saveButton.click();
            } else {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'save';
                hiddenInput.value = '1';
                editForm.appendChild(hiddenInput);
                editForm.submit();
            }
        }

        // ========================================
        // WSKAŹNIK AKTUALNEGO SKLEPU / CURRENT STORE INDICATOR
        // ========================================

        function updateCurrentStoreIndicator() {
            const currentStoreName = document.getElementById('currentStoreName');
            const currentProductCount = document.getElementById('currentStoreProductCount');
            
            // Intersection Observer do śledzenia który sklep jest w centrum
            const observerOptions = {
                root: null,
                rootMargin: '-50% 0px -50% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const storeInput = entry.target.querySelector('.store-header input[type="text"]');
                        const productCountEl = entry.target.querySelector('.product-count');
                        
                        if (storeInput) {
                            const storeName = storeInput.value.trim();
                            const productCount = productCountEl ? productCountEl.textContent : '0';
                            const indicator = document.getElementById('currentStoreIndicator');
                            
                            if (storeName) {
                                currentStoreName.textContent = storeName;
                                currentProductCount.textContent = productCount;
                                indicator.classList.add('visible');
                                
                                indicator.style.transform = 'scale(1.05)';
                                setTimeout(() => {
                                    indicator.style.transform = 'scale(1)';
                                }, 200);
                            } else {
                                indicator.classList.remove('visible');
                            }
                        }
                    } else {
                        const indicator = document.getElementById('currentStoreIndicator');
                        const allIntersecting = Array.from(document.querySelectorAll('.store-editor')).some(store => {
                            const rect = store.getBoundingClientRect();
                            const windowHeight = window.innerHeight;
                            return rect.top < windowHeight / 2 && rect.bottom > windowHeight / 2;
                        });
                        
                        if (!allIntersecting) {
                            indicator.classList.remove('visible');
                        }
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.store-editor').forEach(store => {
                observer.observe(store);
            });

            // Aktualizuj gdy zmieniamy nazwę sklepu / Update on store name change
            document.addEventListener('input', (e) => {
                if (e.target.matches('.store-header input[type="text"]')) {
                    const store = e.target.closest('.store-editor');
                    const rect = store.getBoundingClientRect();
                    const windowHeight = window.innerHeight;
                    
                    if (rect.top < windowHeight / 2 && rect.bottom > windowHeight / 2) {
                        const storeName = e.target.value.trim() || (T.new_store || 'New store');
                        currentStoreName.textContent = storeName;
                    }
                }
            });

            // Re-obserwuj gdy sklepy są dodawane/usuwane / Re-observe on store add/remove
            const containerObserver = new MutationObserver(() => {
                observer.disconnect();
                document.querySelectorAll('.store-editor').forEach(store => {
                    observer.observe(store);
                });
            });

            containerObserver.observe(document.getElementById('storesContainer'), {
                childList: true
            });
        }

        // ========================================
        // INICJALIZACJA / INITIALIZATION
        // ========================================

        let formChanged = false;

        document.addEventListener('DOMContentLoaded', () => {
            setupStoreDragAndDrop();
            setupProductDragAndDrop();
            restoreCollapsedStates();
            updateCurrentStoreIndicator();

            floatingButtonElement = document.getElementById('floatingButton');
            
            if (floatingButtonElement) {
                let scrollTimeout;
                window.addEventListener('scroll', () => {
                    if (scrollTimeout) clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(showFloatingButton, 50);
                });

                showFloatingButton();
            }

            const editForm = document.getElementById('editForm');
            
            if (editForm) {
                editForm.addEventListener('change', () => {
                    formChanged = true;
                });

                editForm.addEventListener('input', () => {
                    formChanged = true;
                });

                editForm.addEventListener('submit', () => {
                    formChanged = false;
                });
            }

            window.addEventListener('beforeunload', (e) => {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = T.unsaved_changes || 'You have unsaved changes. Are you sure you want to leave?';
                }
            });

            document.querySelectorAll('.store-editor').forEach(store => {
                updateProductCounter(store);
            });
        });

        // ========================================
        // SKRÓTY KLAWISZOWE / KEYBOARD SHORTCUTS
        // ========================================

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                submitFromFloating();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput?.focus();
            }
            
            if (e.key === 'Escape' && searchInput === document.activeElement) {
                searchInput.value = '';
                searchClear.classList.remove('visible');
                filterStores('');
            }
        });
	</script>
	
	<!-- Pływający przycisk zapisz / Floating save button -->
	<div id="floatingButton" class="floating-save" style="display: none;">
	    <button type="button" onclick="submitFromFloating()" class="btn-floating-save" title="<?php _e('editor.save_shortcut'); ?>">
	        💾 <?php _e('editor.save_changes'); ?>
	    </button>
	</div>

    <script>
        // Upewnij się, że formularz zawsze zawiera _csrf / Ensure form always has _csrf
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('editForm');
            if (form && !form.querySelector('input[name="_csrf"]')) {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = '_csrf';
                inp.value = CSRF_TOKEN || '';
                form.appendChild(inp);
            }
        });
    </script>
	<?php if ($saved_successfully): ?>
	<div class="toast" id="successToast">
		✓ <?php _e('editor.saved'); ?>
	</div>
	<script>
		// Usuń toast po animacji / Remove toast after animation
		setTimeout(() => {
			document.getElementById('successToast')?.remove();
		}, 3000);
	</script>
	<?php endif; ?>
</body>
</html>
