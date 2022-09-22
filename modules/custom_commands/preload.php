<?php

const CUSTOM_COMMANDS_V_MAJOR = 1;
const CUSTOM_COMMANDS_V_MINOR = 0;
const CUSTOM_COMMANDS_V_RELEASE = 0;
const CUSTOM_COMMANDS_VERSION = CUSTOM_COMMANDS_V_MAJOR.'.'.CUSTOM_COMMANDS_V_MINOR.'.'.CUSTOM_COMMANDS_V_RELEASE;

$m->lang('custom_commands');

if ($cfg_custom_commands = Config::parseByPeerId($vk->obj['peer_id'], Config::load('custom_commands')))
    foreach ($cfg_custom_commands as $cmd => $params)
        $m->regCmd(explode(',', $cmd), $params['description'] ?? '');

return [
    'name' => 'Custom Commands',
    'description' => LANG_CUSTOM_COMMANDS[0],
    'version' => CUSTOM_COMMANDS_VERSION,
    'author' => 'DeathScore13',
    'url' => '*link*'
];