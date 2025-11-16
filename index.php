<?php
// ============================================
// SHOPICKER - Lista zakupów
// Wersja: 2.0 (ulepszona)
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
    // Usuń puste sekcje sklepów
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
    
    // Zachowaj filtry sklepów
    if (!empty($_POST['widoczne_sklepy'])) {
        $parametry['sklepy'] = $_POST['widoczne_sklepy'];
    }
    
    // Zachowaj tryb ukrycia TYLKO jeśli jest "ukryte"
    if (!empty($_POST['widoczne_tryb']) && $_POST['widoczne_tryb'] === 'ukryte') {
        $parametry['tryb'] = 'ukryte';
    }
    // Jeśli tryb to "pokazane" lub puste - NIE dodawaj parametru
    
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
    
    // Logika walidacji ilości
    if (trim($_POST['ilosc']) === '') {
        $ilosc_input = 1; // domyślna ilość, jeśli użytkownik nic nie wpisał
    } elseif (is_numeric($_POST['ilosc']) && (int)$_POST['ilosc'] > 0) {
        $ilosc_input = (int)$_POST['ilosc'];
    } else {
        $ilosc_input = ''; // wszystko inne (np. 0, liczby ujemne) – traktuj jako brak
    }
    
    // Sprawdź czy produkt istnieje w konfiguracji
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
    
    // Sprawdź czy produkt istnieje w konfiguracji
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
</head>
<body>

    <!-- ============================================ -->
    <!-- SEKCJA WYBORU SKLEPÓW -->
    <!-- ============================================ -->
    
    <div id="wyborSklepow" style="margin-bottom: 20px;">
        <strong>Wybierz sklepy:</strong><br>
        <?php foreach (array_keys($produkty_sklepy) as $sklep_nazwa): ?>
            <label style="margin-right: 10px;">
                <input type="checkbox" 
                       class="checkboxSklep" 
                       value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                <?php echo htmlspecialchars($sklep_nazwa); ?>
            </label>
        <?php endforeach; ?>
    </div>

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
            <a href="/shopicker/edytuj.php" class="przycisk-naglowek przycisk-edytuj">Edytuj listę</a>
            <a href="/shopicker/" 
               class="przycisk-naglowek przycisk-odswiez" 
               onclick="sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);">
                Odśwież listę
            </a>
            <button id="przyciskUkryj" 
                    class="przycisk-naglowek przycisk-ukryj" 
                    onclick="toggleUkryj()">
                Zamówione
            </button>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- LISTY PRODUKTÓW DLA KAŻDEGO SKLEPU -->
    <!-- ============================================ -->

    <?php foreach ($produkty_sklepy as $sklep_nazwa => $produkty_w_sklepie): ?>
        <?php if (!empty($filtr_sklepy) && !in_array($sklep_nazwa, $filtr_sklepy)) continue; ?>
        
        <div class="sklep-sekcja">
            <h2 class="sklep-nazwa"><?php echo htmlspecialchars($sklep_nazwa); ?></h2>
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
                    <!-- Nazwa produktu -->
                    <span class="nazwa-produktu">
                        <?php echo htmlspecialchars($produkt); ?> - 
                        <span class="ilosc-tekst"><?php echo $ilosc_tekst; ?></span>
                    </span>
                    
                    <!-- Kontrolki -->
                    <div class="formularz-ilosc">
                        
                        <!-- Formularz "Kupione!" -->
                        <form method="POST" 
                              style="display:inline;" 
                              onsubmit="sessionStorage.setItem('shoppingList_scrollPos', window.scrollY);">
                            <input type="hidden" name="produkt" value="<?php echo htmlspecialchars($produkt); ?>">
                            <input type="hidden" name="sklep" value="<?php echo htmlspecialchars($sklep_nazwa); ?>">
                            <?php if ($czy_potrzebny): ?>
                                <button type="submit" 
                                        name="oznacz_jako_mam" 
                                        class="przycisk przycisk-mam">
                                    Kupione!
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
        // Klucze localStorage
        const STORAGE_KEY_HIDE = 'listaZakupow_ukryte';
        const STORAGE_KEY_SCROLL = 'shoppingList_scrollPos';
        const STORAGE_KEY_SKLEPY = 'karteczka_wybrane_sklepy';
        
        // Elementy DOM
        const przyciskUkryj = document.getElementById('przyciskUkryj');
        const checkboxes = document.querySelectorAll('.checkboxSklep');

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
                przyciskUkryj.textContent = 'Zamówione';
                localStorage.setItem(STORAGE_KEY_HIDE, 'pokazane');
            } else {
                elementyGot.forEach(el => el.classList.add('ukryty'));
                przyciskUkryj.textContent = 'Wszystkie';
                localStorage.setItem(STORAGE_KEY_HIDE, 'ukryte');
            }
            
            // Ukryj/pokaż puste sekcje sklepów
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
                przyciskUkryj.textContent = 'Wszystkie';
            } else {
                przyciskUkryj.textContent = 'Zamówione';
            }
            
            // Ukryj puste sekcje po przywróceniu stanu
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
                // Tylko sklepy - NIE dodawaj trybu!
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
                // GET ma priorytet - aktualizuj localStorage
                localStorage.setItem(STORAGE_KEY_SKLEPY, sklepyZUrl);
                const lista = sklepyZUrl.split(',');
                checkboxes.forEach(ch => {
                    ch.checked = lista.includes(ch.value);
                });
            } else {
                // Brak GET - użyj localStorage
                const zapamietane = localStorage.getItem(STORAGE_KEY_SKLEPY);
                if (zapamietane) {
                    const lista = zapamietane.split(',');
                    checkboxes.forEach(ch => {
                        ch.checked = lista.includes(ch.value);
                    });
                } else {
                    // Brak danych - zaznacz wszystkie
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
            sessionStorage.setItem('shoppingList_scrollPos', 0); // reset scrolla
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
        // EVENT LISTENERS
        // ========================================
        
        // Każdy checkbox reaguje na zmianę
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