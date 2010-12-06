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
	public $type;
	public $type_alias;
	public $name;

	protected $_begin;
	protected $_length;

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
		$this->_length = 3;

		switch($level)
		{
			case 1:
				$this->_begin = 0;
				$this->_length = 2;
				break;
			case 2:
				$this->_begin = 2;
				break;
			case 3:
				$this->_begin = 5;
				break;
			case 4:
				$this->_begin = 8;
				break;

		}
	}

	/**
	 * KLADR_Address_Item code getter/setter
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
	 * Gets KLADR_Address_Item type
	 *
	 * @return string|
	 * NULL
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
			->from($this->_config['db_tables']['sorcbase'])
			->where('LEVEL', '=', $this->level)
			->execute();
		return $query->as_array('KOD_T_ST', 'SOCRNAME');
	}

	public function names_collection($parent_code = NULL)
	{
		$code = str_repeat('0', 13);
		if($parent_code === NULL)
		{
			$code = sprintf("%-013s", str_repeat('%', $this->_begin + 1 + $this->_length));
		}
		else
		{
			$chars_in_parent_code = strlen($parent_code);

			// parent code part set in address CODE
			$code = substr_replace($code, $parent_code, 0, $chars_in_parent_code);

			// current CODE mask set
			$code = substr_replace($code, str_repeat('%', $this->_length), $this->_begin, $this->_length);
		}

		// status mask set
		$code = substr_replace($code, '%%', 11, 2);

		$query = DB::select(DB::expr('CONCAT(`NAME`, " ", `SOCR`) AS NAME'), 'CODE')
			->from($this->_config['db_tables']['kladr'])
			->where('CODE', 'LIKE', $code)
			->and_where_open()
				->where(DB::expr('right(CODE,2)'), '=', '51')
				->or_where(DB::expr('right(CODE,2)'), '=', '00')
			->and_where_close()
			->order_by('NAME', 'ASC')
			->execute();

		return $query->as_array('CODE', 'NAME');
	}

	public function code_length()
	{
		return $this->_length;
	}
} // End KLADR_Core_Address_Item