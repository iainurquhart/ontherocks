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

Craft::requirePackage(CraftPackage::Rebrand);

/**
 * Handles email message tasks.
 */
class EmailMessagesController extends BaseController
{
	/**
	 * Saves an email message
	 */
	public function actionSaveMessage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$message = new RebrandEmailModel();
		$message->key = craft()->request->getRequiredPost('key');
		$message->subject = craft()->request->getRequiredPost('subject');
		$message->body = craft()->request->getRequiredPost('body');

		if (Craft::hasPackage(CraftPackage::Localize))
		{
			$message->locale = craft()->request->getPost('locale');
		}
		else
		{
			$message->locale = craft()->language;
		}

		if (craft()->emailMessages->saveMessage($message))
		{
			$this->returnJson(array('success' => true));
		}
		else
		{
			$this->returnErrorJson(Craft::t('There was a problem saving your message.'));
		}
	}
}
