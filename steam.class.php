<?php

class Steam {
  private $ch;
  private $dom;
  private $sleepSecond = 40;

  private $itemsList = [];

  private $referer = 'https://steamcommunity.com';
  private $url = 'https://steamcommunity.com';
  private $source = '/market/search/render';
  private $appid;


  function __construct($appid) {
    $this->ch = curl_init();
    $this->appid = $appid;
  }

  function __destruct() {
    curl_close($this->ch);
  }

  function getHeaders() {
    $headers = array(
      'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/79.0.3945.79 Chrome/79.0.3945.79 Safari/537.36',
      //'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
      //'Accept-Encoding: gzip, deflate, br',
      'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
      //'Cache-Control: no-cache',
      //'Connection: keep-alive'
    );
    return $headers;
  }


  private function getApiUrl($start, $count) {
    return $this->url . $this->source . '?start=' . $start . '&count=' . $count . '&appid=' . $this->appid .'&norender=1';
  }


  public function getPage($headers, $fields, $start, $count) {
    curl_setopt($this->ch, CURLOPT_TIMEOUT, 10); 
    curl_setopt($this->ch, CURLOPT_URL, $this->getApiUrl($start, $count));
    if (is_array($headers) AND count($headers) > 0) curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->referer);
    
    $response = json_decode(curl_exec($this->ch));

    if (curl_errno($this->ch)) { 
      print curl_error($this->ch);
    } 

    if (isset($response->success)) {
      $this->dom = $response->results;
    } else {
      $this->dom = [];
    }  
  }


  public function getItemList($count = 100, $saveToCSV = true) {
    
    $start = 0;

    while (true) {
      $this->getPage($this->getHeaders(), null, $start, $count);

      if (count($this->dom) == 0) {
        break;
      }

      foreach ($this->dom as $row) {
        $this->itemsList[] = array(
          'name' => $row->name,
          'qty' => $row->sell_listings,
          'priceNormal' => preg_replace("/[^,.0-9]/", '', str_replace('.', ',', $row->sell_price_text)),
          'priceSale' => preg_replace("/[^,.0-9]/", '', str_replace('.', ',', $row->sale_price_text))
        );
        // EXAMPLE RESPONSE
  
        // name:"Кейс «Расколотая сеть»"
        // hash_name:"Shattered Web Case"
        // sell_listings:38560
        // sell_price:76
        // sell_price_text:"$0.76"
        // app_icon:"https://steamcdn-a.akamaihd.net/steamcommunity/public/images/apps/730/69f7ebe2735c366c65c0b33dae00e12dc40edbe4.jpg"
        // app_name:"Counter-Strike: Global Offensive"
        // asset_description:stdClass
        // sale_price_text:"$0.73"
      }

      $start += count($this->dom);
      echo $start . PHP_EOL;
      sleep($this->sleepSecond);
    }
    
    if ($saveToCSV) {
      $headers = array('name', 'qty', 'price normal','price sale');
      $this->saveToCSV($this->appid . '.csv', $headers, $this->itemsList);
    }
    return $this->itemsList;
  }


  public function saveToCSV($filename, $headers, $data) {
    $separate = ';';
    // open the file or create
    $file = fopen($filename, 'w');
    
    // save the column headers
    fputcsv($file, $headers, $separate);
    
    // save each row of the data
    foreach ($data as $row) {
      fputcsv($file, $row, $separate);
    }

    fclose($file);
  }
}

?>