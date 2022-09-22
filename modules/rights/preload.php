<?php

const RIGHTS_V_MAJOR = 1;
const RIGHTS_V_MINOR = 0;
const RIGHTS_V_RELEASE = 0;
const RIGHTS_VERSION = RIGHTS_V_MAJOR.'.'.RIGHTS_V_MINOR.'.'.RIGHTS_V_RELEASE;

require('rights.php');

$rights = new Rights($db, $vk->obj['peer_id'], $vk);

$m->lang('rights');
$rights->regRight('root', LANG_RIGHTS[8]);

$m->regCmd(['rights', LANG_RIGHTS[18]], LANG_RIGHTS[16], [
    [
        'names' => [
            'set',
            LANG_RIGHTS[19]
        ],
        'params' => LANG_RIGHTS[7],
        'description' => LANG_RIGHTS[12]
    ],
    [
        'names' => [
            'info',
            LANG_RIGHTS[20]
        ],
        'params' => LANG_RIGHTS[13],
        'description' => LANG_RIGHTS[14]
    ],
    [
        'names' => [
            'list',
            LANG_RIGHTS[21]
        ],
        'description' => LANG_RIGHTS[15]
    ]
]);

function preloadEnd_rights()
{
    global $rights;
    $rights->blockRights();
}

return [
    'name' => 'Rights',
    'description' => LANG_RIGHTS[17],
    'version' => RIGHTS_VERSION,
    'author' => 'DeathScore13',
    'url' => '*link*'
];