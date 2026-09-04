<?php declare(strict_types=1);
namespace Phessage\HeadlessCommerce;
final class Client {
    /** @param null|callable(string,array<string,string>,string,?string):array{status:int,body:string,headers?:array<string,string>} $transport */
    public function __construct(private readonly string $baseUrl, private readonly string $publishableKey, private readonly mixed $transport = null, private readonly int $maxRetries = 2, private readonly float $timeoutSeconds = 10.0) {
        if ($baseUrl === '' || !str_starts_with($publishableKey, 'pk_')) throw new \InvalidArgumentException('A base URL and publishable key are required');
        if ($timeoutSeconds <= 0 || $timeoutSeconds > 120) throw new \InvalidArgumentException('timeoutSeconds must be between 0 and 120');
    }
    public static function forStore(string $storeId,string $bootstrapUrl='https://api.1ecomm.com',mixed $transport=null,int $maxRetries=2,float $timeoutSeconds=10.0):self {
        $url=rtrim($bootstrapUrl,'/').'/v1/headless/stores/'.rawurlencode($storeId).'/config';
        if($timeoutSeconds<=0||$timeoutSeconds>120)throw new \InvalidArgumentException('timeoutSeconds must be between 0 and 120');
        if(is_callable($transport))$response=$transport($url,['Accept'=>'application/json'],'GET',null);else{$context=stream_context_create(['http'=>['method'=>'GET','ignore_errors'=>true,'timeout'=>$timeoutSeconds,'header'=>"Accept: application/json\r\n"]]);$body=file_get_contents($url,false,$context);$line=$http_response_header[0]??'HTTP/1.1 500';preg_match('/\s(\d{3})\s/',$line,$match);$response=['status'=>(int)($match[1]??500),'body'=>$body===false?'':$body];}
        if($response['status']!==200)throw new \RuntimeException('Headless store bootstrap failed');
        $runtime=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR)['data']??[];
        if(($runtime['storeId']??null)!==$storeId||!str_starts_with((string)($runtime['publishableKey']??''),'pk_'))throw new \RuntimeException('Invalid headless store bootstrap response');
        return new self((string)$runtime['apiUrl'],(string)$runtime['publishableKey'],$transport,$maxRetries,$timeoutSeconds);
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
    public function customerAuthConfig(): array { return $this->customerRequest('GET','/auth/config',null,null,null,true); }
    public function loginCustomer(string $email,string $password,?string $cartToken=null): array { return $this->customerRequest('POST','/auth/login',null,['email'=>$email,'password'=>$password],$cartToken); }
    public function requestCustomerOtp(string $channel,string $destination,?string $region=null): array { return $this->customerRequest('POST','/auth/otp/request',null,array_filter(['channel'=>$channel,'destination'=>$destination,'region'=>$region],static fn(mixed $v):bool=>$v!==null)); }
    public function verifyCustomerOtp(array $input,?string $cartToken=null): array { return $this->customerRequest('POST','/auth/otp/verify',null,$input,$cartToken); }
    public function socialLoginCustomer(string $provider,string $idToken,?string $cartToken=null): array { if(!in_array($provider,['google','apple'],true))throw new \InvalidArgumentException('Social provider must be google or apple');return $this->customerRequest('POST','/auth/social/'.rawurlencode($provider),null,['idToken'=>$idToken],$cartToken); }
    public function refreshCustomer(string $refreshToken): array { return $this->customerRequest('POST','/auth/refresh',null,['refreshToken'=>$refreshToken]); }
    public function logoutCustomer(string $refreshToken): array { return $this->customerRequest('POST','/auth/logout',null,['refreshToken'=>$refreshToken]); }
    public function customerProfile(string $token): array { return $this->customerRequest('GET','/me',$token,null,null,true); }
    public function updateCustomerProfile(string $token,array $input): array { return $this->customerRequest('PATCH','/me',$token,$input); }
    public function mergeCustomerCart(string $token,string $cartToken): array { return $this->customerRequest('POST','/cart/merge',$token,null,$cartToken); }
    public function customerAddresses(string $token): array { return $this->customerRequest('GET','/addresses',$token,null,null,true); }
    public function createCustomerAddress(string $token,array $input): array { return $this->customerRequest('POST','/addresses',$token,$input); }
    public function updateCustomerAddress(string $token,string $id,array $input): array { return $this->customerRequest('PATCH','/addresses/'.rawurlencode($id),$token,$input); }
    public function deleteCustomerAddress(string $token,string $id): array { return $this->customerRequest('DELETE','/addresses/'.rawurlencode($id),$token); }
    public function setDefaultCustomerAddress(string $token,string $id): array { return $this->customerRequest('POST','/addresses/'.rawurlencode($id).'/default',$token); }
    public function customerOrders(string $token,int $page=1,int $limit=20,?string $status=null): array { $query=http_build_query(array_filter(['page'=>max(1,$page),'limit'=>min(100,max(1,$limit)),'status'=>$status],static fn(mixed $v):bool=>$v!==null));return $this->customerRequest('GET','/orders?'.$query,$token,null,null,true); }
    public function customerOrder(string $token,string $id): array { return $this->customerRequest('GET','/orders/'.rawurlencode($id),$token,null,null,true); }
    public function cancelCustomerOrder(string $token,string $id,string $reason): array { return $this->customerRequest('POST','/orders/'.rawurlencode($id).'/cancel',$token,['reason'=>$reason]); }
    public function customerReturns(string $token): array { return $this->customerRequest('GET','/returns',$token,null,null,true); }
    public function customerOrderReturns(string $token,string $orderId): array { return $this->customerRequest('GET','/orders/'.rawurlencode($orderId).'/returns',$token,null,null,true); }
    public function createCustomerReturn(string $token,string $orderId,array $input,string $idempotencyKey): array { $key=trim($idempotencyKey);if($key===''||strlen($key)>120)throw new \InvalidArgumentException('An idempotency key of 1-120 characters is required');return $this->customerRequest('POST','/orders/'.rawurlencode($orderId).'/returns',$token,$input,null,false,['Idempotency-Key'=>$key]); }
    public function cancelCustomerReturn(string $token,string $id): array { return $this->customerRequest('POST','/returns/'.rawurlencode($id).'/cancel',$token); }
    /** @return array<string,mixed> */
    private function get(string $url): array {
        for($attempt=0;$attempt<=$this->maxRetries;$attempt++){
            $response=$this->send($url,'GET',null,null); $status=$response['status'];
            if($status>=200&&$status<300){$data=json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);return $data;}
            if(in_array($status,[429,502,503,504],true)&&$attempt<$this->maxRetries){$this->waitBeforeRetry($response,$attempt);continue;}
            $problem=json_decode($response['body'],true)?:[];throw $this->problem($status,$problem,$response);
        } throw new \LogicException('Unreachable');
    }
    /** @param array<string,mixed>|null $body @return array<string,mixed> */
    private function cartRequest(string $method,string $path,string $cartToken,?array $body=null): array {if(!str_starts_with($cartToken,'hc_'))throw new \InvalidArgumentException('A cart capability token is required');return $this->request($method,rtrim($this->baseUrl,'/').$path,$body,$cartToken,$method==='GET');}
    /** @param array<string,mixed>|null $body @return array<string,mixed> */
    private function customerRequest(string $method,string $path,?string $token,?array $body=null,?string $cartToken=null,bool $retry=false,array $extraHeaders=[]): array {if($token!==null&&$token==='')throw new \InvalidArgumentException('A customer access token is required');$headers=array_merge($token===null?[]:['x-customer-token'=>$token],$extraHeaders);return $this->request($method,rtrim($this->baseUrl,'/').'/v1/headless/customer'.$path,$body,$cartToken,$retry,$headers);}
    /** @param array<string,mixed>|null $body @return array<string,mixed> */
    private function request(string $method,string $url,?array $body,?string $cartToken,bool $retry,array $extraHeaders=[]): array {$attempts=$retry?$this->maxRetries+1:1;for($attempt=0;$attempt<$attempts;$attempt++){$response=$this->send($url,$method,$body===null?null:json_encode($body,JSON_THROW_ON_ERROR),$cartToken,$extraHeaders);$status=$response['status'];if($status>=200&&$status<300)return json_decode($response['body'],true,512,JSON_THROW_ON_ERROR);if($retry&&in_array($status,[429,502,503,504],true)&&$attempt+1<$attempts){$this->waitBeforeRetry($response,$attempt);continue;}$problem=json_decode($response['body'],true)?:[];throw $this->problem($status,$problem,$response);}throw new \LogicException('Unreachable');}
    /** @param array<string,mixed> $problem @param array{headers?:array<string,string>} $response */
    private function problem(int $status,array $problem,array $response):ProblemException { $rawErrors=is_array($problem['errors']??null)?$problem['errors']:[];$errors=array_values(array_filter($rawErrors,static fn(mixed $entry):bool=>is_string($entry)));$fields=is_array($problem['fields']??null)?$problem['fields']:[];return new ProblemException($status,(string)($problem['type']??'about:blank'),$this->header($response,'x-request-id')??(isset($problem['requestId'])?(string)$problem['requestId']:null),(string)($problem['detail']??$problem['title']??'Request failed'),$this->rateLimit($response),isset($problem['code'])?(string)$problem['code']:null,$errors,$fields); }
    /** @return array{status:int,body:string,headers?:array<string,string>} */
    private function send(string $url,string $method,?string $body,?string $cartToken,array $extraHeaders=[]): array {
        $headers=['Accept'=>'application/json','x-publishable-key'=>$this->publishableKey]+$extraHeaders;if($cartToken!==null)$headers['x-cart-token']=$cartToken;if($body!==null)$headers['Content-Type']='application/json';
        if(is_callable($this->transport))return ($this->transport)($url,$headers,$method,$body);
        $header='';foreach($headers as $name=>$value)$header.="{$name}: {$value}\r\n";
        $context=stream_context_create(['http'=>['method'=>$method,'ignore_errors'=>true,'timeout'=>$this->timeoutSeconds,'header'=>$header,'content'=>$body??'']]);
        $body=file_get_contents($url,false,$context);$line=$http_response_header[0]??'HTTP/1.1 500';preg_match('/\s(\d{3})\s/',$line,$match);$responseHeaders=[];foreach(($http_response_header??[])as$headerLine){$parts=explode(':',$headerLine,2);if(count($parts)===2)$responseHeaders[strtolower(trim($parts[0]))]=trim($parts[1]);}return ['status'=>(int)($match[1]??500),'body'=>$body===false?'':$body,'headers'=>$responseHeaders];
    }
    /** @param array{headers?:array<string,string>} $response */
    private function header(array $response,string $name):?string {foreach(($response['headers']??[])as$key=>$value)if(strtolower($key)===strtolower($name))return$value;return null;}
    /** @param array{headers?:array<string,string>} $response @return array{limit:?int,remaining:?int,reset:?int,retryAfter:?string} */
    private function rateLimit(array $response):array {$number=function(string $name)use($response):?int{$value=$this->header($response,$name);return$value!==null&&is_numeric($value)?(int)$value:null;};return['limit'=>$number('ratelimit-limit'),'remaining'=>$number('ratelimit-remaining'),'reset'=>$number('ratelimit-reset'),'retryAfter'=>$this->header($response,'retry-after')];}
    /** @param array{headers?:array<string,string>} $response */
    private function waitBeforeRetry(array $response,int $attempt):void {$value=$this->header($response,'retry-after');$delay=null;if($value!==null){if(is_numeric($value)&&((float)$value)>=0)$delay=(float)$value;else{$date=strtotime($value);if($date!==false)$delay=max(0,$date-time());}}$delay??=min(30,(0.25*(2**$attempt))+(random_int(0,100)/1000));if($delay>0)usleep((int)min(30000000,round($delay*1000000)));}
}
