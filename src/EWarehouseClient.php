<?php 
namespace MCS;

use DateTime;
use Exception;

class EWarehouseClient {
    
    const URL = 'http://connect.e-warehouse.eu/api/';
    const VERSION = '1.0';
    
    const DATE_FORMAT_HASH = 'n-j-Y H:i:s';
    const DATE_FORMAT_REQUEST_MESSAGE = '#j/n/Y h:i:s A#';
    
    private $username = null;
    private $password = null;
    private $userid = null;
    private $customerid = null;
    private $tokenJar = null;
    private $token = null;
    
    public function __construct($username, $password, $userid, $customerid)
    {
        if (!isset($username) || !isset($password) || !isset($userid)) {
            throw new Exception('Missing constructor parameter!');    
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
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ]
        ];
        
        if ($endpoint != 'Authentication') {
            if (is_null($this->token)) {
                $options[CURLOPT_HTTPHEADER][] = 'validation_key: ' . $this->getToken();
            } else {
                $options[CURLOPT_HTTPHEADER][] = 'validation_key: ' . $this->token;
            }
        }

        if (!is_null($data)) {
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data, JSON_UNESCAPED_SLASHES);
            }
            $options[CURLOPT_POSTFIELDS] = $data;
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        
        $response = json_decode($response, true);
        
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
    
    public function postOrder($order = [])
    {
        return $this->request('POST', 'Orders/', [$order]);
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
    
    public function getProduct($id)
    {
        return $this->request('GET', 'Products/' . $id);       
    }
    
    public function searchProduct($arguments = [])
    {
        $response = $this->request('GET', 'Products/' . '?count=1&' . http_build_query($arguments));       
        
        if (isset($response[0])) {
            return $response[0];
        } else {
            return false;
        }
    }
    
    public function setToken($token)
    {
        $this->token = $token;    
    }
    
    public function getToken()
    {
        //ISO8601
        
        $datetime = new DateTime(); 
        
        $hash = base64_encode(
            md5(
                $datetime->format(DateTime::ATOM) . $this->customerid . $this->username . $this->password, true
            )
        );
        
        $response = $this->request('POST', 'Authentication', [
            'UserId' => (int) $this->userid,
            'Timestamp' => $datetime->format(DateTime::ATOM),
            'Hash' => $hash
        ]);
        
        if (!isset($response['ValidationKey'])) {
            throw new Exception('No ValidationKey');    
        }
        return $response['ValidationKey'];
    }
}
