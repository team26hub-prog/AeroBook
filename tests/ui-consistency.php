<?php
declare(strict_types=1);
ob_start();
require __DIR__.'/public-home.php';
function renderUi(string $view,array $data):string{
    extract($data,EXTR_SKIP);
    ob_start();require BASE_PATH.'/app/Views/'.$view.'.php';return ob_get_clean();
}
function uiDocument(string $html):DOMXPath{
    $document=new DOMDocument();
    $previous=libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();libxml_use_internal_errors($previous);
    return new DOMXPath($document);
}
$common=['csrf'=>'test-token','userName'=>'Test User','success'=>null,'errors'=>[]];
$_SESSION=['user_id'=>1,'user_role'=>'customer','user_name'=>'Test User'];
foreach(['home','bookings','tickets','profile','passengers'] as $section){
    $_SERVER['REQUEST_URI']=$section==='home'?'/account':'/account?section='.$section;
    $html=renderUi('customer/home',$common+['section'=>$section,'title'=>ucfirst($section),'activeSection'=>$section,'bookings'=>[],'tickets'=>[],'profile'=>null,'sectionTitle'=>'Passenger Details','sectionMessage'=>'Choose a flight first.']);
    $xpath=uiDocument($html);
    check($xpath->query('//main | //*[@role="main"]')->length===1,'Customer main landmark duplicated: '.$section);
    check(str_contains($html,'/assets/css/customer-layout.css'),'Customer shared layout missing');
    check($xpath->query('//*[@data-page-back]')->length===($section==='home'?0:1),'Customer Back button missing or shown on Home: '.$section);
    if($section==='home'){
        check(!str_contains($html,'href="/flights/details"')&&!str_contains($html,'href="/booking/passengers"'),'Home links bypass flight selection');
        check(!str_contains($html,'class="customer-hero-stats"')&&!str_contains($html,'class="customer-trip-cards"'),'Unsupported Home figures or empty routes displayed');
        check(!str_contains($html,'aria-labelled='),'Invalid Home ARIA');
        foreach($xpath->query('//section[@aria-labelledby]') as $node){
            $id=$node->getAttribute('aria-labelledby');
            check($xpath->query('//*[@id="'.$id.'"]')->length===1,'Missing section heading: '.$id);
        }
    }
}
$stats=['bookings'=>0,'flights'=>0,'payments'=>0,'users'=>0,'pending_payments'=>0,'revenue'=>0,'recent_bookings'=>[]];
foreach(['dashboard','airlines','airports','flights','seats','bookings','payments'] as $section){
    $_SERVER['REQUEST_URI']=$section==='dashboard'?'/admin':'/admin/'.$section;
    $html=renderUi('admin/panel',$common+['section'=>$section,'title'=>ucfirst($section),'adminName'=>'Test Admin','stats'=>$stats,'rows'=>[],'charts'=>null,'options'=>['airlines'=>[],'airports'=>[],'flights'=>[]]]);
    $xpath=uiDocument($html);
    check($xpath->query('//main | //*[@role="main"]')->length===1,'Admin main landmark duplicated: '.$section);
    check($xpath->query('//main//main')->length===0,'Nested main: '.$section);
    check(str_contains($html,'action="/admin/logout"'),'Admin sign-out missing');
    check($xpath->query('//*[@data-page-back]')->length===1,'Shared admin Back button missing: '.$section);
}
$_SERVER['REQUEST_URI']='/login';
$html=renderUi('auth/login',['title'=>'Login','csrf'=>'test-token','old'=>[],'errors'=>[],'success'=>null]);
check(uiDocument($html)->query('//main')->length===1,'Auth main landmark missing');
check(uiDocument($html)->query('//*[@data-page-back]')->length===1,'Login Back button missing');
$_SERVER['REQUEST_URI']='/register';
$html=renderUi('auth/register',['title'=>'Sign Up','csrf'=>'test-token','old'=>[],'errors'=>[],'success'=>null]);
check(uiDocument($html)->query('//*[@data-page-back]')->length===1,'Sign Up Back button missing');
ob_end_clean();
echo "PASS: Home entry links and honest empty data; valid landmarks and section labels; shared customer layout; seven admin sections and login rendering\n";
