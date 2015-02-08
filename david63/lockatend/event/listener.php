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
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\twig\twig */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Constructor for listener
	*
	* @param \phpbb\config\config		$config		Config object
	* @param \phpbb\request\request		$request	Request object
	* @param \phpbb\template\twig\twig	$template	Template object
	* @param \phpbb\user                $user		User object
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\request\request $request, \phpbb\template\twig\twig $template, \phpbb\user $user)
	{
		$this->config	= $config;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;
	}

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
			'core.user_setup'					=> 'load_user_data',
			'core.acp_board_config_edit_add'	=> 'acp_board_settings',
			'core.ucp_prefs_view_data'			=> 'add_user_prefs',
			'core.ucp_prefs_view_update_data'	=> 'update_user_prefs',
			'core.viewforum_get_topic_ids_data'	=> 'update_viewforum_sql_ary',
			'core.mcp_view_forum_modify_sql'	=> 'update_mcp_sql_ary',
		);
	}

	/**
	* Load the necessay data during user setup
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function load_user_data($event)
	{
		// Load the language file
		$lang_set_ext	= $event['lang_set_ext'];
		$lang_set_ext[]	= array(
			'ext_name' => 'david63/lockatend',
			'lang_set' => 'lockatend',
		);

		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	* Set ACP board settings
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function acp_board_settings($event)
	{
		if ($event['mode'] == 'post')
		{
			$new_display_var = array(
				'title'	=> $event['display_vars']['title'],
				'vars'	=> array(),
			);

			foreach ($event['display_vars']['vars'] as $key => $content)
			{
				$new_display_var['vars'][$key] = $content;
				if ($key == 'posts_per_page')
				{
					$new_display_var['vars']['lockatend_user_enable'] = array(
						'lang'		=> 'LOCK_AT_END_ENABLE',
						'validate'	=> 'bool',
						'type'		=> 'radio:yes_no',
						'explain' 	=> true,
					);
				}
			}
			$event->offsetSet('display_vars', $new_display_var);
		}
	}

	/**
	* Add the necessay variables
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function add_user_prefs($event)
	{
		if ($this->config['lockatend_user_enable'])
		{
			$data = $event['data'];

			$data = array_merge($data, array(
				'lock_at_end' => $this->request->variable('lock_at_end', (!empty($user->data['user_lock_at_end'])) ? $user->data['user_lock_at_end'] : 0),
			));

			$event->offsetSet('data', $data);
		}

		$this->template->assign_vars(array(
			'S_LOCK_AT_END'	=> $this->user->data['user_lock_at_end'],
			'S_USER_ENABLE' => $this->config['lockatend_user_enable'],
		));
	}

	/**
	* Update the sql data
	*
	* @param object $event The event object
	* @return null
	* @access public
	*/
	public function update_user_prefs($event)
	{
		if ($this->config['lockatend_user_enable'])
		{
			$sql_ary	= $event['sql_ary'];
			$data		= $event['data'];

			$sql_ary = array_merge($sql_ary, array(
				'user_lock_at_end'	=> $data['lock_at_end'],
			));

			$event->offsetSet('sql_ary', $sql_ary);
		}
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
		if (($this->config['lockatend_user_enable'] && $this->user->data['user_lock_at_end']) || !$this->config['lockatend_user_enable'])
		{
			$sql_ary			= $event['sql_ary'];
			$store_reverse		= $event['store_reverse'];
			$sql_sort_order		= $event['sql_sort_order'];
			$event['sql_ary']	= str_replace($sql_sort_order, 't.topic_status ' . ((!$store_reverse) ? 'ASC' : 'DESC') . ', ' . $sql_sort_order, $sql_ary);
		}
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
		if (($this->config['lockatend_user_enable'] && $this->user->data['user_lock_at_end']) || !$this->config['lockatend_user_enable'])
		{
			$sql			= $event['sql'];
			$event['sql']	= str_replace('t.topic_last_post_time', 't.topic_status ASC, t.topic_last_post_time', $sql);
		}
	}
}
