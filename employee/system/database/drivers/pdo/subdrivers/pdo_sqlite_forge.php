<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_DB_pdo_sqlite_forge extends CI_DB_pdo_forge {

		protected $_create_table_if	= 'CREATE TABLE IF NOT EXISTS';

		protected $_drop_table_if	= 'DROP TABLE IF EXISTS';

		protected $_unsigned		= FALSE;

		protected $_null		= 'NULL';

	
		public function __construct(&$db)
	{
		parent::__construct($db);

		if (version_compare($this->db->version(), '3.3', '<'))
		{
			$this->_create_table_if = FALSE;
			$this->_drop_table_if   = FALSE;
		}
	}

	
		public function create_database($db_name)
	{
						return TRUE;
	}

	
		public function drop_database($db_name)
	{
				if (file_exists($this->db->database))
		{
						$this->db->close();
			if ( ! @unlink($this->db->database))
			{
				return $this->db->db_debug ? $this->db->display_error('db_unable_to_drop') : FALSE;
			}
			elseif ( ! empty($this->db->data_cache['db_names']))
			{
				$key = array_search(strtolower($this->db->database), array_map('strtolower', $this->db->data_cache['db_names']), TRUE);
				if ($key !== FALSE)
				{
					unset($this->db->data_cache['db_names'][$key]);
				}
			}

			return TRUE;
		}

		return $this->db->db_debug ? $this->db->display_error('db_unable_to_drop') : FALSE;
	}

	
		protected function _alter_table($alter_type, $table, $field)
	{
		if ($alter_type === 'DROP' OR $alter_type === 'CHANGE')
		{
																											
			return FALSE;
		}

		return parent::_alter_table($alter_type, $table, $field);
	}

	
		protected function _process_column($field)
	{
		return $this->db->escape_identifiers($field['name'])
			.' '.$field['type']
			.$field['auto_increment']
			.$field['null']
			.$field['unique']
			.$field['default'];
	}

	
		protected function _attr_type(&$attributes)
	{
		switch (strtoupper($attributes['TYPE']))
		{
			case 'ENUM':
			case 'SET':
				$attributes['TYPE'] = 'TEXT';
				return;
			default: return;
		}
	}

	
		protected function _attr_auto_increment(&$attributes, &$field)
	{
		if ( ! empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === TRUE && stripos($field['type'], 'int') !== FALSE)
		{
			$field['type'] = 'INTEGER PRIMARY KEY';
			$field['default'] = '';
			$field['null'] = '';
			$field['unique'] = '';
			$field['auto_increment'] = ' AUTOINCREMENT';

			$this->primary_keys = array();
		}
	}

}
