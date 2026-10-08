<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Booking;

final class AccessController extends Controller
{
    public function customer(): void
    {
        $sections=[
            'bookings'=>['My Bookings','Review your booking references, itineraries, passenger counts, totals, and current statuses.'],
            'passengers'=>['Passenger Details','Passenger details are entered during booking and can be reviewed from each booking confirmation.'],
            'seats'=>['Seat Selection','Seat selection will be available in a future booking step.'],
            'payments'=>['Payments','Payment steps are not available yet.'],
            'tickets'=>['E-Tickets','Your e-tickets will appear here after a booking is confirmed.'],
            'profile'=>['Profile / Account','Your account area is ready. Profile management will be added later.'],
        ];
        $section=(string)($_GET['section']??'home');
        if(!isset($sections[$section]))$section='home';
        $bookings=[];
        if($section==='bookings'){
            try{$bookings=(new Booking())->listForCustomer((int)Session::get('user_id',0));}
            catch(\Throwable $e){error_log('Customer booking list error: '.$e->getMessage());}
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
            'bookings'=>$bookings,
        ]);
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
