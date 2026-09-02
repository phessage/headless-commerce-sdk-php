<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class Client {
    /** @param null|callable(string,array<string,string>,string,?string):array{status:int,body:string,headers?:array<string,string>} $transport */
    public function __construct(private readonly string $baseUrl, private readonly string $publishableKey, private readonly mixed $transport = null, private readonly int $maxRetries = 2) {
        if ($baseUrl === '' || !str_starts_with($publishableKey, 'pk_')) throw new \InvalidArgumentException('A base URL and publishable key are required');
    }
    public static function forStore(string $storeId,string $bootstrapUrl='https://api.1ecomm.com',mixed $transport=null,int $maxRetries=2):self {
        $url=rtrim($bootstrapUrl,'/').'/v1/headless/stores/'.rawurlencode($storeId).'/config';
        if(is_callable($transport))$response=$transport($url,['Accept'=>'application/json'],'GET',null);else{$context=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'timeout'=>10,'header'=>"Accept: application/json\r\n"]]);$body=file_get_contents($url,false,$context);$line=$http_response_header[0]??'HTTP/1.1 500';preg_match('/\s(\d{3})\s/',$line,$match);$response=['status'=>(int)($match[1]??500),'body'=>$body===false?'':$body];}
        if($response['status']!==200)throw new \RuntimeException('Headless store bootstrap failed');
        $runtime=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR)['data']??[];
        if(($runtime['storeId']??null)!==$storeId||!str_starts_with((string)($runtime['publishableKey']??''),'pk_'))throw new \RuntimeException('Invalid headless store bootstrap response');
        return new self((string)$runtime['apiUrl'],(string)$runtime['publishableKey'],$transport,$maxRetries);
    }
    /** @return array{data:list<array<string,mixed>>,nextCursor:?string,requestId:string} */
    public function listProducts(int $limit = 20, ?string $cursor = null, ?string $query = null): array {
        $params=['limit'=>(string)max(1,min(100,$limit))]; if($cursor!==null)$params['cursor']=$cursor;if($query!==null&&trim($query)!=='')$params['query']=trim($query);
        $url=rtrim($this->baseUrl,'/').'/v1/headless/products?'.http_build_query($params);
        return $this->get($url);
    }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function getProduct(string $id): array { return $this->get(rtrim($this->baseUrl,'/').'/v1/headless/products/'.rawurlencode($id)); }
    /** @return array{data:list<array<string,mixed>>,requestId:string} */
    public function listCategories(): array { return $this->get(rtrim($this->baseUrl,'/').'/v1/headless/products/categories'); }
    /** @return array{data:array<string,mixed>,cartToken:string,created:true,requestId:string} */
    public function createCart(): array { return $this->request('POST',rtrim($this->baseUrl,'/').'/v1/headless/carts',null,null,false); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function getCart(string $cartToken): array { return $this->cartRequest('GET','/v1/headless/carts/current',$cartToken); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function addCartItem(string $cartToken,string $productId,int $quantity=1,?string $variantId=null): array {$body=['productId'=>$productId,'quantity'=>$quantity];if($variantId!==null)$body['variantId']=$variantId;return $this->cartRequest('POST','/v1/headless/carts/current/items',$cartToken,$body);}
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function updateCartItem(string $cartToken,string $itemId,int $quantity): array { return $this->cartRequest('PATCH','/v1/headless/carts/current/items/'.rawurlencode($itemId),$cartToken,['quantity'=>$quantity]); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function removeCartItem(string $cartToken,string $itemId): array { return $this->cartRequest('DELETE','/v1/headless/carts/current/items/'.rawurlencode($itemId),$cartToken); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function getCheckoutPreparation(string $cartToken): array { return $this->cartRequest('GET','/v1/headless/carts/current/checkout',$cartToken); }
    /** @param array<string,mixed> $details @return array{data:array<string,mixed>,requestId:string} */
    public function updateCheckoutDetails(string $cartToken,array $details): array { return $this->cartRequest('PATCH','/v1/headless/carts/current/checkout',$cartToken,$details); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function selectCheckoutShippingMethod(string $cartToken,string $id): array { return $this->cartRequest('PUT','/v1/headless/carts/current/checkout/shipping-method',$cartToken,['id'=>$id]); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function selectCheckoutPaymentMethod(string $cartToken,string $id): array { return $this->cartRequest('PUT','/v1/headless/carts/current/checkout/payment-method',$cartToken,['id'=>$id]); }
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function placeOrder(string $cartToken,string $idempotencyKey): array {$key=trim($idempotencyKey);if($key===''||strlen($key)>120)throw new \InvalidArgumentException('An idempotency key of 1-120 characters is required');if(!str_starts_with($cartToken,'hc_'))throw new \InvalidArgumentException('A cart capability token is required');return $this->request('POST',rtrim($this->baseUrl,'/').'/v1/headless/carts/current/checkout/order',null,$cartToken,true,['Idempotency-Key'=>$key]);}
    /** @return array{data:array<string,mixed>,requestId:string} */
    public function lookupOrder(string $orderNumber,string $email): array {$number=trim($orderNumber);$address=trim($email);if($number===''||$address===''||filter_var($address,FILTER_VALIDATE_EMAIL)===false)throw new \InvalidArgumentException('An order number and valid checkout email are required');return $this->request('POST',rtrim($this->baseUrl,'/').'/v1/headless/orders/lookup',['orderNumber'=>$number,'email'=>$address],null,false);}
    /** @return array<string,mixed> */
    private function get(string $url): array {
        for($attempt=0;$attempt<=$this->maxRetries;$attempt++){
            $response=$this->send($url,'GET',null,null); $status=$response['status'];
            if($status>=200&&$status<300){$data=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);return $data;}
            if(in_array($status,[429,502,503,504],true)&&$attempt<$this->maxRetries){$this->waitBeforeRetry($response,$attempt);continue;}
            $problem=json_decode($response['body'],true)?:[];throw new ProblemException($status,$problem['type']??'about:blank',$this->header($response,'x-request-id')??$problem['requestId']??null,$problem['detail']??$problem['title']??'Request failed');
        } throw new \LogicException('Unreachable');
    }
    /** @param array<string,mixed>|null $body @return array<string,mixed> */
    private function cartRequest(string $method,string $path,string $cartToken,?array $body=null): array {if(!str_starts_with($cartToken,'hc_'))throw new \InvalidArgumentException('A cart capability token is required');return $this->request($method,rtrim($this->baseUrl,'/').$path,$body,$cartToken,$method==='GET');}
    /** @param array<string,mixed>|null $body @return array<string,mixed> */
    private function request(string $method,string $url,?array $body,?string $cartToken,bool $retry,array $extraHeaders=[]): array {$attempts=$retry?$this->maxRetries+1:1;for($attempt=0;$attempt<$attempts;$attempt++){$response=$this->send($url,$method,$body===null?null:json_encode($body,JSON_THROW_ON_ERROR),$cartToken,$extraHeaders);$status=$response['status'];if($status>=200&&$status<300)return json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);if($retry&&in_array($status,[429,502,503,504],true)&&$attempt+1<$attempts){$this->waitBeforeRetry($response,$attempt);continue;}$problem=json_decode($response['body'],true)?:[];throw new ProblemException($status,$problem['type']??'about:blank',$this->header($response,'x-request-id')??$problem['requestId']??null,$problem['detail']??$problem['title']??'Request failed');}throw new \LogicException('Unreachable');}
    /** @return array{status:int,body:string,headers?:array<string,string>} */
    private function send(string $url,string $method,?string $body,?string $cartToken,array $extraHeaders=[]): array {
        $headers=['Accept'=>'application/json','x-publishable-key'=>$this->publishableKey]+$extraHeaders;if($cartToken!==null)$headers['x-cart-token']=$cartToken;if($body!==null)$headers['Content-Type']='application/json';
        if(is_callable($this->transport))return ($this->transport)($url,$headers,$method,$body);
        $header='';foreach($headers as $name=>$value)$header.="{$name}: {$value}\r\n";
        $context=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'timeout'=>10,'header'=>$header,'content'=>$body??'']]);
        $body=file_get_contents($url,false,$context);$line=$http_response_header[0]??'HTTP/1.1 500';preg_match('/\s(\d{3})\s/',$line,$match);$responseHeaders=[];foreach(($http_response_header??[])as$headerLine){$parts=explode(':',$headerLine,2);if(count($parts)===2)$responseHeaders[strtolower(trim($parts[0]))]=trim($parts[1]);}return ['status'=>(int)($match[1]??500),'body'=>$body===false?'':$body,'headers'=>$responseHeaders];
    }
    /** @param array{headers?:array<string,string>} $response */
    private function header(array $response,string $name):?string {foreach(($response['headers']??[])as$key=>$value)if(strtolower($key)===strtolower($name))return$value;return null;}
    /** @param array{headers?:array<string,string>} $response */
    private function waitBeforeRetry(array $response,int $attempt):void {$value=$this->header($response,'retry-after');$delay=null;if($value!==null){if(is_numeric($value)&&((float)$value)>=0)$delay=(float)$value;else{$date=strtotime($value);if($date!==false)$delay=max(0,$date-time());}}$delay??=min(30,(0.25*(2**$attempt))+(random_int(0,100)/1000));if($delay>0)usleep((int)min(30000000,round($delay*1000000)));}
}
