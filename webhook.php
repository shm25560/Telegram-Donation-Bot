<?php
/**
 * Webhook entry point for Telegram updates and payment verification
 */

require_once 'functions.php';
require_once 'bot.php';

// Check if this is a payment verification callback
if (isset($_GET['action']) && $_GET['action'] === 'verify') {
    // Handle payment verification
    if (isset($_POST['transid'])) {
        $transId = $_POST['transid'];
        
        // Verify the payment
        $transaction = verifyPayment($transId);
        
        if ($transaction) {
            // Payment successful
            $chatId = $transaction['chat_id'];
            $amount = $transaction['amount'];
            
            // Send thank you message to user
            $thankYouMessage = "🎉 با تشکر از حمایت شما!\n\n" .
                              "پرداخت شما با موفقیت انجام شد.\n" .
                              "مبلغ: $amount تومان\n" .
                              "شماره تراکنش: $transId";
            sendMessage($chatId, $thankYouMessage);
            
            // Show success page to user
            echo "<html><body dir='rtl'><h1>پرداخت موفق</h1>" .
                 "<p>با تشکر از حمایت شما. می‌توانید به ربات تلگرام بازگردید.</p>" .
                 "<p><a href='https://t.me/" . str_replace('bot', '', BOT_TOKEN) . "'>بازگشت به ربات</a></p>" .
                 "</body></html>";
        } else {
            // Payment failed
            echo "<html><body dir='rtl'><h1>پرداخت ناموفق</h1>" .
                 "<p>متأسفانه پرداخت شما با موفقیت انجام نشد. لطفاً دوباره تلاش کنید.</p>" .
                 "<p><a href='https://t.me/" . str_replace('bot', '', BOT_TOKEN) . "'>بازگشت به ربات</a></p>" .
                 "</body></html>";
        }
        exit;
    }
    
    // No transaction ID provided
    echo "<html><body dir='rtl'><h1>خطا</h1><p>پارامترهای لازم ارسال نشده است.</p></body></html>";
    exit;
}

// Handle Telegram webhook
$update = json_decode(file_get_contents('php://input'), true);

// Process the update if it exists
if ($update) {
    processUpdate($update);
}

// Return 200 OK to Telegram
http_response_code(200);
echo "OK";
