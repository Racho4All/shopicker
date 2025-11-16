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
        .edytor-kontener {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .sklep-edytor {
            background: #f9f9f9;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .sklep-edytor.dragging {
            opacity: 0.5;
            border-color: #4CAF50;
        }
        
        .sklep-edytor.drag-over {
            border-color: #2196F3;
            background: #e3f2fd;
        }
        
        .sklep-naglowek {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sklep-naglowek-drag {
            cursor: move;
            font-size: 1.5em;
            color: #666;
            padding: 0 10px;
            user-select: none;
        }
        
        .sklep-naglowek input {
            font-size: 1.2em;
            font-weight: bold;
            padding: 8px;
            border: 2px solid #4CAF50;
            border-radius: 4px;
            flex: 1;
            min-width: 200px;
        }
        
        .produkt-edytor {
            display: grid;
            grid-template-columns: 2fr 1fr auto auto;
            gap: 8px;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .produkt-edytor.dragging {
            opacity: 0.5;
        }
        
        .produkt-drag-handle {
            cursor: move;
            font-size: 1.2em;
            color: #999;
            user-select: none;
            text-align: center;
        }
        
        .produkt-edytor input[type="text"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .btn-usun {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            white-space: nowrap;
        }
        
        .btn-usun:hover {
            background: #d32f2f;
        }
        
        .btn-dodaj {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-dodaj:hover {
            background: #45a049;
        }
        
        .btn-dodaj-sklep {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            margin: 20px 0;
            width: 100%;
        }
        
        .btn-dodaj-sklep:hover {
            background: #1976D2;
        }
        
        .przyciski-akcji {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .btn-zapisz {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-zapisz:hover {
            background: #45a049;
        }
        
        .btn-anuluj {
            background: #757575;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-anuluj:hover {
            background: #616161;
        }
        
        .komunikat {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .komunikat.sukces {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .komunikat.blad {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        /* Responsywność dla małych ekranów */
        @media (max-width: 768px) {
            .edytor-kontener {
                padding: 10px;
                margin: 10px auto;
            }
            
            .sklep-naglowek {
                flex-direction: column;
                align-items: stretch;
            }
            
            .sklep-naglowek input {
                font-size: 1em;
                min-width: auto;
                width: 100%;
            }
            
            .btn-usun {
                width: 100%;
                padding: 10px;
            }
            
            .produkt-edytor {
                grid-template-columns: auto 1fr;
                gap: 5px;
            }
            
            .produkt-drag-handle {
                grid-row: 1 / 3;
            }
            
            .produkt-edytor input[type="text"]:first-of-type {
                grid-column: 2;
            }
            
            .produkt-edytor input[type="text"]:last-of-type {
                grid-column: 2;
            }
            
            .produkt-edytor .btn-usun {
                grid-column: 1 / 3;
                margin-top: 5px;
            }
            
            .btn-dodaj {
                width: 100%;
            }
            
            .przyciski-akcji {
                flex-direction: column;
            }
            
            .btn-zapisz,
            .btn-anuluj {
                width: 100%;
                min-width: auto;
            }
        }
        
        @media (max-width: 480px) {
            .sklep-naglowek input {
                font-size: 0.9em;
                padding: 6px;
            }
            
            .produkt-edytor input[type="text"] {
                font-size: 0.9em;
                padding: 6px;
            }
            
            .sklep-naglowek-drag {
                font-size: 1.2em;
            }
        }
        .btn-powrot-sukces {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
            font-size: 1em;
        }
        
        .btn-powrot-sukces:hover {
            background: #45a049;
        }
        
        @media (max-width: 768px) {
            .btn-powrot-sukces {
                display: block;
                text-align: center;
                width: 100%;
            }
        }
        /* Pływający przycisk zapisz */
        .plywajacy-zapisz {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            animation: fadeInUp 0.3s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-plywajacy-zapisz {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-plywajacy-zapisz:hover {
            background: #45a049;
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
        
        .btn-plywajacy-zapisz:active {
            transform: scale(0.95);
        }
        
        @media (max-width: 768px) {
            .plywajacy-zapisz {
                bottom: 15px;
                right: 15px;
                left: 15px;
            }
            
            .btn-plywajacy-zapisz {
                width: 100%;
                justify-content: center;
                padding: 18px 20px;
                font-size: 1.2em;
            }
        }
        
        @media (max-width: 480px) {
            .plywajacy-zapisz {
                bottom: 10px;
                right: 10px;
                left: 10px;
            }
            
            .btn-plywajacy-zapisz {
                padding: 16px 18px;
                font-size: 1.1em;
            }
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
                            <span class="sklep-naglowek-drag">☰</span>
                            <input type="text" 
                                   name="sklepy[<?php echo $sklep_index; ?>][nazwa]" 
                                   value="<?php echo htmlspecialchars($sklep_nazwa); ?>"
                                   placeholder="Nazwa sklepu"
                                   required>
                            <button type="button" class="btn-usun" onclick="usunSklep(this)">
                                🗑️ Usuń sklep
                            </button>
                        </div>
                        
                        <div class="produkty-kontener">
                            <?php $produkt_index = 0; ?>
                            <?php foreach ($produkty as $produkt): ?>
                                <div class="produkt-edytor" draggable="true">
                                    <span class="produkt-drag-handle">☰</span>
                                    <input type="text" 
                                           name="sklepy[<?php echo $sklep_index; ?>][produkty][<?php echo $produkt_index; ?>][name]"
                                           value="<?php echo htmlspecialchars($produkt['name']); ?>"
                                           placeholder="Nazwa produktu"
                                           required>
                                    <input type="text" 
                                           name="sklepy[<?php echo $sklep_index; ?>][produkty][<?php echo $produkt_index; ?>][unit]"
                                           value="<?php echo htmlspecialchars($produkt['unit']); ?>"
                                           placeholder="Jednostka"
                                           required>
                                    <button type="button" class="btn-usun" onclick="usunProdukt(this)">🗑️</button>
                                </div>
                                <?php $produkt_index++; ?>
                            <?php endforeach; ?>
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
        let draggedType = null; // 'sklep' lub 'produkt'

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
                        <span class="sklep-naglowek-drag">☰</span>
                        <input type="text" 
                               name="sklepy[${nowyIndex}][nazwa]" 
                               placeholder="Nazwa sklepu"
                               required>
                        <button type="button" class="btn-usun" onclick="usunSklep(this)">
                            🗑️ Usuń sklep
                        </button>
                    </div>
                    
                    <div class="produkty-kontener">
                        <div class="produkt-edytor" draggable="true">
                            <span class="produkt-drag-handle">☰</span>
                            <input type="text" 
                                   name="sklepy[${nowyIndex}][produkty][0][name]"
                                   placeholder="Nazwa produktu"
                                   required>
                            <input type="text" 
                                   name="sklepy[${nowyIndex}][produkty][0][unit]"
                                   placeholder="Jednostka"
                                   required>
                            <button type="button" class="btn-usun" onclick="usunProdukt(this)">🗑️</button>
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
        }

        function dodajProdukt(button) {
            const sklepDiv = button.closest('.sklep-edytor');
            const sklepIndex = sklepDiv.dataset.sklepIndex;
            const produktyKontener = sklepDiv.querySelector('.produkty-kontener');
            const aktualnaIlosc = produktyKontener.querySelectorAll('.produkt-edytor').length;
            
            const produktHTML = `
                <div class="produkt-edytor" draggable="true">
                    <span class="produkt-drag-handle">☰</span>
                    <input type="text" 
                           name="sklepy[${sklepIndex}][produkty][${aktualnaIlosc}][name]"
                           placeholder="Nazwa produktu"
                           required>
                    <input type="text" 
                           name="sklepy[${sklepIndex}][produkty][${aktualnaIlosc}][unit]"
                           placeholder="Jednostka"
                           required>
                    <button type="button" class="btn-usun" onclick="usunProdukt(this)">🗑️</button>
                </div>
            `;
            
            produktyKontener.insertAdjacentHTML('beforeend', produktHTML);
            setupProduktDragAndDrop();
            formZmieniony = true;
        }

        function usunProdukt(button) {
            if (confirm('Czy na pewno usunąć ten produkt?')) {
                const produktDiv = button.closest('.produkt-edytor');
                const sklepDiv = produktDiv.closest('.sklep-edytor');
                const sklepIndex = sklepDiv.dataset.sklepIndex;
                
                produktDiv.remove();
                aktualizujIndeksyProduktow(sklepDiv, sklepIndex);
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

        function pokazPrzycisk() {
            if (!plywajacyPrzyciskElement) return;
            
            const scrollPos = window.scrollY;
            
            if (scrollPos > 200) {
                plywajacyPrzyciskElement.style.display = 'block';
            } else {
                plywajacyPrzyciskElement.style.display = 'none';
            }
        }

        function submitFormZPlywajecgo() {
            // Symuluj kliknięcie normalnego przycisku zapisz
            const formEdycja = document.getElementById('formEdycja');
            const przyciskZapisz = formEdycja.querySelector('button[name="zapisz"]');
            
            if (przyciskZapisz) {
                przyciskZapisz.click();
            } else {
                // Fallback - dodaj ukryte pole i submituj
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
                // Sprawdzaj przy scrollowaniu
                let scrollTimeout;
                window.addEventListener('scroll', () => {
                    if (scrollTimeout) clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(pokazPrzycisk, 50);
                });

                // Sprawdź od razu przy załadowaniu
                pokazPrzycisk();
            }

            // Setup formularza
            const formEdycja = document.getElementById('formEdycja');
            
            if (formEdycja) {
                formEdycja.addEventListener('change', () => {
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
                    e.returnValue = '';
                }
            });
        });
    </script>
	
    <!-- Pływający przycisk zapisz -->
    <div id="plywajacyPrzycisk" class="plywajacy-zapisz" style="display: none;">
        <button type="button" onclick="submitFormZPlywajecgo()" class="btn-plywajacy-zapisz">
            💾 Zapisz zmiany
        </button>
    </div>

</body>

</body>
</html>