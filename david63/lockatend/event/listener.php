<?php
/**
*
* @package Locked Topics at End Extension
* @copyright (c) 2015 david63
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace david63\lockatend\event;

/**
* @ignore
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
* Event listener
*/
class listener implements EventSubscriberInterface
{
	/**
	* Assign functions defined in this class to event listeners in the core
	*
	* @return array
	* @static
	* @access public
	*/
	static public function getSubscribedEvents()
	{
		return array(
			'core.viewforum_get_topic_ids_data'	=> 'update_viewforum_sql_ary',
			'core.mcp_view_forum_modify_sql'	=> 'update_mcp_sql_ary',
		);
	}

	/**
	* Update the sql data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function update_viewforum_sql_ary($event)
	{
		$sql_ary			= $event['sql_ary'];
		$store_reverse		= $event['store_reverse'];
		$sql_sort_order		= $event['sql_sort_order'];
		$event['sql_ary']	= str_replace($sql_sort_order, 't.topic_status ' . ((!$store_reverse) ? 'ASC' : 'DESC') . ', ' . $sql_sort_order, $sql_ary);
	}

	/**
	* Update the sql data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function update_mcp_sql_ary($event)
	{
		$sql			= $event['sql'];
		$event['sql']	= str_replace('t.topic_last_post_time', 't.topic_status ASC, t.topic_last_post_time', $sql);
	}
}
