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
class AssetFolderModel extends BaseModel
{

	/**
	 * @var array
	 */
	private $_children = null;

	/**
	 * Use the folder name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return $this->name;
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'       => AttributeType::Number,
			'parentId' => AttributeType::Number,
			'sourceId' => AttributeType::Number,
			'name'     => AttributeType::String,
			'fullPath' => AttributeType::String,
		);
	}

	/**
	 * @return AssetSourceModel|null
	 */
	public function getSource()
	{
		return craft()->assetSources->getSourceById($this->sourceId);
	}

	/**
	 * Get this folder's children.
	 *
	 * @return array|null
	 */
	public function getChildren()
	{
		if (is_null($this->_children))
		{
			$this->_children = craft()->assets->findFolders(array('parentId' => $this->id));
		}

		return $this->_children;
	}

	/**
	 * Add a child folder manually.
	 *
	 * @param AssetFolderModel $folder
	 */
	public function addChild(AssetFolderModel $folder)
	{
		if (is_null($this->_children))
		{
			$this->_children = array();
		}
		$this->_children[] = $folder;
	}
}
