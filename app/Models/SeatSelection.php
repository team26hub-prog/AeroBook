<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

final class SeatSelection extends Model
{
    public function pendingBookings(int $customerId): array
    {
        $q=$this->db->prepare("SELECT b.id,b.pnr,b.status,b.flight_id,f.flight_number,f.departure_at,da.iata_code departure_code,aa.iata_code arrival_code,
            (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) passenger_count,
            (SELECT COUNT(DISTINCT bs.passenger_id) FROM booking_seats bs WHERE bs.booking_id=b.id AND bs.passenger_id IS NOT NULL) assigned_count
            FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            WHERE b.user_id=? AND b.status='pending' ORDER BY b.created_at DESC");
        $q->execute([$customerId]);return $q->fetchAll();
    }

    public function booking(int $bookingId,int $customerId): ?array
    {
        $q=$this->db->prepare("SELECT b.id,b.pnr,b.status,b.flight_id,b.total_amount,b.currency,f.flight_number,f.departure_at,f.arrival_at,f.base_fare,al.name airline_name,
            da.iata_code departure_code,da.city departure_city,aa.iata_code arrival_code,aa.city arrival_city
            FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airlines al ON al.id=f.airline_id
            JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            WHERE b.id=? AND b.user_id=? AND b.status='pending' LIMIT 1");
        $q->execute([$bookingId,$customerId]);$booking=$q->fetch();return $booking===false?null:$booking;
    }

    public function passengers(int $bookingId): array
    {
        $q=$this->db->prepare('SELECT p.id,p.first_name,p.last_name,p.date_of_birth,bs.seat_id,s.seat_number FROM passengers p LEFT JOIN booking_seats bs ON bs.booking_id=p.booking_id AND bs.passenger_id=p.id LEFT JOIN seats s ON s.id=bs.seat_id WHERE p.booking_id=? ORDER BY p.id');
        $q->execute([$bookingId]);return $q->fetchAll();
    }

    public function seats(int $bookingId,int $flightId): array
    {
        $q=$this->db->prepare("SELECT s.id,s.seat_number,s.cabin_class,s.status seat_status,bs.booking_id occupied_booking,bs.passenger_id
            FROM seats s LEFT JOIN booking_seats bs ON bs.seat_id=s.id WHERE s.flight_id=?");
        $q->execute([$flightId]);$seats=$q->fetchAll();
        foreach($seats as &$seat){
            if($seat['seat_status']!=='available')$seat['display_state']='blocked';
            elseif($seat['occupied_booking']!==null&&(int)$seat['occupied_booking']===$bookingId)$seat['display_state']='assigned';
            elseif($seat['occupied_booking']!==null)$seat['display_state']='occupied';
            else $seat['display_state']='available';
        }
        unset($seat);usort($seats,static fn($a,$b)=>strnatcasecmp($a['seat_number'],$b['seat_number']));return $seats;
    }

    /** @param array<int,int> $selection passenger ID => seat ID */
    public function save(int $bookingId,int $customerId,array $selection): void
    {
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare("SELECT flight_id,status FROM bookings WHERE id=? AND user_id=? FOR UPDATE");$q->execute([$bookingId,$customerId]);$booking=$q->fetch();
            if(!$booking||$booking['status']!=='pending')throw new RuntimeException('This booking is not available for seat selection.');
            $pq=$this->db->prepare('SELECT id FROM passengers WHERE booking_id=? ORDER BY id FOR UPDATE');$pq->execute([$bookingId]);$passengerIds=array_map('intval',$pq->fetchAll(\PDO::FETCH_COLUMN));
            if(count($passengerIds)<1||count($selection)!==count($passengerIds))throw new RuntimeException('Choose one seat for each passenger.');
            $normalized=[];foreach($selection as $passengerId=>$seatId){$passengerId=(int)$passengerId;$seatId=(int)$seatId;if(!in_array($passengerId,$passengerIds,true)||$seatId<1)throw new RuntimeException('The passenger or seat selection is invalid.');$normalized[$passengerId]=$seatId;}
            if(count(array_unique(array_values($normalized)))!==count($passengerIds))throw new RuntimeException('Each passenger must have a different seat.');
            $seatIds=array_values($normalized);sort($seatIds,SORT_NUMERIC);$marks=implode(',',array_fill(0,count($seatIds),'?'));
            $lock=$this->db->prepare("SELECT id,status FROM seats WHERE flight_id=? AND id IN ({$marks}) ORDER BY id FOR UPDATE");$lock->execute([(int)$booking['flight_id'],...$seatIds]);$locked=$lock->fetchAll();
            if(count($locked)!==count($seatIds))throw new RuntimeException('One or more selected seats do not belong to this flight.');
            $occupancy=$this->db->prepare("SELECT seat_id,booking_id FROM booking_seats WHERE seat_id IN ({$marks}) ORDER BY seat_id FOR UPDATE");$occupancy->execute($seatIds);$occupied=[];foreach($occupancy->fetchAll() as $row)$occupied[(int)$row['seat_id']]=(int)$row['booking_id'];
            foreach($locked as $seat){$seatId=(int)$seat['id'];if($seat['status']!=='available'||(isset($occupied[$seatId])&&$occupied[$seatId]!==$bookingId))throw new \App\Core\SeatUnavailable();}
            $this->db->prepare('DELETE FROM booking_seats WHERE booking_id=?')->execute([$bookingId]);
            $insert=$this->db->prepare('INSERT INTO booking_seats(booking_id,seat_id,passenger_id,status) VALUES(?,?,?,"reserved")');
            foreach($normalized as $passengerId=>$seatId)$insert->execute([$bookingId,$seatId,$passengerId]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
