<?php

require_once 'functions.php';

function processUpdate($update) {
    if (isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';

        switch ($text) {
            case '/start':
                $welcomeMessage = "سلام! به ربات پرداخت خوش آمدید. برای حمایت مالی از ما، روی دکمه زیر کلیک کنید:";
                $keyboard = createDonationButton();
                sendMessage($chatId, $welcomeMessage, $keyboard);
                break;
                
            case '/help':
                $helpMessage = "این ربات برای دریافت حمایت مالی طراحی شده است.\n\n" .
                              "دستورات موجود:\n" .
                              "/start - شروع کار با ربات\n" .
                              "/help - نمایش این راهنما\n" .
                              "/donate - دریافت لینک پرداخت";
                sendMessage($chatId, $helpMessage);
                break;
                
            case '/donate':
                $donateMessage = "برای حمایت مالی، لطفا روی دکمه زیر کلیک کنید:";
                $keyboard = createDonationButton();
                sendMessage($chatId, $donateMessage, $keyboard);
                break;
        }
    }
    
    if (isset($update['callback_query'])) {
        $callbackData = $update['callback_query']['data'];
        $chatId = $update['callback_query']['message']['chat']['id'];
        
        if (strpos($callbackData, 'donate_') === 0) {
            $amount = substr($callbackData, 7);
            
            $paymentUrl = createPaymentLink($chatId, $amount);
            
            if ($paymentUrl) {
                sendMessage($chatId, "برای پرداخت لطفا روی لینک زیر کلیک کنید:\n\n$paymentUrl");
            } else {
                sendMessage($chatId, "متأسفانه در ایجاد لینک پرداخت مشکلی پیش آمد. لطفا بعدا تلاش کنید.");
            }
        }
    }
}
