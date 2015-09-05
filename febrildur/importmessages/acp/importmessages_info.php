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
			'version'	=> '1.0.0',
			'modes'		=> array(
				'main'	=> array('title' => 'ACP_IMPORT_MESSAGES', 
						'auth' => 'ext_febrildur/importmessages && acl_a_user', 
						'cat' => array('ACP_CAT_USERS')),
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