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
            --danger-color: #f44336;
            --danger-hover: #d32f2f;
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
           SKLEPY
           ======================================== */
        
        .sklep-edytor {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
            cursor: move;
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
        }
        
        .sklep-edytor:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
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
        
        .sklep-naglowek {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .sklep-naglowek-drag {
            cursor: grab;
            font-size: 1.5em;
            color: #999;
            padding: 8px;
            user-select: none;
            transition: var(--transition);
            border-radius: 4px;
        }
        
        .sklep-naglowek-drag:hover {
            color: var(--primary-color);
            background: var(--bg-light);
        }
        
        .sklep-naglowek-drag:active {
            cursor: grabbing;
            color: var(--primary-hover);
        }
        
        .sklep-naglowek input {
            flex: 1;
            font-size: 1.2em;
            font-weight: 600;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            transition: var(--transition);
            background: var(--bg-lighter);
        }
        
        .sklep-naglowek input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        /* ========================================
           PRODUKTY
           ======================================== */
        
        .produkty-kontener {
            background: var(--bg-lighter);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .produkt-edytor {
            display: grid;
            grid-template-columns: auto 1fr 120px auto;
            gap: 10px;
            margin-bottom: 12px;
            align-items: center;
            background: white;
            padding: 12px;
            border-radius: var(--radius);
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
        
        .produkt-drag-handle {
            cursor: grab;
            font-size: 1.2em;
            color: #bbb;
            padding: 4px 8px;
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
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            width: 100%;
            font-size: 0.95em;
            transition: var(--transition);
        }
        
        .produkt-edytor input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        }
        
        /* ========================================
           PRZYCISKI
           ======================================== */
        
        .btn-base {
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-base:active {
            transform: scale(0.98);
        }
        
        .btn-usun {
            background: var(--danger-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9em;
        }
        
        .btn-usun:hover {
            background: var(--danger-hover);
            box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
        }
        
        .btn-dodaj {
            background: var(--primary-color);
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-dodaj:hover {
            background: var(--primary-hover);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        
        .btn-dodaj-sklep {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            margin: 24px 0;
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
           WSKAŹNIK LICZBY PRODUKTÓW
           ======================================== */
        
        .licznik-produktow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-light);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #666;
            font-weight: 500;
        }
        
        /* ========================================
           PUSTY SKLEP
           ======================================== */
        
        .pusty-sklep-info {
            text-align: center;
            padding: 30px;
            background: var(--bg-lighter);
            border-radius: var(--radius);
            color: #999;
            font-style: italic;
        }
        
        /* ========================================
           RESPONSYWNOŚĆ
           ======================================== */
        
        @media (max-width: 768px) {
            .edytor-kontener {
                padding: 12px;
                margin: 12px auto;
            }
            
            .sklep-edytor {
                padding: 16px;
                margin-bottom: 16px;
            }
            
            .sklep-naglowek {
                flex-wrap: wrap;
            }
            
            .sklep-naglowek input {
                font-size: 1.1em;
                width: 100%;
                order: 2;
            }
            
            .sklep-naglowek-drag {
                order: 1;
            }
            
            .btn-usun {
                order: 3;
                width: 100%;
                padding: 12px;
            }
            
            .produkty-kontener {
                padding: 12px;
            }
            
            .produkt-edytor {
                grid-template-columns: auto 1fr;
                gap: 8px;
                padding: 10px;
            }
            
            .produkt-drag-handle {
                grid-row: 1 / 4;
            }
            
            .produkt-edytor input[type="text"]:nth-of-type(1) {
                grid-column: 2;
            }
            
            .produkt-edytor input[type="text"]:nth-of-type(2) {
                grid-column: 2;
            }
            
            .produkt-edytor .btn-usun {
                grid-column: 1 / 3;
                width: 100%;
            }
            
            .przyciski-akcji {
                flex-direction: column;
            }
            
            .btn-zapisz,
            .btn-anuluj {
                width: 100%;
            }
            
            .plywajacy-zapisz {
                bottom: 12px;
                right: 12px;
                left: 12px;
            }
            
            .btn-plywajacy-zapisz {
                width: 100%;
                justify-content: center;
                padding: 18px 20px;
                font-size: 1.15em;
            }
        }
        
        @media (max-width: 480px) {
            .sklep-naglowek input {
                font-size: 1em;
                padding: 10px 12px;
            }
            
            .produkt-edytor input[type="text"] {
                font-size: 0.9em;
                padding: 8px 10px;
            }
            
            .sklep-naglowek-drag {
                font-size: 1.3em;
            }
            
            .btn-plywajacy-zapisz {
                padding: 16px 18px;
                font-size: 1.1em;
            }
        }
        
        /* ========================================
           ANIMACJE HOVER
           ======================================== */
        
        @media (hover: hover) {
            .sklep-edytor::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
                border-radius: var(--radius) var(--radius) 0 0;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .sklep-edytor:hover::before {
                opacity: 1;
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

        <form method="POST" id="formEdycja">
            <div id="kontenerSklepy">
                <?php $sklep_index = 0; ?>
                <?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty): ?>
                    <div class="sklep-edytor" data-sklep-index="<?php echo $sklep_index; ?>" draggable="true">
                        <div class="sklep-naglowek">
                            <span class="sklep-naglowek-drag" title="Przeciągnij, aby zmienić kolejność">☰</span>
                            <input type="text" 
                                   name="sklepy[<?php echo $sklep_index; ?>][nazwa]" 
                                   value="<?php echo htmlspecialchars($sklep_nazwa); ?>"
                                   placeholder="Nazwa sklepu"
                                   required
                                   aria-label="Nazwa sklepu">
                            <span class="licznik-produktow">
                                📦 <span class="liczba-produktow"><?php echo count($produkty); ?></span>
                            </span>
                            <button type="button" class="btn-usun" onclick="usunSklep(this)" title="Usuń sklep">
                                🗑️ Usuń sklep
                            </button>
                        </div>
                        
                        <div class="produkty-kontener">
                            <?php if (empty($produkty)): ?>
                                <div class="pusty-sklep-info">
                                    Brak produktów. Dodaj pierwszy produkt poniżej.
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
                                               aria-label="Nazwa produktu">
                                        <input type="text" 
                                               name="sklepy[<?php echo $sklep_index; ?>][produkty][<?php echo $produkt_index; ?>][unit]"
                                               value="<?php echo htmlspecialchars($produkt['unit']); ?>"
                                               placeholder="np. kg, szt, l"
                                               required
                                               aria-label="Jednostka">
                                        <button type="button" class="btn-usun" onclick="usunProdukt(this)" title="Usuń produkt">🗑️</button>
                                    </div>
                                    <?php $produkt_index++; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" class="btn-dodaj" onclick="dodajProdukt(this)">
                            ➕ Dodaj produkt
                        </button>
                    </div>
                    <?php $sklep_index++; ?>
                <?php endforeach; ?>
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
            
            // Aktualizuj licznik produktów
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
                    <div class="sklep-naglowek">
                        <span class="sklep-naglowek-drag" title="Przeciągnij, aby zmienić kolejność">☰</span>
                        <input type="text" 
                               name="sklepy[${nowyIndex}][nazwa]" 
                               placeholder="Nazwa sklepu"
                               required
                               aria-label="Nazwa sklepu">
                        <span class="licznik-produktow">
                            📦 <span class="liczba-produktow">1</span>
                        </span>
                        <button type="button" class="btn-usun" onclick="usunSklep(this)" title="Usuń sklep">
                            🗑️ Usuń sklep
                        </button>
                    </div>
                    
                    <div class="produkty-kontener">
                        <div class="produkt-edytor" draggable="true">
                            <span class="produkt-drag-handle" title="Przeciągnij, aby zmienić kolejność">☰</span>
                            <input type="text" 
                                   name="sklepy[${nowyIndex}][produkty][0][name]"
                                   placeholder="Nazwa produktu"
                                   required
                                   aria-label="Nazwa produktu">
                            <input type="text" 
                                   name="sklepy[${nowyIndex}][produkty][0][unit]"
                                   placeholder="np. kg, szt, l"
                                   required
                                   aria-label="Jednostka">
                            <button type="button" class="btn-usun" onclick="usunProdukt(this)" title="Usuń produkt">🗑️</button>
                        </div>
                    </div>
                    
                    <button type="button" class="btn-dodaj" onclick="dodajProdukt(this)">
                        ➕ Dodaj produkt
                    </button>
                </div>
            `;
            
            kontener.insertAdjacentHTML('beforeend', sklepHTML);
            setupSklepDragAndDrop();
            setupProduktDragAndDrop();
            formZmieniony = true;
            
            // Scroll do nowego sklepu
            setTimeout(() => {
                const nowyElement = kontener.lastElementChild;
                nowyElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                nowyElement.querySelector('input').focus();
            }, 100);
        }

        function dodajProdukt(button) {
            const sklepDiv = button.closest('.sklep-edytor');
            const sklepIndex = sklepDiv.dataset.sklepIndex;
            const produktyKontener = sklepDiv.querySelector('.produkty-kontener');
            
            // Usuń info o pustym sklepie jeśli istnieje
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
                           aria-label="Nazwa produktu">
                    <input type="text" 
                           name="sklepy[${sklepIndex}][produkty][${aktualnaIlosc}][unit]"
                           placeholder="np. kg, szt, l"
                           required
                           aria-label="Jednostka">
                    <button type="button" class="btn-usun" onclick="usunProdukt(this)" title="Usuń produkt">🗑️</button>
                </div>
            `;
            
            produktyKontener.insertAdjacentHTML('beforeend', produktHTML);
            setupProduktDragAndDrop();
            aktualizujLicznikProduktow(sklepDiv);
            formZmieniony = true;
            
            // Focus na nowym produkcie
            setTimeout(() => {
                const nowyProdukt = produktyKontener.lastElementChild;
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
                
                // Pokaż info o pustym sklepie jeśli to był ostatni produkt
                if (produktyKontener.querySelectorAll('.produkt-edytor').length === 0) {
                    produktyKontener.innerHTML = '<div class="pusty-sklep-info">Brak produktów. Dodaj pierwszy produkt poniżej.</div>';
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
            
            // Pokaż przycisk gdy scroll > 200px i scrollujemy w dół
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
            // Setup drag and drop
            setupSklepDragAndDrop();
            setupProduktDragAndDrop();

            // Setup pływający przycisk
            plywajacyPrzyciskElement = document.getElementById('plywajacyPrzycisk');
            
            if (plywajacyPrzyciskElement) {
                let scrollTimeout;
                window.addEventListener('scroll', () => {
                    if (scrollTimeout) clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(pokazPrzycisk, 50);
                });

                pokazPrzycisk();
            }

            // Setup formularza
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

            // Ostrzeżenie przed opuszczeniem
            window.addEventListener('beforeunload', (e) => {
                if (formZmieniony) {
                    e.preventDefault();
                    e.returnValue = 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?';
                }
            });

            // Aktualizuj liczniki przy załadowaniu
            document.querySelectorAll('.sklep-edytor').forEach(sklep => {
                aktualizujLicznikProduktow(sklep);
            });
        });

        // ========================================
        // SKRÓTY KLAWISZOWE
        // ========================================

        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S = Zapisz
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                submitFormZPlywajecgo();
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