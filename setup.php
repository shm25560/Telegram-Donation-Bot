<?php
/**
 * One-time setup script to configure the bot
 */

require_once 'functions.php';

// Set error reporting for setup
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Telegram Payment Bot Setup</h1>";

// Check configuration
echo "<h2>Configuration Check</h2>";
echo "<ul>";

// Check bot token
if (BOT_TOKEN === 'YOUR_TELEGRAM_BOT_TOKEN') {
    echo "<li style='color:red'>❌ Bot token not configured. Please update config.php</li>";
} else {
    echo "<li style='color:green'>✅ Bot token configured</li>";
}

// Check webhook URL
if (WEBHOOK_URL === 'https://your-domain.com/webhook.php') {
    echo "<li style='color:red'>❌ Webhook URL not configured. Please update config.php</li>";
} else {
    echo "<li style='color:green'>✅ Webhook URL configured</li>";
}

// Check gateway PIN
if (GATEWAY_PIN === 'YOUR_GATEWAY_PIN') {
    echo "<li style='color:red'>❌ Gateway PIN not configured. Please update config.php</li>";
} else {
    echo "<li style='color:green'>✅ Gateway PIN configured</li>";
}

echo "</ul>";

// Check if data directory is writable
echo "<h2>File System Check</h2>";
if (is_writable('data')) {
    echo "<p style='color:green'>✅ Data directory is writable</p>";
} else {
    echo "<p style='color:red'>❌ Data directory is not writable. Please check permissions</p>";
}

// Set webhook if configuration is valid
if (BOT_TOKEN !== 'YOUR_TELEGRAM_BOT_TOKEN' && WEBHOOK_URL !== 'https://your-domain.com/webhook.php') {
    echo "<h2>Setting Webhook</h2>";
    $result = setWebhook();
    
    if (isset($result['ok']) && $result['ok'] === true) {
        echo "<p style='color:green'>✅ Webhook set successfully!</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to set webhook: " . json_encode($result) . "</p>";
    }
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Make sure all configuration values are set in config.php</li>";
echo "<li>Ensure your server is accessible from the internet</li>";
echo "<li>Test your bot by sending /start command in Telegram</li>";
echo "</ol>";
