<?php

namespace febrildur\importmessages\migrations\v100;

class install_v100 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['messageimport_version']);
	}

	public function update_data()
	{
		return array(
			array('config.add', array('messageimport_version', '1.0.0')),
			array('module.add', array('acp', 'ACP_MESSAGES','ACP_MESSAGEIMPORT')),
			array('module.add', array(
				'acp',
				'ACP_MESSAGEIMPORT',
				array(
					'module_basename'	=> '\febrildur\importmessages\acp\importmessages_module',
					'modes'				=> array('main'),
				),
			)),
		);
	}
}