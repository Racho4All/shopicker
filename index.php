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
        $filtr_sklepy = []; // Pusta tablica = nie pokazuj żadnych
    }
}

// ============================================
// STATYSTYKI (lekkie)
// ============================================

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
    <link rel="stylesheet" href="/shopicker/style.css">
    
    <style>
        /* Reset i podstawy */
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            padding-bottom: 80px;
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
        }
        
        .counter-badge.zero {
            background: #4CAF50;
        }
        
        .top-actions {
            display: flex;
            gap: 8px;
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
    }
    
    .counter-badge {
        font-size: 1em;
        padding: 6px 12px;
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
        gap: 10px; /* Większy odstęp między liniami */
        min-height: auto; /* Pozwól na rozszerzanie */
        max-height: none; /* Usuń ograniczenie */
        height: auto; /* Auto wysokość */
    }
    
    /* Nazwa produktu w pierwszej linii - SAMA, może się zawijać */
    .nazwa-produktu {
        font-size: 1.05em;
        width: 100%;
        display: block;
        word-wrap: break-word;
        overflow-wrap: break-word;
        line-height: 1.4; /* Dobra czytelność */
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
        <div class="counter-badge <?php echo $do_kupienia_total === 0 ? 'zero' : ''; ?>">
            <?php if ($do_kupienia_total > 0): ?>
                🛒 <?php echo $do_kupienia_total; ?>
            <?php else: ?>
                ✓ Gotowe!
            <?php endif; ?>
        </div>
        <div class="top-actions">
            <button class="btn-top btn-toggle" onclick="toggleUkryj()" id="btnToggle">
                👁️
            </button>
            <a href="/shopicker/edytuj.php" class="btn-top btn-edit">
                ✏️
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
        <?php if (!empty($filtr_sklepy) && !in_array($sklep_nazwa, $filtr_sklepy)) continue; ?>
        
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
                
                <li id="<?php echo htmlspecialchars($id_elementu); ?>" class="<?php echo $klasa_css; ?>">
                    <span class="nazwa-produktu">
                        <?php echo htmlspecialchars($produkt); ?>
                        <span class="ilosc-tekst"><?php echo $ilosc_tekst; ?></span>
                    </span>
                    
                    <div class="formularz-ilosc" data-ilosc="<?php echo $czy_potrzebny ? htmlspecialchars($ilosc_tekst) : ''; ?>">
                        <?php if ($czy_potrzebny): ?>
                            <form method="POST" style="display:inline;" onsubmit="animKupiono(this)">
                                <input type="hidden" name="produkt" value="<?php echo htmlspecialchars($produkt); ?>">
                                <input type="hidden" name="sklep" value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                                <button type="submit" name="oznacz_jako_mam" class="przycisk przycisk-mam">
                                    ✓ Kupione
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;" onsubmit="saveScroll()">
                                <input type="number" 
                                       name="ilosc" 
                                       value="<?php echo htmlspecialchars($wartosc_input); ?>" 
                                       min="0" 
                                       class="wejscie-ilosc"
                                       placeholder="1">
                                <span class="jednostka-miary"><?php echo htmlspecialchars($jednostka); ?></span>
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

		(function() {
			const pos = sessionStorage.getItem('shoppingList_scrollPos');
			if (pos) {
				// Ustaw scroll NATYCHMIAST
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
			const url = '/shopicker/?sklepy=' + encodeURIComponent(sklepyParam);
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
            }
            ukryjPusteSklepy();
            
            addHiddenFields();
            restoreScroll();
        });
    </script>

</body>
</html>