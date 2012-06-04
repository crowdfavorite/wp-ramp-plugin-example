<?php
/*
Plugin Name: RAMP plugin example
Description: Crowd Favorite RAMP plugin plugin example
Author: Crowd Favorite
Version: 1.0
Author URI: http://crowdfavorite.com/
*/

/**
 * Action to register the callbacks; must be called at cfd_admin_init
 **/
function rpe_register_deploy_callbacks() {
	global $rpe_deploy_callbacks;
	$rpe_deploy_callbacks = new rpe_deploy_callbacks;
	$rpe_deploy_callbacks->register_deploy_callbacks();
}

add_action('cfd_admin_init', 'rpe_register_deploy_callbacks');
	
class rpe_deploy_callbacks {
	// The name is used to generate the callback ID in RAMP.  Using a 
	// translated name may result in problems if the production and staging 
	// servers have different languages set.
	protected $name = 'RAMP Plugin Example';
	protected $description = '';
	
	public function __construct() {
		// This is a good place to do initial setup, if any is needed
		$this->description = __('Demonstrates how to integrate with RAMP by sending the admin email setting', 'rpe');
	}

	/**
	 * Callbacks should be registered in a batch when possible.  Check the name 
	 * of the callback a function is being registered to - there is no error 
	 * message for using an invalid callback, and it can produce unexpected 
	 * results.
	 **/
	public function register_deploy_callbacks() {
		cfd_register_deploy_callback($this->name, $this->description, 
			array(
				'send_callback' => array($this, 'rpe_send_callback'), 
				'receive_callback' => array($this, 'rpe_receive_callback'),
				'preflight_send_callback' => array($this, 'rpe_preflight_send_callback'),
				'preflight_check_callback' => array($this, 'rpe_preflight_check_callback'),
				'preflight_display_callback' => array($this, 'rpe_preflight_display_callback'),
				'comparison_send_callback' => array($this, 'rpe_comparison_send_callback'),
				'comparison_check_callback' => array($this, 'rpe_comparison_check_callback'),
				'comparison_selection_row_callback' => array($this, 'rpe_comparison_selection_row_callback'),
			)
		);
	}

	// -- Comparison (New Batch) callbacks

	/**
	 * Runs on the staging server
	 * Generates data to do the "new batch" comparison
	 * Data is passed to comparison_check
	 *
	 * @param array $batch_comparison_data The complete array of batch 
	 * comparison data to send.
	 * 
	 * @return array Returns an array of arrays of data to be passed to the 
	 * comparison_check callback, with the top-level keys being internal 
	 * identifiers for 'rows' of this extra's data
	 *
	 **/
	public function rpe_comparison_send_callback($batch_comparison_data) {
		// We use the 'send' callback for comparison_send as well, passing an 
		// optional second parameter to force the send callback to operate; 
		// the send callback can check if it is part of the batch and skip its
		// operation if not, but we want it to always run during comparison.
		return $this->rpe_send_callback($batch_comparison_data, true);
	}

	/**
 	 * Runs on the production server
	 * Generates data to do the "new batch" comparison
	 * Data is received from comparison_send
	 * Data is passed to comparison_selection_row
	 *
	 * @param array $data The data produced by the comparison_send callback
	 * @param array $batch_items The complete array of batch comparison data 
	 * sent
	 *
	 * @return array An array of arrays of data to be passed to the 
	 * comparison_selection_row callback, with the top-level keys being 
	 * internal identifiers for 'rows' of this extra's data
	 **/
	public function rpe_comparison_check_callback($data, $batch_items) {
		// We start with a default return value
		$ret = array('differ' => false);

		$rpe_config = get_option('admin_email');
		$stage_rpe_config = (isset($data['rpe_config']) && isset($data['rpe_config']['status'])) ? $data['rpe_config']['status'] : false;
		if (serialize($stage_rpe_config) !== serialize($rpe_config)) {
			$ret['rpe_config'] = $rpe_config;
			$ret['stage_rpe_config'] = $stage_rpe_config;
			$ret['differ'] = true;
		}
		// All data should be returned in an array of top-level keys
		return array('rpe_config' => $ret);
	}

	/**
	 * Runs on staging
	 * Generates the "new batch" comparison rows
	 * Data is received from comparison_send and comparison_check
	 * Data is passed to RAMP for display
	 *
	 * Takes the comparison data returned from staging and from production,
	 * and determines if any "New Batch" rows should be displayed (and if
	 * they should have checkboxes, and if so, if those checkboxes should
	 * be checked by default).
	 *
	 * @param array $compiled_data One row of the compiled array of comparison 
	 * data, including the following keys:
	 *		status: one row of the data returned by comparison_send
	 *		remote_status: one row of the data returned by comparison_check
	 *		id: the ID of the extra which produced this data
	 *		extra_id: the top-level key of the comparison_* row currently being 
	 *		processed
	 *		in_batch: if the corresponding row's checkbox was checked on the 
	 *		new batch page
	 *
	 *	@return array An array with three keys:
	 *		selected: true|false|null - true or false checks or unchecks the 
	 *		new batch row checkbox by default; null suppresses the checkbox
	 *		forced: true|false (optional) - if true, the checkbox will be 
	 *		disabled
	 *		title: The title for the row - usually the plugin name
	 *		message: The message for the row (describing changes if any, et 
	 *		cetera)
	 **/
	public function rpe_comparison_selection_row_callback($compiled_data) {
		$ret = array(
			'selected' => null,
			'title' => $this->name,
			'message' => $this->description,
		);
		// Check the 'remote_status' - the data from comparison_check
		if ($compiled_data['remote_status']['differ']) {
			$ret['selected'] = true;
			// If we wanted to require this row to be read-only, we would set 
			// 'forced' here
			//$ret['forced'] = true;
		}
		else {
			$ret['message'] .= ' (no differences found)';
		}

		return $ret;
	}

	// -- Preflight callbacks
	
	/**
	 * Runs on staging server
	 * Generates the "preflight" check data
	 * Data is passed to preflight_check
	 *
	 *
	 * Prepare staging data to send for the preflight checks
	 *
	 * @param array $data 
	 * @return array
	 **/
	public function rpe_preflight_send_callback($data) {
		// Again, use the send callback, but this time let it decide if it 
		// should run based on whether the row was selected in the batch.
		return $this->rpe_send_callback($data);
	}

	/**
	 * Runs on production server
	 * Generates the "preflight" check data
	 * Data is checked by RAMP for flagged error condition
	 *
	 * Receive the staging preflight checked data, compare it to the
	 * production state, and return messages about the preflight; among
	 * other things, trigger an error if there is a change in EPT data
	 * and there is anything in the batch aside from EPT data.
	 *
	 * @param array $data The data produced by the preflight_send callback
	 * @param array $batch_items The complete array of batch preflight data 
	 * sent
	 *
	 * @return array A row of preflight messages, with optional subrows
	 * Rows are an array of (all optional) '__message__', '__notice__', 
	 * '__warning__', '__error__', which are in turn arrays of strings.  The 
	 * presence of any of these but '__message__' will cause the preflight to 
	 * block with an error. Optionally, it can also contain a key 'rows', which 
	 * is an array of 'Descriptive Name' => array(), where the sub-arrays are 
	 * likewise '__message__' et al, which will be shown as sub-rows on the 
	 * preflight screen.
	 **/
	public function rpe_preflight_check_callback($data, $batch_data) {
		$ret = array();
		$errors = array();
		
		// Here we require the batch to /only/ contain this plugin's items; 
		// generally this would not be needed.
		$rpe_config = get_option('admin_email');
		if (serialize($data) !== serialize($rpe_config)) {
			foreach ($batch_data as $key => $val) {
				// If the batch contains anything that isn't an 'extra':
				if ('extras' !== $key && !empty($val)) {
					$errors[] = sprintf(__('Batch contains items in %s', 'rpe'), $key);
				}
				// Or if it contains an 'extra' that isn't this plugin's:
				else if (is_array($val)) {
					$extra_id = cfd_make_callback_id($this->name);
					foreach ($val as $extra_key => $extra_val) {
						if ($extra_id !== $extra_key)
							$errors[] = sprintf(__('Batch contains items in %s', 'rpe'), $key . '/' . $extra_key);

					}
				}
			}
			if (!empty($errors)) {
				$ret['__notice__'] = array('The admin email must, as an example, be sent separately from any other batch items');
			}
			$ret['__message__'] = array('The admin email will be updated.');
		}
		if (!empty($errors)) {
			$ret['__error__'] = $errors;
		}

		// Other checks could be run (to make sure that everything this data 
		// depended on was being sent, for example).

		// The message types are as follows:
		#$ret['__notice__'] = array("This is a notice, and does not error the batch");
		#$ret['__warning__'] = array("This is a warning, and does not error the batch");
		#$ret['__error__'] = array("This is an error, and errors the batch");
		#
		#$ret['__message__'] = array("This is a message, and does not error the batch");
		
		// If you want to display more than one row, the 'rows' key can be set 
		// as an array.  The key is the name of the row, and the value is a 
		// similar collection of messages:
		#$ret['rows'] = array('Sub-row example' => $ret);
		
		return $ret;
	}
	
	/**
	 * Runs on staging server
	 * Optionally changes other messages in the preflight display
	 * Data is checked by RAMP for flagged error condition
	 *
	 * @param array $batch_preflight_data An array of messages; top-level keys 
	 * are gross types (post_type, etc) including 'extras'); second-level keys 
	 * are rows; below that, the format differs per type.  Extras rows are an 
	 * array of (all optional) '__message__', '__notice__', '__warning__', 
	 * '__error__'; 
	 *
	 * @return array The modified messages array
	 **/
	public function rpe_preflight_display_callback($batch_preflight_data) {
		// Here we can modify messages.  __message__ is only valid for extras, 
		// while __notice__, __warning__, and __error__ should be valid for all 
		// types.
		// The presence of __error__ for an item causes the preflight not to validate.
		if (isset($batch_preflight_data['post_types']) && isset($batch_preflight_data['post_types']['post'])) {
			foreach ($batch_preflight_data['post_types']['post'] as $key => $val) {
				if (isset($val['__error__'])) {
					$batch_preflight_data['post_types']['post'][$key]['__notice__'][] = __('(Adding a notice to a post\'s message.)', 'rpe');
				}
				else {
					$batch_preflight_data['post_types']['post'][$key]['__notice__'] = array(__('Creating a notice for a post.', 'rpe'));
				}
			}

		}
		return $batch_preflight_data;
	}

// Transfer Callback Methods
	
	/**
 	 * Runs on staging server
	 * Prepare staging server data for actual deploy
	 * Data is passed to receive
	 *
	 * @param array $batch_data The complete array of batch 
	 * data to send.
	 * 
	 * @return array Returns data to be passed to the receive callback.
	 * If the data is empty(), the receive callback will not be called.
	 * 
	 **/
	public function rpe_send_callback($batch_data, $forced = false) {
		$extra_id = cfd_make_callback_id($this->name);
		// Check to see if the 'rpe_config' row was checked in the batch, or if 
		// the 'forced' parameter was set (as in the case where we are being 
		// called from comparison_send)
		if ($forced || (
				isset($batch_data['extras']) &&
				isset($batch_data['extras'][$extra_id]) &&
				in_array('rpe_config', $batch_data['extras'][$extra_id]))) {
			return array('rpe_config' => get_option('admin_email'));
		}
		return null;
	}
	
	/**
	 * Runs on production server
	 * Update the production server with staging server data
	 * Data is passed from send
	 * Data is passed to RAMP to display messages and error conditions
	 *
	 * @param array $rpe_settings The data returned by the send callback.
	 *
	 * @return array An array with the following required keys:
	 *		'success': a boolean for successful or failed transfer
	 *		'message': a message to display on the batch complete page for this 
	 *		plugin's data
	 **/
	public function rpe_receive_callback($rpe_settings) {
		// default return status
		$success = true;
		$message = __('No RAMP plugin example changes to deploy', 'rpe');

		// We check to see if there are actually any changes to make, so we can 
		// make the message accurate.
		$local_settings = get_option('admin_email');
		if (serialize($rpe_settings['rpe_config']) !== serialize($local_settings)) {
			update_option('admin_email', $rpe_settings['rpe_config']);
			$message = __('RAMP plugin example changes made: updated the admin email', 'rpe');
		}

		return array(
			'success' => $success,
			'message' => $message
		);
	}
	
}

