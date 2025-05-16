<?php
/**
 * Configuration file for Telegram Payment Bot
 */

// Bot configuration
define('BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN'); // Replace with your bot token
define('WEBHOOK_URL', 'https://your-domain.com/webhook.php'); // Replace with your webhook URL

// Payment gateway configuration
define('GATEWAY_PIN', 'YOUR_GATEWAY_PIN'); // Replace with your gateway PIN
define('GATEWAY_API_URL', 'https://panel.aqayepardakht.ir/api/v2');
define('GATEWAY_PAYMENT_URL', 'https://panel.aqayepardakht.ir/startpay');

// Default payment amount (in Tomans)
define('DEFAULT_AMOUNT', 20000);

// Optional payment parameters (uncomment and set if needed)
// define('DEFAULT_MOBILE', '09123456789');
// define('DEFAULT_EMAIL', 'email@example.com');
// define('DEFAULT_CARD_NUMBER', '1111222233334444');

// Database configuration (file-based for simplicity)
define('TRANSACTIONS_FILE', 'data/transactions.json');

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0755, true);
}
