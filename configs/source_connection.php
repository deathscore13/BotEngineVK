<?php

return [
    'every' => [    // peer_id (через запятую) или: chats - все чаты, pm - личные сообщения, every - везде
        'settings' => [
            // имеет смысл настраивать для НЕ pm
            'replace' => '{green}❌{default}',    // в чате игры не поддерживаются символы больше 3-х байт. они будут заменены на указанный
            'from' => 'из VK',      // пишется после имени пользователя
            'players' => 512,       // если количество символов при выводе игроков больше, то сообщение отправляется в ЛС
            'response' => 256,      // если количество символов в ответе больше, то сообщение отправляется в ЛС

            // работает для pm
            'steamid' => true,      // нужны ли права для доступа к команде !ID steamid (показывает SteamId вместо K/D и IP вместо мута)
            'maps' => []            /* изображения карт уже загруженные в VK (например, альбом). формат: ['карта' => 'id_изображения', ... ]
                                       пример:
                                        [
                                            'none' => '-189617780_457239918', // none - неизвестная карта
                                            '$2000$' => '-189617780_457239917',
                                            'aim_deagle7k' => '-189617780_457239919',
                                            'awp_lego_2' => '-189617780_457239922',
                                        ]
                                    */
        ],

        '1' => [
            'description' => 'MIRAGE #1', // описание сервера в !bot commands
            'ip' => '37.230.228.240', // ip сервера
            'port' => '27015', // port сервера
            'rcon' => '', // rcon пароль
            'timeout' => 5 // через сколько секунд разорвать соединение если ответа не последовало
        ],
        '2' => [
            'description' => 'AWP',
            'ip' => '194.93.2.127',
            'port' => '27015',
            'rcon' => '',
            'timeout' => 5
        ],
        '3' => [
            'description' => 'MIRAGE #2',
            'ip' => '46.174.51.246',
            'port' => '27015',
            'rcon' => '',
            'timeout' => 5
        ],
        '4' => [
            'description' => 'SURF CLASSIC',
            'ip' => '62.122.213.241',
            'port' => '27015',
            'rcon' => '',
            'timeout' => 5
        ],
        '5' => [
            'description' => 'DUST2',
            'ip' => '46.174.52.253',
            'port' => '27015',
            'rcon' => '',
            'timeout' => 5
        ],
        '6' => [
            'description' => 'AIM',
            'ip' => '37.230.137.169',
            'port' => '27015',
            'rcon' => '',
            'timeout' => 5
        ],
    ],
];