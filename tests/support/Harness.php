<?php
declare(strict_types=1);
if(!defined('BASE_PATH'))define('BASE_PATH',dirname(__DIR__,2));
spl_autoload_register(static function(string $class):void{
    if(str_starts_with($class,'App\\'))require BASE_PATH.'/app/'.str_replace('\\','/',substr($class,4)).'.php';
});
function expect(bool $condition,string $message='Assertion failed'):void{if(!$condition)throw new RuntimeException($message);}
function equal(mixed $actual,mixed $expected):void{expect($actual===$expected,'Expected '.json_encode($expected).', received '.json_encode($actual));}
function rejects(callable $action,string $class=RuntimeException::class):Throwable{
    try{$action();}catch(Throwable $error){expect($error instanceof $class,'Unexpected exception: '.get_class($error));return $error;}
    throw new RuntimeException('Expected operation to be rejected');
}
final class Suite{
    private array $tests=[];
    public function add(string $name,callable $test):void{$this->tests[$name]=$test;}
    public function run(?callable $before=null):int{
        $failed=0;foreach($this->tests as $name=>$test){try{if($before)$before();$test();echo "PASS: {$name}\n";}catch(Throwable $e){$failed++;echo "FAIL: {$name}: {$e->getMessage()}\n";}}
        echo count($this->tests)." tests, {$failed} failures\n";return $failed;
    }
}
final class TestEnvironment{
    public PDO $db;
    public PDO $server;
    public string $name;
    public string $runtime;
    public array $env=[];
    private bool $created=false;
    public function __construct(){
        foreach(file(BASE_PATH.'/.env',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){
            $line=trim($line);if($line===''||str_starts_with($line,'#')||!str_contains($line,'='))continue;
            [$key,$value]=explode('=',$line,2);$this->env[trim($key)]=trim(trim($value),"\"'");
        }
        date_default_timezone_set($this->env['APP_TIMEZONE']??'Asia/Karachi');
        $this->name='aerobook_test_'.bin2hex(random_bytes(6));
        $this->runtime=BASE_PATH.'/tests/.runtime/'.$this->name;
        if(!is_dir($this->runtime)&&!mkdir($this->runtime,0700,true))throw new RuntimeException('Cannot create test runtime');
        $dsn='mysql:host='.($this->env['DB_HOST']??'127.0.0.1').';port='.($this->env['DB_PORT']??3306).';charset=utf8mb4';
        $options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
        $this->server=new PDO($dsn,$this->env['DB_USERNAME']??'',$this->env['DB_PASSWORD']??'',$options);
        $this->server->exec('CREATE DATABASE `'.$this->name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$this->created=true;
        register_shutdown_function(fn()=> $this->cleanup());
        $this->db=new PDO($dsn.';dbname='.$this->name,$this->env['DB_USERNAME']??'',$this->env['DB_PASSWORD']??'',$options);
        $this->db->exec('SET time_zone='.$this->db->quote((new DateTimeImmutable())->format('P')));
        $this->db->exec(file_get_contents(BASE_PATH.'/database/schema.sql'));
        $GLOBALS['config']=['app'=>['name'=>'AeroBook','environment'=>'testing','debug'=>false,'base_url'=>'http://127.0.0.1','timezone'=>date_default_timezone_get()]];
        (new ReflectionProperty(App\Core\Database::class,'connection'))->setValue(null,$this->db);
        if(session_status()!==PHP_SESSION_ACTIVE){ini_set('session.save_path',$this->runtime);App\Core\Session::start();}
    }
    public function clear():void{
        equal($this->db->query('SELECT DATABASE()')->fetchColumn(),$this->name);
        expect((bool)preg_match('/^aerobook_test_[a-f0-9]{12}$/D',$this->name),'Unsafe test database');
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
        try{foreach(['e_tickets','payments','booking_seats','passengers','bookings','seats','flights','airports','airlines','users'] as $table)$this->db->exec('TRUNCATE TABLE '.$table);}
        finally{$this->db->exec('SET FOREIGN_KEY_CHECKS=1');}
    }
    public function reset():void{
        $this->clear();$_SESSION=[];$_POST=[];$_GET=[];
        $hash=password_hash('Password123!',PASSWORD_DEFAULT);
        $q=$this->db->prepare('INSERT INTO users(id,full_name,email,password_hash,role,status) VALUES(?,?,?,?,?,?)');
        foreach([[1,'Customer One','one@example.test','customer','active'],[2,'Customer Two','two@example.test','customer','active'],[3,'Admin','admin@example.test','admin','active'],[4,'Suspended','suspended@example.test','customer','suspended']] as [$id,$name,$email,$role,$status])$q->execute([$id,$name,$email,$hash,$role,$status]);
        $this->db->exec("INSERT INTO airlines(id,name,iata_code,icao_code,status) VALUES(1,'AeroBook Air','AB','ABK','active')");
        $this->db->exec("INSERT INTO airports(id,name,iata_code,city,country,timezone) VALUES(1,'Karachi','KHI','Karachi','Pakistan','Asia/Karachi'),(2,'Lahore','LHE','Lahore','Pakistan','Asia/Karachi'),(3,'Islamabad','ISB','Islamabad','Pakistan','Asia/Karachi')");
        $q=$this->db->prepare('INSERT INTO flights(id,airline_id,flight_number,departure_airport_id,arrival_airport_id,departure_at,arrival_at,base_fare,currency,status) VALUES(?,1,?,1,2,?,?,?, ?,?)');
        $future=new DateTimeImmutable('+7 days');$past=new DateTimeImmutable('-7 days');
        foreach([[1,'AB100',$future,'10.25','PKR','scheduled'],[2,'AB200',$future,'20.00','USD','scheduled'],[3,'AB300',$past,'10.25','PKR','departed']] as [$id,$number,$date,$fare,$currency,$status])$q->execute([$id,$number,$date->format('Y-m-d H:i:s'),$date->modify('+2 hours')->format('Y-m-d H:i:s'),$fare,$currency,$status]);
        $q=$this->db->prepare('INSERT INTO seats(id,flight_id,seat_number,cabin_class,status) VALUES(?,?,?,"economy",?)');
        foreach([[1,1,'1A','available'],[2,1,'1B','available'],[3,1,'1C','available'],[4,1,'1D','available'],[5,1,'2A','available'],[6,1,'2B','available'],[7,1,'2C','blocked'],[8,1,'2D','unavailable'],[9,2,'1A','available']] as $seat)$q->execute($seat);
    }
    public function passenger(string $first='Test'):array{return ['first_name'=>$first,'last_name'=>'Passenger','date_of_birth'=>'1995-03-12','gender'=>'unspecified','passport_number'=>null,'passport_country'=>null];}
    public function booking(int $user=1,int $people=1,int $flight=1):array{
        return (new App\Models\Booking($this->db))->createPending($user,$flight,array_fill(0,$people,$this->passenger()),$flight===2?'20.00':'10.25',$flight===2?'USD':'PKR');
    }
    public function assign(array $booking,int $user=1,array $seats=[1]):array{
        $q=$this->db->prepare('SELECT id FROM passengers WHERE booking_id=? ORDER BY id');$q->execute([$booking['id']]);$ids=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
        $selection=array_combine($ids,$seats);(new App\Models\SeatSelection($this->db))->save($booking['id'],$user,$selection);return $selection;
    }
    public function payment(array $booking,string $reference='REF-001'):array{
        return ['method'=>'bank_transfer','sender_name'=>'Test Sender','transaction_reference'=>$reference,'paid_at'=>(new DateTimeImmutable())->format('Y-m-d\TH:i'),'amount'=>$booking['total_amount'],'remarks'=>'Integration test'];
    }
    public function submitted(array $booking,int $user=1):int{
        $reference='REF-'.$booking['id'].'-001';
        (new App\Models\ManualPayment($this->db))->submit($booking['id'],$user,$this->payment($booking,$reference));
        return (int)$this->scalar('SELECT id FROM payments WHERE transaction_reference=?',[$reference]);
    }
    public function scalar(string $sql,array $params=[]):mixed{$q=$this->db->prepare($sql);$q->execute($params);return $q->fetchColumn();}
    public function cleanup():void{
        if($this->created&&preg_match('/^aerobook_test_[a-f0-9]{12}$/D',$this->name)){$this->server->exec('DROP DATABASE `'.$this->name.'`');$this->created=false;}
    }
}
