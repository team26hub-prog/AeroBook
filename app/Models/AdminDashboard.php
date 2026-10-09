<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;

final class AdminDashboard extends Model
{
    public function data(): array
    {
        $today=new DateTimeImmutable('today');
        $until=$today->modify('+1 day')->format('Y-m-d H:i:s');
        $daily=new DateTimeImmutable('2026-10-08');
        $weekly=$daily;
        $monthly=$daily->modify('first day of this month');
        $days=$today<$daily?0:(int)$daily->diff($today)->days+1;
        $months=$days===0?0:((int)$today->format('Y')-(int)$monthly->format('Y'))*12+(int)$today->format('n')-(int)$monthly->format('n')+1;
        $trends=[];
        foreach([
            'daily'=>[$daily,$days,'+1 day','DATE(booked_at)','d M Y'],
            'weekly'=>[$weekly,(int)ceil($days/7),'+1 week',"DATE_ADD('2026-10-08', INTERVAL (FLOOR(DATEDIFF(booked_at, '2026-10-08') / 7) * 7) DAY)",'d M Y'],
            'monthly'=>[$monthly,$months,'+1 month',"DATE_FORMAT(booked_at, '%Y-%m-01')",'M Y'],
        ] as $period=>[$start,$length,$step,$group,$label]){
            $q=$this->db->prepare("SELECT {$group} bucket, COUNT(*) total FROM bookings WHERE booked_at>=? AND booked_at<? GROUP BY bucket ORDER BY bucket");
            $q->execute([$daily->format('Y-m-d H:i:s'),$until]);
            $trends[$period]=$this->series($q->fetchAll(),$start,$length,$step,$label);
        }
        // Payments are aggregated directly, so passenger and seat joins cannot inflate revenue.
        $q=$this->db->prepare("SELECT DATE_FORMAT(COALESCE(paid_at,created_at),'%Y-%m-01') bucket, SUM(amount) total
            FROM payments WHERE status='verified' AND currency='PKR'
            AND COALESCE(paid_at,created_at)>=? AND COALESCE(paid_at,created_at)<? GROUP BY bucket ORDER BY bucket");
        $q->execute([$daily->format('Y-m-d H:i:s'),$until]);
        $revenue=$this->series($q->fetchAll(),$monthly,$months,'+1 month','M Y',true);
        $bookingStatuses=$this->statuses('bookings',['pending','confirmed','cancelled','completed','expired']);
        $paymentStatuses=$this->statuses('payments',['pending','submitted','verified','rejected','refunded','failed']);
        return [
            'trends'=>$trends,'revenue'=>$revenue,'bookingStatuses'=>$bookingStatuses,'paymentStatuses'=>$paymentStatuses,
            'updatedAt'=>(new DateTimeImmutable())->format(DATE_ATOM),
            'timezone'=>date_default_timezone_get(),
        ];
    }

    private function series(array $rows,DateTimeImmutable $start,int $length,string $step,string $label,bool $money=false): array
    {
        $totals=array_column($rows,'total','bucket');$labels=[];$values=[];
        for($i=0;$i<$length;$i++){
            $labels[]=$start->format($label);$value=$totals[$start->format('Y-m-d')]??0;
            $values[]=$money?round((float)$value,2):(int)$value;
            $start=$start->modify($step);
        }
        return ['labels'=>$labels,'values'=>$values];
    }

    private function statuses(string $table,array $statuses): array
    {
        $rows=$this->db->query("SELECT status,COUNT(*) total FROM {$table} GROUP BY status")->fetchAll();
        $totals=array_column($rows,'total','status');
        return ['labels'=>array_map('ucfirst',$statuses),'values'=>array_map(static fn($s)=>(int)($totals[$s]??0),$statuses)];
    }
}
