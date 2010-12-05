<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyrignt
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
	public function instance($db = 'default')
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

		$this->_address = new KLADR_Address();
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
		$address_item = '_' . $item_name;
		if(property_exists(KLADR_Address, $address_item))
		{
			$this->_address->$address_item = new KLADR_Address_Item();
			$this->_address->$address_item->code($code);
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

		$subject = $_address->_subject->code();
		$district = $_address->_district->code();
		$city = $_address->_city->code();
		$locality = $_address->_locality->code();

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
			->join(array(array($this->_config['db_tables']['kladr'], 'c')), 'LEFT')
				->on('c.CODE', '=',  sprintf('%-013d', $subject.$district.$city))
			->join(array(array($this->_config['db_tables']['kladr'], 'd')), 'LEFT')
				->on('d.CODE', '=', sprintf('%-013d', $subject.$district))
			->join(array(array($this->_config['db_tables']['kladr'], 's')), 'LEFT')
				->on('s.CODE', '=', sprintf('%-013d', $subject))
			->where('l.CODE', '=', sprintf('%-013d', $subject.$district.$city.$locality))
			->execute($this->_db);
		$address = $query->subject_name . ' ' . $query->subject_type . ', '
		         . $query->district_name . ' ' . $query->district_type . ', '
		         . $query->city_type . ' ' . $query->city_name . ', '
		         . $query->locality_type . ' ' . $query->locality_name;

		return $address;
	}
} // End Cladr