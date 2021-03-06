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
 * EmailSettingsModel class.
 * It is used by the 'saveEmail' action of 'settingsController'.
 */
class EmailSettingsModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'protocol'                => AttributeType::String,
			'host'                    => AttributeType::String,
			'port'                    => AttributeType::String,
			'smtpAuth'                => AttributeType::String,
			'username'                => AttributeType::String,
			'password'                => AttributeType::String,
			'smtpKeepAlive'           => AttributeType::Bool,
			'smtpSecureTransportType' => AttributeType::String,
			'timeout'                 => AttributeType::String,
			'emailAddress'            => AttributeType::Email,
			'senderName'              => AttributeType::String,
			'testEmailAddress'        => AttributeType::Email,
		);
	}

	/**
	 * Declares the validation rules.
	 *
	 * @return array of validation rules.
	 */
	public function rules()
	{
		$rules[] = array('protocol, emailAddress, senderName', 'required');

		switch ($this->protocol)
		{
			case EmailerType::Smtp:
			{
				if ($this->smtpAuth)
				{
					$rules[] = array('username, password', 'required');
				}

				$rules[] = array('port, host, timeout', 'required');
				break;
			}

			case EmailerType::Gmail:
			{
				$rules[] = array('username, password, timeout', 'required');
				$rules[] = array('username', 'email');
				break;
			}

			case EmailerType::Pop:
			{
				$rules[] = array('port, host, username, password, timeout', 'required');
				break;
			}

			case EmailerType::Php:
			case EmailerType::Sendmail:
			{
				break;
			}
		}

		return $rules;
	}
}
