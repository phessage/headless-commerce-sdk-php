<?php declare(strict_types=1);
require __DIR__.'/../src/ProblemException.php';require __DIR__.'/../src/Client.php';
use Phessage\HeadlessCommerce\Client;use Phessage\HeadlessCommerce\ProblemException;
$assert=function(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);};
$calls=[];$client=new Client('https://sandbox.test','pk_test_demo',function(string $url,array $headers)use(&$calls):array{$calls[]=[$url,$headers];return ['status'=>200,'body'=>json_encode(['data'=>[['id'=>'p1','name'=>'Pack']],'nextCursor'=>null,'requestId'=>'req_1'],JSON_THROW_ON_ERROR)];});
$page=$client->listProducts(999,null,' pack ');$assert($page['data'][0]['name']==='Pack','product parsing failed');$assert(str_contains($calls[0][0],'/v1/headless/products?limit=100&query=pack'),'URL contract failed');$assert($calls[0][1]['x-publishable-key']==='pk_test_demo','key header missing');
$attempts=0;$client=new Client('https://sandbox.test','pk_test_demo',function()use(&$attempts):array{$attempts++;return $attempts===1?['status'=>503,'body'=>'{}']:['status'=>404,'body'=>'{"type":"x","title":"Missing","requestId":"req_2"}'];},1);try{$client->listProducts();throw new RuntimeException('expected exception');}catch(ProblemException $e){$assert($e->status===404&&$e->requestId==='req_2','typed problem failed');}$assert($attempts===2,'safe retry failed');
try{new Client('https://sandbox.test','secret');throw new RuntimeException('expected invalid key');}catch(InvalidArgumentException){}
echo "3 tests passed\n";
