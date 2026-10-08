<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\AdminModel;
use RuntimeException;

final class AdminController extends Controller
{
    private AdminModel $model;
    public function __construct() { $this->model=new AdminModel(); }

    public function dashboard(): void { $this->page('dashboard'); }
    public function airlines(): void { $this->page('airlines'); }
    public function airports(): void { $this->page('airports'); }
    public function flights(): void { $this->page('flights'); }
    public function seats(): void { $this->page('seats'); }
    public function bookings(): void { $this->page('bookings'); }
    public function payments(): void { $this->page('payments'); }

    private function page(string $section): void
    {
        try {
            $this->view('admin/panel',['section'=>$section,'title'=>ucfirst($section),'csrf'=>Csrf::token(),'adminName'=>Auth::name(),'rows'=>$section==='dashboard'?[]:$this->model->all($section),'stats'=>$section==='dashboard'?$this->model->dashboard():[],'options'=>$this->model->options(),'errors'=>Session::pullFlash('errors',[]),'success'=>Session::pullFlash('success')]);
        } catch (\Throwable $e) { error_log('Admin page error: '.$e->getMessage()); http_response_code(500);$this->view('admin/error'); }
    }

    public function save(): void
    {
        $this->requireValidCsrf();$kind=(string)($_POST['kind']??'');
        try {
            if(in_array($kind,['airlines','airports','flights'],true))$this->model->save($kind,$_POST);
            elseif($kind==='seats')$this->model->generateSeats((int)($_POST['flight_id']??0),(int)($_POST['rows']??0),(array)($_POST['letters']??[]),(string)($_POST['cabin_class']??''));
            elseif($kind==='seat_status')$this->model->seatStatus((int)($_POST['flight_id']??0),(string)($_POST['status']??''));
            elseif($kind==='booking_status')$this->model->bookingStatus((int)($_POST['id']??0),(string)($_POST['status']??''));
            elseif($kind==='payment')$this->model->reviewPayment((int)($_POST['id']??0),(string)($_POST['decision']??''));
            else throw new RuntimeException('Unknown admin action.');
            Session::flash('success','Changes saved successfully.');
        }catch(\Throwable $e){error_log('Admin action error: '.$e->getMessage());Session::flash('errors',[($e instanceof RuntimeException&&!($e instanceof \PDOException))?$e->getMessage():'Could not save changes. Check the details and try again.']);}
        $back=(string)($_POST['return_to']??'/admin');if(!in_array($back,['/admin','/admin/airlines','/admin/airports','/admin/flights','/admin/seats','/admin/bookings','/admin/payments'],true))$back='/admin';$this->redirect($back);
    }

    public function proof(): void
    {
        $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);$relative=$id?$this->model->proofPath((int)$id):null;
        if(!$relative){http_response_code(404);exit;}
        $base=realpath(BASE_PATH.'/storage/payment-proofs');$file=realpath(BASE_PATH.'/storage/payment-proofs/'.basename($relative));
        if(!$base||!$file||!str_starts_with($file,$base.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit;}
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file);if(!in_array($mime,['image/jpeg','image/png','image/webp','application/pdf'],true)){http_response_code(415);exit;}
        header('Content-Type: '.$mime);header('Content-Length: '.(string)filesize($file));header('X-Content-Type-Options: nosniff');header('Content-Disposition: attachment; filename="payment-proof-'.$id.'.'.($mime==='application/pdf'?'pdf':'img').'"');readfile($file);
    }
}
