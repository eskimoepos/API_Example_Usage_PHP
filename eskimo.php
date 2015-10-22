<?php
require 'php-curl-class-master/src/Curl/Curl.php';

use \Curl\Curl;

//CREDENTIALS - CAN BE MOVED TO EXTERNAL FILE, DATABASE OR ANOTHER METHOD
define('DOMAIN', 'https://api.eskimoepos.com/');
define('USERNAME', 'name@domin.com');
define('PASSWORD', 'password');

class Eskimo
{

	var $debug = FALSE;
	
	public function __construct()
    {
        if(session_status() === PHP_SESSION_NONE) 
       	{
            session_start();
            
            if(DOMAIN == '')
            {
            	echo 'No Domain URL Provided';
            	exit();
            }
            elseif(USERNAME == '')
			{
				echo 'No Username Provided';
            	exit();
			}
            elseif(PASSWORD == '')
			{
            	echo 'No Password Provided';
            	exit();
			}
            $this->authenticate();
        }
    }
    

    public function authenticate()
    {
        if(isset($_SESSION['authenticated']) && $_SESSION['authenticated']) 
        {
            return;
        }
        $this->getAccessToken();
    }
    
    
   	private function getOAuthParameters()
    {
        return array(
        	'username' => USERNAME,
        	'password' => PASSWORD,
        	'grant_type' => 'password',
        );
    }
    
    
    private function getAccessToken()
    {
        $oauth_data = $this->getOAuthParameters();
        
        $access_token_url = DOMAIN.'token';
        
        $curl = new Curl();
        $curl->post($access_token_url, $oauth_data);
        
    	if($curl->error)
    		return $this->apiError($curl);
    	else
    	{
    		$_SESSION['access_token'] = $curl->response->access_token;
        	$_SESSION['authenticated'] = true;
        }
   	}
    
    
    public function apiError($curl)
    {
    	if($this->debug)
    	{
    		var_dump($curl->request_headers);
			var_dump($curl->response_headers);
    	}

    	$arr['Error'] = $curl->Message;
    	if(isset($curl->ExceptionMessage))
    		$arr['ErrorException'] = $curl->ExceptionMessage;
    	return $arr;
    	exit;
    }

    
    public function getData($api_url, $api_opts = FALSE)
    {
		$curl = new Curl();
        $curl->setHeader('Authorization', 'Bearer ' . $_SESSION['access_token']);
        $curl->get($api_url, $api_opts);
        
        if($this->debug)
        	var_dump($curl);
        
        if($curl->error)
    		return $this->apiError($curl->response);
    	else
    		return array('Eskimo' => $curl->response);    
    }
    
    
    public function postData($api_url, $api_opts = FALSE)
    {
		$curl = new Curl();
    	$curl->setHeader('Content-Type', 'application/json');
        $curl->setHeader('Authorization', 'Bearer ' . $_SESSION['access_token']);
        $curl->post($api_url, $api_opts);
        
        if($this->debug)
        	var_dump($curl);
        
        if($curl->error)
    		return $this->apiError($curl->response);
    	else
    		return array('Eskimo' => $curl->response);    
    }
    
    
    /****************************
    *****************************
    USEFUL FUNCTIONS
    *****************************
    ****************************/
    
	
	/****************************
    TAX CODES
    ****************************/
   	public function TaxCodes($id = FALSE)
    {
    	$api_url = ($id) ? DOMAIN."api/TaxCodes/SpecificID/$id" : DOMAIN.'api/TaxCodes/All';
    	return $this->getData($api_url);
    }
    
    
    /****************************
    SKUs
    ****************************/
   	public function SKUs($sku_product = FALSE, $id = FALSE)
    {
    	$api_url = DOMAIN.'api/SKUs/All';
    	
    	if($sku_product && $id)
    	{
    		if($sku_product == 'sku')
    			return $this->getData(DOMAIN."api/SKUs/SpecificSKUCode/$id");
    		elseif($sku_product == 'product')
    			return $this->getData(DOMAIN."api/SKUs/SpecificIdentifier/$id");
    		else
    		{
    			$curl->Message = "SKU ERROR - $id";
    			$this->apiError($curl);
    		}	
    	}
    	else
    		return $this->postData($api_url);
    }
    
    
    /****************************
    IMAGES
    ****************************/
   	public function ImageLinksAll($api_opts)
    {
    	$api_url = DOMAIN.'api/ImageLinks/All';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    public function ImagesAll($api_opts)
    {
    	$api_url = DOMAIN.'api/Images/All';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    public function ImagesImageDataID($id) //eskimo image ID
    {
    	$api_url = DOMAIN."api/Images/ImageData/$id";
    	return $this->getData($api_url);
    }
    
    
    /****************************
    PRODUCTS
    ****************************/
   	public function ProductsAll($api_opts)
    {
    	$api_url = DOMAIN.'api/Products/All';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    
    public function ProductsSpecificID($id)
    {
    	$api_url = DOMAIN."api/Products/SpecificID/$id";	
    	return $this->getData($api_url);
    }
    
    
    public function ProductsUpdateCartIDs($api_opts)
    {
    	$api_url = DOMAIN."api/Products/UpdateCartIDs";
    	$api_opts = json_encode($api_opts);
    	return $this->postData($api_url, $api_opts);
    }
    
    
    /****************************
    CATEGORIESPRODUCTS
    ****************************/
   	public function CategoryProductsAll($api_opts)
    {
    	$api_url = DOMAIN.'api/CategoryProducts/All';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    
    public function CategoryProductsSpecificCategory($id)
    {
    	$api_url = DOMAIN."api/CategoryProducts/SpecificCategory/$id";	
    	return $this->getData($api_url);
    }
    
    
    /****************************
    SHOPS
    ****************************/
   	public function ShopsAll()
    {
    	$api_url = DOMAIN.'api/Shops/All';
    	return $this->getData($api_url);
    }
    
    
    public function ShopsSpecificID($id)
    {
    	$api_url = DOMAIN."api/Shops/SpecificID/$id";	
    	return $this->getData($api_url);
    }
    
    
    /****************************
    CUSTOMERS
    ****************************/
   	public function CustomersSpecificID($id)
    {
    	$api_url = DOMAIN."api/Customers/SpecificID/$id";	
    	return $this->getData($api_url);
    }
    
    
    public function CustomersCreate($api_opts)
    {
    	$api_url = DOMAIN.'api/Customers/Insert';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    
    public function CustomersUpdate($api_opts)
    {
    	$api_url = DOMAIN.'api/Customers/Update';	
    	$api_opts = json_encode($api_opts); 
    	return $this->postData($api_url, $api_opts);
    }
    
    
    /****************************
    CATEGORIES
    ****************************/
   	public function CategoriesAll()
    {
    	$api_url = DOMAIN.'api/Categories/All';
    	return $this->getData($api_url);
    }
    
    
    public function CategoriesSpecificID($id)
    {
    	$api_url = DOMAIN."api/Categories/SpecificID/$id";	
    	return $this->getData($api_url);
    }
    
    
    public function CategoriesSpecificParentID($id)
    {
    	$api_url = DOMAIN."api/Categories/SpecificParent/$id";	
    	return $this->getData($api_url);
    }
   	
   	
   	public function CategoriesUpdateCartIDs($api_opts)
    {
    	$api_url = DOMAIN."api/Categories/UpdateCartIDs";
    	$api_opts = json_encode($api_opts);
    	return $this->postData($api_url, $api_opts);
    }
    
    
    /****************************
    ORDERS
    ****************************/
   	public function OrdersInsert($api_opts)
    {
    	$api_url = DOMAIN.'api/Orders/Insert';	
    	$api_opts = json_encode($api_opts);
    	return $this->postData($api_url, $api_opts);
    }
    
}
?>