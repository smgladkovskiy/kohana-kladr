<?php defined('SYSPATH') or die('No direct access allowed.');

/**
 * KLADR_Core_Street
 *
 * @author avis <smgladkovskiy@gmial.com>
 * @copyright (c) 2010 EnerDesign <http://enerdesign.ru>
 */
abstract class KLADR_Core_Street {

	public $code;
	public $type;
	public $type_alias;
	public $index;
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
	public function __construct($db = 'default')
	{
		$this->_config = Kohana::config('kladr');
	    if($db != 'default')
		{
			$this->_db = $db;
		}
	}

	/**
	 * KLADR_Street code getter/setter
	 *
	 * @param null|string $code
	 * @return string|bool
	 */
	public function code($code = NULL)
	{
		if($code === NULL)
		{
			return $this->code;
		}

		$this->code = (string) $code;
		return TRUE;
	}


	/**
	 * KLADR_Street code length
	 *
	 * @return int
	 */
	public function code_length()
	{
		return $this->_length;
	}

	/**
	 * KLADR_Street code getter/setter
	 *
	 * @param null|string $code
	 * @return string|bool
	 */
	public function name()
	{
		if($this->code)
		{
			$query = DB::select(
					$this->_config['db_tables']['street'].'.NAME',
					$this->_config['db_tables']['street'].'.SOCR')
				->from($this->_config['db_tables']['street'])
				->where($this->_config['db_tables']['street'].'.CODE', 'LIKE', $this->code)
				->limit(1)
				->as_object()
				->execute($this->_db);

		    return $query[0]->NAME . ' ' . $query[0]->SOCR;
		}

	   return NULL;
	}

	/**
	 * Gets KLADR_Street type
	 *
	 * @todo rebuild this shit
	 * @return string| NULL
	 */
	public function type()
	{
		if($this->code AND ! $this->type)
		{
			$query = DB::select(
					$this->_config['db_tables']['sorcbase'].'.SCNAME',
					$this->_config['db_tables']['sorcbase'].'.SOCRNAME')
				->from($this->_config['db_tables']['street'])
				->join($this->_config['db_tables']['sorcbase'], 'LEFT')
					->on($this->_config['db_tables']['street'].'.SOCR', '=', $this->_config['db_tables']['sorcbase'].'.SCNAME')
				->where($this->_config['db_tables']['street'].'.CODE', 'LIKE', $this->code)
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

	public function get_by_name($city_code, $name)
	{
		$query = DB::select(
			$this->_config['db_tables']['street'].'.CODE',
			$this->_config['db_tables']['street'].'.NAME',
			$this->_config['db_tables']['street'].'.SOCR')
		->from($this->_config['db_tables']['street'])
		->where($this->_config['db_tables']['street'].'.NAME', 'LIKE', '%' . $name . '%')
			->where($this->_config['db_tables']['street'].'.CODE', 'LIKE', substr($city_code, 0, 11).'%%%%00')
		->limit(1)
		->as_object()
		->execute($this->_db);

		if(count($query))
		{
			$this->name = $query[0]->NAME . ' ' . $query[0]->SOCR;
			$this->code = $query[0]->CODE;
		}
		else
		{
			return NULL;
		}

		return $this->code;
	}
} // End KLADR_Core_Street
