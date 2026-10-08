<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\SeatSelection;
use RuntimeException;

final class SeatSelectionController extends Controller
{
    private SeatSelection $seats;
    public function __construct(){ $this->seats=new SeatSelection(); }

    public function index(): void
    {
        $customerId=(int)Session::get('user_id',0);$bookingId=filter_input(INPUT_GET,'booking_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        try{
            if($bookingId){
                $booking=$this->seats->booking((int)$bookingId,$customerId);
                if(!$booking){http_response_code(404);$this->redirect('/seat-selection');}
                $passengers=$this->seats->passengers((int)$booking['id']);$seats=$this->seats->seats((int)$booking['id'],(int)$booking['flight_id']);
                $this->view('seats/select',['title'=>'Seat selection','activeSection'=>'seats','userName'=>Auth::name(),'csrf'=>Csrf::token(),'booking'=>$booking,'passengers'=>$passengers,'seats'=>$seats,'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
                return;
            }
            $bookings=$this->seats->pendingBookings($customerId);
            $this->view('seats/index',['title'=>'Seat selection','activeSection'=>'seats','userName'=>Auth::name(),'csrf'=>Csrf::token(),'bookings'=>$bookings,'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
        }catch(\Throwable $e){error_log('Seat selection page error: '.$e->getMessage());http_response_code(500);$this->view('seats/error',['title'=>'Seat selection unavailable','activeSection'=>'seats','userName'=>Auth::name(),'csrf'=>Csrf::token()]);}
    }

    public function save(): void
    {
        $this->requireValidCsrf();$bookingId=filter_var($_POST['booking_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$raw=$_POST['passengers']??null;$selection=[];
        if($bookingId===false||!is_array($raw)){Session::flash('errors',['Choose one seat for each passenger.']);$this->redirect('/seat-selection');}
        foreach($raw as $passengerId=>$seatId){$p=filter_var($passengerId,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$s=filter_var($seatId,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($p===false||$s===false){Session::flash('errors',['The passenger or seat selection is invalid.']);$this->redirect('/seat-selection?booking_id='.(int)$bookingId);}$selection[(int)$p]=(int)$s;}
        try{$this->seats->save((int)$bookingId,(int)Session::get('user_id',0),$selection);Session::flash('success','Seats saved for every passenger.');$this->redirect('/payments?booking_id='.(int)$bookingId);}
        catch(\PDOException $e){error_log('Seat assignment database error: '.$e->getMessage());Session::flash('errors',['Could not save these seats. Refresh the layout and try again.']);}
        catch(RuntimeException $e){Session::flash('errors',[$e->getMessage()]);}
        catch(\Throwable $e){error_log('Seat assignment error: '.$e->getMessage());Session::flash('errors',['Could not save these seats. Refresh the layout and try again.']);}
        $this->redirect('/seat-selection?booking_id='.(int)$bookingId);
    }
}
