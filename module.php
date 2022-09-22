<?php

class Module
{
    private ?VK $vk = null;
    private array $cmds = [];
    private array $params = [];
    private array $targets = [];
    private array $paramStrings = [];
    private bool $blockCmd = false;
    
    /**
     * Установка VK объекта
     * Нужен для Module::setTargets(), получения user_id из ссылки в Module::target(), peer_id чата в Module::log()
     * и отправки в VK ошибки в Module::error()
     * 
     * @param VK &$vk           VK объект
     */
    public function setVK(VK $vk): void
    {
        $this->vk = $vk;
    }

    /**
     * Регистрация команды (выполнять в preload)
     * 
     * @param array $cmds           Массив с командами (первое значение - идентификатор команды)
     * @param string $description   Описание команды
     * @param mixed $params         Параметры команды:
     *                                  [
     *                                      [
     *                                          'names' => [    // имена параметра
     *                                              'parameter',
     *                                              'параметр'
     *                                          ],
     *                                          'params' => '<слово> [цель]',   // следующие параметры
     *                                          'description' => 'тестовый параметр'    // описание параметра
     *                                      ],
     *                                      // следующий параметр
     *                                  ]
     *                              Либо строка, если команда не разделяется на под-параметры
     * 
     * @return bool                 false если команда уже есть или регистрация заблокированна, true если нет
     */
    public function regCmd(array $cmds, string $description = '', mixed $params = ''): bool
    {
        if ($this->blockCmd || !isset($cmds[0]) || isset($this->cmds[$cmds[0]]))
            return false;
        
        $cmds = array_unique($cmds);
        if (is_array($params))
            foreach ($params as $key => $param)
                $params[$key]['names'] = array_unique($param['names']);
        
        $this->cmds += [
            $cmds[0] => [
                'cmds' => $cmds,
                'description' => $description ? $description : '???',
                'params' => $params ? $params : ''
            ]
        ];
        return true;
    }

    /**
     * Возвращает массив с зарегистрированными командами
     * 
     * @return array                Массив с зарегистрированными командами
     */
    public function getCmds(): array
    {
        return $this->cmds;
    }

    /**
     * Возвращает строку с информацией о команде
     * 
     * @param string $cmd           Идентификатор команды
     * 
     * @return bool|string          false если команда не найдена, или строка с информацией о команде
     */
    public function aboutCmd(string $cmd): bool|string
    {
        if (!isset($this->cmds[$cmd]))
            return false;
        
        if (is_array($this->cmds[$cmd]['params']))
        {
            $buffer = sprintf(LANG_ENGINE[17], implode(', ', $this->cmds[$cmd]['cmds']), $this->cmds[$cmd]['description']);
            foreach ($this->cmds[$cmd]['params'] as $param)
                $buffer .= PHP_EOL.sprintf(LANG_ENGINE[18], implode(', ', $param['names']), isset($param['params']) ?
                    $param['params'].' ' : '', $param['description'] ?? '???');
        }
        else
            $buffer = sprintf(LANG_ENGINE[19], implode(', ', $this->cmds[$cmd]['cmds']), $this->cmds[$cmd]['params'] ?
                $this->cmds[$cmd]['params'].' ' : '', $this->cmds[$cmd]['description']);
        return $buffer;
    }

    /**
     * Блокировка Module::regCmd()
     */
    public function blockCmd(): void
    {
        $this->blockCmd = true;
    }

    /**
     * Проверяет может ли текст являться командой
     * 
     * @param string $text      Текст для проверки
     * 
     * @return bool             true если текст содержит команду, false если нет
     */
    public function isCmd(string $text): bool
    {
        return mb_strtolower(substr($text, 0, $len = strlen(CFG_ENGINE['prefix']))) === CFG_ENGINE['prefix'] && isset($text[$len]);
    }

    /**
     * Запись параметров из текста (первый параметр - команда)
     * 
     * @param string $text      Текст для обработки
     */
    public function setParams(string $text): void
    {
        if ($text)
        {
            $buffer = preg_replace('/\s*'.REGEX_USER.'\s*|\s*'.REGEX_COMMUNITY.'\s*|\s*'.REGEX_LINK.'\s*/', ' ', $text);
            if ($buffer[$len = strlen($buffer) - 1] === ' ')
                $buffer = substr($buffer, 0, $len);
            $this->params = explode(' ', $buffer);
        }
        else
            $this->params = [];
    }

    /**
     * Запись целей из текста (упоминания, ссылки) и отвеченного или пересланных сообщений
     * 
     * @param string $text      Текст для обработки
     */
    public function setTargets(string $text): void
    {
        if ($text)
        {
            preg_match_all('/'.REGEX_USER.'|'.REGEX_COMMUNITY.'|'.REGEX_LINK.'/', $text, $this->targets);
            $this->targets = $this->targets[0];
        }
        else
            $this->targets = [];

        if (isset($this->vk->obj['reply_message']))
            $this->targets[] = $this->vk->obj['reply_message']['from_id'];
        else
        {
            $i = -1;
            while (isset($this->vk->obj['fwd_messages'][++$i]))
                $this->targets[] = $this->vk->obj['fwd_messages'][$i]['from_id'];
        }
    }

    /**
     * Запись строк параметров из текста
     * Разделение строки - перенос (enter)
     * 
     * @param string $text      Текст для обработки
     */
    public function setParamStrings(string $text): void
    {
        $this->paramStrings = $text ? explode(PHP_EOL, $text) : [];
    }
    
    /**
     * Подключение языкового файла
     * 
     * @param string $name      Имя папки с переводами
     */
    public function lang(string $name): void
    {
        require(file_exists($path = 'langs/'.$name.'/'.CFG_ENGINE['lang'].'.php') ? $path : 'langs/'.$name.'/en.php');
    }
    
    /**
     * Подключение библиотеки
     * 
     * @param string $file      Путь к php файлу относительно директории libs
     */
    public function lib(string $file): void
    {
        require_once('libs/'.$file.'.php');
    }
    
    /**
     * Проверяет существование модуля
     * 
     * @param string $name      Имя папки модуля
     * 
     * @return bool             true если существует, false если нет
     */
    public function moduleExists(string $name): bool
    {
        return is_dir('modules/'.$name);
    }

    /**
     * Подключает preload.php модуля
     * 
     * @param string $name      Имя папки модуля
     * 
     * @return bool             true если существует, false если нет
     */
    public function modulePreload(string $name): bool
    {
        if (file_exists($path = 'modules/'.$name.'/preload.php'))
        {
            require_once($path);
            return true;
        }
        return false;
    }
    
    /**
     * Проверка вызова зарегистрированной команды
     * 
     * @param string $cmd       Идентификатор команды
     * 
     * @return bool             true если найдена, false если не найдена
     */
    public function cmd(string $cmd): bool
    {
        return isset($this->params[0]) && $cmd && isset($this->cmds[$cmd]) ? in_array($this->params[0], $this->cmds[$cmd]['cmds']) : false;
    }
    
    /**
     * Проверка или получение параметра
     * 
     * @param int $num          Номер параметра, начиная с 1; 0 - имя команды
     * @param array $params     Массив с возможными параметрами
     * 
     * @return bool|string      Параметр, если не указан или пустой $params, false если не найден или true если найден и указан $params
     */
    public function param(int $num, array $params = []): bool|string
    {
        return isset($this->params[$num]) ? ($params ? in_array($this->params[$num], $params) : $this->params[$num]) : false;
    }
    
    /**
     * Цель (member_id) из сообщения, отвеченного сообщения или пересланных сообщений
     * В сообщении цель определяется упоминанием (@deathscore13) или ссылкой (vk.com/deathscore13), причём не важно есть https:// или нет
     * Отправители отвеченного или пересланных сообщений идут после целей в тексте основного сообщения
     * 
     * @param int $num          Номер параметра, начиная с 1; 0 не существует для того чтобы не было путаницы
     * 
     * @return bool|int         false если не найдена или недействительная, member_id пользователя/сообщества если найдена и действительна
     */
    public function getTarget(int $num): bool|int
    {
        $num -= 1;

        if (!isset($this->targets[$num]))
            return false;
        
        if (is_numeric($this->targets[$num]))
            return $this->targets[$num];
        
        if (preg_match('/'.REGEX_USER.'/', $this->targets[$num]))
            return substr($this->targets[$num], 3, strpos($this->targets[$num], '|') - 3);
        
        if (preg_match('/'.REGEX_COMMUNITY.'/', $this->targets[$num]))
            return -substr($this->targets[$num], 5, strpos($this->targets[$num], '|') - 5);
        
        if ($this->vk && ($res = strpos($this->targets[$num], 'vk.com/')) !== false)
        {
            if (isset(($res = $this->vk->query('utils.resolveScreenName', [
                    'screen_name' => substr($this->targets[$num], $res + 7)
                ])['response'])['type']))
                if ($res['type'] === 'user')
                    return $res['object_id'];
                elseif ($res['type'] === 'group')
                    return -$res['object_id'];
        }
        
        return false;
    }

    /**
     * Строка с параметрами
     * Разделение строки - перенос (enter)
     * 
     * @param int $num          Номер строки, начиная с 1; 0 - строка с командой и параметрами
     * 
     * @return bool|string      false если не найдена, текст строки если найдена
     */
    public function getParamString(int $num): bool|string
    {
        return $this->paramStrings[$num] ?? false;
    }
    
    /**
     * Запись текста в лог
     * 
     * @param string $text      Текст для записи
     * @param string $folder    Имя папки относительно директории logs
     *                          По умолчанию '~without_peer_id'
     */
    public function log(string $text, string $folder = '~without_peer_id'): void
    {
        if (CFG_ENGINE['logs'])
        {
            if (!is_dir($dir = 'logs/'.($this->vk->obj['peer_id'] ?? $folder)))
                mkdir($dir);
            file_put_contents($dir.'/'.date('d.m.Y').'.log', date('[H:i:s]:    ').$text.PHP_EOL, FILE_APPEND);
        }
    }
    
    /**
     * Сообщение об ошибке отправляется в VK, или записывается в лог если Module::setVK() не выполнен
     * После репорта скрипт завершается
     * 
     * @param string $format    Строка для форматирования
     * @param mixed ...$params  Параметры для форматирования
     */
    public function error(string $format, mixed ...$params)
    {
        if ($this->vk)
            $this->vk->send(vsprintf(LANG_ENGINE[6].$format, $params));
        else
            $this->log(vsprintf(LANG_ENGINE[6].$format, $params));
        exit();
    }
}