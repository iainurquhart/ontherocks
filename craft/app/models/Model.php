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
class Model extends BaseModel
{
	private $_attributeDefs;

	/**
	 * Constructor
	 *
	 * @param array $attributeDefs
	 */
	function __construct($attributeDefs)
	{
		$this->_attributeDefs = $attributeDefs;
		parent::__construct();
	}

	/**
	 * Defines this model's attributeDefs.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return $this->_attributeDefs;
	}
}
