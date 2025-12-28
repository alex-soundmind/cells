<?php
// config.php

$host = 'dpg-d3j56rali9vc73dorfng-a.singapore-postgres.render.com';
$db   = 'school'; // Измените на имя вашей новой БД, если оно отличается
$user = 'user';
$pass = '0urzMvp0cvo7Oi7D2CzXEorPHYfQOwZc';
$dsn  = "pgsql:host=$host;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

function translate($column) {
    static $map = [
        // Subscribers (Абоненты)
        'subscriber_id' => 'ID Абонента',
        'address' => 'Адрес',
        'fio' => 'ФИО',

        // Tariffs (Тарифы)
        'tariff_id' => 'ID Тарифа',
        'title' => 'Название тарифа',
        'cost' => 'Стоимость',
        'included_minutes' => 'Минуты',
        'included_traffic' => 'Трафик (МБ)',
        'description' => 'Описание',

        // Sim_Cards
        'sim_id' => 'ID SIM-карты',
        'phone_number' => 'Номер телефона',
        'balance' => 'Баланс',

        // Contracts
        'contract_id' => 'ID Договора',
        'signing_date' => 'Дата подписания',

        // Calls
        'call_id' => 'ID Звонка',
        'outgoing_sim_id' => 'ID Исходящей SIM',
        'incoming_sim_id' => 'ID Входящей SIM',
        'call_date' => 'Дата звонка',
        'call_time' => 'Время звонка',
        'duration' => 'Длительность (сек)',

        // SMS_Messages
        'message_id' => 'ID Сообщения',
        'sender_sim_id' => 'ID Отправителя',
        'recipient_sim_id' => 'ID Получателя',
        'send_date' => 'Дата отправки',
        'send_time' => 'Время отправки',
        'text' => 'Текст сообщения',
        'status' => 'Статус',

        // Internet_Sessions
        'session_id' => 'ID Сессии',
        'start_date' => 'Дата начала',
        'start_time' => 'Время начала',
        'end_date' => 'Дата конца',
        'end_time' => 'Время конца',
        'traffic' => 'Трафик (байт)',

        // Admins
        'admin_id' => 'ID Админа',
        'email' => 'E-mail',
        'password' => 'Пароль',
    ];
    return $map[$column] ?? ucfirst(str_replace('_', ' ', $column));
}
?>