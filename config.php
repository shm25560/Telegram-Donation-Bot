<?php

// پیکربندی ربات
define('BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN'); // با توکن خودتون جایگزین کنید
define('WEBHOOK_URL', 'https://your-domain.com/webhook.php'); // ادرس فایل webhook.php روی سرور خودتون رو وارد کنید

// کانفیگ های درگاه
define('GATEWAY_PIN', 'YOUR_GATEWAY_PIN'); // پین درگاهتون رو اینجا وارد کنید
define('GATEWAY_API_URL', 'https://panel.aqayepardakht.ir/api/v2');
define('GATEWAY_PAYMENT_URL', 'https://panel.aqayepardakht.ir/startpay');
define('DEFAULT_AMOUNT', 20000);// هزینه پیشفرض درگاه 20000 تومان

// گزینه های اضافی درگاه (برای استفاده از کامنت خارج کنید)
// define('DEFAULT_MOBILE', '09111111111');
// define('DEFAULT_EMAIL', 'email@example.com');
// define('DEFAULT_CARD_NUMBER', '1111222233334444');

// دیتابیس
define('TRANSACTIONS_FILE', 'data/transactions.json');
if (!file_exists('data')) {
    mkdir('data', 0755, true);
}
