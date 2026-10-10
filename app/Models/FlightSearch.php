<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class FlightSearch extends Model
{
    public function seatAvailability(array $ids): array
    {
        if($ids===[])return [];
        $placeholders=implode(',',array_fill(0,count($ids),'?'));
        $query=$this->db->prepare("SELECT f.id,COUNT(s.id) available_seats
            FROM flights f JOIN airlines al ON al.id=f.airline_id
            JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            LEFT JOIN seats s ON s.flight_id=f.id AND s.status='available'
                AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id)
            WHERE f.id IN ({$placeholders}) AND f.departure_at>=NOW()
                AND f.status IN ('scheduled','boarding') AND al.status='active' AND da.status='active' AND aa.status='active'
            GROUP BY f.id");
        $query->execute($ids);
        return array_map(static fn($row)=>['id'=>(int)$row['id'],'available_seats'=>(int)$row['available_seats']],$query->fetchAll());
    }

    public function airports(): array
    {
        return $this->db->query("SELECT id,name,iata_code,city,country FROM airports WHERE status='active' ORDER BY city,name")->fetchAll();
    }

    public function allAvailable(): array
    {
        $sql = $this->baseSelect() . " WHERE f.departure_at>=NOW()
            AND f.status IN ('scheduled','boarding') AND da.status='active' AND aa.status='active' AND al.status='active'
            AND (SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id AND s.status='available'
                 AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id))>0
            ORDER BY f.departure_at ASC, f.id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function search(int $departureId, int $arrivalId, string $date): array
    {
        $from = $date . ' 00:00:00';
        $until = (new \DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00');
        $sql = $this->baseSelect() . " WHERE f.departure_airport_id=:departure AND f.arrival_airport_id=:arrival
            AND f.departure_at>=:from_date AND f.departure_at<:until_date AND f.departure_at>=NOW()
            AND f.status IN ('scheduled','boarding') AND da.status='active' AND aa.status='active' AND al.status='active'
            AND (SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id AND s.status='available'
                 AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id))>0
            ORDER BY f.departure_at ASC";
        $statement = $this->db->prepare($sql);
        $statement->execute(['departure'=>$departureId,'arrival'=>$arrivalId,'from_date'=>$from,'until_date'=>$until]);
        return $statement->fetchAll();
    }

    public function departingFrom(int $departureId): array
    {
        $sql=$this->baseSelect()." WHERE f.departure_airport_id=:departure AND f.departure_at>=NOW()
            AND f.status IN ('scheduled','boarding') AND da.status='active' AND aa.status='active' AND al.status='active'
            AND (SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id AND s.status='available'
                 AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id))>0
            ORDER BY f.departure_at ASC,f.id ASC";
        $statement=$this->db->prepare($sql);
        $statement->execute(['departure'=>$departureId]);
        return $statement->fetchAll();
    }

    public function findAvailable(int $id): ?array
    {
        $flight=$this->findUpcoming($id);
        return $flight!==null&&(int)$flight['available_seats']>0?$flight:null;
    }

    public function findUpcoming(int $id): ?array
    {
        $sql = $this->baseSelect() . " WHERE f.id=:id AND f.departure_at>=NOW()
            AND f.status IN ('scheduled','boarding') AND da.status='active' AND aa.status='active' AND al.status='active'";
        $statement=$this->db->prepare($sql);$statement->execute(['id'=>$id]);$flight=$statement->fetch();
        return $flight===false?null:$flight;
    }

    private function baseSelect(): string
    {
        return 'SELECT f.id,f.flight_number,f.departure_at,f.arrival_at,f.base_fare,f.currency,f.status,
            al.name airline_name,al.iata_code airline_code,
            da.id departure_id,da.name departure_name,da.iata_code departure_code,da.city departure_city,da.country departure_country,da.timezone departure_timezone,
            aa.id arrival_id,aa.name arrival_name,aa.iata_code arrival_code,aa.city arrival_city,aa.country arrival_country,aa.timezone arrival_timezone,
            TIMESTAMPDIFF(MINUTE,f.departure_at,f.arrival_at) duration_minutes,
            (SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id AND s.status="available"
             AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id)) available_seats
            FROM flights f JOIN airlines al ON al.id=f.airline_id
            JOIN airports da ON da.id=f.departure_airport_id
            JOIN airports aa ON aa.id=f.arrival_airport_id';
    }
}
