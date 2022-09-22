<?php

class Rights
{
    public const TABLE = CFG_ENGINE['db']['prefix'].'rights';
    public const REGEX_RIGHTS = '(?=^.{1,20}$)(?=[^_])^(?!.*__)[a-zA-Z_]+$(?<=[^_])';

    private PDO $db;
    private string $chat = '';
    
    private ?VK $vk = null;
    private array $rights = [];
    private bool $blockRights = false;

    /**
     * Конструктор
     * 
     * @param PDO $db               PDO объект для работы с MySQL
     * @param int $peerId           peer_id чата для установки чату статус "текущий"
     * @param VK $vk                Необходим для получения админов чата в Rights::isRight()
     */
    public function __construct(PDO $db, int $peerId, ?VK $vk = null)
    {
        $this->db = $db;
        $db->exec('CREATE TABLE IF NOT EXISTS '.self::TABLE.' (id VARCHAR(32) UNIQUE NOT NULL, chats TINYINT NOT NULL DEFAULT 0, '.
            'pm TINYINT NOT NULL DEFAULT 0, every TINYINT NOT NULL DEFAULT 0)');
        $this->changeChat($peerId);
        $this->vk = $vk;
    }

    /**
     * Очистка пользователей без прав
     */
    public function __destruct()
    {
        $sql = 'DELETE FROM '.self::TABLE.' WHERE chats=0';
        $i = 0;
        foreach ($this->db->query('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_NAME = \''.self::TABLE.'\'',
            PDO::FETCH_ASSOC) as $row)
            if (2 < ++$i)
                $sql .= ' AND '.$row['COLUMN_NAME'].'=0';
        $this->db->exec($sql);
    }

    /**
     * Регистрация прав (выполнять в preload)
     * 
     * @param string $right         Права
     * @param string $description   Описание прав
     * 
     * @return bool                 false если права уже зарегистрированы, недействительны, или регистрация заблокированна, true если нет
     */
    public function regRight(string $right, string $description = ''): bool
    {
        if ($this->blockRights || isset($this->rights[$right]) || !self::isValid($right))
            return false;
        
        $this->rights[$right] = $description;
        return true;
    }

    /**
     * Блокировка регистрации прав
     */
    public function blockRights(): void
    {
        $this->blockRights = true;
    }

    /**
     * Получение массива с правами и описаниями
     * 
     * @return array                Массив с правами и описаниями
     */
    public function getRights(): array
    {
        return $this->rights;
    }

    /**
     * Установка чату статус "текущий"
     * 
     * @param int $peerId           peer_id чата
     */
    public function changeChat(int $peerId): void
    {
        if (Utils::isChat($peerId))
        {
            $this->chat = 'c'.($peerId - 2000000000);

            if ($this->db->query('SELECT NULL FROM information_schema.COLUMNS WHERE TABLE_NAME = \''.self::TABLE.'\' AND COLUMN_NAME = \''.
                $this->chat.'\'')->fetch(PDO::FETCH_ASSOC) === false)
                $this->db->exec('ALTER TABLE '.self::TABLE.' ADD COLUMN '.$this->chat.' TINYINT NOT NULL DEFAULT 0');
        }
        else
            $this->chat = 'pm';
    }

    /**
     * Получение текущего чата
     * 
     * @return string               Текущий чат или пустая строка если не установлен
     */
    public function getChat(): string
    {
        return $this->chat;
    }

    /**
     * Проверяет действительность имени прав
     * 
     * @param string $right         Права
     * 
     * @return bool                 true если действительное, false если нет
     */
    public static function isValid(string $right): bool
    {
        return preg_match('/'.self::REGEX_RIGHTS.'/', $right) === 1;
    }

    /**
     * Проверяет действительность чата
     * 
     * @param string $chat          Чат. Действителен при значениях:
     *                                  текущий чат из Rights::getChat()
     *                                  chats - все чаты
     *                                  pm - личные сообщения
     *                                  every - везде
     * 
     * @return bool                 true если чат действителен, false если нет
     */
    public function isValidChat(string $chat): bool
    {
        return $chat === $this->chat || $chat === 'chats' || $chat === 'pm' || $chat === 'every';
    }

    /**
     * Выдача прав
     * 
     * @param int $memberId         member_id пользователя
     * @param string $right         Права
     * @param bool $value           Значение
     * @param string $chat          Чат для выдачи.
     *                                  chats - все чаты
     *                                  pm - личные сообщения
     *                                  every - везде
     *                              По умолчанию - текущий чат
     * 
     * @return bool                 false если права или чат недействительны, true если нет
     */
    public function setRight(int $memberId, string $right, bool $value, string $chat = ''): bool
    {
        if (!$chat)
            $chat = $this->chat;
        
        if (!isset($this->rights[$right]) || !$this->isValidChat($chat))
            return false;

        $this->db->exec('INSERT INTO '.self::TABLE.' (id, '.$chat.') VALUES (\''.$memberId.'_'.$right.'\', '.(int)$value.
            ') ON DUPLICATE KEY UPDATE '.$chat.' = '.(int)$value);
        return true;
    }

    /**
     * Проверка прав
     * 
     * @param int $memberId         member_id пользователя
     * @param string $right         Права
     * @param string $chat          Чат для выдачи.
     *                                  chats - все чаты
     *                                  pm - личные сообщения
     *                                  every - везде
     *                              По умолчанию - текущий чат
     * 
     * @return bool                 true если пользователь админ или у него есть права, false если права/чат недействительны,
     *                              либо у пользователя нет прав
     */
    public function isRight(int $memberId, string $right, string $chat = ''): bool
    {
        if ($this->vk && $this->vk->isAdmin($memberId))
            return true;
        
        if (!$chat)
            $chat = $this->chat;
        
        if (isset($this->rights[$right]) && $this->isValidChat($chat))
        {
            switch ($chat)
            {
                case 'pm':
                {
                    $buffer = 'pm, every';
                    break;
                }
                case $this->chat:
                {
                    $buffer = $chat.', chats, every';
                    break;
                }
                case 'chats':
                {
                    $buffer = 'chats, every';
                    break;
                }
                case 'every':
                    $buffer = 'every';
            }

            foreach ($this->db->query('SELECT '.$buffer.' FROM '.self::TABLE.' WHERE id = \''.$memberId.'_'.$right.'\' OR id = \''.
                $memberId.'_root\'', PDO::FETCH_ASSOC) as $buffer)
            {
                switch ($chat)
                {
                    case 'pm':
                        if ($buffer['pm'] || $buffer['every'])
                            return true;
                    case $this->chat:
                        if ($buffer[$this->chat] || $buffer['chats'] || $buffer['every'])
                            return true;
                    case 'chats':
                        if ($buffer['chats'] || $buffer['every'])
                            return true;
                    case 'every':
                        if ($buffer['every'])
                            return true;
                }
            }
        }
        return false;
    }
}