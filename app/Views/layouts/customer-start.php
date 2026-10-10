<?php
declare(strict_types=1);
$customerEscape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$isAuthenticated=\App\Core\Auth::check();
$customerNav=['home'=>'Home','search'=>'Search Flights','bookings'=>'My Bookings','passengers'=>'Passenger Details','seats'=>'Seat Selection','payments'=>'Payments','tickets'=>'E-Tickets','profile'=>'Profile / Account'];
require BASE_PATH.'/app/Views/partials/menu-icons.php';
$panelLayout=true;
require BASE_PATH.'/app/Views/layouts/header.php';
?>
<link rel="stylesheet" href="/assets/css/customer.css">
<link rel="stylesheet" href="/assets/css/customer-layout.css">
<div class="customer-app">
    <header class="customer-topbar<?= $isAuthenticated?'':' customer-topbar-guest' ?>">
        <button class="customer-menu-toggle" type="button" aria-expanded="false" aria-controls="customer-sidebar" aria-label="Open customer navigation"><span aria-hidden="true"><i></i><i></i><i></i></span></button>
        <a class="mobile-navbar-logo" href="/" aria-label="AeroBook home"><img src="/assets/images/aerobook-logo.png?v=globe-transparent" alt="" width="1536" height="1024"></a>
        <div class="customer-top-title"><span><?= $isAuthenticated?'Your travel':'Welcome' ?></span><strong><?= $customerEscape($title) ?></strong></div>
        <?php if($isAuthenticated): ?>
        <span class="customer-greeting">Hi, <?= $customerEscape($userName) ?></span>
        <?php else: ?>
        <nav class="customer-auth-links" aria-label="Account access"><a href="/login">Login</a><a href="/register">Sign Up</a></nav>
        <?php endif ?>
    </header>
    <button class="customer-backdrop" type="button" aria-label="Close customer navigation" tabindex="-1"></button>
    <div class="customer-layout">
        <aside class="customer-sidebar" id="customer-sidebar" aria-label="Customer navigation">
            <a class="customer-brand" href="/"><span class="sidebar-logo"><img src="/assets/images/aerobook-logo.png?v=globe-transparent" alt="" width="1536" height="1024"></span><span class="sidebar-brand-copy"><span>AeroBook</span></span></a>
            <button class="customer-sidebar-close" type="button" aria-label="Close customer navigation">Close</button>
            <nav>
                <?php foreach($customerNav as $key=>$label): ?>
                    <a class="<?= $activeSection===$key?'active':'' ?>" href="<?= $key==='home'?'/':($key==='search'?'/flights':($key==='seats'?'/seat-selection':($key==='payments'?'/payments':'/account?section='.$key))) ?>"><span class="sidebar-menu-label"><?= $menuIcon($key) ?><span><?= $customerEscape($label) ?></span></span><?php if($key==='search'): ?><b>Find a trip</b><?php endif ?></a>
                <?php endforeach ?>
            </nav>
            <?php if($isAuthenticated): ?>
            <?php if(\App\Core\Auth::role()==='admin'): ?><nav aria-label="Administration"><a href="/admin"><span class="sidebar-menu-label"><?= $menuIcon('dashboard') ?><span>Admin panel</span></span></a></nav><?php endif ?>
            <div class="customer-sidebar-user"><span class="customer-sidebar-name" title="<?= $customerEscape($userName) ?>"><?= $customerEscape($userName) ?></span><form class="customer-signout" method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= $customerEscape($csrf) ?>"><button type="submit"><?= $menuIcon('logout') ?><span>Sign out</span></button></form></div>
            <?php else: ?>
            <nav class="customer-guest-access" aria-label="Sign in or create an account"><a href="/login">Login</a><a href="/register">Sign Up</a></nav>
            <?php endif ?>
        </aside>
        <div class="customer-content" role="main">
            <?php if($activeSection!=='home')require BASE_PATH.'/app/Views/partials/back-button.php'; ?>
