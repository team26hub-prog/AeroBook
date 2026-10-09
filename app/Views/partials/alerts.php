<?php $flightAlert=\App\Core\Session::pullFlash('flight_alert'); ?>
<?php if(is_array($flightAlert)): ?>
    <div class="alert alert-warning" role="alert" data-popup-title="<?= htmlspecialchars((string)$flightAlert['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><strong><?= htmlspecialchars((string)$flightAlert['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><p><?= htmlspecialchars((string)$flightAlert['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p></div>
<?php endif ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialchars((string) $success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
