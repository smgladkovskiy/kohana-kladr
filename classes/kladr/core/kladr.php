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

	protected $_address;
	protected $_config;
	protected $_db;

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
			if($code == '7700000000000')
			{
				$this->_address->$address_item->code_length(count($code));
				$this->_address->$address_item->code_begin(0);
			}
			$this->_address->$address_item->code($code);
			$this->_address->update_code();
			return TRUE;
		}

		return FALSE;
	}

	public function actual($status = FALSE)
	{
		$this->_address->_actual = (bool) $status;
	    return $this;
	}


	public function address()
	{
	    return $this->_address;
	}

	/**
	 * Subject getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function subject()
	{
		return $this->_address->subject;
	}

	/**
	 * District getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function district()
	{
		return $this->_address->district;
	}

	/**
	 * City getter
	 *
	 * @return KLADR_Address_Item
	 */
	public function city()
	{
		return $this->_address->city;
	}

	/**
	 * Locality getter
	 * @return KLADR_Address_Item
	 */
	public function locality()
	{
		return $this->_address->locality;
	}

	/**
	 * Street getter
	 * @return KLADR_Street
	 */
	public function street($name = NULL)
	{
		if($name === NULL)
		{
			return $this->_address->street;
		}

		$this->_address->street->get_by_name($this->_address->city->code, $name);
	}

	/**
	 * Gets subjects collection
	 *
	 * @return array
	 */
	public function subjects()
	{
		return $this->_address->subject->collections(NULL, TRUE);
	}

	/**
	 * Gets districts collection of the subject
	 *
	 * @return array
	 */
	public function districts()
	{
		return $this->_address->district->collections($this->_address->subject->code);
	}

	/**
	 * Gets cities collection of the districts
	 *
	 * @return array
	 */
	public function cities()
	{
		return $this->_address->city->collections($this->_address->subject->code.$this->_address->district->code);
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
	public function set_address_by_code($code)
	{
		$this->_set_address_by_code($code);
	}

	/**
	 * Street setting
	 *
	 * @param string $code
	 * @return void
	 */
	public function set_street_by_code($code)
	{
		$this->_set_street_by_code($code);
	}

	/**
	 * Address setting
	 *
	 * @param string $code
	 * @return void
	 */
	public function subject_name($name)
	{
		$name = explode(' ', $name);
		array_pop($name);
		$name = implode(' ', $name);
		$query = DB::select(
			$this->_config['db_tables']['kladr'].'.CODE')
		->from($this->_config['db_tables']['kladr'])
		->where('NAME', '=', $name)
		->where('CODE', 'LIKE', '%%00000000000')
		->limit(1)
		->as_object()
		->execute();

		$result = $query[0];

		$this->_address->subject->code($result->CODE);
	}

	/**
	 * @todo придумать, зачем это нужно было мне в 3 часа ночи...
	 * @throws Kohana_Exception
	 * @param  $address_item
	 * @return
	 */
	public function get_type_collections($address_item)
	{
		if(property_exists($this->_address, $address_item))
		{
			return $this->_address->$address_item->collection($this->_address->code);
		}

		throw new Kohana_Exception('there is no such Address property: '. $address_item);
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
	protected function _set_address_by_code($code)
	{
		if($code == '7700000000000')
		{
			$this->set_address_data('subject', (substr($code, 0, 2)));
			$this->set_address_data('city', $code);
		}
		else
		{
			$this->set_address_data('subject', (substr($code, 0, 2)));
			$this->set_address_data('district', (substr($code, 2, 3)));
			$this->set_address_data('city', (substr($code, 5, 3)));
			$this->set_address_data('locality', (substr($code, 8, 3)));
		}
	}

	/**
	 * Address object filling
	 *
	 * @param string|int $code
	 * @return void
	 */
	protected function _set_street_by_code($code)
	{
		$this->_address->street->code($code);
	}

	/**
	 * Gets address string from DB
	 *
	 * @todo implement streets
	 * @param KLADR_Address $_address
	 * @return string
	 */
	protected function _get_address(KLADR_Address $_address)
	{

		$code = str_repeat('0', 13);
		if($this->_address->actual === FALSE)
			$code = substr_replace($code, '%%', 11, 2);

		$subject = substr_replace(
			$code,
			$_address->subject->code(),
			$_address->subject->code_begin(),
			$_address->subject->code_length()
		);

		$district = substr_replace(
			$subject,
			$_address->district->code(),
			$_address->district->code_begin(),
			$_address->district->code_length()
		);

		$city = substr_replace(
			$district,
			$_address->city->code(),
			$_address->city->code_begin(),
			$_address->city->code_length()
		);

		$locality = substr_replace(
			$city,
			$_address->locality->code(),
			$_address->locality->code_begin(),
			$_address->locality->code_length()
		);

//		$street = $_address->street->code();

		$query = DB::select(
				array('s.NAME', 'subject_name'),
				array('s.SOCR', 'subject_type'),
				array('d.NAME', 'district_name'),
				array('d.SOCR', 'district_type'),
				array('c.NAME', 'city_name'),
				array('c.SOCR', 'city_type'),
				array('l.NAME', 'locality_name'),
				array('l.SOCR', 'locality_type'))
			->from(
				array($this->_config['db_tables']['kladr'], 's'),
				array($this->_config['db_tables']['kladr'], 'd'),
				array($this->_config['db_tables']['kladr'], 'c'),
				array($this->_config['db_tables']['kladr'], 'l')
			)
			->where('s.CODE', 'LIKE', $subject)
			->where('d.CODE', 'LIKE', $district)
			->where('c.CODE', 'LIKE',  $city)
			->where('l.CODE', 'LIKE', $locality)
			->limit(1)
			->as_object()
			->execute();
		$result = $query[0];

		$address = $result->subject_name  . ' '  . $result->subject_type  . ', '
		         . $result->district_name . ' '  . $result->district_type . ', '
		         . $result->city_type     . '. ' . $result->city_name;

		if($result->locality_name != $result->city_name)
			$address .= ', ' . $result->locality_type . '. ' . $result->locality_name;

		return $address;
	}
} // End KLADR_Core_Kladr