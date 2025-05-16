<?php

require_once 'config.php';

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
// ساخت لینک پرداخت
function createPaymentLink($chatId, $amount, $description = 'حمایت مالی از ربات تلگرام') {
    $data = [
        'pin' => GATEWAY_PIN,
        'amount' => (int)$amount,
        'callback' => WEBHOOK_URL . '?action=verify',
        'invoice_id' => $chatId . '_' . time(),
        'description' => $description
    ];
    if (defined('DEFAULT_MOBILE')) $data['mobile'] = DEFAULT_MOBILE;
    if (defined('DEFAULT_EMAIL')) $data['email'] = DEFAULT_EMAIL;
    if (defined('DEFAULT_CARD_NUMBER')) $data['card_number'] = DEFAULT_CARD_NUMBER;
    $jsonData = json_encode($data);
    $ch = curl_init(GATEWAY_API_URL . '/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("Payment API Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    $response = json_decode($result);
    // اگه درخواست موفق بود
    if ($response && isset($response->status) && $response->status == "success" && isset($response->transid)) {
        // ذخیره سازی تراکنش
        saveTransactionInfo($response->transid, $chatId, $amount, $description);
        // لینک پرداخت
        return GATEWAY_PAYMENT_URL . '/' . $response->transid;
    } else {
        error_log("Payment creation failed: " . print_r($response, true));
        return false;
    }
}

// ذخیره سازی تراکنش
function saveTransactionInfo($transId, $chatId, $amount, $description) {
    $transaction = [
        'transid' => $transId,
        'chat_id' => $chatId,
        'amount' => $amount,
        'description' => $description,
        'timestamp' => time(),
        'status' => 'pending'
    ];
    $transactions = [];
    if (file_exists(TRANSACTIONS_FILE)) {
        $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?: [];
    }
    $transactions[$transId] = $transaction;
    return file_put_contents(TRANSACTIONS_FILE, json_encode($transactions)) !== false;
}

// تایید پرداخت
function verifyPayment($transId) {
    $transactions = [];
    if (file_exists(TRANSACTIONS_FILE)) {
        $transactions = json_decode(file_get_contents(TRANSACTIONS_FILE), true) ?: [];
    }
    if (!isset($transactions[$transId])) {
        return false;
    }
    $transaction = $transactions[$transId];
    $amount = $transaction['amount'];
    $data = [
        'pin' => GATEWAY_PIN,
        'amount' => (int)$amount,
        'transid' => $transId
    ];
    $jsonData = json_encode($data);
    $ch = curl_init(GATEWAY_API_URL . '/verify');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("Verification API Error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    $response = json_decode($result);
    if ($response && $response->code == "1") {
        // تراکنش موفق
        $transactions[$transId]['status'] = 'completed';
        $transactions[$transId]['verified_at'] = time();
        file_put_contents(TRANSACTIONS_FILE, json_encode($transactions));
        
        return $transactions[$transId];
    } else {
        // تراکنش ناموفق
        $transactions[$transId]['status'] = 'failed';
        $transactions[$transId]['failed_at'] = time();
        file_put_contents(TRANSACTIONS_FILE, json_encode($transactions));
        return false;
    }
}

// تنظیم وبهوک ربات
function setWebhook() {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . WEBHOOK_URL;
    $response = file_get_contents($url);
    return json_decode($response, true);
}
