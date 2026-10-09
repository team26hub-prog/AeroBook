<?php
declare(strict_types=1);
// Decorative icons use the link's text as its accessible name.
$menuIcon=static function(string $key):string{
    $paths=[
        'trends'=>'<path d="M3 3v18h18M6 15l5-5 4 3 6-8"/>',
        'bars'=>'<path d="M3 3v18h18M7 17v-5m5 5V7m5 10V4"/>',
        'distribution'=>'<path d="M12 3v9h9A9 9 0 0 0 12 3Z"/><path d="M8 3.9A9 9 0 1 0 20.1 16H8Z"/>',
        'home'=>'<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
        'search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'dashboard'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'bookings'=>'<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4m8-4v4M4 11h16m-12 4h3m-3 3h7"/>',
        'passengers'=>'<circle cx="9" cy="8" r="3"/><path d="M3 21v-2a6 6 0 0 1 12 0v2m1-16a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v2"/>',
        'seats'=>'<path d="M7 4v9h10V4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2Zm-3 7v6h16v-6M7 17v4m10-4v4M7 13v4m10-4v4"/>',
        'payments'=>'<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
        'tickets'=>'<path d="M3 5h18v5a2 2 0 0 0 0 4v5H3v-5a2 2 0 0 0 0-4Zm12 0v2m0 3v2m0 3v4"/>',
        'profile'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'flights'=>'<path d="m22 2-7 20-4-9-9-4Zm0 0L11 13"/>',
        'airlines'=>'<path d="M4 21V5h10v16M2 21h20M8 9h2m-2 4h2m-2 4h2m4-8h6v12m-3-8h1m-1 4h1"/>',
        'airports'=>'<path d="M3 21h18M6 21v-8h12v8M4 9h16l-2 4H6ZM12 9V3m-3 2h6M10 21v-4h4v4"/>',
        'login'=>'<path d="M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5M3 12h12m-4-4 4 4-4 4"/>',
        'register'=>'<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0m3-13v6m-3-3h6"/>',
        'logout'=>'<path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5m0-9h11m-4-4 4 4-4 4"/>',
    ];
    return '<svg class="sidebar-menu-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$key]??$paths['dashboard']).'</svg>';
};
