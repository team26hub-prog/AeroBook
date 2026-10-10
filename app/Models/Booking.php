<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;
use RuntimeException;

final class Booking extends Model
{
    public function createPending(int $customerId,int $flightId,array $passengers,string $reviewedFare,string $reviewedCurrency): array
    {
        if($customerId<1||$flightId<1||count($passengers)<1||count($passengers)>9)throw new RuntimeException('Booking details are incomplete.');
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare("SELECT f.base_fare,f.currency,f.status,f.departure_at,al.status airline_status,da.status departure_status,aa.status arrival_status,
                (SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id AND s.status='available' AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id)) available_seats
                FROM flights f JOIN airlines al ON al.id=f.airline_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id WHERE f.id=? FOR UPDATE");
            $q->execute([$flightId]);$flight=$q->fetch();
            if(!$flight||!in_array($flight['status'],['scheduled','boarding'],true)||$flight['airline_status']!=='active'||$flight['departure_status']!=='active'||$flight['arrival_status']!=='active'||strtotime($flight['departure_at'])<time())throw new RuntimeException('This flight is no longer available. Search for another flight.');
            if(number_format((float)$flight['base_fare'],2,'.','')!==number_format((float)$reviewedFare,2,'.','')||$flight['currency']!==$reviewedCurrency)throw new RuntimeException('The fare changed since your review. Please review the updated booking total.');
            if((int)$flight['available_seats']<count($passengers))throw new \App\Core\InsufficientSeats((int)$flight['available_seats']);
            $fareCents=(int)round((float)$flight['base_fare']*100);$totalCents=$fareCents*count($passengers);
            if($totalCents>9999999999)throw new RuntimeException('The total amount exceeds the booking limit.');
            $total=number_format($totalCents/100,2,'.','');
            $bookingId=0;$pnr='';$insert=$this->db->prepare('INSERT INTO bookings(user_id,flight_id,pnr,status,currency,total_amount) VALUES(?,?,?,"pending",?,?)');
            for($attempt=0;$attempt<5;$attempt++){
                $pnr=strtoupper(bin2hex(random_bytes(5)));
                try{$insert->execute([$customerId,$flightId,$pnr,$flight['currency'],$total]);$bookingId=(int)$this->db->lastInsertId();break;}
                catch(PDOException $e){if((int)($e->errorInfo[1]??0)!==1062||$attempt===4)throw $e;}
            }
            if($bookingId<1)throw new RuntimeException('Could not create a booking reference. Please try again.');
            $passengerInsert=$this->db->prepare('INSERT INTO passengers(booking_id,first_name,last_name,date_of_birth,gender,passport_number,passport_country) VALUES(?,?,?,?,?,?,?)');
            foreach($passengers as $passenger)$passengerInsert->execute([$bookingId,$passenger['first_name'],$passenger['last_name'],$passenger['date_of_birth'],$passenger['gender'],$passenger['passport_number'],$passenger['passport_country']]);
            $this->db->commit();
            return ['id'=>$bookingId,'pnr'=>$pnr,'total_amount'=>$total,'currency'=>$flight['currency']];
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function findForCustomer(int $bookingId,int $customerId): ?array
    {
        $q=$this->db->prepare('SELECT b.*,f.flight_number,f.departure_at,f.arrival_at,al.name airline_name,da.iata_code departure_code,da.city departure_city,aa.iata_code arrival_code,aa.city arrival_city FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airlines al ON al.id=f.airline_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id WHERE b.id=? AND b.user_id=? LIMIT 1');
        $q->execute([$bookingId,$customerId]);$row=$q->fetch();if($row===false)return null;
        $p=$this->db->prepare('SELECT first_name,last_name,date_of_birth,gender,passport_number,passport_country FROM passengers WHERE booking_id=? ORDER BY id');$p->execute([$bookingId]);$row['passengers']=$p->fetchAll();return $row;
    }

    public function listForCustomer(int $customerId): array
    {
        $q=$this->db->prepare("SELECT b.id,b.pnr,b.status,b.currency,b.total_amount,b.booked_at,f.flight_number,f.departure_at,f.arrival_at,al.name airline_name,
            da.iata_code departure_code,da.city departure_city,aa.iata_code arrival_code,aa.city arrival_city,
            (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) passenger_count,
            (SELECT py.status FROM payments py WHERE py.booking_id=b.id ORDER BY py.id DESC LIMIT 1) payment_status,
            (b.status IN ('pending','confirmed') AND f.departure_at>NOW()) can_cancel,
            (f.departure_at<=NOW()) departure_time_passed
            FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airlines al ON al.id=f.airline_id
            JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            WHERE b.user_id=? ORDER BY b.created_at DESC");
        $q->execute([$customerId]);$bookings=$q->fetchAll();
        if(!$bookings)return [];
        $ids=array_map(static fn($booking)=>(int)$booking['id'],$bookings);$marks=implode(',',array_fill(0,count($ids),'?'));
        $people=$this->db->prepare("SELECT p.booking_id,p.id,p.first_name,p.last_name,p.date_of_birth,p.gender,s.seat_number,t.ticket_number,t.status ticket_status
            FROM passengers p LEFT JOIN booking_seats bs ON bs.booking_id=p.booking_id AND bs.passenger_id=p.id
            LEFT JOIN seats s ON s.id=bs.seat_id LEFT JOIN e_tickets t ON t.booking_id=p.booking_id AND t.passenger_id=p.id
            WHERE p.booking_id IN ({$marks}) ORDER BY p.booking_id,p.id");
        $people->execute($ids);$byBooking=[];foreach($people->fetchAll() as $person){$bookingId=(int)$person['booking_id'];unset($person['booking_id']);$byBooking[$bookingId][]=$person;}
        foreach($bookings as &$booking){$booking['passengers']=$byBooking[(int)$booking['id']]??[];}
        unset($booking);return $bookings;
    }

    public function cancelForCustomer(int $bookingId,int $customerId): void
    {
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare('SELECT b.status,f.departure_at,(f.departure_at>NOW()) departure_is_future FROM bookings b JOIN flights f ON f.id=b.flight_id WHERE b.id=? AND b.user_id=? FOR UPDATE');
            $q->execute([$bookingId,$customerId]);$booking=$q->fetch();
            if(!$booking)throw new RuntimeException('Booking not found.');
            if(!in_array($booking['status'],['pending','confirmed'],true)||(int)$booking['departure_is_future']!==1)throw new RuntimeException('This booking is no longer eligible for cancellation.');
            $this->db->prepare('UPDATE bookings SET status="cancelled" WHERE id=?')->execute([$bookingId]);
            $this->db->prepare('UPDATE e_tickets SET status="void" WHERE booking_id=? AND status="issued"')->execute([$bookingId]);
            $this->db->prepare('UPDATE seats s JOIN booking_seats bs ON bs.seat_id=s.id SET s.status="available" WHERE bs.booking_id=?')->execute([$bookingId]);
            $this->db->prepare('DELETE FROM booking_seats WHERE booking_id=?')->execute([$bookingId]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
