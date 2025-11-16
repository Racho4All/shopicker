<?php
// ============================================
// SHOPICKER - Lista zakupów
// Wersja: 2.1 (ultra-lekka)
// ============================================
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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

$filtr_sklepy = null;
if (isset($_GET['sklepy'])) {
    if ($_GET['sklepy'] !== '') {
        $filtr_sklepy = explode(',', $_GET['sklepy']);
    } else {
        $filtr_sklepy = []; // Pusta tablica = ukryj wszystko
    }
}

// ============================================
// STATYSTYKI (lekkie)
// ============================================

$do_kupienia_total = 0;
foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie) {
    if ($filtr_sklepy !== null && !in_array($sklep_nazwa, $filtr_sklepy)) continue;
    
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
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Shopicker - lista zakupów</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/shopicker/assets/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/shopicker/assets/favicon.svg" />
    <link rel="shortcut icon" href="/shopicker/assets/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/shopicker/assets/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Shopicker" />
    <link rel="manifest" href="/shopicker/assets/site.webmanifest" />
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <style>
        /* Reset i podstawy */
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body { 
            font-family: sans-serif; 
            max-width: 800px; 
            margin: 20px auto; 
            padding: 0 10px; 
            line-height: 1.4;
            padding-bottom: 80px;
        }
        
        /* ========================================
           NAGŁÓWEK
           ======================================== */
        
        .naglowek-kontener { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .montserrat-logo {
            margin: 0;
            font-size: 1.8em;
        }
        
        .przycisk-naglowek { 
            padding: 8px 12px; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            font-size: 0.9em;
        }
        
        .przycisk-odswiez { background-color: #007bff; }
        .przycisk-ukryj { background-color: #5d6a7a; }
        .przycisk-edytuj { background-color: #9C27B0; }
        .przycisk-odswiez:hover { background-color: #0056b3; }
        .przycisk-ukryj:hover { background-color: #434d58; }
        .przycisk-edytuj:hover { background-color: #7B1FA2; }
        
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
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .counter-badge {
            background: #FF9800;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1em;
            white-space: nowrap;
        }
        
        .counter-badge.zero {
            background: #4CAF50;
        }
        
        /* ========================================
           WYBÓR SKLEPÓW
           ======================================== */
        
        .sklepy-picker {
            background: #f5f5f5;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .sklepy-label {
            font-weight: 600;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
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
        
        .sklepy-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 8px;
        }
        
        .sklep-chip {
            display: flex;
            align-items: center;
            padding: 6px 10px;
            background: white;
            border-radius: 4px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9em;
        }
        
        .sklep-chip input {
            margin: 0 6px 0 0;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .sklep-chip:has(input:checked) {
            background: #E3F2FD;
            border-color: #2196F3;
            font-weight: 600;
        }
        
        .sklep-chip span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* ========================================
           SEKCJE SKLEPÓW
           ======================================== */
        
        .sklep-sekcja { 
            margin-bottom: 30px; 
            border: 1px solid #ccc; 
            padding: 15px; 
            border-radius: 5px;
        }
        
        .sklep-sekcja.ukryty {
            display: none;
        }
        
        .sklep-nazwa { 
            font-size: 1.5em; 
            margin-top: 0; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sklep-counter {
            font-size: 0.7em;
            background: #FF9800;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: normal;
        }
        
        .sklep-counter.zero {
            background: #4CAF50;
        }
        
        /* ========================================
           LISTA PRODUKTÓW
           ======================================== */
        
        .lista { 
            list-style-type: none; 
            padding: 0;
            margin: 0;
        }
        
        .lista li { 
            padding: 10px; 
            border-bottom: 1px solid #ddd; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            transition: opacity 0.3s ease, background-color 0.3s ease, max-height 0.3s ease;
            overflow: hidden;
            max-height: 200px;
        }
        
        .ukryty {
            opacity: 0 !important;
            max-height: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            border-bottom: none !important;
            visibility: hidden !important;
        }
        
        .status-have { 
            background-color: #ccffcc; 
            opacity: 0.85; 
            text-decoration: none; 
        }
        
        .status-need { 
            background-color: #ffcccc; 
            font-weight: bold; 
            text-decoration: none;
        }
        
        .nazwa-produktu {
            flex: 1;
            min-width: 150px;
        }
        
        .ilosc-tekst {
            font-style: italic;
            display: inline;
            margin-left: 5px;
        }
        
        /* ========================================
           FORMULARZE
           ======================================== */
        
        .formularz-ilosc { 
            display: flex; 
            align-items: center; 
            justify-content: flex-end;
            gap: 5px;
            max-width: 250px;
        }
        
        .wejscie-ilosc { 
            width: 50px; 
            padding: 5px; 
            text-align: right;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        
        .jednostka-miary { 
            font-size: 0.9em; 
            color: #555;
            min-width: 4ch;
            text-align: left;
        }
        
        .przycisk { 
            padding: 6px 10px; 
            cursor: pointer; 
            border-radius: 4px; 
            border: none;
            font-size: 0.9em;
            white-space: nowrap;
        }
        
        .przycisk-mam { 
            background-color: #28a745; 
            color: white;
        }
        
        .przycisk-mam:hover {
            background-color: #218838;
        }
        
        .przycisk-zmien { 
            background-color: #007bff; 
            color: white;
        }
        
        .przycisk-zmien:hover {
            background-color: #0056b3;
        }
        
        /* ========================================
           RESPONSYWNOŚĆ MOBILE
           ======================================== */
        
        @media screen and (max-width: 600px) {
            body {
                margin: 10px auto;
            }
            
            .top-bar {
                padding: 8px 10px;
            }
            
            .counter-badge {
                font-size: 1em;
                padding: 6px 12px;
            }
            
            .sklepy-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .sklep-chip {
                font-size: 0.85em;
            }
            
            .lista li { 
                flex-direction: column; 
                align-items: flex-start; 
                gap: 10px;
            }
            
            .nazwa-produktu {
                width: 100%;
            }
            
            .formularz-ilosc { 
                justify-content: flex-end; 
                max-width: 100%;
                width: 100%;
            }
            
            .formularz-ilosc form {
                display: flex;
                align-items: center;
                gap: 5px;
            }
            
            /* Przycisk "Kupione" na pełną szerokość */
            .status-need .formularz-ilosc {
                width: 100%;
            }
            
            .status-need .przycisk-mam {
                width: 100%;
            }
        }
        
        @media screen and (max-width: 400px) {
            .sklepy-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- ============================================ -->
    <!-- NAGŁÓWEK -->
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
            <button id="przyciskUkryj" 
                    class="przycisk-naglowek przycisk-ukryj" 
                    onclick="toggleUkryj()">
					Wszystkie
            </button>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- STICKY TOP BAR -->
    <!-- ============================================ -->
    
    <div class="top-bar">
        <div class="counter-badge <?php echo $do_kupienia_total === 0 ? 'zero' : ''; ?>">
            <?php if ($do_kupienia_total > 0): ?>
                🛒 <?php echo $do_kupienia_total; ?>
            <?php else: ?>
                ✓ Gotowe!
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- WYBÓR SKLEPÓW -->
    <!-- ============================================ -->
    
    <div class="sklepy-picker">
        <div class="sklepy-label">
            🏪 Sklepy
            <button class="btn-all-shops" onclick="toggleAllShops()" id="btnToggleShops">wszystkie</button>
        </div>
        <div class="sklepy-grid">
            <?php foreach (array_keys($produkty_sklepy) as $sklep_nazwa): ?>
                <label class="sklep-chip">
                    <input type="checkbox" 
                           class="checkboxSklep" 
                           value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                    <span><?php echo htmlspecialchars($sklep_nazwa); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- LISTY PRODUKTÓW -->
    <!-- ============================================ -->

    <?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie): ?>
        <?php if ($filtr_sklepy !== null && !in_array($sklep_nazwa, $filtr_sklepy)) continue; ?>
        
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
        
        <div class="sklep-sekcja" data-sklep="<?php echo htmlspecialchars($sklep_nazwa); ?>">
            <h2 class="sklep-nazwa">
                <span><?php echo htmlspecialchars($sklep_nazwa); ?></span>
                <span class="sklep-counter <?php echo $do_kupienia_sklep === 0 ? 'zero' : ''; ?>">
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
                        ? "Ilość: $ilosc_obecna $jednostka" 
                        : "Mam/Nie potrzebuję";
                    $wartosc_input = $czy_potrzebny ? $ilosc_obecna : '';
                    $id_elementu = generuj_id_kotwicy($sklep_nazwa, $produkt);
                ?>
                
                <li id="<?php echo htmlspecialchars($id_elementu); ?>" class="<?php echo $klasa_css; ?>">
                    <span class="nazwa-produktu">
                        <?php echo htmlspecialchars($produkt); ?> - 
                        <span class="ilosc-tekst"><?php echo $ilosc_tekst; ?></span>
                    </span>
                    
                    <div class="formularz-ilosc">
                        <?php if ($czy_potrzebny): ?>
                            <form method="POST" 
                                  style="display:inline;" 
                                  onsubmit="sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);">
                                <input type="hidden" name="produkt" value="<?php echo htmlspecialchars($produkt); ?>">
                                <input type="hidden" name="sklep" value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                                <button type="submit" name="oznacz_jako_mam" class="przycisk przycisk-mam">
                                    Kupione!
                                </button>
                            </form>
                        <?php else: ?>
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
        const STORAGE_HIDE = 'listaZakupow_ukryte';
        const STORAGE_SCROLL = 'shoppingList_scrollPos';
        const STORAGE_SKLEPY = 'karteczka_wybrane_sklepy';
        
        const checkboxes = document.querySelectorAll('.checkboxSklep');
        const btnToggle = document.getElementById('przyciskUkryj');
        const btnToggleShops = document.getElementById('btnToggleShops');
        
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
            btnToggle.textContent = anyVisible ? 'Zamówione' : 'Wszystkie';
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
                localStorage.setItem(STORAGE_SKLEPY, fromUrl);
                const lista = fromUrl.split(',').filter(s => s.trim() !== '');
                checkboxes.forEach(ch => ch.checked = lista.includes(ch.value));
            } else {
                const saved = localStorage.getItem(STORAGE_SKLEPY);
                if (saved !== null) {
                    const lista = saved.split(',').filter(s => s.trim() !== '');
                    checkboxes.forEach(ch => ch.checked = lista.includes(ch.value));
                } else {
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
            
            const url = '/shopicker/?sklepy=' + encodeURIComponent(sklepyParam);
            sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
            window.location.href = url;
        }
        
        function toggleAllShops() {
            const allChecked = Array.from(checkboxes).every(ch => ch.checked);
            checkboxes.forEach(ch => ch.checked = !allChecked);
            saveSklepy();
        }
        
        function updateToggleButton() {
            if (!btnToggleShops) return;
            const allChecked = Array.from(checkboxes).every(ch => ch.checked);
            btnToggleShops.textContent = allChecked ? 'żadne' : 'wszystkie';
        }
        
        checkboxes.forEach(ch => ch.addEventListener('change', saveSklepy));
        
        // ========================================
        // Scroll
        // ========================================
        
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                sessionStorage.setItem(STORAGE_SCROLL, window.scrollY);
            }, 100);
        }, { passive: true });
        
        function restoreScroll() {
            const pos = sessionStorage.getItem(STORAGE_SCROLL);
            if (pos) {
                const scrollPos = parseInt(pos);
                window.scrollTo(0, scrollPos);
                setTimeout(() => {
                    sessionStorage.removeItem(STORAGE_SCROLL);
                }, 100);
            }
        }
        
        // ========================================
        // Ukryte pola w formularzach
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
                btnToggle.textContent = 'Wszystkie';
            }
            ukryjPusteSklepy();
            
            addHiddenFields();
            restoreScroll();
        });
    </script>

</body>
</html>