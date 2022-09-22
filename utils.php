<?php

abstract class Utils
{
    /**
     * Необходимо задать значение перед сортировкой с помощью usort через Utils::usort_asc_callback и Utils::usort_desc_callback
     */
    public static $sortKey = '';

    /**
     * Проверка что peer_id является групповым чатом
     * 
     * @param int $peerId       peer_id
     * 
     * @return bool             true если групповой чат, false если ЛС
     */
    public static function isChat(int $peerId): bool
    {
        return 2000000000 < $peerId;
    }
    
    /**
     * Проверка что member_id является пользователем
     * 
     * @param int $memberId     member_id
     * 
     * @return bool             true если пользователь, false если сообщество
     */
    public static function isUser(int $memberId): bool
    {
        return 0 < $memberId;
    }
    
    /**
     * Создание упоминания с текстом, которое не сломается во время переноса длинных сообщений
     * 
     * @param mixed $id         member_id или имя короткой ссылки (например: deathscore13)
     * @param string $text      Текст упоминания
     * 
     * @return string           Упоминание
     */
    public static function createMention(mixed $id, string $text): string
    {
        return '['.(is_numeric($id) ? ((self::isUser($id) ? 'id'.$id : 'club'.substr($id, 1))) : $id).'|'.$text.']';
    }
    
    /**
     * Поиск $find в ключе массива
     * В ключе массива $find может разделяться через запятую
     * 
     * @param mixed $find       Что-то для поиска
     * @param array $buffer     Массив где будет происходить поиск
     * 
     * @return bool|array       false если не найдено, значение по ключу если найдено
     */
    public static function findKey(mixed $find, array $buffer): bool|array
    {
        if (!isset($buffer[0]))
            foreach ($buffer as $key => $value)
                if (in_array($find, explode(',', $key)))
                    return $value;
        return false;
    }

    /**
     * Создание payload объекта, поддерживаемого этим движком
     * 
     * @param array $buffer     Массив для преобразования
     * 
     * @return string           payload объект
     */
    public static function setPayload(array $buffer): string
    {
        return json_encode([
            '~confirm' => CFG_ENGINE['confirm'],
            'base64' => base64_encode(json_encode($buffer))
        ]);
    }

    /**
     * Получить значение из payload объекта, поддерживаемого этим движком
     * 
     * @param string $payload   payload объект
     * 
     * @return bool|array       false если payload объект создан не текущим ботом
     *                          Массив с данными, если payload оказался действительным
     */
    public static function getPayload(string $payload): bool|array
    {
        if (isset(($res = json_decode($payload, true))['~confirm']) && $res['~confirm'] === CFG_ENGINE['confirm'])
            return json_decode(base64_decode($res['base64']), true);
        return false;
    }

    /**
     * Каллбек функция usort для сортировки по возрастанию ключа Utils::$sortKey
     */
    public static function usort_asc_callback(mixed $a, mixed $b): int
    {
        if ($a[self::$sortKey] == $b[self::$sortKey])
            return 0;
        return $a[self::$sortKey] < $b[self::$sortKey] ? -1 : 1;
    }

    /**
     * Каллбек функция usort для сортировки по убыванию ключа Utils::$sortKey
     */
    public static function usort_desc_callback(mixed $a, mixed $b): int
    {
        if ($a[self::$sortKey] == $b[self::$sortKey])
            return 0;
        return $a[self::$sortKey] < $b[self::$sortKey] ? 1 : -1;
    }

    /**
     * Удаление пустых значений в массивах
     * Отличие от array_filter в том, что подмассивы тоже проверяются
     * 
     * @param array $arr        Массив для очищения
     * 
     * @return array            Очищенный массив
     */
    public static function array_filter(array $arr): array
    {
        foreach ($arr as $key => $value)
        {
            if (is_array($value))
                $value = self::array_filter($value);
            
            if (!$value)
                unset($arr[$key]);
        }
        return $arr;
    }
}