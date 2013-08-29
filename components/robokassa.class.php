<?php
/*
	**************************************************
	antongorodezkiy@gmail.com © 2011
	Version 1.0
	**************************************************
*/


class Robokassa {

	var $Username = '';
	var $Signature1 = '';
	var $Signature2 = '';
	var $Sandbox = '';
	var $encoding = '';
	
	function __construct($config) {
  
		if(isset($config['Sandbox']))
			$this->Sandbox = $config['Sandbox'];
		else
			$this->Sandbox = true;
			
		$this->Username = isset($config['Username']) && $config['Username'] != '' ? $config['Username'] : '';
		$this->Signature1 = isset($config['Signature1']) && $config['Signature1'] != ''  ? $config['Signature1'] : '';
		$this->Signature2 = isset($config['Signature2']) && $config['Signature2'] != ''  ? $config['Signature2'] : '';
		$this->encoding = isset($config['encoding']) && $config['encoding'] != ''  ? $config['encoding'] : '';
			
		if($this->Sandbox) {
			#Sandbox
			$this -> EndPointURL = 'http://test.robokassa.ru/Index.aspx';	
		}
		else {
			$this -> EndPointURL = 'https://merchant.roboxchange.com/Index.aspx';
		}
	
	}  // End function __construct()
	
	
	function doPay($order) {
  
		// email
			$email = $order->primary_email;
	
		// Order Number
			$inv_id = $order->order_id;
		
		// Description of the order
			$inv_desc = "Product Purchase";
		
		// Ammount of order
			$out_summ = $order->order_total;
		
		// Type of Product
			$shp_item = implode("," array_keys($order->products));
		
		// Proposed payment currency
			$in_curr = "WMZM";
		
		// Language
			$culture = "ru";
		
		// encoding
			$encoding = $this->encoding;
		
		// Signature generation, the order is important
    $crc_fields['login'] = $this->Username;
    $crc_fields['amount'] = $out_summ;
    $crc_fields['order_num'] = $inv_id;
    $crc_fields['payment_pass'] = $this -> Signature1;
    $crc_fields['additional'] = "Shp_item=".$shp_item;
    $crc  = md5( implode(':',$crc_fields));
		
		$params = array();
		$params['Culture'] = $culture;
		$params['Desc'] = $inv_desc;
		$params['EMail'] = $email;
		$params['Encoding'] = $encoding;
		$params['IncCurrLabel'] = $in_curr;
		$params['InvId'] = $inv_id;
		$params['MrchLogin'] = $this->Username;
		$params['OutSum'] = $out_summ;
		$params['Shp_item'] = $shp_item;
		$params['SignatureValue'] = $crc;
		$params['in'] = $out_summ;
		
		$params_str = array();
    
		foreach($params as $name => $value) {
			$params_str[] = $name.'='.$value;
		}
		
		$redirectUrl = $this->EndPointURL.'?'.implode('&',$params_str);
		
		return array('REDIRECTURL' => $redirectUrl);
	}
	
	
	function checkPayment($request) {
		if (!isset($request['OutSum']) or !isset($request['InvId']) or !isset($request['SignatureValue']) or !isset($request['Shp_item']))
			return false;
		
		$out_summ = (string)$request['OutSum'];
		$inv_id = (int)$request['InvId'];
		$SignatureValue = $request['SignatureValue'];
		$shp_item = $request['Shp_item'];
		
		// Signature generation, the order is important
    $crc_fields['order_amount'] = $out_summ;
    $crc_fields['indiv_order_id'] = $inv_id;
    $crc_fields['pass_two'] = $this -> Signature2;
    $crc_fields['params'] = "Shp_item=".$shp_item;
    $crc  = strtoupper(md5( implode(':',$crc_fields) ));
		
		// All the way through , we continue to
		if ($SignatureValue == $crc)
			return true;
		else
			return false;
	}
	
	
}