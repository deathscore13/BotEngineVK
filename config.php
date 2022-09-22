<?php

abstract class Config
{
    /**
     * Подключение конфига
     * 
     * @param string $file  Имя файла из папки configs
     * 
     * @return array        Массив с настройками
     */
    public static function load(string $file): array
    {
        return require('configs/'.$file.'.php');
    }
    
    /**
     * Парсинг конфига
     * 
     * @param int $peerId   peer_id чата
     * @param array $config Массив с настройками
     *                      peer_id могут быть перечислены в ключе через запятую
     *                      chats - все чаты
     *                      pm - личные сообщения
     *                      every - везде
     * 
     * @return array|bool   Массив с настройками для текущего чата или false, если настройки не были найдены
     */
    public static function parseByPeerId(int $peerId, array $config): array|bool
    {
        $config = Utils::array_filter($config);
        
        if ($res = Utils::findKey($peerId, $config))
            return $res;
        
        if (Utils::isChat($peerId))
        {
            if (isset($config['chats']))
                return $config['chats'];
        }
        elseif (isset($config['pm']))
            return $config['pm'];
        
        if (isset($config['every']))
            return $config['every'];
        
        return false;
    }
}