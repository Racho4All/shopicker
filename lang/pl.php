<?php
// ============================================
// SHOPICKER - Tłumaczenia: Polski (pl)
// ============================================

return [
    // Metadane języka
    'meta' => [
        'code' => 'pl',
        'name' => 'Polski',
        'native_name' => 'Polski',
        'flag' => '🇵🇱',
    ],
    
    // Ogólne
    'app' => [
        'name' => 'Shopicker',
        'tagline' => 'Lista zakupów',
        'title' => 'Shopicker - lista zakupów',
    ],
    
    // Strona logowania
    'login' => [
        'title' => 'Shopicker - Logowanie',
        'heading' => '🛒 Shopicker',
        'prompt' => 'Wpisz PIN aby kontynuować',
        'placeholder' => '••••',
        'submit' => 'Wejdź',
        'error_csrf' => '❌ Nieprawidłowy token CSRF',
        'error_blocked' => '❌ Zbyt wiele nieudanych prób. Spróbuj ponownie później.',
        'error_invalid_pin' => '❌ Nieprawidłowy PIN',
    ],
    
    // Strona konfiguracji / błędy
    'config' => [
        'error_title' => 'Shopicker - Błąd konfiguracji',
        'error_heading' => '⚠️ Błąd konfiguracji',
        'error_subheading' => 'Brak wymaganych plików',
        'missing_files' => 'Brakujące pliki:',
        'file_config' => 'config.php (konfiguracja)',
        'file_setup' => 'generate_hash.php (instalator)',
        'how_to_fix' => '🔧 Jak to naprawić:',
        'step_1' => 'Wgraj plik <strong>generate_hash.php</strong> do katalogu aplikacji',
        'step_2' => 'Odśwież tę stronę',
        'step_3' => 'Zostaniesz przekierowany na formularz konfiguracji',
        'step_4' => 'Ustaw PIN i gotowe!',
        'contact_admin' => 'Jeśli problem się powtarza, skontaktuj się z administratorem lub sprawdź',
        'documentation' => 'dokumentację',
        'error_products_file' => 'Błąd: plik produkty_sklepy.php nie zwrócił poprawnej tablicy.',
    ],
    
    // Główny interfejs
    'ui' => [
        'stores' => '🏪 Sklepy',
        'all_stores' => 'wszystkie',
        'select_all' => 'zaznacz wszystkie',
        'deselect_all' => 'odznacz wszystkie',
        'refresh' => 'Odśwież listę',
        'edit' => 'Edytuj listę produktów',
        'logout' => 'Wyloguj',
        'show_all' => 'Pokaż wszystkie',
        'cart_only' => 'Tylko koszyk',
        'language' => 'Zmień język',
    ],
    
    // Licznik / status
    'counter' => [
        'cart_icon' => '🛒',
        'done' => '✓ Gotowe!',
    ],
    
    // Produkty
    'product' => [
        'bought' => '✓ Kupione',
        'buy' => 'Kup',
        'have' => '✓ Mam',
    ],
    
    // Błędy CSRF
    'errors' => [
        'csrf_invalid' => 'Nieprawidłowy token CSRF',
    ],
    
    // JavaScript - teksty używane w skryptach (główna lista)
    'js' => [
        'show_all' => 'Pokaż wszystkie',
        'cart_only' => 'Tylko koszyk',
        'select_all' => 'zaznacz wszystkie',
        'deselect_all' => 'odznacz wszystkie',
        'have' => '✓ Mam',
    ],
    
    // Edytor listy produktów
    'editor' => [
        'title' => 'Edycja listy - Shopicker',
        'heading' => 'Shopicker - Edycja',
        'back_to_list' => '← Powrót do listy',
        'go_to_main' => 'Przejdź na stronę główną aplikacji',
        
        // Wyszukiwanie i toolbar
        'search_placeholder' => 'Szukaj sklepu lub produktu...',
        'clear_search' => 'Wyczyść wyszukiwanie',
        'expand' => 'Rozwiń',
        'collapse' => 'Zwiń',
        'expand_all' => 'Rozwiń wszystkie sklepy',
        'collapse_all' => 'Zwiń wszystkie sklepy',
        
        // Sklepy
        'store_name' => 'Nazwa sklepu',
        'delete_store' => 'Usuń sklep',
        'delete' => 'Usuń',
        'add_new_store' => 'Dodaj nowy sklep',
        'drag_to_reorder' => 'Przeciągnij, aby zmienić kolejność',
        
        // Produkty
        'product_name' => 'Nazwa produktu',
        'unit' => 'Jednostka',
        'unit_placeholder' => 'np. kg, szt, l',
        'add_product' => 'Dodaj produkt',
        'add_product_below' => 'Dodaj produkt poniżej',
        'delete_product' => 'Usuń produkt',
        'no_products' => 'Brak produktów. Dodaj pierwszy produkt poniżej.',
        
        // Przyciski akcji
        'save_changes' => 'Zapisz zmiany',
        'save_shortcut' => 'Zapisz zmiany (Ctrl+S)',
        'cancel' => 'Anuluj',
        
        // Komunikaty
        'save_success' => 'Zmiany zostały zapisane pomyślnie!',
		'saved' => 'Zapisano',
        'save_error' => 'Błąd zapisu pliku!',
        'no_results' => 'Nie znaleziono wyników',
        'try_different_keywords' => 'Spróbuj użyć innych słów kluczowych',
        
        // Błędy walidacji
        'error_no_stores' => 'Brak danych sklepów.',
        'error_empty_store' => 'Sklep #{number}: Nazwa sklepu nie może być pusta.',
        'error_empty_product' => 'Sklep \'{store}\', produkt #{number}: Nazwa produktu nie może być pusta.',
        'error_empty_unit' => 'Sklep \'{store}\', produkt #{number}: Jednostka nie może być pusta.',
    ],
    
    // JavaScript - teksty dla edytora
    'editor_js' => [
        'drag_to_reorder' => 'Przeciągnij, aby zmienić kolejność',
        'store_name' => 'Nazwa sklepu',
        'delete_store' => 'Usuń sklep',
        'delete' => 'Usuń',
        'no_products' => 'Brak produktów. Dodaj pierwszy produkt poniżej.',
        'add_product' => 'Dodaj produkt',
        'product_name' => 'Nazwa produktu',
        'unit' => 'Jednostka',
        'unit_placeholder' => 'np. kg, szt, l',
        'delete_product' => 'Usuń produkt',
        'add_product_below' => 'Dodaj produkt poniżej',
        'new_store' => 'Nowy sklep',
        'possible_duplicate' => 'Możliwy duplikat produktu',
        'confirm_delete_product' => 'Czy na pewno usunąć ten produkt?',
        'confirm_delete_store' => 'Czy na pewno usunąć cały sklep z wszystkimi produktami?',
        'unsaved_changes' => 'Masz niezapisane zmiany. Czy na pewno chcesz opuścić stronę?',
    ],
    
    // Setup / Konfiguracja PIN
    'setup' => [
        'page_title' => 'Shopicker - Setup PIN',
        'heading' => '🔐 Shopicker Setup',
        'subtitle' => 'Ustaw PIN zabezpieczający dostęp do listy zakupów',
        'info_title' => 'ℹ️ Jednorazowa konfiguracja',
        'info_text' => 'PIN będzie zahaszowany i bezpiecznie zapisany.<br>Ten formularz usunie się automatycznie.',
        'pin_label' => 'PIN (minimum 4 cyfry)',
        'pin_placeholder' => '••••',
        'pin_hint' => 'Zapamiętaj ten PIN - będzie potrzebny do logowania',
        'pin_confirm_label' => 'Potwierdź PIN',
        'submit_button' => '🚀 Wygeneruj konfigurację',
        'toggle_pin' => 'Pokaż/Ukryj PIN',
        'success_title' => 'Shopicker - Setup zakończony!',
        'success_heading' => '🎉 Setup zakończony!',
        'success_message' => '✅ Konfiguracja została utworzona',
        'success_config_saved' => 'Plik config.php zapisany',
        'success_pin_hashed' => 'PIN zahaszowany bezpiecznie',
        'success_file_delete' => 'Ten plik zaraz się usunie',
        'success_go_to_app' => 'Przejdź do Shopicker 🛒',
        'success_warning' => '⚠️ Jeśli plik generate_hash.php nadal istnieje, usuń go ręcznie',
        'already_configured_title' => 'Shopicker - Setup zakończony',
        'already_configured_heading' => '✅ Setup zakończony',
        'already_configured_message' => 'Konfiguracja już istnieje!',
        'already_configured_hint' => 'Możesz bezpiecznie usunąć ten plik (generate_hash.php)',
        'error_blocked' => 'Zbyt wiele nieudanych prób. Spróbuj ponownie później.',
        'error_csrf' => 'Nieprawidłowy token CSRF.',
        'error_pin_empty' => 'Wprowadź PIN',
        'error_pin_min_length' => 'PIN musi mieć minimum 4 znaki',
        'error_pin_mismatch' => 'PIN i potwierdzenie nie są identyczne',
        'error_pin_digits_only' => 'PIN może zawierać tylko cyfry',
        'error_write_config' => 'Błąd zapisu pliku config.php - sprawdź uprawnienia.',
        'error_write_temp' => 'Błąd zapisu pliku tymczasowego - sprawdź uprawnienia katalogu.',
        'blocked_message' => 'Panel tymczasowo zablokowany z powodu wielokrotnych nieudanych prób. Spróbuj ponownie później.',
    ],
    
    // JavaScript - teksty dla setup
    'setup_js' => [
        'pins_match' => '✓ PIN-y są zgodne',
        'pins_mismatch' => '✗ PIN-y nie są zgodne',
        'pin_too_short' => 'Minimum 4 cyfry',
    ],
];
