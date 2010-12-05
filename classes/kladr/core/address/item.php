<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Address_Item
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyrignt
 */
abstract class KLADR_Core_Address_Item {

	public $code;
	public $name;

	protected $_config;
	protected $_db;

	public function __construct($db = 'default')
	{
		$this->_config = Kohana::config('kladr');
		if($db != 'default')
		{
			$this->_db = $db;
		}
	}

	public function code(string $code = NULL)
	{
		if($code === NULL)
		{
			return $this->code;
		}

		$this->code = $code;
		return TRUE;
	}

	public function name()
	{
		if($this->code AND ! $this->name)
		{
			$query = DB::select($this->_config['db_tables']['sorcbase'].'SOCRNAME')
				->from($this->_config['db_tables']['sorcbase'])
				->where('KOD_T_ST', '=', $this->code)
				->limit(1)
				->execute($this->_db);

			$this->name = $query->SOCRNAME;
		}

		if($this->name)
		{
			return $this->name;
		}

		return NULL;
	}
} // END KLADR_Core_Address_Item