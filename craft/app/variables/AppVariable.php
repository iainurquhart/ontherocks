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
 * App functions
 */
class AppVariable
{
	/**
	 * Returns the current Craft version.
	 *
	 * @return string
	 */
	public function getVersion()
	{
		return Craft::getVersion();
	}

	/**
	 * Returns the current Craft build.
	 *
	 * @return string
	 */
	public function getBuild()
	{
		return Craft::getBuild();
	}

	/**
	 * Returns the site name.
	 *
	 * @return string
	 */
	public function getSiteName()
	{
		return Craft::getSiteName();
	}

	/**
	 * Returns the site URL.
	 *
	 * @return string
	 */
	public function getSiteUrl()
	{
		return Craft::getSiteUrl();
	}

	/**
	 * Returns the site language.
	 *
	 * @return string
	 */
	public function getLocale()
	{
		return 'en_us';
		return Craft::getLanguage();
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @return string
	 */
	public function isSystemOn()
	{
		return Craft::isSystemOn();
	}

	/**
	 * Return max upload size in bytes.
	 *
	 * @return int
	 */
	public function getMaxUploadSize()
	{
		$maxUpload = (int)(ini_get('upload_max_filesize'));
		$maxPost = (int)(ini_get('post_max_size'));
		$memoryLimit = (int)(ini_get('memory_limit'));
		$uploadMb = min($maxUpload, $maxPost, $memoryLimit);

		// Convert MB to B and return
		return $uploadMb * 1048576; // 1024 x 1024 = 1048576
	}
}
