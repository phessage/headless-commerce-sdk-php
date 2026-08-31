<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class Client {
    /** @param null|callable(string,array<string,string>):array{status:int,body:string} $transport */
    public function __construct(private readonly string $baseUrl, private readonly string $publishableKey, private readonly mixed $transport = null, private readonly int $maxRetries = 2) {
        if ($baseUrl === '' || !str_starts_with($publishableKey, 'pk_')) throw new \InvalidArgumentException('A base URL and publishable key are required');
    }
    /** @return array{data:list<array<string,mixed>>,nextCursor:?string,requestId:string} */
    public function listProducts(int $limit = 20, ?string $cursor = null, ?string $query = null): array {
        $params=['limit'=>(string)max(1,min(100,$limit))]; if($cursor!==null)$params['cursor']=$cursor;if($query!==null&&trim($query)!=='')$params['query']=trim($query);
        $url=rtrim($this->baseUrl,'/').'/v1/headless/products?'.http_build_query($params);
        for($attempt=0;$attempt<=$this->maxRetries;$attempt++){
            $response=$this->send($url); $status=$response['status'];
            if($status>=200&&$status<300){$data=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);return $data;}
            if(in_array($status,[429,502,503,504],true)&&$attempt<$this->maxRetries)continue;
            $problem=json_decode($response['body'],true)?:[];throw new ProblemException($status,$problem['type']??'about:blank',$problem['requestId']??null,$problem['detail']??$problem['title']??'Request failed');
        } throw new \LogicException('Unreachable');
    }
    /** @return array{status:int,body:string} */
    private function send(string $url): array {
        if(is_callable($this->transport))return ($this->transport)($url,['Accept'=>'application/json','x-publishable-key'=>$this->publishableKey]);
        $context=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'timeout'=>10,'header'=>"Accept: application/json\r\nx-publishable-key: {$this->publishableKey}\r\n"]]);
        $body=file_get_contents($url,false,$context);$line=$http_response_header[0]??'HTTP/1.1 500';preg_match('/\s(\d{3})\s/',$line,$match);return ['status'=>(int)($match[1]??500),'body'=>$body===false?'':$body];
    }
}
