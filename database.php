<?php

class Database extends PDO
{
    public const TABLE = CFG_ENGINE['db']['prefix'].'chats';

    /**
     * Конструктор
     */
    public function __construct(string $host, string $database, string $user, string $pass, int $port = 3306, string $charset = 'utf8mb4')
    {
        parent::__construct('mysql:host='.$host.':'.$port.';dbname='.$database.';charset='.$charset, $user, $pass);
        $this->exec('CREATE TABLE IF NOT EXISTS '.self::TABLE.' (id INT UNIQUE NOT NULL)');
    }

    /**
     * Регистрация чата
     * 
     * @param int $peerId       peer_id чата
     * 
     * @return bool             true при успехе, false если peer_id не является чатом
     */
    public function regChat(int $peerId): bool
    {
        if (Utils::isChat($peerId))
        {
            $this->exec('INSERT IGNORE INTO '.self::TABLE.' (id) VALUES ("'.$peerId.'")');
            return true;
        }
        return false;
    }

    /**
     * Проверка регистрации чата
     * 
     * @param int $peerId       peer_id чата
     * 
     * @return bool             true если зарегистрирован, false если нет
     */
    public function isRegChat(int $peerId): bool
    {
        if (Utils::isChat($peerId))
            return (bool)$this->query('SELECT COUNT(id) FROM '.self::TABLE.' WHERE id = '.$peerId)->fetch(PDO::FETCH_ASSOC);
        return false;
    }
}