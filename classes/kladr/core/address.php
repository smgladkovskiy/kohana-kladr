<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Address
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
abstract class KLADR_Core_Address {

	public $_code;
	public $_subject;
	public $_district;
	public $_city;
	public $_locality;
	public $_street;

	public function __construct($db = 'default')
	{
		$i = 1;
		foreach(get_class_methods(__CLASS__) as $address_item)
		{
			$this->$address_item = new KLADR_Address_Item($i++, $db);
		}
	}

} // End KLADR_Core_Address
