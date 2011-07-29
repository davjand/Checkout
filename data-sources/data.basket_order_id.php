<?php 
    
  Final Class datasourceBasket_Order_Id Extends DataSource{     

    function about(){
      return array(
           'name' => 'Basket: Order Id',
           'author' => array(
              'name' => 'David Anderson',
              'website' => 'http://veodesign.co.uk',
              'email' => 'dave@veodesign.co.uk'),
           'version' => '1.0',
           'description' => 'Returns the Order ID of the current Basket Item',
           'release-date' => '2011-26-06'); 
    }

    
		public function grab(&$param_pool){
			
			$id = $_SESSION[__SYM_COOKIE_PREFIX_ . 'basket'];
			
			$xml = new XMLElement('basket-order-id');
			if(empty($id)) {
				$xml->appendChild(new XMLElement('empty'));
				return $xml;
			}

			$param_pool['ds-basket-order-id'] = $id;
			
			$xml->setAttributeArray(array("order-id"=>$id));
			
			return $xml;

		}
  }
