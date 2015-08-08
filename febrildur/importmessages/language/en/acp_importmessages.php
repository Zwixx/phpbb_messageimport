<?php
/**
*
* acp_database [English]
*
* @package language
* @copyright (c) 2010 Giles314
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

// Database Backup/Restore
$lang = array_merge($lang, array(
	'TITLE'		 				=> 'Message Import',
	'EXPLAIN'					=> 'Import messages from XML files. Files to be imported have to be in the \'store\' directory. '
									. 'When importing the necessary topics are created, but the forums are not. ' 
									. 'Therefore the forums must exist before running the import. '
									. 'The imported topics are never merged with existing topic. '
									. 'If a topic is imported twice it will be added twice in the forum. ',
	'ACP_IMPORT_EXPLAIN'		=>	'Import messages from XML files.',

	'FILENAME_EXPLAIN'			=> 'Name of the XML file to import stored in \'store\' directory. The file format must conform to ' .
								'<a href="../includes/acp/import_messages.xsd" title="XML schema">import_messages.xsd</a> or to this ' .
								'<a href="../includes/acp/import_messages.xsd.html" title="graphical presentation of the XML schema">graph</a>.',
	'TOPIC_RANGE'				=> 'Topic range to import (inclusive)',
	'TOPIC_RANGE_EXPLAIN'		=> 'Topics are numbered in the order they are defined in the XML file starting at 1. ' .
								'A dash between 2 numbers allows to define a range of topics. ' .
									'The second number may be omitted to represent up to the last topic. ' .
									'If only one number is provided (no dash) only this topic is imported. '.
									'If this field is left empty, all topics are imported (same as \'1-\'). ',
	'ALLOW_BBCODE'				=> 'Default for BBCODE conversion',
	'ALLOW_BBCODE_EXPLAIN'		=> 'This parameter defines whether BBCODE conversion must be performed '
									. 'when no attribute \'bbcode\' is provided for a message',
	'ALLOW_SMILIES'			 	=> 'Default for Smiley conversion',
	'ALLOW_SMILIES_EXPLAIN'		=> 'This parameter defines whether Smiley conversion must be performed '
									. 'when no attribute \'smiley\' is provided for a message',
	'ALLOW_MAGIC_URL'			=> 'Default for URL conversion',
	'ALLOW_MAGIC_URL_EXPLAIN'	=> 'This parameter defines whether URL conversion must be performed '
									. 'when no attribute \'magic-url\' is provided for a message',
	'ALLOW_SIG'					=> 'Default for Signature addition',
	'ALLOW_SIG_EXPLAIN'			=> 'This parameter defines whether Signature must be attached to the messsage '
									. 'when no attribute \'signature\' is provided for a message',
	'SUCCESS'					=> 'Success',
	'LOAD_STAT'					=> 'XML file loaded in %ds',
	'PARSE_STAT'				=> '%2$d topics parsed in %1$ds, containing %3$d valid messages',
	'PARSE_ERROR'				=> 'and including %d discarded topics',
	'IMPORT_STAT'				=> '%2$d imported topics in %1$ds',
	'AVAILABLE_TIME'			=> 'within maximum %1$ds available time.',
	'OUT_OF_TIME'				=> 'running out of the %1$ds maximum time (%2$d-%3$d done).',

	'MISSING_FILENAME'			=> 'The name of the file to import is not defined',
	'MISSING_FILE'				=> 'The file to import \'%s\' does not exists or is not readable',
	'INVALID_XML'				=> 'The syntax of the file to import is not valid XML',
	'NOTHING_TO_IMPORT'			=> 'No data to import in XML file (file format may be wrong)',
	'EMPTY_RANGE'				=> 'The range is empty. No topic will be parsed.',
	'TOPIC_WITHOUT_FORUM'		=> 'The topic #%1$d has no parent forum defined (%2$d messages ignored)',
	'NO_MSG_IN_TOPIC'			 => 'No message defined in the topic #%d. The topic cannot be created.',
	'UNKNOWN_FORUM'				=> 'The topic #%1$d will be skipped (%3$d messages ignored) because it belongs to the following unknown forum: %2$s',
	'TOPIC_WITHOUT_TITLE'		=> 'The topic #%1$d will be skipped (%2$d messages ignored) because it has no title (none in its first message either)',
	'MSG_TITLE_OVERWRIT'		=> 'The first message of topic titled "%1$s" has a different title ("%2$s") : first message title ignored',
	'MSG_TITLE_TRUNCAT'			=> 'The topic title "%s" has been truncated to 60 characters.',
	'UNKNOWN_USER_WARNING'		=> 'The following message authors are unknown and are processed as anonymous: ',
	'MISSING_OR_UNK_DATE'		=> 'The message #%1$d of topic #%2$d has no date. Current time will be used.',
	'WRONG_DATE'				=> 'The message #%1$d of topic #%2$d has not a valid past date (%3$s / date-format=%4$s). Current time is used instead.',
	'MORE_MISSING_DATE'			=> 'More missing or invalid dates follow.',
	
));

?>