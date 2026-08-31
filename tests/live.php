<?php declare(strict_types=1);
require __DIR__.'/../src/ProblemException.php';require __DIR__.'/../src/Client.php';
use Phessage\HeadlessCommerce\Client;
$url=getenv('HEADLESS_API_URL')?:'';$key=getenv('HEADLESS_PUBLISHABLE_KEY')?:'';if($url===''||$key===''){fwrite(STDERR,"Live test requires HEADLESS_API_URL and HEADLESS_PUBLISHABLE_KEY\n");exit(2);}
$assert=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);};
$client=new Client($url,$key);$product='1f7884bd-759d-4f47-9fdb-c7ea3dd3a9ef';
$catalog=$client->listProducts(100);$assert(in_array($product,array_column($catalog['data'],'id'),true),'sellable fixture missing');
$created=$client->createCart();$token=$created['cartToken'];$added=$client->addCartItem($token,$product);$assert(count($added['data']['items'])===1,'item was not added');
$prepared=$client->updateCheckoutDetails($token,['customerInfo'=>['firstName'=>'Headless','lastName'=>'Fixture','email'=>'php-live@example.test'],'billingAddress'=>['firstName'=>'Headless','lastName'=>'Fixture','email'=>'php-live@example.test','address1'=>'1 Test Way','city'=>'Vancouver','state'=>'BC','postalCode'=>'V6B1A1','country'=>'CA'],'shippingAddress'=>['sameAsBilling'=>true]]);
$data=$prepared['data'];$assert(count($data['shippingOptions'])===2&&count($data['paymentMethods'])===1,'checkout choices differ from fixture contract');
$shipping=$client->selectCheckoutShippingMethod($token,$data['shippingOptions'][0]['id']);$payment=$client->selectCheckoutPaymentMethod($token,$data['paymentMethods'][0]['id']);$assert($shipping['data']['selectedShippingMethodId']!==null,'shipping selection missing');$assert($payment['data']['selectedPaymentMethodId']!==null,'payment selection missing');
echo "live checkout journey passed\n";
