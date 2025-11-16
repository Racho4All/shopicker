<?php
// ============================================
// SHOPICKER - Lista zakupów
// Wersja: 2.1 (ulepszona UX)
// ============================================

$plik_danych = 'statusy_sklepy.txt';
$produkty_sklepy = require __DIR__ . '/produkty_sklepy.php';

if (!is_array($produkty_sklepy)) {
    die('Błąd: plik produkty_sklepy.php nie zwrócił poprawnej tablicy.');
}

// ============================================
// FUNKCJE POMOCNICZE
// ============================================

function wczytajIlosci($plik) {
    if (!file_exists($plik)) return [];
    $json = file_get_contents($plik);
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
    
    return @file_put_contents($plik, $json, LOCK_EX) !== false;
}

function generuj_id_kotwicy($sklep, $produkt) {
    return urlencode($sklep) . '_' . urlencode($produkt);
}

function przekierujZFiltrami() {
    $parametry = [];
    
    if (!empty($_POST['widoczne_sklepy'])) {
        $parametry['sklepy'] = $_POST['widoczne_sklepy'];
    }
    
    if (!empty($_POST['widoczne_tryb']) && $_POST['widoczne_tryb'] === 'ukryte') {
        $parametry['tryb'] = 'ukryte';
    }
    
    $qs = $parametry ? '?' . http_build_query($parametry) : '';
    header('Location: /shopicker/' . $qs);
    exit();
}

// ============================================
// OBSŁUGA USTAWIANIA ILOŚCI (POST)
// ============================================

if (isset($_POST['ustaw_ilosc']) && isset($_POST['produkt']) && isset($_POST['ilosc']) && isset($_POST['sklep'])) {
    $ilosci_globalne = wczytajIlosci($plik_danych);
    $produkt = htmlspecialchars($_POST['produkt']);
    $sklep = htmlspecialchars($_POST['sklep']);
    
    if (trim($_POST['ilosc']) === '') {
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
    $ilosci_globalne = wczytajIlosci($plik_danych);
    $produkt = htmlspecialchars($_POST['produkt']);
    $sklep = htmlspecialchars($_POST['sklep']);
    
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

$filtr_sklepy = [];
if (isset($_GET['sklepy']) && $_GET['sklepy'] !== '') {
    $filtr_sklepy = explode(',', $_GET['sklepy']);
}

// ============================================
// STATYSTYKI
// ============================================

$statystyki = [
    'wszystkie' => 0,
    'do_kupienia' => 0,
    'kupione' => 0
];

foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie) {
    if (!empty($filtr_sklepy) && !in_array($sklep_nazwa, $filtr_sklepy)) continue;
    
    foreach ($produkty_w_sklepie as $item) {
        $produkt = $item['name'];
        $ilosc_obecna = isset($aktualne_ilosci[$sklep_nazwa][$produkt]) 
            ? $aktualne_ilosci[$sklep_nazwa][$produkt] 
            : null;
        
        $statystyki['wszystkie']++;
        if ($ilosc_obecna !== null && $ilosc_obecna > 0) {
            $statystyki['do_kupienia']++;
        } else {
            $statystyki['kupione']++;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Shopicker - handy shopping list</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/shopicker/assets/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/shopicker/assets/favicon.svg" />
    <link rel="shortcut icon" href="/shopicker/assets/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/shopicker/assets/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Shopicker" />
    <link rel="manifest" href="/shopicker/assets/site.webmanifest" />
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/shopicker/style.css">
    
    <style>
        /* ========================================
           TOOLBAR Z WYBOREM SKLEPÓW
           ======================================== */
        
        .toolbar-sklepy {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toolbar-sklepy-tytul {
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sklepy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .sklep-checkbox-label {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: #f5f5f5;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .sklep-checkbox-label:hover {
            background: #e8f5e9;
            border-color: #4CAF50;
        }
        
        .sklep-checkbox-label input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .sklep-checkbox-label input[type="checkbox"]:checked + span {
            font-weight: 600;
            color: #4CAF50;
        }
        
        .sklepy-akcje {
            display: flex;
            gap: 8px;
        }
        
        .btn-zaznacz {
            padding: 6px 12px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.2s ease;
        }
        
        .btn-zaznacz:hover {
            background: #1976D2;
        }
        
        /* ========================================
           STATYSTYKI
           ======================================== */
        
        .statystyki-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .statystyki-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }
        
        .stat-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 12px;
            border-radius: 6px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: 700;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        /* ========================================
           SZYBKIE AKCJE
           ======================================== */
        
        .szybkie-akcje {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn-szybka-akcja {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-ukryj-zakupione {
            background: #FF9800;
            color: white;
        }
        
        .btn-ukryj-zakupione:hover {
            background: #F57C00;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        
        .btn-wyczysc-liste {
            background: #f44336;
            color: white;
        }
        
        .btn-wyczysc-liste:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
        }
        
        /* ========================================
           ULEPSZENIA PRODUKTÓW
           ======================================== */
        
        .lista li {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .lista li.status-need {
            background: #fff3e0;
            border-left: 4px solid #FF9800;
        }
        
        .lista li.status-have {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        
        .lista li.ukryty {
            opacity: 0;
            max-height: 0;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        
        .nazwa-produktu {
            font-weight: 600;
            flex: 1;
        }
        
        .ilosc-tekst {
            font-weight: 500;
            color: #FF9800;
        }
        
        .status-have .ilosc-tekst {
            color: #4CAF50;
        }
        
        /* Animacja po kliknięciu "Kupione" */
        @keyframes zakupiono {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); background: #4CAF50; }
            100% { transform: scale(1); }
        }
        
        .zakupiono-animacja {
            animation: zakupiono 0.5s ease;
        }
        
        /* ========================================
           RESPONSYWNOŚĆ
           ======================================== */
        
        @media (max-width: 768px) {
            .toolbar-sklepy {
                position: relative;
            }
            
            .sklepy-grid {
                grid-template-columns: 1fr;
            }
            
            .statystyki-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            .stat-number {
                font-size: 1.5em;
            }
            
            .stat-label {
                font-size: 0.8em;
            }
            
            .szybkie-akcje {
                flex-direction: column;
            }
            
            .btn-szybka-akcja {
                width: 100%;
            }
        }
        
        /* ========================================
           LICZNIK SKLEPU
           ======================================== */
        
        .sklep-nazwa {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sklep-licznik {
            font-size: 0.9em;
            background: #4CAF50;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .sklep-licznik.zero {
            background: #9e9e9e;
        }
        
        /* ========================================
           PUSTE SKLEPY
           ======================================== */
        
        .sklep-sekcja.wszystko-kupione {
            opacity: 0.6;
        }
        
        .sklep-sekcja.wszystko-kupione .sklep-nazwa {
            color: #9e9e9e;
        }
    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- NAGŁÓWEK Z LOGO I PRZYCISKAMI -->
    <!-- ============================================ -->
    
    <div class="naglowek-kontener">
        <h1 class="montserrat-logo">
            <img src="/shopicker/assets/favicon.svg" 
                 alt="Logo" 
                 style="height: 1.5em; vertical-align: middle; margin-right: -0.2em">
            Shopicker
        </h1>
        <div>
            <a href="/shopicker/edytuj.php" class="przycisk-naglowek przycisk-edytuj">✏️ Edytuj</a>
            <a href="/shopicker/" 
               class="przycisk-naglowek przycisk-odswiez" 
               onclick="sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);">
                🔄 Odśwież
            </a>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STATYSTYKI -->
    <!-- ============================================ -->
    
    <div class="statystyki-box">
        <strong>📊 Podsumowanie</strong>
        <div class="statystyki-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo $statystyki['wszystkie']; ?></span>
                <span class="stat-label">Wszystkie</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $statystyki['do_kupienia']; ?></span>
                <span class="stat-label">Do kupienia</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $statystyki['kupione']; ?></span>
                <span class="stat-label">Kupione</span>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SZYBKIE AKCJE -->
    <!-- ============================================ -->
    
    <div class="szybkie-akcje">
        <button id="przyciskUkryj" 
                class="btn-szybka-akcja btn-ukryj-zakupione" 
                onclick="toggleUkryj()">
            <span>👁️</span>
            <span id="tekstPrzyciskuUkryj">Ukryj zakupione</span>
        </button>
        <button class="btn-szybka-akcja btn-wyczysc-liste" 
                onclick="wyczyscListe()">
            <span>🗑️</span>
            <span>Wyczyść listę</span>
        </button>
    </div>

    <!-- ============================================ -->
    <!-- WYBÓR SKLEPÓW -->
    <!-- ============================================ -->
    
    <div class="toolbar-sklepy">
        <div class="toolbar-sklepy-tytul">
            <strong>🏪 Wybierz sklepy</strong>
            <div class="sklepy-akcje">
                <button class="btn-zaznacz" onclick="zaznaczWszystkieSklepy()">✓ Wszystkie</button>
                <button class="btn-zaznacz" onclick="odznaczWszystkieSklepy()">✗ Żadne</button>
            </div>
        </div>
        <div class="sklepy-grid">
            <?php foreach (array_keys($produkty_sklepy) as $sklep_nazwa): ?>
                <label class="sklep-checkbox-label">
                    <input type="checkbox" 
                           class="checkboxSklep" 
                           value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                    <span><?php echo htmlspecialchars($sklep_nazwa); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- LISTY PRODUKTÓW DLA KAŻDEGO SKLEPU -->
    <!-- ============================================ -->

    <?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie): ?>
        <?php if (!empty($filtr_sklepy) && !in_array($sklep_nazwa, $filtr_sklepy)) continue; ?>
        
        <?php
        // Policz produkty do kupienia w tym sklepie
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
        $klasa_sklep = $do_kupienia_sklep === 0 ? 'wszystko-kupione' : '';
        ?>
        
        <div class="sklep-sekcja <?php echo $klasa_sklep; ?>" data-sklep="<?php echo htmlspecialchars($sklep_nazwa); ?>">
            <h2 class="sklep-nazwa">
                <span><?php echo htmlspecialchars($sklep_nazwa); ?></span>
                <span class="sklep-licznik <?php echo $do_kupienia_sklep === 0 ? 'zero' : ''; ?>">
                    <?php echo $do_kupienia_sklep; ?> / <?php echo count($produkty_w_sklepie); ?>
                </span>
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
                        : "✓ Kupione";
                    $wartosc_input = $czy_potrzebny ? $ilosc_obecna : '';
                    $id_elementu = generuj_id_kotwicy($sklep_nazwa, $produkt);
                ?>
                
                <li id="<?php echo htmlspecialchars($id_elementu); ?>" 
                    class="<?php echo $klasa_css; ?>"
                    data-sklep="<?php echo htmlspecialchars($sklep_nazwa); ?>"
                    data-produkt="<?php echo htmlspecialchars($produkt); ?>">
                    
                    <span class="nazwa-produktu">
                        <?php echo htmlspecialchars($produkt); ?> 
                        <span class="ilosc-tekst"><?php echo $ilosc_tekst; ?></span>
                    </span>
                    
                    <div class="formularz-ilosc">
                        
                        <!-- Formularz "Kupione!" -->
                        <form method="POST" 
                              style="display:inline;" 
                              onsubmit="animujKupione(event, '<?php echo htmlspecialchars($id_elementu); ?>')">
                            <input type="hidden" name="produkt" value="<?php echo htmlspecialchars($produkt); ?>">
                            <input type="hidden" name="sklep" value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                            <?php if ($czy_potrzebny): ?>
                                <button type="submit" 
                                        name="oznacz_jako_mam" 
                                        class="przycisk przycisk-mam">
                                    ✓ Kupione!
                                </button>
                            <?php endif; ?>
                        </form>

                        <!-- Formularz ilości/Kup -->
                        <?php if (!$czy_potrzebny): ?>
                            <form method="POST" 
                                  style="display:inline;" 
                                  onsubmit="sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);">
                                <span class="jednostka-miary"><?php echo htmlspecialchars($jednostka); ?></span>
                                <input type="number" 
                                       name="ilosc" 
                                       value="<?php echo htmlspecialchars($wartosc_input); ?>" 
                                       min="0" 
                                       class="wejscie-ilosc"
                                       placeholder="1">
                                <input type="hidden" name="produkt" value="<?php echo htmlspecialchars($produkt); ?>">
                                <input type="hidden" name="sklep" value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                                <button type="submit" 
                                        name="ustaw_ilosc" 
                                        class="przycisk przycisk-zmien">
                                    🛒 Kup
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
        // Klucze localStorage
        const STORAGE_KEY_HIDE = 'listaZakupow_ukryte';
        const STORAGE_KEY_SCROLL = 'shoppingList_scrollPos';
        const STORAGE_KEY_SKLEPY = 'karteczka_wybrane_sklepy';
        
        // Elementy DOM
        const przyciskUkryj = document.getElementById('przyciskUkryj');
        const tekstPrzyciskuUkryj = document.getElementById('tekstPrzyciskuUkryj');
        const checkboxes = document.querySelectorAll('.checkboxSklep');

        // ========================================
        // FUNKCJA: Animacja kupienia
        // ========================================
        
        function animujKupione(event, elementId) {
            sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);
            const element = document.getElementById(elementId);
            if (element) {
                element.classList.add('zakupiono-animacja');
            }
        }

        // ========================================
        // FUNKCJA: Toggle ukrywania produktów
        // ========================================
        
        function toggleUkryj() {
            const elementyGot = document.querySelectorAll('.status-have');
            let wszystkieUkryte = true;
            
            elementyGot.forEach(el => {
                if (!el.classList.contains('ukryty')) {
                    wszystkieUkryte = false;
                }
            });
            
            if (wszystkieUkryte) {
                elementyGot.forEach(el => el.classList.remove('ukryty'));
                tekstPrzyciskuUkryj.textContent = 'Ukryj zakupione';
                localStorage.setItem(STORAGE_KEY_HIDE, 'pokazane');
            } else {
                elementyGot.forEach(el => el.classList.add('ukryty'));
                tekstPrzyciskuUkryj.textContent = 'Pokaż zakupione';
                localStorage.setItem(STORAGE_KEY_HIDE, 'ukryte');
            }
            
            ukryjPusteSekcjeSklepy();
        }

        // ========================================
        // FUNKCJA: Ukrywanie pustych sekcji sklepów
        // ========================================
        
        function ukryjPusteSekcjeSklepy() {
            const sekcjeSklepy = document.querySelectorAll('.sklep-sekcja');
            
            sekcjeSklepy.forEach(sekcja => {
                const lista = sekcja.querySelector('.lista');
                if (!lista) return;
                
                const widoczneElementy = Array.from(lista.querySelectorAll('li')).filter(li => {
                    return !li.classList.contains('ukryty');
                });
                
                if (widoczneElementy.length === 0) {
                    sekcja.classList.add('ukryty');
                } else {
                    sekcja.classList.remove('ukryty');
                }
            });
        }

        // ========================================
        // FUNKCJA: Przywracanie stanu ukrycia
        // ========================================
        
        function przywrocStanUkrycia() {
            const stanZapamietany = localStorage.getItem(STORAGE_KEY_HIDE);
            if (stanZapamietany === 'ukryte') {
                document.querySelectorAll('.status-have').forEach(el => el.classList.add('ukryty'));
                tekstPrzyciskuUkryj.textContent = 'Pokaż zakupione';
            } else {
                tekstPrzyciskuUkryj.textContent = 'Ukryj zakupione';
            }
            
            ukryjPusteSekcjeSklepy();
        }

        // ========================================
        // FUNKCJA: Zapisywanie pozycji scrolla
        // ========================================
        
        window.addEventListener('scroll', () => {
            sessionStorage.setItem(STORAGE_KEY_SCROLL, window.scrollY);
        });

        function przywrocPozycjePrzewijania() {
            const zapisanaPozycja = sessionStorage.getItem(STORAGE_KEY_SCROLL);
            if (zapisanaPozycja) {
                window.scrollTo(0, parseInt(zapisanaPozycja, 10));
                sessionStorage.removeItem(STORAGE_KEY_SCROLL);
            }
        }

        // ========================================
        // FUNKCJA: Dodawanie ukrytych pól do formularzy
        // ========================================
        
        function dodajUkrytePoleSklepy() {
            const aktywne_sklepy = localStorage.getItem(STORAGE_KEY_SKLEPY) || '';
            
            document.querySelectorAll('form').forEach(f => {
                if (!f.querySelector('input[name="widoczne_sklepy"]')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'widoczne_sklepy';
                    hidden.value = aktywne_sklepy;
                    f.appendChild(hidden);
                }
            });
        }

        // ========================================
        // FUNKCJA: Przywracanie wyboru sklepów
        // ========================================
        
		function przywrocWyborSklepow() {
			const urlParams = new URLSearchParams(window.location.search);
			const sklepyZUrl = urlParams.get('sklepy');
			
			if (sklepyZUrl) {
				localStorage.setItem(STORAGE_KEY_SKLEPY, sklepyZUrl);
				const lista = sklepyZUrl.split(',').filter(s => s.trim() !== '');
				checkboxes.forEach(ch => {
					ch.checked = lista.includes(ch.value);
				});
			} else {
				const zapamietane = localStorage.getItem(STORAGE_KEY_SKLEPY);
				if (zapamietane !== null && zapamietane !== '') {
					const lista = zapamietane.split(',').filter(s => s.trim() !== '');
					checkboxes.forEach(ch => {
						ch.checked = lista.includes(ch.value);
					});
				} else if (zapamietane === '') {
					// Pusty string = świadomie odznaczone wszystko
					checkboxes.forEach(ch => {
						ch.checked = false;
					});
				} else {
					// null = pierwszy raz, zaznacz wszystkie
					checkboxes.forEach(ch => {
						ch.checked = true;
					});
				}
			}
		}

        // ========================================
        // FUNKCJA: Zapisywanie wyboru sklepów
        // ========================================
        
        function zapiszWyborSklepow() {
            const wybrane = Array.from(checkboxes)
                .filter(ch => ch.checked)
                .map(ch => ch.value);
            
            localStorage.setItem(STORAGE_KEY_SKLEPY, wybrane.join(','));
            
            const param = wybrane.length ? '?sklepy=' + wybrane.join(',') : '';
            sessionStorage.setItem('shoppingList_scrollPos', 0);
            window.location.href = '/shopicker/' + param;
        }

        // ========================================
        // FUNKCJA: Aktualizacja linku Odśwież
        // ========================================
        
        function aktualizujLinkOdswiez() {
            const wybrane = localStorage.getItem(STORAGE_KEY_SKLEPY);
            const odswiezLink = document.querySelector('.przycisk-odswiez');
            
            if (odswiezLink) {
                if (wybrane && wybrane.trim() !== '') {
                    odswiezLink.href = '/shopicker/?sklepy=' + encodeURIComponent(wybrane);
                } else {
                    odswiezLink.href = '/shopicker/';
                }
            }
        }

        // ========================================
        // FUNKCJA: Zaznacz/odznacz wszystkie sklepy
        // ========================================
        
        function zaznaczWszystkieSklepy() {
            checkboxes.forEach(ch => ch.checked = true);
            zapiszWyborSklepow();
        }
		
		function odznaczWszystkieSklepy() {
			if (confirm('Czy na pewno odznaczyć wszystkie sklepy? Lista będzie pusta.')) {
				checkboxes.forEach(ch => ch.checked = false);
				localStorage.setItem(STORAGE_KEY_SKLEPY, ''); // ← Pusty string zamiast usuwania
				window.location.href = '/shopicker/'; // Reload bez parametrów
			}
		}

        // ========================================
        // FUNKCJA: Wyczyść całą listę
        // ========================================
        
        function wyczyscListe() {
            if (confirm('Czy na pewno chcesz wyczyść całą listę zakupów? Wszystkie produkty zostaną oznaczone jako kupione.')) {
                const formularze = document.querySelectorAll('form[method="POST"]');
                let licznik = 0;
                
                formularze.forEach(form => {
                    if (form.querySelector('button[name="oznacz_jako_mam"]')) {
                        licznik++;
                    }
                });
                
                if (licznik > 0) {
                    // Tutaj można dodać AJAX albo przekierowanie do specjalnego skryptu
                    alert(`Oznaczono ${licznik} produktów jako kupione!`);
                    location.reload();
                } else {
                    alert('Wszystkie produkty są już kupione!');
                }
            }
        }

        // ========================================
        // EVENT LISTENERS
        // ========================================
        
        checkboxes.forEach(ch => {
            ch.addEventListener('change', zapiszWyborSklepow);
        });

        // ========================================
        // INICJALIZACJA PO ZAŁADOWANIU DOM
        // ========================================
        
        document.addEventListener('DOMContentLoaded', () => {
            przywrocWyborSklepow();
            przywrocStanUkrycia();
            dodajUkrytePoleSklepy();
            aktualizujLinkOdswiez();
            przywrocPozycjePrzewijania();
        });
    </script>

</body>
</html>