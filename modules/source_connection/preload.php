<?php

const SOURCE_CONNECTION_V_MAJOR = 1;
const SOURCE_CONNECTION_V_MINOR = 0;
const SOURCE_CONNECTION_V_RELEASE = 0;
const SOURCE_CONNECTION_VERSION = SOURCE_CONNECTION_V_MAJOR.'.'.SOURCE_CONNECTION_V_MINOR.'.'.SOURCE_CONNECTION_V_RELEASE;

$m->lang('source_connection');

if ($m->modulePreload('rights'))
{
    if (!$rights->regRight('source_rcon', LANG_SOURCE_CONNECTION[23]))
        $m->error(LANG_RIGHTS[11], 'source_rcon');
    
    if (!$rights->regRight('source_steamid', LANG_SOURCE_CONNECTION[27]))
        $m->error(LANG_RIGHTS[11], 'source_steamid');
}

if ($cfg_source_connection = Config::parseByPeerId($vk->obj['peer_id'], Config::load('source_connection')))
    foreach ($cfg_source_connection as $cmd => $info)
        if ($cmd !== 'settings')
            $m->regCmd(explode(',', $cmd), $info['description'] ?? '', [
                [
                    'names' => [
                        'info',
                        LANG_SOURCE_CONNECTION[31]
                    ],
                    'description' => LANG_SOURCE_CONNECTION[28]
                ],
                [
                    'names' => [
                        'rcon',
                        LANG_SOURCE_CONNECTION[30]
                    ],
                    'params' => LANG_SOURCE_CONNECTION[22],
                    'description' => LANG_SOURCE_CONNECTION[23]
                ],
                [
                    'names' => [
                        'steamid',
                        LANG_SOURCE_CONNECTION[26]
                    ],
                    'description' => LANG_SOURCE_CONNECTION[27]
                ],
                [
                    'names' => [
                        LANG_SOURCE_CONNECTION[24]
                    ],
                    'description' => LANG_SOURCE_CONNECTION[25]
                ],
                [
                    'names' => [
                        LANG_ENGINE[29]
                    ],
                    'description' => LANG_SOURCE_CONNECTION[29]
                ]
            ]);

require('sourceconnection.php');

return [
    'name' => 'Source Connection',
    'description' => LANG_SOURCE_CONNECTION[33],
    'version' => SOURCE_CONNECTION_VERSION,
    'author' => 'DeathScore13',
    'url' => '*link*'
];