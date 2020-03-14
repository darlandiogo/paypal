<?php

class PayPal {

    protected $token;
    protected $mode;
    protected $client_id;
    protected $password;

    public function __construct( $config = false ) {
        if($config) {
            $this->mode      = $config['mode'];
            $this->client_id = $config['client_id'];
            $this->password  = $config['password'];
        }
    }

    public function auth( ){

        $data    =  ["grant_type" => "client_credentials"];
        $headers =  [
            "Content-Type"    => "application/x-www-form-urlencoded; charset=utf-8",
            "Accept"          => "application/json", 
            "Accept-Language" => " en-US,en;q=0.5",
            "Authorization"   => " Basic"
        ];

        $result = $this->curl($data, $headers, false, true);
        
        if(isset($result->error))
            throw new Exception($result->error_description);

        if(isset($result->access_token))
            $this->token = $result->access_token; 
        
    }
    
    public function token( ){
        return $this->token;
    }

    public function payment_url( ){
        return ($this->mode == "sandbox") 
            ? "https://api.sandbox.paypal.com/v1/payments/payment" 
            : "https://api.paypal.com/v1/payments/payment";
    }

    public function auth_url( ){
        return ($this->mode == "sandbox") 
            ? "https://api.sandbox.paypal.com/v1/oauth2/token" 
            : "https://api.paypal.com/v1/oauth2/token";
    }

    public function payment($paypal){

        $this->auth(); // geração de token para autenticacão 
        
        $headers =  array("Content-Type: application/json", "Authorization: Bearer $this->token");

        $data      = [     
            "intent"=> "sale",
            "payer" => [
            "payment_method"=> "paypal"
            ],
            "transactions"=> [ [
            "amount"=> [
                "total"=> $paypal["amount"] ,
                "currency"=> $paypal["currency_code"],
                "details"=> [
                    "subtotal"=> $paypal["amount"],
                    "tax"=> "0.00",
                    "shipping"=> "0.00",
                    "handling_fee"=> "0.00",
                    "shipping_discount"=> "0.00",
                    "insurance"=> "0.00"
                ] ]
            ,
            "description"=> $paypal["item_name"],
            "custom"=> $paypal["item_name"],
            "invoice_number"=> $paypal["item_number"],
            "payment_options"=> [
                "allowed_payment_method"=> "IMMEDIATE_PAY"
            ],
            "soft_descriptor"=> $paypal['soft_descriptor'],
            "item_list"=> [
                "items"=> [
                    /*    [
                        "name"=> "hat",
                        "description"=> "Brown color hat",
                        "quantity"=> "5",
                        "price"=> "3",
                        "tax"=> "0.01",
                        "sku"=> "1",
                        "currency"=> "USD" ], */
                [
                "name"=> $paypal["item_name"],
                "description"=> $paypal["item_name"],
                "quantity"=> $paypal["quantity"],
                "price"=> $paypal["amount"] ,
                "tax"=> "0.00",
                "sku"=> $paypal["item_name"],
                "currency"=> $paypal["currency_code"] ]
                ],
                "shipping_address"=> [
                "recipient_name"=> $paypal["first_name"],
                "line1"=> $paypal["custom"],
                "line2"=> $paypal["last_name"],
                "city"=> $paypal["city"] ,
                "country_code"=> $paypal["country"] ,
                "postal_code"=> $paypal["zip"],
                "phone"=> $paypal['night_phone_a'] . $paypal['night_phone_b'],
                "state"=> $paypal["state"]
                ] ]
            ] ],
            "note_to_payer"=> "Contact us for any questions on your order.",
            "redirect_urls"=> [
                "return_url"=> $paypal['return_url'],
                "cancel_url"=> $paypal['cancel_url'] 
            ]
        ];
        
        return $this->curl($data, $headers);
    }

    public function execute( $payerId, $token, $execute_url ){
        $headers =  array("Content-Type: application/json", "Authorization: Bearer $token");
        $data    = ["payer_id" => $payerId ]; //$_REQUEST['payerId']];
        $result  = $this->curl($data, $headers, $execute_url);
        
        if($result->state === 'approved') {
            return (json_encode(array('success' => 'Obrigado, sua solicitacão foi concluída!', 'result' => $result->id)));
        }
       
        return (json_encode(array('error' => 'Erro ao concluir pagamento, tente novamente!', 'result' => $result)));
    }

    public function curl($data, $headers, $url = false, $auth = false){

        if(!$url) {
            $url = ($auth) ? $this->auth_url() : $this->payment_url();
        }
        
        $tuCurl = curl_init(); 
        curl_setopt($tuCurl, CURLOPT_URL, $url); 
        curl_setopt($tuCurl, CURLOPT_HEADER, 0); 
        curl_setopt($tuCurl, CURLOPT_SSLVERSION, 6); 
        curl_setopt($tuCurl, CURLOPT_POST, 1); 
        curl_setopt($tuCurl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1); 
        if($auth){
            curl_setopt($tuCurl, CURLOPT_USERPWD, "$this->client_id:$this->password");
            curl_setopt($tuCurl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($tuCurl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        else {
            curl_setopt($tuCurl, CURLOPT_POSTFIELDS, json_encode($data)); 
        }
        curl_setopt($tuCurl, CURLOPT_HTTPHEADER, $headers); 

        $tuData = curl_exec($tuCurl); 

        if(!curl_errno($tuCurl)){ 
        $info = curl_getinfo($tuCurl); 
        } else { 
        echo 'Curl error: ' . curl_error($tuCurl); 
        } 

        curl_close($tuCurl); 
        $result = json_decode($tuData); 

        return $result;

    }

    public function crypt($action, $string) {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'defaultEncrypt';
        $secret_iv = 'defaultDecrypt';
        // hash
        $key = hash('sha256', $secret_key);
        
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ( $action == 'encrypt' ) {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if( $action == 'decrypt' ) {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }

    public function viewPaymentPaypal($args){ 

        return '
          <script type="text/javascript" src="https://www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js">
          </script>
          <script type="application/javascript">
                var ppp = PAYPAL.apps.PPP({ 
                    "approvalUrl": "'.$args["approval_url"].'",
                    "placeholder": "ppplus",
                    "mode": "'.$args["mode"].'",
                    "payerEmail" : "'.$args["email"].'",
                    "payerFirstName": "'.$args["first_name"].'",
                    "payerLastName": "'.$args["last_name"].'",
                    "payerPhone": "'.$args["night_phone_a"].$args["night_phone_b"].'",
                    "payerTaxId": "'.$args["custom"].'",
                    "payerTaxIdType": "'.$args["payerTaxIdType"].'",
                    "language": "'.$args["language"].'",
                    "country": "'.$args["country"].'",
                    "disableContinue": "continueButton",
                    "enableContinue": "continueButton",
                    "merchantInstallmentSelection": "1",
                    "merchantInstallmentSelectionOptional":"true",
                    onContinue: function (rememberedCards, payerId, token, term) {
                            document.getElementById("responseDiv").innerHTML = \'<div style="text-align:center"><img alt="GIF by William Wolfgang Wunderbar" height="150" style="align" src="https://media1.giphy.com/media/l0HlFhR3LOrKljgkM/giphy.webp?cid=790b76115cefea1162574b6b2e424bef&amp;rid=giphy.webp" width="150" class="sc-bZQynM dxfFkd"></div>\';
                            document.getElementById("continueButton").style.display = "none";
                            document.getElementById("ppplus").style.display = "none";
                            if (payerId) {
                              var url = "'.$args["result_url"].'";
                              var access_token = "'.$args["token"].'";
                              var execute_url = "'.$args["execute_url"].'";
                              var uri = url +"?action=cotacao_ajax"+"&access_token="+access_token+"&execute_url="+execute_url+"&payerId="+payerId; 
                              var xhttp = new XMLHttpRequest();
                              xhttp.onreadystatechange = function() {
                                if (this.readyState == 4 && this.status == 200) {
                                  result = JSON.parse(this.responseText);
                                  if(result.success){
                                        document.getElementById("responseDiv").innerHTML = " "+
                                        "<p>"+result.success+"</p>"+
                                        "<p>"+result.result+"</p>";
                                  }
                                  else{
                                        if(result.error){
                                            document.getElementById("responseDiv").innerHTML = " "+
                                            "<p>"+result.error+"</p>"+
                                            "<p>"+result.result.name+"</p>"+
                                            "<p>"+result.result.message+"</p>"+
                                            "<p>"+result.result.information_link+"</p>"+
                                            "<p>"+result.result.debug_id+"</p>";
                                        }
                                  }
                                  
                                }
                              };
                              xhttp.open("GET", uri, true);
                              xhttp.send();
                            }
                    },
                    onError: function (err) {
                        result = JSON.parse(this.responseText);
                        var msg = document.getElementById("#responseOnError").innerHTML = "<p>"+result+"</p>";
                    }
                
                });
        
                if (window.addEventListener) {
                    window.addEventListener("message", messageListener, false);
                }
                else if (window.attachEvent) {
                    window.attachEvent("onmessage", messageListener);
                }
                else {
                    throw new Error("Can not attach message listener");
                }
        
        function messageListener(event) {
            try {
        
                var data = JSON.parse(event.data);
        
                // transaction submitted
                if (data && data.action && data.action === "disableContinueButton") {
                    document.getElementById("ppplus").style.display = "block";
                }
            
                // transaction error 
                if (data && ((data.action && data.action === "onError") || (data.result && data.result === "error")) ) {
                    document.getElementById("ppplus").style.display = "block";
                }
            
                // transaction data came back 
                if (data && data.action && data.result && data.action === "checkout") {
                    document.getElementById("ppplus").style.display = "block";
                }
            
                // transaction completed 
                if (data && data.action && data.action === "closeMiniBrowser") {
                    document.getElementById("ppplus").style.display = "none";
                }

            }
            catch (exc) {
                console.log("EXCEPTION: " + exc);
            }
        }
          </script>
          <div style="margin-left:4%; margin-top:3%;margin-bottom:5%; display: block;">
            <div id="responseDiv"></div>
            <div id="responseOnError"></div>
            <div id="ppplus"></div>
            <button style="background-color: #003751 !important;color:#fff" type="submit" id="continueButton" onclick="ppp.doContinue(); return false;"> Finalizar </button>  
        </div>
        ';
        
    } 
    
}
