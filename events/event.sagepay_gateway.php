<?php
	require_once(EXTENSIONS . '/symql/lib/class.symql.php');
	require_once(EXTENSIONS . '/checkout/lib/sagePayIncludes.php');
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/class.entrymanager.php');	
	require_once(TOOLKIT . '/class.fieldmanager.php');	
	require_once(TOOLKIT . '/class.extensionmanager.php');

	Final Class eventSagepay_gateway extends Event{
		protected $driver;
		
		private $_order_id;

		public static function about(){
					
			return array(
						 'name' => 'Sagepay Gateway',
						 'author' => array('name' => 'David Anderson',
										   'website' => 'http://veodesign.co.uk',
										   'email' => 'dave@veodesign.co.uk'),
						 'version' => '1.0',
						 'release-date' => '2011-05-07',
					);
		}

    public static function documentation(){
      return '<h3>Adds SagePay Crypt Data</h3>';
    }

		public function load(){
			
			return $this->__trigger();
		}


		protected function __trigger(){
		
			if (empty($this->driver)){
				$this->driver = Symphony::ExtensionManager()->create('checkout');
			}
			
			$entryManager = new EntryManager(Symphony::Engine());
			$fieldManager = new FieldManager(Symphony::Engine());
			
			$orderSectionId=$this->driver->getConfig('orders-section');
			$orderItemSectionId=$this->driver->getConfig('order-items-section');
			$statusFieldId=$this->driver->getConfig('status-field');
			
			$xml = new XMLelement('sagepay-gateway');
			
			$flag=false;	
			//check the cookie
			if(isset($_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'])){
					
				$id=$_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'];
				$entries = $entryManager->fetch($id,$orderSectionId);	
					
				
				if($entries){					
					$entry = $entries[0];
					
					//check it is in the right section
					if($entry->get('section_id')==$orderSectionId){
						
						//check that it isn't a completed order or something stupid!
						$status = $entry->getData($statusFieldId);

						if( $status['handle'] =='checkout-pay' ){							
							$this->_order_id=$id;
							$flag=true;
						}
					}					
				}
			}
				
			if($flag){

				$query = new SymQLQuery();
				$query 	->select("*")
						->from($orderSectionId)
						->where("system:id",$this->_order_id);
				$result = SymQL::run($query,SymQL::RETURN_ARRAY);
	
				$record = $result['entries'][0]['entry'];
				
				//print_r($record);
				
				$crypt = array();
				
				$crypt['VendorTxCode']=$record['ipn-id']['value'];
				$crypt['Amount']=$record['order-total']['value'];
				$crypt['Currency']="GBP";
				$crypt['Description']="Order with CoreFlooring";
				$crypt['SuccessURL']="http://coreflooring.co.uk/checkout/confirm";
				$crypt['FailureURL']="http://coreflooring.co.uk/checkout/fail";
				$crypt['CustomerName']=cleanInput($record['first-name']['value']." ".$record['last-name']['value']);
				$crypt['CustomerEmail']=$record['email']['value'];
				
				$crypt['VendorEmail']="info@coreflooring.co.uk";
				$crypt['SendEmail']=1;
				//$crypt['eMailMessage']=;
				$crypt['BillingSurname']=cleanInput($record['last-name']['value']);
				$crypt['BillingFirstnames']=cleanInput($record['first-name']['value']);
				$crypt['BillingAddress1']=cleanInput($record['billing-address-1']['value']);
				$crypt['BillingAddress2']=cleanInput($record['billing-address-2']['value']);
				$crypt['BillingCity']=cleanInput($record['billing-city']['value']);
				$crypt['BillingPostCode']=cleanInput($record['billing-postcode']['value']);
				$crypt['BillingCountry']=cleanInput($record['billing-country']['item']['value']);
				
				//$crypt['BillingState']=;
				$crypt['BillingPhone']=cleanInput($record['phone']['value']);
				$crypt['DeliverySurname']=cleanInput($record['last-name']['value']);
				$crypt['DeliveryFirstnames']=cleanInput($record['first-name']['value']);
				$crypt['DeliveryAddress1']=cleanInput($record['address-1']['value']);
				$crypt['DeliveryAddress2']=cleanInput($record['address-2']['value']);
				$crypt['DeliveryCity']=cleanInput($record['city']['value']);
				$crypt['DeliveryPostCode']=cleanInput($record['postcode']['value']);
				$crypt['DeliveryCountry']=cleanInput($record['country']['item']['value']);
				//$crypt['DeliveryState']=;
				$crypt['DeliveryPhone']=cleanInput($record['phone']['value']);
				//$crypt['Basket']=;
				//$crypt['AllowGiftAid']=;
				
				//$crypt['ApplyAVSCV2']=;
				//$crypt['Apply3DSecure']=;
				//$crypt['BillingAgreement']=;*/

				//print_r($crypt);
				
				//implode the array
				$strCrypt="";
				$i=1;
				foreach($crypt as $k => $v){
					$strCrypt = $strCrypt.$k."=".$v;
					
					if($i < count($crypt)){
						$strCrypt = $strCrypt."&";		
					}
					$i++;
				}
				
				$cryptVal = encryptAndEncode($strCrypt);
				
				$xml->appendChild(new XMLElement('crypt', $cryptVal));
				
			}
			
			
			return $xml;
				

		}
	}