<?php

class CurlManagerCall {
  private $token;
  private $authHeader;
  private $baseUrl;
  private $requestUrl;
  private $top;
  private $ch;

  public function __construct() {
    $this->baseUrl = 'https://pbx.megafon.ru/integration/usercrm';
    $this->requestUrl = $this->baseUrl;
    $this->ch = curl_init();
    $this->query = http_build_query([
      'action' => 'make_call',
      'obj' => 'UserCRM',
      'action_id' => '123',
      'params' => [
        'crm_user_id' => '1911',
        'dst' => '+79111391688'
      ],
    ]);
  } 


  public function Post() {

    $js_auth = [
      "action" => "make_call",
      "obj" => "UserCRM",
      "action_id" => "123",
      "params" => [
          "crm_user_id" => 1911,
          "dst" => "79111391688"
      ]
  ];
  
  $url = 'https://pbx.megafon.ru/integration/usercrm/';
  $auth_headers = [
      'Content-Type: application/json',
      'Authorization: Bearer 0042878e-cb4c-44f7-adc8-975daedcb19b028afcbc-a6f4-4596-9659-667dffac5cb4'
  ];
  
  $curl = curl_init($url);
  
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($js_auth));
  curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_headers);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  
  $response = curl_exec($curl);
  $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  
  curl_close($curl);
  
  echo $status_code . "\n";
  echo $response . "\n";

return $decodedResponse ?? null; // Возвращаем разобранный JSON или null в случае ошибки

    
}

}




/*
js_auth = {
  "action": "make_call",
  "obj": "UserCRM",
  "action_id": "123",
  "params": {
    "crm_user_id": 110,
    "dst": "89500299181"
  }
}

url = 'https://pbx.megafon.ru/integration/usercrm/'
auth_headers = {'Content-type': 'application/json','Authorization': 'Bearer 0042878e-cb4c-44f7-adc8-975daedcb19b028afcbc-a6f4-4596-9659-667dffac5cb4'}*/