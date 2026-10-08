<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Booking;
use App\Models\FlightSearch;
use RuntimeException;

final class BookingController extends Controller
{
    private Booking $bookings;
    private FlightSearch $flights;
    public function __construct(){ $this->bookings=new Booking();$this->flights=new FlightSearch(); }

    public function passengers(): void
    {
        $flightId=(int)Session::get('selected_flight_id',0);try{$flight=$flightId?$this->flights->findAvailable($flightId):null;}catch(\Throwable $e){error_log('Selected flight check failed: '.$e->getMessage());$flight=null;}
        if(!$flight){Session::flash('errors',['Select an available flight before entering passenger details.']);$this->redirect('/flights');}
        $old=Session::pullFlash('booking_passengers',[]);if(!$old){$review=Session::get('booking_review');if(is_array($review)&&is_array($review['passengers']??null))$old=$review['passengers'];}
        $this->view('booking/passengers',['title'=>'Passenger details','activeSection'=>'search','userName'=>Auth::name(),'csrf'=>Csrf::token(),'flight'=>$flight,'errors'=>Session::pullFlash('errors',[]),'old'=>$old]);
    }

    public function review(): void
    {
        $this->requireValidCsrf();$flightId=(int)Session::get('selected_flight_id',0);$passengers=$this->validatePassengers($_POST['passengers']??null);
        if($passengers['errors']){Session::flash('errors',$passengers['errors']);Session::flash('booking_passengers',$passengers['data']);$this->redirect('/booking/passengers');}
        try{$flight=$flightId?$this->flights->findAvailable($flightId):null;}catch(\Throwable $e){error_log('Booking review flight check failed: '.$e->getMessage());$flight=null;}
        if(!$flight){Session::flash('errors',['Your selected flight is no longer available. Search for another flight.']);$this->redirect('/flights');}
        if((int)$flight['available_seats']<count($passengers['data'])){Session::flash('errors',['There are not enough available seats for the passenger count.']);Session::flash('booking_passengers',$passengers['data']);$this->redirect('/booking/passengers');}
        $fareCents=(int)round((float)$flight['base_fare']*100);$totalCents=$fareCents*count($passengers['data']);
        if($totalCents>9999999999){Session::flash('errors',['The total amount exceeds the booking limit.']);Session::flash('booking_passengers',$passengers['data']);$this->redirect('/booking/passengers');}
        Session::put('booking_review',['flight_id'=>$flightId,'passengers'=>$passengers['data'],'fare'=>$flight['base_fare'],'currency'=>$flight['currency']]);
        $this->view('booking/review',['title'=>'Review booking','activeSection'=>'search','userName'=>Auth::name(),'csrf'=>Csrf::token(),'flight'=>$flight,'passengers'=>$passengers['data'],'total'=>number_format($totalCents/100,2,'.','')]);
    }

    public function create(): void
    {
        $this->requireValidCsrf();$review=Session::get('booking_review');$flightId=(int)Session::get('selected_flight_id',0);
        if(!is_array($review)||!isset($review['flight_id'],$review['passengers'],$review['fare'],$review['currency'])||(int)$review['flight_id']!==$flightId||!is_array($review['passengers'])){Session::flash('errors',['Your booking review has expired. Please enter passenger details again.']);$this->redirect('/booking/passengers');}
        try{$booking=$this->bookings->createPending((int)Session::get('user_id',0),$flightId,$review['passengers'],(string)$review['fare'],(string)$review['currency']);}
        catch(RuntimeException $e){Session::flash('errors',[$e->getMessage()]);Session::flash('booking_passengers',$review['passengers']);$this->redirect('/booking/passengers');}
        catch(\Throwable $e){error_log('Booking creation error: '.$e->getMessage());Session::flash('errors',['We could not create the booking. Please try again.']);Session::flash('booking_passengers',$review['passengers']);$this->redirect('/booking/passengers');}
        Session::put('selected_flight_id',0);Session::put('booking_review',null);Session::put('booking_confirmation_id',$booking['id']);
        $this->redirect('/booking/confirmation?id='.(int)$booking['id']);
    }

    public function confirmation(): void
    {
        $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $booking=$id?$this->bookings->findForCustomer((int)$id,(int)Session::get('user_id',0)):null;
        if(!$booking)$this->redirect('/account?section=bookings');
        $this->view('booking/confirmation',['title'=>'Booking created','activeSection'=>'bookings','userName'=>Auth::name(),'booking'=>$booking]);
    }

    private function validatePassengers(mixed $input): array
    {
        $errors=[];$data=[];
        if(!is_array($input)||count($input)<1||count($input)>9)return ['errors'=>['Add between 1 and 9 passengers.'],'data'=>[]];
        foreach(array_values($input) as $index=>$row){
            if(!is_array($row)){$errors[]='Passenger '.($index+1).' details are invalid.';continue;}
            $first=trim($this->scalar($row['first_name']??null));$last=trim($this->scalar($row['last_name']??null));$dob=$this->scalar($row['date_of_birth']??null);$gender=$this->scalar($row['gender']??'unspecified');$passport=trim($this->scalar($row['passport_number']??null));$country=strtoupper(trim($this->scalar($row['passport_country']??null)));
            if($first===''||$this->length($first)>100)$errors[]='Enter a valid first name for passenger '.($index+1).'.';
            if($last===''||$this->length($last)>100)$errors[]='Enter a valid last name for passenger '.($index+1).'.';
            $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$dob);$dateErrors=\DateTimeImmutable::getLastErrors();
            if(!$date||($dateErrors!==false&&($dateErrors['warning_count']||$dateErrors['error_count']))||$date->format('Y-m-d')!==$dob||$dob>date('Y-m-d'))$errors[]='Enter a valid date of birth for passenger '.($index+1).'.';
            if(!in_array($gender,['female','male','other','unspecified'],true))$errors[]='Choose a valid gender for passenger '.($index+1).'.';
            if(strlen($passport)>30)$errors[]='Passport number must be 30 characters or fewer.';
            if($country!==''&&!preg_match('/^[A-Z]{2}$/',$country))$errors[]='Passport country must be a two-letter country code.';
            if($passport!==''&&$country==='')$errors[]='Choose a passport country when a passport number is provided.';
            $data[]=['first_name'=>$first,'last_name'=>$last,'date_of_birth'=>$dob,'gender'=>$gender,'passport_number'=>$passport===''?null:$passport,'passport_country'=>$country===''?null:$country];
        }
        return ['errors'=>$errors,'data'=>$data];
    }

    private function length(string $value): int { $n=preg_match_all('/./us',$value);return $n===false?PHP_INT_MAX:$n; }
    private function scalar(mixed $value): string { return is_scalar($value)?(string)$value:''; }
}
