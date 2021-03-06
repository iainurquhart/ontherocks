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
 * Stores the available Craft update info.
 */
class AppUpdateModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes['localBuild']              = AttributeType::String;
		$attributes['localVersion']            = AttributeType::String;
		$attributes['latestVersion']           = AttributeType::String;
		$attributes['latestBuild']             = AttributeType::String;
		$attributes['latestDate']              = AttributeType::DateTime;
		$attributes['realLatestVersion']       = AttributeType::String;
		$attributes['realLatestBuild']         = AttributeType::String;
		$attributes['realLatestDate']          = AttributeType::DateTime;
		$attributes['criticalUpdateAvailable'] = AttributeType::Bool;
		$attributes['manualUpdateRequired']    = AttributeType::Bool;
		$attributes['breakpointRelease']       = AttributeType::Bool;
		$attributes['versionUpdateStatus']     = AttributeType::String;
		$attributes['manualDownloadEndpoint']  = AttributeType::String;
		$attributes['releases']                = AttributeType::Mixed;

		return $attributes;
	}

	/**
	 * @param string $name
	 * @param mixed  $value
	 * @return bool|void
	 */
	public function setAttribute($name, $value)
	{
		if ($name == 'releases')
		{
			$value = AppNewReleaseModel::populateModels($value);
		}

		parent::setAttribute($name, $value);
	}
}
