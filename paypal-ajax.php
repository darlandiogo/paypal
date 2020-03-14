<?php

require_once 'PayPal.php';
header("Access-Control-Allow-Origin: *");

if(isset($_REQUEST['payerId'])) {

    $_paypal = new PayPal();

    $payerId      = ["payer_id" => $_REQUEST['payerId']];
    $access_token = $_paypal->crypt('decrypt', $_REQUEST['access_token']);
    $execute_url  = $_paypal->crypt('decrypt', $_REQUEST['execute_url']);

    echo $_paypal->execute($payerId, $access_token, $execute_url);
    
}

