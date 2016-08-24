<?php 
namespace MCS;

use DateTime;
use Exception;

class EWarehouseClient{
    
    const URL = 'http://connect.e-warehouse.eu/api/';
    const VERSION = '1.0';
    
    private $username = null;
    private $password = null;
    private $userid = null;
    private $customerid = null;
    private $tokenJar = null;
    
    public function __construct($username, $password, $userid, $customerid)
    {
        if (!isset($username) || !isset($password) || !isset($userid)) {
            throw new Exception('Missing __construct parameter!');    
        } else {
            $this->username = $username;    
            $this->password = $password;    
            $this->userid = $userid;    
            $this->customerid = $customerid;    
        }
        
        $strpos = strpos($this->customerid, '-');
        if ($strpos !== false) {
            $this->customerid = substr($this->customerid, $strpos + 1);
        }
    }
    
    private function request($method, $endpoint, $data = null)
    {
        
        $options = [
            CURLOPT_URL => self::URL . $endpoint,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_VERBOSE => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ];
        
        if ($endpoint != 'Authentication') {
            $options[CURLOPT_HTTPHEADER][] = 'validation_key: ' . $this->getToken();
        }

        if (!is_null($data)) {
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data);
                //echo $data;
            }
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        
        if (isset($response['Status']) && $response['Status'] == 'success') {
            return $response['Content'];    
        } else {
            return $response;    
        }
        
        
    }
    
    public function getOrder($id)
    {
        return $this->request('GET', 'Orders/' . $id);   
    }
    
    public function getStock($arguments = [])
    {
        return $this->request('GET', 'Stock/');       
    }
    
    public function postProduct($data = [])
    {
        $product = array_merge([
            'Name' => '', 
            'Description' => '',
            'VATType' => 'High',
            'CustomID' => isset($data['SKU']) ? $data['SKU'] : '',
            'Measurement' => [
                'Weight' => 200,
                'Width' => 120,
                'Height' => 120,
                'Depth' => 60
            ],
            'Barcode' => isset($data['EAN']) ? $data['EAN'] : '',
            'EAN' => '',
            'SKU' => '',
            'ArticleCode' => '',
            'isSlaveProduct' => false,
            'isCombinationProduct' => false,
            'hasMinimumStock' => false,
            'MinimumStock' => 0,
        ], $data);
                               
        
        return $this->request('POST', 'Products/', [$product]);
    }
    
    public function getProducts($arguments = [])
    {
        return $this->request('GET', 'Products/');       
    }
    
    public function getToken()
    {
        $now = new DateTime();
        
        $hash = base64_encode(
            md5(
                $now->format('j-n-Y H:i:s') . $this->customerid . $this->username . $this->password, true
            )
        );
         
        $response = $this->request('POST', 'Authentication', [
            'UserId' => (int) $this->userid,
            'Timestamp' => $now->format('#n/j/Y h:i:s A#'),
            'Hash' => $hash
        ]);
        
        return $response['ValidationKey'];
    }
  
    
}
