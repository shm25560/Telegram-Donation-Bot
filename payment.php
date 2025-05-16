<?php
// Payment integration functions

// Create payment link
function createPaymentLink($chatId, $amount) {
    // Gateway configuration
    $gatewayPin = 'YOUR_GATEWAY_PIN'; // Replace with your actual gateway pin
    
    // Prepare data for payment gateway
    $data = [
        'pin' => $gatewayPin,
        'amount' => (int)$amount,
        'callback' => 'https://your-domain.com/verify.php',
        'invoice_id' => $chatId . '_' . time(), // Use chat ID and timestamp as invoice ID
        'description' => 'حمایت مالی از ربات تلگرام'
    ];
    
    // Optional parameters
    if (isset($_ENV['CARD_NUMBER'])) $data['card_number'] = $_ENV['CARD_NUMBER'];
    if (isset($_ENV['MOBILE'])) $data['mobile'] = $_ENV['MOBILE'];
    if (isset($_ENV['EMAIL'])) $data['email'] = $_ENV['EMAIL'];
    
    // Convert data to JSON
    $jsonData = json_encode($data);
    
    // Initialize cURL session
    $ch = curl_init('https://panel.aqayepardakht.ir/api/v2/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    
    // Set HTTP headers
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    // Execute cURL request
    $result = curl_exec($ch);
    curl_close($ch);
    
    // Parse response
    $response = json_decode($result);
    
    // Check if request was successful
    if ($response && $response->status == "success") {
        // Store transaction info in database or file for later verification
        saveTransactionInfo($response->transid, $chatId, $amount);
        
        // Return payment URL
        return 'https://panel.aqayepardakht.ir/startpay/' . $response->transid;
    } else {
        // Handle error
        error_log("Payment creation failed: " . print_r($response, true));
        return false;
    }
}

// Save transaction information for later verification
function saveTransactionInfo($transId, $chatId, $amount) {
    // Create a simple record of the transaction
    $transaction = [
        'transid' => $transId,
        'chat_id' => $chatId,
        'amount' => $amount,
        'timestamp' => time(),
        'status' => 'pending'
    ];
    
    // Save to a file (in production, use a database instead)
    $transactions = [];
    if (file_exists('transactions.json')) {
        $transactions = json_decode(file_get_contents('transactions.json'), true);
    }
    
    $transactions[$transId] = $transaction;
    file_put_contents('transactions.json', json_encode($transactions));
}
