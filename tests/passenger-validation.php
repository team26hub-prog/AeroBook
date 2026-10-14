<?php
declare(strict_types=1);
require __DIR__.'/../app/Core/Controller.php';
require __DIR__.'/../app/Controllers/BookingController.php';

$reflection=new ReflectionClass(App\Controllers\BookingController::class);
$controller=$reflection->newInstanceWithoutConstructor();
$validate=$reflection->getMethod('validatePassengers');
$base=['first_name'=>'Daniel','last_name'=>'Harris','date_of_birth'=>'2000-01-01','gender'=>'unspecified','passport_number'=>'','passport_country'=>''];
$check=static function(mixed $input,bool $valid)use($validate,$controller):void{
    $result=$validate->invoke($controller,$input);
    if(($result['errors']===[])!==$valid)throw new RuntimeException('Unexpected validation result: '.json_encode([$input,$result['errors']],JSON_UNESCAPED_UNICODE));
};
foreach(['first_name','last_name'] as $key){
    foreach(['','123','John1',"O'Connor",'Anne-Marie',str_repeat('A',101),['bad']] as $value)$check([array_replace($base,[$key=>$value])],false);
    foreach(['Mary Jane','محمد','José',"Jose\u{0301}",'李'] as $value)$check([array_replace($base,[$key=>$value])],true);
}
foreach(['',"2000-01-01\0",'2026-02-30','0999-12-31',date('Y-m-d',strtotime('+1 day'))] as $dob)$check([array_replace($base,['date_of_birth'=>$dob])],false);
$check([array_replace($base,['date_of_birth'=>'2000-02-29'])],true);
foreach([
    ['gender'=>'invalid'],['passport_number'=>'AB-123','passport_country'=>'PK'],
    ['passport_number'=>str_repeat('A',31),'passport_country'=>'PK'],
    ['passport_number'=>'AB123'],['passport_country'=>'PK'],
    ['passport_number'=>'AB123','passport_country'=>'PAK']
] as $invalid)$check([array_replace($base,$invalid)],false);
$check([array_replace($base,['passport_number'=>'AB123','passport_country'=>'pk'])],true);
foreach([null,[],array_fill(0,10,$base)] as $input)$check($input,false);
$check(array_fill(0,9,$base),true);
echo "Passenger server validation checks passed.\n";
