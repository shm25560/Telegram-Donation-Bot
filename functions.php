<?php
/**
 * Core functions for Telegram Payment Bot
 */

require_once 'config.php';

/**
 * Send a message to Telegram
 * 
 * @param int $chatId The chat ID to send the message to
 * @param string $text The message text
 * @param array|null $keyboard Optional inline keyboard
 * @return array|false Response from Telegram or false on failure
 */
function sendMessage($chatId, $text, $keyboard = null) {
    $apiUrl = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard !== null) {
        $data['reply_markup'] = $keyboard;
    }
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($apiUrl, false, $context);
    
    return $response ? json_decode($response, true) : false;
}

/**
 * Create an inline keyboard with a donation button
 * 
 * @param int $amount The donation amount
 * @return string JSON-encoded keyboard
 */
function createDonationButton($amount = DEFAULT_AMOUNT) {
    $keyboard = [
        'inline_keyboard' => [
            [
                [
                    'text' => "حمایت مالی ($amount تومان)",
                    'callback_data' => "donate_$amount"
                ]
            ]
        ]
    ];
    
    return json_encode($keyboard);
}

/**
 * Create a payment link
 * 
 * @param int $chatId The chat ID of the user
 * @param int $amount The payment amount
 * @param string $description Optional payment description
 * @return string|false Payment URL or false on failure
 */
function createPaymentLink($chatId, $amount, $description = 'حمایت مالی از ربات تلگرام') {
    // Prepare data for payment gateway
    $data = [
        'pin' => GATEWAY_PIN,
        'amount' => (int)$amount,
        'callback' => WEBHOOK_URL . '?action=verify',
        'invoice_id' => $chatId . '_' . time(),
        'description' => $description
    ];
    
    // Add optional parameters if defined
    if (defined('DEFAULT_MOBILE')) $data['mobile'] = DEFAULT_MOBILE;
    if (defined('DEFAULT_EMAIL')) $data['email'] = DEFAULT_EMAIL;
    if (defined('DEFAULT_CARD_NUMBER')) $data['card_number'] = DEFAULT_CARD_NUMBER;
    
    // Convert data to JSON
    $jsonData = json_encode($data);
    
    // Initialize cURL session
    $ch = curl_init(GATEWAY_API_URL . '/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    // Execute cURL request
    $result = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log("Payment API Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $response = json_decode($result);
    
    // Check if request was successful
    if ($response && isset($response->status) && $response->status == "success" && isset($response->transid)) {
        // Store transaction info
        saveTransactionInfo($response->transid, $chatId, $amount, $description);
        
        // Return payment URL
        return GATEWAY_PAYMENT_URL . '/' . $response->transid;
    } else {
        error_log("Payment creation failed: " . print_r($response, true));
        return false;
    }
}

/**
 * Save transaction information
 * 
 * @param string $transId Transaction ID
 * @param int $chatId User's chat ID
 * @param int $amount Payment amount
 * @param string $description Payment description
 * @return bool Success status
 */
function saveTransactionInfo($transId, $chatId, $amount, $description) {
    // Create transaction record
    $transaction = [
        'transid' => $transId,
        'chat_id' => $chatId,
        'amount' => $amount,
        'description' => $description,
        'timestamp' => time(),
        'status' => 'pending'
    ];
    
    // Get existing transactions
    $transactions = [];
    if (file_exists(TRANSACTIONS_FILE)) {
        $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?: [];
    }
    
    // Add new transaction
    $transactions[$transId] = $transaction;
    
    // Save to file
    return file_put_contents(TRANSACTIONS_FILE, json_encode($transactions)) !== false;
}

/**
 * Verify a payment transaction
 * 
 * @param string $transId Transaction ID
 * @return bool|array False on failure or transaction details on success
 */
function verifyPayment($transId) {
    // Get transaction info from our records
    $transactions = [];
    if (file_exists(TRANSACTIONS_FILE)) {
        $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?: [];
    }
    
    // Check if transaction exists in our records
    if (!isset($transactions[$transId])) {
        return false;
    }
    
    $transaction = $transactions[$transId];
    $amount = $transaction['amount'];
    
    // Prepare verification data
    $data = [
        'pin' => GATEWAY_PIN,
        'amount' => (int)$amount,
        'transid' => $transId
    ];
    
    // Convert data to JSON
    $jsonData = json_encode($data);
    
    // Initialize cURL session
    $ch = curl_init(GATEWAY_API_URL . '/verify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    // Execute cURL request
    $result = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        error_log("Verification API Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    // Parse response
    $response = json_decode($result);
    
    // Update transaction status
    if ($response && $response->code == "1") {
        // Transaction successful
        $transactions[$transId]['status'] = 'completed';
        $transactions[$transId]['verified_at'] = time();
        file_put_contents(TRANSACTIONS_FILE, json_encode($transactions));
        
        return $transactions[$transId];
    } else {
        // Transaction failed
        $transactions[$transId]['status'] = 'failed';
        $transactions[$transId]['failed_at'] = time();
        file_put_contents(TRANSACTIONS_FILE, json_encode($transactions));
        
        return false;
    }
}

/**
 * Set webhook for Telegram bot
 * 
 * @return array Response from Telegram
 */
function setWebhook() {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . WEBHOOK_URL;
    $response = file_get_contents($url);
    return json_decode($response, true);
}
