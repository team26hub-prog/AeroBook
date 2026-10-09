<?php
declare(strict_types=1);
final class TestHttpServer{
    public string $url;
    private mixed $process;
    public function __construct(TestEnvironment $environment){
        $socket=stream_socket_server('tcp://127.0.0.1:0',$code,$message);if(!$socket)throw new RuntimeException($message);
        $address=stream_socket_get_name($socket,false);fclose($socket);$this->url='http://'.$address;
        $env=array_merge(getenv(),$environment->env,['DB_DATABASE'=>$environment->name,'APP_ENV'=>'testing','APP_DEBUG'=>'false','APP_URL'=>$this->url]);
        $this->process=proc_open([PHP_BINARY,'-d','session.save_path='.$environment->runtime,'-S',$address,'-t',BASE_PATH.'/public',BASE_PATH.'/tests/support/http-router.php'],[0=>['pipe','r'],1=>['file',$environment->runtime.'/http.log','a'],2=>['file',$environment->runtime.'/http.log','a']],$pipes,BASE_PATH,$env);
        if(!is_resource($this->process))throw new RuntimeException('Cannot start test HTTP server');fclose($pipes[0]);
        for($i=0;$i<40;$i++){if(@file_get_contents($this->url.'/login')!==false)return;usleep(100000);}
        $this->stop();throw new RuntimeException('Test HTTP server did not start');
    }
    public function stop():void{if(is_resource($this->process)){proc_terminate($this->process);proc_close($this->process);$this->process=null;}}
    public function __destruct(){$this->stop();}
}
final class TestClient{
    public array $cookies=[];
    public function __construct(private string $url){}
    public function request(string $path,array $data=[],string $method='GET'):array{
        $cookie=implode('; ',array_map(static fn($key,$value)=>$key.'='.$value,array_keys($this->cookies),$this->cookies));
        $context=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>10,'header'=>"Content-Type: application/x-www-form-urlencoded\r\nCookie: {$cookie}\r\n",'content'=>$method==='POST'?http_build_query($data):'']]);
        $body=file_get_contents($this->url.$path,false,$context);$headers=$http_response_header??[];
        preg_match('/\s(\d{3})\s/',$headers[0]??'',$match);$parsed=[];
        foreach($headers as $header){if(!str_contains($header,':'))continue;[$name,$value]=explode(':',$header,2);$parsed[strtolower($name)]=trim($value);
            if(strtolower($name)==='set-cookie'){[$pair]=explode(';',trim($value));[$key,$value]=explode('=',$pair,2);$this->cookies[$key]=$value;}}
        return ['status'=>(int)($match[1]??0),'headers'=>$parsed,'body'=>(string)$body];
    }
    public function token(string $path='/login'):string{
        $response=$this->request($path);equal($response['status'],200);
        expect((bool)preg_match('/name="_csrf" value="([a-f0-9]{64})"/',$response['body'],$match),'CSRF token not found');return $match[1];
    }
    public function login(string $email='one@example.test'):void{
        $response=$this->request('/login',['_csrf'=>$this->token(),'email'=>$email,'password'=>'Password123!'],'POST');equal($response['status'],303);
        equal($response['headers']['location'],$email==='admin@example.test'?'/admin':'/account');
    }
}
