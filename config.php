<?php

abstract class Config
{
    /**
     * Подключение конфига
     * 
     * @param string $file      Имя файла из папки configs
     * 
     * @return bool             true если подключён, false если файл отсутствует
     */
    public static function load(string $file): bool
    {
        if (file_exists($file = 'configs/'.$file.'.php'))
        {
            if (!in_array($file, get_included_files()))
            {
                require($file);

                foreach (get_defined_vars() as $key => $value)
                    $GLOBALS[$key] = $value;
            }
            return true;
        }
        return false;
    }
    
    /**
     * Парсинг конфига
     * 
     * @param int $peerId       peer_id чата
     * @param array $config     Массив с настройками
     *                              peer_id могут быть перечислены в ключе через запятую
     *                              chats - все чаты
     *                              pm - личные сообщения
     *                              every - везде
     * 
     * @return array|bool       Массив с настройками для текущего чата или false, если настройки не были найдены
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
        else if (isset($config['pm']))
        {
            return $config['pm'];
        }
        
        if (isset($config['every']))
            return $config['every'];
        
        return false;
    }
}