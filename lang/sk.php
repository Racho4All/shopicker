<?php
// ============================================
// SHOPICKER - Preklady: Slovenčina (sk)
// ============================================

return [
    // Metadáta jazyka
    'meta' => [
        'code' => 'sk',
        'name' => 'Slovak',
        'native_name' => 'Slovenčina',
        'flag' => '🇸🇰',
    ],
    
    // Všeobecné
    'app' => [
        'name' => 'Shopicker',
        'tagline' => 'Nákupný zoznam',
        'title' => 'Shopicker - Nákupný zoznam',
    ],
    
    // Prihlasovacia stránka
    'login' => [
        'title' => 'Shopicker - Prihlásenie',
        'heading' => '🛒 Shopicker',
        'prompt' => 'Zadajte PIN pre pokračovanie',
        'placeholder' => '••••',
        'submit' => 'Vstúpiť',
        'error_csrf' => '❌ Neplatný CSRF token',
        'error_blocked' => '❌ Príliš veľa neúspešných pokusov. Skúste to znova neskôr.',
        'error_invalid_pin' => '❌ Nesprávny PIN',
    ],
    
    // Konfigurácia / chyby
    'config' => [
        'error_title' => 'Shopicker - Chyba konfigurácie',
        'error_heading' => '⚠️ Chyba konfigurácie',
        'error_subheading' => 'Chýbajú požadované súbory',
        'missing_files' => 'Chýbajúce súbory:',
        'file_config' => 'config.php (konfigurácia)',
        'file_setup' => 'generate_hash.php (inštalátor)',
        'how_to_fix' => '🔧 Ako to opraviť:',
        'step_1' => 'Nahrajte súbor <strong>generate_hash.php</strong> do adresára aplikácie',
        'step_2' => 'Obnovte túto stránku',
        'step_3' => 'Budete presmerovaní na konfiguračný formulár',
        'step_4' => 'Nastavte PIN a je to!',
        'contact_admin' => 'Ak problém pretrváva, kontaktujte administrátora alebo si pozrite',
        'documentation' => 'dokumentáciu',
        'error_products_file' => 'Chyba: súbor produkty_sklepy.php nevrátil platné pole.',
    ],
    
    // Hlavné rozhranie
    'ui' => [
        'stores' => '🏪 Obchody',
        'all_stores' => 'všetky',
        'select_all' => 'vybrať všetky',
        'deselect_all' => 'zrušiť výber',
        'refresh' => 'Obnoviť zoznam',
        'edit' => 'Upraviť zoznam produktov',
        'logout' => 'Odhlásiť sa',
        'show_all' => 'Zobraziť všetko',
        'cart_only' => 'Len košík',
        'language' => 'Zmeniť jazyk',
    ],
    
    // Počítadlo / stav
    'counter' => [
        'cart_icon' => '🛒',
        'done' => '✓ Hotovo!',
    ],
    
    // Produkty
    'product' => [
        'bought' => '✓ Kúpené',
        'buy' => 'Kúpiť',
        'have' => '✓ Mám',
    ],
    
    // CSRF chyby
    'errors' => [
        'csrf_invalid' => 'Neplatný CSRF token',
    ],
    
    // JavaScript - texty používané v skriptoch (hlavný zoznam)
    'js' => [
        'show_all' => 'Zobraziť všetko',
        'cart_only' => 'Len košík',
        'select_all' => 'vybrať všetky',
        'deselect_all' => 'zrušiť výber',
        'have' => '✓ Mám',
    ],
    
    // Editor zoznamu produktov
    'editor' => [
        'title' => 'Úprava zoznamu - Shopicker',
        'heading' => 'Shopicker - Editor',
        'back_to_list' => '← Späť na zoznam',
        'go_to_main' => 'Prejdite na hlavnú stránku aplikácie',
        
        // Vyhľadávanie a panel nástrojov
        'search_placeholder' => 'Hľadať obchod alebo produkt...',
        'clear_search' => 'Vymazať vyhľadávanie',
        'expand' => 'Rozbaliť',
        'collapse' => 'Zbaliť',
        'expand_all' => 'Rozbaliť všetky obchody',
        'collapse_all' => 'Zbaliť všetky obchody',
        
        // Obchody
        'store_name' => 'Názov obchodu',
        'delete_store' => 'Odstrániť obchod',
        'delete' => 'Odstrániť',
        'add_new_store' => 'Pridať nový obchod',
        'drag_to_reorder' => 'Potiahnite pre zmenu poradia',
        
        // Produkty
        'product_name' => 'Názov produktu',
        'unit' => 'Jednotka',
        'unit_placeholder' => 'napr. kg, ks, l',
        'add_product' => 'Pridať produkt',
        'add_product_below' => 'Pridať produkt nižšie',
        'delete_product' => 'Odstrániť produkt',
        'no_products' => 'Žiadne produkty. Pridajte prvý produkt nižšie.',
        
        // Akčné tlačidlá
        'save_changes' => 'Uložiť zmeny',
        'save_shortcut' => 'Uložiť zmeny (Ctrl+S)',
        'cancel' => 'Zrušiť',
        
        // Správy
        'save_success' => 'Zmeny boli úspešne uložené!',
        'save_error' => 'Chyba pri ukladaní súboru!',
        'no_results' => 'Nenašli sa žiadne výsledky',
        'try_different_keywords' => 'Skúste použiť iné kľúčové slová',
        
        // Validačné chyby
        'error_no_stores' => 'Žiadne údaje o obchodoch.',
        'error_empty_store' => 'Obchod #{number}: Názov obchodu nemôže byť prázdny.',
        'error_empty_product' => 'Obchod \'{store}\', produkt #{number}: Názov produktu nemôže byť prázdny.',
        'error_empty_unit' => 'Obchod \'{store}\', produkt #{number}: Jednotka nemôže byť prázdna.',
    ],
    
    // JavaScript - texty pre editor
    'editor_js' => [
        'drag_to_reorder' => 'Potiahnite pre zmenu poradia',
        'store_name' => 'Názov obchodu',
        'delete_store' => 'Odstrániť obchod',
        'delete' => 'Odstrániť',
        'no_products' => 'Žiadne produkty. Pridajte prvý produkt nižšie.',
        'add_product' => 'Pridať produkt',
        'product_name' => 'Názov produktu',
        'unit' => 'Jednotka',
        'unit_placeholder' => 'napr. kg, ks, l',
        'delete_product' => 'Odstrániť produkt',
        'add_product_below' => 'Pridať produkt nižšie',
        'new_store' => 'Nový obchod',
        'possible_duplicate' => 'Možný duplikát produktu',
        'confirm_delete_product' => 'Naozaj chcete odstrániť tento produkt?',
        'confirm_delete_store' => 'Naozaj chcete odstrániť celý obchod so všetkými produktmi?',
        'unsaved_changes' => 'Máte neuložené zmeny. Naozaj chcete opustiť stránku?',
    ],
    
    // Setup / Konfigurácia PIN
    'setup' => [
        'page_title' => 'Shopicker - Nastavenie PIN',
        'heading' => '🔐 Shopicker Setup',
        'subtitle' => 'Nastavte PIN pre zabezpečenie prístupu k nákupnému zoznamu',
        'info_title' => 'ℹ️ Jednorazová konfigurácia',
        'info_text' => 'PIN bude zahashovaný a bezpečne uložený.<br>Tento formulár sa automaticky odstráni.',
        'pin_label' => 'PIN (minimálne 4 číslice)',
        'pin_placeholder' => '••••',
        'pin_hint' => 'Zapamätajte si tento PIN - budete ho potrebovať na prihlásenie',
        'pin_confirm_label' => 'Potvrďte PIN',
        'submit_button' => '🚀 Vygenerovať konfiguráciu',
        'toggle_pin' => 'Zobraziť/Skryť PIN',
        'success_title' => 'Shopicker - Setup dokončený!',
        'success_heading' => '🎉 Setup dokončený!',
        'success_message' => '✅ Konfigurácia bola vytvorená',
        'success_config_saved' => 'Súbor config.php uložený',
        'success_pin_hashed' => 'PIN bezpečne zahashovaný',
        'success_file_delete' => 'Tento súbor sa teraz odstráni',
        'success_go_to_app' => 'Prejsť do Shopicker 🛒',
        'success_warning' => '⚠️ Ak súbor generate_hash.php stále existuje, odstráňte ho manuálne',
        'already_configured_title' => 'Shopicker - Setup dokončený',
        'already_configured_heading' => '✅ Setup dokončený',
        'already_configured_message' => 'Konfigurácia už existuje!',
        'already_configured_hint' => 'Môžete bezpečne odstrániť tento súbor (generate_hash.php)',
        'error_blocked' => 'Príliš veľa neúspešných pokusov. Skúste to znova neskôr.',
        'error_csrf' => 'Neplatný CSRF token.',
        'error_pin_empty' => 'Zadajte PIN',
        'error_pin_min_length' => 'PIN musí mať minimálne 4 znaky',
        'error_pin_mismatch' => 'PIN a potvrdenie sa nezhodujú',
        'error_pin_digits_only' => 'PIN môže obsahovať iba číslice',
        'error_write_config' => 'Chyba zápisu súboru config.php - skontrolujte oprávnenia.',
        'error_write_temp' => 'Chyba zápisu dočasného súboru - skontrolujte oprávnenia adresára.',
        'blocked_message' => 'Panel dočasne zablokovaný kvôli viacerým neúspešným pokusom. Skúste to znova neskôr.',
    ],
    
    // JavaScript - texty pre setup
    'setup_js' => [
        'pins_match' => '✓ PIN-y sa zhodujú',
        'pins_mismatch' => '✗ PIN-y sa nezhodujú',
        'pin_too_short' => 'Minimálne 4 číslice',
    ],
];
