<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mileage rates
    |--------------------------------------------------------------------------
    |
    | Pence per mile. The first `threshold_miles` in each `period` are costed at
    | `rate_pence`, anything beyond that at `rate_pence_after`. Historic reports
    | use the current values.
    |
    */

    'mileage' => [
        'uc' => [
            'period' => 'uc_assessment',
            'threshold_miles' => 833,
            'rate_pence' => 45,
            'rate_pence_after' => 25,
        ],
        'hmrc' => [
            'period' => 'tax_year',
            'threshold_miles' => 10000,
            'rate_pence' => 55,
            'rate_pence_after' => 25,
        ],
    ],

];
