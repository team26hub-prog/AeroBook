<?php
$appName = $GLOBALS['config']['app']['name'] ?? 'AeroBook';
$title = isset($title) ? $title . ' | ' . $appName : $appName;
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#183251">
    <title><?= $escape($title) ?></title>
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/branding.css">
    <link rel="stylesheet" href="/assets/css/mobile-navigation.css">
    <link rel="stylesheet" href="/assets/css/back-navigation.css">
    <script defer src="/assets/js/back-navigation.js"></script>
</head>
<body data-back-role="<?= $escape(\App\Core\Auth::role()??'guest') ?>" data-back-user="<?= $escape(\App\Core\Session::get('user_id',0)) ?>" data-back-booking-active="<?= (int)\App\Core\Session::get('selected_flight_id',0)>0?'1':'0' ?>" data-back-method="<?= $escape($_SERVER['REQUEST_METHOD']??'GET') ?>">
<header class="site-header<?= !empty($authPage)?' auth-topbar':'' ?>">
    <a class="brand" href="/" aria-label="AeroBook home"><?php if(!empty($authPage)): ?><span class="sidebar-logo auth-logo"><img src="/assets/images/aerobook-logo.png" alt="" width="1254" height="1254"></span><?php endif ?>AeroBook</a>
    <span class="header-note">Your journey starts here</span>
</header>
<?php if(!empty($panelLayout)): ?><div class="page-main"><?php else: ?><main class="page-main page-main-with-back"><?php endif ?>
<?php if(empty($panelLayout))require BASE_PATH.'/app/Views/partials/back-button.php'; ?>
