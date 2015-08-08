<?php
/**
*
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
namespace febrildur\importmessages\acp;

/**
* @package module_install
*/
class importmessages_info
{
	function module()
	{
		return array(
			'filename'	=> 'febrildur\importmessages\acp\importmessages_module',
			'title'		=> 'IMPORT_MESSAGES',
			'version'	=> '1.0.2',
			'modes'		=> array(
				'import'		=> array('title' => 'ACP_IMPORT_MESSAGES', 'auth' => 'acl_a_user', 'cat' => array('ACP_CAT_USERS')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>