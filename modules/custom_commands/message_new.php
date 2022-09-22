<?php

if (($res = $m->param(0)) &&
    $cfg_custom_commands &&
    $res = Utils::findKey($res, $cfg_custom_commands))
{
    if ($res['pm'])
        $vk->replyPM($res['message'] ?? '', -1, $res);
    else
        $vk->send($res['message'] ?? '', $res);
    exit();
}