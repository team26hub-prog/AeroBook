<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ETicket extends Model
{
    public function forCustomer(int $customerId): array
    {
        $q=$this->db->prepare("SELECT t.id,t.ticket_number,t.status ticket_status,t.issued_at,
            b.id booking_id,b.pnr,b.status booking_status,b.total_amount booking_total,b.currency,
            (SELECT py.amount FROM payments py WHERE py.booking_id=b.id AND py.status='verified' ORDER BY py.id DESC LIMIT 1) payment_amount,
            p.first_name,p.last_name,p.date_of_birth,p.gender,
            al.name airline_name,f.flight_number,f.departure_at,f.arrival_at,
            da.name departure_airport,da.iata_code departure_code,da.city departure_city,
            aa.name arrival_airport,aa.iata_code arrival_code,aa.city arrival_city,s.seat_number
            FROM e_tickets t JOIN bookings b ON b.id=t.booking_id JOIN passengers p ON p.id=t.passenger_id AND p.booking_id=t.booking_id
            JOIN flights f ON f.id=b.flight_id JOIN airlines al ON al.id=f.airline_id
            JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            LEFT JOIN booking_seats bs ON bs.booking_id=b.id AND bs.passenger_id=p.id LEFT JOIN seats s ON s.id=bs.seat_id
            WHERE b.user_id=? ORDER BY t.issued_at DESC,p.last_name,p.first_name");
        $q->execute([$customerId]);return $q->fetchAll();
    }
}
