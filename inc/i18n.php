<?php
// ============================================
// SHOPICKER - Internationalization (i18n)
// System tłumaczeń z automatycznym wykrywaniem języka
// ============================================

/**
 * Klasa obsługująca internacjonalizację
 */
class I18n {
    private static $instance = null;
    private $translations = [];
    private $currentLang = 'en';
    private $fallbackLang = 'en';
    private $availableLangs = [];
    private $langDir;
    
    private function __construct() {
        $this->langDir = __DIR__ . '/../lang/';
        $this->loadAvailableLanguages();
    }
    
    /**
     * Singleton - pobierz instancję
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Załaduj listę dostępnych języków
     */
    private function loadAvailableLanguages(): void {
        if (!is_dir($this->langDir)) {
            return;
        }
        
        $files = glob($this->langDir . '*.php');
        foreach ($files as $file) {
            $lang = basename($file, '.php');
            $this->availableLangs[] = $lang;
        }
    }
    
    /**
     * Pobierz listę dostępnych języków
     */
    public function getAvailableLanguages(): array {
        return $this->availableLangs;
    }
    
    /**
     * Wykryj preferowany język z nagłówka Accept-Language przeglądarki
     */
    public function detectBrowserLanguage(): string {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $this->fallbackLang;
        }
        
        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Parsuj nagłówek Accept-Language
        // Format: pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7
        $langs = [];
        $parts = explode(',', $acceptLang);
        
        foreach ($parts as $part) {
            $part = trim($part);
            $q = 1.0; // domyślna waga
            
            if (strpos($part, ';q=') !== false) {
                list($lang, $qPart) = explode(';q=', $part);
                $q = (float) $qPart;
            } else {
                $lang = $part;
            }
            
            // Wyciągnij główny kod języka (np. 'pl' z 'pl-PL')
            $langCode = strtolower(substr($lang, 0, 2));
            
            if (!isset($langs[$langCode]) || $langs[$langCode] < $q) {
                $langs[$langCode] = $q;
            }
        }
        
        // Sortuj według wagi malejąco
        arsort($langs);
        
        // Znajdź pierwszy dostępny język
        foreach (array_keys($langs) as $lang) {
            if (in_array($lang, $this->availableLangs)) {
                return $lang;
            }
        }
        
        return $this->fallbackLang;
    }
    
    /**
     * Ustaw aktualny język
     */
    public function setLanguage(string $lang): self {
        // Sprawdź czy język jest dostępny
        if (in_array($lang, $this->availableLangs)) {
            $this->currentLang = $lang;
        } else {
            $this->currentLang = $this->fallbackLang;
        }
        
        $this->loadTranslations();
        return $this;
    }
    
    /**
     * Pobierz aktualny język
     */
    public function getLanguage(): string {
        return $this->currentLang;
    }
    
    /**
     * Załaduj tłumaczenia dla aktualnego języka
     */
    private function loadTranslations(): void {
        $file = $this->langDir . $this->currentLang . '.php';
        
        if (file_exists($file)) {
            $this->translations = require $file;
        } else {
            // Fallback do angielskiego
            $fallbackFile = $this->langDir . $this->fallbackLang . '.php';
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
            } else {
                $this->translations = [];
            }
        }
    }
    
    /**
     * Pobierz tłumaczenie dla klucza
     * Obsługuje zagnieżdżone klucze, np. 'errors.csrf_invalid'
     * Może zwrócić string lub tablicę (dla sekcji jak 'js')
     * 
     * @param string $key Klucz tłumaczenia
     * @param array $params Parametry do podstawienia
     * @return string|array Tłumaczenie lub sam klucz jeśli nie znaleziono
     */
    public function get(string $key, array $params = []) {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Klucz nie znaleziony - zwróć sam klucz jako fallback
                return $key;
            }
        }
        
        // Jeśli wartość to tablica, zwróć ją bezpośrednio (np. dla sekcji 'js')
        if (is_array($value)) {
            return $value;
        }
        
        // Podmień parametry {name} w tekście
        foreach ($params as $paramKey => $paramValue) {
            $value = str_replace('{' . $paramKey . '}', $paramValue, $value);
        }
        
        return $value;
    }
    
    /**
     * Sprawdź czy klucz istnieje
     */
    public function has(string $key): bool {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Pobierz wszystkie tłumaczenia (do użycia w JS)
     */
    public function getAllTranslations(): array {
        return $this->translations;
    }
    
    /**
     * Pobierz metadane języka (flag, native_name, itp.)
     * Ładuje plik językowy tymczasowo, aby pobrać dane meta
     */
    public function getLangMeta(string $lang, string $key): ?string {
        $file = $this->langDir . $lang . '.php';
        
        if (!file_exists($file)) {
            return null;
        }
        
        $translations = require $file;
        
        if (isset($translations['meta'][$key])) {
            return $translations['meta'][$key];
        }
        
        return null;
    }
    
    /**
     * Pobierz wszystkie metadane wszystkich języków
     */
    public function getAllLangsMeta(): array {
        $result = [];
        
        foreach ($this->availableLangs as $lang) {
            $file = $this->langDir . $lang . '.php';
            if (file_exists($file)) {
                $translations = require $file;
                if (isset($translations['meta'])) {
                    $result[$lang] = $translations['meta'];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Automatycznie zainicjalizuj i18n
     * - Sprawdź sesję
     * - Sprawdź parametr GET ?lang=
     * - Wykryj język przeglądarki
     */
    public function autoInit(): self {
        // Priorytet 1: parametr GET ?lang=xx
        if (isset($_GET['lang']) && in_array($_GET['lang'], $this->availableLangs)) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
            $this->setLanguage($lang);
            return $this;
        }
        
        // Priorytet 2: zapisany w sesji
        if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $this->availableLangs)) {
            $this->setLanguage($_SESSION['lang']);
            return $this;
        }
        
        // Priorytet 3: wykryj z przeglądarki
        $detectedLang = $this->detectBrowserLanguage();
        $_SESSION['lang'] = $detectedLang;
        $this->setLanguage($detectedLang);
        
        return $this;
    }
}

// ============================================
// GLOBALNE FUNKCJE POMOCNICZE
// ============================================

/**
 * Skrócona funkcja do pobierania tłumaczeń
 * Użycie: __('key') lub __('key', ['param' => 'value'])
 */
function __(string $key, array $params = []): string {
    return I18n::getInstance()->get($key, $params);
}

/**
 * Echo z escapowaniem HTML
 * Użycie: _e('key')
 */
function _e(string $key, array $params = []): void {
    echo htmlspecialchars(__($key, $params), ENT_QUOTES, 'UTF-8');
}

/**
 * Pobierz aktualny język
 */
function getCurrentLang(): string {
    return I18n::getInstance()->getLanguage();
}

/**
 * Pobierz dostępne języki
 */
function getAvailableLangs(): array {
    return I18n::getInstance()->getAvailableLanguages();
}

/**
 * Zainicjalizuj i18n (wywołaj raz na początku)
 */
function initI18n(): void {
    I18n::getInstance()->autoInit();
}