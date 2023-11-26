<?php
namespace BazisCM\Workspace\Tools;

class CurlManagerTest {
  private $token;
  private $authHeader;
  private $baseUrl;
  private $requestUrl;
  private $top;
  private $ch;

  public function __construct($apiKey, $metod) {
    $this->apiKey = $apiKey;
    $this->authHeader = 'apiKey: ' . $this->apiKey;
    $this->baseUrl = 'https://cloud.bazissoft.ru';
    $this->requestUrl = $this->baseUrl . $metod;
    $this->top = 50;
    $this->ch = curl_init();
  } 

  public function CreateQueryOrders($p,$ProcesParam) {

    if($p['CheckContentName'] == 'orders'){
      $queryParams = http_build_query([
        'pageIndex' => $ProcesParam['pageIndex'],
        'pageSize' => $ProcesParam['pageSize'],
        'from' => $p['from'],
        'to' => $p['to'], 
        //     'filterByNumber' => 'R47101'
      ]);
    }

    if($p['CheckContentName'] == 'order'){
      $queryParams = http_build_query([]);
    }

    if($p['CheckContentName'] == 'file'){
      $queryParams = http_build_query([
        'filename' => $p['filename']
      ]);
    }
    
    return $queryParams;
  }

  public function Get($p) {
    
    $continue = true;
    $accumulatedMessages = array();

    $ProcesParam['pageIndex'] = 0;
    $ProcesParam['pageSize'] = 50;

    if($p['CheckContentName'] == 'file'){
      if (strpos($this->requestUrl, '/file') !== false) {
        
      }
      else{
        $this->requestUrl = $this->requestUrl . '/file';
      }
    }

    while ($continue) {

      // Создание cURL-запроса
      curl_setopt($this->ch, CURLOPT_URL, $this->requestUrl . '?' . $this->CreateQueryOrders($p, $ProcesParam));
      curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
          $this->authHeader
      ]);

      $jsonResponse = curl_exec($this->ch);
      $response = json_decode($jsonResponse, true);
    
      if ($jsonResponse === false) {
        echo 'cURL Error: ' . curl_error($this->ch);
        $continue = false; // Останавливаем цикл в случае ошибки
      } else {

      if($p['ContentPars']){
        if (isset($response[$p['CheckContentName']]) && !empty($response[$p['CheckContentName']])) {
          foreach ($response[$p['CheckContentName']] as $message) {
            if(isset($message)){
              $accumulated[] = $message;
            }
          }
        }
        else{
          $continue = false;
        }
      }
      else{
        if($p['CheckContentName'] == 'file'){
          $accumulated = $jsonResponse;
          $continue = false;
        }
        else{
          $accumulated = $response;
          $continue = false;
        }  
      }
    
      $ProcesParam['pageIndex']++;

      }
    }

    curl_close($this->ch);
    return ['data' => $accumulated];
  }
}
