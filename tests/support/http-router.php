<?php
declare(strict_types=1);
// This router may only run with a disposable database, never the configured application DB.
if(!preg_match('/^aerobook_test_[a-f0-9]{12}$/D',(string)getenv('DB_DATABASE'))){http_response_code(503);exit('Test database required');}
$public=realpath(__DIR__.'/../../public');
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath($public.'/'.ltrim((string)$path,'/'));
if($file&&str_starts_with($file,$public.DIRECTORY_SEPARATOR)&&is_file($file)&&strtolower(pathinfo($file,PATHINFO_EXTENSION))!=='php')return false;
require $public.'/index.php';
