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
class MigrationsService extends BaseApplicationComponent
{
	/**
	 * @var string the default command action. It defaults to 'up'.
	 */
	public $defaultAction = 'up';

	private $_migrationTable;

	/**
	 * @throws Exception
	 * @return bool|void
	 */
	public function init()
	{
		$migration = new MigrationRecord('install');
		$this->_migrationTable = $migration->getTableName();
	}

	/**
	 * @param null $plugin
	 * @return mixed
	 */
	public function runToTop($plugin = null)
	{
		if (($migrations = $this->getNewMigrations($plugin)) === array())
		{
			if ($plugin)
			{
				Craft::log('No new migration(s) found for the plugin '.$plugin->getClassHandle().'. Your system is up-to-date.');
			}
			else
			{
				Craft::log('No new migration(s) found for Craft. Your system is up-to-date.');
			}

			return true;
		}

		$total = count($migrations);

		if ($plugin)
		{
			Craft::log("Total $total new ".($total === 1 ? 'migration' : 'migrations')." to be applied for plugin ".$plugin->getClassHandle().":");
		}
		else
		{
			Craft::log("Total $total new ".($total === 1 ? 'migration' : 'migrations')." to be applied for Craft:");
		}

		foreach ($migrations as $migration)
		{
			Craft::log($migration);
		}

		foreach ($migrations as $migration)
		{
			// Refresh the DB cache
			craft()->db->getSchema()->refresh();

			// Set a new 2 minute time limit
			set_time_limit(120);

			if ($this->migrateUp($migration, $plugin) === false)
			{
				if ($plugin)
				{
					Craft::log('Migration failed for plugin '.$plugin->getClassHandle().'. All later '.$plugin->getClassHandle().' migrations are canceled.', \CLogger::LEVEL_ERROR);
				}
				else
				{
					Craft::log('Migration failed for Craft. All later Craft migrations are canceled.', \CLogger::LEVEL_ERROR);
				}

				return false;
			}
		}

		if ($plugin)
		{
			Craft::log($plugin->getClassHandle().' migrated up successfully.');
		}
		else
		{
			Craft::log('Craft migrated up successfully.');
		}

		return true;
	}

	/**
	 * @param      $class
	 * @param null $plugin
	 * @return bool|null
	 */
	public function migrateUp($class, $plugin = null)
	{
		if($class === $this->getBaseMigration())
		{
			return null;
		}

		if ($plugin)
		{
			Craft::log('Applying migration: '.$class.' for plugin: '.$plugin->getClassHandle());
		}
		else
		{
			Craft::log('Applying migration: '.$class);
		}

		$start = microtime(true);
		$migration = $this->instantiateMigration($class, $plugin);

		if ($migration->up() !== false)
		{
			$column = $this->_getCorrectApplyTimeColumn();

			if ($plugin)
			{
				$pluginRecord = craft()->plugins->getPluginRecord($plugin);

				craft()->db->createCommand()->insert($this->_migrationTable, array(
					'version' => $class,
					$column => DateTimeHelper::currentTimeForDb(),
					'pluginId' => $pluginRecord->getPrimaryKey()
				));
			}
			else
			{
				craft()->db->createCommand()->insert($this->_migrationTable, array(
					'version' => $class,
					$column => DateTimeHelper::currentTimeForDb()
				));
			}

			$time = microtime(true) - $start;
			Craft::log('Applied migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)');
			return true;
		}
		else
		{
			$time = microtime(true) - $start;
			Craft::log('Failed to apply migration: '.$class.' (time: '.sprintf("%.3f", $time).'s)', \CLogger::LEVEL_ERROR);
			return false;
		}
	}

	/**
	 * @param      $class
	 * @param null $plugin
	 * @return mixed
	 */
	public function instantiateMigration($class, $plugin = null)
	{
		$file = IOHelper::normalizePathSeparators($this->getMigrationPath($plugin).$class.'.php');

		require_once($file);

		$class = __NAMESPACE__.'\\'.$class;
		$migration = new $class;
		$migration->setDbConnection(craft()->db);

		return $migration;
	}

	/**
	 * @param null $plugin
	 * @param null $limit
	 * @return mixed
	 */
	public function getMigrationHistory($plugin = null, $limit = null)
	{
		$column = $this->_getCorrectApplyTimeColumn();

		if ($plugin === 'all')
		{
			$query = craft()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->order('version DESC');
		}
		else if ($plugin)
		{
			$pluginRecord = craft()->plugins->getPluginRecord($plugin);

			$query = craft()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->where('pluginId = :pluginId', array(':pluginId' => $pluginRecord->getPrimaryKey()))
				->order('version DESC');
		}
		else
		{
			$query = craft()->db->createCommand()
				->select('version, '.$column)
				->from($this->_migrationTable)
				->where('pluginId IS NULL')
				->order('version DESC');
		}

		if ($limit !== null)
		{
			$query->limit($limit);
		}

		$migrations = $query->queryAll();

		// Convert the dates to DateTime objects
		foreach ($migrations as &$migration)
		{
			$column = $this->_getCorrectApplyTimeColumn();

			// TODO: MySQL specific.
			$migration['applyTime'] = DateTime::createFromFormat(DateTime::MYSQL_DATETIME, $migration[$column]);
		}

		return $migrations;
	}

	/**
	 * Gets migrations that have no been applied yet AND have a later timestamp than the current Craft release.
	 *
	 * @param $plugin
	 *
	 * @return array
	 */
	public function getNewMigrations($plugin = null)
	{
		$applied = array();
		$migrationPath = $this->getMigrationPath($plugin);

		foreach ($this->getMigrationHistory($plugin) as $migration)
		{
			$applied[] = $migration['version'];
		}

		$migrations = array();
		$handle = opendir($migrationPath);

		if ($plugin)
		{
			$pluginRecord = craft()->plugins->getPluginRecord($plugin);
			$storedDate = $pluginRecord->installDate->getTimestamp();
		}
		else
		{
			$storedDate = Craft::getReleaseDate()->getTimestamp();
		}

		while (($file = readdir($handle)) !== false)
		{
			if ($file[0] === '.')
			{
				continue;
			}

			$path = IOHelper::normalizePathSeparators($migrationPath.$file);
			$class = IOHelper::getFileName($path, false);

			// Have we already run this migration?
			if (in_array($class, $applied))
			{
				continue;
			}

			if (preg_match('/^m(\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_\w+\.php$/', $file, $matches))
			{
				// Check the migration timestamp against the Craft release date
				$time = strtotime('20'.$matches[1].'-'.$matches[2].'-'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6]);

				if ($time > $storedDate)
				{
					$migrations[] = $class;
				}
			}
		}

		closedir($handle);
		sort($migrations);
		return $migrations;
	}

	/**
	 * Returns the base migration name.
	 *
	 * @return string
	 */
	public function getBaseMigration()
	{
		return 'm000000_000000_base';
	}

	/**
	 * @param null $plugin
	 * @return string
	 * @throws Exception
	 */
	public function getMigrationPath($plugin = null)
	{
		if ($plugin)
		{
			$path = craft()->path->getMigrationsPath($plugin->getClassHandle());
		}
		else
		{
			$path = craft()->path->getMigrationsPath();
		}

		if (!IOHelper::folderExists($path))
		{
			if (!IOHelper::createFolder($path))
			{
				throw new Exception(Craft::t('Tried to create the migration folder at “{folder}”, but could not.', array('folder' => $path)));
			}
		}

		return $path;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return file_get_contents(Craft::getPathOfAlias('app.etc.updates.migrationtemplate').'.php');
	}

	/**
	 * TODO: Deprecate after next breakpoint.
	 *
	 * @return string
	 */
	private function _getCorrectApplyTimeColumn()
	{
		$migrationsTable = craft()->db->getSchema()->getTable('{{migrations}}');

		$applyTimeColumn = 'apply_time';

		if ($migrationsTable->getColumn('applyTime') !== null)
		{
			$applyTimeColumn = 'applyTime';
		}

		return $applyTimeColumn;
	}
}
