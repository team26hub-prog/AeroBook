<?php
declare(strict_types=1);
$customerEscape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$customerNav=['home'=>'Home','search'=>'Search Flights','bookings'=>'My Bookings','passengers'=>'Passenger Details','seats'=>'Seat Selection','payments'=>'Payments','tickets'=>'E-Tickets','profile'=>'Profile / Account'];
require BASE_PATH.'/app/Views/layouts/header.php';
?>
<link rel="stylesheet" href="/assets/css/customer.css">
<div class="customer-app">
    <header class="customer-topbar">
        <button class="customer-menu-toggle" type="button" aria-expanded="false" aria-controls="customer-sidebar" aria-label="Open customer navigation"><span aria-hidden="true"><i></i><i></i><i></i></span></button>
        <a class="customer-top-brand" href="/account">AeroBook</a>
        <div class="customer-top-title"><span>Customer area</span><strong><?= $customerEscape($title) ?></strong></div>
        <span class="customer-greeting">Hi, <?= $customerEscape($userName) ?></span>
    </header>
    <button class="customer-backdrop" type="button" aria-label="Close customer navigation" tabindex="-1"></button>
    <div class="customer-layout">
        <aside class="customer-sidebar" id="customer-sidebar" aria-label="Customer navigation">
            <a class="customer-brand" href="/account">AeroBook <small>TRAVEL</small></a>
            <button class="customer-sidebar-close" type="button" aria-label="Close customer navigation">Close</button>
            <nav>
                <?php foreach($customerNav as $key=>$label): ?>
                    <a class="<?= $activeSection===$key?'active':'' ?>" href="<?= $key==='home'?'/account':($key==='search'?'/flights':'/account?section='.$key) ?>"><span><?= $customerEscape($label) ?></span><?php if($key==='search'): ?><b>Find a trip</b><?php endif ?></a>
                <?php endforeach ?>
            </nav>
            <form class="customer-signout" method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= $customerEscape($csrf) ?>"><button type="submit">Sign out</button></form>
        </aside>
        <div class="customer-content" role="main">
