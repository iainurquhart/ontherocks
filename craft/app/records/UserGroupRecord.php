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
 *
 */
class UserGroupRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'usergroups';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'   => array(AttributeType::Name, 'required' => true),
			'handle' => array(AttributeType::Handle, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'users' => array(static::MANY_MANY, 'UserRecord', 'usergroups_users(groupId, userId)'),
		);
	}
}
