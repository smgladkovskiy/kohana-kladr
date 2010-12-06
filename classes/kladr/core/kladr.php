<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Kladr
 *
 * @example:
 * 	$kladr = kladr::instance();
 * 	$kladr->address_item_type('subject', 'обл');
 * 	$kladr->get_collections('subjects');
 * 	$kladr->address_item_name('subjects', 'Башкортостан');
 * 	$kladr->get_collections('district');
 * 	$kladr->address_item_name('district', 'Колумбия');
 * 	$kladr->get_collections('city');
 * 	$kladr->address_item_name('city', 'Вашингтон');
 * 	$kladr->get_collections('locality');
 * 	$kladr->address_item_name('locality', 'Васюки');
 * 	$kladr->get_collections('street');
 * 	$kladr->address_item_name('street', 'Поляны');
 * 	$kladr->get_collections('houses');
 * 	$kladr->get_address();
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
class KLADR_Core_Kladr {

	public static $instance;

	private $_address;
	private $_config;
	private $_db;

	/**
	 * KLADR class instance
	 *
	 * @param string $db
	 * @return KLADR
	 */
	public static function instance($db = 'default')
	{
		if( ! is_object(self::$instance))
		{
			self::$instance = new KLADR($db);
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 *
	 * @param string $db
	 */
	public function __construct($db)
	{
		$this->_config = Kohana::config('kladr');
		if($db != 'default')
		{
			$this->_db = $db;
		}

		$this->_address = new KLADR_Address($db);
	}

	/**
	 * KLADR address items factory
	 *
	 * @param string $item_name
	 * @param string|integer $code
	 * @return bool
	 */
	public function set_address_data($item_name, $code)
	{
		$address_item = $item_name;
		if(property_exists($this->_address, $address_item))
		{
			$this->_address->$address_item->code($code);
			$this->_address->update_code();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Subject getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function subject()
	{
		return $this->_address->_subject;
	}

	/**
	 * District getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function district()
	{
		return $this->_address->_district;
	}

	/**
	 * City getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function city()
	{
		return $this->_address->_city;
	}

	/**
	 * Locality getter
	 * @return KLADR_Address_Item
	 */
	public function locality()
	{
		return $this->_address->_locality;
	}

	/**
	 * Address getter/setter
	 *
	 * @param int|null $code
	 * @return KLADR_Address
	 */
	public function get_address(int $code = NULL)
	{
		if($code !== NULL)
		{
			$this->_set_address_by_code($code);
		}

		$address = $this->_get_address($this->_address);

		return $address;
	}

	/**
	 * Address setting
	 *
	 * @param string $code
	 * @return void
	 */
	public function set_address(string $code)
	{
		$this->_set_address_by_code($code);
	}

	public function get_type_collections($address_item)
	{
		if(property_exists($this->_address, $address_item))
		{
			return $this->_address->$address_item->collection($this->_address->code);
		}

		throw new Kohana_Exception('there is no such Address property: '. $address_item);
	}

//	public function get_name_collections($address_item)
//	{
//		if(property_exists($this->_address, $address_item))
//		{
//			return $this->_address->$address_item->names_collection($this);
//		}
//
//		throw new Kohana_Exception('there is no such Address property: '. $address_item);
//	}

	public function subjects()
	{
		return $this->_address->subject->names_collection();
	}
	public function districts()
	{
		return $this->_address->district->names_collection($this->_address->subject->code);
	}
	public function cities()
	{
		return $this->_address->city->names_collection($this->_address->subject->code.$this->_address->district->code);
	}

	/**
	 * Address object filling
	 *
	 * Код имеет вид:
	 * 01.234.567.890.12
	 *  |  |   |   |  |
	 *  |  |   |   |  |
	 *  |  |   |   |  признак актуальности
	 *  |  |   |   код населенного пункта
	 *  |  |   код города
	 *  |  код района
	 *  код субъекта
	 *
	 * @param string|int $code
	 * @return void
	 */
	private function _set_address_by_code($code)
	{
		$this->set_address_data('subject', (substr($code, 0, 2)));
		$this->set_address_data('district', (substr($code, 2, 3)));
		$this->set_address_data('city', (substr($code, 5, 3)));
		$this->set_address_data('locality', (substr($code, 8, 3)));
	}

	/**
	 * Gets address string from DB
	 *
	 * @param KLADR_Address $_address
	 * @return KLADR_Address|string
	 */
	private function _get_address(KLADR_Address $_address)
	{

		$subject = $_address->subject->code();
		$district = $_address->district->code();
		$city = $_address->city->code();
		$locality = $_address->locality->code();

		$street = $_address->street->code();

		$query = DB::select(
				's.NAME AS subject_name',
				's.SOCR AS subject_type',
				'd.NAME AS district_name',
				'd.SOCR AS district_type',
				'c.NAME AS city_name',
				'c.SOCR AS city_type',
				'l.NAME AS locality_name',
				'l.SOCR AS locality_type')
			->from(array($this->_config['db_tables']['kladr'], 'l'))
			->join(array($this->_config['db_tables']['kladr'], 'c'), 'LEFT')
				->on('c.CODE', '=',  sprintf('%-013d', $subject.$district.$city))
			->join(array($this->_config['db_tables']['kladr'], 'd'), 'LEFT')
				->on('d.CODE', '=', sprintf('%-013d', $subject.$district))
			->join(array($this->_config['db_tables']['kladr'], 's'), 'LEFT')
				->on('s.CODE', '=', sprintf('%-013d', $subject))
			->where('l.CODE', '=', sprintf('%-013d', $subject.$district.$city.$locality))
			->execute();
		$address = $query->subject_name . ' ' . $query->subject_type . ', '
		         . $query->district_name . ' ' . $query->district_type . ', '
		         . $query->city_type . ' ' . $query->city_name . ', '
		         . $query->locality_type . ' ' . $query->locality_name;

		return $address;
	}
} // End KLADR_Core_Kladr