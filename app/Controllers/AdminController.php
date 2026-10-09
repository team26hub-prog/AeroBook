<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\AdminModel;
use App\Models\AdminDashboard;
use RuntimeException;

final class AdminController extends Controller
{
    private AdminModel $model;
    public function __construct() { $this->model=new AdminModel(); }

    public function dashboard(): void
    {
        if(($_GET['dashboard_data']??null)!=='1'){$this->page('dashboard');return;}
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        try{echo json_encode((new AdminDashboard())->data(),JSON_THROW_ON_ERROR);}
        catch(\Throwable $e){error_log('Admin chart refresh error: '.$e->getMessage());http_response_code(500);echo json_encode(['error'=>'Dashboard charts could not be refreshed.']);}
    }
    public function airlines(): void { $this->page('airlines'); }
    public function airports(): void { $this->page('airports'); }
    public function flights(): void { $this->page('flights'); }
    public function seats(): void { $this->page('seats'); }
    public function bookings(): void { $this->page('bookings'); }
    public function payments(): void { $this->page('payments'); }

    private function page(string $section): void
    {
        try {
            $charts=null;
            if($section==='dashboard'){
                try{$charts=(new AdminDashboard())->data();}
                catch(\Throwable $e){error_log('Admin chart data error: '.$e->getMessage());}
            }
            $this->view('admin/panel',['section'=>$section,'title'=>ucfirst($section),'csrf'=>Csrf::token(),'adminName'=>Auth::name(),'rows'=>$section==='dashboard'?[]:$this->model->all($section),'stats'=>$section==='dashboard'?$this->model->dashboard():[],'charts'=>$charts,'options'=>$this->model->options(),'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
        } catch (\Throwable $e) { error_log('Admin page error: '.$e->getMessage()); http_response_code(500);$this->view('admin/error'); }
    }

    public function save(): void
    {
        $this->requireValidCsrf();$kind=$this->scalar($_POST['kind']??null);
        try {
            if(in_array($kind,['airlines','airports','flights'],true))$this->model->save($kind,$_POST);
            elseif($kind==='seats')$this->model->generateSeats($this->positiveInt($_POST['flight_id']??null),$this->positiveInt($_POST['rows']??null),(is_array($_POST['letters']??null)?$_POST['letters']:[]),$this->scalar($_POST['cabin_class']??null));
            elseif($kind==='seat_status')$this->model->seatStatus($this->positiveInt($_POST['flight_id']??null),$this->scalar($_POST['status']??null));
            elseif($kind==='booking_status')$this->model->bookingStatus($this->positiveInt($_POST['id']??null),$this->scalar($_POST['status']??null));
            elseif($kind==='payment')$this->model->reviewPayment($this->positiveInt($_POST['id']??null),$this->scalar($_POST['decision']??null));
            else throw new RuntimeException('Unknown admin action.');
            $message=match($kind){'payment'=>$this->scalar($_POST['decision']??null)==='verified'?'Payment verified. The booking is confirmed and e-tickets are ready.':'Payment rejected. The booking remains unconfirmed.','booking_status'=>'Booking status updated.','seats'=>'Seat layout generated.','seat_status'=>'Seat availability updated.',default=>'Changes saved successfully.'};
            Session::flash('success',$message);
        }catch(\Throwable $e){error_log('Admin action error: '.$e->getMessage());Session::flash('errors',[($e instanceof RuntimeException&&!($e instanceof \PDOException))?$e->getMessage():'Could not save changes. Check the details and try again.']);}
        $back=$this->scalar($_POST['return_to']??null);if(!in_array($back,['/admin','/admin/airlines','/admin/airports','/admin/flights','/admin/seats','/admin/bookings','/admin/payments'],true))$back='/admin';$this->redirect($back);
    }

    public function proof(): void
    {
        $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);$relative=$id?$this->model->proofPath((int)$id):null;
        if(!$relative){\App\Core\HttpError::render(404);return;}
        $base=realpath(BASE_PATH.'/storage/payment-proofs');$file=realpath(BASE_PATH.'/storage/payment-proofs/'.basename($relative));
        if(!$base||!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){\App\Core\HttpError::render(404);return;}
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file);if(!in_array($mime,['image/jpeg','image/png','image/webp','application/pdf'],true)){\App\Core\HttpError::render(415,'This payment proof format cannot be downloaded.');return;}
        header('Content-Type: '.$mime);header('Content-Length: '.(string)filesize($file));header('X-Content-Type-Options: nosniff');header('Content-Disposition: attachment; filename="payment-proof-'.$id.'.'.($mime==='application/pdf'?'pdf':'img').'"');readfile($file);
    }

    private function scalar(mixed $value): string { return is_scalar($value)?(string)$value:''; }
    private function positiveInt(mixed $value): int { $validated=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($validated===false)throw new RuntimeException('Choose a valid record.');return (int)$validated; }
}
