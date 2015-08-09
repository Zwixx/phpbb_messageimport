<?php
/**
*
* acp_email [English]
*
* @package language
* @version $Id: email.php 8479 2008-03-29 00:22:48Z naderman $
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

// Email settings
$lang = array_merge($lang, array(
	'ACP_MESSAGEIMPORT'					=> 'Import Messages',
	'ACP_IMPORT_MESSAGES'				=> 'Import Messages',
	'IMPORT_MESSAGES'					=> 'Import Messages',
	'INSTALL_IMPORT_MESSAGES'			=> 'Install Import Messages',
	'INSTALL_IMPORT_MESSAGES_CONFIRM'	=> 'Are you ready to install Import Messages MOD',
	'UPDATE_IMPORT_MESSAGES'			=> 'Update Import Messages MOD',
	'UPDATE_IMPORT_MESSAGES_CONFIRM'	=> 'Are you ready to update the Import Messages MOD',
	'UNINSTALL_IMPORT_MESSAGES'			=> 'Uninstall Import Messages MOD',
	'UNINSTALL_IMPORT_MESSAGES_CONFIRM'	=> 'Are you ready to uninstall the Import Messages MOD?  The module will be removed.',
));

?>