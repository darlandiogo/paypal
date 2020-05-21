<?php

require_once 'PayPal.php';


try {

    
    $_paypal = new PayPal (
        [
            'mode'      => 'sandbox', // live
            'client_id' => '',
            'password'  => ''
        ]
    );

    $paypal = [
    
       "item_name"        => 'plano_00',
       "item_number"      => '23434335',
       "amount"           => '10.00', 
       "quantity"         => "1",
       "currency_code"    => "BRL",
       "first_name"       => 'Roberto',
       "last_name"        => 'Simoes',
       "email"            => 'robertosimoes@hotmail.com',
       "address_number"   => '41',
       "address1"         => 'Rua São Venâncio',
       "address2"         => 'APT 101',
       "night_phone_a"    => '55',
       "night_phone_b"    => '21965423332',
       "city"             => 'Rio de Janeiro',
       "state"            => 'Rio de Janeiro',
       "zip"              => '21640330',
       "country"          => "BR",
       "custom"           => '87966291006',
       "notify_url"       => "http://localhost:8080",
       "return"           => "http://localhost:8080",
       "cancel_return"    => "http://localhost:8080",
       "soft_descriptor"  => " descricao do site", 
       "return_url" => 'http://localhost:8080',
       "cancel_url" => 'http://localhost:8080'
    ];

    
     $result =  $_paypal->payment($paypal);   

     // parametros adicionais para executar pagamento 
     $otherParams = [
        "approval_url"   =>  $result->links[1]->href,
        "token"          =>  $_paypal->crypt('encrypt', $_paypal->token()),
        "execute_url"    =>  $_paypal->crypt('encrypt', $result->links[2]->href),
        "mode"           =>  'sandbox',
        "payerTaxIdType" =>  'BR_CPF',
        "language"       =>  'pt_BR',
        "result_url"     =>  'http://localhost:8080'
    ];

    $paypal = array_merge($paypal, $otherParams);

     echo $_paypal->viewPaymentPaypal($paypal);

} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
} 
