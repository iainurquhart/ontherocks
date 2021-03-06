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
 * Contains all global variables.
 */
class CraftVariable
{
	private $_rebrandVariable;

	/**
	 * @param $name
	 * @return mixed
	 */
	public function __get($name)
	{
		$plugin = craft()->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			$pluginName = $plugin->getClassHandle();
			$className = __NAMESPACE__.'\\'.$pluginName.'Variable';

			// Variables should already be imported by the plugin service, but let's double check.
			if (!class_exists($className))
			{
				Craft::import('plugins.'.$pluginName.'.variables.'.$pluginName.'Variable');
			}

			return new $className;
		}
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		$plugin = craft()->plugins->getPlugin($name);

		if ($plugin && $plugin->isEnabled)
		{
			return true;
		}

		return false;
	}

	/**
	 * Gets the current language in use.
	 *
	 * @return string
	 */
	public function locale()
	{
		return craft()->language;
	}

	/**
	 * Returns the packages in this Craft install, as defined by the craft_info table.
	 *
	 * @return array
	 */
	public function getPackages()
	{
		return Craft::getPackages();
	}

	/**
	 * Returns whether a package is included in the Craft build.
	 *
	 * @param $packageName;
	 * @return bool
	 */
	public function hasPackage($packageName)
	{
		return Craft::hasPackage($packageName);
	}

	// -------------------------------------------
	//  Template variable classes
	// -------------------------------------------

	/**
	 * @return AppVariable
	 */
	public function app()
	{
		return new AppVariable();
	}

	/**
	 * @return AssetsVariable
	 */
	public function assets()
	{
		return new AssetsVariable();
	}

	/**
	 * @return ConfigVariable
	 */
	public function config()
	{
		return new ConfigVariable();
	}

	/**
	 * @return FieldTypesVariable
	 */
	public function fieldTypes()
	{
		return new FieldTypesVariable();
	}

	/**
	 * @return CpVariable
	 */
	public function cp()
	{
		return new CpVariable();
	}

	/**
	 * @return DashboardVariable
	 */
	public function dashboard()
	{
		return new DashboardVariable();
	}

	/**
	 * @return EmailMessagesVariable
	 */
	public function emailMessages()
	{
		if (Craft::hasPackage(CraftPackage::Rebrand))
		{
			return new EmailMessagesVariable();
		}
	}

	/**
	 * @param array|null $criteria
	 * @return ElementCriteriaModel
	 */
	public function entries($criteria = null)
	{
		return craft()->elements->getCriteria(ElementType::Entry, $criteria);
	}

	/**
	 * @return FieldsVariable
	 */
	public function fields()
	{
		return new FieldsVariable();
	}

	/**
	 * @return EntryRevisionsVariable
	 */
	public function entryRevisions()
	{
		if (Craft::hasPackage(CraftPackage::PublishPro))
		{
			return new EntryRevisionsVariable();
		}
	}

	/**
	 * @return FeedsVariable
	 */
	public function feeds()
	{
		return new FeedsVariable();
	}

	/**
	 * @return LinksVariable
	 */
	public function links()
	{
		return new LinksVariable();
	}

	/**
	 * @return GlobalsVariable
	 */
	public function globals()
	{
		return new GlobalsVariable();
	}

	/**
	 * @return PluginsVariable
	 */
	public function plugins()
	{
		return new PluginsVariable();
	}

	/**
	 * @return RebrandVariable
	 */
	public function rebrand()
	{
		if (Craft::hasPackage(CraftPackage::Rebrand))
		{
			if (!isset($this->_rebrandVariable))
			{
				$this->_rebrandVariable = new RebrandVariable();
			}

			return $this->_rebrandVariable;
		}
	}

	/**
	 * @return HttpRequestVariable
	 */
	public function request()
	{
		return new HttpRequestVariable();
	}

 	/**
	 * @return RoutesVariable
	 */
	public function routes()
	{
		return new RoutesVariable();
	}

	/**
	 * @return SectionsVariable
	 */
	public function sections()
	{
		return new SectionsVariable();
	}

	/**
	 * @return SystemSettingsVariable
	 */
	public function systemSettings()
	{
		return new SystemSettingsVariable();
	}

	/**
	 * @return UpdatesVariable
	 */
	public function updates()
	{
		return new UpdatesVariable();
	}

	/**
	 * @param array|null $criteria
	 * @return ElementCriteriaModel|null
	 */
	public function users($criteria = null)
	{
		if (Craft::hasPackage(CraftPackage::Users))
		{
			return craft()->elements->getCriteria(ElementType::User, $criteria);
		}
	}

	/**
	 * @return UserGroupsVariable|null
	 */
	public function userGroups()
	{
		if (Craft::hasPackage(CraftPackage::Users))
		{
			return new UserGroupsVariable();
		}
	}

	/**
	 * @return UserPermissionsVariable|null
	 */
	public function userPermissions()
	{
		if (Craft::hasPackage(CraftPackage::Users))
		{
			return new UserPermissionsVariable();
		}
	}

	/**
	 * @return UserSessionVariable
	 */
	public function session()
	{
		return new UserSessionVariable();
	}

	/**
	 * @return LocalizationVariable
	 */
	public function i18n()
	{
		return new LocalizationVariable();
	}
}
