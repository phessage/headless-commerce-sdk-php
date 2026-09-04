<?php declare(strict_types=1);
require __DIR__.'/../src/ProblemException.php';require __DIR__.'/../src/Client.php';
use Phessage\HeadlessCommerce\Client;
use Phessage\HeadlessCommerce\ProblemException;
$storeId=getenv('HEADLESS_STORE_ID')?:'01f5b02f-d7c0-42cd-b880-59f78ea70aa3';
$assert=static function(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);};
$product=getenv('HEADLESS_PRODUCT_ID')?:'1f7884bd-759d-4f47-9fdb-c7ea3dd3a9ef';
$key=getenv('HEADLESS_PUBLISHABLE_KEY')?:'';$api=getenv('HEADLESS_API_URL')?:'https://api.1ecomm.com';
$client=$key!==''?new Client($api,$key):Client::forStore($storeId);
$catalog=$client->listProducts(100);$assert(in_array($product,array_column($catalog['data'],'id'),true),'sellable fixture missing');
$created=$client->createCart();$token=$created['cartToken'];$added=$client->addCartItem($token,$product);$assert(count($added['data']['items'])===1,'item was not added');
$prepared=$client->updateCheckoutDetails($token,['customerInfo'=>['firstName'=>'Headless','lastName'=>'Fixture','email'=>'php-live@example.test'],'billingAddress'=>['firstName'=>'Headless','lastName'=>'Fixture','email'=>'php-live@example.test','address1'=>'1 Test Way','city'=>'Vancouver','state'=>'BC','postalCode'=>'V6B1A1','country'=>'CA'],'shippingAddress'=>['sameAsBilling'=>true]]);
$data=$prepared['data'];$assert(count($data['paymentMethods'])>=1,'payment choices differ from fixture contract');
if($data['shippingOptions']!==[]){$shipping=$client->selectCheckoutShippingMethod($token,$data['shippingOptions'][0]['id']);$assert($shipping['data']['selectedShippingMethodId']!==null,'shipping selection missing');}$payment=$client->selectCheckoutPaymentMethod($token,$data['paymentMethods'][0]['id']);$assert($payment['data']['selectedPaymentMethodId']!==null,'payment selection missing');
$order=$client->placeOrder($token,'php-live-'.bin2hex(random_bytes(16)));$assert(($order['data']['requiresPayment']??true)===false&&($order['data']['paymentStatus']??'')==='pending','pending order confirmation missing');$lookup=$client->lookupOrder($order['data']['orderNumber'],'php-live@example.test');$assert(($lookup['data']['orderNumber']??'')===$order['data']['orderNumber'],'created order could not be reopened');

$customerEmail=getenv('HEADLESS_CUSTOMER_EMAIL')?:'';$customerPassword=getenv('HEADLESS_CUSTOMER_PASSWORD')?:'';
if($customerEmail!==''&&$customerPassword!==''){
    $customerCart=$client->createCart();$client->addCartItem($customerCart['cartToken'],$product);
    $login=$client->loginCustomer($customerEmail,$customerPassword,$customerCart['cartToken']);$auth=$login['data'];
    $authKeys=array_keys($auth);sort($authKeys);$expectedAuthKeys=['cartInfo','customer','expiresIn','refreshToken','token'];sort($expectedAuthKeys);$assert($authKeys===$expectedAuthKeys,'customer authentication projection drifted');
    $profileKeys=array_keys($auth['customer']);sort($profileKeys);$expectedProfileKeys=['id','email','firstName','lastName','phone','avatarUrl','emailVerified'];sort($expectedProfileKeys);$assert($profileKeys===$expectedProfileKeys,'customer profile projection drifted');
    try{$client->customerProfile('');throw new RuntimeException('missing customer token was accepted');}catch(InvalidArgumentException){}
    $profile=$client->updateCustomerProfile($auth['token'],['firstName'=>'PHP E2E']);$assert($profile['data']['firstName']==='PHP E2E','profile update failed');
    $merged=$client->mergeCustomerCart($auth['token'],$customerCart['cartToken']);$assert($merged['data']['merged']===true&&str_starts_with($merged['data']['cartToken'],'hc_'),'customer cart merge failed');
    $address=$client->createCustomerAddress($auth['token'],['firstName'=>'PHP','lastName'=>'E2E','address1'=>'1 Fixture Way','city'=>'Vancouver','province'=>'BC','country'=>'CA','zip'=>'V6B1A1','setDefault'=>true]);$addressId=$address['data']['address']['id'];
    $assert($addressId!==null&&$address['data']['address']['isDefault']===true,'customer address creation failed');
    $updated=$client->updateCustomerAddress($auth['token'],$addressId,['address2'=>'Suite PHP']);$assert($updated['data']['address']['address2']==='Suite PHP','customer address update failed');
    $addresses=$client->customerAddresses($auth['token']);$assert(in_array($addressId,array_column($addresses['data']['addresses'],'id'),true),'customer address list failed');
    $returnOrderId=getenv('HEADLESS_RETURN_ORDER_ID')?:'';$returnOrderItemId=getenv('HEADLESS_RETURN_ORDER_ITEM_ID')?:'';$assert($returnOrderId!==''&&$returnOrderItemId!=='','eligible return fixture missing');
    $return=$client->createCustomerReturn($auth['token'],$returnOrderId,['reason'=>'not_as_expected','items'=>[['orderItemId'=>$returnOrderItemId,'quantity'=>1,'resolution'=>'refund']]])['data']['return'];$assert(($return['orderId']??'')===$returnOrderId&&($return['status']??'')==='requested','return creation projection drifted');
    $orderReturns=$client->customerOrderReturns($auth['token'],$returnOrderId)['data']['returns'];$assert(in_array($return['id'],array_column($orderReturns,'id'),true),'return missing from order history');
    $customerReturns=$client->customerReturns($auth['token'])['data']['returns'];$assert(in_array($return['id'],array_column($customerReturns,'id'),true),'return missing from customer history');
    $cancelledReturn=$client->cancelCustomerReturn($auth['token'],$return['id'])['data']['return'];$assert(($cancelledReturn['status']??'')==='cancelled','return cancellation projection drifted');
    $deleted=$client->deleteCustomerAddress($auth['token'],$addressId);$assert($deleted['data']['deleted']===true,'customer address deletion failed');
    $rotated=$client->refreshCustomer($auth['refreshToken']);
    try{$client->refreshCustomer($auth['refreshToken']);throw new RuntimeException('consumed refresh token was accepted');}catch(ProblemException $error){$assert($error->status===401&&$error->problemCode==='HEADLESS_HTTP_401','refresh replay problem contract failed');}
    $logout=$client->logoutCustomer($rotated['data']['refreshToken']);$assert($logout['data']['loggedOut']===true,'customer logout failed');
}
echo "live guest and customer journeys passed and reopened: ".$order['data']['orderNumber']."\n";
