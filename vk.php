<?php

class VK implements ClassAPIExtensionResult
{
    use ClassAPIExtension;

    private const API_URL = 'https://api.vk.com/method/';

    /**
     * "Отвечать" на сообщения
     */
    public bool $reply = false;

    /**
     * Статус тайпинга перед отправкой сообщения
     */
    public bool $typing = false;

    /**
     * Объект полученный от VK через CallBack API
     */
    public array $obj = [];
    
    private CurlHandle $curl;
    private CurlMultiHandle $multiCurl;

    private bool $analysis = false;
    private array $members = [];
    
    public function __construct()
    {
        curl_setopt($this->curl = curl_init(), CURLOPT_RETURNTRANSFER, true);
        $this->multiCurl = curl_multi_init();
    }
    
    public function __destruct()
    {
        curl_close($this->curl);
        curl_multi_close($this->multiCurl);
    }

    /**
     * Установка режима анализа
     */
    public function setAnalysis(): void
    {
        $this->analysis = true;
    }

    /**
     * Проверка режима анализа
     * 
     * @return bool             true если включен, false если нет
     */
    public function getAnalysis(): bool
    {
        return $this->analysis;
    }
    
    /**
     * Отправка запроса в VK
     * 
     * @param string $method    Имя метода
     * @param array $params     Массив с параметрами
     * 
     * @return array            Ответ от VK
     */
    public function query(string $method, array $params): array
    {
        curl_setopt_array($this->curl, [
            CURLOPT_URL => self::API_URL.$method,
            CURLOPT_POSTFIELDS => [
                'access_token' => CFG_ENGINE['access_token'],
                'v' => CFG_ENGINE['v']
            ] + Utils::array_filter($params)
        ]);

        try
        {
            if ($res = json_decode(curl_exec($this->curl), true))
                return $res;
        }
        catch (Exception $e)
        {
        }
        exit(); // VK упал
    }
    
    /**
     * Асинхронная отправка запросов в VK (очередь не соблюдается)
     * 
     * @param array $buffer     Массив с методами и параметрами
     *                          Пример в VK::send()
     * 
     * @return array            Массив с ответами
     */
    public function multiQuery(array $buffer): array
    {
        $i = -1;
        $curls = [];
        foreach ($buffer as $request => $buffer)
        {
            curl_setopt_array($curls[++$i] = curl_init(), [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => self::API_URL.$buffer['method'],
                CURLOPT_POSTFIELDS => [
                    'access_token' => CFG_ENGINE['access_token'],
                    'v' => CFG_ENGINE['v']
                ] + Utils::array_filter($buffer['params'])
            ]);
            curl_multi_add_handle($this->multiCurl, $curls[$i]);
        }
        
        try
        {
            do
                {
                $status = curl_multi_exec($this->multiCurl, $active);
                if ($active)
                    curl_multi_select($this->multiCurl);
            }
            while ($active && $status === CURLM_OK);
        
            $i = -1;
            $buffer = [];
               while (isset($curls[++$i]))
               {
                   $buffer[$i] = curl_multi_getcontent($curls[$i]);
                curl_multi_remove_handle($this->multiCurl, $curls[$i]);
                curl_close($curls[$i]);
            }

            if (Utils::array_filter($buffer))
                return $buffer;
        }
        catch (Exception $e)
        {
        }
        exit(); // VK упал
    }

    /**
     * Установка участников чата
     * 
     * @param int $peedId       peer_id чата
     */
    public function setMembers(int $peerId): void
    {
        $this->members = $this->query('messages.getConversationMembers', ['peer_id' => $peerId])['response'] + ['peer_id' => $peerId];
        
        $buffer = [];
        foreach ($this->members['items'] as $key => $value)
            $buffer += [$value['member_id'] => $value];
        $this->members['items'] = $buffer;

        $buffer = [];
        foreach ($this->members['profiles'] as $key => $value)
            $buffer += [$value['id'] => $value];
        $this->members['profiles'] = $buffer;

        $buffer = [];
        foreach ($this->members['groups'] as $key => $value)
            $buffer += [$value['id'] => $value];
        $this->members['groups'] = $buffer;
    }

    /**
     * Получение участников чата
     * 
     * @return array            Ответ messages.getConversationMembers, с peer_id из VK::setMembers() и изменёнными индексами:
     *                              items = [member_id => array]
     *                              profiles = [id => array]
     *                              groups = [id => array]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * Проверяет является ли участник админом чата.
     * Получение админов происходит в VK::setMembers()
     * 
     * @param int $memberId         member_id участника
     * 
     * @return bool                 true если является админом, false если нет
     */
    public function isAdmin(int $memberId): bool
    {
        return isset($this->members['items'][$memberId]['is_admin']);
    }
    
    /**
     * Правильная отправка сообщения в VK
     * Если сообщение превышает лимит текста, то оно будет разбито и параметры из $params распределятся по end, start и all
     * 
     * @param string $msg       Текст сообщения
     * @param array $params     Опционально. Массив с параметрами, где end - параметры применяются для последнего сообщения,
     *                          start - параметры применяются для первого сообщения, и all - параметры применяются для всех сообщений.
     *                          Пример (start, end или all могут быть пропущены):
     *                              [
     *                                  'start' => [ // параметры только для первого сообщения
     *                                      'forward' => json_encode([ // например, только в первом сообщении ответить на какое-то сообщение
     *                                          'peer_id' => $peerId,
     *                                          'conversation_message_ids' => [$vk->obj['conversation_message_id']],
     *                                          'is_reply' => true
     *                                      ])
     *                                  ],
     *                                  'end' => [ // параметры только для последнего сообщения
     *                                      // например, только к последнему сообщению прикрепить фотографии
     *                                      'attachments' => 'photo181426832_457263734,photo181426832_457263723,photo181426832_457261979'
     *                                  ],
     *                                  'all' => [ // параметры для всех сообщений, но может быть изменено в start и end
     *                                      // например, во всех сообщениях отключим уведомления и парсинг ссылок
     *                                      'disable_mentions' => false,
     *                                      'dont_parse_links' => false,
     *                                  ]
     *                              ]
     *                          Приоритет: end, start, all
     *                          Если не указан end, start или all, но массив не пустой, то применяется для всех сообщений
     * @param int $peerId       peer_id чата
     *                          По умолчанию текущий чат
     * 
     * @return array            Массив с ответами от VK
     */
    public function send(string $msg, array $params = [], int $peerId = 0): array
    {
        if ($params && !isset($params['end']) && !isset($params['start']) && !isset($params['all']))
            $params = ['all' => $params];

        if (!$peerId)
            $peerId = $this->obj['peer_id'];
        
        if ($this->typing && !$this->analysis)
        {
            $this->multiQuery([
                [
                    'method' => 'messages.markAsRead',
                    'params' => [
                        'mark_conversation_as_read' => true,
                        'peer_id' => $peerId
                    ]
                ],
                [
                    'method' => 'messages.setActivity',
                    'params' => [
                        'peer_id' => $peerId,
                        'type' => 'typing'
                    ]
                ]
            ]);
            sleep(rand(CFG_ENGINE['typing']['min'], CFG_ENGINE['typing']['max']));
        }
        
        $i = -1;
        $buffer = [];
        while (true)
        {
            $sendParams = ($params['all'] ?? []) + [
                'peer_ids' => $peerId,
                'random_id' => rand(-2147483648, 2147483647)
            ];

            $end = false;
            if (4096 < mb_strlen($msg))
            {
                $send = mb_substr($msg, 0, 4096);
                
                if (($sym = mb_strrpos($send, ']', -CFG_ENGINE['pruning'])) !== false ||
                    ($sym = mb_strrpos($send, PHP_EOL, -CFG_ENGINE['pruning'])) !== false ||
                    ($sym = mb_strrpos($send, ' ', -CFG_ENGINE['pruning'])) !== false)
                {
                    $send = mb_substr($msg, 0, ++$sym);
                    $msg = mb_substr($msg, $sym);
                }
                else
                    $msg = mb_substr($msg, 4096);
            }
            else
            {
                $send = $msg;
                $end = true;
            }
            
            if (!++$i)
            {
                $this->typing = false;
                if (!empty($params['start']['forward']))
                    $sendParams['forward'] = $params['start']['forward'];
                else if (empty($sendParams['forward']) && $this->reply && Utils::isChat($peerId) && $this->obj)
                    $sendParams['forward'] = json_encode([
                        'peer_id' => $peerId,
                        'conversation_message_ids' => [$this->obj['conversation_message_id']],
                        'is_reply' => true
                    ]);
            }
            
            if ($end)
            {
                $this->typing = CFG_ENGINE['typing']['enable'];
                return $buffer + [
                        $i => $this->query('messages.send', [
                            'forward' => $params['end']['forward'] ?? $sendParams['forward'] ?? '',
                            'message' => $send
                        ] + $sendParams)
                    ];
            }
            
            $buffer[$i] = $this->query('messages.send', $sendParams + ['message' => $send]);
        }
    }

    /**
     * Ответ в ЛС с предупреждением в чат (если $peerId является ЛС, то предупреждения нет)
     * 
     * @param string $msg       Текст сообщения
     * @param int $len          Отправка сообщения в лс если текст превышает $len символов
     * @param array $params     Идентично VK::send()
     * @param int $peerId       peer_id чата
     *                          По умолчанию текущий чат
     * @param int $fromId       ID пользователя для отправки в лс
     *                          По умолчанию текущий пользователь
     * 
     * @return array            Массив с ответами от VK
     *                          Последнее из $peerId (предупреждение), остальные из $fromId
     */
    public function replyPM(string $msg, int $len = -1, array $params = [], int $peerId = 0, int $fromId = 0): array
    {
        if (!$peerId)
            $peerId = $this->obj['peer_id'];
        
        if (!$fromId)
            $fromId = $this->obj['from_id'];
        
        if ($len < mb_strlen($msg))
        {
            $res = $this->send($msg, $params, $fromId);
            if (Utils::isChat($peerId))
                $res[] = $this->send($this->getErrorArray($res) ? LANG_ENGINE[14] : LANG_ENGINE[15], $params, $peerId)[0];
        }
        else
        {
            $res = $this->send($msg, $params, $peerId);
        }
        
        return $res;
    }
    
    /**
     * Проверяет существование пользователя или сообщества в чате
     * Получение участников происходит в VK::setMembers()
     * 
     * @param int $peerId       peer_id чата
     * @param int $memberId     member_id участника
     * 
     * @return bool             true если состоит в чате, false если нет
     */
    public function inChat(int $peerId, int $memberId): bool
    {
        if (Utils::isChat($this->members['peer_id']))
            return isset($this->members['items'][$memberId]);
        
        return $this->members['peer_id'] === $memberId;
    }
    
    /**
     * Проверяет наличие ошибки в ответе от VK
     * 
     * @param array $result     Ответ от VK
     * 
     * @return bool|string      false если ошибки нет, текст ошибки если есть
     */
    public function getError(array $result): bool|string
    {
        return isset($result['error']) ? sprintf(LANG_ENGINE[6].LANG_ENGINE[8], $result['error']['error_code'],
            $result['error']['error_msg']) : false;
    }

    /**
     * Проверяет наличие ошибки в массиве ответов от VK
     * Подойдёт для VK::send() и VK::multiQuery()
     * 
     * @param array $results    Массив ответов от VK
     * 
     * @return bool|string      false если ошибки нет, текст ошибки если есть
     */
    public function getErrorArray(array $results): bool|string
    {
        foreach ($results as $request => $result)
            if (isset($result['response'][0]['error']))
                return sprintf(LANG_ENGINE[6].LANG_ENGINE[9], $request, $result['response'][0]['error']['code'],
                    $result['response'][0]['error']['description']);
        return false;
    }
}