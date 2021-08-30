This is simple custom php PAYTM gatewaye integration.

#Install
composer require rajib/custompaytm
#uses
require_once __DIR__ . '/vendor/autoload.php';
use rajib\PayTM\PayTM;

Create instant 


    $config['environment']='local'; // for pruduction 'production'
    $config['merchant_id']='your merchant id';
    $config['merchant_key']='your merchant key';
    $config['merchant_website']='merchant website';
    $config['industry_type']='type';
    $config['chanel']='chanel';
    $paytm = new PayTM($config);


    For sending payment request 

    $data=[
    'order_id'=>'order_id', // Order id must be unique
    'user_id'=>'user_id',
    'amount'=>10.00,
    'call_back_url'=>'' // where recieved response 
   ];
   $paytm->pay($data);


    Recieved Responce In call back 
    $config['environment']='local'; // for pruduction 'production'
    $config['merchant_id']='your merchant id';
    $config['merchant_key']='your merchant key';
    $config['merchant_website']='merchant website';
    $config['industry_type']='type';
    $config['chanel']='chanel';
    $paytm = new PayTM($config);
    $responce = $paytm->recived($_POST);