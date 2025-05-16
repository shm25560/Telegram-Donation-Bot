<?php

require_once 'functions.php';
require_once 'bot.php';

if (isset($_GET['action']) && $_GET['action'] === 'verify') {
    if (isset($_POST['transid'])) {
        $transId = $_POST['transid'];
        // تاییدیه درگاه
        $transaction = verifyPayment($transId);
        if ($transaction) {
            // پرداخت موفق
            $chatId = $transaction['chat_id'];
            $amount = $transaction['amount'];
            $thankYouMessage = "🎉 با تشکر از حمایت شما!\n\n" .
                              "پرداخت شما با موفقیت انجام شد.\n" .
                              "مبلغ: $amount تومان\n" .
                              "شماره تراکنش: $transId";
            sendMessage($chatId, $thankYouMessage);
            echo "<html><body dir='rtl'><h1>پرداخت موفق</h1>" .
                 "<p>با تشکر از حمایت شما. می‌توانید به ربات تلگرام بازگردید.</p>" .
                 "<p><a href='https://t.me/" . str_replace('bot', '', BOT_TOKEN) . "'>بازگشت به ربات</a></p>" .
                 "</body></html>";
        } else {
            // پرداخت ناموفق
            echo "<html><body dir='rtl'><h1>پرداخت ناموفق</h1>" .
                 "<p>متأسفانه پرداخت شما با موفقیت انجام نشد. لطفاً دوباره تلاش کنید.</p>" .
                 "<p><a href='https://t.me/" . str_replace('bot', '', BOT_TOKEN) . "'>بازگشت به ربات</a></p>" .
                 "</body></html>";
        }
        exit;
    }
    echo "<html><body dir='rtl'><h1>خطا</h1><p>پارامترهای لازم ارسال نشده است.</p></body></html>";
    exit;
}
// هوک تلگرام
$update = json_decode(file_get_contents('php://input'), true);

if ($update) {
    processUpdate($update);
}

http_response_code(200);
echo "OK";
