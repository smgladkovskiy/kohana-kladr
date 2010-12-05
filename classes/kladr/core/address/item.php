<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Address_Item
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
abstract class KLADR_Core_Address_Item {

	public $code;
	public $name;

	protected $_config;
	protected $_db;

	/**
	 * KLADR_Address_Item contsructor
	 *
	 * @param string $db
	 * @return void
	 */
	public function __construct($db = 'default')
	{
		$this->_config = Kohana::config('kladr');
		if($db != 'default')
		{
			$this->_db = $db;
		}
	}

	/**
	 * KLADR_Address_Item name getter/setter
	 *
	 * @param null|string $code
	 * @return string|bool
	 */
	public function code(string $code = NULL)
	{
		if($code === NULL)
		{
			return $this->code;
		}

		$this->code = $code;
		return TRUE;
	}

	/**
	 * Gets KLADR_Address_Item name
	 *
	 * @return string|NULL
	 */
	public function name()
	{
		if($this->code AND ! $this->name)
		{
			$query = DB::select($this->_config['db_tables']['sorcbase'].'SOCRNAME')
				->from($this->_config['db_tables']['sorcbase'])
				->where('KOD_T_ST', '=', $this->code)
				->limit(1)
				->as_object()
				->execute($this->_db);

			$this->name = $query->SOCRNAME;
		}

		if($this->name)
		{
			return $this->name;
		}

		return NULL;
	}
} // End KLADR_Core_Address_Item