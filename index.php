<?php

const ENGINE_V_MAJOR = 1;
const ENGINE_V_MINOR = 0;
const ENGINE_V_RELEASE = 0;
const ENGINE_VERSION = ENGINE_V_MAJOR.'.'.ENGINE_V_MINOR.'.'.ENGINE_V_RELEASE;

const REGEX_CALLBACK = '^(?!.*\/\.\.\/).*\/modules\/\S+\/\S+_callback$';
const REGEX_USER = '\[id\d+\|[^\]]+\]';
const REGEX_COMMUNITY = '\[club\d+\|[^\]]+\]';
const REGEX_LINK = '\S*vk\.com\/\S+';

unset($_GET['/index_php']);
if (isset(($buffer = array_keys($_GET))[0]) &&
    preg_match('/'.REGEX_CALLBACK.'/', $buffer[0]))
{
    if (file_exists($buffer = '.'.$buffer[0].'.php'))
    {
        require('configs/engine.php');
        $data = json_decode(file_get_contents('php://input'), true);

        require('libs/ClassAPIExtension.php');
        require('utils.php');
        require('vk.php');
        require('module.php');
        require('config.php');
        require('database.php');
        
        $vk = new VK();
        ($m = new Module())->setVK($vk);
        $db = new Database(CFG_ENGINE['db']['host'], CFG_ENGINE['db']['database'], CFG_ENGINE['db']['user'], CFG_ENGINE['db']['pass'],
            CFG_ENGINE['db']['port'], CFG_ENGINE['db']['charset']);
        
        $vk->reply = CFG_ENGINE['reply'];
        $vk->typing = CFG_ENGINE['typing']['enable'];
        $m->lang('engine');

        require($buffer);
    }
    exit();
}
else if ($buffer[0] !== '/botenginevk')
    exit();

require('configs/engine.php');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['secret']) || !isset($data['group_id']) ||
    $data['secret'] !== CFG_ENGINE['secret'] ||
    $data['group_id'] !== CFG_ENGINE['group_id'])
    exit();

if ($data['type'] === 'confirmation')
{
    echo(CFG_ENGINE['confirm']);
    exit();
}
else
{
    echo('ok');
    if (isset(getallheaders()['X-Retry-Counter']) ||
        $data['v'] !== CFG_ENGINE['v'])
        exit();
}

require('libs/ClassAPIExtension.php');
require('utils.php');
require('vk.php');
require('module.php');
require('config.php');
require('database.php');

$vk = new VK();
($m = new Module())->setVK($vk);
$db = new Database(CFG_ENGINE['db']['host'], CFG_ENGINE['db']['database'], CFG_ENGINE['db']['user'], CFG_ENGINE['db']['pass'],
    CFG_ENGINE['db']['port'], CFG_ENGINE['db']['charset']);

$vk->reply = CFG_ENGINE['reply'];
$vk->typing = CFG_ENGINE['typing']['enable'];
$m->lang('engine');

function engine_analysis(VK $vk, float $reqestTime): void
{
    $vk->query('messages.edit', [
        'conversation_message_id' => $vk->send($send = sprintf(LANG_ENGINE[0], $reqestTime = bcsub($time = microtime(true), $reqestTime, 3)))
            [0]['response'][0]['conversation_message_id'],
        'peer_id' => $vk->obj['peer_id'],
        'message' => $send.PHP_EOL.sprintf(LANG_ENGINE[1],
            $time = bcsub(microtime(true), $time, 3),
            bcadd($reqestTime, $time, 3)),
        'keep_forward_messages' => 1
    ]);
}

if ($data['type'] === 'message_new' || $data['type'] === 'message_reply')
{
    $vk->obj = $data['type'] === 'message_new' ? $data['object']['message'] : $data['object'];

    if (isset($vk->obj['payload']) && $res = Utils::getPayload($vk->obj['payload']))
        $vk->obj = $res + $vk->obj;
    
    $vk->setMembers($vk->obj['peer_id']);
    $db->regChat($vk->obj['peer_id']);
    
    if (empty($vk->obj['text']) || !Utils::isUser($vk->obj['from_id']))
        goto skip_engine_cmd;

    while (strpos($vk->obj['text'], '  ') !== false)
        $vk->obj['text'] = strtr($vk->obj['text'], ['  ' => ' ']);
    
    if (!$m->isCmd($vk->obj['text']))
    {
        $m->setParams($res = ' '.$vk->obj['text']);
        $m->setTargets($res);
        $m->setParamStrings($res);

        goto skip_engine_cmd;
    }

    $m->setParams($res = substr($vk->obj['text'], $len = $len + ($vk->obj['text'][$len = strlen(CFG_ENGINE['prefix'])] === ' ' ? 1 : 0)));
    $m->setTargets($res);
    $m->setParamStrings($res);

    $m->regCmd(['bot', LANG_ENGINE[30]], LANG_ENGINE[21], [
        [
            'names' => [
                'analysis',
                LANG_ENGINE[31]
            ],
            'params' => LANG_ENGINE[22],
            'description' => LANG_ENGINE[23]
        ],
        [
            'names' => [
                'peerid',
                LANG_ENGINE[32]
            ],
            'params' => LANG_ENGINE[36],
            'description' => LANG_ENGINE[24]
        ],
        [
            'names' => [
                'info',
                LANG_ENGINE[33]
            ],
            'description' => LANG_ENGINE[25]
        ],
        [
            'names' => [
                'modules',
                LANG_ENGINE[34]
            ],
            'params' => LANG_ENGINE[26],
            'description' => LANG_ENGINE[27]
        ],
        [
            'names' => [
                'commands',
                LANG_ENGINE[35]
            ],
            'description' => LANG_ENGINE[28]
        ]
    ]);
    
    if ($m->cmd('bot'))
    {
        if ($m->param(1, ['analysis', LANG_ENGINE[31]]))
        {
            $m->setParams($res = substr($res, strlen($m->param(0)) + strlen($m->param(1)) + 2));
            $m->setParamStrings($res);
            $vk->setAnalysis();
            register_shutdown_function('engine_analysis', $vk, $_SERVER['REQUEST_TIME_FLOAT']);
        }
        
        if (!$m->cmd('bot'))
            goto skip_engine_cmd;
        
        if ($m->param(1, ['analysis', LANG_ENGINE[31]]))
        {
            $vk->send(LANG_ENGINE[13]);
        }
        else if ($m->param(1, ['peerid', LANG_ENGINE[32]]))
        {
            $vk->send(($target = $m->getTarget(1)) === false ? $vk->obj['peer_id'] : $target);
        }
        else if ($m->param(1, ['info', LANG_ENGINE[33]]))
        {
            $vk->send(LANG_ENGINE[2]);
        }
        else if ($m->param(1, ['modules', LANG_ENGINE[34]]))
        {
            if ($hndl = opendir('modules'))
            {
                $i = -1;
                $modules = [];
                while (($name = readdir($hndl)) !== false)
                {
                    if ($name !== '.' && $name !== '..' && $name !== '~callbacks')
                    {
                        require_once($path = $m->pathPreload($name));
                        
                        if (defined($res = strtoupper($name).'_INFO'))
                        {
                            if (!isset(($res = constant($res))['name']))
                                $res['name'] = $name;

                            $modules[++$i] = ['path' => substr($path, 0, -12)] + $res;
                        }
                        else
                        {
                            $modules[++$i] = ['name' => $name, 'path' => substr($path, 0, -12)];
                        }
                    }
                }
                closedir($hndl);

                if ($num = $m->param(2))
                {
                    if (!is_numeric($num) || !isset($modules[$num -= 1]))
                        $m->error(LANG_ENGINE[11], 2);
                    
                    $vk->send(sprintf(LANG_ENGINE[12],
                        $modules[$num]['name'],
                        $modules[$num]['description'] ?? '???',
                        $modules[$num]['version'] ?? '???',
                        $modules[$num]['author'] ?? '???',
                        $modules[$num]['url'] ?? '???',
                        $modules[$num]['path']));
                }
                else
                {
                    $send = sprintf(LANG_ENGINE[4], count($modules));
                    $i = -1;
                    while (isset($modules[++$i]))
                        $send .= PHP_EOL.sprintf(LANG_ENGINE[5],
                            $i + 1,
                            $modules[$i]['name'],
                            $modules[$i]['version'] ?? '???',
                            $modules[$i]['author'] ?? '???');
                    $vk->replyPM($i ? $send : LANG_ENGINE[10], CFG_ENGINE['bot']);
                }
            }
            else
                $vk->send(LANG_ENGINE[10]);
        }
        else if ($m->param(1, ['commands', LANG_ENGINE[35]]))
        {
            if ($hndl = opendir('modules'))
            {
                while (($name = readdir($hndl)) !== false)
                    if ($name !== '.' && $name !== '..' && $name !== '~callbacks')
                        require_once($m->pathPreload($name));
                closedir($hndl);
            }

            $send = sprintf(LANG_ENGINE[20], count($buffer = $m->getCmds()));
            foreach (array_keys($buffer) as $cmd)
                $send .= PHP_EOL.PHP_EOL.$m->aboutCmd($cmd);
            $vk->replyPM($send, CFG_ENGINE['bot']);
        }
        else
        {
            $vk->send(LANG_ENGINE[3].$m->aboutCmd('bot'));
        }

        exit();
    }
skip_engine_cmd:
}


if ($hndl = opendir('modules'))
{
    $exec = [];
    while (($name = readdir($hndl)) !== false)
    {
        if ($name !== '.' && $name !== '..' && $name !== '~callbacks')
        {
            $exec[] = $name;
            require_once($m->pathPreload($name));
        }
    }
    
    $m->blockCmd();
    foreach ($exec as $name)
        if (function_exists($name = 'preloadEnd_'.$name))
            $name();

    rewinddir($hndl);
    while (($name = readdir($hndl)) !== false)
        if ($name !== '.' && $name !== '..' && $name !== '~callbacks' && file_exists($path = 'modules/'.$name.'/'.$data['type'].'.php'))
            require($path);

    closedir($hndl);
}