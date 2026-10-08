<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\ManualPayment;
use RuntimeException;

final class ManualPaymentController extends Controller
{
    private ManualPayment $payments;
    public function __construct(){ $this->payments=new ManualPayment(); }

    public function index(): void
    {
        $customerId=(int)Session::get('user_id',0);$bookingId=filter_input(INPUT_GET,'booking_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        try{
            if($bookingId){
                $booking=$this->payments->booking((int)$bookingId,$customerId);
                if(!$booking){http_response_code(404);$this->redirect('/payments');}
                $this->view('payments/customer',['title'=>'Payments','activeSection'=>'payments','userName'=>Auth::name(),'csrf'=>Csrf::token(),'booking'=>$booking,'passengers'=>$this->payments->passengers((int)$booking['id']),'history'=>$this->payments->history((int)$booking['id'],$customerId),'old'=>Session::pullFlash('payment_old',[]),'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
                return;
            }
            $this->view('payments/index',['title'=>'Payments','activeSection'=>'payments','userName'=>Auth::name(),'csrf'=>Csrf::token(),'bookings'=>$this->payments->bookingsForCustomer($customerId),'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
        }catch(\Throwable $e){error_log('Customer payment page error: '.$e->getMessage());http_response_code(500);$this->view('payments/error',['title'=>'Payments unavailable','activeSection'=>'payments','userName'=>Auth::name(),'csrf'=>Csrf::token()]);}
    }

    public function submit(): void
    {
        $this->requireValidCsrf();$bookingId=filter_var($_POST['booking_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($bookingId===false){Session::flash('errors',['The booking reference is invalid.']);$this->redirect('/payments');}
        try{$this->payments->submit((int)$bookingId,(int)Session::get('user_id',0),$_POST);Session::flash('success','Payment details submitted. Your booking will be updated after an administrator verifies the payment.');}
        catch(\PDOException $e){error_log('Customer payment database error: '.$e->getMessage());Session::flash('payment_old',$this->oldInput($_POST));Session::flash('errors',['We could not submit these payment details. Check the reference ID and try again.']);}
        catch(RuntimeException $e){Session::flash('payment_old',$this->oldInput($_POST));Session::flash('errors',[$e->getMessage()]);}
        catch(\Throwable $e){error_log('Customer payment submission error: '.$e->getMessage());Session::flash('errors',['We could not submit these payment details. Please try again.']);}
        $this->redirect('/payments?booking_id='.(int)$bookingId);
    }

    private function oldInput(array $input): array
    {
        $old=[];foreach(['method','sender_name','transaction_reference','paid_at','amount','remarks'] as $key)if(isset($input[$key])&&is_scalar($input[$key]))$old[$key]=(string)$input[$key];return $old;
    }
}
