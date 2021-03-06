<?php
namespace Craft;

/**
 * Craft by Pixel & Tonic
 *
 * @package   Craft
 * @author    Pixel & Tonic, Inc.
 * @copyright Copyright (c) 2013, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license1.0.html Craft License
 * @link      http://buildwithcraft.com
 */

/**
 *
 */
abstract class BaseMigration extends \CDbMigration
{

	/**
	 * This method contains the logic to be executed when applying this migration.
	 * Child classes may implement this method to provide actual migration logic.
	 *
	 * @return boolean
	 */
	public function up()
	{
		$transaction = $this->dbConnection->beginTransaction();

		try
		{
			$result = $this->safeUp();

			if ($result === false)
			{
				$transaction->rollback();
				return false;
			}

			$transaction->commit();
			return true;
		}
		catch(\Exception $e)
		{
			Craft::log($e->getMessage().' ('.$e->getFile().':'.$e->getLine().')', \CLogger::LEVEL_ERROR);
			Craft::log($e->getTraceAsString(), \CLogger::LEVEL_ERROR);

			$transaction->rollback();
			return false;
		}
	}

	/**
	 * Builds and executes a SQL statement for creating a new DB table.
	 *
	 * The columns in the new  table should be specified as name-definition pairs (e.g. 'name'=>'string'),
	 * where name stands for a column name which will be properly quoted by the method, and definition
	 * stands for the column type which can contain an abstract DB type.
	 * The {@link getColumnType} method will be invoked to convert any abstract type into a physical one.
	 *
	 * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly
	 * inserted into the generated SQL.
	 *
	 * @param string $table           the name of the table to be created. The name will be properly quoted by the method.
	 * @param array  $columns         the columns (name=>definition) in the new table.
	 * @param string $options         additional SQL fragment that will be appended to the generated SQL.
	 * @param bool   $addIdColumn     whether to add an auto-incrementing primary key id column to the table.
	 * @param bool   $addAuditColumns whether to append auditing columns to the end of the table (dateCreated, dateUpdated, uid)
	 * @return void
	 */
	public function createTable($table, $columns, $options = null, $addIdColumn = true, $addAuditColumns = true)
	{
		Craft::log('Create table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->createTable($table, $columns, $options, $addIdColumn, $addAuditColumns);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $vals
	 * @return int
	 */
	public function insertAll($table, $columns, $vals)
	{
		Craft::log('Batch inserting into '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->insertAll($table, $columns, $vals);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @return int
	 */
	public function dropTableIfExists($table)
	{
		Craft::log('Dropping table if exists '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropTableIfExists($table);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for adding a new DB column.
	 *
	 * @param string $table the table that the new column will be added to. The table name will be properly quoted by the method.
	 * @param string $column the name of the new column. The name will be properly quoted by the method.
	 * @param string $type the column type. The {@link getColumnType} method will be invoked to convert abstract column type (if any) into the physical one. Anything that is not recognized as abstract type will be kept in the generated SQL.
	 * For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
	 */
	public function addColumn($table, $column, $type)
	{
		Craft::log('Adding column '.$column.' to table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addColumn($table, $column, $type);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @return mixed
	 */
	public function addColumnFirst($table, $column, $type)
	{
		Craft::log('Adding column '.$column.' first to table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addColumnFirst($table, $column, $type);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $before
	 * @return mixed
	 */
	public function addColumnBefore($table, $column, $type, $before)
	{
		Craft::log('Adding column '.$column.' before '.$before.' to table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addColumnBefore($table, $column, $type, $before);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $column
	 * @param $type
	 * @param $after
	 * @return mixed
	 */
	public function addColumnAfter($table, $column, $type, $after)
	{
		Craft::log('Adding column '.$column.' after '.$after.' to table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addColumnAfter($table, $column, $type, $after);

		$this->_processDoneTime($time);
	}

	/**
	 * @param      $table
	 * @param      $column
	 * @param      $type
	 * @param null $newName
	 * @param      $after
	 * @return int
	 */
	public function alterColumn($table, $column, $type, $newName = null, $after = null)
	{
		Craft::log('Altering column '.$column.' in table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->alterColumn($table, $column, $type, $newName, $after);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param $refTable
	 * @param $refColumns
	 * @param null $delete
	 * @param null $update
	 * @return int
	 */
	public function addForeignKey($table, $columns, $refTable, $refColumns, $delete = null, $update = null)
	{
		Craft::log('Adding foreign key to '.$table.' ('.$columns.') references '.$refTable.' ('.$refColumns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addForeignKey($table, $columns, $refTable, $refColumns, $delete, $update);

		$this->_processDoneTime($time);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropForeignKey($table, $columns)
	{
		Craft::log('Dropping foreign key from table '.$table.' ('.$columns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropForeignKey($table, $columns);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $table
	 * @param $columns
	 * @param bool $unique
	 * @return int
	 */
	public function createIndex($table, $columns, $unique = false)
	{
		Craft::log('Creating '.($unique ? ' unique' : '').' index on '.$table.' ('.$columns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->createIndex($table, $columns, $unique);

		$this->_processDoneTime($time);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @param bool   $unique
	 * @return int
	 */
	public function dropIndex($table, $columns, $unique = false)
	{
		Craft::log('Dropping '.($unique ? ' unique' : '').' index on '.$table.' ('.$columns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropIndex($table, $columns, $unique);

		$this->_processDoneTime($time);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function addPrimaryKey($table, $columns)
	{
		Craft::log('Altering table '.$table.' add new primary key ('.$columns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->addPrimaryKey($table, $columns);

		$this->_processDoneTime($time);
	}

	/**
	 * @param string $table
	 * @param string $columns
	 * @return int
	 */
	public function dropPrimaryKey($table, $columns)
	{
		Craft::log('Altering table '.$table.' drop primary key ('.$columns.') ...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropPrimaryKey($table, $columns);

		$this->_processDoneTime($time);
	}

	/**
	 * @return bool|void
	 */
	public function down()
	{
		Craft::log('Down migrations are not supported.', \CLogger::LEVEL_WARNING);
	}

	/**
	 * @return bool|void
	 */
	public function safeDown()
	{
		Craft::log('Down migrations are not supported.', \CLogger::LEVEL_WARNING);
	}

	/**
	 * Executes a SQL statement.
	 * This method executes the specified SQL statement using {@link dbConnection}.
	 *
	 * @param string $sql the SQL statement to be executed
	 * @param array $params input parameters (name=>value) for the SQL execution. See {@link CDbCommand::execute} for more details.
	 */
	public function execute($sql, $params=array())
	{
		Craft::log('Executing SQL: '.$sql.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand($sql)->execute($params);

		$this->_processDoneTime($time);
	}

	/**
	 * Creates and executes an INSERT SQL statement.
	 * The method will properly escape the column names, and bind the values to be inserted.
	 *
	 * @param string $table the table that new rows will be inserted into.
	 * @param array $columns the column data (name=>value) to be inserted into the table.
	 */
	public function insert($table, $columns)
	{
		Craft::log('Inserting into '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->insert($table, $columns);

		$this->_processDoneTime($time);
	}

	/**
	 * Creates and executes an UPDATE SQL statement.
	 * The method will properly escape the column names and bind the values to be updated.
	 *
	 * @param string $table      the table to be updated.
	 * @param array  $columns    the column data (name=>value) to be updated.
	 * @param mixed  $conditions the conditions that will be put in the WHERE part. Please refer to {@link CDbCommand::where} on how to specify conditions.
	 * @param array  $params     the parameters to be bound to the query.
	 * @return void
	 */
	public function update($table, $columns, $conditions = '', $params = array())
	{
		Craft::log('Updating '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->update($table, $columns, $conditions, $params);

		$this->_processDoneTime($time);
	}

	/**
	 * Creates and executes a DELETE SQL statement.
	 *
	 * @param string $table      the table where the data will be deleted from.
	 * @param mixed  $conditions the conditions that will be put in the WHERE part. Please refer to {@link CDbCommand::where} on how to specify conditions.
	 * @param array  $params     the parameters to be bound to the query.
	 * @return void
	 */
	public function delete($table, $conditions = '', $params = array())
	{
		Craft::log('Deleting from '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->delete($table, $conditions, $params);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for renaming a DB table.
	 *
	 * @param string $table the table to be renamed. The name will be properly quoted by the method.
	 * @param string $newName the new table name. The name will be properly quoted by the method.
	 */
	public function renameTable($table, $newName)
	{
		Craft::log('Renaming table '.$table.' to '.$newName.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->renameTable($table, $newName);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB table.
	 *
	 * @param string $table the table to be dropped. The name will be properly quoted by the method.
	 */
	public function dropTable($table)
	{
		Craft::log('Dropping table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropTable($table);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for truncating a DB table.
	 *
	 * @param string $table the table to be truncated. The name will be properly quoted by the method.
	 */
	public function truncateTable($table)
	{
		Craft::log('Truncating table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->truncateTable($table);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB column.
	 *
	 * @param string $table the table whose column is to be dropped. The name will be properly quoted by the method.
	 * @param string $column the name of the column to be dropped. The name will be properly quoted by the method.
	 */
	public function dropColumn($table, $column)
	{
		Craft::log('Drop column '.$column.' from table '.$table.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->dropColumn($table, $column);

		$this->_processDoneTime($time);
	}

	/**
	 * Builds and executes a SQL statement for renaming a column.
	 *
	 * @param string $table the table whose column is to be renamed. The name will be properly quoted by the method.
	 * @param string $name the old name of the column. The name will be properly quoted by the method.
	 * @param string $newName the new name of the column. The name will be properly quoted by the method.
	 */
	public function renameColumn($table, $name, $newName)
	{
		Craft::log('Rename column '.$name.' in table '.$table.' to '.$newName.'...');

		$time = microtime(true);
		$this->dbConnection->createCommand()->renameColumn($table, $name, $newName);

		$this->_processDoneTime($time);
	}

	/**
	 * Refreshed schema cache for a table
	 *
	 * @param string $table name of the table to refresh
	 */
	public function refreshTableSchema($table)
	{
		Craft::log('Refresh table '.$table.' schema cache...');

		$time = microtime(true);
		$this->dbConnection->getSchema()->getTable($table,true);

		$this->_processDoneTime($time);
	}

	/**
	 * @param $time
	 */
	private function _processDoneTime($time)
	{
		Craft::log("Done (time: ".sprintf('%.3f', microtime(true) - $time)."s)");
	}
}
