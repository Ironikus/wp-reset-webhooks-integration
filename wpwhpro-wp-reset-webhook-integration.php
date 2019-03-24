<?php
/**
 * Plugin Name: WPWH - WP Reset Webhook Integration
 * Plugin URI: https://ironikus.com/downloads/wp-reset-webhook-integration/
 * Description: A WP Webhooks Pro extension to integrate WP Reset
 * Version: 1.0.2
 * Author: Ironikus
 * Author URI: https://ironikus.com/
 * License: GPL2
 *
 * You should have received a copy of the GNU General Public License
 * along with TMG User Filter. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'WP_Webhooks_WP_Reset_Integration' ) ){

	class WP_Webhooks_WP_Reset_Integration{

	    private $wpwhpro_wp_reset = false;
	    private $wpr_is_active = null;

		public function __construct() {

			add_action( 'wpwhpro/webhooks/add_webhooks_actions', array( $this, 'add_webhook_actions' ), 20, 3 );
			add_filter( 'wpwhpro/webhooks/get_webhooks_actions', array( $this, 'add_webhook_actions_content' ), 20 );
			add_action( 'admin_notices', array( $this, 'wpwhpro_wpr_throw_admin_notices' ), 100 );
		}

		public function wpwhpro_wpr_throw_admin_notices(){

		    if( ! $this->is_wp_reset_active() ){
			    echo sprintf(WPWHPRO()->helpers->create_admin_notice( '<strong>WP Reset Webhook Integration</strong> is active, but <strong>WP Reset</strong> isn\'t. Please activate it to use the functionality for <strong>WP Reset</strong>. <a href="%s" target="_blank" rel="noopener">More Info</a>', 'warning', false ), 'https://de.wordpress.org/plugins/wp-reset/');
            }

        }

		/**
		 * ######################
		 * ###
		 * #### HELPERS
		 * ###
		 * ######################
		 */

		public function is_wp_reset_active(){

		    if( $this->wpr_is_active !== null ){
		        return $this->wpr_is_active;
            }

		    global $wp_reset;
            $return = false;

		    if( $wp_reset ){
			    $return = true;
            } else {

		        //Check for the frontend hooking functionality
                if( class_exists( 'WP_Reset' ) ){
	                $return = true;
                }

            }

			$this->wpr_is_active = $return;

            return $return;

        }

		public function get_wp_reset(){

		    if( $this->wpwhpro_wp_reset ){
		        return $this->wpwhpro_wp_reset;
            }

            global $wp_reset;

		    if( $this->is_wp_reset_active() ){

		        if( $wp_reset ){
			        $this->wpwhpro_wp_reset = $wp_reset;
                } else {
		            $this->wpwhpro_wp_reset = WP_Reset::getInstance(); //Initialize it by ourselves if it it not set on the frontend
                }

            }

            return $this->wpwhpro_wp_reset;

        }

		/**
         * Return false to deactivate the redirection that gets initialized from WP reset
         *
		 * @param $location
		 * @param $status
		 *
		 * @return bool
		 */
        public function wpwhpro_remove_redirect_filter( $location, $status ){

            return false;

        }

		/**
         * Temporarily activate CLI to make it possible to call certain function from outside
         *
		 * @param $val
		 *
		 * @return bool
		 */
        public function activate_cli_for_wp_reset( $val ){

		    return true;

        }

		/**
		 * ######################
		 * ###
		 * #### WEBHOOK ACTIONS
		 * ###
		 * ######################
		 */

		/*
		 * Register all available action webhooks here
		 *
		 * This function will add your webhook to our globally registered actions array
		 * You can add a webhook by just adding a new line item here.
		 */
		public function add_webhook_actions_content( $actions ){

		    if( ! $this->is_wp_reset_active() ){
		        return $actions;
            }

			$actions[] = $this->action_reset_wp_content();
			$actions[] = $this->action_delete_transients_content();
			$actions[] = $this->action_clean_uploads_folder_content();
			$actions[] = $this->action_delete_themes_content();
			$actions[] = $this->action_delete_plugins_content();
			$actions[] = $this->action_truncate_custom_tables_content();
			$actions[] = $this->action_delete_custom_tables_content();
			$actions[] = $this->action_delete_htaccess_content();

			return $actions;
		}

		/*
		 * Add the callback function for a defined action
		 *
		 * We call the default get_active_webhooks function to grab
		 * all of the currently activated triggers.
		 *
		 * We always send three different properties with the defined wehook.
		 * @param $action - the defined action defined within the action_delete_user_content function
		 * @param $webhook - The webhook itself
		 * @param $api_key - an api_key if defined
		 */
		public function add_webhook_actions( $action, $webhook, $api_key ){

			if( ! $this->is_wp_reset_active() ){
				return;
			}

			$active_webhooks = WPWHPRO()->settings->get_active_webhooks();

			$available_actions = $active_webhooks['actions'];

			switch( $action ){
				case 'reset_wp':
					if( isset( $available_actions['reset_wp'] ) ){
						$this->action_reset_wp();
					}
					break;
				case 'delete_transients':
					if( isset( $available_actions['delete_transients'] ) ){
						$this->action_delete_transients();
					}
					break;
				case 'clean_uploads_folder':
					if( isset( $available_actions['clean_uploads_folder'] ) ){
						var_dump('workz');
						$this->action_clean_uploads_folder();
					}
					break;
				case 'delete_themes':
					if( isset( $available_actions['delete_themes'] ) ){
						$this->action_delete_themes();
					}
					break;
				case 'delete_plugins':
					if( isset( $available_actions['delete_plugins'] ) ){
						$this->action_delete_plugins();
					}
					break;
				case 'truncate_custom_tables':
					if( isset( $available_actions['truncate_custom_tables'] ) ){
						$this->action_truncate_custom_tables();
					}
					break;
				case 'delete_custom_tables':
					if( isset( $available_actions['delete_custom_tables'] ) ){
						$this->action_delete_custom_tables();
					}
					break;
				case 'delete_htaccess':
					if( isset( $available_actions['delete_htaccess'] ) ){
						$this->action_delete_htaccess();
					}
					break;
			}
		}

		public function action_reset_wp_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, nothing gets reset.', 'action-reset_wp-content' ) ),
				'reactivate_theme'   => array( 'short_description' => WPWHPRO()->helpers->translate( 'Wether you want to reactivate the currently active theme again or not. Possible values: "yes" and "no". Default: "no"', 'action-reset_wp-content' ) ),
				'reactivate_plugins' => array( 'short_description' => WPWHPRO()->helpers->translate( 'Wether you want to reactivate the currently active plugins again or not. Possible values: "yes" and "no". Default: "no"', 'action-reset_wp-content' ) ),
				'reactivate_wpreset' => array( 'short_description' => WPWHPRO()->helpers->translate( 'Wether you want to reactivate WP Reset again or not. Possible values: "yes" and "no". Default: "no"', 'action-reset_wp-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-reset_wp-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-reset_wp-content' ) ),
				'data'           => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) The attachment id on success, wp_error on inserting error, upload error on wrong upload or status code error.', 'action-reset_wp-content' ) ),
				'msg'            => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-reset_wp-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => ''
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to completely reset WordPress. This also results in deactivating this plugin and deleting all its data.', 'action-reset_wp-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-reset_wp-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'reset_wp', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Reset your whole website using webhooks.', 'action-reset_wp-content' ),
				'description'       => $description
			);

		}

		public function action_delete_transients_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, no transients are deleted.', 'action-delete_transients-content' ) ),
				'do_action'      => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete_transients-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-delete_transients-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the deleted transients.', 'action-create_url_attachment-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete_transients-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to delete all transients on your website. You can also fire a custom action by defining the action name itself.', 'action-delete_transients-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-delete_transients-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'delete_transients', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Delete all transients on your website using webhooks.', 'action-delete_transients-content' ),
				'description'       => $description
			);

		}

		public function action_clean_uploads_folder_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, the upload folder doesn\'t get cleaned.', 'action-clean_uploads_folder-content' ) ),
				'do_action'      => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-clean_uploads_folder-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-clean_uploads_folder-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the deleted transients.', 'action-create_url_attachment-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-clean_uploads_folder-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to clean the whole uploads directory. The main uploads directory itself will still be available after cleaning it.', 'action-clean_uploads_folder-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-clean_uploads_folder-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'clean_uploads_folder', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Clean your upload directory on your website using webhooks.', 'action-clean_uploads_folder-content' ),
				'description'       => $description
			);

		}

		public function action_delete_themes_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, no theme will be deleted.', 'action-delete_themes-content' ) ),
				'keep_default_theme' => array( 'short_description' => WPWHPRO()->helpers->translate( 'Wether to keep the default theme or not. Possible values: "yes" and "no". Default: "yes"', 'action-delete_themes-content' ) ),
				'do_action'      => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete_themes-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-delete_themes-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the deleted themes.', 'action-delete_themes-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete_themes-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to delete all the currently installed themes.', 'action-delete_themes-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-delete_themes-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'delete_themes', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Delete all themes on your website using webhooks.', 'action-delete_themes-content' ),
				'description'       => $description
			);

		}

		public function action_delete_plugins_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, no plugin will be deleted.', 'action-delete_plugins-content' ) ),
				'keep_wp_reset'      => array( 'short_description' => WPWHPRO()->helpers->translate( 'Wether WP Reset should be deleted as well or not. Possible values: "yes" and "no". Default: "yes"', 'action-delete_plugins-content' ) ),
				'silent_deactivate'  => array( 'short_description' => WPWHPRO()->helpers->translate( 'Skip individual plugin deactivation functions when deactivating. Possible values: "yes" and "no". Default: "no"', 'action-delete_plugins-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete_plugins-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-delete_plugins-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the deleted plugins.', 'action-delete_plugins-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete_plugins-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to delete all the currently installed plugins.', 'action-delete_plugins-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-delete_plugins-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'delete_plugins', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Delete all plugins on your website using webhooks.', 'action-delete_plugins-content' ),
				'description'       => $description
			);

		}

		public function action_truncate_custom_tables_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, no tables get truncated.', 'action-truncate_custom_tables-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-truncate_custom_tables-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-truncate_custom_tables-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the truncated custom tables.', 'action-truncate_custom_tables-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-truncate_custom_tables-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to truncate all custom tables.', 'action-truncate_custom_tables-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-truncate_custom_tables-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'truncate_custom_tables', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Truncate all custom tables on your website using webhooks.', 'action-truncate_custom_tables-content' ),
				'description'       => $description
			);

		}

		public function action_delete_custom_tables_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, no tables get deleted.', 'action-delete_custom_tables-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete_custom_tables-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-delete_custom_tables-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) Count of all the deleted custom tables.', 'action-delete_custom_tables-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete_custom_tables-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'count' => 0
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to delete all custom tables.', 'action-delete_custom_tables-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $count', 'action-delete_custom_tables-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'delete_custom_tables', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Delete all custom tables on your website using webhooks.', 'action-delete_custom_tables-content' ),
				'description'       => $description
			);

		}

		public function action_delete_htaccess_content(){

			$parameter = array(
				'confirm'            => array( 'required' => true, 'short_description' => WPWHPRO()->helpers->translate( 'Please set this value to "yes". If not set, the htaccess file will not be deleted.', 'action-delete_htaccess-content' ) ),
				'do_action'          => array( 'short_description' => WPWHPRO()->helpers->translate( 'Advanced: Register a custom action after Webhooks Pro fires this webhook. More infos are in the description.', 'action-delete_htaccess-content' ) )
			);

			$returns = array(
				'success'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(Bool) True if the action was successful, false if not. E.g. array( \'success\' => true )', 'action-delete_htaccess-content' ) ),
				'data'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(mixed) True if successful or error on failure.', 'action-delete_htaccess-content' ) ),
				'msg'        => array( 'short_description' => WPWHPRO()->helpers->translate( '(string) A message with more information about the current request. E.g. array( \'msg\' => "This action was successful." )', 'action-delete_htaccess-content' ) ),
			);

			ob_start();
			?>
            <pre>
$return_args = array(
    'success' => false,
    'msg' => '',
    'data' => array(
        'response' => null
    )
);
        </pre>
			<?php
			$returns_code = ob_get_clean();

			ob_start();
			?>
                <p><?php echo WPWHPRO()->helpers->translate( 'This webhook enables you to delete the main htaccess file within your WordPress root directory.', 'action-delete_htaccess-content' ); ?></p>
                <p><?php echo WPWHPRO()->helpers->translate( 'The do_action parameter includes the following attributes: $return_args, $confirm, $response', 'action-delete_htaccess-content' ); ?></p>
            <?php
			$description = ob_get_clean();

			return array(
				'action'            => 'delete_htaccess', //required
				'parameter'         => $parameter,
				'returns'           => $returns,
				'returns_code'      => $returns_code,
				'short_description' => WPWHPRO()->helpers->translate( 'Delete the main htaccess file on your website using webhooks.', 'action-delete_htaccess-content' ),
				'description'       => $description
			);

		}

		public function action_reset_wp() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
			);

			$confirm        = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$reactivate_theme    = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'reactivate_theme' ) == 'yes' ) ? true : false;
			$reactivate_plugins  = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'reactivate_plugins' ) == 'yes' ) ? true : false;
			$reactivate_wpreset  = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'reactivate_wpreset' ) == 'yes' ) ? true : false;

			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$args = array(
					'reactivate_theme' => $reactivate_theme,
					'reactivate_plugins' => $reactivate_plugins,
					'reactivate_wpreset' => $reactivate_wpreset
				);

				//Remove the redirect
				add_filter( 'wp_redirect', array( $this, 'wpwhpro_remove_redirect_filter' ), 100, 2 );
				add_filter( 'wp-reset-override-is-cli-running', array( $this, 'activate_cli_for_wp_reset' ), 100 );

				$this->get_wp_reset()->do_reinstall( $args );

				//Add the redirect again
				remove_filter( 'wp_redirect', array( $this, 'wpwhpro_remove_redirect_filter' ) );
				remove_filter( 'wp-reset-override-is-cli-running', array( $this, 'activate_cli_for_wp_reset' ), 100 );

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Reset was successful.", 'action-create_url_attachment-success' );

            } else {
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: Nothing was reset. You did not set the confirmation parameter.", 'action-delete_transients-success' );
            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $reactivate_theme, $reactivate_plugins, $reactivate_wpreset );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_delete_transients() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm        = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$count = $this->get_wp_reset()->do_delete_transients();

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Transients successfully deleted.", 'action-delete_transients-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: No transients deleted. You did not set the confirmation parameter.", 'action-delete_transients-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_clean_uploads_folder() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm        = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$do_action      = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$count = $this->get_wp_reset()->do_delete_uploads();

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Uploads folder successfully cleaned.", 'action-clean_uploads_folder-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: upload directory not cleared. You did not set the confirmation parameter.", 'action-clean_uploads_folder-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_delete_themes() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm            = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$keep_default_theme = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'keep_default_theme' ) == 'no' ) ? false : true;
			$do_action          = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				if (!function_exists('delete_theme')) {
					require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';
				}

				$count = $this->get_wp_reset()->do_delete_themes( $keep_default_theme );

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Themes successfully deleted.", 'action-delete_themes-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: Themes not deleted. You did not set the confirmation parameter.", 'action-delete_themes-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_delete_plugins() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm            = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$keep_wp_reset      = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'keep_wp_reset' ) == 'no' ) ? false : true;
			$silent_deactivate  = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'silent_deactivate' ) == 'yes' ) ? true : false;
			$do_action          = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				if (!function_exists('request_filesystem_credentials')) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				$count = $this->get_wp_reset()->do_delete_plugins( $keep_wp_reset, $silent_deactivate );

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Plugins successfully deleted.", 'action-delete_plugins-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: Plugins not deleted. You did not set the confirmation parameter.", 'action-delete_plugins-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_truncate_custom_tables() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm            = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$do_action          = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$count = $this->get_wp_reset()->do_truncate_custom_tables();

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Custom tables successfully truncated.", 'action-truncate_custom_tables-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: Custom tables not truncated. You did not set the confirmation parameter.", 'action-truncate_custom_tables-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_delete_custom_tables() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'count' => 0
                )
			);

			$confirm            = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$do_action          = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$count = $this->get_wp_reset()->do_drop_custom_tables();

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "Custom tables successfully deleted.", 'action-delete_custom_tables-success' );
				$return_args['data']['count'] = $count;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: Custom tables not truncated. You did not set the confirmation parameter.", 'action-delete_custom_tables-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $count );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

		public function action_delete_htaccess() {

			$response_body = WPWHPRO()->helpers->get_response_body();
			$return_args = array(
				'success' => false,
                'msg' => '',
                'data' => array(
                    'response' => null
                )
			);

			$confirm            = ( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'confirm' ) == 'yes' ) ? true : false;
			$do_action          = sanitize_title( WPWHPRO()->helpers->validate_request_value( $response_body['content'], 'do_action' ) );

			if( $confirm ){

				$response = $this->get_wp_reset()->do_delete_htaccess();

				$return_args['success'] = true;
				$return_args['msg'] = WPWHPRO()->helpers->translate( "htaccess file successfully deleted.", 'action-delete_htaccess-success' );
				$return_args['data']['response'] = $response;

            } else {

				$return_args['msg'] = WPWHPRO()->helpers->translate( "Error: The htaccess file was not deleted. You did not set the confirmation parameter.", 'action-delete_htaccess-success' );

            }

			if( ! empty( $do_action ) ){
				do_action( $do_action, $return_args, $confirm, $response );
			}

			WPWHPRO()->webhook->echo_response_data( $return_args );

			die();
		}

	} // End class

	function wpwhpro_load_wp_reset_integration(){
		new WP_Webhooks_WP_Reset_Integration();
	}

	// Make sure we load the extension after main plugin is loaded
	if( defined( 'WPWH_SETUP' ) || defined( 'WPWHPRO_SETUP' ) ){
		wpwhpro_load_wp_reset_integration();
    } else {
		add_action( 'wpwhpro_plugin_loaded', 'wpwhpro_load_wp_reset_integration' );
    }

	//Throw message in case WP Webhook is not active
	add_action( 'admin_notices', 'wpwh_wp_reset_throw_custom_notice', 100 );
    function wpwh_wp_reset_throw_custom_notice(){

        if( ! defined( 'WPWH_SETUP' ) && ! defined( 'WPWHPRO_SETUP' ) ){

                ob_start();
                ?>
                <div class="notice notice-warning">
                    <p><?php echo sprintf( '<strong>WP Reset Webhook Integration</strong> is active, but <strong>WP Webhooks</strong> or <strong>WP Webhooks Pro</strong> isn\'t. Please activate it to use the functionality for <strong>WP Reset</strong>. <a href="%s" target="_blank" rel="noopener">More Info</a>', 'https://de.wordpress.org/plugins/wp-webhooks/' ); ?></p>
                </div>
                <?php
                echo ob_get_clean();

        }

    }

}