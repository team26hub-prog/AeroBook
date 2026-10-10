<?php
declare(strict_types=1);
ob_start();
require __DIR__.'/ui-consistency.php';
ob_end_clean();
$_SESSION=['user_id'=>1,'user_role'=>'admin','user_name'=>'Test Admin'];
$common=['csrf'=>'test-token','adminName'=>'Test Admin','success'=>null,'errors'=>[],'charts'=>null];
$options=['airlines'=>[['id'=>1,'name'=>'AeroBook Air']],'airports'=>[['id'=>1,'name'=>'Jinnah Airport','iata_code'=>'KHI','city'=>'Karachi'],['id'=>2,'name'=>'Allama Iqbal Airport','iata_code'=>'LHE','city'=>'Lahore']],'flights'=>[['id'=>1,'flight_number'=>'AB101']]];
$stats=['bookings'=>13,'flights'=>13,'payments'=>13,'users'=>4,'pending_payments'=>7,'revenue'=>62000,'recent_bookings'=>[]];
$fixtures=[];
for($i=1;$i<=13;$i++){
    $fixtures['airlines'][]=['id'=>$i,'name'=>'Test Airline '.$i,'iata_code'=>'AB','icao_code'=>'ABK','status'=>$i%2?'active':'inactive'];
    $fixtures['airports'][]=['id'=>$i,'name'=>'Test Airport '.$i,'iata_code'=>'KHI','icao_code'=>'OPKC','city'=>'Karachi','country'=>'Pakistan','timezone'=>'Asia/Karachi','status'=>$i%2?'active':'inactive'];
    $fixtures['flights'][]=['id'=>$i,'flight_number'=>'AB'.$i,'airline'=>'AeroBook Air','airline_id'=>1,'departure_airport_id'=>1,'arrival_airport_id'=>2,'departure'=>'KHI','arrival'=>'LHE','departure_at'=>'2027-01-12 08:00:00','arrival_at'=>'2027-01-12 09:55:00','currency'=>'PKR','base_fare'=>18500,'capacity'=>60,'reserved'=>7,'status'=>$i%2?'scheduled':'completed'];
    $fixtures['seats'][]=['flight_id'=>$i,'flight_number'=>'AB'.$i,'departure'=>'KHI','arrival'=>'LHE','departure_at'=>'2027-01-12 08:00:00','capacity'=>60,'available'=>45,'blocked'=>8];
    $fixtures['bookings'][]=['id'=>$i,'pnr'=>'PNR'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'full_name'=>'Test Traveler '.$i,'email'=>'test'.$i.'@example.test','flight_number'=>'AB'.$i,'departure'=>'KHI','arrival'=>'LHE','passengers'=>'Test Passenger · DOB 2000-01-01','currency'=>'PKR','total_amount'=>18500,'status'=>$i%2?'pending':'confirmed','booked_at'=>'2026-10-10 08:00:00'];
    $fixtures['payments'][]=['id'=>$i,'pnr'=>'PNR'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'full_name'=>'Test Traveler '.$i,'email'=>'test'.$i.'@example.test','currency'=>'PKR','amount'=>18500,'method'=>'bank_transfer','sender_name'=>'Test Sender '.$i,'transaction_reference'=>'REF'.$i,'paid_at'=>'2026-10-10 08:00:00','proof_path'=>'','remarks'=>'Test payment details','status'=>['pending','submitted','verified','rejected'][($i-1)%4],'booking_status'=>'pending'];
}
$stats['recent_bookings']=array_slice($fixtures['bookings'],0,8);
$fixtures['flights'][0]['capacity']=0;
$fixtures['flights'][0]['reserved']=0;
$options['flights']=array_map(static fn($flight)=>['id'=>$flight['id'],'flight_number'=>$flight['flight_number']],$fixtures['flights']);
foreach(['dashboard','airlines','airports','flights','seats','bookings','payments'] as $section){
    $_SERVER['REQUEST_URI']=$section==='dashboard'?'/admin':'/admin/'.$section;
    $html=renderUi('admin/panel',$common+['section'=>$section,'title'=>ucfirst($section),'rows'=>$fixtures[$section]??[],'stats'=>$stats,'options'=>$options]);
    $xpath=uiDocument($html);
    check(str_contains($html,'/assets/js/admin-workspace.js'),'Admin enhancements missing: '.$section);
    check(str_contains($html,'/assets/js/admin-usability.js'),'Guided admin workflows missing: '.$section);
    check(str_contains($html,'admin-workflow-help'),'Contextual help missing: '.$section);
    if(in_array($section,['airlines','airports','flights','seats'],true)){
        check($xpath->query('//details[@id="admin-create"]')->length===1,'Create form is not collapsible: '.$section);
        check($xpath->query('//form[contains(@class,"admin-guided-form")]')->length===1,'Guided form missing: '.$section);
    }
    if($section==='flights'){
        check($xpath->query('//select[@name="departure_airport_id"]/option[@value="" and @disabled]')->length===1,'Route selector must require a deliberate choice');
        check(str_contains($html,'Arrival must be later than departure'),'Schedule guidance missing');
        check($xpath->query('//a[@href="/admin/seats?q=AB1#admin-create"]')->length===1,'Unconfigured flight must link directly to seat creation');
    }
    check($xpath->query('//tr/form')->length===0,'Invalid form directly inside table row: '.$section);
    check($xpath->query('//form//form')->length===0,'Nested form: '.$section);
    check($xpath->query('//*[@aria-current="page" and ancestor::aside]')->length===1,'Active admin navigation missing: '.$section);
    if(in_array($section,['airlines','airports','flights'],true)){
        check($xpath->query('//form[contains(@class,"admin-record-form")]')->length===13,'Record forms lost: '.$section);
        foreach($xpath->query('//*[@form]') as $control){
            $id=$control->getAttribute('form');
            check($xpath->query('//form[@id="'.$id.'"]')->length===1,'Editable control has no form: '.$section);
            check($xpath->query('//form[@id="'.$id.'"]//input[@name="_csrf" and @value="test-token"]')->length===1,'Record CSRF missing');
            check($xpath->query('//form[@id="'.$id.'"]//input[@name="kind" and @value="'.$section.'"]')->length===1,'Record action kind changed');
        }
    }
    if($section==='payments')check($xpath->query('//button[@name="decision" and @value="verified"]')->length===7,'Payment verify actions changed');
    if($section==='dashboard')check(strpos($html,'recent-heading')<strpos($html,'analytics-heading'),'Recent bookings must appear before analytics');
    if(in_array('--preview',$argv,true)){
        $directory=BASE_PATH.'/tests/.runtime/admin-ui';
        if(!is_dir($directory))mkdir($directory,0777,true);
        file_put_contents($directory.'/'.$section.'.html',$html);
    }
}
echo "PASS: seven admin sections; valid row forms and CSRF ownership; unchanged payment decisions; dashboard priorities\n";
