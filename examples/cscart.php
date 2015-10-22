<?php
require_once '../eskimo.php';

define('BOOTSTRAP', TRUE); //CSCART HELPER
require_once '../config.local.php'; //ASSUMING CSCART INSTALLED IN ROOT DIRECTORY

require_once '../meekrodb/db.class.php'; //FOR TESTING AND DEMONSTRATION PURPOSES

class cscart
{
	//ASSUMPTIONS
	private $company_id = 1;
	private $lang_code = 'en';
	private $product_qty = 1;

	public function __construct($config)
    {
        $this->eskimo = new Eskimo();

		DB::$user = $config['db_user'];
		DB::$password = $config['db_password'];
		DB::$dbName = $config['db_name'];
		DB::$encoding = 'utf8';
		//DB::debugMode();
    }
    
    public function is_error($arr)
    {     
        if(isset($arr['Error']))
        	print_r($arr['Error']);
        
        if(isset($arr['ErrorException']))
        	print_r($arr['ErrorException']);
        		
       	exit;
    }


    /**********************************************************************************
    CATEGORIES
    **********************************************************************************/
    public function ul_categories()
    {
    	$arr_return_data = array();
    	
   		$res_eskimo = $this->eskimo->CategoriesAll();

		if(is_array($res_eskimo['Eskimo']))
		{
			$arr_children = array();
			$arr_parent = array();
			
			foreach($res_eskimo['Eskimo'] as $arr_eskimo)
			{
				if($arr_eskimo->ParentID == NULL)
					$arr_parent[] = $arr_eskimo;
				else
					$arr_children[] = $arr_eskimo;
			}

			if(!empty($arr_parent))
				foreach($arr_parent as $arr_eskimo)
				{
					$params = $this->sort_categories($arr_eskimo);
					$arr_return_data[] = $this->ul_category($params);
				}
				
			if(!empty($arr_children))
				foreach($arr_children as $arr_eskimo)
				{
					$params = $this->sort_categories($arr_eskimo);
					$arr_return_data[] = $this->ul_category($params);
				}
		}
		
		$arr_update_eskimo = array();
		foreach($arr_return_data as $update_eskimo)
		{
			if($update_eskimo['msg'] == 'Inserted')
			{
				$arr_update_eskimo[] = array(
					'Eskimo_Category_ID' => $update_eskimo['id_eskimo'],
					'Web_ID' => $update_eskimo['id_cscart'],
				);
			}
		}
		
		$res_eskimo = $this->eskimo->CategoriesUpdateCartIDs($arr_update_eskimo);
		
		return $arr_return_data;
    }
    
    
    public function sort_categories($arr_eskimo)
    {
		$params = array(
			'categories' => array(
				'parent_id' => $arr_eskimo->ParentID,
				'company_id' => $this->company_id,
				'position' => $arr_eskimo->OrderByValue,
				'eskimo_categoryidFK' => $arr_eskimo->Eskimo_Category_ID,
			),
			'cscart_category_descriptions' => array(
				'lang_code' => $this->lang_code,
				'category' => $arr_eskimo->ShortDescription,
				'description' => $arr_eskimo->LongDescription,
			),
	    );
	    return $params;
    }
    
    
	public function ul_category($params)
	{	
		$msg = FALSE;
		$insertId = FALSE;
		
		if($params['categories']['parent_id'] > 0)
		{
			$rows = DB::queryFirstRow("SELECT category_id FROM cscart_categories WHERE eskimo_categoryidFK = %s", $params['categories']['parent_id']);
			if(DB::count() == 1)
			{
				$params['categories']['parent_id'] = $rows['category_id'];
				$params['categories']['id_path'] = $rows['category_id'].'/'.$params['categories']['parent_id'];
				$params['categories']['level'] = 2;
			}
			else
			{
				$msg = 'No Parent ID Found';
			}
		}

		if(!$msg)
		{
			$rows = DB::queryFirstRow("SELECT category_id FROM cscart_categories WHERE eskimo_categoryidFK = %s", $params['categories']['eskimo_categoryidFK']);
			if(DB::count() == 1)
			{
				DB::update('cscart_categories', array('position' => $params['categories']['position']), "category_id = %s", $rows['category_id']);
				DB::update('cscart_category_descriptions', $params['cscart_category_descriptions'], "category_id = %s", $rows['category_id']);
				
				$msg = 'Updated';
			}
			elseif(DB::count() == 0)
			{
				if($params['categories']['parent_id'] == NULL) 
					$params['categories']['parent_id'] = 0;
					
				DB::insert('cscart_categories', $params['categories']);
				$params['cscart_category_descriptions']['category_id'] = $insertId = DB::insertId();
				DB::insert('cscart_category_descriptions', $params['cscart_category_descriptions']);
				
				if($params['categories']['parent_id'] > 0)
				{
					DB::update('cscart_categories', 
						array('id_path' => $params['categories']['parent_id'].'/'.$insertId), 
						"category_id = %s", $insertId
					);
				}
				else
				{
					DB::update('cscart_categories', 
						array('id_path' => $insertId), 
						"category_id = %s", $insertId
					);
				}
				$msg = 'Inserted';
			}
			else
				$msg = 'Fail';
		}

		return array('id_eskimo' => $params['categories']['eskimo_categoryidFK'], 'id_cscart' => $insertId, 'msg' => $msg);
	}
	
	
	/**********************************************************************************
    PRODUCTS
    **********************************************************************************/
    public function ul_products($api_opts, $sku = FALSE)
    {
    	$arr_return_data = array();
    	$arr_update = array();
    	
    	$res_eskimo = $this->eskimo->ProductsAll($api_opts);
    	
		if(is_array($res_eskimo['Eskimo']))
		{
			foreach($res_eskimo['Eskimo'] as $arr_eskimo)
			{
				$params = array(
					'cscart_products' => array(
						'eskimo_productidFK' => $arr_eskimo->eskimo_identifier,
						'company_id' => $this->company_id,
						'amount' => $this->product_qty,
						'timestamp' => time($arr_eskimo->date_created),
						'updated_timestamp' => time($arr_eskimo->last_updated),
						'details_layout' => 'default',
					),
					'cscart_product_descriptions' => array(
						'lang_code' => $this->lang_code,
						'product' => $arr_eskimo->short_description,
						'full_description' => $arr_eskimo->long_description,
						'meta_keywords' => $arr_eskimo->meta_keywords,
						'meta_description' => $arr_eskimo->meta_description,
						'page_title' => $arr_eskimo->page_title,
					),
					'cscart_product_sales' => array(
						'category_id' => $arr_eskimo->web_category_id,
					),
					'cscart_products_categories' => array(
						'category_id' => $arr_eskimo->web_category_id,
					),
					'cscart_product_prices' => array(
						'lower_limit' => 1,
					),
			    );
			    
			    $arr_return_data[] = $id = $this->ul_product($params);
			    $arr_update[] = array('Eskimo_Identifier' => $arr_eskimo->eskimo_identifier, 'Web_ID' => $id['id']);
			}
		}
		
		$this->eskimo->ProductsUpdateCartIDs($arr_update);
		
		return $arr_return_data;
    }
    

	public function ul_product($params)
	{	
		$msg = FALSE;

		if(!$msg)
		{
			$rows = DB::queryFirstRow("SELECT product_id FROM cscart_products WHERE eskimo_productidFK = %s", $params['cscart_products']['eskimo_productidFK']);
			if(DB::count() == 1)
			{
				$product_id = $rows['product_id'];
				DB::update('cscart_products', $params['cscart_products'], "product_id = %s", $rows['product_id']);
				DB::update('cscart_product_descriptions', $params['cscart_product_descriptions'], "product_id = %s", $rows['product_id']);
				DB::update('cscart_product_sales', $params['cscart_product_sales'], "product_id = %s", $rows['product_id']);
				DB::update('cscart_products_categories', $params['cscart_products_categories'], "product_id = %s", $rows['product_id']);
				DB::update('cscart_product_prices', $params['cscart_product_prices'], "product_id = %s", $rows['product_id']);
				
				$msg = 'Updated';
			}
			elseif(DB::count() == 0)
			{
				DB::insert('cscart_products', $params['cscart_products']);
				$product_id = DB::insertId();
				$params['cscart_product_descriptions']['product_id'] = $product_id;
				$params['cscart_product_sales']['product_id'] = $product_id;
				$params['cscart_products_categories']['product_id'] = $product_id;
				$params['cscart_product_prices']['product_id'] = $product_id;
				
				DB::insert('cscart_product_descriptions', $params['cscart_product_descriptions']);
				DB::insert('cscart_product_sales', $params['cscart_product_sales']);
				DB::insert('cscart_products_categories', $params['cscart_products_categories']);
				DB::insert('cscart_product_prices', $params['cscart_product_prices']);
				
				$msg = 'Inserted';
			}
			else
				$msg = 'Fail';
		}
		return array('id' => $product_id, 'msg' => $msg);
	}
	
	
	/**********************************************************************************
    TAX CODES
    **********************************************************************************/
    public function ul_tax_codes()
    {
    	$arr_return_data = array();
    	
   		$res_eskimo = $this->eskimo->TaxCodes();

		if(is_array($res_eskimo['Eskimo']))
		{
			foreach($res_eskimo['Eskimo'] as $arr_eskimo)
			{
				$params = array(
					'cscart_taxes' => array(
						'eskimo_taxidFK' => $arr_eskimo->TaxID,
						'status' => 'A',
					),
					'cscart_tax_descriptions' => array(
						'lang_code' => $this->lang_code,
						'tax' => $arr_eskimo->TaxDescription,
					),
					'cscart_tax_rates' => array(
						'destination_id' => 1, //ASSUMED
						'rate_value' => $arr_eskimo->TaxRate,
						'rate_type' => 'P',
					),
			    );
			    $arr_return_data[] = $this->ul_tax_code($params);
			}
				
		}
		return $arr_return_data;
    }	
    
    public function ul_tax_code($params)
	{	
		$msg = FALSE;

		$rows = DB::queryFirstRow("SELECT tax_id FROM cscart_taxes WHERE eskimo_taxidFK = %s", $params['cscart_taxes']['eskimo_taxidFK']);
		if(DB::count() == 1)
		{
			DB::update('cscart_tax_descriptions', $params['cscart_tax_descriptions'], "tax_id = %s", $rows['tax_id']);
			DB::update('cscart_tax_rates', $params['cscart_tax_rates'], "tax_id = %s", $rows['tax_id']);
		}
		else
		{
			DB::insert('cscart_taxes', $params['cscart_taxes']);
			$params['cscart_tax_descriptions']['tax_id'] = $params['cscart_tax_rates']['tax_id'] = DB::insertId();
			DB::insert('cscart_tax_descriptions', $params['cscart_tax_descriptions']);
			DB::insert('cscart_tax_rates', $params['cscart_tax_rates']);
			
			$msg = 'Inserted';		
		}
			
		return array('id' => $params['cscart_taxes']['eskimo_taxidFK'], 'msg' => $msg);
	}
	
    
    /****************************
    SKUS
    ****************************/
    public function ul_skus()
    {
    	$arr_return_data = array();
    	
    	$res_eskimo = $this->eskimo->SKUs();
    	
		if(is_array($res_eskimo['Eskimo']))
		{
			foreach($res_eskimo['Eskimo'] as $arr_eskimo)
			{
				$rows = DB::queryFirstRow("SELECT product_id FROM cscart_products WHERE eskimo_productidFK = %s", $arr_eskimo->eskimo_product_identifier);
				if(DB::count() == 1)
				{
					DB::update('cscart_products', array('amount' => $arr_eskimo->StockAmount), "product_id = %s", $rows['product_id']);
					$this->ul_sku_price($rows['product_id'], $arr_eskimo->SellPrice);
					$this->ul_sku_tax($rows['product_id'], $arr_eskimo->TaxCodeID);
				}
			}
		}
    }
    
    
    public function ul_sku_setup($params)
    {
    	$arr_return_data = array();
    	
    	foreach($params['skus'] as $key => $val)
    	{
    		DB::insert('cscart_product_options', $params['cscart_product_options']);
	    	$option_id = DB::insertId();
	    	$params['cscart_product_options_descriptions']['option_id'] = $option_id;
	    	$params['cscart_product_options_descriptions']['option_name'] = $key;
			DB::insert('cscart_product_options_descriptions', $params['cscart_product_options_descriptions']);	
	    	$arr_return_data[$key] = $option_id;
    	}
    	return($arr_return_data);
    }
    
    
    public function ul_sku_price($id, $price)
    {
		DB::update('cscart_product_prices', array('price' => $price), "product_id = %s", $id);
    }
    
    
    public function ul_sku_tax($product_id, $eskimo_tax_id)
    {
    	$rows = DB::queryFirstRow("SELECT tax_id FROM cscart_taxes WHERE eskimo_taxidFK = %s", $eskimo_tax_id);
		if(DB::count() == 1)
			DB::update('cscart_products', array('tax_ids' => $rows['tax_id']), "product_id = %s", $product_id);		
    }
    	
    
    public function ul_sku($params)
    {
		$rows = DB::queryFirstRow("SELECT product_id FROM cscart_products WHERE eskimo_productidFK = %s", $params['cscart_products']['eskimo_productidFK']);
		if(DB::count() == 1)
		{
			DB::update('cscart_products', array('list_price' => $params['cscart_products']['list_price']), "eskimo_productidFK = %s", $rows['product_id']);
			
			$msg = 'Updated';
		}
		else
			$msg = 'Fail';
	
		return array('eskimo_productidFK' => $params['cscart_products']['eskimo_productidFK'], 'msg' => $msg);
    }
    
    
	/**********************************************************************************
    ORDERS
    **********************************************************************************/
    public function ul_orders()
    {	
    	$rows = DB::query("
    		SELECT co.*, cu.eskimo_customeridFK
    		FROM cscart_orders co
    		LEFT JOIN cscart_users cu ON co.user_id = cu.user_id 
    	");
    	if(DB::count() > 0)
		{
	    	foreach($rows as $row)
			{
				$id = $row['order_id'];
				$api_opts = array(
				  	"order_id" 				=> $row['order_id'],
				  	"eskimo_customer_id" 	=> $row['eskimo_customeridFK'],
				  	"order_date" 			=> date('Y-m-d H:i:s', $row['timestamp']),
				  	"invoice_amount" 		=> $row['total'],
				  	"amount_paid" 			=> $row['timestamp'],
				  	"DeliveryAddress" 		=> array(
				  		"FAO"			=>	$row['b_firstname'].' '.$row['b_lastname'],
				  		"AddressLine1" 	=>	$row['b_address'],
				  		"AddressLine2" 	=>	$row['b_address_2'],
				  		"AddressLine3" 	=>	NULL,
				  		"PostalTown"	=>	$row['b_city'],
				  		"County"		=>	$row['b_state'],
				  		"CountryCode"	=>	$row['b_country'],
				  		"PostCode"		=>	$row['b_zipcode'],
				  	),
				  	"InvoiceAddress" 		=> array(
				  		"FAO"			=>	$row['s_firstname'].' '.$row['s_lastname'],
				  		"AddressLine1" 	=>	$row['s_address'],
				  		"AddressLine2" 	=>	$row['s_address_2'],
				  		"AddressLine3" 	=>	NULL,
				  		"PostalTown"	=>	$row['s_city'],
				  		"County"		=>	$row['s_state'],
				  		"CountryCode"	=>	$row['s_country'],
				  		"PostCode"		=>	$row['s_zipcode'],
				  	),
				  	"CustomerReference" 	=> $row['notes'],
				  	"DeliveryNotes" 		=> NULL,
				  	"ShippingRateID" 		=> $row['shipping_ids'],
				  	"ShippingAmountGross" 	=> $row['shipping_cost'],
				);
				
				$rows = DB::query("
					SELECT cod.amount, cod.price, cp.eskimo_productidFK 
					FROM cscart_order_details cod 
					LEFT JOIN cscart_products cp ON cod.product_id = cp.product_id 
					WHERE cod.order_id = $id
				");
	    		foreach($rows as $row)
				{
					$api_opts['OrderedItems'][] = array(
				  		"sku_code" 				=>	$row['eskimo_productidFK'],
				  		"qty_purchased"			=>	$row['amount'],
				  		"unit_price"			=>	$row['price'],
				  		"line_discount_amount"	=>	0,
				  		"item_note"				=>	NULL,				
					);
				}				
			}
			return $this->eskimo->OrdersInsert($api_opts);
		}
    }
    
    
    /**********************************************************************************
    CUSTOMERS
    **********************************************************************************/
    public function ul_customers()
    {	
    	$arr_return_data = array();
    	
		$rows = DB::query("
			SELECT cu.user_id, cu.firstname, cu.lastname, cu.company, cu.phone, cu.email, cup.b_address, cup.b_address_2, cup.b_city, cup.b_county, cup.b_state, cup.b_country, cup.b_zipcode
			FROM cscart_users cu 
			LEFT JOIN cscart_user_profiles cup ON cu.user_id = cup.user_id 
			WHERE cu.user_type = 'C'
		");
		if(DB::count() > 0)
		{
	    	foreach($rows as $row)
			{
				$api_opts = array(
					"ForeName" 				=> $row['firstname'],
					"Surname" 				=> $row['lastname'],
					"CompanyName" 			=> $row['company'],
					"Notes" 				=> NULL,
					"Telephone" 			=> $row['phone'],
					"Mobile" 				=> NULL,
					"EmailAddress" 			=> $row['email'],
					"ActiveAccount" 		=> TRUE,
					"Address" 				=> $row['b_address']."\n".$row['b_address_2']."\n".$row['b_city']."\n".$row['b_county']."\n".$row['b_state'],
					"PostCode" 				=> $row['b_zipcode'],
					"TitleID" 				=> 1, //DOES NOT EXIST AT ESKIMO
					"CountryCode" 			=> $row['b_country'],
				);		
				$res_eskimo = $this->eskimo->CustomersCreate($api_opts);	
				
				if($res_eskimo['Eskimo'])
					if($res_eskimo['Eskimo']->ID != NULL)
						DB::update('cscart_users', array('eskimo_customeridFK' => $res_eskimo['Eskimo']->ID), "user_id = %s", $row['user_id']);
						
			}
		}
		
		$arr_return_data[] = array('user_id' => $row['user_id'], 'msg' => 'Inserted');
		
		return $arr_return_data;
    }
    
}
?>