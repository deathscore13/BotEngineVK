<?php

if ($m->cmd('rights'))
{
    if (!$rights->isRight($vk->obj['from_id'], 'root'))
        $m->error(LANG_RIGHTS[0]);
    
    if ($m->param(1, ['set', LANG_RIGHTS[19]]))
    {
        if (($right = $m->param(2)) === false)
            $m->error(LANG_ENGINE[11], 2);
        
        if (!$m->param(3, [0, 1]))
            $m->error(LANG_RIGHTS[1]);
        
        if (($target = $m->getTarget(1)) === false)
            $m->error(LANG_ENGINE[7], 1);
        
        if (!$rights->setRight($target, $right, $m->param(3), ($chat = $m->param(4)) === false ? '' : $chat))
            $m->error(LANG_RIGHTS[2]);

        $vk->send(LANG_RIGHTS[3]);
    }
    else if ($m->param(1, ['info', LANG_RIGHTS[20]]))
    {
        if (($target = $m->getTarget(1)) === false)
            $m->error(LANG_ENGINE[7], 1);
        
        $send = '';
        if ($vk->isAdmin($target))
            $send = PHP_EOL.LANG_RIGHTS[4].PHP_EOL;

        foreach ($db->query('SELECT * FROM '.Rights::TABLE.' WHERE id LIKE \''.$target.'_%\'', PDO::FETCH_ASSOC) as $row)
        {
            $send .= PHP_EOL.substr($row['id'], strpos($row['id'], '_') + 1).': ';
            $i = 0;
            $res = array_keys($row);
            while (isset($res[++$i]))
            {
                if ($row[$res[$i]])
                {
                    if (is_numeric($buffer = substr($res[$i], 1)))
                        $res[$i] = $buffer + 2000000000;
                    $send .= $res[$i].', ';
                }
            }
            $send = substr($send, 0, -2);
        }
        if (!$send)
            $send = PHP_EOL.LANG_RIGHTS[5];
        $vk->send(LANG_RIGHTS[6].PHP_EOL.$send);
    }
    else if ($m->param(1, ['list', LANG_RIGHTS[21]]))
    {
        $send = LANG_RIGHTS[9].PHP_EOL;
        foreach ($rights->getRights() as $right => $description)
            $send .= PHP_EOL.$right.' - '.($description ? $description : '???');
        $vk->send($send);
    }
    else
        $vk->send(LANG_ENGINE[3].$m->aboutCmd('rights'));
    exit();
}