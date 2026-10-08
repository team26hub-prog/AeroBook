<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;
use RuntimeException;

final class ManualPayment extends Model
{
    public function bookingsForCustomer(int $customerId): array
    {
        $q=$this->db->prepare("SELECT b.id,b.pnr,b.status,b.currency,b.total_amount,b.booked_at,f.flight_number,f.departure_at,
            da.iata_code departure_code,aa.iata_code arrival_code,
            (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) passenger_count,
            (SELECT COUNT(DISTINCT bs.passenger_id) FROM booking_seats bs WHERE bs.booking_id=b.id AND bs.passenger_id IS NOT NULL) assigned_count,
            (SELECT p.status FROM payments p WHERE p.booking_id=b.id ORDER BY p.id DESC LIMIT 1) latest_payment_status,
            (SELECT p.id FROM payments p WHERE p.booking_id=b.id ORDER BY p.id DESC LIMIT 1) latest_payment_id
            FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            WHERE b.user_id=? ORDER BY b.created_at DESC");
        $q->execute([$customerId]);return $q->fetchAll();
    }

    public function booking(int $bookingId,int $customerId): ?array
    {
        $q=$this->db->prepare("SELECT b.id,b.pnr,b.status,b.currency,b.total_amount,b.booked_at,b.flight_id,f.flight_number,f.departure_at,f.arrival_at,
            al.name airline_name,da.iata_code departure_code,da.city departure_city,aa.iata_code arrival_code,aa.city arrival_city,
            (SELECT COUNT(*) FROM passengers p WHERE p.booking_id=b.id) passenger_count,
            (SELECT COUNT(DISTINCT bs.passenger_id) FROM booking_seats bs WHERE bs.booking_id=b.id AND bs.passenger_id IS NOT NULL) assigned_count
            FROM bookings b JOIN flights f ON f.id=b.flight_id JOIN airlines al ON al.id=f.airline_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id
            WHERE b.id=? AND b.user_id=? LIMIT 1");
        $q->execute([$bookingId,$customerId]);$booking=$q->fetch();return $booking===false?null:$booking;
    }

    public function passengers(int $bookingId): array
    {
        $q=$this->db->prepare('SELECT p.first_name,p.last_name,s.seat_number FROM passengers p LEFT JOIN booking_seats bs ON bs.booking_id=p.booking_id AND bs.passenger_id=p.id LEFT JOIN seats s ON s.id=bs.seat_id WHERE p.booking_id=? ORDER BY p.id');
        $q->execute([$bookingId]);return $q->fetchAll();
    }

    public function history(int $bookingId,int $customerId): array
    {
        $q=$this->db->prepare('SELECT p.id,p.amount,p.currency,p.method,p.status,p.sender_name,p.transaction_reference,p.paid_at,p.remarks,p.created_at FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE p.booking_id=? AND b.user_id=? ORDER BY p.id DESC');
        $q->execute([$bookingId,$customerId]);return $q->fetchAll();
    }

    public function submit(int $bookingId,int $customerId,array $input): void
    {
        $method=(string)($input['method']??'');
        if(!in_array($method,['bank_transfer','bank_deposit','mobile_wallet','cash','other'],true))throw new RuntimeException('Choose one of the available manual payment methods.');
        $sender=trim($this->scalar($input['sender_name']??null));
        if($sender===''||$this->length($sender)>150)throw new RuntimeException('Enter the sender or account holder name (maximum 150 characters).');
        $reference=trim($this->scalar($input['transaction_reference']??null));
        if($reference===''||$this->length($reference)>100)throw new RuntimeException('Enter a transaction or reference ID (maximum 100 characters).');
        $amount=trim($this->scalar($input['amount']??null));
        if(!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/',$amount)||(float)$amount<=0)throw new RuntimeException('Enter a valid paid amount.');
        $paidAtInput=$this->scalar($input['paid_at']??null);
        $paidAt=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$paidAtInput);
        $dateErrors=DateTimeImmutable::getLastErrors();
        if(!$paidAt||($dateErrors!==false&&($dateErrors['warning_count']||$dateErrors['error_count']))||$paidAt->format('Y-m-d\TH:i')!==$paidAtInput||$paidAt>new DateTimeImmutable('now'))throw new RuntimeException('Enter a valid payment date and time that is not in the future.');
        $remarks=trim($this->scalar($input['remarks']??null));
        if($this->length($remarks)>2000)throw new RuntimeException('Remarks must be 2,000 characters or fewer.');
        $amount=number_format((float)$amount,2,'.','');

        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare('SELECT id,status,total_amount,booked_at FROM bookings WHERE id=? AND user_id=? FOR UPDATE');
            $q->execute([$bookingId,$customerId]);$booking=$q->fetch();
            if(!$booking||$booking['status']!=='pending')throw new RuntimeException('Only a pending booking can receive a payment submission.');
            $paidTime=$paidAt->format('Y-m-d H:i:s');
            if($paidAt->format('Y-m-d H:i')<substr($booking['booked_at'],0,16))throw new RuntimeException('Payment date and time cannot be earlier than the booking.');
            $counts=$this->db->prepare('SELECT (SELECT COUNT(*) FROM passengers WHERE booking_id=?) passenger_count,(SELECT COUNT(DISTINCT passenger_id) FROM booking_seats WHERE booking_id=? AND passenger_id IS NOT NULL) assigned_count');
            $counts->execute([$bookingId,$bookingId]);$seatCounts=$counts->fetch();
            if(!$seatCounts||(int)$seatCounts['passenger_count']<1||(int)$seatCounts['assigned_count']!==(int)$seatCounts['passenger_count'])throw new RuntimeException('Select and save a seat for each passenger before submitting payment.');
            $last=$this->db->prepare('SELECT status FROM payments WHERE booking_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE');
            $last->execute([$bookingId]);$latestStatus=$last->fetchColumn();
            if(in_array($latestStatus,['pending','submitted','verified'],true))throw new RuntimeException($latestStatus==='verified'?'This booking already has a verified payment.':'A payment for this booking is already awaiting admin verification.');
            $insert=$this->db->prepare('INSERT INTO payments(booking_id,amount,currency,method,status,sender_name,transaction_reference,paid_at,remarks) VALUES(?,?,?,?,"pending",?,?,?,?)');
            $insert->execute([$bookingId,$amount,$booking['currency'],$method,$sender,$reference,$paidTime,$remarks===''?null:$remarks]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    private function scalar(mixed $value): string { return is_scalar($value)?(string)$value:''; }
    private function length(string $value): int { $n=preg_match_all('/./us',$value);return $n===false?PHP_INT_MAX:$n; }
}
