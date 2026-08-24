<?php

return [
    'trial_days' => env('SUBSCRIPTION_TRIAL_DAYS', 7),
    'grace_days' => env('SUBSCRIPTION_GRACE_DAYS', 5),
    'default_plan_code' => 'essentiel',
];
