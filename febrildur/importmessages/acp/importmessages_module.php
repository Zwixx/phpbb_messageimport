<?php

namespace febrildur\importmessages\acp;

class clock_monitor
{
	var $start_time;
	var $max_time;
	var $cur_time;
	var $count;
	var $timeleft;
	
	function __construct($duration)
	{
		$this->start_time = time();
		$this->max_time = (is_object($duration))
						? $duration->get_max_time()
						: $this->start_time + $duration -2;
		
		$count = 0;
	}
	
	function time_for_one_more ()
	{
		++$this->count;
		$this->cur_time = time();
		$this->timeleft = ($this->cur_time + ($this->cur_time-$this->start_time)/$this->count < $this->max_time);
		return $this->timeleft;
	}
	
	function get_max_time ()
	{
		return $this->max_time;
	}
	
	function get_count ()
	{
		return $this->count;
	}
	function get_duration()
	{
		return $this->cur_time - $this->start_time;
	}
	
	function is_started ()
	{
		return ($this->count > 0);
	}
	function is_completed ()
	{
		return $this->timeleft;
	}
}

class importmessages_module
{
	var $dateformat;
	var $topic_list;
	var $topic_num;
	var $total_valid_msg;
	var $message_count;
	var $more_missing_date;
	var $lost_topic;
	var $anonymous;
	
	var $indexing_initialized = false;
	var $indexing_failed      = false;
	
	var $forum_ary   = array();
	var $user_ary    = array();
	var $poster_ary  = array();
	var $user_id_ary = array();

	var $errors      = array();
	
	
	function main($id, $mode)
	{
		global $config, $db, $user, $auth, $template, $cache;
		global $phpbb_root_path, $phpbb_admin_path, $phpEx;

		
		include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
		include($phpbb_root_path . 'includes/message_parser.' . $phpEx);
		
		$user->add_lang_ext('febrildur/importmessages', 'acp_importmessages');

		$this->tpl_name = 'acp_importmessages';
		$this->page_title = 'ACP_IMPORT_MESSAGES';
		add_form_key('message_import');
				
		$filename = request_var('filename', '');
		
		$range = request_var('topicrange', '');
		$this->allow_bbcode    = request_var('allowbbcode',   0);
		$this->allow_smilies   = request_var('allowsmilies',  0);
		$this->allow_magic_url = request_var('allowmagicurl', 0);
		$this->allow_sig       = request_var('allowsig',      0);
		
		$submit = request_var('submit', false);
		$preview = request_var('preview', false);
		
		$is_error     = false;
		while ($submit | $preview) // will never loop (always use break to exit)
		{
			$max_duration = ini_get ('max_execution_time');
			$load_timing = new clock_monitor ($max_duration);
			
			$is_error = true;
			//-- Topic range syntax check
			if (!preg_match('/^\s*((\d+)\s*(-\s*(\d*)\s*)?)?$/',$range, $matches))
			{
				$this->errors[] = $user->lang['BAD_RANGE'];
				break;
			}
				
			if ($filename == '')
			{
				$this->errors[] = $user->lang['MISSING_FILENAME'];
				break;
			}
			$full_filename = $phpbb_root_path . 'store/' . $filename;
			if (!is_readable($full_filename))
			{
				$this->errors[] = sprintf($user->lang['MISSING_FILE'], $filename);
				break;
			}
			
			//-- Parse the XML file
			$xml_data = simplexml_load_file($full_filename);
			if ($xml_data === false)
			{
				$this->errors[] = $user->lang['INVALID_XML'];
				break;
			}
			$this->dateformat = $xml_data['date-format'];
			$this->topic_list = $xml_data->xpath('topic');
			if (sizeof($this->topic_list) == 0)
			{
				$this->errors[] = $user->lang['NOTHING_TO_IMPORT'];
				break;
			}
			
			//-- XML file loading is completed
			$load_timing->time_for_one_more();
			
			//-- Compute the topic range
			$first_topic = 1;
			$last_topic = sizeof($this->topic_list);
			if (sizeof($matches) > 1)
			{
				$first_topic = (int)$matches[2];
				if ($matches[3] == '')
				{
					$last_topic = $first_topic;
				}
				else if ($matches[4] != '')
				{
					$last_topic = (int)$matches[4];
				}
			}
			--$first_topic;  //-- 1st element is at 0
			unset($matches);
			if ($last_topic > sizeof($this->topic_list))
			{
				$last_topic = sizeof($this->topic_list);
			}
			if ($first_topic > $last_topic)
			{
				$this->errors[] = $user->lang['EMPTY_RANGE'];
				break;
			}
			$end_parsing_time = time();

			$parse_timing = new clock_monitor ($load_timing);

			//-- Analyze the XML data
			$this->topic_num = 0;
			$this->lost_topic = 0;
			$this->more_missing_date = -10;
			for ($this->topic_num = $first_topic; $this->topic_num < $last_topic; ++$this->topic_num)
			{
				$this->parse_cur_topic();
				if (!$parse_timing->time_for_one_more())
				{
					break;
				}
			}
			$parsed_topic = $last_topic - $first_topic;
			
			//-- Search for users
			if ($this->poster_ary)
			{
				$user_ary = array();
				$sql = 'SELECT user_id, username, username_clean, user_lastpost_time, user_colour
					FROM ' . USERS_TABLE . '
					WHERE ' . $db->sql_in_set('username_clean', $this->poster_ary);
				$result = $db->sql_query($sql);
				
				while ($row = $db->sql_fetchrow($result))
				{
					$this->user_ary[$row['username_clean']] = array(
							'user_id'       => (int)$row['user_id'], 
							'username'      => $row['username'], 
							'colour'        => $row['user_colour'],
							'lastpost_time' => (int)$row['user_lastpost_time'],
							'post'          => 0,
							'time_updated'  => false
						);
				}
				unset($row);
				$db->sql_freeresult($result);
			
				$unknown_user_ary = array();
				foreach ($this->poster_ary as $poster => $clean_name)
				{
					if (isset ($this->user_ary[$clean_name]))
					{
						$this->poster_ary[$poster] = &$this->user_ary[$clean_name];
					}
					else
					{
						$clean_name = false;
						$unknown_user_ary[] = $poster;
					}
				}
				unset($poster, $clean_name);
			}
			
			if ($unknown_user_ary)
			{
				$this->errors[] = $user->lang['UNKNOWN_USER_WARNING'] . implode(', ', $unknown_user_ary);
			}
			unset($unknown_user_ary);
			
			// if submit add the messages in the forums
			if ($submit)
			{
				$this->anonymous = array('user_id'  => ANONYMOUS,
										 'username' => '',
										 'colour'   => '');
										 
				$import_timing = new clock_monitor ($parse_timing);
				for ($this->topic_num = $first_topic; $this->topic_num < $last_topic; ++$this->topic_num)
				{
					if (isset($this->topic_list[$this->topic_num]['valid']))
					{
						$this->add_cur_topic ();
						if (!$import_timing->time_for_one_more())
						{
							break;
						}
					}
				}
			}
			
			// All done
			break;
		}
		
		$diag = "";
		//-- Build the operation statistics
		if ($submit | $preview)
		{
			$stat = array();
			$completed = true;
			if ($load_timing->is_started())
			{
				$stat[] = sprintf ($user->lang['LOAD_STAT'], $load_timing->get_duration());
			}
			if (isset($parse_timing) && $parse_timing->is_started())
			{
				$stat[] = sprintf ($user->lang['PARSE_STAT'], $parse_timing->get_duration(), $parse_timing->get_count(), $this->total_valid_msg);
				$completed &= $parse_timing->is_completed();
			}
			if ($this->lost_topic > 0)
			{
				$stat[] = sprintf ($user->lang['PARSE_ERROR'], $this->lost_topic);
				$is_error = true;
			}
			if (isset($import_timing) && $import_timing->is_started())
			{
				$stat[] = sprintf ($user->lang['IMPORT_STAT'], $import_timing->get_duration(), $import_timing->get_count());
				$completed &= $import_timing->is_completed();
			}
			$is_error &= !$completed;
			$stat[] = sprintf ($user->lang[($completed) ? 'AVAILABLE_TIME' : 'OUT_OF_TIME'], $max_duration, $first_topic+1, $this->topic_num+1);
			$diag = implode(', ', $stat);
			if ($this->errors)
				$diag = implode('<br />', $this->errors) . '<br />' . $diag;
		}

		//-- Fill the form
		$template->assign_vars(array(
				'FILENAME'		 => $filename,
				'DATEFORMAT'     => $this->dateformat,
				'TOPIC_RANGE'    => $range,
				'ALLOW_BBCODE'   => $this->allow_bbcode,
				'ALLOW_SMILIES'  => $this->allow_smilies,
				'ALLOW_MAGIC_URL'=> $this->allow_magic_url,
				'ALLOW_SIG'      => $this->allow_sig,
				
				'S_DIAG'		 => $submit | $preview,
				'S_ERROR'		 => $is_error,
				'DIAG'			 => $diag,
		));
	}
	
	
	//-- Parse the topic whose number is given by $this->topic_num
	function parse_cur_topic ()		
	{
		global $db, $user;
		
		$topic_elm = $this->topic_list[$this->topic_num];
		$message_elm = $topic_elm->xpath('message');
		$msg_count = sizeof($message_elm);
		$forum_has_title = isset($topic_elm['title']) && ($topic_elm['title'] != '');
		if (!isset($topic_elm['forum-name']))
		{
			$this->errors[] = sprintf($user->lang['TOPIC_WITHOUT_FORUM'], $topic_num+1, $msg_count);
			++$this->lost_topic;
		}
		else if ($msg_count == 0)
		{
			$this->errors[] = sprintf($user->lang['NO_MSG_IN_TOPIC'], $topic_num+1);
			++$this->lost_topic;
		}
		else if (!$forum_has_title && (!isset($message_elm[0]['title']) || ($message_elm[0]['title'] == '')))
		{
			$this->errors[] = sprintf($user->lang['TOPIC_WITHOUT_TITLE'], $topic_num+1, $msg_count);
			++$this->lost_topic;
		}
		else 
		{
			if ($forum_has_title)
			{
				$topic_title = $topic_elm['title'];
			}
			else
			{
				$topic_title = $message_elm[0]['title'];
				$topic_elm['title'] = $topic_title;
			}
			if (isset($message_elm[0]['title']) && ($message_elm[0]['title'] != '') && ($message_elm[0]['title'] != $topic_title))
			{
				$this->errors[] = sprintf($user->lang['MSG_TITLE_OVERWRIT'], $message_elm[0]['title'], $topic_title);
				$message_elm[0]['title'] = $topic_title;
			}
			if (strlen($topic_title) > 255)
			{
				$this->errors[] = sprintf($user->lang['MSG_TITLE_TRUNCAT'], $topic_title);
				$message_elm[0]['title'] = substr($topic_title, 0, 255);
			}
			
			$forum_name = (string)$topic_elm['forum-name'];

			if (!isset($this->forum_ary[$forum_name]))
			{
				$sql = 'SELECT forum_id, forum_last_post_time, enable_indexing
							FROM ' . FORUMS_TABLE . "
							WHERE forum_name = '". $db->sql_escape($forum_name) . "'";
				$result = $db->sql_query($sql);
				$this->forum_ary[$forum_name] = (!$result) ? false : $db->sql_fetchrow($result);
			}
			
			if (!$this->forum_ary[$forum_name])
			{
				$this->errors[] = sprintf($user->lang['UNKNOWN_FORUM'], $this->topic_num+1, $forum_name, $msg_count);
				++$this->lost_topic;
			}
			else
			{
				foreach ($message_elm as $cur_msg)
				{
					$this->parse_message ($cur_msg, $topic_elm);
				}
				$topic_elm['valid'] = true;
				$this->total_valid_msg += $msg_count;
			}
		}
	}


	//-- Verify and convert the given message data also given the topic data
	function parse_message (&$msg_data, $topic_elm)
	{
		++$this->message_count;
		if (! isset ($msg_data['by']))
		{
			$message_elm['by'] = '';
		}
		else if ($msg_data['by'])
		{
			$username = (string)$msg_data['by'];
			if (! isset($this->poster_ary[$username]))
			{
				$this->poster_ary[$username] = utf8_clean_string($username);
			}
		}
		$cur_time = time();
		if (! isset ($msg_data['posted']))
		{
			$this->warning_no_date();
			$msg_data['posted'] = $cur_time;
		}
		else 
		{
			if ($this->dateformat == '')
			{
				$msg_data['date'] = trim($msg_data['posted']);
				if (!preg_match('/^\d+$/',$msg_data['date']))
				{
					$this->warning_wrong_date($msg_data['posted']);
					$msg_data['date'] = $cur_time;
				}
				else
					$msg_data['date'] = (int)$msg_data['posted'];
			}
			else
			{
				$date = strptime($msg_data['posted'], $this->dateformat);
				if ($date === false)
				{
					$this->warning_wrong_date($msg_data['posted']);
					$msg_data['date'] = $cur_time;
				}
				else
				{
					$msg_data['date'] = gmmktime(
												$date['tm_hour'], 
					                           	$date['tm_min'], 
												$date['tm_sec'], 
												$date['tm_mon']+1, 
												$date['tm_mday'], 
												$date['tm_year']+1900
											);
				}
			}
			if ($msg_data['date'] > $cur_time)
			{
				$this->warning_wrong_date($msg_data['posted']);
				$msg_data['date'] = $cur_time;
			}
		}
		if (!isset($msg_data['ip'])
			|| !preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $msg_data['ip'], $matches) 
			|| ($matches[1] > 255)
			|| ($matches[2] > 255)
			|| ($matches[3] > 255)
			|| ($matches[4] > 255))
		{
			$msg_data['ip'] = '';
		}
		if (!isset($msg_data['title']) || ($msg_data['title'] == ''))
		{
			$msg_data['title'] = $topic_elm['title'];
		}
		if (!isset($msg_data['bbcode']))
		{
			$msg_data['bbcode'] = $this->allow_bbcode;
		}
		if (!isset($msg_data['smiley']))
		{
			$msg_data['smiley'] = $this->allow_smilies;
		}
		if (!isset($msg_data['magic-url']))
		{
			$msg_data['magic-url'] = $this->allow_magic_url;
		}
		if (!isset($msg_data['signature']))
		{
			$msg_data['signature'] = $this->allow_sig;
		}
		
		$message_parser = new \parse_message();
		$message_parser->message = utf8_normalize_nfc((string)$msg_data[0]);
		$message_parser->parse(
							$msg_data['bbcode'], 
							$msg_data['magic-url'], 
							$msg_data['smiley'], 
							false,            // img_status, 
							false,            // flash_status, 
							true,             // quote_status, 
							$msg_data['magic-url']
						);
		if (sizeof($message_parser->warn_msg) > 0)
		{
			$this->error[] = implode('<br />', $message_parser->warn_msg);
			$message_parser->warn_msg = array();
		}
		$msg_data['bbcode_bitfield'] = $message_parser->bbcode_bitfield;
		$msg_data['bbcode_uid'] = $message_parser->bbcode_uid;
		$msg_data[0] = $message_parser->message;
	}
	
	//-- Raise a date error except if too many errors have been reported
	function warning_no_date ()
	{
		global $user;
		
		if ($this->more_missing_date <= 0)
		{
			$this->errors[] = ($this->more_missing_date < 0) 
								? sprintf($user->lang['MISSING_OR_UNK_DATE'], 
										$this->message_count, 
										$this->topic_num+1)
								: $user->lang['MORE_MISSING_DATE'];
		}
		++$this->more_missing_date;
	}

	function warning_wrong_date ($date)
	{
		global $user;
		
		if ($this->more_missing_date <= 0)
		{
			$this->errors[] = ($this->more_missing_date < 0)
								 ? sprintf($user->lang['WRONG_DATE'], 
										$this->message_count, 
										$this->topic_num+1,
										$date,
										$this->dateformat)
								: $user->lang['MORE_MISSING_DATE'];
		}
		++$this->more_missing_date;
	}
	
	
	//-- Import the topic whose number is $this->topic_num
	function add_cur_topic ()
	{
		global $db, $auth, $user, $config, $phpbb_root_path, $phpEx;
		
		$topic_elm = $this->topic_list[$this->topic_num];

		$forum_name  = (string)$topic_elm['forum-name'];
		$forum_data  = &$this->forum_ary[$forum_name];
		$forum_id    = $forum_data['forum_id'];
		$post_count  = ($auth->acl_get('f_postcount', $forum_id)) ? 1 : 0;
		
		// Get first post data
		$forum_time  = $topic_elm->message[0]['date'];
		$poster_name = (string)$topic_elm->message[0]['by'];
		$poster_data = &$this->poster_ary[$poster_name];
		if (($poster_data === false) || !is_numeric($poster_data['user_id']))
		{
			$poster_data = array('user_id'  => ANONYMOUS,
					 'username' => '',
					 'colour'   => '');
			$poster_name = '';
		}
		
		$db->sql_transaction('begin');
		// Create the topic
		$sql_data = array(
			'topic_poster'				=> $poster_data['user_id'],
			'topic_time'				=> $forum_time,
			'topic_last_view_time'		=> $forum_time,
			'topic_last_post_time'      => $forum_time,
			'forum_id'					=> $forum_id,
//			'icon_id'					=> 0,
//			'topic_approved'			=> 1,
			'topic_title'				=> (string)$topic_elm['title'],
			'topic_first_poster_name'	=> $poster_name,
			'topic_first_poster_colour'	=> $poster_data['colour'],
			'topic_type'				=> POST_NORMAL,
//			'topic_time_limit'			=> 0,
//			'topic_attachment'			=> 0,
			'topic_visibility'			=> 1
		);

		$sql = 'INSERT INTO ' . TOPICS_TABLE . ' ' .
			$db->sql_build_array('INSERT', $sql_data);
		$db->sql_query($sql);
		$topic_id = $db->sql_nextid();
		unset($sql_data);

		// Add the topic posts
		foreach ($topic_elm as $msg_index => $msg_data)
		{
			$msg_date    = $msg_data['date'];
			$poster_name = (string)$msg_data['by'];
			$poster_data = &$this->poster_ary[$poster_name];
			if (($poster_data === false) || !is_numeric($poster_data['user_id']))
			{
				$poster_data = array('user_id'  => ANONYMOUS,
						 'username' => '',
						 'colour'   => '');
				$poster_name = '';
			}
			$sql_data = array(
				'topic_id'			=> $topic_id,
				'forum_id'			=> $forum_id,
				'poster_id'			=> $poster_data['user_id'],
//				'icon_id'			=> 0,
				'poster_ip'			=> (string)$msg_data['ip'],
				'post_time'			=> $msg_date,
//				'post_approved'		=> 1,
				'enable_bbcode'		=> $msg_data['bbcode'],
				'enable_smilies'	=> $msg_data['smiley'],
				'enable_magic_url'	=> $msg_data['magic-url'],
				'enable_sig'		=> $msg_data['signature'],
				'post_username'		=> $poster_name,
				'post_subject'		=> (string)$msg_data['title'],
				'post_text'			=> (string)$msg_data[0],
				'post_checksum'		=> md5((string)$msg_data[0]),
//				'post_attachment'	=> 0,
				'bbcode_bitfield'	=> (string)$msg_data['bbcode_bitfield'],
				'bbcode_uid'		=> (string)$msg_data['bbcode_uid'],
				'post_postcount'	=> $post_count,
				'post_edit_locked'	=> 0,
				'post_visibility'	=> 1
			);
			
			$sql = 'INSERT INTO ' . POSTS_TABLE . ' '
					. $db->sql_build_array('INSERT', $sql_data);
			$db->sql_query($sql);
			$msg_id = $db->sql_nextid();
			
			if ($msg_index == 0)
			{
				$first_msg_id = $msg_id;
			}
			
			// Update user data to take into account this post
			if ($poster_data['user_id'] != ANONYMOUS)
			{
				if ($post_count)
				{
					if (isset($poster_data['post']))
					{
						$poster_data['post'] = 1;
					}
					else
					{
						++$poster_data['post'];
					}
				}
				if ($msg_date > $poster_data['lastpost_time'])
				{
					$poster_data['lastpost_time'] = $msg_date;
					$poster_data['time_updated']  = true;
				}
			}
			
			// If the forum indexing is enable, index this message
			if ($forum_data['enable_indexing'] && !$this->indexing_failed)
			{
				if (!$this->indexing_initialized)
				{
					// Select the search method and do some additional checks to ensure it can actually be utilised
					$search_type = basename($config['search_type']);
			
					if (!file_exists($phpbb_root_path . 'includes/search/' . $search_type . '.' . $phpEx))
					{
						$this->errors[] = $user->lang['NO_SUCH_SEARCH_MODULE'] . ' (' .$phpbb_root_path . 'includes/search/' . $search_type . '.' . $phpEx .')';
						$this->indexing_failed = true;
					}
					else
					{
						if (!class_exists($search_type))
						{
							include("{$phpbb_root_path}includes/search/$search_type.$phpEx");
						}
				
						$error = false;
						$this->search = new $search_type($error);
				
						if ($error)
						{
							$this->errors[] = $error;
							$this->indexing_failed = true;
						}
						else
						{
							$this->indexing_initialized = true;
						}
					}
				}
		
				if ($this->indexing_initialized)
				{
					$this->search->index(
							($msg_index == 0) ? 'post' : 'reply', 
							$msg_id, 
							$msg_data[0], 
							$msg_data['title'], 
							$poster_data['user_id'], 
							$forum_id
						);
				}
			}
		}
		
		// Store last post data in topic
		$post_count = sizeof($topic_elm);

		$sql_data = array(
			'topic_first_post_id'		=> $first_msg_id,
			'topic_last_post_id'		=> $msg_id,
			'topic_last_post_time'		=> $msg_date,
			'topic_last_view_time'      => $msg_date,
			'topic_last_poster_id'		=> $poster_data['user_id'],
			'topic_last_poster_name'	=> $poster_name,
			'topic_last_poster_colour'	=> (string)$poster_data['colour'],
			'topic_last_post_subject'	=> (string)$msg_data['title'],
			'topic_posts_approved'             => $post_count,
			// 'topic_replies_real'        => $post_count,
			);
		$sql = 'UPDATE ' . TOPICS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_data) . '
			WHERE topic_id = ' . $topic_id;
		$db->sql_query($sql);
		
		// Update users stat
		$sql_data = array();
		foreach ($this->user_ary as &$user_data)
		{
			if ($user_data['post'] > 0)
			{
				$sql_data[] = 'user_posts = user_posts + ' . $user_data['post'];
				$user_data['post'] = 0;
			}
			if ($user_data['time_updated'])
			{
				$sql_data[] = 'user_lastpost_time = greatest(user_lastpost_time, ' . $user_data['lastpost_time'] . ')';
				$user_data['time_updated'] = false;
			}
			if ($sql_data)
			{
				$sql = 'UPDATE ' . USERS_TABLE . 
						' SET ' . implode(', ', $sql_data) .
						' WHERE user_id = ' . $user_data['user_id'];
				$db->sql_query($sql);
				$sql_data = array();
			}
		}
		
		// Update forum stat
		// $sql_data[] = 'forum_posts = forum_posts + ' . $post_count;
		// $sql_data[] = 'forum_topics_real = forum_topics_real + 1';
		// $sql_data[] = 'forum_topics = forum_topics + 1';
		if ($forum_data['forum_last_post_time'] < $msg_date)
		{
			$forum_data['forum_last_post_time'] = $msg_date;
			$sql_data[] = 'forum_last_post_id = ' . $msg_id;
			$sql_data[] = "forum_last_post_subject = '" . $db->sql_escape($msg_data['title']) . "'";
			$sql_data[] = 'forum_last_post_time = ' . $msg_date;
			$sql_data[] = 'forum_last_poster_id = ' . $poster_data['user_id'];
			$sql_data[] = "forum_last_poster_name = '" . $db->sql_escape($poster_name) . "'";
			$sql_data[] = "forum_last_poster_colour = '" . $db->sql_escape($poster_data['colour']) . "'";
		}
		$sql = 'UPDATE ' . FORUMS_TABLE . 
						' SET ' . implode(', ', $sql_data) . 
						' WHERE forum_id = ' . $forum_id;
		// $db->sql_query($sql);
		
		// Update global topic and post count
		set_config_count('num_topics', 1, true);
		set_config_count('num_posts', $post_count, true);

		sync('forum', 'forum_id', Array($forum_id), true, true);

		$db->sql_transaction('commit');
	}
}