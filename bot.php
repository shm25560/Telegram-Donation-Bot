<?php
/**
 * Main bot file - handles Telegram commands and callbacks
 */

require_once 'functions.php';

/**
 * Process incoming update from Telegram
 * 
 * @param array $update The update data from Telegram
 * @return void
 */
function processUpdate($update) {
    // Handle text messages
    if (isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        
        // Process commands
        switch ($text) {
            case '/start':
                // Welcome message
                $welcomeMessage = "سلام! به ربات پرداخت خوش آمدید. برای حمایت مالی از ما، روی دکمه زیر کلیک کنید:";
                $keyboard = createDonationButton();
                sendMessage($chatId, $welcomeMessage, $keyboard);
                break;
                
            case '/help':
                // Help message
                $helpMessage = "این ربات برای دریافت حمایت مالی طراحی شده است.\n\n" .
                              "دستورات موجود:\n" .
                              "/start - شروع کار با ربات\n" .
                              "/help - نمایش این راهنما\n" .
                              "/donate - دریافت لینک پرداخت";
                sendMessage($chatId, $helpMessage);
                break;
                
            case '/donate':
                // Send donation button
                $donateMessage = "برای حمایت مالی، لطفا روی دکمه زیر کلیک کنید:";
                $keyboard = createDonationButton();
                sendMessage($chatId, $donateMessage, $keyboard);
                break;
        }
    }
    
    // Handle callback queries (button clicks)
    if (isset($update['callback_query'])) {
        $callbackData = $update['callback_query']['data'];
        $chatId = $update['callback_query']['message']['chat']['id'];
        
        // Handle donation button press
        if (strpos($callbackData, 'donate_') === 0) {
            $amount = substr($callbackData, 7); // Extract amount from callback data
            
            // Generate payment link
            $paymentUrl = createPaymentLink($chatId, $amount);
            
            if ($paymentUrl) {
                // Send payment link to user
                sendMessage($chatId, "برای پرداخت لطفا روی لینک زیر کلیک کنید:\n\n$paymentUrl");
            } else {
                // Inform user about the error
                sendMessage($chatId, "متأسفانه در ایجاد لینک پرداخت مشکلی پیش آمد. لطفا بعدا تلاش کنید.");
            }
        }
    }
}
