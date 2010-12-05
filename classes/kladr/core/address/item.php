<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Address_Item
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
abstract class KLADR_Core_Address_Item {

	public $level;
	public $code;
	public $code_socr;
	public $type;
	public $type_alias;
	public $name;

	protected $_config;
	protected $_db;

	/**
	 * KLADR_Address_Item contsructor
	 *
	 * @param int $level
	 * @param string $db
	 * @return void
	 */
	public function __construct($level = 1, $db = 'default')
	{
		$this->level = $level;
		$this->_config = Kohana::config('kladr');
		if($db != 'default')
		{
			$this->_db = $db;
		}
	}

	/**
	 * KLADR_Address_Item code_socr getter/setter
	 *
	 * @param null|string $code
	 * @return string|bool
	 */
	public function code_socr(string $code = NULL)
	{
		if($code === NULL)
		{
			return $this->code_socr;
		}

		$this->code_socr = $code;
		return TRUE;
	}

	/**
	 * Gets KLADR_Address_Item type
	 *
	 * @return string|NULL
	 */
	public function type()
	{
		if($this->code AND ! $this->type)
		{
			$query = DB::select(
					$this->_config['db_tables']['sorcbase'].'SCNAME',
					$this->_config['db_tables']['sorcbase'].'SOCRNAME')
				->from($this->_config['db_tables']['sorcbase'])
				->where('KOD_T_ST', '=', $this->code)
				->limit(1)
				->as_object()
				->execute($this->_db);

			$this->type = $query->SOCRNAME;
			$this->type_alias = $query->SCNAME;
		}

		if($this->type)
		{
			return $this->type;
		}

		return NULL;
	}

	/**
	 * Gets collection of items from next address_item level
	 *
	 * @todo level searching (?)
	 * @param string $name
	 * @return array
	 */
	public function collection()
	{
		$query = DB::select('*')
			->from($this->_config['db_tables']['kladr'])
			->where('CODE', 'LIKE', $this->code)
			->execute();
		return $query->as_array('CODE', 'NAME');
	}
} // End KLADR_Core_Address_Item