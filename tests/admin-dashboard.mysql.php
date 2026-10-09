<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__));
spl_autoload_register(static function(string $class):void{
    if(str_starts_with($class,'App\\'))require BASE_PATH.'/app/'.str_replace('\\','/',substr($class,4)).'.php';
});
function check(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$env=[];
foreach(file(BASE_PATH.'/.env',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){
    $line=trim($line);if($line===''||str_starts_with($line,'#')||!str_contains($line,'='))continue;
    [$key,$value]=explode('=',$line,2);$env[trim($key)]=trim(trim($value),"\"'");
}
date_default_timezone_set($env['APP_TIMEZONE']??'Asia/Karachi');
$db=new PDO('mysql:host='.($env['DB_HOST']??'127.0.0.1').';port='.($env['DB_PORT']??3306).';dbname='.($env['DB_DATABASE']??'aerobook').';charset=utf8mb4',$env['DB_USERNAME']??'',$env['DB_PASSWORD']??'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$db->exec('SET time_zone='.$db->quote((new DateTimeImmutable())->format('P')));
$model=new App\Models\AdminDashboard($db);
$live=$model->data();
check(array_sum($live['bookingStatuses']['values'])===(int)$db->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),'Live booking totals disagree');
check(array_sum($live['paymentStatuses']['values'])===(int)$db->query('SELECT COUNT(*) FROM payments')->fetchColumn(),'Live payment totals disagree');
echo "PASS: live MySQL chart queries and status totals\n";
// These session-local tables shadow the real tables and disappear when this connection closes.
foreach([
    'bookings'=>'id INT PRIMARY KEY,flight_id INT,status VARCHAR(20),booked_at DATETIME',
    'payments'=>'id INT PRIMARY KEY,booking_id INT,status VARCHAR(20),currency CHAR(3),amount DECIMAL(10,2),paid_at DATETIME NULL,created_at DATETIME',
    'flights'=>'id INT PRIMARY KEY,departure_airport_id INT,arrival_airport_id INT',
    'passengers'=>'id INT PRIMARY KEY,booking_id INT',
] as $table=>$columns)$db->exec("CREATE TEMPORARY TABLE {$table} ({$columns})");
$empty=$model->data();
$chartStart=new DateTimeImmutable('2026-10-08');
$chartToday=new DateTimeImmutable('today');
$chartDays=$chartToday<$chartStart?0:(int)$chartStart->diff($chartToday)->days+1;
check(count($empty['trends']['daily']['values'])===$chartDays,'Daily bucket length');
check(count($empty['trends']['weekly']['values'])===(int)ceil($chartDays/7),'Weekly bucket length');
if($chartDays>0)check($empty['trends']['daily']['labels'][0]==='08 Oct 2026'&&$empty['trends']['weekly']['labels'][0]==='08 Oct 2026'&&$empty['trends']['monthly']['labels'][0]==='Oct 2026','Charts must start on 8 October');
check(array_sum($empty['revenue']['values'])===0.0&&array_sum($empty['bookingStatuses']['values'])===0,'Empty data not zero-filled');
echo "PASS: empty database and zero-filled calendar periods\n";
$airportIds=array_column($db->query("SELECT id,iata_code FROM airports WHERE iata_code IN ('KHI','LHE','ISB')")->fetchAll(),'id','iata_code');
check(count($airportIds)===3,'Fixture needs existing KHI, LHE, and ISB airports');
$flightInsert=$db->prepare('INSERT INTO flights VALUES(?,?,?)');
foreach([[1,'KHI','LHE'],[2,'KHI','LHE'],[3,'LHE','ISB']] as [$id,$departure,$arrival])$flightInsert->execute([$id,$airportIds[$departure],$airportIds[$arrival]]);
$today=new DateTimeImmutable('today');
$q=$db->prepare('INSERT INTO bookings VALUES(?,?,?,?)');
$fixtures=[
    [1,1,'confirmed',$today],[2,2,'confirmed',$today],[3,1,'pending',$today->modify('-1 day')],
    [4,1,'cancelled',new DateTimeImmutable('2026-10-07')],
    [5,3,'confirmed',$today],[6,1,'confirmed',$today->modify('-40 days')],[7,3,'completed',$today],
    [8,1,'expired',$today->modify('+1 day')],[9,2,'confirmed',new DateTimeImmutable('2026-10-08')],
];
foreach($fixtures as [$id,$flight,$state,$date])$q->execute([$id,$flight,$state,$date->format('Y-m-d H:i:s')]);
$db->exec('INSERT INTO passengers VALUES(1,1),(2,1),(3,1),(4,2),(5,2)');
$q=$db->prepare('INSERT INTO payments VALUES(?,?,?,?,?,?,?)');
$now=$today->format('Y-m-d H:i:s');
foreach([
    [1,1,'verified','PKR','100.25',$now,$now],
    [2,1,'verified','PKR','200.00',null,$today->modify('-1 day')->format('Y-m-d H:i:s')],
    [3,2,'verified','USD','99999.00',$now,$now],
    [4,2,'pending','PKR','500.00',$now,$now],
    [5,2,'rejected','PKR','100.00',$now,$now],
    [6,2,'verified','PKR','1000.00',$today->modify('-13 months')->format('Y-m-d H:i:s'),$now],
] as $payment)$q->execute($payment);
$data=$model->data();
$expected=count(array_filter($fixtures,static fn($booking)=>$booking[3]>=$chartStart&&$booking[3]<$today->modify('+1 day')));
check(array_sum($data['trends']['daily']['values'])===$expected,'Daily count includes dates before 8 October, future dates, or duplicates');
check(array_sum($data['trends']['weekly']['values'])===$expected&&array_sum($data['trends']['monthly']['values'])===$expected,'Calendar aggregation mismatch');
check(abs(array_sum($data['revenue']['values'])-300.25)<0.001,'Revenue currency, status, date, or duplicate aggregation is wrong');
check(array_sum($data['bookingStatuses']['values'])===9&&array_sum($data['paymentStatuses']['values'])===6,'Status totals inflated');
echo "PASS: calendar boundaries, future-date exclusion, PKR-only verified revenue, and totals with multiple passengers/payments\n";
