<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cartthrob_change_product_status_ext
{
	public $settings = array();
	public $name = 'CartThrob Custom Extension - Change Product Status';
	public $version = '1.0.0';
	public $description = 'Change the status of a product entry after purchase.';
	public $settings_exist = 'y';
	public $docs_url = '';

	/**
	 * constructor
	 * 
	 * @access	public
	 * @param	mixed $settings = ''
	 * @return	void
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		
		$this->settings = $settings;
	}
	
	/**
	 * activate_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function activate_extension()
	{
		$hook_defaults = array(
			'class' => __CLASS__,
			'settings' => '',
			'version' => $this->version,
			'enabled' => 'y',
			'priority' => 10
		);
		
		$hooks[] = array(
			'method' => 'cartthrob_on_authorize',
			'hook' => 'cartthrob_on_authorize'
		);
		
		foreach ($hooks as $hook)
		{
			$this->EE->db->insert('extensions', array_merge($hook_defaults, $hook));
		}
	}
	
	/**
	 * update_extension
	 * 
	 * @access	public
	 * @param	mixed $current = ''
	 * @return	void
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->update('extensions', array('version' => $this->version), array('class' => __CLASS__));
	}
	
	/**
	 * disable_extension
	 * 
	 * @access	public
	 * @return	void
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array('class' => __CLASS__));
	}
	
	/**
	 * settings
	 * 
	 * @access	public
	 * @return	void
	 */
	public function settings()
	{
		$settings = array();
		
		$this->EE->load->add_package_path(PATH_THIRD.'cartthrob/');
		
		$this->EE->load->library('cartthrob_loader');
		
		$channels = $this->EE->cartthrob->store->config('product_channels');
		
		if ( ! $channels)
		{
			show_error('no_product_channels');
		}
		
		$statuses = array();
		
		$channel_titles = array();
		
		$query = $this->EE->db->select('statuses.status, channels.channel_id, channels.channel_title')
					->join('status_groups', 'status_groups.group_id = statuses.group_id')
					->join('channels', 'channels.status_group = status_groups.group_id')
					->where_in('channels.channel_id', $channels)
					->order_by('statuses.status_order', 'asc')
					->get('statuses');
		
		$this->EE->lang->loadfile('admin_content');
		
		foreach ($query->result() as $row)
		{
			if ( ! isset($statuses[$row->channel_id]))
			{
				$statuses[$row->channel_id] = array('' => lang('dont_change_status'));
			}
			
			if ( ! isset($channel_titles[$row->channel_id]))
			{
				$channel_titles[$row->channel_id] = $row->channel_title;
			}
			
			$statuses[$row->channel_id][$row->status] = lang($row->status);
		}
		
		$query->free_result();
		
		foreach ($channels as $channel_id)
		{
			if ( ! isset($channel_titles[$channel_id]) || ! isset($statuses[$channel_id]))
			{
				continue;
			}
			
			$this->EE->lang->language['channel_'.$channel_id] = sprintf(lang('channel_status'), $channel_titles[$channel_id]);
			
			$settings['channel_'.$channel_id] = array('s', $statuses[$channel_id], '');
		}
		
		return $settings;
	}
	
	public function cartthrob_on_authorize()
	{
		foreach ($this->EE->cartthrob->cart->items() as $item)
		{
			if ( ! $item->meta('channel_id') || ! $item->product_id() || empty($this->settings['channel_'.$item->meta('channel_id')]))
			{
				continue;
			}
			
			$this->EE->db->update(
				'channel_titles',
				array('status' => $this->settings['channel_'.$item->meta('channel_id')]),
				array('entry_id' => $item->product_id())
			);
		}
	}
}

/* End of file ext.cartthrob_change_product_status.php */
/* Location: ./system/expressionengine/third_party/cartthrob_change_product_status/ext.cartthrob_change_product_status.php */