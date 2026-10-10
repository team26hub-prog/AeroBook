<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\FlightSearch;

final class CustomerFlightController extends Controller
{
    private FlightSearch $flights;
    public function __construct() { $this->flights=new FlightSearch(); }

    public function search(): void
    {
        if(($_GET['seat_counts']??null)==='1'){
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            $raw=$_GET['ids']??null;
            if(!is_string($raw)||!preg_match('/^[1-9][0-9]*(,[1-9][0-9]*){0,499}$/D',$raw)){
                http_response_code(400);echo json_encode(['error'=>'Invalid flight IDs.']);return;
            }
            $ids=array_values(array_unique(array_map('intval',explode(',',$raw))));
            try{echo json_encode(['flights'=>$this->flights->seatAvailability($ids)],JSON_THROW_ON_ERROR);}
            catch(\Throwable $e){error_log('Flight seat availability refresh error: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Seat availability could not be refreshed.']);}
            return;
        }
        $errors=Session::pullFlash('errors',[]);$results=null;$departure=is_scalar($_GET['departure_id']??null)?(string)$_GET['departure_id']:'';$arrival=is_scalar($_GET['arrival_id']??null)?(string)$_GET['arrival_id']:'';$date=is_scalar($_GET['date']??null)?(string)$_GET['date']:'';
        $hasSearch=isset($_GET['departure_id'])||isset($_GET['arrival_id'])||isset($_GET['date']);$flightsUnavailable=false;
        $airportBrowse=isset($_GET['departure_id'])&&!array_key_exists('arrival_id',$_GET)&&!array_key_exists('date',$_GET);
        if($airportBrowse){
            if(filter_var($departure,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)$errors[]='Choose a departure airport.';
            if($errors===[]){try{$results=$this->flights->departingFrom((int)$departure);}catch(\Throwable $e){error_log('Airport departure search error: '.$e->getMessage());$flightsUnavailable=true;$errors[]='Flight search is temporarily unavailable.';}}
        }elseif($hasSearch){
            if(filter_var($departure,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)$errors[]='Choose a departure airport.';
            if(filter_var($arrival,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)$errors[]='Choose a destination airport.';
            if($departure!==''&&$arrival!==''&&$departure===$arrival)$errors[]='Departure and destination must be different airports.';
            $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);$dateErrors=\DateTimeImmutable::getLastErrors();
            if(!$parsed||($dateErrors!==false&&($dateErrors['warning_count']||$dateErrors['error_count']))||$parsed->format('Y-m-d')!==$date)$errors[]='Choose a valid travel date.';
            elseif($date<date('Y-m-d'))$errors[]='Travel date cannot be in the past.';
            if($errors===[]){try{$results=$this->flights->search((int)$departure,(int)$arrival,$date);}catch(\Throwable $e){error_log('Flight search query error: '.$e->getMessage());$flightsUnavailable=true;$errors[]='Flight search is temporarily unavailable.';}}
        }
        try{$airports=$this->flights->airports();}catch(\Throwable $e){error_log('Flight search airport error: '.$e->getMessage());http_response_code(500);$airports=[];$errors[]='Flight search is temporarily unavailable.';}
        $this->view('flights/search',['title'=>'Search Flights','activeSection'=>'search','userName'=>Auth::name(),'airports'=>$airports,'results'=>$results,'hasSearch'=>$hasSearch,'flightsUnavailable'=>$flightsUnavailable,'errors'=>$errors,'filters'=>['departure_id'=>$departure,'arrival_id'=>$arrival,'date'=>$date],'csrf'=>Csrf::token()]);
    }

    public function details(): void
    {
        $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $failed=false;try{$flight=$id?$this->flights->findUpcoming((int)$id):null;}catch(\Throwable $e){error_log('Flight details error: '.$e->getMessage());$failed=true;$flight=null;}
        if($flight===null){http_response_code($failed?500:404);$this->view('flights/unavailable',['title'=>'Flight unavailable','activeSection'=>'search','userName'=>Auth::name(),'csrf'=>Csrf::token(),'failed'=>$failed]);return;}
        if((int)$flight['available_seats']<1){$this->flashSeatAvailabilityAlert();$this->redirect('/flights');}
        $this->view('flights/details',['title'=>'Flight details','activeSection'=>'search','userName'=>Auth::name(),'flight'=>$flight,'csrf'=>Csrf::token(),'selected'=>(int)Session::get('selected_flight_id',0)===(int)$flight['id'],'success'=>Session::pullFlash('success')]);
    }

    public function select(): void
    {
        $this->requireValidCsrf();$id=filter_var($_POST['flight_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($id===false){Session::flash('errors',['Select a valid flight.']);$this->redirect('/flights');}
        try{$flight=$this->flights->findUpcoming((int)$id);}catch(\Throwable $e){error_log('Flight selection error: '.$e->getMessage());Session::flash('errors',['We could not check seat availability. Please try again.']);$this->redirect('/flights');}
        if($flight===null){Session::flash('errors',['This flight is no longer available. Search again to see current options.']);$this->redirect('/flights');}
        if((int)$flight['available_seats']<1){$this->flashSeatAvailabilityAlert();$this->redirect('/flights');}
        Session::put('selected_flight_id',(int)$flight['id']);
        Session::put('booking_review',null);Session::pullFlash('booking_passengers',[]);
        $this->redirect('/booking/passengers');
    }
}
