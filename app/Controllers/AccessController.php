<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Booking;
use App\Models\ETicket;

final class AccessController extends Controller
{
    public function customer(): void
    {
        $sections=[
            'bookings'=>['My Bookings','Review your booking references, itineraries, passenger counts, totals, and current statuses.'],
            'passengers'=>['Passenger Details','Passenger details are entered during booking and can be reviewed from each booking confirmation.'],
            'seats'=>['Seat Selection','Choose a seat for each passenger on an eligible booking.'],
            'payments'=>['Payments','Submit manual payment details and track admin verification.'],
            'tickets'=>['E-Tickets','View and print e-tickets for confirmed bookings.'],
            'profile'=>['Profile / Account','Your account area is ready. Profile management will be added later.'],
        ];
        $section=is_scalar($_GET['section']??null)?(string)$_GET['section']:'home';
        if(!isset($sections[$section]))$section='home';
        $bookings=[];
        $tickets=[];
        $errors=[];
        if($section==='bookings'){
            try{$bookings=(new Booking())->listForCustomer((int)Session::get('user_id',0));}
            catch(\Throwable $e){error_log('Customer booking list error: '.$e->getMessage());$errors[]='Your bookings are temporarily unavailable. Please refresh and try again.';}
        }
        if($section==='tickets'){
            try{$tickets=(new ETicket())->forCustomer((int)Session::get('user_id',0));}
            catch(\Throwable $e){error_log('Customer e-ticket list error: '.$e->getMessage());$errors[]='Your e-tickets are temporarily unavailable. Please refresh and try again.';}
        }
        $this->view('customer/home',[
            'title'=>$section==='home'?'Home':$sections[$section][0],
            'section'=>$section,
            'sectionTitle'=>$section==='home'?'Home':$sections[$section][0],
            'sectionMessage'=>$section==='home'?'':$sections[$section][1],
            'activeSection'=>$section,
            'userName'=>Auth::name(),
            'csrf'=>Csrf::token(),
            'success'=>Session::pullFlash('success'),
            'errors'=>array_merge($errors??[],Session::pullFlash('errors',[])),
            'bookings'=>$bookings,
            'tickets'=>$tickets,
        ]);
    }

    public function cancelBooking(): void
    {
        $this->requireValidCsrf();
        $bookingId=filter_var($_POST['booking_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($bookingId===false){Session::flash('errors',['Choose a valid booking to cancel.']);$this->redirect('/account?section=bookings');}
        try{(new Booking())->cancelForCustomer((int)$bookingId,(int)Session::get('user_id',0));Session::flash('success','Booking cancelled. Any issued e-tickets have been voided and the seats released.');}
        catch(\PDOException $e){error_log('Customer booking cancellation database error: '.$e->getMessage());Session::flash('errors',['We could not cancel this booking. Please refresh and try again.']);}
        catch(\RuntimeException $e){Session::flash('errors',[$e->getMessage()]);}
        catch(\Throwable $e){error_log('Customer booking cancellation error: '.$e->getMessage());Session::flash('errors',['We could not cancel this booking. Please try again.']);}
        $this->redirect('/account?section=bookings');
    }

    public function admin(): void
    {
        $this->view('auth/access', [
            'title' => 'Admin Panel',
            'heading' => 'Admin Panel',
            'message' => 'Welcome, ' . Auth::name() . '. You are signed in as an administrator.',
            'actionPath' => null,
            'actionLabel' => null,
            'logoutPath' => '/admin/logout',
            'csrf' => Csrf::token(),
            'errors' => [],
            'success' => Session::pullFlash('success'),
        ]);
    }
}
