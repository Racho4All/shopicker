<?php
// ============================================
// SHOPICKER - Edytor listy produktów
// ============================================

$plik_konfiguracji = __DIR__ . '/produkty_sklepy.php';
$wersja_backup = __DIR__ . '/produkty_sklepy_backup_' . date('Y-m-d_His') . '.php';

// ============================================
// FUNKCJE POMOCNICZE
// ============================================

function zapiszKonfiguracje($plik, $dane) {
    $kod_php = "<?php\n";
    $kod_php .= "// ============================================\n";
    $kod_php .= "// KONFIGURACJA: Lista sklepów i produktów\n";
    $kod_php .= "// Ostatnia edycja: " . date('Y-m-d H:i:s') . "\n";
    $kod_php .= "// ============================================\n\n";
    $kod_php .= "return [\n";
    
    foreach ($dane as $sklep => $produkty) {
        $kod_php .= "    '" . addslashes($sklep) . "' => [\n";
        foreach ($produkty as $produkt) {
            $kod_php .= "        ['name' => '" . addslashes($produkt['name']) . "', 'unit' => '" . addslashes($produkt['unit']) . "'],\n";
        }
        $kod_php .= "    ],\n\n";
    }
    
    $kod_php .= "];\n";
    
    return file_put_contents($plik, $kod_php, LOCK_EX);
}

function walidujDane($post_data) {
    $bledy = [];
    
    if (empty($post_data['sklepy']) || !is_array($post_data['sklepy'])) {
        $bledy[] = "Brak danych sklepów.";
        return $bledy;
    }
    
    foreach ($post_data['sklepy'] as $index => $sklep) {
        $numer = $index + 1;
        
        if (empty(trim($sklep['nazwa']))) {
            $bledy[] = "Sklep #{$numer}: Nazwa sklepu nie może być pusta.";
        }
        
        if (isset($sklep['produkty']) && is_array($sklep['produkty'])) {
            foreach ($sklep['produkty'] as $p_index => $produkt) {
                $p_numer = $p_index + 1;
                if (empty(trim($produkt['name']))) {
                    $bledy[] = "Sklep '{$sklep['nazwa']}', produkt #{$p_numer}: Nazwa produktu nie może być pusta.";
                }
                if (empty(trim($produkt['unit']))) {
                    $bledy[] = "Sklep '{$sklep['nazwa']}', produkt #{$p_numer}: Jednostka nie może być pusta.";
                }
            }
        }
    }
    
    return $bledy;
}

// ============================================
// OBSŁUGA ZAPISU
// ============================================

$komunikat = '';
$komunikat_typ = '';
$zapisano_pomyslnie = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapisz'])) {
    $bledy = walidujDane($_POST);
    
    if (empty($bledy)) {
        // Utwórz backup
        if (file_exists($plik_konfiguracji)) {
            copy($plik_konfiguracji, $wersja_backup);
        }
        
        // Przygotuj dane
        $nowe_dane = [];
        foreach ($_POST['sklepy'] as $sklep) {
            $nazwa_sklepu = trim($sklep['nazwa']);
            if (empty($nazwa_sklepu)) continue;
            
            $nowe_dane[$nazwa_sklepu] = [];
            
            if (isset($sklep['produkty']) && is_array($sklep['produkty'])) {
                foreach ($sklep['produkty'] as $produkt) {
                    $nazwa_produktu = trim($produkt['name']);
                    $jednostka = trim($produkt['unit']);
                    
                    if (!empty($nazwa_produktu) && !empty($jednostka)) {
                        $nowe_dane[$nazwa_sklepu][] = [
                            'name' => $nazwa_produktu,
                            'unit' => $jednostka
                        ];
                    }
                }
            }
        }
        
        // Zapisz
        if (zapiszKonfiguracje($plik_konfiguracji, $nowe_dane)) {
            $komunikat = "Zmiany zostały zapisane pomyślnie!";
            $komunikat_typ = 'sukces';
            $zapisano_pomyslnie = true;
        } else {
            $komunikat = "Błąd zapisu pliku!";
            $komunikat_typ = 'blad';
        }
    } else {
        $komunikat = "Błędy walidacji:<br>" . implode("<br>", $bledy);
        $komunikat_typ = 'blad';
    }
}

// ============================================
// WCZYTANIE AKTUALNYCH DANYCH
// ============================================

$produkty_sklepy = require $plik_konfiguracji;
if (!is_array($produkty_sklepy)) {
    die('Błąd: plik produkty_sklepy.php nie zwrócił poprawnej tablicy.');
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edycja listy - Shopicker</title>
    <link rel="icon" type="image/svg+xml" href="/shopicker/assets/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/shopicker/style.css">
    <style>
        :root {
            --primary-color: #4CAF50;
            --primary-hover: #45a049;
            --secondary-color: #2196F3;
            --secondary-hover: #1976D2;
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
        }

        body {
            padding-bottom: 100px;
        }

        .edytor-kontener {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        /* ========================================
           TOOLBAR - FILTR I AKCJE
           ======================================== */
        
        .toolbar {
            background: white;
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
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
        }
        
        .btn-toolbar.active {
            background: var(--primary-color);
        }
        
        /* ========================================
           SKLEPY - SKŁADANE
           ======================================== */
        
        .sklep-edytor {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 12px;
            transition: var(--transition);
            box-shadow: var(--shadow);
        }
        
        .sklep-edytor.hidden {
            display: none;
        }
        
        .sklep-edytor:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .sklep-edytor.dragging {
            opacity: 0.6;
            border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }
        
        .sklep-edytor.drag-over {
            border-color: var(--secondary-color);
            background: #e3f2fd;
            border-style: dashed;
        }
        
        .sklep-edytor.collapsed .sklep-zawartość {
            display: none;
        }
        
        .sklep-edytor.collapsed .sklep-naglowek {
            border-bottom: none;
        }
        
        .sklep-naglowek {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 2px solid var(--bg-light);
            background: var(--bg-lighter);
            border-radius: var(--radius) var(--radius) 0 0;
            cursor: pointer;
            user-select: none;
        }
        
        .sklep-edytor.collapsed .sklep-naglowek {
            border-radius: var(--radius);
        }
        
        .toggle-icon {
            font-size: 1.2em;
            color: #666;
            transition: transform 0.3s ease;
            cursor: pointer;
            padding: 4px;
        }
        
        .sklep-edytor.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        .sklep-naglowek-drag {
            cursor: grab;
            font-size: 1.3em;
            color: #999;
            padding: 4px 8px;
            transition: var(--transition);
            border-radius: 4px;
        }
        
        .sklep-naglowek-drag:hover {
            color: var(--primary-color);
            background: white;
        }
        
        .sklep-naglowek-drag:active {
            cursor: grabbing;
        }
        
        .sklep-naglowek input {
            flex: 1;
            font-size: 1.1em;
            font-weight: 600;
            padding: 8px 12px;
            border: 2px solid transparent;
            border-radius: 6px;
            transition: var(--transition);
            background: white;
            min-width: 150px;
        }
        
        .sklep-naglowek input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .licznik-produktow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #666;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .sklep-akcje {
            display: flex;
            gap: 6px;
        }
        
        .btn-usun-sklep {
            background: var(--danger-bg);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .btn-usun-sklep:hover {
            background: var(--danger-color);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        /* ========================================
           ZAWARTOŚĆ SKLEPU
           ======================================== */
        
        .sklep-zawartość {
            padding: 16px;
        }
        
        .dodaj-produkt-gora {
            background: var(--bg-lighter);
            border-radius: var(--radius);
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .produkty-kontener {
            background: var(--bg-lighter);
            border-radius: var(--radius);
            padding: 12px;
            margin-bottom: 12px;
        }
        
        .produkt-edytor {
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
        }
        
        .produkt-edytor:last-child {
            margin-bottom: 0;
        }
        
        .produkt-edytor:hover {
            box-shadow: var(--shadow);
        }
        
        .produkt-edytor.dragging {
            opacity: 0.6;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }
        
        .produkt-edytor.duplicate-warning {
            border-color: var(--warning-color);
            background: var(--warning-bg);
        }
        
        .produkt-drag-handle {
            cursor: grab;
            font-size: 1.1em;
            color: #bbb;
            padding: 4px 6px;
            user-select: none;
            transition: var(--transition);
            border-radius: 4px;
        }
        
        .produkt-drag-handle:hover {
            color: var(--primary-color);
            background: var(--bg-light);
        }
        
        .produkt-drag-handle:active {
            cursor: grabbing;
        }
        
        .produkt-edytor input[type="text"] {
            padding: 8px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 100%;
            font-size: 0.95em;
            transition: var(--transition);
        }
        
        .produkt-edytor input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        .btn-usun-produkt {
            background: var(--danger-bg);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-usun-produkt:hover {
            background: var(--danger-color);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }
        
        .btn-dodaj {
            background: var(--primary-color);
            color: white;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.95em;
        }
        
        .btn-dodaj:hover {
            background: var(--primary-hover);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        
        .pusty-sklep-info {
            text-align: center;
            padding: 24px;
            background: white;
            border-radius: var(--radius);
            color: #999;
            font-style: italic;
        }
        
        /* ========================================
           OSTRZEŻENIE O DUPLIKACIE
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
        
        .produkt-edytor {
            position: relative;
        }
        
        /* ========================================
           PRZYCISKI GŁÓWNE
           ======================================== */
        
        .btn-dodaj-sklep {
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
        
        .btn-dodaj-sklep:hover {
            background: var(--secondary-hover);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }
        
        .przyciski-akcji {
            display: flex;
            gap: 12px;
            margin: 30px 0;
        }
        
        .btn-zapisz {
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
        
        .btn-zapisz:hover {
            background: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .btn-anuluj {
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
        
        .btn-anuluj:hover {
            background: #616161;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* ========================================
           KOMUNIKATY
           ======================================== */
        
        .komunikat {
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
        
        .komunikat.sukces {
            background: #d4edda;
            border: 2px solid #c3e6cb;
            color: #155724;
        }
        
        .komunikat.blad {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            color: #721c24;
        }
        
        .btn-powrot-sukces {
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
        
        .btn-powrot-sukces:hover {
            background: var(--primary-hover);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        
        /* ========================================
           PŁYWAJĄCY PRZYCISK
           ======================================== */
        
        .plywajacy-zapisz {
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
        
        .btn-plywajacy-zapisz {
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
        
        .btn-plywajacy-zapisz:hover {
            background: var(--primary-hover);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.5);
        }
        
        .btn-plywajacy-zapisz:active {
            transform: translateY(-1px) scale(1.02);
        }
        
        /* ========================================
           INFO O BRAKU WYNIKÓW
           ======================================== */
        
        .brak-wynikow {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .brak-wynikow-icon {
            font-size: 4em;
            margin-bottom: 16px;
        }
        
        .brak-wynikow h3 {
            margin: 0 0 8px 0;
            color: #666;
        }
        
        .brak-wynikow p {
            margin: 0;
            font-size: 0.95em;
        }
        
        /* ========================================
           RESPONSYWNOŚĆ
           ======================================== */
        
        @media (max-width: 768px) {
            .edytor-kontener {
                padding: 12px;
                margin: 12px auto;
            }
            
            .toolbar {
                position: relative;
                padding: 14px;
                font-size: 1.05em;
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
            
            .sklep-naglowek {
                flex-wrap: wrap;
                padding: 12px 14px;
            }
            
            .toggle-icon {
                font-size: 1.4em;
            }
            
            .sklep-naglowek-drag {
                font-size: 1.5em;
            }
            
            .sklep-naglowek input {
                order: 3;
                width: 100%;
                margin-top: 10px;
                font-size: 1.15em;
                padding: 10px 14px;
            }
            
            .licznik-produktow {
                order: 2;
                font-size: 1em;
                padding: 7px 14px;
            }
            
            .sklep-akcje {
                order: 4;
                width: 100%;
                margin-top: 10px;
            }
            
            .btn-usun-sklep {
                flex: 1;
                padding: 12px;
                font-size: 1.05em;
            }
            
            .produkty-kontener {
                padding: 12px;
            }
            
            .produkt-edytor {
                grid-template-columns: auto 1fr;
                gap: 10px;
                padding: 12px;
            }
            
            .produkt-drag-handle {
                grid-row: 1 / 4;
                font-size: 1.3em;
            }
            
            .produkt-edytor input[type="text"] {
                font-size: 1.05em;
                padding: 10px 12px;
            }
            
            .produkt-edytor input[type="text"]:nth-of-type(1) {
                grid-column: 2;
            }
            
            .produkt-edytor input[type="text"]:nth-of-type(2) {
                grid-column: 2;
            }
            
            .btn-usun-produkt {
                grid-column: 1 / 3;
                width: 100%;
                padding: 10px;
                font-size: 1.05em;
            }
            
            .btn-dodaj {
                padding: 12px;
                font-size: 1.05em;
            }
            
            .btn-dodaj-sklep {
                padding: 16px 24px;
                font-size: 1.1em;
            }
            
            .przyciski-akcji {
                flex-direction: column;
            }
            
            .btn-zapisz,
            .btn-anuluj {
                width: 100%;
                padding: 18px 32px;
                font-size: 1.15em;
            }
            
            .plywajacy-zapisz {
                bottom: 12px;
                right: 12px;
                left: 12px;
            }
            
            .btn-plywajacy-zapisz {
                width: 100%;
                justify-content: center;
                padding: 20px 24px;
                font-size: 1.2em;
            }
            
            .pusty-sklep-info {
                font-size: 1.05em;
                padding: 28px;
            }
            
            .brak-wynikow h3 {
                font-size: 1.2em;
            }
            
            .brak-wynikow p {
                font-size: 1.05em;
            }
        }
        
        @media (max-width: 480px) {
            .sklep-naglowek input {
                font-size: 1.1em;
                padding: 10px 12px;
            }
            
            .produkt-edytor input[type="text"] {
                font-size: 1em;
                padding: 9px 11px;
            }
            
            .btn-plywajacy-zapisz {
                padding: 18px 22px;
                font-size: 1.15em;
            }
        }
        
        /* ========================================
           FOCUS VISIBLE (dostępność)
           ======================================== */
        
        button:focus-visible,
        input:focus-visible,
        a:focus-visible {
            outline: 3px solid var(--primary-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>

    <div class="naglowek-kontener">
        <h1 class="montserrat-logo">
            <img src="/shopicker/assets/favicon.svg" 
                 alt="Logo" 
                 style="height: 1.5em; vertical-align: middle; margin-right: -0.2em">
            Shopicker - Edycja
        </h1>
        <div>
            <a href="/shopicker/" class="przycisk-naglowek">← Powrót do listy</a>
        </div>
    </div>
		
    <div class="edytor-kontener">
        <?php if ($komunikat): ?>
            <div class="komunikat <?php echo $komunikat_typ; ?>">
                <?php echo $komunikat; ?>
                <?php if ($zapisano_pomyslnie): ?>
                    <div style="margin-top: 15px;">
                        <a href="/shopicker/" class="btn-powrot-sukces">
                            ← Powrót do listy zakupów
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TOOLBAR Z WYSZUKIWANIEM I AKCJAMI -->
        <div class="toolbar">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       id="searchInput" 
                       placeholder="Szukaj sklepu lub produktu..."
                       autocomplete="off">
                <button type="button" class="search-clear" id="searchClear" title="Wyczyść wyszukiwanie">✕</button>
            </div>
            <div class="toolbar-actions">
                <button type="button" class="btn-toolbar" id="btnRozwinWszystkie" title="Rozwiń wszystkie sklepy">
                    📂 Rozwiń
                </button>
                <button type="button" class="btn-toolbar" id="btnZwinWszystkie" title="Zwiń wszystkie sklepy">
                    📁 Zwiń
                </button>
            </div>
        </div>

        <form method="POST" id="formEdycja">
            <div id="kontenerSklepy">
                <?php $sklep_index = 0; ?>
                <?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty): ?>
                    <div class="sklep-edytor" data-sklep-index="<?php echo $sklep_index; ?>" draggable="true">
                        <div class="sklep-naglowek" onclick="toggleSklep(this)">
                            <span class="toggle-icon">▼</span>
                            <span class="sklep-naglowek-drag" 
                                  draggable="true"
                                  onclick="event.stopPropagation()"
                                  title="Przeciągnij, aby zmienić kolejność">☰</span>
                            <input type="text" 
                                   name="sklepy[<?php echo $sklep_index; ?>][nazwa]" 
                                   value="<?php echo htmlspecialchars($sklep_nazwa); ?>"
                                   placeholder="Nazwa sklepu"
                                   required
                                   onclick="event.stopPropagation()"
                                   aria-label="Nazwa sklepu">
                            <span class="licznik-produktow">
                                📦 <span class="liczba-produktow"><?php echo count($produkty); ?></span>
                            </span>
                            <div class="sklep-akcje" onclick="event.stopPropagation()">
                                <button type="button" class="btn-usun-sklep" onclick="usunSklep(this)" title="Usuń sklep">
                                    🗑️ Usuń
                                </button>
                            </div>
                        </div>
                        
                        <div class="sklep-zawartość">
                            <!-- DODAJ PRODUKT NA GÓRZE -->
                            <div class="dodaj-produkt-gora">
                                <button type="button" class="btn-dodaj" onclick="dodajProdukt(this, true)">
                                    ➕ Dodaj produkt
                                </button>
                            </div>
                            
                            <div class="produkty-kontener">
                                <?php if (empty($produkty)): ?>
                                    <div class="pusty-sklep-info">
                                        Brak produktów. Dodaj pierwszy produkt powyżej.
                                    </div>
                                <?php else: ?>
                                    <?php $produkt_index = 0; ?>
                                    <?php foreach ($produkty as $produkt): ?>
                                        <div class="produkt-edytor" draggable="true">
                                            <span class="produkt-drag-handle" title="Przeciągnij, aby zmienić kolejność">☰</span>
                                            <input type="text" 
                                                   name="sklepy[<?php echo $sklep_index; ?>][produkty][<?php echo $produkt_index; ?>][name]"
                                                   value="<?php echo htmlspecialchars($produkt['name']); ?>"
                                                   placeholder="Nazwa produktu"
                                                   required
                                                   oninput="checkDuplicates(this)"
                                                   aria-label="Nazwa produktu">
                                            <input type="text" 
                                                   name="sklepy[<?php echo $sklep_index; ?>][produkty][<?php echo $produkt_index; ?>][unit]"
                                                   value="<?php echo htmlspecialchars($produkt['unit']); ?>"
                                                   placeholder="np. kg, szt, l"
                                                   required
                                                   aria-label="Jednostka">
                                            <button type="button" class="btn-usun-produkt" onclick="usunProdukt(this)" title="Usuń produkt">🗑️</button>
                                        </div>
                                        <?php $produkt_index++; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php $sklep_index++; ?>
                <?php endforeach; ?>
            </div>

            <!-- Info o braku wyników wyszukiwania -->
            <div id="brakWynikow" class="brak-wynikow" style="display: none;">
                <div class="brak-wynikow-icon">🔍</div>
                <h3>Nie znaleziono wyników</h3>
                <p>Spróbuj użyć innych słów kluczowych</p>
            </div>

            <button type="button" class="btn-dodaj-sklep" onclick="dodajSklep()">
                ➕ Dodaj nowy sklep
            </button>

            <div class="przyciski-akcji">
                <button type="submit" name="zapisz" class="btn-zapisz">💾 Zapisz zmiany</button>
                <a href="/shopicker/" class="btn-anuluj">❌ Anuluj</a>
            </div>
        </form>
    </div>

    <script>
        let sklepCounter = <?php echo $sklep_index; ?>;
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

        function normalizujString(str) {
            return str.toLowerCase()
                .trim()
                .replace(/\s+/g, ' ')
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
        }

        function checkDuplicates(input) {
            const produktDiv = input.closest('.produkt-edytor');
            const sklepDiv = produktDiv.closest('.sklep-edytor');
            const wartoscInput = normalizujString(input.value);
            
            // Usuń poprzednie ostrzeżenie
            const stareBadge = produktDiv.querySelector('.duplicate-badge');
            if (stareBadge) stareBadge.remove();
            produktDiv.classList.remove('duplicate-warning');

            if (wartoscInput.length < 2) return;

            // Sprawdź duplikaty w tym samym sklepie
            const produkty = sklepDiv.querySelectorAll('.produkt-edytor');
            let znalezionoDuplikat = false;

            produkty.forEach(innyProdukt => {
                if (innyProdukt === produktDiv) return;
                
                const innyInput = innyProdukt.querySelector('input[name*="[name]"]');
                const innaWartosc = normalizujString(innyInput.value);
                
                if (innaWartosc.length < 2) return;

                // Dokładne dopasowanie
                if (wartoscInput === innaWartosc) {
                    znalezionoDuplikat = true;
                    return;
                }

                // Fuzzy matching - próg 80% podobieństwa
                const distance = levenshteinDistance(wartoscInput, innaWartosc);
                const maxLen = Math.max(wartoscInput.length, innaWartosc.length);
                const similarity = 1 - (distance / maxLen);

                if (similarity >= 0.8) {
                    znalezionoDuplikat = true;
                }
            });

            if (znalezionoDuplikat) {
                produktDiv.classList.add('duplicate-warning');
                const badge = document.createElement('span');
                badge.className = 'duplicate-badge';
                badge.textContent = '⚠️';
                badge.title = 'Możliwy duplikat produktu';
                produktDiv.appendChild(badge);
            }
        }

        // ========================================
        // WYSZUKIWANIE Z PRZYCISKIEM CZYSZCZENIA
        // ========================================

        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        const searchClear = document.getElementById('searchClear');
        
        searchInput?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            
            // Pokaż/ukryj przycisk X
            if (e.target.value) {
                searchClear.classList.add('visible');
            } else {
                searchClear.classList.remove('visible');
            }
            
            searchTimeout = setTimeout(() => {
                filterSklepy(e.target.value);
            }, 300);
        });

        searchClear?.addEventListener('click', () => {
            searchInput.value = '';
            searchClear.classList.remove('visible');
            filterSklepy('');
            searchInput.focus();
        });

        function filterSklepy(query) {
            const search = query.toLowerCase().trim();
            const sklepy = document.querySelectorAll('.sklep-edytor');
            let visibleCount = 0;

            if (!search) {
                sklepy.forEach(sklep => {
                    sklep.classList.remove('hidden');
                    sklep.querySelectorAll('.produkt-edytor').forEach(p => {
                        p.style.display = '';
                    });
                });
                document.getElementById('brakWynikow').style.display = 'none';
                restoreCollapsedStates();
                return;
            }

            sklepy.forEach(sklep => {
                const nazwaSklep = sklep.querySelector('input[name*="[nazwa]"]').value.toLowerCase();
                const produkty = sklep.querySelectorAll('.produkt-edytor');
                let sklepVisible = false;

                if (nazwaSklep.includes(search)) {
                    sklepVisible = true;
                    produkty.forEach(p => p.style.display = '');
                } else {
                    let visibleProducts = 0;
                    produkty.forEach(produkt => {
                        const nazwaProdukt = produkt.querySelector('input[name*="[name]"]').value.toLowerCase();
                        if (nazwaProdukt.includes(search)) {
                            produkt.style.display = '';
                            visibleProducts++;
                            sklepVisible = true;
                        } else {
                            produkt.style.display = 'none';
                        }
                    });
                }

                if (sklepVisible) {
                    sklep.classList.remove('hidden');
                    sklep.classList.remove('collapsed');
                    visibleCount++;
                } else {
                    sklep.classList.add('hidden');
                }
            });

            document.getElementById('brakWynikow').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        // ========================================
        // ZWIJANIE/ROZWIJANIE SKLEPÓW
        // ========================================

        function toggleSklep(naglowek) {
            const sklep = naglowek.closest('.sklep-edytor');
            sklep.classList.toggle('collapsed');
            
            const sklepIndex = sklep.dataset.sklepIndex;
            const collapsedStates = getCollapsedStates();
            collapsedStates[sklepIndex] = sklep.classList.contains('collapsed');
            localStorage.setItem('shopicker_collapsed', JSON.stringify(collapsedStates));
        }

        function getCollapsedStates() {
            const saved = localStorage.getItem('shopicker_collapsed');
            return saved ? JSON.parse(saved) : {};
        }

        function restoreCollapsedStates() {
            const states = getCollapsedStates();
            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                const index = sklep.dataset.sklepIndex;
                if (states[index]) {
                    sklep.classList.add('collapsed');
                }
            });
        }

        document.getElementById('btnRozwinWszystkie')?.addEventListener('click', () => {
            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                sklep.classList.remove('collapsed');
            });
            localStorage.removeItem('shopicker_collapsed');
        });

        document.getElementById('btnZwinWszystkie')?.addEventListener('click', () => {
            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                sklep.classList.add('collapsed');
            });
            const states = {};
            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                states[sklep.dataset.sklepIndex] = true;
            });
            localStorage.setItem('shopicker_collapsed', JSON.stringify(states));
        });

        // ========================================
        // DRAG AND DROP - SKLEPY
        // ========================================

        function setupSklepDragAndDrop() {
            const sklepy = document.querySelectorAll('.sklep-edytor');
            
            sklepy.forEach(sklep => {
                sklep.addEventListener('dragstart', handleSklepDragStart);
                sklep.addEventListener('dragover', handleSklepDragOver);
                sklep.addEventListener('drop', handleSklepDrop);
                sklep.addEventListener('dragend', handleSklepDragEnd);
                sklep.addEventListener('dragleave', handleSklepDragLeave);
            });
        }

        function handleSklepDragStart(e) {
            draggedElement = this;
            draggedType = 'sklep';
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleSklepDragOver(e) {
            if (draggedType !== 'sklep') return;
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

        function handleSklepDrop(e) {
            if (draggedType !== 'sklep') return;
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            aktualizujIndeksySklepow();
            formZmieniony = true;
        }

        function handleSklepDragEnd(e) {
            this.classList.remove('dragging');
            document.querySelectorAll('.sklep-edytor').forEach(s => s.classList.remove('drag-over'));
        }

        function handleSklepDragLeave(e) {
            this.classList.remove('drag-over');
        }

        // ========================================
        // DRAG AND DROP - PRODUKTY
        // ========================================

        function setupProduktDragAndDrop() {
            const produkty = document.querySelectorAll('.produkt-edytor');
            
            produkty.forEach(produkt => {
                produkt.addEventListener('dragstart', handleProduktDragStart);
                produkt.addEventListener('dragover', handleProduktDragOver);
                produkt.addEventListener('drop', handleProduktDrop);
                produkt.addEventListener('dragend', handleProduktDragEnd);
            });
        }

        function handleProduktDragStart(e) {
            draggedElement = this;
            draggedType = 'produkt';
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.stopPropagation();
        }

        function handleProduktDragOver(e) {
            if (draggedType !== 'produkt') return;
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

        function handleProduktDrop(e) {
            if (draggedType !== 'produkt') return;
            e.preventDefault();
            e.stopPropagation();
            
            const sklepDiv = this.closest('.sklep-edytor');
            const sklepIndex = sklepDiv.dataset.sklepIndex;
            aktualizujIndeksyProduktow(sklepDiv, sklepIndex);
            formZmieniony = true;
        }

        function handleProduktDragEnd(e) {
            this.classList.remove('dragging');
            e.stopPropagation();
        }

        // ========================================
        // FUNKCJE POMOCNICZE
        // ========================================

        function getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('.sklep-edytor:not(.dragging), .produkt-edytor:not(.dragging)')];
            
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

        function aktualizujIndeksySklepow() {
            const sklepy = document.querySelectorAll('.sklep-edytor');
            sklepy.forEach((sklep, index) => {
                sklep.dataset.sklepIndex = index;
                const nazwaInput = sklep.querySelector('input[name^="sklepy["]');
                nazwaInput.name = `sklepy[${index}][nazwa]`;
                
                aktualizujIndeksyProduktow(sklep, index);
            });
        }

        function aktualizujIndeksyProduktow(sklepDiv, sklepIndex) {
            const produkty = sklepDiv.querySelectorAll('.produkt-edytor');
            produkty.forEach((produkt, pIndex) => {
                const inputy = produkt.querySelectorAll('input[type="text"]');
                inputy[0].name = `sklepy[${sklepIndex}][produkty][${pIndex}][name]`;
                inputy[1].name = `sklepy[${sklepIndex}][produkty][${pIndex}][unit]`;
            });
            
            aktualizujLicznikProduktow(sklepDiv);
        }

        function aktualizujLicznikProduktow(sklepDiv) {
            const licznik = sklepDiv.querySelector('.liczba-produktow');
            const iloscProduktow = sklepDiv.querySelectorAll('.produkt-edytor').length;
            if (licznik) {
                licznik.textContent = iloscProduktow;
            }
        }

        // ========================================
        // DODAWANIE/USUWANIE ELEMENTÓW
        // ========================================

        function dodajSklep() {
            const kontener = document.getElementById('kontenerSklepy');
            const nowyIndex = sklepCounter++;
            
            const sklepHTML = `
                <div class="sklep-edytor" data-sklep-index="${nowyIndex}" draggable="true">
                    <div class="sklep-naglowek" onclick="toggleSklep(this)">
                        <span class="toggle-icon">▼</span>
                        <span class="sklep-naglowek-drag" 
                              draggable="true"
                              onclick="event.stopPropagation()"
                              title="Przeciągnij, aby zmienić kolejność">☰</span>
                        <input type="text" 
                               name="sklepy[${nowyIndex}][nazwa]" 
                               placeholder="Nazwa sklepu"
                               required
                               onclick="event.stopPropagation()"
                               aria-label="Nazwa sklepu">
                        <span class="licznik-produktow">
                            📦 <span class="liczba-produktow">0</span>
                        </span>
                        <div class="sklep-akcje" onclick="event.stopPropagation()">
                            <button type="button" class="btn-usun-sklep" onclick="usunSklep(this)" title="Usuń sklep">
                                🗑️ Usuń
                            </button>
                        </div>
                    </div>
                    
                    <div class="sklep-zawartość">
                        <div class="dodaj-produkt-gora">
                            <button type="button" class="btn-dodaj" onclick="dodajProdukt(this, true)">
                                ➕ Dodaj produkt
                            </button>
                        </div>
                        
                        <div class="produkty-kontener">
                            <div class="pusty-sklep-info">
                                Brak produktów. Dodaj pierwszy produkt powyżej.
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            kontener.insertAdjacentHTML('beforeend', sklepHTML);
            setupSklepDragAndDrop();
            setupProduktDragAndDrop();
            formZmieniony = true;
            
            setTimeout(() => {
                const nowyElement = kontener.lastElementChild;
                nowyElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                nowyElement.querySelector('input').focus();
            }, 100);
        }

        function dodajProdukt(button, naGorze = false) {
            const sklepDiv = button.closest('.sklep-edytor');
            const sklepIndex = sklepDiv.dataset.sklepIndex;
            const produktyKontener = sklepDiv.querySelector('.produkty-kontener');
            
            const pustyInfo = produktyKontener.querySelector('.pusty-sklep-info');
            if (pustyInfo) {
                pustyInfo.remove();
            }
            
            const aktualnaIlosc = produktyKontener.querySelectorAll('.produkt-edytor').length;
            
            const produktHTML = `
                <div class="produkt-edytor" draggable="true">
                    <span class="produkt-drag-handle" title="Przeciągnij, aby zmienić kolejność">☰</span>
                    <input type="text" 
                           name="sklepy[${sklepIndex}][produkty][${aktualnaIlosc}][name]"
                           placeholder="Nazwa produktu"
                           required
                           oninput="checkDuplicates(this)"
                           aria-label="Nazwa produktu">
                    <input type="text" 
                           name="sklepy[${sklepIndex}][produkty][${aktualnaIlosc}][unit]"
                           placeholder="np. kg, szt, l"
                           required
                           aria-label="Jednostka">
                    <button type="button" class="btn-usun-produkt" onclick="usunProdukt(this)" title="Usuń produkt">🗑️</button>
                </div>
            `;
            
            if (naGorze) {
                produktyKontener.insertAdjacentHTML('afterbegin', produktHTML);
            } else {
                produktyKontener.insertAdjacentHTML('beforeend', produktHTML);
            }
            
            setupProduktDragAndDrop();
            aktualizujIndeksyProduktow(sklepDiv, sklepIndex);
            formZmieniony = true;
            
            setTimeout(() => {
                const nowyProdukt = naGorze ? produktyKontener.firstElementChild : produktyKontener.lastElementChild;
                nowyProdukt.querySelector('input').focus();
            }, 100);
        }

        function usunProdukt(button) {
            if (confirm('Czy na pewno usunąć ten produkt?')) {
                const produktDiv = button.closest('.produkt-edytor');
                const sklepDiv = produktDiv.closest('.sklep-edytor');
                const sklepIndex = sklepDiv.dataset.sklepIndex;
                const produktyKontener = sklepDiv.querySelector('.produkty-kontener');
                
                produktDiv.remove();
                aktualizujIndeksyProduktow(sklepDiv, sklepIndex);
                
                if (produktyKontener.querySelectorAll('.produkt-edytor').length === 0) {
                    produktyKontener.innerHTML = '<div class="pusty-sklep-info">Brak produktów. Dodaj pierwszy produkt powyżej.</div>';
                    aktualizujLicznikProduktow(sklepDiv);
                }
                
                formZmieniony = true;
            }
        }

        function usunSklep(button) {
            if (confirm('Czy na pewno usunąć cały sklep z wszystkimi produktami?')) {
                button.closest('.sklep-edytor').remove();
                aktualizujIndeksySklepow();
                formZmieniony = true;
            }
        }

        // ========================================
        // PŁYWAJĄCY PRZYCISK ZAPISZ
        // ========================================

        let plywajacyPrzyciskElement = null;
        let lastScrollTop = 0;

        function pokazPrzycisk() {
            if (!plywajacyPrzyciskElement) return;
            
            const scrollPos = window.scrollY || document.documentElement.scrollTop;
            
            if (scrollPos > 200 && scrollPos > lastScrollTop) {
                plywajacyPrzyciskElement.style.display = 'block';
            } else if (scrollPos < 100) {
                plywajacyPrzyciskElement.style.display = 'none';
            }
            
            lastScrollTop = scrollPos <= 0 ? 0 : scrollPos;
        }

        function submitFormZPlywajecgo() {
            const formEdycja = document.getElementById('formEdycja');
            const przyciskZapisz = formEdycja.querySelector('button[name="zapisz"]');
            
            if (przyciskZapisz) {
                przyciskZapisz.click();
            } else {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'zapisz';
                hiddenInput.value = '1';
                formEdycja.appendChild(hiddenInput);
                formEdycja.submit();
            }
        }

        // ========================================
        // INICJALIZACJA
        // ========================================

        let formZmieniony = false;

        document.addEventListener('DOMContentLoaded', () => {
            setupSklepDragAndDrop();
            setupProduktDragAndDrop();
            restoreCollapsedStates();

            plywajacyPrzyciskElement = document.getElementById('plywajacyPrzycisk');
            
            if (plywajacyPrzyciskElement) {
                let scrollTimeout;
                window.addEventListener('scroll', () => {
                    if (scrollTimeout) clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(pokazPrzycisk, 50);
                });

                pokazPrzycisk();
            }

            const formEdycja = document.getElementById('formEdycja');
            
            if (formEdycja) {
                formEdycja.addEventListener('change', () => {
                    formZmieniony = true;
                });

                formEdycja.addEventListener('input', () => {
                    formZmieniony = true;
                });

                formEdycja.addEventListener('submit', () => {
                    formZmieniony = false;
                });
            }

            window.addEventListener('beforeunload', (e) => {
                if (formZmieniony) {
                    e.preventDefault();
                    e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
                }
            });

            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                aktualizujLicznikProduktow(sklep);
            });
        });

        // ========================================
        // SKRÓTY KLAWISZOWE
        // ========================================

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                submitFormZPlywajecgo();
            }
            
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                searchInput?.focus();
            }
            
            if (e.key === 'Escape' && searchInput === document.activeElement) {
                searchInput.value = '';
                searchClear.classList.remove('visible');
                filterSklepy('');
            }
        });
    </script>
	
    <!-- Pływający przycisk zapisz -->
    <div id="plywajacyPrzycisk" class="plywajacy-zapisz" style="display: none;">
        <button type="button" onclick="submitFormZPlywajecgo()" class="btn-plywajacy-zapisz" title="Zapisz zmiany (Ctrl+S)">
            💾 Zapisz zmiany
        </button>
    </div>

</body>
</html>