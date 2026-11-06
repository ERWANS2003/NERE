<?php

return [
    // Module paie masqué partout (menus, routes, Admin > Modules) tant que false.
    'available' => (bool) env('PAYROLL_AVAILABLE', false),
];
