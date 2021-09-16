<?php

/**
 * 關鍵字
 */
return [
    'silent_on' => env('LINE_BOT_SILENT_ON', 'silent'),
    'silent_off' => env('LINE_BOT_SILENT_OFF', 'speak'),
    'update_group' => env('LINE_BOT_UPDATE_GROUP_INFO', 'update group'),
    'claim_group_admin' => env('LINE_BOT_CLAIM_GROUP_ADMIN', 'you are mine!'),
    'claim_group_sidekick' => env('LINE_BOT_CLAIM_GROUP_SIDEKICK', 'my lord'),
    'able_apply_group_sidekick' => env('LINE_BOT_APPLY_GROUP_SIDEKICK', 'kneel down'),
    'review_group_sidekick' => env('LINE_BOT_REVIEW_GROUP_SIDEKICK', 'my sidekick'),
    'operation_list' => env('LINE_BOT_OPERATION_LIST', 'list!'),
];
