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
class UserRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'users';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'username'                   => array(AttributeType::String, 'maxLength' => 100, 'required' => true),
			'photo'                      => array(AttributeType::String, 'maxLength' => 50),
			'firstName'                  => array(AttributeType::String, 'maxLength' => 100),
			'lastName'                   => array(AttributeType::String, 'maxLength' => 100),
			'email'                      => array(AttributeType::Email, 'required' => true),
			'password'                   => array(AttributeType::String, 'maxLength' => 255, 'column' => ColumnType::Char),
			'encType'                    => array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'preferredLocale'            => array(AttributeType::Locale),
			'emailFormat'                => array(AttributeType::Enum, 'values' => array('text', 'html'), 'default' => 'text', 'required' => true),
			'admin'                      => array(AttributeType::Bool),
			'status'                     => array(AttributeType::Enum, 'values' => array('locked', 'suspended', 'pending', 'active', 'archived'), 'default' => 'pending'),
			'lastLoginDate'              => array(AttributeType::DateTime),
			'lastLoginAttemptIPAddress'  => array(AttributeType::String, 'maxLength' => 45),
			'invalidLoginWindowStart'    => array(AttributeType::DateTime),
			'invalidLoginCount'          => array(AttributeType::Number, 'column' => ColumnType::TinyInt, 'unsigned' => true),
			'lastInvalidLoginDate'       => array(AttributeType::DateTime),
			'lockoutDate'                => array(AttributeType::DateTime),
			'verificationCode'           => array(AttributeType::String, 'maxLength' => 100, 'column' => ColumnType::Char),
			'verificationCodeIssuedDate' => array(AttributeType::DateTime),
			'passwordResetRequired'      => array(AttributeType::Bool),
			'lastPasswordChangeDate'     => array(AttributeType::DateTime),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		$relations = array(
			'element'         => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'preferredLocale' => array(static::BELONGS_TO, 'LocaleRecord', 'preferredLocale', 'onDelete' => static::SET_NULL, 'onUpdate' => static::CASCADE),
		);

		if (Craft::hasPackage(CraftPackage::Users))
		{
			$relations['profile'] = array(static::HAS_ONE, 'UserProfileRecord', 'userId');
			$relations['groups']  = array(static::MANY_MANY, 'UserGroupRecord', 'usergroups_users(userId, groupId)');
		}

		$relations['sessions'] = array(static::HAS_MANY, 'SessionRecord', 'userId');

		return $relations;
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('username'), 'unique' => true),
			array('columns' => array('email'), 'unique' => true),
			array('columns' => array('verificationCode')),
			array('columns' => array('uid')),
		);
	}
}