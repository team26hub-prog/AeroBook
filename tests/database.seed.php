<?php
declare(strict_types=1);
require __DIR__.'/support/Harness.php';
ob_start();$environment=new TestEnvironment();$db=$environment->db;$suite=new Suite();
$suite->add('database: fresh seed creates 115 flights with 60 seats each and valid routes',function()use($environment,$db){
    $environment->clear();$db->exec(file_get_contents(BASE_PATH.'/database/seed.sql'));
    equal((int)$environment->scalar('SELECT COUNT(*) FROM flights'),115);
    equal((int)$environment->scalar('SELECT COUNT(*) FROM seats'),6900);
    equal((int)$environment->scalar('SELECT COUNT(*) FROM (SELECT flight_id FROM seats GROUP BY flight_id HAVING COUNT(*)<>60 OR SUM(cabin_class="business")<>12 OR SUM(cabin_class="economy")<>48) bad'),0);
    equal((int)$environment->scalar('SELECT COUNT(*) FROM flights WHERE departure_at>=arrival_at OR departure_airport_id=arrival_airport_id'),0);
});
$suite->add('regression/database: import is repeatable and preserves existing bookings, reservations and blocked seats',function()use($environment,$db){
    $environment->clear();$db->exec(file_get_contents(BASE_PATH.'/database/seed.sql'));
    $db->prepare('INSERT INTO users(id,full_name,email,password_hash) VALUES(1,?,?,?)')->execute(['Seed Test','seed@example.test',password_hash('Password123!',PASSWORD_DEFAULT)]);
    $flight=(int)$environment->scalar("SELECT id FROM flights WHERE flight_number='AB401' ORDER BY departure_at LIMIT 1");
    // Historical reservations must survive imports too; this fixture stays valid after October.
    $db->prepare('INSERT INTO bookings(user_id,flight_id,pnr,total_amount,currency,status) VALUES(1,?,?,18500.00,"PKR","pending")')->execute([$flight,'IMPORTTEST']);
    $booking=['id'=>(int)$db->lastInsertId(),'pnr'=>'IMPORTTEST'];
    $db->prepare('INSERT INTO passengers(booking_id,first_name,last_name,date_of_birth,gender) VALUES(?,"Seed","Passenger","1995-03-12","unspecified")')->execute([$booking['id']]);
    $seat=(int)$environment->scalar('SELECT MIN(id) FROM seats WHERE flight_id=?',[$flight]);$environment->assign($booking,1,[$seat]);
    $db->exec('UPDATE seats SET status="blocked" WHERE id='.$seat);
    $missing=(int)$environment->scalar('SELECT MAX(id) FROM seats');$db->exec('DELETE FROM seats WHERE id='.$missing);
    for($i=0;$i<2;$i++)$db->exec(file_get_contents(BASE_PATH.'/database/import_additional_flights.sql'));
    equal((int)$environment->scalar('SELECT COUNT(*) FROM flights'),115);equal((int)$environment->scalar('SELECT COUNT(*) FROM seats'),6900);
    equal((int)$environment->scalar('SELECT COUNT(*) FROM bookings'),1);equal((int)$environment->scalar('SELECT COUNT(*) FROM booking_seats'),1);
    equal($environment->scalar('SELECT status FROM seats WHERE id=?',[$seat]),'blocked');equal($environment->scalar('SELECT pnr FROM bookings'),$booking['pnr']);
});
$suite->add('database: additional import into base routes inserts exactly 111 October flights',function()use($environment,$db){
    $environment->clear();$db->exec("INSERT INTO airlines(id,name,iata_code) VALUES(1,'AeroBook','AB'); INSERT INTO airports(name,iata_code,city,country,timezone) VALUES('Karachi','KHI','Karachi','PK','Asia/Karachi'),('Lahore','LHE','Lahore','PK','Asia/Karachi'),('Islamabad','ISB','Islamabad','PK','Asia/Karachi'),('Dubai','DXB','Dubai','AE','Asia/Dubai')");
    $db->exec(file_get_contents(BASE_PATH.'/database/import_additional_flights.sql'));equal((int)$environment->scalar('SELECT COUNT(*) FROM flights'),111);equal((int)$environment->scalar('SELECT COUNT(*) FROM seats'),6660);
    equal((int)$environment->scalar("SELECT COUNT(*) FROM flights WHERE departure_at<'2026-10-15' OR departure_at>='2026-10-25'"),0);
});
$suite->add('integration: empty models and charts return usable empty states',function()use($environment,$db){$environment->clear();equal((new App\Models\FlightSearch($db))->allAvailable(),[]);equal((new App\Models\SeatSelection($db))->pendingBookings(1),[]);equal((new App\Models\ETicket($db))->forCustomer(1),[]);$model=new App\Models\AdminModel($db);foreach(['airlines','airports','flights','seats','bookings','payments'] as $section)equal($model->all($section),[]);equal((float)$model->dashboard()['revenue'],0.0);$chart=(new App\Models\AdminDashboard($db))->data();equal(array_sum($chart['bookingStatuses']['values']),0);equal(array_sum($chart['paymentStatuses']['values']),0);});
try{$failures=$suite->run();}finally{session_write_close();$environment->cleanup();}echo ob_get_clean();exit($failures?1:0);
