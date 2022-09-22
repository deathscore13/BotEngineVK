<?php
// здесь довольно сложные настройки. ничего страшного, если вы поймёте, что вы тупой.
return [
    'every' => // peer_id (через запятую) или: chats - все чаты, pm - личные сообщения, every - везде
    [
        'command,команда' => [                  // команды, на которые среагирует модуль (через запятую)
            'description' => 'Custom command',  // описание команды для !bot commands
            'pm' => true,                       // true - ответить в ЛС, false - в чате где было вызвано
            'message' => 'text',                /* текст сообщения. безопасно упоминать пользователей нужно
                                                   через Utils::createMention('id пользователя', 'текст')
                                                   пример:
                                                   'сегодня '.Utils::createMention('deathscore13', 'Олег').' не кушал кашу :('
                                                   $vk->obj['from_id'] - id пользователя, который отправил команду
                                                */
            'all' => [                          /* позиция дополнительных полей (если размер текста превышает одно сообщение).
                                                   приоритет и обозначения:
                                                   1. end - последнее сообщение
                                                   2. start - первое сообщение
                                                   3. all - все сообщения
                                                */
                'disable_mentions' => false,    // выключить упоминания из сообщения
                'dont_parse_links' => false,    // выключить парсинг ссылок
                'forward' => '',                /* объект forward, выполняющий пересылание сообщений. полное описание параметра forward
                                                   тут: https://dev.vk.com/method/messages.send#Параметры
                                                   пример:
                                                    json_encode([
                                                        'peer_id' => 2000000001,
                                                        'conversation_message_ids' =>
                                                        [
                                                            '1234'
                                                        ]
                                                    ])
                                                */
                'attachments' => '',            /* строка с вложениями к сообщению (через запятую). полное описание параметра attachment
                                                   тут: https://dev.vk.com/method/messages.send#Параметры
                                                   пример:
                                                   'photo181426832_457263734,photo181426832_457263723,photo181426832_457261979'
                                                */
                'keyboard' => '',               /* объект keyboard, отправляющий клавиатуру. полное описание параметра keyboard
                                                   тут: https://dev.vk.com/api/bots/development/keyboard
                                                   ВНИМАНИЕ! для правильного payload необходим Utils::setPayload, и он
                                                   будет перезаписан в $vk->obj. например,
                                                    Utils::setPayload([
                                                        'text' => CFG_ENGINE['prefix'].'peerid'
                                                    ])
                                                   вызовет команду peerid
                                                   пример:
                                                    json_encode([
                                                        'buttons' => [
                                                            [
                                                                [
                                                                    'action' => [
                                                                        'type' => 'text',
                                                                        'payload' => Utils::setPayload([
                                                                            'text' => CFG_ENGINE['prefix'].'bot peerid'
                                                                        ]),
                                                                        'label' => 'peer_id'
                                                                    ],
                                                                    'color' => 'negative'
                                                                ],
                                                                // следующие кнопки в этом ряду
                                                            ],
                                                            // кнопки во втором ряду
                                                        ],
                                                        'inline' => true
                                                    ])
                                                */
                'template' => '',               /* объект template, отправляющий шаблон. полное описание параметра template
                                                   тут: https://dev.vk.com/api/bots/development/messages#%D0%A8%D0%B0%D0%B1%D0%BB%D0%BE%D0%BD%D1%8B%20%D1%81%D0%BE%D0%BE%D0%B1%D1%89%D0%B5%D0%BD%D0%B8%D0%B9
                                                   пример:
                                                    json_encode([
                                                        'type' => 'carousel',
                                                        'elements' => [
                                                            [
                                                                'photo_id' => '-109837093_457242811',
                                                                'action' => [
                                                                    'type' => 'open_photo'
                                                                ],
                                                                'buttons' => [
                                                                    [
                                                                        'action' => [
                                                                            'type' => 'text',
                                                                            'label' => 'Текст кнопки 1'
                                                                        ]
                                                                    ]
                                                                ]
                                                            ],
                                                            [
                                                                'photo_id' => '-109837093_457242811',
                                                                'action' => [
                                                                    'type' => 'open_photo'
                                                                ],
                                                                'buttons' => [
                                                                    [
                                                                        'action' => [
                                                                            'type' => 'text',
                                                                            'label' => 'Текст кнопки 2'
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ])
                                                */
                // остальные дополнительные параметры вы можете найти здесь: https://dev.vk.com/method/messages.send
                // json можно вводить в виде обычного текста, не обязательно использовать массивы + json_encode
            ]
        ]
    ]
];