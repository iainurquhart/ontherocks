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

Craft::requirePackage(CraftPackage::Users);

/**
 * User group functions
 */
class UserGroupsVariable
{
	/**
	 * Returns all user groups.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		return craft()->userGroups->getAllGroups($indexBy);
	}

	/**
	 * Gets a user group by its ID.
	 *
	 * @param int $groupId
	 * @return UserGroupModel|null
	 */
	public function getGroupById($groupId)
	{
		return craft()->userGroups->getGroupById($groupId);
	}

	/**
	 * Gets a user group by its handle.
	 *
	 * @param string $groupHandle
	 * @return UserGroupModel|null
	 */
	public function getGroupByHandle($groupHandle)
	{
		return craft()->userGroups->getGroupByHandle($groupHandle);
	}
}
