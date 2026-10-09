<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(static function (string $class): void {
    if(str_starts_with($class, 'App\\'))require BASE_PATH.'/app/'.str_replace('\\','/',substr($class,4)).'.php';
});
set_error_handler(static function (int $severity, string $message): never {
    throw new ErrorException($message, 0, $severity);
});
function check(bool $condition, string $message): void {
    if(!$condition)throw new RuntimeException($message);
}
final class FlightFixtureStatement extends PDOStatement
{
    public function __construct(private array|false $row) {}
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->row; }
}
final class FlightFixtureDatabase extends PDO
{
    public function __construct(private array|false $row, private bool $fail = false) {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if($this->fail)throw new PDOException('Test database unavailable');
        return new FlightFixtureStatement($this->row);
    }
}

$scenario=$argv[1]??'model';
if($scenario==='model'){
    foreach([0,1,5] as $count){
        $model=new App\Models\FlightSearch(new FlightFixtureDatabase(['id'=>42,'available_seats'=>$count]));
        check($model->findUpcoming(42)!==null,'Sold-out upcoming flight must be identifiable');
        check(($model->findAvailable(42)!==null)===($count>0),'Incorrect availability result');
    }
    $model=new App\Models\FlightSearch(new FlightFixtureDatabase(false));
    check($model->findUpcoming(42)===null&&$model->findAvailable(42)===null,'Missing flight accepted');
    $controller=new class extends App\Core\Controller {
        public function warn(int $seats): void { $this->flashSeatAvailabilityAlert($seats); }
    };
    $_SESSION=[];
    foreach([0,1] as $count){
        $controller->warn($count);
        ob_start();require BASE_PATH.'/app/Views/partials/alerts.php';$html=ob_get_clean();
        check(str_contains($html,'data-popup-title="'.($count?'Not enough seats available':'No seats available').'"'),'Warning markup missing');
        check(App\Core\Session::pullFlash('flight_alert')===null,'Warning not consumed');
    }
    echo "PASS: availability model and one-time warning rendering\n";
    exit;
}

$_SESSION=['_csrf_token'=>'test-token','selected_flight_id'=>0];
$_POST=['_csrf'=>'test-token','flight_id'=>'42'];
$row=$scenario==='missing'?false:['id'=>42,'available_seats'=>$scenario==='available'?5:0];
$model=new App\Models\FlightSearch(new FlightFixtureDatabase($row,$scenario==='database-error'));
$reflection=new ReflectionClass(App\Controllers\CustomerFlightController::class);
$controller=$reflection->newInstanceWithoutConstructor();
$reflection->getProperty('flights')->setValue($controller,$model);
register_shutdown_function(static function () use ($scenario): void {
    $alert=$_SESSION['_flash']['flight_alert']??null;
    if($scenario==='sold-out'){
        check(($alert['title']??null)==='No seats available','Sold-out selection did not trigger warning');
        check($_SESSION['selected_flight_id']===0,'Sold-out flight was selected');
    }elseif($scenario==='available'){
        check($_SESSION['selected_flight_id']===42,'Available flight was not selected');
        check($alert===null,'Available flight triggered warning');
    }else{
        check($alert===null,'Missing flight or database failure incorrectly reported sold out');
        check(!empty($_SESSION['_flash']['errors']),'Missing flight or database failure did not report error');
        check($_SESSION['selected_flight_id']===0,'Unavailable flight was selected');
    }
    echo 'PASS: flight selection '.$scenario.PHP_EOL;
});
$controller->select();
