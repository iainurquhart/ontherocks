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
class UpdatesService extends BaseApplicationComponent
{
	private $_updateModel;

	/**
	 * @param $craftReleases
	 * @return bool
	 */
	public function criticalCraftUpdateAvailable($craftReleases)
	{
		foreach ($craftReleases as $craftRelease)
		{
			if ($craftRelease->critical)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $plugins
	 * @return bool
	 */
	public function criticalPluginUpdateAvailable($plugins)
	{
		foreach ($plugins as $plugin)
		{
			if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
			{
				foreach ($plugin->releases as $release)
				{
					if ($release->critical)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return (isset($this->_updateModel) || craft()->fileCache->get('updateinfo') !== false);
	}

	/**
	 * @return int
	 */
	public function getTotalAvailableUpdates()
	{
		$count = 0;

		if ($this->isUpdateInfoCached())
		{
			$updateModel = $this->getUpdates();

			// Could be false!
			if ($updateModel)
			{
				if (!empty($updateModel->app))
				{
					if ($updateModel->app->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
					{
						if (isset($updateModel->app->releases) && count($updateModel->app->releases) > 0)
						{
							$count++;
						}
					}
				}

				if (!empty($updateModel->plugins))
				{
					foreach ($updateModel->plugins as $plugin)
					{
						if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable)
						{
							if (isset($plugin->releases) && count($plugin->releases) > 0)
							{
								$count++;
							}
						}
					}
				}
			}
		}

		return $count;
	}

	/**
	 * @return mixed
	 */
	public function isCriticalUpdateAvailable()
	{
		if ((isset($this->_updateModel) && $this->_updateModel->app->criticalUpdateAvailable))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	public function isManualUpdateRequired()
	{
		if ((isset($this->_updateModel) && $this->_updateModel->app->manualUpdateRequired))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param bool $forceRefresh
	 * @return UpdateModel|false
	 */
	public function getUpdates($forceRefresh = false)
	{
		if (!isset($this->_updateModel) || $forceRefresh)
		{
			$updateModel = false;

			if (!$forceRefresh)
			{
				// get the update info from the cache if it's there
				$updateModel = craft()->fileCache->get('updateinfo');
			}

			// fetch it if it wasn't cached, or if we're forcing a refresh
			if ($forceRefresh || $updateModel === false)
			{
				$etModel = $this->check();

				if ($etModel == null)
				{
					$updateModel = new UpdateModel();
					$errors[] = Craft::t('Craft is unable to determine if an update is available at this time.');
					$updateModel->errors = $errors;
				}
				else
				{
					$updateModel = $etModel->data;

					// cache it and set it to expire according to config
					craft()->fileCache->set('updateinfo', $updateModel);
				}
			}

			$this->_updateModel = $updateModel;
		}

		return $this->_updateModel;
	}

	/**
	 * @return bool
	 */
	public function flushUpdateInfoFromCache()
	{
		Craft::log('Flushing update info from cache.');

		if (IOHelper::clearFolder(craft()->path->getCompiledTemplatesPath()) && IOHelper::clearFolder(craft()->path->getCachePath()))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $version
	 * @param $build
	 * @param $releaseDate
	 * @return bool
	 */
	public function setNewCraftInfo($version, $build, $releaseDate)
	{
		$info = Craft::getInfo();

		$info->version     = $version;
		$info->build       = $build;
		$info->releaseDate = $releaseDate;

		return Craft::saveInfo($info);
	}

	/**
	 * @param $plugin
	 * @return bool
	 */
	public function setNewPluginInfo($plugin)
	{
		$pluginRecord = craft()->plugins->getPluginRecord($plugin);

		$pluginRecord->version = $plugin->getVersion();
		if ($pluginRecord->save())
		{
			return true;
		}

		return false;
	}

	/**
	 * @return UpdateModel
	 */
	public function check()
	{
		$updateModel = new UpdateModel();
		$updateModel->app = new AppUpdateModel();
		$updateModel->app->localBuild   = CRAFT_BUILD;
		$updateModel->app->localVersion = CRAFT_VERSION;

		$plugins = craft()->plugins->getPlugins();

		$pluginUpdateModels = array();

		foreach ($plugins as $plugin)
		{
			$pluginUpdateModel = new PluginUpdateModel();
			$pluginUpdateModel->class = $plugin->getClassHandle();
			$pluginUpdateModel->localVersion = $plugin->version;

			$pluginUpdateModels[$plugin->getClassHandle()] = $pluginUpdateModel;
		}

		$updateModel->plugins = $pluginUpdateModels;

		$etModel = craft()->et->checkForUpdates($updateModel);
		return $etModel;
	}

	/**
	 * Checks to see if Craft can write to a defined set of folders/files that are needed for auto-update to work.
	 *
	 * @return array|null
	 */
	public function getUnwritableFolders()
	{
		$checkPaths = array(
			craft()->path->getAppPath(),
			craft()->path->getPluginsPath(),
		);

		$errorPath = null;

		foreach ($checkPaths as $writablePath)
		{
			if (!IOHelper::isWritable($writablePath))
			{
				$errorPath[] = IOHelper::getRealPath($writablePath);
			}
		}

		return $errorPath;
	}

	/**
	 * @param $manual
	 * @param $handle
	 * @return array
	 */
	public function prepareUpdate($manual, $handle)
	{
		Craft::log('Preparing to update '.$handle.'.');

		try
		{
			$updater = new Updater();

			// No need to get the latest update info if this is a manual update.
			if (!$manual)
			{
				$updater->getLatestUpdateInfo();
			}

			$updater->checkRequirements();

			Craft::log('Finished preparing to update '.$handle.'.');
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function processUpdateDownload()
	{
		Craft::log('Starting to process the update download.');

		try
		{
			$updater = new Updater();
			$result = $updater->processDownload();
			$result['success'] = true;

			Craft::log('Finished processing the update download.');
			return $result;
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function backupFiles($uid)
	{
		Craft::log('Starting to backup files that need to be updated.');

		try
		{
			$updater = new Updater();
			$updater->backupFiles($uid);

			Craft::log('Finished backing up files.');
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function updateFiles($uid)
	{
		Craft::log('Starting to update files.');

		try
		{
			$updater = new Updater();
			$updater->updateFiles($uid);

			Craft::log('Finished updating files.');
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function backupDatabase($uid)
	{
		Craft::log('Starting to backup database.');

		try
		{
			$updater = new Updater();
			$result = $updater->backupDatabase($uid);

			if (!$result)
			{
				Craft::log('Did not backup database because there were no migrations to run.');
				return array('success' => true);
			}
			else
			{
				Craft::log('Finished backing up database.');
				return array('success' => true, 'dbBackupPath' => $result);
			}
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param      $uid
	 * @param      $handle
	 * @param bool $dbBackupPath
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateDatabase($uid, $handle, $dbBackupPath = false)
	{
		Craft::log('Starting to update the database.');

		try
		{
			$updater = new Updater();

			if ($handle == 'craft')
			{
				Craft::log('Craft wants to update the database.');
				$updater->updateDatabase($uid, $dbBackupPath);
				Craft::log('Craft is done updating the database.');
			}
			else
			{
				$plugin = craft()->plugins->getPlugin($handle);
				if ($plugin)
				{
					Craft::log('The plugin, '.$plugin->getName().' wants to update the database.');
					$updater->updateDatabase($uid, $dbBackupPath, $plugin);
					Craft::log('The plugin, '.$plugin->getName().' is done updating the database.');
				}
				else
				{
					Craft::log('Cannot find a plugin with the handle '.$handle.' or it is not enabled, therefore it cannot update the database.', \CLogger::LEVEL_ERROR);
					throw new Exception(Craft::t('Cannot find an enabled plugin with the handle '.$handle));
				}
			}

			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @param $handle
	 * @return array
	 */
	public function updateCleanUp($uid, $handle)
	{
		Craft::log('Starting to clean up after the update.');

		try
		{
			$updater = new Updater();
			$updater->cleanUp($uid, $handle);

			Craft::log('Finished cleaning up after the update.');
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param      $uid
	 * @param bool $dbBackupPath
	 * @return array
	 */
	public function rollbackUpdate($uid, $dbBackupPath = false)
	{
		try
		{
			if ($dbBackupPath && craft()->config->get('backupDbOnUpdate') && craft()->config->get('restoreDbOnUpdateFailure'))
			{
				Craft::log('Rolling back any database changes.');
				UpdateHelper::rollBackDatabaseChanges($dbBackupPath);
				Craft::log('Done rolling back any database changes.');
			}

			// If uid !== false, it's an auto-update.
			if ($uid !== false)
			{
				Craft::log('Rolling back any file changes.');
				UpdateHelper::rollBackFileChanges(UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid)));
				Craft::log('Done rolling back any file changes.');
			}

			Craft::log('Finished rolling back changes.');
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * Determines if we're in the middle of a manual update either because of Craft or a plugin, and a DB update is needed.
	 *
	 * @return bool
	 */
	public function isDbUpdateNeeded()
	{
		if ($this->isCraftDbUpdateNeeded())
		{
			return true;
		}

		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns if Craft needs to run a database update or not.
	 *
	 * @access private
	 * @return bool
	 */
	public function isCraftDbUpdateNeeded()
	{
		return (CRAFT_BUILD > Craft::getBuild());
	}

	/**
	 * Returns true is the build stored in craft_info is less than the minimum required build on the file system.
	 * This effectively makes sure that a user cannot manually update past a manual breakpoint.
	 *
	 * @return bool
	 */
	public function isBreakpointUpdateNeeded()
	{
		// Only Craft has the concept of a breakpoint, not plugins.
		if ($this->isCraftDbUpdateNeeded())
		{
			return (Craft::getBuild() < CRAFT_MIN_BUILD_REQUIRED);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns a list of plugins that are in need of a database update.
	 *
	 * @return array|null
	 */
	public function getPluginsThatNeedDbUpdate()
	{
		$pluginsThatNeedDbUpdate = array();

		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
			{
				$pluginsThatNeedDbUpdate[] = $plugin;
			}
		}

		return $pluginsThatNeedDbUpdate;
	}
}
