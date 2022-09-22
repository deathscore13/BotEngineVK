<?php

if (($res = $m->param(0)) &&
    $cfg_source_connection &&
    $res = Utils::findKey($res, $cfg_source_connection))
{
    if ($m->param(1, ['rcon', LANG_SOURCE_CONNECTION[30]]))
    {
        if (!$m->moduleExists('rights'))
            $m->error(LANG_ENGINE[16], 'rights');
        
        if (!$rights->isRight($vk->obj['from_id'], 'source_rcon'))
            $m->error(LANG_RIGHTS[10], 'source_rcon');
        
        if (!($buffer = substr($m->getParamString(0), strlen($m->param(0)) + strlen($m->param(1)) + 2)))
            $m->error(LANG_ENGINE[11], 2);
        
        $vk->replyPM(LANG_SOURCE_CONNECTION[0].PHP_EOL.PHP_EOL.(($res = SourceConnection::exec($res, $buffer)) ? $res :
            LANG_SOURCE_CONNECTION[1]), $cfg_source_connection['settings']['response']);
    }
    else if (($buffer = $m->param(1)) &&
        $buffer !== 'info' && $buffer !== LANG_SOURCE_CONNECTION[31] &&
        $buffer !== 'steamid' && $buffer !== LANG_SOURCE_CONNECTION[32])
    {
        $msg = substr($m->getParamString(0), strlen($m->param(0)) + 1);
        $send = '';
        
        if ($buffer = SourceConnection::clearMsg($msg, $cfg_source_connection['settings']['replace']))
            $send .= sprintf(LANG_SOURCE_CONNECTION[3], implode(', ', $buffer), $cfg_source_connection['settings']['replace']).PHP_EOL.PHP_EOL;
        
        if (!($len = mb_strlen($msg)) || SourceConnection::MSG_SIZE <= $len)
            $send .= sprintf(LANG_SOURCE_CONNECTION[2], SourceConnection::MSG_SIZE);
        else
        {
            $sc = new SourceConnection($db);
            $member = $vk->getMembers()['profiles'][$vk->obj['from_id']];
            switch ($res = $sc->send($res, $member['first_name'].' '.$member['last_name'], $cfg_source_connection['settings']['from'], $msg))
            {
                case SourceConnection::SUCCESS:
                {
                    $send .= LANG_SOURCE_CONNECTION[4];
                    break;
                }
                case SourceConnection::FAILED:
                case '':
                {
                    $send .= LANG_SOURCE_CONNECTION[5];
                    break;
                }
                default:
                    $send .= $res;
            }
        }
        $vk->replyPM($send, $cfg_source_connection['settings']['response']);
    }
    else
    {
        $subcmd = $m->param(1);
        if (($subcmd === 'steamid' || $subcmd === LANG_SOURCE_CONNECTION[32]) && $cfg_source_connection['settings']['steamid'])
        {
            if (!$m->moduleExists('rights'))
                $m->error(LANG_ENGINE[16], 'rights');
        
            if (!$rights->isRight($vk->obj['from_id'], 'source_steamid'))
                $m->error(LANG_RIGHTS[10], 'source_steamid');
        }

        $sc = new SourceConnection($db);
        if (is_array($buffer = $sc->info($res)))
        {
            $send = sprintf(LANG_SOURCE_CONNECTION[20],
            /* 01 */    $buffer['HostName'],
            /* 02 */    $res['ip'],
            /* 03 */    $res['port'],
            /* 04 */    isset($buffer['PlayersList']) && is_array($buffer['PlayersList']) ? count($buffer['PlayersList']) : $buffer['Players'],
            /* 05 */    $buffer['MaxPlayers'],
            /* 06 */    $buffer['Bots'],
            /* 07 */    $buffer['Map']
            );
            
            if ($subcmd !== 'info' && $subcmd !== LANG_SOURCE_CONNECTION[31])
            {
                if (is_array($buffer['PlayersList']))
                {
                    Utils::$sortKey = 'frags';
                    usort($buffer['PlayersList'], 'Utils::usort_desc_callback');

                    Utils::$sortKey = 'team';
                    usort($buffer['PlayersList'], 'Utils::usort_asc_callback');

                    $send .= PHP_EOL.PHP_EOL.($subcmd ? LANG_SOURCE_CONNECTION[18] : LANG_SOURCE_CONNECTION[17]);
                    foreach ($buffer['PlayersList'] as $player)
                    {
                        $d = floor($player['time'] / 86400);
                        $h = floor(($player['time'] - ($d * 86400)) / 3600);
                        $m = floor(($player['time'] - ($d * 86400) - ($h * 3600)) / 60);
                        $s = floor(($player['time'] - ($d * 86400) - ($h * 3600) - ($m * 60)));
                        
                        $send .= PHP_EOL.sprintf(($subcmd ? LANG_SOURCE_CONNECTION[19] : LANG_SOURCE_CONNECTION[11]), 
                        /* 01 */    LANG_SOURCE_CONNECTION[7 + $player['team']],
                        /* 02 */    $player['frags'],
                        /* 03 */    $player['deaths'],
                        /* 04 */    ($d ? $d.LANG_SOURCE_CONNECTION[12].' ' : '').($h ? $h.LANG_SOURCE_CONNECTION[13].' ' : '').
                                        ($m ? $m.LANG_SOURCE_CONNECTION[14].' ' : '').$s.LANG_SOURCE_CONNECTION[15],
                        /* 05 */    $player['muted'] ? LANG_SOURCE_CONNECTION[16] : '',
                        /* 06 */    $player['ip'],
                        /* 07 */    $player['steamid'],
                        /* 08 */    $player['name']
                        );
                    }
                }
                else
                    $send .= PHP_EOL.PHP_EOL.$buffer['PlayersList'];
            }
        }
        else
            $send = $buffer;
        
        $vk->replyPM($send, $cfg_source_connection['settings']['players'], [
            'dont_parse_links' => true,
            'disable_mentions' => true
        ]);
    }

    exit();
}