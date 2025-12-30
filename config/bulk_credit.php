<?php

return [
    'fail_user_ids' => array_filter(
        explode(',', env('BULK_CREDIT_FAIL_USER_IDS', ''))
    ),
];
