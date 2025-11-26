<?php
// ============================================
// SHOPICKER - Translations: English (en)
// ============================================

return [
    // Language metadata
    'meta' => [
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'flag' => '🇬🇧',
    ],
    
    // General
    'app' => [
        'name' => 'Shopicker',
        'tagline' => 'Shopping List',
        'title' => 'Shopicker - Shopping List',
    ],
    
    // Login page
    'login' => [
        'title' => 'Shopicker - Login',
        'heading' => '🛒 Shopicker',
        'prompt' => 'Enter PIN to continue',
        'placeholder' => '••••',
        'submit' => 'Enter',
        'error_csrf' => '❌ Invalid CSRF token',
        'error_blocked' => '❌ Too many failed attempts. Please try again later.',
        'error_invalid_pin' => '❌ Invalid PIN',
    ],
    
    // Configuration / errors page
    'config' => [
        'error_title' => 'Shopicker - Configuration Error',
        'error_heading' => '⚠️ Configuration Error',
        'error_subheading' => 'Required files missing',
        'missing_files' => 'Missing files:',
        'file_config' => 'config.php (configuration)',
        'file_setup' => 'generate_hash.php (installer)',
        'how_to_fix' => '🔧 How to fix:',
        'step_1' => 'Upload <strong>generate_hash.php</strong> to the application directory',
        'step_2' => 'Refresh this page',
        'step_3' => 'You will be redirected to the configuration form',
        'step_4' => 'Set your PIN and you\'re done!',
        'contact_admin' => 'If the problem persists, contact your administrator or check the',
        'documentation' => 'documentation',
        'error_products_file' => 'Error: produkty_sklepy.php file did not return a valid array.',
    ],
    
    // Main interface
    'ui' => [
        'stores' => '🏪 Stores',
        'all_stores' => 'all',
        'select_all' => 'select all',
        'deselect_all' => 'deselect all',
        'refresh' => 'Refresh list',
        'edit' => 'Edit product list',
        'logout' => 'Logout',
        'show_all' => 'Show all',
        'cart_only' => 'Cart only',
        'language' => 'Change language',
    ],
    
    // Counter / status
    'counter' => [
        'cart_icon' => '🛒',
        'done' => '✓ Done!',
    ],
    
    // Products
    'product' => [
        'bought' => '✓ Bought',
        'buy' => 'Buy',
        'have' => '✓ Have',
    ],
    
    // CSRF errors
    'errors' => [
        'csrf_invalid' => 'Invalid CSRF token',
    ],
    
    // JavaScript - texts used in scripts (main list)
    'js' => [
        'show_all' => 'Show all',
        'cart_only' => 'Cart only',
        'select_all' => 'select all',
        'deselect_all' => 'deselect all',
        'have' => '✓ Have',
    ],
    
    // Product list editor
    'editor' => [
        'title' => 'Edit List - Shopicker',
        'heading' => 'Shopicker - Editor',
        'back_to_list' => '← Back to list',
        'go_to_main' => 'Go to the main page',
        
        // Search and toolbar
        'search_placeholder' => 'Search store or product...',
        'clear_search' => 'Clear search',
        'expand' => 'Expand',
        'collapse' => 'Collapse',
        'expand_all' => 'Expand all stores',
        'collapse_all' => 'Collapse all stores',
        
        // Stores
        'store_name' => 'Store name',
        'delete_store' => 'Delete store',
        'delete' => 'Delete',
        'add_new_store' => 'Add new store',
        'drag_to_reorder' => 'Drag to reorder',
        
        // Products
        'product_name' => 'Product name',
        'unit' => 'Unit',
        'unit_placeholder' => 'e.g. kg, pcs, l',
        'add_product' => 'Add product',
        'add_product_below' => 'Add product below',
        'delete_product' => 'Delete product',
        'no_products' => 'No products. Add your first product below.',
        
        // Action buttons
        'save_changes' => 'Save changes',
        'save_shortcut' => 'Save changes (Ctrl+S)',
        'cancel' => 'Cancel',
        
        // Messages
        'save_success' => 'Changes saved successfully!',
        'save_error' => 'Error saving file!',
        'no_results' => 'No results found',
        'try_different_keywords' => 'Try using different keywords',
        
        // Validation errors
        'error_no_stores' => 'No store data provided.',
        'error_empty_store' => 'Store #{number}: Store name cannot be empty.',
        'error_empty_product' => 'Store \'{store}\', product #{number}: Product name cannot be empty.',
        'error_empty_unit' => 'Store \'{store}\', product #{number}: Unit cannot be empty.',
    ],
    
    // JavaScript - texts for editor
    'editor_js' => [
        'drag_to_reorder' => 'Drag to reorder',
        'store_name' => 'Store name',
        'delete_store' => 'Delete store',
        'delete' => 'Delete',
        'no_products' => 'No products. Add your first product below.',
        'add_product' => 'Add product',
        'product_name' => 'Product name',
        'unit' => 'Unit',
        'unit_placeholder' => 'e.g. kg, pcs, l',
        'delete_product' => 'Delete product',
        'add_product_below' => 'Add product below',
        'new_store' => 'New store',
        'possible_duplicate' => 'Possible duplicate product',
        'confirm_delete_product' => 'Are you sure you want to delete this product?',
        'confirm_delete_store' => 'Are you sure you want to delete this entire store with all products?',
        'unsaved_changes' => 'You have unsaved changes. Are you sure you want to leave?',
    ],
];
