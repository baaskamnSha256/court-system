<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Path patterns to skip (Laravel Str::is)
    |--------------------------------------------------------------------------
    */
    'ignore_path_patterns' => [
        'livewire/*',
        '_boost/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route names to skip for HTTP / file audit (e.g. handled elsewhere)
    |--------------------------------------------------------------------------
    */
    'ignore_route_names' => [
        'logout',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP audit: skip routes that already emit dedicated domain logs
    |--------------------------------------------------------------------------
    |
    | @var array<int, string>
    */
    'http_audit_excluded_route_names' => [
        'admin.hearings.store',
        'admin.hearings.update',
        'admin.hearings.destroy',
        'secretary.hearings.store',
        'secretary.hearings.update',
        'secretary.hearings.destroy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Файл таталтын маршрут → хэрэглэгчид ойлгомжтой нэр
    |--------------------------------------------------------------------------
    |
    | @var array<string, string>
    */
    'download_route_labels' => [
        'admin.hearings.print.download' => 'хурлын зар',
        'info_desk.hearings.print.download' => 'хурлын зар',
        'admin.reports.download' => 'тэмдэглэл хүлээлцэх тайлан',
        'admin.reports.download.defendant-details' => 'тэмдэглэл хүлээлцэх — батлан даагчийн дэлгэрэнгүй',
        'court_clerk.reports.download' => 'тэмдэглэл хүлээлцэх тайлан (нарийн бичгийн дарга)',
        'admin.textmask.download' => 'текст нууцлах — үр дүн',
        'secretary.textmask.download' => 'текст нууцлах — үр дүн',
    ],
];
