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
 * TODO: create save function which calls parent::save and then updates the meta data table (keywords, author, etc)
 */
class AssetFileRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'assetfiles';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'filename'		=> array(AttributeType::String, 'required' => true),
			'kind'			=> array(AttributeType::String, 'maxLength' => 10, 'column' => ColumnType::Char),
			'width'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'height'		=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::SmallInt),
			'size'			=> array(AttributeType::Number, 'min' => 0, 'column' => ColumnType::Int),
			'dateModified'	=> AttributeType::DateTime
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element' => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'source'  => array(static::BELONGS_TO, 'AssetSourceRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'folder'  => array(static::BELONGS_TO, 'AssetFolderRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'content' => array(static::HAS_ONE, 'AssetContentRecord', 'fileId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('filename', 'folderId'), 'unique' => true),
		);
	}
}
