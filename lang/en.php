<?php
// ============================================
// SHOPICKER 2.5.2 - Translations: English (en)
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
        'error_products_file' => 'Error: products_stores.php file did not return a valid array.',
    ],
    
    // Main interface
    'ui' => [
		'buycoffee' => 'Will you buy me a coffee?',
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
		'saved' => 'Saved',
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
    
    // Setup / PIN Configuration
    'setup' => [
        'page_title' => 'Shopicker - PIN Setup',
        'heading' => '🔐 Shopicker Setup',
        'subtitle' => 'Set a PIN to secure access to your shopping list',
        'info_title' => 'ℹ️ One-time configuration',
        'info_text' => 'Your PIN will be hashed and stored securely.<br>This form will delete itself automatically.',
        'pin_label' => 'PIN (from 4 to 6 digits)',
        'pin_placeholder' => '••••',
        'pin_hint' => 'Remember this PIN - you will need it to log in',
        'pin_confirm_label' => 'Confirm PIN',
        'submit_button' => '🚀 Generate configuration',
        'toggle_pin' => 'Show/Hide PIN',
        'success_title' => 'Shopicker - Setup Complete!',
        'success_heading' => '🎉 Setup Complete!',
        'success_message' => '✅ Configuration has been created',
        'success_config_saved' => 'config.php file saved',
        'success_pin_hashed' => 'PIN securely hashed',
        'success_file_delete' => 'This file will delete itself now',
        'success_go_to_app' => 'Go to Shopicker 🛒',
        'success_warning' => '⚠️ If generate_hash.php still exists, delete it manually',
        'already_configured_title' => 'Shopicker - Setup Complete',
        'already_configured_heading' => '✅ Setup Complete',
        'already_configured_message' => 'Configuration already exists!',
        'already_configured_hint' => 'You can safely delete this file (generate_hash.php)',
        'error_blocked' => 'Too many failed attempts. Please try again later.',
        'error_csrf' => 'Invalid CSRF token.',
        'error_pin_empty' => 'Please enter a PIN',
        'error_pin_min_length' => 'PIN must be at least 4 characters',
        'error_pin_mismatch' => 'PIN and confirmation do not match',
        'error_pin_digits_only' => 'PIN can only contain digits',
        'error_write_config' => 'Error writing config.php file - check permissions.',
        'error_write_temp' => 'Error writing temporary file - check directory permissions.',
        'blocked_message' => 'Panel temporarily blocked due to multiple failed attempts. Please try again later.',
    ],
    
    // JavaScript - texts for setup
    'setup_js' => [
        'pins_match' => '✓ PINs match',
        'pins_mismatch' => '✗ PINs do not match',
        'pin_too_short' => 'Minimum 4 digits',
    ],
];
