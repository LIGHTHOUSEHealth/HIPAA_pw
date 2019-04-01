<?php

$exchange = exchangeCode($_REQUEST['code']);
	
$api_token = "YOUR TOKEN";

$base_url = "https://YOUR URL/api/v1";

$userList_json = sendGetData($base_url."/users?limit=200",$api_token);
$userList = json_decode($userList_json,"TRUE");

$HIPAA_trigger = 28;

if(array_key_exists("trigger", $_GET)){
	$trigger = $_GET["trigger"];
	if($trigger=="all"){$HIPAA_trigger = -1;}
}

$check_date = date("Y-m-d");

$expired_ids = 0;
$expired_users = array();
$one_user = array();

foreach($userList as $user){
	$update_day = substr($user["lastUpdated"],0,10); 
	$start_date = strtotime($update_day); 
	$end_date = strtotime($check_date); 
	
	$seconds_diff = $end_date - $start_date; 
	$day_seconds = 60 * 60 *24;
	$days_old = $seconds_diff/$day_seconds;
	
	
	if($days_old > $HIPAA_trigger){
		$expired_ids++;
		$one_user["first"]=$user["profile"]["firstName"];
		$one_user["last"]=$user["profile"]["lastName"];
		$one_user["id"]=$user["id"];
		
		$expired_users[]=$one_user;
		
		$reset = resetUser($user["id"],$api_token);
	}
	
}

$reply = array();
$reply["expired_count"]=$expired_ids;
$reply["expired_users"]=$expired_users;

$reply_json = json_encode($reply);
echo $reply_json;



////////// FUNCTIONS

function resetUser($userid,$api_token){
	
	$post_url = "https://dev-615245.okta.com/api/v1/users/$userid/lifecycle/expire_password?tempPassword=false";
	$reset_it = sendPostData($post_url,"",$api_token);
	
	// INSERT CODE FOR HIPAA LOGGING TO YOUR SERVICE
	
	
}

function sendPostData($url, $post,$token){
    $ch = curl_init($url);
	
	$auth = 'Authorization: SSWS '.$token;
	
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept: application/json','Content-Type: application/json',$auth));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}



function sendGetData($url,$token){
    $ch = curl_init($url);
   
	$auth = 'Authorization: SSWS '.$token;
	
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept: application/json','Content-Type: application/json',$auth));
    $result = trim(curl_exec($ch));
    curl_close($ch);
    return $result;
}


function sendDeleteData($url,$token){
	
    $ch = curl_init($url);
	$auth = 'Authorization: SSWS '.$token;
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");	
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Accept: application/json','Content-Type: application/json',$auth));
    $result = trim(curl_exec($ch));
    curl_close($ch);
    return $result;
}



function exchangeCode($code) {
    $authHeaderSecret = base64_encode( '0oaelgrcepzMXf5vo356:bFZ33pEkJhMzjTuTkkpBD4UmcniMWCMX-I_-uJsr' );
    $query = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => 'Redirect URI'
    ]);
    $headers = [
        'Authorization: Basic ' . $authHeaderSecret,
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'Connection: close',
        'Content-Length: 0'
    ];
    $url = $base_url.'token?' . $query;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if(curl_error($ch)) {
        $httpcode = 500;
    }
    curl_close($ch);
    return json_decode($output);
}




?>


