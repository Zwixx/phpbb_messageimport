<?php

namespace febrildur\importmessages\migrations\v100;

class install_v100 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['messageimport_version']) && version_compare($this->config['messageimport_version'], '1.0.0', '>=');
	}
	static public function depends_on()
	{
		return array();
	}
	public function update_data()
	{
		return array(
			array('config.add', array('messageimport_version', '1.0.0')),
			array('module.add', array(
				'acp',
				'ACP_CAT_POSTING',
				array(
					'module_basename'	=> '\febrildur\importmessages\acp\importmessages_module',
					
					'modes'				=> array('main'),
				),
			)),
		);
	}
	public function revert_data()
	{
		return array(
			array('config.remove', array('messageimport_version')),
			array('module.remove', array(
				'acp',
				'ACP_CAT_POSTING',
				array(
					'module_basename'	=> '\febrildur\importmessages\acp\importmessages_module',
				),
			)),
		);
	}
}