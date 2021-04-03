<?php

namespace febrildur\importmessages\controller;

use Symfony\Component\DependencyInjection\Container;

class main {
	/* @var string phpBB root path */
	protected $root_path;

	/* @var string phpEx */
	protected $php_ext;

	/* @var Container */
	protected $phpbb_container;

	/* @var \phpbb\extension\manager */
	protected $phpbb_extension_manager;

	/* @var \phpbb\path_helper */
	protected $phpbb_path_helper;

	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\log\log_interface */
	protected $log;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\auth\auth */
	protected $auth;

	/* @var \phpbb\request\request_interface */
	protected $request;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var \phpbb\language\language */
	protected $language;

	/**
	 * Constructor
	 *
	 * @param string $root_path
	 * @param string $php_ext
	 * @param Container $phpbb_container
	 * @param \phpbb\extension\manager $phpbb_extension_manager
	 * @param \phpbb\path_helper $phpbb_path_helper
	 * @param \phpbb\db\driver\driver_interfacer $db
	 * @param \phpbb\config\config $config
	 * @param \phpbb\log\log_interface $log
	 * @param \phpbb\controller\helper $helper
	 * @param \phpbb\auth\auth $auth
	 * @param \phpbb\request\request_interface $request
	 * @param \phpbb\template\template $template
	 * @param \phpbb\user $user
	 * @param \phpbb\language\language $language
	 */
	public function __construct($root_path, $php_ext, Container $phpbb_container, \phpbb\extension\manager $phpbb_extension_manager, \phpbb\path_helper $phpbb_path_helper, \phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, \phpbb\log\log_interface $log, \phpbb\controller\helper $helper, \phpbb\auth\auth $auth, \phpbb\request\request_interface $request, \phpbb\template\template $template, \phpbb\user $user, \phpbb\language\language $language) {
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->phpbb_container = $phpbb_container;
		$this->phpbb_extension_manager = $phpbb_extension_manager;
		$this->phpbb_path_helper = $phpbb_path_helper;
		$this->db = $db;
		$this->config = $config;
		$this->phpbb_log = $log;
		$this->helper = $helper;
		$this->auth = $auth;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->language = $language;
	}

	/**
	 * redirecttoid controller for route /f1webtip/{name}
	 *
	 * @param string $name
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function handle($id) {
		if (is_int ( $id )) {
			$sql = "SELECT max(newpostid) newpostid
    			FROM phpbb_posts_convert
    			WHERE oldpostid = $id";
			echo $sql;
			$result = $db->sql_query_limit ( $sql, 1 );

			while ( $row = $db->sql_fetchrow ( $result ) ) {
				$redirect = append_sid ( "{$this->root_path}viewtopic.$phpEx", 't=' . $row ["newpostid"] );
				redirect ( $redirect );
			}

			$db->sql_freeresult ( $result );

			return "Topic-ID wurde nicht gefunden";
		} else {
			return "Topic-ID wurde nicht gefunden";
		}
	}
}
