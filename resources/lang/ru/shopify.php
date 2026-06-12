<?php

return [

    'store_connected' => 'Магазин успешно подключен!',
    'store_disconnected' => 'Магазин отключен.',
    'authentication_failed' => 'Ошибка аутентификации. Пожалуйста, попробуйте снова.',
    'sync_started' => 'Синхронизация начата для :entity',
    'sync_completed' => 'Синхронизация завершена для :entity',
    'sync_failed' => 'Синхронизация не удалась для :entity',
    'webhook_received' => 'Получен webhook: :topic',
    'webhook_processed' => 'Обработан webhook: :topic',

    'resources' => [
        'store' => ['singular' => 'Магазин', 'plural' => 'Магазины', 'nav' => 'Магазины'],
        'product' => ['singular' => 'Товар', 'plural' => 'Товары', 'nav' => 'Товары'],
        'order' => ['singular' => 'Заказ', 'plural' => 'Заказы', 'nav' => 'Заказы'],
        'draft_order' => ['singular' => 'Черновик заказа', 'plural' => 'Черновики заказов', 'nav' => 'Черновики'],
        'fulfillment' => ['singular' => 'Выполнение', 'plural' => 'Выполнения', 'nav' => 'Выполнения'],
        'discount' => ['singular' => 'Скидка', 'plural' => 'Скидки', 'nav' => 'Скидки'],
        'customer' => ['singular' => 'Клиент', 'plural' => 'Клиенты', 'nav' => 'Клиенты'],
        'collection' => ['singular' => 'Коллекция', 'plural' => 'Коллекции', 'nav' => 'Коллекции'],
        'metafield' => ['singular' => 'Metafield', 'plural' => 'Metafields', 'nav' => 'Metafields'],
        'product_type' => ['singular' => 'Тип товара', 'plural' => 'Типы товаров', 'nav' => 'Типы товаров'],
        'product_tag' => ['singular' => 'Тег товара', 'plural' => 'Теги товаров', 'nav' => 'Теги товаров'],
        'shopify_log' => ['singular' => 'Лог синхронизации', 'plural' => 'Логи синхронизации', 'nav' => 'Логи синхронизации'],
        'user' => ['singular' => 'Пользователь', 'plural' => 'Пользователи', 'nav' => 'Пользователи'],
        'role' => ['singular' => 'Роль', 'plural' => 'Роли', 'nav' => 'Роли'],
        'permission' => ['singular' => 'Право', 'plural' => 'Права', 'nav' => 'Права'],
    ],

    'nav_groups' => [
        'shopify' => 'Shopify',
        'operations' => 'Операции',
        'marketing' => 'Маркетинг',
        'reports' => 'Отчёты',
        'access_control' => 'Доступ',
    ],

    'pages' => [
        'analytics' => ['title' => 'Аналитика', 'nav' => 'Аналитика'],
    ],
];
