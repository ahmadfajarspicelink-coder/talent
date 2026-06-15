<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modul yang sedang tidak tersedia
    |--------------------------------------------------------------------------
    |
    | Daftar nama modul yang sedang "down"/tidak tersedia. Ketika sebuah modul
    | tercantum di sini, middleware EnsureModuleAccess akan menolak akses ke
    | modul tersebut dengan HTTP 503 + pesan "layanan tidak tersedia" (R2.6),
    | bahkan jika role pengguna sebenarnya diizinkan mengaksesnya.
    |
    | Nilai modul yang valid: partner, order, client, finance, user_management.
    |
    | Mekanisme ini memudahkan simulasi modul down pada feature test (task 8.3),
    | mis. dengan `config(['modules.unavailable' => ['order']])`.
    |
    */

    'unavailable' => array_filter(
        array_map('trim', explode(',', (string) env('MODULES_UNAVAILABLE', '')))
    ),

];
