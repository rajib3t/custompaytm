<?php 

namespace rajib\PayTM;
class PayTM{

    public $env;
    public $mid;
    public $mkey;
    public $website;
    public $indType;
    public $chanel;
    public $paytmStatusUrl; 
    public $paytmTxnUrl;   
    public $paytmRefundUrl;

    public function __construct($config)
    {
		
		
        $this->env = $config['environment'];
        if($this->env=='local'){
            $this->paytmStatusUrl='https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
            $this->paytmTxnUrl = 'https://securegw-stage.paytm.in/order/process';
        }else if('production'){
            $this->paytmStatusUrl='https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
            $this->paytmTxnUrl = 'https://securegw-stage.paytm.in/order/process';
        }
        $this->mid=$config['merchant_id'];
        $this->mkey = $config['merchant_key'];
        $this->website=$config['merchant_website'];
        $this->indType = $config['industry_type'];
        $this->chanel = $config['chanel'];
        $this->paytmRefundUrl = '';

    }

    public function encrypt_e($input, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_encrypt ( $input , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}
	
	public function decrypt_e($crypt, $ky) {
		$key   = html_entity_decode($ky);
		$iv = "@@@@&&&&####$$$$";
		$data = openssl_decrypt ( $crypt , "AES-128-CBC" , $key, 0, $iv );
		return $data;
	}
   

    public function generateSalt_e($length) {
		$random = "";
		srand((double) microtime() * 1000000);
	
		$data = "AbcDE123IJKLMN67QRSTUVWXYZ";
		$data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
		$data .= "0FGH45OP89";
	
		for ($i = 0; $i < $length; $i++) {
			$random .= substr($data, (rand() % (strlen($data))), 1);
		}
	
		return $random;
	}
    public function checkString_e($value) {
		if ($value == 'null')
			$value = '';
		return $value;
	}
	
	private function getChecksumFromArray($arrayList, $key, $sort=1) {
		if ($sort != 0) {
			ksort($arrayList);
		}
		$str = $this->getArray2Str($arrayList);
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}
	public function getChecksumFromString($str, $key) {
		
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}
	
	public function verifychecksum_e($arrayList, $key, $checksumvalue) {
		$arrayList = $this->removeCheckSumParam($arrayList);
		ksort($arrayList);
		$str = $this->getArray2StrForVerify($arrayList);
		$paytm_hash = $this->decrypt_e($checksumvalue, $key);
		$salt = substr($paytm_hash, -4);
	
		$finalString = $str . "|" . $salt;
	
		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;
	
		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}
	
	public function verifychecksum_eFromStr($str, $key, $checksumvalue) {
		$paytm_hash = $this->decrypt_e($checksumvalue, $key);
		$salt = substr($paytm_hash, -4);
	
		$finalString = $str . "|" . $salt;
	
		$website_hash = hash("sha256", $finalString);
		$website_hash .= $salt;
	
		$validFlag = "FALSE";
		if ($website_hash == $paytm_hash) {
			$validFlag = "TRUE";
		} else {
			$validFlag = "FALSE";
		}
		return $validFlag;
	}
	
	public function getArray2Str($arrayList) {
		$findme   = 'REFUND';
		$findmepipe = '|';
		$paramStr = "";
		$flag = 1;	
		foreach ($arrayList as $key => $value) {
			$pos = strpos($value, $findme);
			$pospipe = strpos($value, $findmepipe);
			if ($pos !== false || $pospipe !== false) 
			{
				continue;
			}
			
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}
	
	public function getArray2StrForVerify($arrayList) {
		$paramStr = "";
		$flag = 1;
		foreach ($arrayList as $key => $value) {
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}
	
	public function redirect2PG($paramList, $key) {
		$hashString = $this->getchecksumFromArray($paramList,$key);
		$checksum = $this->encrypt_e($hashString, $key);
	}
	
	public function removeCheckSumParam($arrayList) {
		if (isset($arrayList["CHECKSUMHASH"])) {
			unset($arrayList["CHECKSUMHASH"]);
		}
		return $arrayList;
	}
	
	public function getTxnStatus($requestParamList) {
		return $this->callAPI($this->paytmUrl, $requestParamList);
	}
	
	public function getTxnStatusNew($requestParamList) {
		return $this->callNewAPI($this->paytmStatusUrl, $requestParamList);
	}
	
	public function initiateTxnRefund($requestParamList) {
		$CHECKSUM = $this->getRefundChecksumFromArray($requestParamList,$this->mkey,0);
		$requestParamList["CHECKSUM"] = $CHECKSUM;
		return $this->callAPI($this->paytmRefundUrl, $requestParamList);
	}
	
	public function callAPI($apiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                         
		'Content-Type: application/json', 
		'Content-Length: ' . strlen($postData))                                                                       
		);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}
	
	public function callNewAPI($apiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($apiURL);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                         
		'Content-Type: application/json', 
		'Content-Length: ' . strlen($postData))                                                                       
		);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}
	public function getRefundChecksumFromArray($arrayList, $key, $sort=1) {
		if ($sort != 0) {
			ksort($arrayList);
		}
		$str = $this->getRefundArray2Str($arrayList);
		$salt = $this->generateSalt_e(4);
		$finalString = $str . "|" . $salt;
		$hash = hash("sha256", $finalString);
		$hashString = $hash . $salt;
		$checksum = $this->encrypt_e($hashString, $key);
		return $checksum;
	}
	public function getRefundArray2Str($arrayList) {	
		$findmepipe = '|';
		$paramStr = "";
		$flag = 1;	
		foreach ($arrayList as $key => $value) {		
			$pospipe = strpos($value, $findmepipe);
			if ($pospipe !== false) 
			{
				continue;
			}
			
			if ($flag) {
				$paramStr .= $this->checkString_e($value);
				$flag = 0;
			} else {
				$paramStr .= "|" . $this->checkString_e($value);
			}
		}
		return $paramStr;
	}
	public function callRefundAPI($refundApiURL, $requestParamList) {
		$jsonResponse = "";
		$responseParamList = array();
		$JsonData =json_encode($requestParamList);
		$postData = 'JsonData='.urlencode($JsonData);
		$ch = curl_init($refundApiURL);	
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_URL, $refundApiURL);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		$headers = array();
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  
		$jsonResponse = curl_exec($ch);   
		$responseParamList = json_decode($jsonResponse,true);
		return $responseParamList;
	}
    public  function pay($data)
	{

		$ORDER_ID=$data['order_id'];
		$CUST_ID=$data['user_id'];
		$TXN_AMOUNT=$data['amount'];
		$CALLBACK_URL=$data['call_back_url'];
		$paramList["MID"] =  $this->mid;
		$paramList["ORDER_ID"] = $ORDER_ID;
		$paramList["CUST_ID"] = $CUST_ID;
		$paramList["INDUSTRY_TYPE_ID"] =  $this->indType;
		$paramList["CHANNEL_ID"] =  $this->chanel;
		$paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
		$paramList["WEBSITE"] = $this->website;
		$paramList["CALLBACK_URL"] = $CALLBACK_URL;


		$checkSum =  $this->getChecksumFromArray($paramList, $this->mkey);
		?>
		<html>
		<head>
		<title>Merchant Check Out Page</title>
		</head>
		<body>
			<center><h1>Please do not refresh this page...</h1></center>
				<form method="post" action="<?php echo  $this->paytmTxnUrl ?>" name="f1">
				<table border="1">
					<tbody>
					<?php
					foreach($paramList as $name => $value) {
						echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
					}
					?>
					<input type="hidden" name="CHECKSUMHASH" value="<?php echo $checkSum ?>">
					</tbody>
				</table>
				<script type="text/javascript">
					document.f1.submit();
				</script>
			</form>
		</body>
		</html>
		<?php
	}
    public  function recived($data)
	{
		$paytmChecksum = "";
		$paramList = array();
		$isValidChecksum = "FALSE";

		$paramList = $data;
		$paytmChecksum = isset($data["CHECKSUMHASH"]) ? $data["CHECKSUMHASH"] : ""; //Sent by Paytm pg

		//Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application�s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
		$isValidChecksum =  $this->verifychecksum_e($paramList,  $this->mkey, $paytmChecksum); //will return TRUE or FALSE string.
		unset($data['CHECKSUMHASH']);
		$res = [];
		if($isValidChecksum == "TRUE") {
			
			if ($_POST["STATUS"] == "TXN_SUCCESS") {

					foreach($data as $paramName => $paramValue) {
						$res[$paramName] = $paramValue;
					}
				}
			else {
				$res['status']=false;
				$res['message']='Transaction status is failure';
			}

			
			

			}
			else {
					$res['status']=false;
					$res['message']='Checksum mismatched.';
				
				}
			return $res;
		}

}
