<?php 
namespace MCS;

use MCS\EWarehouseClient;

class EWarehouseOrder {
    
    private $client;
    
    public $Reference;
    public $ShopID;
    public $Name = '';
    public $CompanyName = '';
    public $Address = '';
    public $PostalCode = '';
    public $City = '';
    public $Country = '';
    public $PhoneNumber = '';
    public $EmailAddress = '';
    public $Remark = '';
    
    public $OrderDetails;
    
    public function __construct(EWarehouseClient $client)
    {
        $this->client = $client;
    }
    
    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } 
    }
    
    public function send()
    {
        
        $message = [
            'Reference' => (string) $this->Reference,
            'ShopID' => (int) $this->ShopID,
            'OrderReceiver' => [
                'Name' => (string) $this->Name,
                'CompanyName' => (string) $this->CompanyName,
                'Address' => (string) $this->Address,
                'PostalCode' => (string) $this->PostalCode,
                'City' => (string) $this->City,
                'Country' => (string) $this->Country,
                'PhoneNumber' => (string) $this->PhoneNumber,
                'EmailAddress' => (string) $this->EmailAddress,
            ],
            'OrderDetails' => $this->OrderDetails
        ];
        
        if (strlen($this->Remark) > 0) {
            $message['Remark'] = (string) $this->Remark;    
        }
        
        $result = $this->client->postOrder($message);
        
        if (isset($result['CreatedOrders'])) {
            $result['CreatedOrders'][0]['success'] = true;
            return $result['CreatedOrders'][0];
        } else {
            return [
                'success' => false,
                'response' => $result
            ];
        }
    }
    
    public function addItem($id, $quantity) 
    {
        if (!is_array($this->OrderDetails)) {
            $this->OrderDetails = [];
        }
        
        $this->OrderDetails[] = [
            'ProductID' => (int) $id,
            'Quantity' => (int) $quantity 
        ];
            
    }
}
