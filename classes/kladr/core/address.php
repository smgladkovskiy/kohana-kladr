<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Address
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
abstract class KLADR_Core_Address {

	public $subject;
	public $district;
	public $city;
	public $locality;
	public $street;
	public $code;
	public $actual = FALSE;

	public function __construct($db = 'default')
	{
		$i = 1;
		foreach(get_class_vars(__CLASS__) as $address_item => $value)
		{
			if($i<=4)
			{
				$this->$address_item = new KLADR_Address_Item($i++, $db);
			}
		    else
		    {
			    break;
		    }
		}
	    $this->street = new KLADR_Street();
	}

	/**
	 * Address code updating. Looking through all KLADR_Address_Items
	 *
	 * @return string
	 */
	public function update_code()
	{
		$this->code = NULL;
		foreach(get_class_vars(__CLASS__) as $address_item_name => $default_value)
		{
			$address_item = $this->$address_item_name;
			if(is_object($address_item) AND $address_item instanceof KLADR_Address_Item)
				$this->code .= sprintf("%-0{$address_item->code_length()}s", $address_item->code);
		}
		return $this->code;
	}

} // End KLADR_Core_Address
