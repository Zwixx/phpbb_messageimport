<?php

namespace febrildur\importmessages\migrations\v100;

class install_v100 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['messageimport_version']);
	}

	public function update_schema()
	{
		return [
				'add_tables'		=> [
						$this->table_prefix . 'posts_convert'	=> [
								'COLUMNS'		=>	[
										'oldpostid'		=> ['UINT', null, ''],
										'newpostid'		=> ['UINT', null, ''],
								],
								'PRIMARY_KEY'	=> 'oldpostid',
						],
				],
		];
	}
	public function update_data()
	{
		return array(
			array('config.add', array('messageimport_version', '1.0.0')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DATABASE',
				array(
					'module_basename'	=> '\febrildur\importmessages\acp\importmessages_module',
					'modes'				=> array('main'),
				),
			)),
		);
	}
}