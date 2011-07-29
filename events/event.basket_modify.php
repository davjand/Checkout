<?php
	require_once(EXTENSIONS . '/symql/lib/class.symql.php');
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/class.entrymanager.php');	
	require_once(TOOLKIT . '/class.fieldmanager.php');	
	require_once(TOOLKIT . '/class.extensionmanager.php');

	Final Class eventBasket_Modify extends Event{
		protected $driver;
		
		private $_s;
		private $_field_id;

		private $_error = false;
		private $_msg = null;
		
		private $_id;
		private $_quantity;
		private $_options;
		
		private $_order_id;
		private $_order_item_id;

		public static function about(){
					
			return array(
						 'name' => 'Modify Basket',
						 'author' => array('name' => 'David Anderson',
										   'website' => 'http://veodesign.co.uk',
										   'email' => 'dave@veodesign.co.uk'),
						 'version' => '1.0',
						 'release-date' => '2011-26-06',
					);
		}

    public static function documentation(){
      return '<h3>Event XML example</h3>
<pre><code>'. htmlentities('<basket action="add|update|empty" result="success|error">
	<msg>Text message</msg>
</basket>').'
</code></pre>

<h3>Example Front-end Form Markup and GET queries</h3>

<h4>Add/Update an item to a cart:</h4>
<p>This can be used to add items, update the quantity of items (if applicable then options must be provied) or remove using quantity=0</p>
<pre><code>'. htmlentities('<form method="post" action="">
	<input type="hidden" name="id" value="42"/>
	
	<!--Optional, defaults to 1-->
	<input type="text" name="quantity" value="5"/>
	
	<!--Optional for product options-->
	<select name="option">
		<option id="1">Small</option>
		<option id="2">Medium</option>
		<option id="3">Large</option>
	</select>
	<input type="submit" name="basket-action[update]" value="Add item"/>
</form>').'
</code></pre>
<p>GET analogue:</p>
<pre>?basket-action=update&amp;id=42&amp;quantity=5&amp;options=1</pre>

<h4>Empty the Basket</h4>
<pre><code>'. htmlentities('<form method="post" action="">
	<input type="submit" name="basket-action[empty]" value="Empty Basket"/>
</form>').'
</code></pre>
<p>GET analogue:</p>
<pre>?basket-action=empty</pre>
';
    }

		public function load(){
			if(isset($_REQUEST['basket-action']) && !empty($_REQUEST['basket-action'])){
				return $this->__trigger();
			}
		}


		protected function __trigger(){
			
			if (empty($this->driver)){
				$this->driver = Symphony::ExtensionManager()->create('checkout');
			}
			
			$allowedActions = array('add','update','empty');
			
			$xml = new XMLelement('basket');

			if($_GET['basket-action']){
				$action = $_GET['basket-action'];
			} else {
				list($action) = array_keys($_POST['basket-action']);
			}

			if(!in_array($action,$allowedActions)) {
				$this->_error = true;
				$this->_msg = __('Unaccepted action');
			}

			if(!$this->_error) {
				
				if($this->updateDataIsValid()){
					
					if($action=="add"){
						$this->add();
					}
					if($action=="update"){
						$this->update();
					}
				
				}
			}
			
			$xml->setAttributeArray(array('action' => General::sanitize($action), 'result' => $this->_error == true ? 'error' : 'success'));
			$xml->appendChild(new XMLElement('msg', $this->_msg));
			return $xml;
			
		}
    
		protected function add(){
			$this->update('add');	
		}

		protected function update($mode = "update" ){
			
			//if cookie is set
			$this->_order_id=NULL;
			
			$entryManager = new EntryManager(Symphony::Engine());
			$fieldManager = new FieldManager(Symphony::Engine());
			
			$orderSectionId=$this->driver->getConfig('orders-section');
			$orderItemSectionId=$this->driver->getConfig('order-items-section');
			$statusFieldId=$this->driver->getConfig('status-field');
			
			$itemQuantityField=$this->driver->getConfig('quantity-field');
			$itemProductLinkField=$this->driver->getConfig('product-link-field');
			$itemOrderLinkField=$this->driver->getConfig('order-link-field');
			$itemProductOpField=$this->driver->getConfig('product-option-link-field');
			
			
			if(isset($_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'])){
					
				$id=$_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'];
				$entries = $entryManager->fetch($id,$orderSectionId);	
						
				if($entries){
					
					$entry = $entries[0];
					
					//check it is in the right section
					if($entry->get('section_id')==$orderSectionId){
						
						//check that it isn't a completed order or something stupid!
						$status = $entry->getData($statusFieldId);

						if( 	$status['handle'] =='basket' ||
								$status['handle'] =='checkout' ||
								$status['handle'] =='checkout-ship' ||
								$status['handle'] =='checkout-pay' ||
							 	strpos(strtolower($status['handle']),'basket') ||
							 	strpos(strtolower($status['handle']),'checkout')){
							
							$this->_order_id=$id;
						}
					}					
				}
			}

		//see if we need to create an orders entry
			if($this->_order_id==NULL){
				
				$orderEntry = $entryManager->create();					
				$orderEntry->set('section_id',$orderSectionId);				
				$orderId=$orderEntry->assignEntryId();		
				$orderEntry->setData($statusFieldId,array('handle'=>'basket','value'=>'Basket'));
				
				$orderEntry->commit();
				
				//save in the session
				$_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'] = $orderId;
				$this->_order_id = $orderId;
				
			}
			
		//Next we need to see if there is an order items entry for this product
			$this->_order_item_id=NULL;
			$prevQuantity=0;
			
			$query = new SymQLQuery();
			$query 	->select("*")
					->from($orderItemSectionId)
					->where($itemProductLinkField,$this->_id, SymQL::DS_FILTER_AND)
					->where($itemOrderLinkField,$this->_order_id, SymQL::DS_FILTER_AND);
			$result = SymQL::run($query,SymQL::RETURN_ENTRY_OBJECTS);

			$entries = $result['entries'];
			
		//Looks like we'll be updating the quantity unless options are set			
			if($entries){	

								
				//loop as if there are product options then the same entry can get added to the basket

				//THIS CODE IS TO ALLOW FOR MUTIPLE PRODUCT OPTIONS TO BE ADDED TO BASKET
				//FOR NOW, SCRAP THIS FUNCTION
				
				
				if($this->_options){
					if(count($entries)>1){				
						foreach($entries as $eid => $entry){
					
							$productOptions=$entry->getData($itemProductOpField);
							if($productOptions['relation_id'] == $this->_options){
								
								$this->_order_item_id=$eid;
								break;	
							}
						}
					}
					else{
						$curKeys=array_keys($entries);
						$eid=$curKeys[0];
						$entry = $entries[$eid];
						
						$productOptions=$entry->getData($itemProductOpField);

						if($productOptions['relation_id'] == $this->_options){
							$this->_order_item_id=$eid;
							
						}				
					}
				}
				else{
					$this->_order_item_id=key($entries);	
				}
				
			
				
				
				//update the quantities	 or options
				if($this->_order_item_id!=NULL){				
					
					$orderItemEntries = $entryManager->fetch($this->_order_item_id,$orderSectionId);
					$orderItemEntry=$orderItemEntries[0];
					
										
					if($this->_quantity==0){
						
						$entryManager->delete($orderItemEntry->get('id'));						
						return $this->_msg = __('Item removed from basket');	
					}
					elseif($mode == 'add'){
						
						$prevQuantity=$entries[$this->_order_item_id]->getData($itemQuantityField);
						$totalItems = $prevQuantity['value'] + $this->_quantity;
									
						$orderItemEntry->setData($itemQuantityField, array('value' => $totalItems));
						$orderItemEntry->setData($itemProductOpField,array('relation_id' =>$this->_options));
						
						$entryManager->edit($orderItemEntry);

						return $this->_msg = __('Item quantity updated');		
					}
					else{
						
						$orderItemEntry->setData($itemQuantityField, array('value' => $this->_quantity));
						$orderItemEntry->setData($itemProductOpField,array('relation_id' =>$this->_options));
						
						$entryManager->edit($orderItemEntry);

						return $this->_msg = __('Item quantity updated');	
					}	
				}
			}
			
			//do we need to update the quantity in an existing record
						
			//do we need to create a record?
			if($this->_order_item_id == NULL){
				
				$orderItemEntry = $entryManager->create();					
				$orderItemEntry->set('section_id',$orderItemSectionId);				
				$this->_order_item_id=$orderItemEntry->assignEntryId();	
						
				$orderItemEntry->setData($itemOrderLinkField,array('relation_id' =>$this->_order_id));
				$orderItemEntry->setData($itemProductLinkField,array('relation_id'=>$this->_id));
				$orderItemEntry->setData($itemQuantityField,array('value' => $this->_quantity));
				$orderItemEntry->setData($itemProductOpField,array('relation_id' =>$this->_options));
				
				$orderItemEntry->commit();
			}


			return $this->_msg = __('Item added to cart');			
		}
		
		
		
		protected function emptyBasket(){
		
			$this->_s = null;
			return $this->_msg = __('All items are dropped');
		
		}


		protected function updateDataIsValid(){
		
			if(empty($_REQUEST['id']) || !is_numeric($_REQUEST['id'])){
				$this->_error = true;
				$this->_msg = __('ID is not set or is invalid');
				return false;
			}
			
			$this->_id = $_REQUEST['id'];
			$this->_quantity = isset($_REQUEST['quantity']) ? $_REQUEST['quantity'] : '1';
			$this->_options = isset($_REQUEST['options']) ? $_REQUEST['options'] : NULL;
			
			if($this->_options== "-1") return false;

			return true;
		
		}


	}