</div>
</main>
</div>
<?php
if(empty($adminHeaderStandalone)){
    $bottomNavClass='admin-bottom-actions';
    $bottomNavLabel='Admin mobile navigation';
    $bottomNavActive=$section;
    $bottomNavItems=['dashboard'=>['Dashboard','/admin'],'flights'=>['Flights','/admin/flights'],'bookings'=>['Bookings','/admin/bookings'],'payments'=>['Payments','/admin/payments']];
    require BASE_PATH.'/app/Views/partials/mobile-navigation.php';
}
?>
