<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'lk_advanced_category/config.php';

/**
 * LK Popular, pulls popular entries
 */
class Lk_advanced_category_ext {

	public $name			= LK_ADVANCED_CATEGORY_NAME;
	public $version			= LK_ADVANCED_CATEGORY_VER;
	public $description		= LK_ADVANCED_CATEGORY_DESC;
	public $docs_url		= LK_ADVANCED_CATEGORY_DOCS;
	public $settings_exist	= 'n';

	public $settings		= array();
	public $config_loc		= FALSE;
	
	public $EE;

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 * @return void
	 */
	public function __construct($settings = array())
	{
		$this->EE =& get_instance();
	}
	// END


	/**
	 * Activate Extension
	 * @return void
	 */
	public function activate_extension()
	{
		$data = array(
			'class'		=> __CLASS__,
			'hook'		=> 'low_search_pre_search',
			'method'	=> 'low_search_pre_search',
			'settings'	=> serialize($this->settings),
			'priority'	=> 10,
			'version'	=> $this->version,
			'enabled'	=> 'y'
		);
		
		$this->EE->db->insert('extensions', $data);
	}
	// END


	/**
	 * Disable Extension
	 *
	 * @return void
	 */
	public function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	// END

	/**
	 * Method to add extra filtering
	 */
	public function low_search_pre_search($params)
	{
		// Play nice with others
		if($this->EE->extensions->last_call !== FALSE)
	    {
	      $params = $this->EE->extensions->last_call;
	    }
	    foreach ($params AS $key => $val)
		{
			// Get groups only
			if (substr($key, 0, 9) != 'category:') continue;
			
			// Get rid of characters
			$val2 = preg_replace("/[^0-9\|&]/", "", $val);
			
			// Change & to |
			$val2 = str_replace('&', '|', $val2);
			
			// Explode
			$cats = explode('|', $val2);
			$cats = array_unique($cats);
			
			foreach ($cats as $cat) { // process all the matching categories in by url_title
				if ( ! ee()->session->cache('lk_advanced_category', $cat)) {
					// Get the url_title
					ee()->db->select('cat_url_title'); 
					ee()->db->where('cat_id', $cat);
					$query = ee()->db->get('categories');
					if ($query->num_rows() > 0)
					{
						$result = $query->result();
						// grab all the categories with matching url titles
						ee()->db->select('cat_id'); 
						ee()->db->where('cat_url_title', $result[0]->cat_url_title);
						$query2 = ee()->db->get('categories');
						$cats2 = array();
						foreach ($query2->result() as $r) {
							$cats2[] = $r->cat_id;
						}
						$query->free_result();
						$query2->free_result();
						// cache it
						ee()->session->set_cache('lk_advanced_category', $cat, implode('|', $cats2));
					}
				}
				// Replace it in
				$val = str_replace($cat, ee()->session->cache('lk_advanced_category', $cat), $val);
			}
			
			$params[$key] = $val;
		}

		return $params;
	}

	/**
	 * Update Extension
	 *
	 * @param 	string	String value of current version
	 * @return 	mixed	void on update / false if none
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		// update table row with version
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
			'extensions', 
			array('version' => $this->version)
		);
	}
	// END

}
// END CLASS