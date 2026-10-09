<?php
declare(strict_types=1);
$bottomEscape=static fn($value)=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<nav class="mobile-bottom-nav <?= $bottomEscape($bottomNavClass) ?>" aria-label="<?= $bottomEscape($bottomNavLabel) ?>">
    <?php foreach($bottomNavItems as $key=>[$label,$url]): ?>
    <a href="<?= $bottomEscape($url) ?>" aria-label="<?= $bottomEscape($label) ?>" title="<?= $bottomEscape($label) ?>"<?= $bottomNavActive===$key?' aria-current="page"':'' ?>><?= $menuIcon($key) ?></a>
    <?php endforeach ?>
</nav>
