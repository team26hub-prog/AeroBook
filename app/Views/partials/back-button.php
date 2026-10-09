<?php
declare(strict_types=1);
$backPath=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
if(rtrim($backPath,'/')!==''):
    $backFallback=\App\Core\Auth::role()==='admin'?'/admin':(\App\Core\Auth::check()?'/account':'/');
    if($backPath===$backFallback)$backFallback=\App\Core\Auth::role()==='admin'?'/':'/flights';
?>
<div class="page-back-row">
    <a class="page-back-button" data-page-back href="<?= htmlspecialchars($backFallback,ENT_QUOTES,'UTF-8') ?>" aria-label="Back to previous page">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7M5 12h14"/></svg>
        <span>Back</span>
    </a>
</div>
<?php endif ?>
