<?php
// для некоторых параметров требуются логические значения (true/false). true - включить, false - выключить
const CFG_ENGINE = [
    // настройки подключения к VK
    'confirm' => 'строка',      // строка, которую должен вернуть сервер
    'secret' => 'секрет',       // секретный ключ
    'access_token' => 'ключ',   // ключ доступа к сообщениям сообщества
    'v' => '5.131',             // версия API. меняйте только если знаете что делаете
    'group_id' => 173777421,    // id сообщества (можно узнать через https://vk.com/app604480)

    // настройки подключения к MySQL
    'db' => [
        'host' => 'хост',           // хост
        'port' => 3306,             // порт
        'database' => 'имя_бд',     // имя бд
        'charset' => 'utf8mb4',     // кодировка
        'user' => 'пользователь',   // пользователь
        'pass' => 'пароль',         // пароль
        'prefix' => 'botvk_',       // префикс таблиц
    ],

    // настройки бота
    'reply' => true,        // "отвечать" на сообщение-триггер в ЧАТЕ (не ЛС)
    'typing' => [           // подражание кожаным ублюдкам (сообщение помечается как прочитанное + статус набора текста перед ответом)
        'enable' => false,  // включить/выключить
        'min' => 3,         // минимальное количество секунд до ответа (минимум 3, т.к. VK не сразу обновляет статус тайпинга)
        'max' => 5          // максимальное количество секунд до ответа
    ],
    'pruning' => 50,        // сколько последних символов проверять на переносы, упоминания и пробелы для "грамотного" разбития больших сообщений
    'prefix' => '!',        // префикс для вызова команд. команды можно писать через пробел (! команда) и без (!команда)
    'lang' => 'ru',         // язык. вдруг VK пользуются арабы (по умолчанию присутствует en/ru)
    'logs' => true,         // логирование модулей (если у них это есть) в logs/peer_id/день.месяц.год.log
    'bot' => '512',         // если списки из команды !bot превышают столько символов, то сообщения отправляются в ЛС
];