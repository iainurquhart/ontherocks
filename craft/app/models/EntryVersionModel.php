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

Craft::requirePackage(CraftPackage::PublishPro);

/**
 *
 */
class EntryVersionModel extends EntryModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$attributes = parent::defineAttributes();

		$attributes['versionId'] = AttributeType::Number;
		$attributes['creatorId'] = AttributeType::Number;
		$attributes['notes'] = AttributeType::String;
		$attributes['dateCreated'] = AttributeType::DateTime;

		return $attributes;
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $attributes
	 * @return EntryVersionModel
	 */
	public static function populateModel($attributes)
	{
		if ($attributes instanceof \CModel)
		{
			$attributes = $attributes->getAttributes();
		}

		// Merge the version and entry data
		$entryData = $attributes['data'];
		$fieldContent = $entryData['fields'];
		$attributes['versionId'] = $attributes['id'];
		$attributes['id'] = $attributes['entryId'];
		unset($attributes['data'], $entryData['fields'], $attributes['entryId']);

		$attributes = array_merge($attributes, $entryData);

		// Initialize the version
		$version = parent::populateModel($attributes);
		$version->setContentIndexedByFieldId($fieldContent);

		return $version;
	}

	/**
	 * Returns the version's creator.
	 *
	 * @return UserModel|null
	 */
	public function getCreator()
	{
		return craft()->users->getUserById($this->creatorId);
	}
}
