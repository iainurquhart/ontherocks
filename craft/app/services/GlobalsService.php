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
class GlobalsService extends BaseApplicationComponent
{
	private $_allGlobalSetIds;
	private $_editableGlobalSetIds;
	private $_globalSetsById;

	/**
	 * Returns all of the global set IDs.
	 *
	 * @return array
	 */
	public function getAllSetIds()
	{
		if (!isset($this->_allGlobalSetIds))
		{
			$this->_allGlobalSetIds = craft()->db->createCommand()
				->select('id')
				->from('globalsets')
				->queryColumn();
		}

		return $this->_allGlobalSetIds;
	}

	/**
	 * Returns all of the global set IDs that are editable by the current user.
	 *
	 * @return array
	 */
	public function getEditableSetIds()
	{
		if (!isset($this->_editableGlobalSetIds))
		{
			$this->_editableGlobalSetIds = array();
			$allGlobalSetIds = $this->getAllSetIds();

			foreach ($allGlobalSetIds as $globalSetId)
			{
				if (craft()->userSession->checkPermission('editGlobalSet:'.$globalSetId))
				{
					$this->_editableGlobalSetIds[] = $globalSetId;
				}
			}
		}

		return $this->_editableGlobalSetIds;
	}

	/**
	 * Returns all global sets.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllSets($indexBy = null)
	{
		if (!isset($this->_globalSetsById))
		{
			$globalSetRecords = GlobalSetRecord::model()->with('element')->ordered()->findAll();
			$this->_globalSetsById = GlobalSetModel::populateModels($globalSetRecords, 'id');
		}

		if ($indexBy == 'id')
		{
			$globalSets = $this->_globalSetsById;
		}
		else if (!$indexBy)
		{
			$globalSets = array_values($this->_globalSetsById);
		}
		else
		{
			$globalSets = array();
			foreach ($this->_globalSetsById as $globalSet)
			{
				$globalSets[$globalSet->$indexBy] = $globalSet;
			}
		}

		return $globalSets;
	}

	/**
	 * Returns all global sets that are editable by the current user.
	 *
	 * @param string|null $indexBy
	 * @param string|null $localeid
	 * @return array
	 */
	public function getEditableSets($indexBy = null, $localeId = null)
	{
		$editableGlobalSetIds = $this->getEditableSetIds();
		$globalSets = $this->getAllSets('id');
		$editableGlobalSets = array();

		foreach ($editableGlobalSetIds as $globalSetId)
		{
			if (isset($globalSets[$globalSetId]))
			{
				$globalSet = new GlobalSetModel($globalSets[$globalSetId]->getAttributes());
				$globalSet->locale = $localeId;

				if ($indexBy)
				{
					$editableGlobalSets[$globalSet->$indexBy] = $globalSet;
				}
				else
				{
					$editableGlobalSets[] = $globalSet;
				}
			}
		}

		return $editableGlobalSets;
	}

	/**
	 * Returns the total number of global sets.
	 *
	 * @return int
	 */
	public function getTotalSets()
	{
		return count($this->getAllSetIds());
	}

	/**
	 * Returns the total number of global sets that are editable by the current user.
	 *
	 * @return int
	 */
	public function getTotalEditableSets()
	{
		return count($this->getEditableSetIds());
	}

	/**
	 * Returns a global set by its ID.
	 *
	 * @param $globalSetId
	 * @return GlobalSetModel|null
	 */
	public function getSetById($globalSetId)
	{
		if (!isset($this->_globalSetsById) || !array_key_exists($globalSetId, $this->_globalSetsById))
		{
			$globalSetRecord = GlobalSetRecord::model()->findById($globalSetId);

			if ($globalSetRecord)
			{
				$this->_globalSetsById[$globalSetId] = GlobalSetModel::populateModel($globalSetRecord);
			}
			else
			{
				$this->_globalSetsById[$globalSetId] = null;
			}
		}

		return $this->_globalSetsById[$globalSetId];
	}

	/**
	 * Saves a global set.
	 *
	 * @param GlobalSetModel $globalSet
	 * @throws \Exception
	 * @return bool
	 */
	public function saveSet(GlobalSetModel $globalSet)
	{
		$isNewSet = empty($globalSet->id);

		if (!$isNewSet)
		{
			$globalSetRecord = GlobalSetRecord::model()->with('element')->findById($globalSet->id);

			if (!$globalSetRecord)
			{
				throw new Exception(Craft::t('No global set exists with the ID “{id}”', array('id' => $globalSet->id)));
			}

			$oldSet = GlobalSetModel::populateModel($globalSetRecord);
			$elementRecord = $globalSetRecord->element;
		}
		else
		{
			$globalSetRecord = new GlobalSetRecord();

			$elementRecord = new ElementRecord();
			$elementRecord->type = ElementType::GlobalSet;
		}

		$globalSetRecord->name   = $globalSet->name;
		$globalSetRecord->handle = $globalSet->handle;
		$globalSetRecord->validate();
		$globalSet->addErrors($globalSetRecord->getErrors());

		$elementRecord->enabled = $globalSet->enabled;
		$elementRecord->validate();
		$globalSet->addErrors($elementRecord->getErrors());

		if (!$globalSet->hasErrors())
		{
			$transaction = craft()->db->beginTransaction();
			try
			{
				if (!$isNewSet && $oldSet->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldSet->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $globalSet->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout, false);

				// Update the set record/model with the new layout ID
				$globalSet->fieldLayoutId = $fieldLayout->id;
				$globalSetRecord->fieldLayoutId = $fieldLayout->id;

				// Save the element record first
				$elementRecord->save(false);

				// Now that we have an element ID, save it on the other stuff
				if (!$globalSet->id)
				{
					$globalSet->id = $elementRecord->id;
					$globalSetRecord->id = $globalSet->id;
				}

				$globalSetRecord->save(false);

				$transaction->commit();
			}
			catch (\Exception $e)
			{
				$transaction->rollBack();
				throw $e;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves a global set's content
	 *
	 * @param GlobalSetModel $globalSet
	 * @return bool
	 */
	public function saveContent(GlobalSetModel $globalSet)
	{
		return craft()->elements->saveElementContent($globalSet, $globalSet->getFieldLayout(), $globalSet->locale);
	}
}
