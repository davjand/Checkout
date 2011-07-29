<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	
	require_once(TOOLKIT . '/class.datasourcemanager.php');		
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	
	class contentExtensionCheckoutPreferences extends AdministrationPage {
		protected $driver;
		
		public function __viewIndex() {
			$this->driver = Symphony::ExtensionManager()->create('checkout');
			$bIsWritable = true;
			
		    if (!is_writable(CONFIG)) {
		        $this->pageAlert(__('The Symphony configuration file, <code>/manifest/config.php</code>, is not writable. You will not be able to save changes to preferences.'), AdministrationPage::PAGE_ALERT_ERROR);
		        $bIsWritable = false;
		    }
			
			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; ' . __('Checkout Options'));
			
			$this->appendSubheading(__('Checkout Options'));
			
			//get all sections
			$sectionManager = new SectionManager($this);
			$sections = $sectionManager->fetch();
			
			
		// Section Settings --------------------------------------------------------
			
			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Section Settings'))
			);
						
			
			$container->appendChild(
				new XMLElement('p',__('Please select the section used for your Orders, Order Items and Products'))
			);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
		
		//  Order Section		
			
			$label = Widget::Label(__('Your Orders Section'));
			$this->__appendSectionSelect('orders-section',$label);			
			$group->appendChild($label);			
			
		//  Order Items Section
			
			$label = Widget::Label(__('Your Order Items Section'));
			$this->__appendSectionSelect('order-items-section',$label);			
			$group->appendChild($label);
			
			$container->appendChild($group);
			
		//  Product Section	
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Your Products Section'));
			$this->__appendSectionSelect('products-section',$label);			
			$group->appendChild($label);
			
			$label = Widget::Label(__('Your Product Options Section'));
			$this->__appendSectionSelect('product-options-section',$label);			
			$group->appendChild($label);		
			
			
			$container->appendChild($group);
			$this->Form->appendChild($container);	
		
		// Order Field Settings --------------------------------------------------------
			
			$orderSection=$this->driver->getConfig('orders-section');			

			
			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Order Field Settings'))
			);


			$p=new XMLElement('p',__('Please select the fields used for each property. Please consult the readme for more information'));
			$p2=new XMLElement('p');
			$p2->appendChild(
				new XMLElement('b',__('You must select the sections AND save first'))
				);
			
			$container->appendChild($p);
			$container->appendChild($p2);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
		
		//  Shipping Field
		
			$label = Widget::Label(__('The Shipping Field that holds the shipping option selection'));
			$this->__appendFieldSelect('shipping-field',$label,$orderSection);			
			$group->appendChild($label);			
		
		//  Gateway Field
				
			$label = Widget::Label(__('The Gateway Field that will store the gateway selection'));
			$this->__appendFieldSelect('gateway-field',$label,$orderSection);			
			$group->appendChild($label);
			
			$container->appendChild($group);
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
		
		//  Status Field	
		
			$label = Widget::Label(__('The Status Field that will store the status of the order'));
			$this->__appendFieldSelect('status-field',$label,$orderSection);			
			$group->appendChild($label);
		
		
		//  IPN Identifer Field
		
			$label = Widget::Label(__('The IPN Identifer field, used to store the IPN txn id'));
			$this->__appendFieldSelect('ipn-field',$label,$orderSection);			
			$group->appendChild($label);
		
			
			$container->appendChild($group);
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
		//  IPN Data Output field
		
			$label = Widget::Label(__('The IPN Data field, used to store IPN response'));
			$this->__appendFieldSelect('ipn-data-field',$label,$orderSection);			
			$group->appendChild($label);
			
			$container->appendChild($group);
			$this->Form->appendChild($container);
			
			
					

		// Order Item Field Settings --------------------------------------------------------
			
			$orderItemSection=$this->driver->getConfig('order-items-section');	
			
			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Order Item Field Settings'))
			);


			$p=new XMLElement('p',__('Please select the fields used for each property. Please consult the readme for more information'));
			$p2=new XMLElement('p');
			$p2->appendChild(
				new XMLElement('b',__('You must select the sections AND save first'))
				);
			
			$container->appendChild($p);
			$container->appendChild($p2);
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
		
		//  Quantity Field
		
			$label = Widget::Label(__('The field in your order item section that holds quantity'));
			$this->__appendFieldSelect('quantity-field',$label,$orderItemSection);			
			$group->appendChild($label);						
		
		
		//  Order Field
		
			$label = Widget::Label(__('The field in your order item section that links to the Order Section'));
			$this->__appendFieldSelect('order-link-field',$label,$orderItemSection);			
			$group->appendChild($label);
			
			$container->appendChild($group);
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
		
		
		//  Product Link Field
		
			$label = Widget::Label(__('The field in your order item section that links to the Product Section'));
			$this->__appendFieldSelect('product-link-field',$label,$orderItemSection);			
			$group->appendChild($label);	
		
		
		//  Product Option Field
		
			$label = Widget::Label(__('The field in your order item section that links to the Product Option Section'));
			$this->__appendFieldSelect('product-option-link-field',$label,$orderItemSection);			
			$group->appendChild($label);
			
			$container->appendChild($group);
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$this->Form->appendChild($container);
		
		
		
		//  Calculator XLST and associated DS --------------------------------------------------------
					
			$container = new XMLElement('fieldset');
			$container->setAttribute('class', 'settings');
			$container->appendChild(
				new XMLElement('legend', __('Calculator XLST'))
			);
			$container->appendChild(new XMLElement('p','The Param $ds-order will container the current order id'));
			
						
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('XLST Template'));
			$value= $this->driver->getConfig('calc-function');
			$label->appendChild(Widget::TextArea('calc-function',15,50,$value));
			
			$group->appendChild($label);
			$this->__viewIndexDSNames($group);
						
			$container->appendChild($group);
			$this->Form->appendChild($container);	

		// Datasources --------------------------------------------------------
		//---------------------------------------------------------------------
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			
			$attr = array('accesskey' => 's');
			if (!$bIsWritable) $attr['disabled'] = 'disabled';
			$div->appendChild(Widget::Input('action[save]', __('Save Changes'), 'submit', $attr));

			$this->Form->appendChild($div);
		}
		
	/*-------------------------------------------------------------------------
		Datasources:
	-------------------------------------------------------------------------*/
		
		public function __viewIndexDSNames($context) {
			
			$DSManager = new DatasourceManager(Symphony::Engine());
			$datasources = $DSManager->listAll();
			$options = array();
			
			$selectedDs=explode(',',$this->driver->getConfig('calc-ds'));
			
			foreach ($datasources as $datasource) {
				$selected = in_array($datasource['handle'],$selectedDs);
				
				$options[] = array(
					$datasource['handle'], $selected, $datasource['name']
				);
			}
			
			$section = Widget::Label(__('Datasources Available'));
			$section->appendChild(Widget::Select(
				'calc-ds[]', $options, array(
					'multiple'	=> 'multiple'
				)
			));
			
			$context->appendChild($section);
		}
		
	/*-------------------------------------------------------------------------
		Section List Generator:
	-------------------------------------------------------------------------*/
		public function __appendSectionSelect($option,$context){			
			
			$data = $this->driver->getConfig($option);
			//$checkedData = $data != false ?  $data : array();
			
			$sectionManager = new SectionManager($this);
			$sections = $sectionManager->fetch();
			
			if($sections)
			{
				$options = array(array('','','--Please Select--'));
				foreach($sections as $section)
				{
					$options[] = array($section->get('id'), $section->get('id')== $data, $section->get('name'));
				}
				$context->appendChild(Widget::Select($option.'[]', $options));
			}			
		}
		
	/*-------------------------------------------------------------------------
		Field List Generator:
	-------------------------------------------------------------------------*/
		public function __appendFieldSelect($option,$context,$section=NULL){			
			
			if($section==NULL){
				$select= Widget::Select($option.'[]', array(), array('disabled'=>'disabled'));
			}
			else{
				
				$data = $this->driver->getConfig($option);
				//$checkedData = $data != false ? $data : '-1';
				
				$fieldManager = new FieldManager($this);
				$fields = $fieldManager->fetch(null,$section);
				
				if($fields)
				{
					$options = array(array('','','--Please Select--'));
					foreach($fields as $field)
					{
			
						$options[] = array($field->get('id'), $field->get('id') == $data, $field->get('label'));
					}
					$select= Widget::Select($option.'[]', $options);		
				}
				else{
					$select= Widget::Select($option.'[]', array(), array('disabled'=>'disabled'));	
				}
			}
			$context->appendChild($select);
			
		}

	/*-------------------------------------------------------------------------
		Save Function
	-------------------------------------------------------------------------*/	
		
		public function __actionIndex() {
			
			$settings  = @$_POST;
			
			if (empty($this->driver)) {
				$this->driver = Symphony::ExtensionManager()->create('checkout');
			}
			
			if (@isset($_POST['action']['save'])) {
				
				foreach($settings as $key => $value){
					$this->driver->setConfig($key,$value);
				}
			}
		}
	}
	
?>
