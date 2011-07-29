<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/class.datasourcemanager.php');	
	//require_once(EXTENSIONS . '/symql/lib/class.symql.php');
	
	Class extension_Checkout extends Extension{
		
		public function about(){
			return array('name' => 'Symphony ECommerce Checkout',
						 'version' => '0.1',
						 'release-date' => '2011-26-06',
						 'author' => array('name' => 'David Anderson',
										   'website' => 'http://veodesign.co.uk',
										   'email' => 'dave@veodesign.co.uk'
						),
				 		'description'	=> 'Basket and Checkout Extension for Symphony'
						);
		}
		
		public function uninstall() {
			Symphony::Engine()->Configuration->remove('checkout');
			Symphony::Engine()->saveConfig();
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'preferences'
				)
			);
		}
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('System'),
					'name'		=> __('Checkout Options'),
					'link'		=> '/preferences/',
					'limit'		=> 'developer'
				)
			);
		}
		
		//  Get Config Functions ------------------------------------------------------------- 
		
		public function getAllowedOptions(){
			return array( 	'orders-section','shipping-field','gateway-field','status-field','ipn-field','ipn-data-field',
							'order-items-section','products-section','product-options-section',
							'quantity-field','order-link-field','product-link-field','product-option-link-field','calc-function','calc-ds');	
		}
		

		
		public function getConfig($name){
			if(in_array($name,$this->getAllowedOptions())){
				return Symphony::Configuration()->get($name, 'checkout');	
			}
		}
		
		//  Set Config Functions ------------------------------------------------------------- 
		
		public function setConfig($name,$values){
			
			if(in_array($name,$this->getAllowedOptions())){
				if (is_array($values)) {
					$values = implode(',', $values);
					Symphony::Configuration()->set($name, $values, 'checkout');
				}			
				else {
					Symphony::Configuration()->set($name,$values, 'checkout');
				}
				
				Symphony::Engine()->saveConfig();
			}
		}
		
	}
		
