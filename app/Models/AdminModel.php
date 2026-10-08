<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use RuntimeException;

final class AdminModel extends Model
{
    public function dashboard(): array
    {
        $counts = [];
        foreach (['bookings', 'flights', 'payments', 'users'] as $table) {
            $counts[$table] = (int) $this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
        }
        $counts['pending_payments'] = (int) $this->db->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','submitted')")->fetchColumn();
        $counts['revenue'] = (string) $this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='verified'")->fetchColumn();
        $counts['recent_bookings'] = $this->db->query('SELECT b.id,b.pnr,b.status,b.total_amount,b.currency,b.booked_at,u.full_name,f.flight_number FROM bookings b JOIN users u ON u.id=b.user_id JOIN flights f ON f.id=b.flight_id ORDER BY b.created_at DESC LIMIT 8')->fetchAll();
        return $counts;
    }

    public function all(string $section): array
    {
        return match ($section) {
            'airlines' => $this->db->query('SELECT * FROM airlines ORDER BY name')->fetchAll(),
            'airports' => $this->db->query('SELECT * FROM airports ORDER BY city,name')->fetchAll(),
            'flights' => $this->db->query('SELECT f.*,al.name airline,da.iata_code departure,aa.iata_code arrival,(SELECT COUNT(*) FROM seats s WHERE s.flight_id=f.id) capacity,(SELECT COUNT(*) FROM booking_seats bs JOIN seats s ON s.id=bs.seat_id WHERE s.flight_id=f.id) reserved FROM flights f JOIN airlines al ON al.id=f.airline_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id ORDER BY f.departure_at DESC')->fetchAll(),
            'seats' => $this->db->query('SELECT f.id flight_id,f.flight_number,f.departure_at,da.iata_code departure,aa.iata_code arrival,COUNT(s.id) capacity,SUM(s.status="available") available,SUM(s.status="blocked") blocked FROM flights f JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id LEFT JOIN seats s ON s.flight_id=f.id GROUP BY f.id ORDER BY f.departure_at DESC')->fetchAll(),
            'bookings' => $this->db->query('SELECT b.*,u.full_name,u.email,f.flight_number,da.iata_code departure,aa.iata_code arrival,(SELECT GROUP_CONCAT(CONCAT(p.first_name," ",p.last_name," · ",p.gender," · DOB ",p.date_of_birth,IF(p.passport_number IS NULL,"",CONCAT(" · Passport ",p.passport_number))) SEPARATOR " | ") FROM passengers p WHERE p.booking_id=b.id) passengers FROM bookings b JOIN users u ON u.id=b.user_id JOIN flights f ON f.id=b.flight_id JOIN airports da ON da.id=f.departure_airport_id JOIN airports aa ON aa.id=f.arrival_airport_id ORDER BY b.created_at DESC')->fetchAll(),
            'payments' => $this->db->query('SELECT p.*,b.pnr,b.status booking_status,u.full_name,u.email FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN users u ON u.id=b.user_id ORDER BY p.created_at DESC')->fetchAll(),
            default => [],
        };
    }

    public function options(): array
    {
        return ['airlines'=>$this->db->query("SELECT id,name FROM airlines WHERE status='active' ORDER BY name")->fetchAll(), 'airports'=>$this->db->query("SELECT id,name,iata_code,city FROM airports WHERE status='active' ORDER BY city,name")->fetchAll(), 'flights'=>$this->db->query('SELECT id,flight_number FROM flights ORDER BY departure_at DESC')->fetchAll()];
    }

    public function save(string $kind, array $v): void
    {
        if ($kind === 'airlines') {
            $iata=strtoupper($this->required($v,'iata_code',2));$icao=$this->optional($v,'icao_code',3);
            if(!preg_match('/^[A-Z]{2}$/',$iata)||($icao!==null&&!preg_match('/^[A-Z]{3}$/',strtoupper($icao))))throw new RuntimeException('Enter a valid two-letter IATA and optional three-letter ICAO code.');
            $data=[$this->required($v,'name',150),$iata,$icao===null?null:strtoupper($icao),$this->enum($v,'status',['active','inactive'])];
            $this->upsert($v,'airlines',['name','iata_code','icao_code','status'],$data); return;
        }
        if ($kind === 'airports') {
            $iata=strtoupper($this->required($v,'iata_code',3));$icao=$this->optional($v,'icao_code',4);
            if(!preg_match('/^[A-Z]{3}$/',$iata)||($icao!==null&&!preg_match('/^[A-Z]{4}$/',strtoupper($icao))))throw new RuntimeException('Enter a valid three-letter IATA and optional four-letter ICAO code.');
            $data=[$this->required($v,'name',180),$iata,$icao===null?null:strtoupper($icao),$this->required($v,'city',120),$this->required($v,'country',120),$this->required($v,'timezone',64),$this->enum($v,'status',['active','inactive'])];
            $this->upsert($v,'airports',['name','iata_code','icao_code','city','country','timezone','status'],$data); return;
        }
        if ($kind === 'flights') {
            $flightNumber=strtoupper($this->required($v,'flight_number',10));$currency=strtoupper($this->required($v,'currency',3));
            if(!preg_match('/^[A-Z0-9-]{2,10}$/',$flightNumber)||!preg_match('/^[A-Z]{3}$/',$currency))throw new RuntimeException('Enter a valid flight number and three-letter currency code.');
            $data=[$this->integer($v,'airline_id'),$flightNumber,$this->integer($v,'departure_airport_id'),$this->integer($v,'arrival_airport_id'),$this->date($v,'departure_at'),$this->date($v,'arrival_at'),$this->money($v,'base_fare'),$currency,$this->enum($v,'status',['scheduled','boarding','departed','completed','cancelled'])];
            if(min($data[0],$data[2],$data[3])<1 || $data[2]===$data[3] || strtotime($data[5])<=strtotime($data[4])) throw new RuntimeException('Choose valid airline, distinct airports, and arrival after departure.');
            $this->upsert($v,'flights',['airline_id','flight_number','departure_airport_id','arrival_airport_id','departure_at','arrival_at','base_fare','currency','status'],$data); return;
        }
        throw new RuntimeException('Unsupported record type.');
    }

    private function upsert(array $v,string $table,array $columns,array $data): void
    {
        $idRaw=$v['id']??null;$id=$idRaw===null||$idRaw===''?null:filter_var($idRaw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if ($idRaw!==null&&$idRaw!==''&&$id===false)throw new RuntimeException('Choose a valid record to update.');
        if ($id!==null&&$id!==false) { $sets=implode(',',array_map(fn($c)=>"{$c}=?",$columns)); $stmt=$this->db->prepare("UPDATE {$table} SET {$sets} WHERE id=?"); $stmt->execute([...$data,$id]);if($stmt->rowCount()===0){$check=$this->db->prepare("SELECT id FROM {$table} WHERE id=?");$check->execute([$id]);if(!$check->fetch())throw new RuntimeException('The record no longer exists. Refresh and try again.');} }
        else { $stmt=$this->db->prepare("INSERT INTO {$table} (".implode(',',$columns).") VALUES (".implode(',',array_fill(0,count($columns),'?')).")"); $stmt->execute($data); }
    }

    public function generateSeats(int $flight,int $rows,array $letters,string $cabin): int
    {
        if($flight<1||$rows<1||$rows>100||!$letters||count($letters)>10||!in_array($cabin,['economy','premium_economy','business','first'],true)) throw new RuntimeException('Provide a flight, 1–100 rows, seat letters, and a valid cabin.');
        foreach($letters as $letter)if(!is_string($letter)||!preg_match('/^[A-Ka-k]$/',$letter))throw new RuntimeException('Choose valid seat letters.');
        $letters=array_values(array_unique(array_map('strtoupper',$letters))); if(!$letters) throw new RuntimeException('Select at least one valid seat letter.');
        $this->db->beginTransaction(); try { $q=$this->db->prepare('SELECT id FROM flights WHERE id=? FOR UPDATE');$q->execute([$flight]);if(!$q->fetch())throw new RuntimeException('Flight not found.');$ins=$this->db->prepare('INSERT IGNORE INTO seats(flight_id,seat_number,cabin_class,status) VALUES(?,?,?,"available")');$n=0;for($r=1;$r<=$rows;$r++)foreach($letters as $letter){$ins->execute([$flight,$r.$letter,$cabin]);$n+=$ins->rowCount();}$this->db->commit();return $n;}catch(\Throwable $e){$this->db->rollBack();throw $e;}
    }

    public function seatStatus(int $flight,string $status): void
    {
        if($flight<1)throw new RuntimeException('Choose a valid flight.');
        if(!in_array($status,['available','blocked','unavailable'],true))throw new RuntimeException('Invalid seat status.');
        $q=$this->db->prepare("UPDATE seats s SET s.status=? WHERE s.flight_id=? AND NOT EXISTS(SELECT 1 FROM booking_seats bs WHERE bs.seat_id=s.id)");$q->execute([$status,$flight]);
    }

    public function bookingStatus(int $id,string $status): void
    {
        if($id<1)throw new RuntimeException('Choose a valid booking.');
        if(!in_array($status,['pending','confirmed','cancelled','completed','expired'],true))throw new RuntimeException('Invalid booking status.');
        $this->db->beginTransaction();try{
            $q=$this->db->prepare('SELECT b.status,f.departure_at FROM bookings b JOIN flights f ON f.id=b.flight_id WHERE b.id=? FOR UPDATE');$q->execute([$id]);$booking=$q->fetch();if(!$booking)throw new RuntimeException('Booking not found.');
            $allowed=match($booking['status']){'pending'=>['pending','cancelled','expired'],'confirmed'=>['confirmed','cancelled','completed'],'completed'=>['completed'],'cancelled'=>['cancelled'],'expired'=>['expired'],default=>[]};
            if(!in_array($status,$allowed,true))throw new RuntimeException('This booking status cannot be changed to the selected status.');
            if($status==='confirmed'){
                $payment=$this->db->prepare('SELECT COUNT(*) FROM payments WHERE booking_id=? AND status="verified"');$payment->execute([$id]);
                $seats=$this->db->prepare('SELECT COUNT(*) FROM passengers p JOIN booking_seats bs ON bs.booking_id=p.booking_id AND bs.passenger_id=p.id JOIN seats s ON s.id=bs.seat_id JOIN bookings b ON b.id=p.booking_id WHERE p.booking_id=? AND s.flight_id=b.flight_id');$seats->execute([$id]);
                $passengers=$this->db->prepare('SELECT COUNT(*) FROM passengers WHERE booking_id=?');$passengers->execute([$id]);$count=(int)$passengers->fetchColumn();
                if((int)$payment->fetchColumn()<1||$count<1||(int)$seats->fetchColumn()!==$count)throw new RuntimeException('A booking needs a verified payment and a valid seat for every passenger before confirmation.');
            }
            if($status==='completed'&&strtotime($booking['departure_at'])>time())throw new RuntimeException('A future flight cannot be marked completed.');
            $this->db->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$status,$id]);
            if(in_array($status,['cancelled','expired'],true)){$this->db->prepare('UPDATE e_tickets SET status="void" WHERE booking_id=? AND status="issued"')->execute([$id]);$this->db->prepare('UPDATE seats s JOIN booking_seats bs ON bs.seat_id=s.id SET s.status="available" WHERE bs.booking_id=?')->execute([$id]);$this->db->prepare('DELETE FROM booking_seats WHERE booking_id=?')->execute([$id]);}elseif($status==='confirmed')$this->db->prepare('UPDATE booking_seats SET status="confirmed" WHERE booking_id=?')->execute([$id]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function reviewPayment(int $id,string $decision): void
    {
        if(!in_array($decision,['verified','rejected'],true))throw new RuntimeException('Invalid payment decision.');
        $this->db->beginTransaction();try{
            $q=$this->db->prepare('SELECT p.*,b.status booking_status,b.flight_id booking_flight_id,b.total_amount booking_amount,b.currency booking_currency FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE p.id=? FOR UPDATE');$q->execute([$id]);$p=$q->fetch();if(!$p)throw new RuntimeException('Payment not found.');if(!in_array($p['status'],['pending','submitted'],true))throw new RuntimeException('This payment has already been reviewed.');
            $this->db->prepare('UPDATE payments SET status=?,paid_at=IF(?="verified",COALESCE(paid_at,NOW()),paid_at) WHERE id=?')->execute([$decision,$decision,$id]);
            if($decision==='verified'){
                if($p['booking_status']!=='pending')throw new RuntimeException('Only a pending booking can be confirmed by payment verification.');
                if(number_format((float)$p['amount'],2,'.','')!==number_format((float)$p['booking_amount'],2,'.','')||$p['currency']!==$p['booking_currency'])throw new RuntimeException('Payment amount or currency does not match the booking.');
                $duplicate=$this->db->prepare('SELECT id FROM payments WHERE booking_id=? AND status="verified" AND id<>? LIMIT 1');$duplicate->execute([$p['booking_id'],$id]);if($duplicate->fetch())throw new RuntimeException('Another payment is already verified for this booking.');
                $passengers=$this->db->prepare('SELECT p.id,bs.seat_id,s.flight_id seat_flight_id FROM passengers p LEFT JOIN booking_seats bs ON bs.booking_id=p.booking_id AND bs.passenger_id=p.id LEFT JOIN seats s ON s.id=bs.seat_id WHERE p.booking_id=? ORDER BY p.id FOR UPDATE');$passengers->execute([$p['booking_id']]);$passengerRows=$passengers->fetchAll();
                if(!$passengerRows)throw new RuntimeException('The booking has no passengers and cannot be ticketed.');
                foreach($passengerRows as $passenger)if($passenger['seat_id']===null||(int)$passenger['seat_flight_id']!==(int)$p['booking_flight_id'])throw new RuntimeException('Assign a valid seat to every passenger before verifying this payment.');
                $this->db->prepare('UPDATE bookings SET status="confirmed" WHERE id=?')->execute([$p['booking_id']]);$this->db->prepare('UPDATE booking_seats SET status="confirmed" WHERE booking_id=?')->execute([$p['booking_id']]);
                $existingTicket=$this->db->prepare('SELECT id FROM e_tickets WHERE passenger_id=? LIMIT 1');$ticketInsert=$this->db->prepare('INSERT INTO e_tickets(booking_id,passenger_id,ticket_number,status) VALUES(?,?,?,"issued")');
                foreach($passengerRows as $passenger){
                    $existingTicket->execute([$passenger['id']]);if($existingTicket->fetch())continue;
                    $created=false;for($attempt=0;$attempt<5&&!$created;$attempt++){
                        try{$ticketInsert->execute([$p['booking_id'],$passenger['id'],strtoupper(bin2hex(random_bytes(10)))]);$created=true;}
                        catch(\PDOException $e){if((int)($e->errorInfo[1]??0)!==1062||$attempt===4)throw $e;}
                    }
                    if(!$created)throw new RuntimeException('Could not create a unique e-ticket number. Please retry verification.');
                }
            }
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function proofPath(int $id): ?string { $q=$this->db->prepare('SELECT proof_path FROM payments WHERE id=?');$q->execute([$id]);$v=$q->fetchColumn();return is_string($v)&&$v!==''?$v:null; }
    public function passengers(int $id): array { $q=$this->db->prepare('SELECT * FROM passengers WHERE booking_id=? ORDER BY id');$q->execute([$id]);return $q->fetchAll(); }
    private function required(array $v,string $key,int $max): string { $raw=$v[$key]??null;$s=is_scalar($raw)?trim((string)$raw):'';if($s===''||strlen($s)>$max)throw new RuntimeException("Enter a valid {$key} (maximum {$max} characters).");return $s; }
    private function optional(array $v,string $key,int $max): ?string { $raw=$v[$key]??null;$s=is_scalar($raw)?trim((string)$raw):'';if($s==='' )return null;if(strlen($s)>$max)throw new RuntimeException("Invalid {$key}.");return $s; }
    private function enum(array $v,string $key,array $values): string { $raw=$v[$key]??null;$s=is_scalar($raw)?(string)$raw:'';if(!in_array($s,$values,true))throw new RuntimeException("Invalid {$key}.");return $s; }
    private function integer(array $v,string $key): int { $id=filter_var($v[$key]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new RuntimeException("Choose a valid {$key}.");return (int)$id; }
    private function money(array $v,string $key): string { $raw=$v[$key]??null;$s=is_scalar($raw)?(string)$raw:'';if(!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/',$s)||(float)$s<=0)throw new RuntimeException('Enter a valid positive fare.');return $s; }
    private function date(array $v,string $key): string { $raw=$v[$key]??null;$s=is_scalar($raw)?(string)$raw:'';$d=\DateTime::createFromFormat('!Y-m-d\TH:i',$s);$errors=\DateTime::getLastErrors();if(!$d||($errors!==false&&($errors['warning_count']||$errors['error_count']))||$d->format('Y-m-d\TH:i')!==$s)throw new RuntimeException('Enter valid flight times.');return $d->format('Y-m-d H:i:s'); }
}
