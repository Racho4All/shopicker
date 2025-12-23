# Shopicker
Handy web shopping list.
Copyright (c) 2025 Racho4All
[Demo](https://shopicker.racho.pl/demo.html)

# Shopicker 2.5 — Dokumentacja użytkownika
[English version below](#eng)

Przewodnik dla użytkownika końcowego: logowanie PIN, praca z listą zakupów, edytor produktów/sklepów, ukrywanie kupionych pozycji, bezpieczeństwo (CSRF, blokada PIN).

## 🔐 Szybki start (5 kroków)

1. Otwórz aplikację w przeglądarce (Chrome/Edge/Firefox/Safari) z włączonym JavaScriptem.
2. Wybierz język z przełącznika flag w prawym górnym rogu.
3. Wpisz PIN (4 do 6 cyfr). Po 5 błędach następuje blokada na 5 minut.
4. Zaznacz sklepy, które chcesz widzieć. Przycisk „Wszystkie/odznacz” masowo zmienia wybór.
5. Przy produkcie wpisz ilość i kliknij „Kup”. Po zakupie kliknij „Kupione!” by ukryć pozycję.

📌 **Szczegóły techniczne:**
- Sesja: cookie HTTPOnly + SameSite=Lax (opcjonalnie Secure przy HTTPS)
- Ilości zapisują się w `store_orders.txt`
- Link „✏️ Edycja” otwiera edytor produktów/sklepów
- Licznik w topbarze pokazuje liczbę produktów do kupienia; kliknięcie resetuje filtry sklepów

---

## 🛒 Widok główny (lista zakupów)

- **Top bar**: licznik koszyka, przełącznik widoczności kupionych (👁️/🛒), odświeżanie 🔄, przycisk ✏️ Edycja, wybór języka, wylogowanie 🚪
- **Filtr sklepów**: zaznacz kratki sklepów; stan zapisuje się w `localStorage` oraz w URL (`?sklepy=`)
- **Pozycje**: status *Do kupienia* (pomarańcz) lub *Mam* (zielony). „Kupione!” zeruje ilość
- **Scroll**: po odświeżeniu/akcji formularza przywraca się ostatnia pozycja przewinięcia

---

## ✏️ Edytor produktów/sklepów (edit.php)

- **Dostęp**: tylko po zalogowaniu. Przycisk „Powrót do listy” zachowuje filtr sklepów
- **Edycja inline**: możesz zmieniać nazwę sklepu, produktów, jednostki; dodawać/usuwać elementy
- **Drag & drop**: złap „☰” aby zmieniać kolejność sklepów i produktów
- **Skróty**: Ctrl/Cmd + S zapisuje, Ctrl/Cmd + F fokusuje wyszukiwarkę. Wyszukiwarka chowa sklepy bez trafień
- **Zapis**: po sukcesie tworzy kopię bezpieczeństwa `products_stores.php_backup_YYYY-mm-dd_HHMMSS.php` i nadpisuje `products_stores.php`

---

## 📋 Typowe zadania — lista

### Kup
**Ustaw ilość produktu**
- Wpisz liczbę (domyślnie 1) i kliknij „Kup”
- Po zapisie pozycja zmieni status na pomarańczowy „Do kupienia”

### Realizacja
**Oznacz jako kupione**
- Kliknij „Kupione!” przy produkcie z ilością
- Pozycja przyjmie status zielony „Mam” i może zostać ukryta przyciskiem 👁️

### Filtr
**Pokaż tylko wybrane sklepy**
- Zaznacz/odznacz sklepy. Widok i adres URL aktualizują się automatycznie
- Kliknięcie licznika resetuje filtr do sklepów z produktami

### Edycja listy
**Przejdź do edytora**
- Kliknij „✏️ Edycja”. Zapamiętane sklepy są przekazywane do edytora (parametr `?expand=`)
- Po zapisie wróć do listy — ostatnia pozycja scrolla zostanie przywrócona

---

## ⌨️ Instrukcja edytora (skrót)

### Dodaj sklep
Kliknij „➕ Dodaj nowy sklep”, uzupełnij nazwę i produkty. Puste sklepy są dozwolone, ale pojawi się info „Brak produktów”

### Dodaj produkt
W sklepie kliknij „➕ Dodaj produkt” lub przycisk ➕ obok pozycji (dodaje pod bieżącym). Wpisz nazwę i jednostkę (np. kg, szt., l)

### Duplikaty
System ostrzega (⚠️) o podobnych nazwach produktów w obrębie sklepu (fuzzy matching ≈80%)

### Zwiń/rozwiń
Przyciski „📂 Rozwiń” / „📁 Zwiń” sterują wszystkimi sklepami. Stan zapisuje się w przeglądarce

### Wyszukiwanie
Pasek „🔍 Szukaj” filtruje sklepy i produkty w locie; brak wyników pokazuje komunikat. „✕” czyści filtr

### Zapis i powrót
„💾 Zapisz zmiany” lub pływający przycisk (również Ctrl/Cmd+S). Po sukcesie pojawia się zielony toast i można wrócić do listy

---

## 🛡️ Bezpieczeństwo

- **CSRF**: każde żądanie POST ma ukryty token `_csrf`; weryfikacja w security.php
- **PIN + rate limit**: po ≥5 błędnych próbach blokada logowania na 5 minut (sesja przechowuje licznik)
- **Ciasteczka**: HTTPOnly, SameSite=Lax, opcjonalnie Secure (HTTPS)
- **Dane**: ilości w `store_orders.txt` (JSON), konfiguracja w `products_stores.php` (PHP array); backup tworzony przy zapisie z edytora

---

## ❓ FAQ

### Czy mogę wkleić pliki z kodem?
Nie. Aplikacja nie importuje plików ani kodu. Wpisujesz jedynie tekst/liczby w formularzach (ilości, nazwy, jednostki). Pliki konfiguracyjne (config.php, products_stores.php, store_orders.txt) są na serwerze.

### Dlaczego potrzebny jest PIN?
Dostęp chroniony PIN-em (hash w config.php). Próby są limitowane: po 5 błędach blokada 5 minut.

### Jak ukryć kupione pozycje?
Użyj przycisku 👁️/🛒 w górnym pasku. Ukrywa/pokazuje pozycje ze statusem „Mam”.

### Co jeśli licznik pokazuje 0?
Żadna pozycja nie ma ustawionej ilości. Dodaj ilość lub kliknij licznik, by zresetować filtry sklepów.

---

## 🛠️ Rozwiązywanie problemów

- **PIN odrzucany**: sprawdź czy blokada 5-minutowa minęła; poproś admina o nowy PIN
- **Brak produktów po zalogowaniu**: zaznacz sklepy lub kliknij licznik, by przywrócić filtry
- **Nie zapisuje ilości**: wpisz liczbę dodatnią; upewnij się, że token CSRF jest aktualny (odśwież stronę)
- **Błąd CSRF**: odśwież i ponów; token jest generowany per formularz
- **Edytor nie zapisuje**: uzupełnij nazwy i jednostki; zobacz komunikat pod „Zapisz” (zielony/pomarańczowy)
- **Układ się rozsypał**: wyłącz blokery JS, spróbuj w innej przeglądarce lub trybie prywatnym

---

## 📘 Słowniczek funkcji

| Symbol | Funkcja |
|--------|---------|
| 👁️ / 🛒 | Przełącznik widoczności pozycji „Wszystkie” / „Do kupienia” |
| 🔄 | Odśwież widok listy zakupów (zachowuje scroll) |
| ✏️ Edycja | Otwiera edytor produktów; przekazuje zaznaczone sklepy jako `?expand=` |
| 🚪 Wyloguj | Kończy sesję, zachowuje wybrany język |
| ☰ (drag) | Przeciągnij, aby zmienić kolejność sklepów lub produktów w edytorze |
| 💾 | Zapisuje konfigurację do `products_stores.php` (oraz backup) |

---

*Ostatnia aktualizacja: 22 grudnia 2025*


## eng
# Shopicker
Handy web shopping list.
Copyright (c) 2025 Racho4All

# Shopicker 2.5 — User Guide

A hands-on guide for end users: PIN login, shopping list workflow, product/store editor, hiding purchased items, and security (CSRF, PIN lockout).

## 🔐 Quick start (5 steps)

1. Open the app in your browser (Chrome/Edge/Firefox/Safari) with JavaScript enabled.
2. Pick a language using the flag switcher in the top-right corner.
3. Enter your PIN (4 to 6 digits). After 5 wrong attempts, login is locked for 5 minutes.
4. Check the stores you want to see. The “All/uncheck” button toggles all at once.
5. Enter a quantity next to a product and click “Buy”. After purchase, click “Bought!” to hide the item.

📌 **Technical details:**
- Session: HTTPOnly cookie + SameSite=Lax (optionally Secure over HTTPS)
- Quantities are stored in `store_orders.txt`
- The “✏️ Edit” link opens the product/store editor
- The topbar counter shows items to buy; clicking it resets store filters

---

## 🛒 Main view (shopping list)

- **Top bar**: cart counter, purchased visibility toggle (👁️/🛒), refresh 🔄, ✏️ Edit button, language switch, logout 🚪
- **Store filter**: tick store checkboxes; state is saved to `localStorage` and in the URL (`?sklepy=`)
- **Items**: status *To buy* (orange) or *Have* (green). “Bought!” clears the quantity
- **Scroll**: after refresh/form action, the last scroll position is restored

---

## ✏️ Product/Store Editor (edit.php)

- **Access**: only when logged in. “Back to list” keeps your store filter
- **Inline editing**: change store names, products, units; add/remove entries
- **Drag & drop**: grab “☰” to reorder stores and products
- **Shortcuts**: Ctrl/Cmd + S saves, Ctrl/Cmd + F focuses search. Search hides stores without matches
- **Save**: on success it creates a backup `products_stores.php_backup_YYYY-mm-dd_HHMMSS.php` and overwrites `products_stores.php`

---

## 📋 Common tasks

### Buy
**Set product quantity**
- Enter a number (default 1) and click “Buy”
- After saving the item turns orange “To buy”

### Fulfillment
**Mark as bought**
- Click “Bought!” on a product with a quantity
- The item turns green “Have” and can be hidden with the 👁️ toggle

### Filter
**Show only selected stores**
- Check/uncheck stores. The view and URL update automatically
- Click the counter to reset filters to stores that have items

### Edit list
**Go to the editor**
- Click “✏️ Edit”. Remembered stores are passed to the editor (`?expand=`)
- After saving, return to the list — your scroll position is restored

---

## ⌨️ Editor quick guide

### Add a store
Click “➕ Add new store”, fill in the name and products. Empty stores are allowed but will show “No products”.

### Add a product
Inside a store click “➕ Add product” or the ➕ button next to an item (adds below). Enter name and unit (e.g., kg, pcs, l).

### Duplicates
The system warns (⚠️) about similar product names within a store (fuzzy matching ≈80%).

### Expand/Collapse
Buttons “📂 Expand” / “📁 Collapse” control all stores. State is saved in the browser.

### Search
The “🔍 Search” bar filters stores and products live; no results shows a message. “✕” clears the filter.

### Save & return
“💾 Save changes” or the floating button (also Ctrl/Cmd+S). On success you’ll see a green toast and can return to the list.

---

## 🛡️ Security

- **CSRF**: every POST form includes a hidden `_csrf` token; verified in security.php
- **PIN + rate limit**: after ≥5 bad attempts, login is locked for 5 minutes (counter stored in session)
- **Cookies**: HTTPOnly, SameSite=Lax, optionally Secure (HTTPS)
- **Data**: quantities in `store_orders.txt` (JSON), configuration in `products_stores.php` (PHP array); backup created on editor save

---

## ❓ FAQ

### Can I paste code files?
No. The app does not import files or code. You only enter text/numbers in forms (quantities, names, units). Config files (config.php, products_stores.php, store_orders.txt) live on the server.

### Why do I need a PIN?
Access is protected by a PIN (hash in config.php). Attempts are limited: after 5 errors there’s a 5-minute lockout.

### How to hide bought items?
Use the 👁️/🛒 toggle in the top bar. It hides/shows items with status “Have”.

### What if the counter shows 0?
No item has a quantity set. Add a quantity or click the counter to reset store filters.

---

## 🛠️ Troubleshooting

- **PIN rejected**: check if the 5-minute lockout has passed; ask admin for a new PIN
- **No products after login**: tick stores or click the counter to restore filters
- **Quantities not saving**: enter a positive number; ensure the CSRF token is fresh (refresh the page)
- **CSRF error**: refresh and retry; the token is generated per form
- **Editor won’t save**: fill in names and units; check the message under “Save” (green/orange)
- **Layout broken**: disable JS blockers, try another browser or private mode

---

## 📘 Function glossary

| Symbol | Function |
|--------|----------|
| 👁️ / 🛒 | Toggle visibility of “All” / “To buy” items |
| 🔄 | Refresh shopping list view (keeps scroll) |
| ✏️ Edit | Opens the product editor; passes selected stores as `?expand=` |
| 🚪 Logout | Ends the session, keeps selected language |
| ☰ (drag) | Drag to reorder stores or products in the editor |
| 💾 | Saves configuration to `products_stores.php` (and backup) |

---

*Last updated: December 22, 2025*
