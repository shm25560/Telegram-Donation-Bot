<?php
// Payment verification endpoint

// Include bot functions to send messages
require_once 'bot.php';

// Verify the transaction
if (isset($_POST['transid'])) {
    $transId = $_POST['transid'];
    
    // Gateway configuration
    $gatewayPin = 'YOUR_GATEWAY_PIN'; // Replace with your actual gateway pin
    
    // Get transaction info from our records
    $transactions = [];
    if (file_exists('transactions.json')) {
        $transactions = json_decode(file_get_contents('transactions.json'), true);
    }
    
    // Check if transaction exists in our records
    if (isset($transactions[$transId])) {
        $transaction = $transactions[$transId];
        $chatId = $transaction['chat_id'];
        $amount = $transaction['amount'];
        
        // Prepare verification data
        $data = [
            'pin' => $gatewayPin,
            'amount' => (int)$amount,
            'transid' => $transId
        ];
        
        // Convert data to JSON
        $jsonData = json_encode($data);
        
        // Initialize cURL session
        $ch = curl_init('https://panel.aqayepardakht.ir/api/v2/verify');
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
        
        // Update transaction status
        if ($response && $response->code == "1") {
            // Transaction successful
            $transactions[$transId]['status'] = 'completed';
            file_put_contents('transactions.json', json_encode($transactions));
            
            // Send thank you message to user
            $thankYouMessage = "🎉 با تشکر از حمایت شما!\n\nپرداخت شما با موفقیت انجام شد.\nمبلغ: $amount تومان\nشماره تراکنش: $transId";
            sendMessage($chatId, $thankYouMessage);
            
            // Show success page to user
            echo "<html><body><h1>پرداخت موفق</h1><p>با تشکر از حمایت شما. می‌توانید به ربات تلگرام بازگردید.</p></body></html>";
        } else {
            // Transaction failed
            $transactions[$transId]['status'] = 'failed';
            file_put_contents('transactions.json', json_encode($transactions));
            
            // Notify user about failed payment
            $failureMessage = "❌ پرداخت ناموفق\n\nمتأسفانه پرداخت شما با موفقیت انجام نشد. لطفاً دوباره تلاش کنید.";
            sendMessage($chatId, $failureMessage);
            
            // Show failure page to user
            echo "<html><body><h1>پرداخت ناموفق</h1><p>متأسفانه پرداخت شما با موفقیت انجام نشد. لطفاً دوباره تلاش کنید.</p></body></html>";
        }
    } else {
        // Transaction not found in our records
        echo "<html><body><h1>خطا</h1><p>تراکنش یافت نشد.</p></body></html>";
    }
} else {
    // No transaction ID provided
    echo "<html><body><h1>خطا</h1><p>پارامترهای لازم ارسال نشده است.</p></body></html>";
}
