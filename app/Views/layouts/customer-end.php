        </div>
    </div>
</div>
<?php
$bottomNavClass='customer-bottom-actions';
$bottomNavLabel='Customer mobile navigation';
$bottomNavActive=$activeSection;
$bottomNavItems=['home'=>['Home','/'],'search'=>['Search flights','/flights'],'bookings'=>['My bookings','/account?section=bookings'],'tickets'=>['E-tickets','/account?section=tickets'],'profile'=>['Profile / Account','/account?section=profile']];
require BASE_PATH.'/app/Views/partials/mobile-navigation.php';
?>
<script defer src="/assets/js/customer-navigation.js"></script>
<?php require BASE_PATH.'/app/Views/layouts/footer.php'; ?>
