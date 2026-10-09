<?php
declare(strict_types=1);
require BASE_PATH.'/app/Views/partials/menu-icons.php';
$panelLayout=true;
require BASE_PATH.'/app/Views/layouts/header.php';
?>
<link rel="stylesheet" href="/assets/css/admin.css">
<div class="admin-shell<?= !empty($adminHeaderStandalone)?' admin-shell-standalone':' admin-section-'.$e($section) ?>">
<?php if(empty($adminHeaderStandalone)): ?>
<aside class="admin-sidebar" id="admin-sidebar"><a class="admin-brand" href="/admin"><span class="sidebar-logo"><img src="/assets/images/aerobook-logo.png" alt="" width="1254" height="1254"></span><span class="sidebar-brand-copy"><span>AeroBook</span></span></a><nav aria-label="Admin sections">
<?php foreach($nav as $key=>$label): ?><a class="<?= $section===$key?'active':'' ?>" href="<?= $path($key) ?>"><span class="sidebar-menu-label"><?= $menuIcon($key) ?><span><?= $e($label) ?></span></span><?php if($key==='payments'&&!empty($stats['pending_payments'])): ?><b><?= (int)$stats['pending_payments'] ?></b><?php endif ?></a><?php endforeach ?>
</nav><div class="admin-user"><span><?= $e($adminName) ?></span><form method="post" action="/admin/logout"><input type="hidden" name="_csrf" value="<?= $e($csrf) ?>"><button type="submit"><?= $menuIcon('logout') ?><span>Sign out</span></button></form></div></aside>
<?php endif ?>
<?php require BASE_PATH.'/app/Views/layouts/admin-header.php'; ?>
<link rel="stylesheet" href="/assets/css/admin-layout.css">
<main class="admin-main">
<div class="admin-content">
<?php require BASE_PATH.'/app/Views/partials/back-button.php'; ?>
