<?php

/**
 * @package		Kar2K CMS
 * @author		Karen Kalashyan <karenishe@gmail.com>
 * @copyright	Copyright (c) 2005-2013, Karen Kalashyan
 * @license		MIT License, see /license.txt
 */

class DataBase{

	/**
	 * Default server information
	 */
	private $dbHost					= 'localhost';
	private $dbUser					= 'root';
	private $dbPassword				= 'password';
	private $dbName					= 'mysql';
	private $dbConnectionCollation	= 'utf8';

	/**
	 * Utility variables
	 */
	private $dbRoot;
	private $dbTable;
	private $dbTables;
	private $dbTableCount;
	
	/**
	 * If $pubTF is true then no "DELETE FROM ..."
	 * only "UPDATE ... SET published = 0"
	 * and every "SELECT FROM ... WHERE ... " will be supplemented with "published = 1"
	 */
	private $pubTF = true;
	
	/**
	 * Default sorting order
	 */
	private $ascDescDefault = 'DESC';

	/**
	 * Construct function
	 *
	 * @param	null|string		$dbConnectionCollation		null		- using default connection collation (class.DB.inc:19)
	 * 														string		- sets connection collation
	 * @param	null|string		$dbName						null		- using default DB name (class.DB.inc:18)
	 * 														string		- sets DB name
	 * @param	boolean			$pubTF						true		- use "UPDATE ... SET published = 0"
	 * 																	  and supplement "SELECT FROM ... WHERE ... " with "published = 1"
	 * 														false		- use "DELETE FROM ..."
	 * @param	null|string		$dbHost						null		- using default DB host (class.DB.inc:15)
	 * 														string		- sets DB host
	 * @param	null|string		$dbUser						null		- using default DB user (class.DB.inc:16)
	 * 														string		- sets DB user
	 * @param	null|string		$dbPassword					null		- using default DB password (class.DB.inc:17)
	 * 														string		- sets DB password
	 * 
	 * @return	null
	 */
	public function __construct($dbConnectionCollation = null, $dbName = null, $pubTF = true, $dbHost = null, $dbUser = null, $dbPassword = null){

		/**
		 * Reassign default values of DB host, user, password, name, connection collation and $pubTF (class.DB.inc:29)
		 */
		if($dbHost)
			$this->dbHost = $dbHost;
		if($dbUser)
			$this->dbUser = $dbUser;
		if($dbPassword)
			$this->dbPassword = $dbPassword;
		if($dbName)
			$this->dbName = $dbName;
		if(is_bool($pubTF))
			$this->pubTF = $pubTF;
		if($dbConnectionCollation)
			$this->dbConnectionCollation = $dbConnectionCollation;

		/**
		 * Connect to DB or call to error function
		 */
		$this->dbRoot = @mysql_connect($this->dbHost, $this->dbUser, $this->dbPassword, true) or $this->error(2, 'DataBase constructor', mysql_error());
		
		/**
		 * Change DB connection collation or call to error function
		 */
		@mysql_query('SET NAMES '.$this->dbConnectionCollation, $this->dbRoot) or $this->error(3, 'DataBase constructor', mysql_error());
		
		/**
		 * Change DB or call to error function
		 */
		@mysql_query('USE `'.$this->dbName.'`;', $this->dbRoot) or $this->error(4, 'DataBase constructor', mysql_error());
	}
	
	/**
	 * Destruct function
	 * Close connection
	 *
	 * @return	null
	 */
	public function __destruct(){
		$this->closeConnection();
	}
	
	/**
	 * Close connection
	 */
	private function closeConnection(){
		if(is_resource($this->dbRoot))
			@mysql_close($this->dbRoot) or $this->error(7, 'closeConnection', mysql_error());
	}
	


/**
 * ========================================================================================
 * ========================================================================================
 * ========================================================================================
 */



	/**
	 * Functions for work with table
	 */
	
		/**
		 * Check table existence
		 *
		 * @param	string	$table		- name of table which existence you want to check
		 *
		 * @return	boolean
		 *
		 */
		public function checkTable($table){
			/*
			 * get DB tables list and check $table name existence
			 */
			$query = @mysql_query('SHOW TABLES;', $this->dbRoot) or $this->error(5, 'setTable', mysql_error());
			if($query)
				while($table_name = mysql_fetch_row($query))
					if($table == $table_name[0])
						return true;
			return false;
		}

		/**
		 * Create table
		 */
		public function createTable($tableName, $tableComments = '', $columns = array()){
			return $this->createTableBase($tableName, $tableComments, $columns, false);
		}
		public function createTable_($tableName, $tableComments = '', $columns = array()){
			return $this->createTableBase($tableName, $tableComments, $columns, true);
		}
		private function createTableBase($tableName, $tableComments = '', $columns, $show = false){
			if(empty($tableName)){
				$this->error(8, 'createTable');
				return false;
			}

			if($this->checkTable($tableName)){
				$this->error(9, 'createTable');
				return false;
			}

			$this->clearForQuery($tableComments);

			$sql = "
				CREATE TABLE `".$tableName."` (
					`id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,".
					($this->pubTF ? "
					`published` INT(1) NOT NULL DEFAULT '1',
					" : "").
					"`seq` INT(11) NOT NULL DEFAULT '0'
				)
				ENGINE = MYISAM CHARACTER SET ".$this->dbConnectionCollation." COLLATE ".$this->dbConnectionCollation."_general_ci
				COMMENT =  '".$tableComments."'
				;
			";

			if($show)
				p($sql, 'mysql');
			$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'createTables', mysql_error());

			if($query)
				$query &= $this->addTableColumnsBase($tableName, $columns, $show);

			return $query;
		}

		/**
		 * Delete table
		 */
		public function deleteTable($table = array()){
			return $this->deleteTableBase($table, false);
		}
		public function deleteTable_($table = array()){
			return $this->deleteTableBase($table, true);
		}
		private function deleteTableBase($table = array(), $show){
			if(!empty($table)){
				if(is_array($table))
					foreach($table as $tab)
						$this->deleteTableBase($tab, $show);
				else if($this->checkTable($table)){
					$sql = "DROP TABLE `".$table."`";
	
					if($show)
						p($sql, 'mysql');
	
					$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'deleteTable', mysql_error());
				
					return $query;
				}else
					return false;
			}else
				return false;
		}
	
		/**
		 * Get tables list
		 */
		public function getTables(){
			$sql = "SHOW TABLES;";
			$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getTables', mysql_error());
			$ret = array();
			if($query)
				while($table_name = mysql_fetch_row($query))
					array_push($ret, $table_name[0]);
			return $ret;
		}


		/**
		 * Get table comment
		 */
		public function getTableComments($tableName){
			return $this->getTableCommentsBase($tableName, false);
		}
		public function getTableComments_($tableName){
			return $this->getTableCommentsBase($tableName, true);
		}
		private function getTableCommentsBase($tableName, $show){
			if(!$this->checkTable($tableName)){
				$this->error(6, 'getTableComments');
				return false;
			}

			$sql = "SHOW TABLE STATUS LIKE '".$tableName."';";

			if($show)
				p($sql, 'mysql');

			$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getTableComments', mysql_error());

			if($query){
				$ex = array();
				while($row = mysql_fetch_row($query))
					array_push($ex, $row[17]);
				if(sizeof($ex) == 1)
					$ex = array_pop($ex);
				return $ex;
			}else
				return false;
		}

		/**
		 * Change table comment
		 */
		public function changeTableComments($table = '', $comments = ''){
			return $this->changeTableCommentsBase($table, $comments, false);
		}
		public function changeTableComments_($table = '', $comments = ''){
			return $this->changeTableCommentsBase($table, $comments, true);
		}
		private function changeTableCommentsBase($table = '', $comments = '', $show){
			if($this->checkTable($table)){
				$sql = "ALTER TABLE `".$table."` COMMENT = '".$comments."';";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'changeTableComments', mysql_error());
			
				return $query;
			}else
				return false;
		}


/**
 * ========================================================================================
 * ========================================================================================
 * ========================================================================================
 */



		/**
		 * Functions for work with table columns
		 */

			/**
			 * Get table columns
			 */
			public function getTableColumns($table = ''){
				return $this->getTableColumnsBase($table, false);
			}
			public function getTableColumns_($table = ''){
				return $this->getTableColumnsBase($table, true);
			}
			private function getTableColumnsBase($table = '', $show = false){
				if(!$this->checkTable($table)){
					$this->error(6, 'getTableColumns');
					return false;
				}

				$sql = "SHOW FULL COLUMNS FROM `".$table."`;";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getTableColumns', mysql_error());

				if($query){
					$ex = array();
					while($row = mysql_fetch_row($query))
						array_push($ex, $row);
					return $ex;
				}else
					return false;
			}

			/**
			 * Get table columns names
			 */
			public function getTableColumnsNames($table = ''){
				return $this->getTableColumnsNamesBase($table, false);
			}
			public function getTableColumnsNames_($table = ''){
				return $this->getTableColumnsNamesBase($table, true);
			}
			private function getTableColumnsNamesBase($table = '', $show = false){
				if(!$this->checkTable($table)){
					$this->error(6, 'getTableColumnsNames');
					return false;
				}

				$sql = "DESCRIBE `".$table."`;";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getTableColumnsNames', mysql_error());

				if($query){
					$ex = array();
					while($row = mysql_fetch_row($query))
						array_push($ex, $row[0]);
					return $ex;
				}else
					return false;
			}

			/**
			 * Delete column from table
			 */
			public function deleteTableColumns($table = '', $columns = array()){
				return $this->deleteTableColumnsBase($table, $columns, false);
			}
			public function deleteTableColumns_($table = '', $columns = array()){
				return $this->deleteTableColumnsBase($table, $columns, true);
			}
			private function deleteTableColumnsBase($table = '', $columns = array(), $show){
				if(!empty($columns)){
					if(is_array($columns))
						foreach($columns as $column)
							$this->deleteTableColumnsBase($table, $column, $show);
					else if($this->checkTable($table)){
						$sql = "ALTER TABLE `".$table."` DROP `".$columns."`;";

						if($show)
							p($sql, 'mysql');

						$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'deleteTableColumn', mysql_error());

						return $query;
					}else
						return false;
				}else
					return false;
			}

			/**
			 * Add columns to table
			 */
			public function addTableColumns($table = '', $columns = array()){
				return $this->addTableColumnsBase($table, $columns, false);
			}
			public function addTableColumns_($table = '', $columns = array()){
				return $this->addTableColumnsBase($table, $columns, true);
			}
			private function addTableColumnsBase($table = '', $columns = array(), $show = false){
				if($this->setTable($table)){
					if(!empty($columns) && is_array($columns) && !$this->is_assoc($columns)){
						$ret = true;
						foreach($columns as $column)
							$ret &= $this->addTableColumnsBase($table, $column, $show);

						return $ret;
					}else{
						switch(strtolower($columns['type'])){
							case 'char':
							case 'text':
							case 'longtext':
								$charset = "CHARACTER SET ".$this->dbConnectionCollation." COLLATE ".$this->dbConnectionCollation."_general_ci ";
							break;
						}
						$sql = "ALTER TABLE  `".$table."` ADD  `".$columns['name']."` ".$columns['type'].($columns['length'] ? "(".$columns['length'].")" : "")." ".(isset($charset) ? $charset : "").(isset($columns['default']) && $columns['default'] ? "NOT NULL DEFAULT ".(preg_match('/^[A-Z_]{1,30}\([^\)]{1,100}\)$/', $columns['default']) ? $columns['default'] : "'".$columns['default']."'") : "").($columns['comment'] ? " COMMENT  '".$columns['comment']."'" : "").";";
						if($show)
							p($sql, 'mysql');
						$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'addTableColumns', mysql_error());

						return $query;
					}
				}
			}

			/**
			 * Get column comment
			 */
			public function getColumnsComments($tableName, $columnsName){
				return $this->getColumnsCommentsBase($tableName, $columnsName, false);
			}
			public function getColumnsComments_($tableName, $columnsName){
				return $this->getColumnsCommentsBase($tableName, $columnsName, true);
			}
			private function getColumnsCommentsBase($tableName, $columnsName, $show){
				if(!$this->checkTable($tableName)){
					$this->error(6, 'getColumnsComments');
					return false;
				}

				$sql = "SHOW FULL COLUMNS FROM  `".$tableName."` LIKE '".$columnsName."';";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getColumnsComments', mysql_error());

				if($query){
					$ex = array();
					while($row = mysql_fetch_row($query))
						array_push($ex, $row[8]);
					if(sizeof($ex) == 1)
						$ex = array_pop($ex);
					return $ex;
				}else
					return false;
			}

			/**
			 * Change column comment
			 */
			public function changeColumnsComments($tableName, $columnsName, $comment = '', $type = 'INT(11)'){
				return $this->changeColumnsCommentsBase($tableName, $columnsName, $comment, $type, false);
			}
			public function changeColumnsComments_($tableName, $columnsName, $comment = '', $type = 'INT(11)'){
				return $this->changeColumnsCommentsBase($tableName, $columnsName, $comment, $type, true);
			}
			private function changeColumnsCommentsBase($tableName, $columnsName, $comment = '', $type = 'INT(11)', $show){
				if(!$this->checkTable($tableName)){
					$this->error(6, 'changeColumnsComments');
					return false;
				}

				$sql = "SHOW FULL COLUMNS FROM  `".$tableName."` LIKE '".$columnsName."';";
				$sql = "ALTER TABLE `".$tableName."` CHANGE COLUMN `".$columnsName."` `".$columnsName."` " . $type . " COMMENT '".$comment."';";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'changeColumnsComments', mysql_error());

				if($query){
					return true;
				}else
					return false;
			}




/**
 * ========================================================================================
 * ========================================================================================
 * ========================================================================================
 */

	
	
	/**
	 * Functions for work with data
	 */

		/**
		 * Increase or decrease value
		 */
		public function inc($table = '', $column, $where = '1', $logic = ''){
			return $this->incDecBase($table, $column, $where, $logic, 1, false);
		}
		public function inc_($table = '', $column, $where = '1', $logic = ''){
			return $this->incDecBase($table, $column, $where, $logic, 1, true);
		}
		public function dec($table = '', $column, $where = '1', $logic = ''){
			return $this->incDecBase($table, $column, $where, $logic, -1, false);
		}
		public function dec_($table = '', $column, $where = '1', $logic = ''){
			return $this->incDecBase($table, $column, $where, $logic, -1, true);
		}
		private function incDecBase($table = '', $column, $where = '1', $logic = '', $incDec = 1, $show = false){
			if($this->setTable($table)){
				if(!is_array($where))
					$where = array($this->clearForQuery($where));
				else
					$this->clearForQuery($where);
	
				$sql = "UPDATE ".$this->dbTable." SET `".$column."` = `".$column."` ".($incDec > 0 ? '+' : '-')." 1 WHERE ".implode(' '.$logic.' ', $where)." ;";
			
				if($show)
					p($sql, 'mysql');
		
				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'incDecBase', mysql_error());
		
				if($query)
					return true;
				else
					return false;
			}else
				return false;
		}
	
		/**
		 * Get row
		 */
		public function getRow($table = '', $columns = array('id'), $where = array('1'), $logic = 'AND', $ordBy = false, $ascDesc = false, $limit = '', $groupBy = array(), $original = false, $ss = false){
			return $this->getRowBase($table, $columns, $where, $logic, $ordBy, $ascDesc, $limit, $groupBy, $original, $ss, false);
		}
		public function getRow_($table = '', $columns = array('id'), $where = array('1'), $logic = 'AND', $ordBy = false, $ascDesc = false, $limit = '', $groupBy = array(), $original = false, $ss = false){
			return $this->getRowBase($table, $columns, $where, $logic, $ordBy, $ascDesc, $limit, $groupBy, $original, $ss, true);
		}
		private function getRowBase($table = '', $columns = array('id'), $where = array('1'), $logic = 'AND', $ordBy = false, $ascDesc = false, $limit = '', $groupBy = array(), $original = false, $ss = false, $show = false){
			if($this->setTable($table)){

				if($ascDesc != 'ASC' && $ascDesc != 'DESC')
					$ascDesc = $this->ascDescDefault;
	
				if(!is_array($columns)){
					$columns = array($this->clearForQuery($columns));
				}else
					$this->clearForQuery($columns);
	
				if(!is_array($where))
					$where = array($this->clearForQuery($where));
				else
					$this->clearForQuery($where);
	//			if(!in_array($this->dbTable, array('`sections`', '`sections_data`', '`media`'))){
				if($this->pubTF)
					if($this->dbTableCount == 1){
						if($logic == 'AOR'){
							$where = array('('.implode(' OR ', $where).')', 'published=1');
							$logic = 'AND';
						}else
							array_push($where, 'published=1');
					}else
						foreach($this->dbTables as $t)
							array_push($where, $t.'.published=1');
	//			}
	
				if(!is_array($groupBy))
					$groupBy = array($this->clearForQuery($groupBy));
				else
					$this->clearForQuery($groupBy);

				if(!$ordBy)
					$ordBy = 'id';
					/*
					if(INCLUDES_ROOT == 'includes/' || INCLUDES_ROOT == '../includes/')
						$ordBy = 'id';
					else
						$ordBy = 'seq';
					*/
	
				$sql = "SELECT ".implode(' , ',$columns)." FROM ".$this->dbTable." WHERE ".implode(' '.$logic.' ', $where).($groupBy ? " GROUP BY ".implode(' , ', $groupBy) : "")." ".($ordBy ? "ORDER BY ".$ordBy." ".$ascDesc : "")." ".($limit ? "LIMIT ".$limit : "" ).";";
	
				if($show)
					p($sql, 'mysql');
	
				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getRowBase', mysql_error());
		
				if($query){
					$ex = array();
					if($ss){
						if(sizeof($columns) > 1)
							foreach($columns as &$columns_val)
								$columns_val = preg_replace('/^[^\s]*\sas\s([a-z0-9_]+)$/i', '$1', $columns_val);
						else
							$one = true;
					}
					while($row = mysql_fetch_row($query)){
						if(isset($one) && $one)
							array_push($ex, $row[0]);
						else
							array_push($ex, array_combine($columns, $row));
					}
					if(is_array($ex) && sizeof($ex) == 0)
						return false;
					else if(is_array($ex) && sizeof($ex) == 1 && is_array($ex[0]) && sizeof($ex[0]) > 1 && $original == false)
						return array_pop($ex);
					else if(is_array($ex) && sizeof($ex) == 1 && is_array($ex[0]) && sizeof($ex[0]) == 1 && $original == false)
						return array_pop($ex[0]);
					return $ex;
				}else
					return false;
			}else
				return false;
		}

		/**
		 * Get COUNT()
		 */
		public function getCount($table = '', $where = '1', $logic = 'AND', $what = '*'){
			return $this->getCountBase($table, $where, $logic, $what, false);
		}
		public function getCount_($table = '', $where = '1', $logic = 'AND', $what = '*'){
			return $this->getCountBase($table, $where, $logic, $what, true);
		}
		private function getCountBase($table = '', $where = '1', $logic = 'AND', $what = '*', $show = false){
			if($this->setTable($table)){
	
				if($ascDesc != 'ASC' && $ascDesc != 'DESC')
					$ascDesc = $this->ascDescDefault;
	
				if(!is_array($where))
					$where = array($this->clearForQuery($where));
				else
					$this->clearForQuery($where);
	//			array_push($where, 'published=1');

				if($this->pubTF and !in_array($this->dbTable, array('`sections`', '`sections_data`', '`media`'))){
					if($this->dbTableCount == 1)
						array_push($where, 'published=1');
					else
						foreach($this->dbTables as $t)
							array_push($where, $t.'.published=1');
				}
	
				$sql = "SELECT COUNT(".$what.") FROM ".$this->dbTable." WHERE ".implode(' '.$logic.' ', $where)." ;";
			
				if($show)
					p($sql, 'mysql');
		
				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getCountBase', mysql_error());
		
				if($query){
					$ex=array();
					while($row=mysql_fetch_row($query))
						array_push($ex,$row);
					return $ex[0][0];
				}else
					return false;
			}else
				return false;
		}

		/**
		 * Update row
		 */
		public function updateRow($table = '', $columns = array(), $where = array('1'), $logic = 'AND', $limit = ''){
			return $this->updateRowBase($table, $columns, $where, $logic, $limit, false);
		}
		public function updateRow_($table = '', $columns = array(), $where = array('1'), $logic = 'AND', $limit = ''){
			return $this->updateRowBase($table, $columns, $where, $logic, $limit, true);
		}
		private function updateRowBase($table = '', $columns = array(), $where = array('1'), $logic = 'AND', $limit = '', $show = false){
			if($this->setTable($table)){
				if(is_array($columns) && sizeof($columns)){
					$values = array_values($columns);
					$columns = array_keys($columns);
				}else
					$values = array();
				if(!is_array($columns)){
					$columns = array($this->clearForQuery($columns));
				}else
					$this->clearForQuery($columns);
				
				if(!is_array($values)){
					$values = array($this->clearForQuery($values, true));
				}else
					$this->clearForQuery($values, true);

				if(!is_array($where))
					$where = array($this->clearForQuery($where));
				else
					$this->clearForQuery($where);
			
				if(sizeof($columns) == sizeof($values)){
					$set_to_sql = array();
					for($i = 0; $i < sizeof($columns); $i++)
	//					array_push($set_to_sql, $columns[$i]."` = '".$values[$i]."'");
						array_push($set_to_sql, $columns[$i]."` = ".$values[$i]);
					$set_to_sql = "`".implode(', `', $set_to_sql);
		
					$sql="UPDATE ".$this->dbTable." SET ".$set_to_sql." WHERE ".implode(' '.$logic.' ', $where)." ".($limit ? "LIMIT ".$limit : "" ).";";
				
					if($show)
						p($sql, 'mysql');

					$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'updateRowBase', mysql_error());

					if($query)
						return true;
					else
						return false;
				}else{
					$this->error(1, 'updateRowBase');
					return false;
				}
			}else
				return false;
		}

		/**
		 * Delete row
		 */
		public function deleteRow($table = '', $where = array('1'), $logic = ''){
			return $this->deleteRowBase($table, $where, $logic, false);
		}
		public function deleteRow_($table = '', $where = array('1'), $logic = ''){
			return $this->deleteRowBase($table, $where, $logic, true);
		}
		private function deleteRowBase($table = '', $where = array('1'), $logic = '', $show = false){
			if($this->setTable($table)){
				if(!is_array($where))
					$where = array($this->clearForQuery($where));
				else
					$this->clearForQuery($where);
				if(is_array($where) && sizeof($where) == 1 && $where[0] == '1')
					$sql = "TRUNCATE TABLE ".$this->dbTable.";";
				else
					$sql = "DELETE FROM ".$this->dbTable." WHERE ".implode(' '.$logic.' ', $where).";";

				if($show)
					p($sql, 'mysql');

				$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'deleteRowBase', mysql_error());

				if($query)
					return true;
				else
					return false;
			}else
				return false;
		}

		/**
		 * Insert row
		 */
		public function insertRow($table = '', $columns = array()){
			return $this->insertRowBase($table, $columns, false);
		}
		public function insertRow_($table = '', $columns = array()){
			return $this->insertRowBase($table, $columns, true);
		}
		private function insertRowBase($table = '', $columns = array(), $show = false){
			if($this->setTable($table)){
				if(is_array($columns) && sizeof($columns)){
					$values = array_values($columns);
					$columns = array_keys($columns);
				}else
					$values = array();
				if(!is_array($columns)){
					$columns = array($this->clearForQuery($columns));
				}else
					$this->clearForQuery($columns);
				
				if(!is_array($values)){
					$values = array($this->clearForQuery($values, true));
				}else
					$this->clearForQuery($values, true);

				if(sizeof($columns) == sizeof($values)){
					$columns = implode(', ', $columns);
	//				$values = '\''.implode('\', \'', $values).'\'';
					$values = implode(' , ', $values);

					$sql="INSERT INTO ".$this->dbTable." (".$columns.") VALUES (".$values.") ;";
				
					if($show)
						p($sql, 'mysql');

					$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'insertRowBase', mysql_error());
				
					if($query)
						return mysql_insert_id($this->dbRoot);
					else
						return false;
		
				}else{
					$this->error(1, 'insertRowBase');
					return false;
				}
			}else
				return false;
		}
	
		/**
		 * freeQuery function allows execute any SQL query
		 * 
		 * @param	string		$sql		- SQL string to execute
		 *
		 * @param	array|null	$columns	- Array of columns names (strings) if needed
		 *
		 * @return	false|array				- Query result array
		 *
		 */
		public function freeQuery($sql, $columns = array()){
			return $this->freeQueryBase($sql, $columns, false);
		}
		public function freeQuery_($sql, $columns = array()){
			return $this->freeQueryBase($sql, $columns, true);
		}
		private function freeQueryBase($sql, $columns = array(), $show){

			if($show)
				p($sql, 'mysql');

			$query = @mysql_query($sql, $this->dbRoot) or $this->error(5, 'getRowBase', mysql_error());

			if($query){
				$ex = array();
				if(is_resource($query))
					while($row = mysql_fetch_row($query))
					 	if(sizeof($row) == 1)
							array_push($ex, $row[0]);
						else
							array_push($ex, array_combine($columns, $row));
				if(is_array($ex) && sizeof($ex) == 0)
					return false;
			
				return $ex;
			}else
				return false;
		}



/**
 * ========================================================================================
 * ========================================================================================
 * ========================================================================================
 */
	
	
	
	/**
	 * Utilities functions
	 */		

		/**
		 * Error function
	 	*/
		private function error($errCode, $func, $errorString = false){
			switch($errCode){
				case 1: $err = 'Columns\'s array length and values\'s array length are different'; break;
				case 2: $err = 'Can\'t connect data base'; break;
				case 3: $err = 'Can\'t set data base connection collation'; break;
				case 4: $err = 'Can\'t set data base name'; break;
				case 5: $err = 'Can\'t send mysql query'; break;
				case 6: $err = 'Can\'t set table name'; break;
				case 7: $err = 'Can\'t close connection'; break;
				case 8: $err = 'Empty table name'; break;
				case 9: $err = 'Table already exists'; break;
				default: $err = 'No error code detected'; break;
			}
			$err = 'DataBase -> '.$func.' :: '.$err.($errorString ? (':
' . $errorString) : '');
			p('<font color="red"><strong>Error: </strong>'.$err.'</font><br />');
			return $err;
		}


		/**
		 * Set table
		 */
		private function setTable(&$table){
			if(is_array($table)){
				$this->clearForQuery($table);
				if('`'.implode('` , `', $table).'`' != $this->dbTable){
					for($i=0; $i < sizeof($table); $i++){
						if(!$this->checkTable($table[$i])){
							$this->error(6, 'setTable');
							return false;
						}
					}
					$this->dbTable = '`'.implode('` , `', $table).'`';
					$this->dbTableCount = sizeof($table);
					$this->dbTables = $table;
					return true;
				}else
					return true;
			}else{
				$this->clearForQuery($table);
				if('`'.$table.'`' != $this->dbTable){
					if($this->checkTable($table)){
						$this->dbTable = '`'.$table.'`';
						$this->dbTableCount = 1;
						return true;
					}else{
						$this->error(6, 'setTable');
						return false;
					}
				}else
					return true;
			}
		}

		/**
		 * Prepare string for query
		 */
		private function clearForQuery(&$obj, $values_tf=false){
			if(is_string($obj)){
				if(preg_match_all('/^([^=><!]{1,})([=><!]{1,2})(FROM_UNIXTIME|UNIX_TIMESTAMP|SUM|GeomFromText|PolygonFromText)\(((?:\"[^\"]*\"[, ]{0,2})*)\)$/', $obj, $res))
					$obj = mysql_real_escape_string("`".implode('`.`', explode('.', $res[1][0]))).'`'.$res[2][0].mysql_real_escape_string($res[3][0]).'('.$res[4][0].')';
				else if(preg_match_all('/^(FROM_UNIXTIME|SUM|DATE_FORMAT|UNIX_TIMESTAMP|NOW|GeomFromText|PolygonFromText)\(((?:[\"]{0,1}[^\"]*[\"]{0,1}[, ]{0,2})*)\)$/', $obj, $res)){
					$res[2][0] = explode(',', $res[2][0]);
					array_walk(
						$res[2][0],
						function(&$value, $index){
							$value = trim($value);
							$addfc = '';
							$fc = substr($value, 0, 1);
							if($fc == '"' || $fc == '\'')
								$addfc = $fc;
							$value = trim($value, '"\'');
							$value = $addfc.mysql_real_escape_string($value).$addfc;
						}
					);
					$obj = $res[1][0].'('.implode(',', $res[2][0]).')';
				}else if(preg_match('/^\((?:[^\s]*[\s]*(?:LIKE)[\s]*\"[^\"]*[\"][\s]*(?:OR|AND)*[\s]*)*\)$/i', $obj)){
				}else if(preg_match('/^\(?([a-z0-9_\.]* like "[^"]*"( or | and )?)+\)?$/i', $obj)){
				}else if(preg_match_all('/^([a-z_]*\=)?(STR_TO_DATE)\(\"([^\"]*)\"\,[\s]\"([^\"]*)\"\)$/i', $obj, $res)){
				}else if(preg_match_all('/^([^=><!]{1,})([=><!]{1,2})[\"]([^"]*)\"$/', $obj, $res))
					$obj = mysql_real_escape_string("`".implode('`.`', explode('.', $res[1][0]))).'`'.$res[2][0].'"'.mysql_real_escape_string($res[3][0]).'"';
				else
					$obj = ($values_tf ? '\'' : '').mysql_real_escape_string($obj).($values_tf ? '\'' : '');
			}else if(is_array($obj) && sizeof($obj) && in_array($obj[0], array('LIKE'))){
				$obj = $obj[1] . ' ' . $obj[0] . ' "'.mysql_real_escape_string($obj[2]).'"';
			}else if(is_array($obj))
				foreach($obj as $key=>$item)
					$this->clearForQuery($obj[$key], $values_tf);
			$obj = str_replace(
				array('~-~+', '+~-~', '~-~'),
				array('"%', '%"', '"'),
				$obj
			);
			return $obj;
		}

		private function is_assoc($var){
			return is_array($var) && array_diff_key($var, array_keys(array_keys($var)));
		}

}

?>