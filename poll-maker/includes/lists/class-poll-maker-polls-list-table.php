<?php
ob_start();

class Polls_List_Table extends WP_List_Table {
	private $plugin_name;
	private $title_length;

	/** Class constructor */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
		$this->title_length = Poll_Maker_Ays_Admin::get_listtables_title_length('polls');
		parent::__construct(array(
			'singular' =>esc_html__('Poll', "poll-maker"), //singular name of the listed records
			'plural'   =>esc_html__('Polls', "poll-maker"), //plural name of the listed records
			'ajax'     => false, //does this table support ajax?
		));
		add_action('admin_notices', array($this, 'poll_notices'));
		add_filter( 'default_hidden_columns', array( $this, 'get_hidden_columns'), 10, 2 );
	}

	public function get_categories() {
		global $wpdb;
		$cat_table = esc_sql($wpdb->prefix."ayspoll_categories");
		$sql = "SELECT * FROM ".$cat_table;

		return $wpdb->get_results($sql, 'ARRAY_A');
	}

	public function display_tablenav( $which ) {
        ?>
        <div class="tablenav <?php echo esc_attr( $which ); ?>">
            
            <div class="alignleft actions">
                <?php  $this->bulk_actions( $which ); ?>
            </div>
            <?php
            $this->extra_tablenav( $which );
            $this->pagination( $which );
            ?>
            <br class="clear" />
        </div>
        <?php
	}

	public function extra_tablenav($which) {
		global $wpdb;
		$category_table = $wpdb->prefix . 'ayspoll_categories';
        $titles_sql = "SELECT ".$category_table.".title,".$category_table.".id FROM ".$category_table;
        $cat_titles = $wpdb->get_results($titles_sql);
        $cat_id = null;
        if( isset( $_GET['filterby'] )){
            $cat_id = intval($_GET['filterby']);
        }

		if( isset( $_GET['filterbyauthor'] )){            
            $author_id_select = absint( sanitize_text_field( $_GET['filterbyauthor'] ) );
        }

		if( isset( $_GET['filterbytype'] )){            
            $type_select = sanitize_text_field( $_GET['filterbytype'] );
        }

        $categories_select = array();
        foreach($cat_titles as $key => $cat_title){
            $selected = "";
            if($cat_id === intval($cat_title->id)){
                $selected = "selected";
            }
            $categories_select[$cat_title->id]['title'] = $cat_title->title;
            $categories_select[$cat_title->id]['selected'] = $selected;
            $categories_select[$cat_title->id]['id'] = $cat_title->id;
        }
        sort($categories_select);

        $cat_id = null;

		$authors = array();
		$users_sql = "SELECT `styles` FROM " .$wpdb->prefix . "ayspoll_polls";
        $users = $wpdb->get_results($users_sql, "ARRAY_A");

		foreach ($users as $user_key => $user) {
			$options = (isset($user['styles']) && $user['styles'] != '') ? json_decode( $user['styles'], 'ARRAY_A' ) : '';
			$author = (isset( $options['author'] ) && !empty($options['author'])) ? $options['author'] : array();

			if (!is_array($author)) {
				$author = json_decode($author, 'ARRAY_A');
			}

			if (!empty($author)) {
				$author_id = (isset($author['id']) && $author['id'] != '') ? intval($author['id']) : '';
				$author_name = (isset($author['name']) && $author['name'] != '') ? sanitize_text_field($author['name']) : '';
				$authors[$author_id] = $author_name;
			}
		}

		$types = array();
		$type_sql = "SELECT DISTINCT `type` FROM " .$wpdb->prefix . "ayspoll_polls";
		$existed_types = $wpdb->get_results( $type_sql, "ARRAY_A");

		foreach( $existed_types as $type_key => $type){
			$curent_type = ( isset( $type['type'] ) && $type['type'] != '') ? sanitize_text_field($type['type']) : '';
			$types[$type_key] = $curent_type;
		}

        ?>
        <div id="poll-filter-div-<?php echo esc_attr( $which ); ?>" class="alignleft actions bulkactions">
            <select name="filterby-<?php echo esc_attr($which); ?>" id="bulk-action-selector-<?php echo esc_attr($which); ?>">
                <option value=""><?php echo esc_html__('Select Category', "poll-maker")?></option>
                <?php
                    foreach($categories_select as $key => $cat_title){
                        echo "<option ".$cat_title['selected']." value='".$cat_title['id']."'>".$cat_title['title']."</option>";
                    }
                ?>
            </select>
			<select name="filterbyauthor-<?php echo esc_attr( $which ); ?>" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>">
				<option value=""><?php echo esc_html__('Select Author', "poll-maker")?></option>
				<?php
					foreach ($authors as $author_key => $author) {
						$user_selected = ( isset($author_id_select) && $author_key == $author_id_select ) ? "selected" : "";

						echo "<option " . $user_selected . " value='" . $author_key . "'>" . $author . "</option>";
					}
				?>
			</select>
			<select name="filterbytype-<?php echo esc_attr( $which ); ?>" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>">
				<option value=""><?php echo esc_html__('Select Type', "poll-maker")?></option>
				<?php
					foreach( $types as $type_key => $type){
						$user_selected_type = ( isset($type_select) && $type == $type_select ) ? "selected" : "";
						echo "<option ".$user_selected_type." value='".$type."'>".$type."</option>";
					}
				?>
			</select>
			<input type="button" id="doaction-<?php echo esc_attr($which); ?>" class="ays-poll-question-tab-all-filter-button-<?php echo esc_attr($which); ?> button" value="Filter">
        </div>
		<a href="?page=<?php echo esc_attr( $_REQUEST['page'] ); ?>" class="button actions_style"><?php echo esc_html__( "Clear filters", "poll-maker" ); ?></a>
        <?php
    }

    protected function get_views() {
		$published_count = $this->published_polls_count();
        $unpublished_count = $this->unpublished_polls_count();
        $all_count = $this->all_record_count();
		$selected_all = "";
        $selected_0 = "";
        $selected_1 = "";
		if( isset( $_REQUEST['fstatus'] ) && is_numeric( $_REQUEST['fstatus'] ) && ! is_null( sanitize_text_field( $_REQUEST['fstatus'] ) ) ){

            $fstatus  = absint( $_REQUEST['fstatus'] );

            switch( $fstatus ){
                case 0:
                    $selected_0 = " style='font-weight:bold;' ";
                    break;
                case 1:
                    $selected_1 = " style='font-weight:bold;' ";
                    break;
                default:
                    $selected_all = " style='font-weight:bold;' ";
                    break;
            }
        }else{
            $selected_all = " style='font-weight:bold;' ";
        }

		$admin_url = get_admin_url( null, 'admin.php' );
        $get_properties = http_build_query($_GET);

		$status_links_url = $admin_url . "?" . $get_properties;
		$publish_url = esc_url( add_query_arg('fstatus', 1, $status_links_url) );
        $unpublish_url = esc_url( add_query_arg('fstatus', 0, $status_links_url) );

		$status_links = array(
            "all" => "<a ".$selected_all." href='?page=".esc_attr( $_REQUEST['page'] )."'>".esc_html__( 'All', "poll-maker" )." (".$all_count.")</a>",
            "published" => "<a ".$selected_1." href='". $publish_url ."'>".esc_html__( 'Published', "poll-maker" )." (".$published_count.")</a>",
            "unpublished"   => "<a ".$selected_0." href='". $unpublish_url ."'>".esc_html__( 'Unpublished', "poll-maker" )." (".$unpublished_count.")</a>"
        );
        return $status_links;
	}

	public function add_or_edit_polls( $data, $id = null, $ays_change_type = "" ) {
		global $allowedtags;
		$old_allowedtags = $allowedtags;
		$default_attribs = array(
			'id'    => array(),
			'class' => array(),
			'title' => array(),
			'style' => array(),
		);
		$allowedtags     = array(
			'div'        => $default_attribs,
			'span'       => $default_attribs,
			'p'          => $default_attribs,
			'a'          => array_merge($default_attribs, array(
				'href'   => array(),
				'target' => array('_blank', '_top'),
			)),
			'input'      => array_merge($default_attribs, array(
				'type'  => array(),
				'name'  => array(),
				'value' => array(),
			)),
			'textarea'   => array_merge($default_attribs, array(
				'type' => array(),
				'name' => array(),
			)),
			'u'          => $default_attribs,
			'i'          => $default_attribs,
			'q'          => $default_attribs,
			'b'          => $default_attribs,
			'ul'         => $default_attribs,
			'dl'         => $default_attribs,
			'ol'         => $default_attribs,
			'li'         => $default_attribs,
			'br'         => $default_attribs,
			'hr'         => $default_attribs,
			'strong'     => $default_attribs,
			'blockquote' => $default_attribs,
			'del'        => $default_attribs,
			'strike'     => $default_attribs,
			'em'         => $default_attribs,
			'code'       => $default_attribs,
		);

		global $wpdb;
		$poll_table   = esc_sql($wpdb->prefix . 'ayspoll_polls');
		$answer_table = esc_sql($wpdb->prefix . 'ayspoll_answers');
		if (isset($data["poll_action"]) && wp_verify_nonce($data["poll_action"], 'poll_action')) {
			$title                     = isset($data['ays-poll-title']) && $data['ays-poll-title'] != "" ? sanitize_text_field($data['ays-poll-title']) : "Default title";
			$show_title                = isset($data['show_title']) && $data['show_title'] == 'show' ? 1 : 0;
			$limit_users               = isset($data['apm_limit_users']) && $data['apm_limit_users'] == 'on' ? 1 : 0;
			$limit_users_method        = isset($data['ays_limit_method']) ? sanitize_text_field($data['ays_limit_method']) : 'ip';
			$limit_users_msg           = isset($data['ays_limitation_message']) ? wpautop($data['ays_limitation_message']) : "";
			$limit_users_url           = isset($data['ays_redirect_url']) ? wp_http_validate_url($data['ays_redirect_url']) : "";
			$limit_users_delay         = isset($data['ays_redirection_delay']) ? absint($data['ays_redirection_delay']) : 0;
			$limit_users_role_enable   = isset($data['ays_enable_restriction_pass']) && $data['ays_enable_restriction_pass'] == 'on' ? 1 : 0;
			$limit_users_role          = (isset($data["ays_users_roles"]) && !empty($data["ays_users_roles"])) ? $data["ays_users_roles"] : array();
			$limit_users_role_msg      = isset($data['restriction_pass_message']) ? wpautop($data['restriction_pass_message']) : "";
			$limit_users_logged_enable = (isset($data['ays_enable_logged_users']) && $data['ays_enable_logged_users'] == 'on') || $limit_users_role_enable ? 1 : 0;
			$limit_users_logged_msg    = isset($data['ays_enable_logged_users_message']) ? wpautop($data['ays_enable_logged_users_message']) : "";
			$hide_results              = isset($data['ays-poll-hide-results']) && 'hide' == $data['ays-poll-hide-results'] ? 1 : 0;
			$hide_result_message       = isset($data['ays_poll_result_message']) && 'hide' == $data['ays_poll_result_message'] ? 1 : 0;
			$hide_results_text         = stripcslashes($data['ays-poll-hide-results-text']);
			$ays_result_message        = wp_kses_post(stripcslashes($data['ays_result_message']));
			$allow_not_vote            = isset($data['ays-poll-allow-not-vote']) && 'allow' == $data['ays-poll-allow-not-vote'] ? 1 : 0;
			$show_social               = isset($data['ays-poll-show-social']) && 'show' == $data['ays-poll-show-social'] ? 1 : 0;
			$poll_social_buttons_heading = ( isset( $data[ 'ays_poll_social_buttons_heading' ] ) && $data[ 'ays_poll_social_buttons_heading' ] != '' ) ? stripslashes(wp_kses_post($data[ 'ays_poll_social_buttons_heading' ])) : '';
			$poll_show_social_ln       = isset($data['ays_poll_enable_linkedin_share_button'])  && $data['ays_poll_enable_linkedin_share_button']  == "on" ? "on" : "off";
			$poll_show_social_fb	   = isset($data['ays_poll_enable_facebook_share_button'])  && $data['ays_poll_enable_facebook_share_button']  == "on" ? "on" : "off";
			$poll_show_social_tr	   = isset($data['ays_poll_enable_twitter_share_button'])   && $data['ays_poll_enable_twitter_share_button']   == "on" ? "on" : "off";
			$poll_show_social_vk	   = isset($data['ays_poll_enable_vkontakte_share_button']) && $data['ays_poll_enable_vkontakte_share_button'] == "on" ? "on" : "off";
			$categories                = isset($data['ays-poll-categories']) ? ',' . implode(',', $data['ays-poll-categories']) . ',' : ',1,';
			$description               = isset($data['ays-poll-description']) && $data['ays-poll-description'] != '' ? sanitize_textarea_field($data['ays-poll-description']) : "";
			$type                      = isset($data['ays-poll-type']) ? sanitize_text_field($data['ays-poll-type']) : "";
			$question                  = wp_kses_post(stripcslashes($data['ays_poll_question']));
			$image = isset($data['ays_poll_image']) && $data['ays_poll_image'] != '' ? sanitize_url($data['ays_poll_image']): '';
			// if ($image != '') {
			// 	if ( !(filter_var($image, FILTER_VALIDATE_URL) && wp_http_validate_url($image)) ) {
			// 		// invalid URL, handle accordingly
			// 		$image = '';
			// 	}
			// }
			if( $image != "" ){
				$check_if_current_image_exists = Poll_Maker_Ays_Admin::ays_poll_check_if_current_image_exists($image);

				if( !$check_if_current_image_exists ){
					$image = "";
				}
			}

			$theme_id = isset($data['ays_poll_theme']) ? absint($data['ays_poll_theme']) : 1;
			$main_color = isset($data['ays_poll_main_color']) ? sanitize_text_field($data['ays_poll_main_color']) : '#000000';
			$text_color = isset($data['ays_poll_text_color']) ? sanitize_text_field($data['ays_poll_text_color']) : '#000000';
			$button_text_color = isset($data['ays_poll_button_text_color']) ? sanitize_text_field($data['ays_poll_button_text_color']) : '#ffffff';
			$button_bg_color = isset($data['ays_poll_button_bg_color']) ? sanitize_text_field($data['ays_poll_button_bg_color']) : '#000000';
			$icon_color = isset($data['ays_poll_icon_color']) ? sanitize_text_field($data['ays_poll_icon_color']) : '#000000';
			$bg_color = isset($data['ays_poll_bg_color']) ? sanitize_text_field($data['ays_poll_bg_color']) : '#ffffff';
			$answer_bg_color = isset($data['ays_poll_answer_bg_color']) ? sanitize_text_field($data['ays_poll_answer_bg_color']) : '#FBFEF9';
			$answer_hover_color = isset($data['ays_poll_answer_hover_color']) ? sanitize_text_field($data['ays_poll_answer_hover_color']) : $text_color;
			$answer_border_side      = isset($data['ays_poll_border_side']) ? sanitize_text_field($data['ays_poll_border_side']) : 'all_sides';
			$bg_image = isset($data['ays_poll_bg_image']) && $data['ays_poll_bg_image'] != '' ? sanitize_url($data['ays_poll_bg_image']): '';
			// if ($bg_image != '') {
			// 	if ( !(filter_var($bg_image, FILTER_VALIDATE_URL) && wp_http_validate_url($bg_image)) ) {
			// 		// invalid URL, handle accordingly
			// 		$bg_image = '';
			// 	}
			// }
			if( $bg_image != "" ){
				$check_if_current_image_exists = Poll_Maker_Ays_Admin::ays_poll_check_if_current_image_exists($bg_image);

				if( !$check_if_current_image_exists ){
					$bg_image = "";
				}
			}
			$randomize_answers       	   = !isset($data['randomize_answers']) ? "off" : $data['randomize_answers'];
			$enable_asnwers_sound    	   = !isset($data['ays_poll_enable_asnwers_sound']) ? "off" : $data['ays_poll_enable_asnwers_sound'];
			$icon_size               	   = absint($data['ays_poll_icon_size']) >= 10 ? absint($data['ays_poll_icon_size']) : 24;
			$width                   	   = absint($data['ays_poll_width']);
			$width_for_mobile        	   = ( isset( $data['ays_poll_width_for_mobile'] ) && $data['ays_poll_width_for_mobile'] != '' ) ? absint($data['ays_poll_width_for_mobile']) : 0;
			$btn_text                	   = sanitize_text_field($data['ays_poll_btn_text']);
			$see_res_btn_text        	   = sanitize_text_field($data['ays_poll_res_btn_text']);
			$border_style            	   = sanitize_text_field($data['ays_poll_border_style']);
			$border_radius           	   = sanitize_text_field($data['ays_poll_border_radius']);
			$border_width            	   = sanitize_text_field($data['ays_poll_border_width']);
			$box_shadow_color        	   = sanitize_text_field($data['ays_poll_box_shadow_color']);
			
			//Gradient
			$enable_background_gradient    = isset($data['ays_enable_background_gradient']) && $data['ays_enable_background_gradient'] == "on" ? sanitize_text_field($data['ays_enable_background_gradient']) : "off";
			$background_gradient_color_1   = sanitize_text_field($data['ays_background_gradient_color_1']);
			$background_gradient_color_2   = sanitize_text_field($data['ays_background_gradient_color_2']);
			$poll_gradient_direction       = sanitize_text_field($data['ays_poll_gradient_direction']);
			///

			// Redirect after submit
            $redirect_after_submit = ( isset( $data['ays_redirect_after_submit'] ) && $data['ays_redirect_after_submit'] == 'on' ) ? 1 : 0;

            $submit_redirect_url = !isset($data['ays_submit_redirect_url']) ? '' : $data['ays_submit_redirect_url'];
            // $submit_redirect_delay = !isset($data['ays_submit_redirect_delay']) ? '' : $data['ays_submit_redirect_delay'];
            ///

			$enable_box_shadow         = isset($data['ays_poll_enable_box_shadow']) && $data['ays_poll_enable_box_shadow'] == "on" ? sanitize_text_field($data['ays_poll_enable_box_shadow']) : "off";
			$enable_answer_style       = isset($data['ays_poll_enable_answer_style']) && $data['ays_poll_enable_answer_style'] == "on" ? sanitize_text_field($data['ays_poll_enable_answer_style']) : "off";
			$load_effect               = sanitize_text_field($data['ays-poll-load-effect']);
			$load_gif                  = isset($data['ays-poll-load-gif']) ? sanitize_text_field($data['ays-poll-load-gif']) : 'plg_default';
			$notify_on                 = isset($data['ays_notify_by_email_on']) && $data['ays_notify_by_email_on'] == 'on' ? 1 : 0;
			$notify_email              = isset($data['ays_notify_email']) && !empty($data['ays_notify_email']) ? sanitize_email($data['ays_notify_email']) : get_option('admin_email');
			$redirect_users            = isset($data['ays_redirect_after_vote']) && $data['ays_redirect_after_vote'] == 'on' ? 1 : 0;
			$redirect_after_vote_url   = isset($data['redirection_url']) ? wp_http_validate_url($data['redirection_url']) : "";
			$redirect_after_vote_delay = isset($data['redirection_delay']) ? absint($data['redirection_delay']) : 0;
			$result_sort_type          = sanitize_text_field($data['ays-poll-result-sort-type']);
			$poll_direction            = sanitize_text_field($data['ays_poll_direction']);
			$published                 = absint(intval($data['ays_publish']));
			$enable_pass_count         = !isset($data['ays_enable_pass_count']) ? null : $data['ays_enable_pass_count'];

			$activeInterval_full       = isset($data['ays-active']) ? $data['ays-active'] : "";
			$activeInterval_fullArr    = explode(" ",$activeInterval_full);
			$activeInterval            = isset($activeInterval_fullArr[0]) ? $activeInterval_fullArr[0] : "";
			$activeIntervalSec         = isset($activeInterval_fullArr[1]) ? $activeInterval_fullArr[1] : "";

			$deactiveInterval_full     = isset($data['ays-deactive']) ? $data['ays-deactive'] : "";
			$deactiveInterval_fullArr  = explode(" ",$deactiveInterval_full);
			$deactiveInterval          = isset($deactiveInterval_fullArr[0]) ? $deactiveInterval_fullArr[0] : "";
			$deactiveIntervalSec       = isset($deactiveInterval_fullArr[1]) ? $deactiveInterval_fullArr[1] : "";

			$active_date_message       = wpautop($data['active_date_message']);
			$active_date_message_soon  = wpautop($data['active_date_message_soon']);
			$css                       = stripcslashes($data['ays_custom_css']);
			$active_date_check         = isset($data['active_date_check']) && !empty($data['active_date_check']) ? $data['active_date_check'] : '';
			$enable_restart_button     = isset($data['ays_enable_restart_button']) && 'on' == $data['ays_enable_restart_button'] ? 1 : 0;
			$enable_vote_btn           = isset($data['ays_enable_vote_button']) && 1 == $data['ays_enable_vote_button'] ? 1 : 0;
			$show_votes_count          = isset($data['show_votes_count']) && 1 == $data['show_votes_count'] ? 1 : 0;
			$show_create_date          = isset($data['show_poll_creation_date']) && 1 == $data['show_poll_creation_date'] ? 1 : 0;
			$show_author          	   = isset($data['show_poll_author']) && 1 == $data['show_poll_author'] ? 1 : 0;
			$show_res_percent          = isset($data['show_res_percent']) && 1 == $data['show_res_percent'] ? 1 : 0;
			$show_result_btn_schedule  = isset($data['show_result_btn_schedule']) && 1 == $data['show_result_btn_schedule'] ? 1 : 0;
			$ays_poll_show_timer  	   = isset($data['ays_poll_show_timer']) && 1 == $data['ays_poll_show_timer'] ? 1 : 0;
			$show_bottom_timer  	   = isset($data['ays_show_bottom_timer']) && 1 == $data['ays_show_bottom_timer'] ? 1 : 0;
			$ays_show_timer_type  	   = isset($data['ays_show_timer_type']) && !empty($data['ays_show_timer_type']) ? $data['ays_show_timer_type'] : 'countdown';
			$ays_show_result_btn_see   = isset($data['ays_poll_show_result_btn_see_schedule']) && !empty($data['ays_poll_show_result_btn_see_schedule']) ? $data['ays_poll_show_result_btn_see_schedule'] : 'with_see';
			$info_form                 = isset($data['ays_poll_info_form']) && 'on' == $data['ays_poll_info_form'] ? 1 : 0;
			$form_fields               = isset($data['ays-poll-form-fields']) ? sanitize_text_field(implode(',', $data['ays-poll-form-fields'])) : "";
			$form_required_fields      = isset($data['ays-poll-form-required-fields']) ? sanitize_text_field(implode(',', $data['ays-poll-form-required-fields'])) : "";
			$info_form_title           = isset($data['ays-poll-info-form-text']) ? wpautop(wp_kses_post(stripslashes($data['ays-poll-info-form-text']))) : "";
			$title_bg_color            = sanitize_text_field($data['ays_poll_title_bg_color']);

			// Poll main URL
			$poll_main_url = (isset( $_POST['ays_poll_main_url'] ) && $_POST['ays_poll_main_url'] != '') ? wp_kses_post( $_POST['ays_poll_main_url'] ) : '';

			$changed_creation_date     = (isset($data['ays_poll_change_creation_date']) && $data['ays_poll_change_creation_date'] != '') ? $data['ays_poll_change_creation_date'] : current_time( 'mysql' ) ;

			$author = ( isset( $data['ays_poll_author'] ) && $data['ays_poll_author'] != '' ) ? stripslashes($data['ays_poll_author']) : '';

            // Change the author of the current poll
            $poll_create_author = ( isset($data['ays_poll_create_author']) && $data['ays_poll_create_author'] != "" ) ? absint( sanitize_text_field( $data['ays_poll_create_author'] ) ) : '';

            if ( $poll_create_author != "" && $poll_create_author > 0 ) {
                $user = get_userdata($poll_create_author);
                if ( ! is_null( $user ) && $user ) {
                    $poll_author = array(
                        'id' => $user->ID."",
                        'name' => $user->data->display_name
                    );

                    $author = json_encode($poll_author, JSON_UNESCAPED_SLASHES);
                } else {
                    $author_data = json_decode($author, true);
                    $poll_create_author = (isset( $author_data['id'] ) && $author_data['id'] != "") ? absint( sanitize_text_field( $author_data['id'] ) ) : get_current_user_id();
                }
            }

            // MailChimp
            $enable_mailchimp           = isset($data['ays_enable_mailchimp']) && $data['ays_enable_mailchimp'] == 'on' ? "on": "off";
            $mailchimp_list             = !isset($data['ays_mailchimp_list'])?"":$data['ays_mailchimp_list'];


            // Show login form for not logged in users
            $show_login_form      = (isset($data['ays_show_login_form']) && $data['ays_show_login_form'] == "on" && $limit_users_logged_enable == 1) ? 'on' : 'off';
            // Disable answer hover 
            $disable_answer_hover = (isset($data['ays_disable_answer_hover']) && $data['ays_disable_answer_hover'] == 'on') ? 1 : 0;

            $custom_class = (isset($data['ays_poll_custom_class']) && $data['ays_poll_custom_class'] != '') ? $data['ays_poll_custom_class'] : '';

            // Bg image positioning
            $poll_bg_image_position     = (isset($data['ays_poll_bg_image_position']) && $data['ays_poll_bg_image_position'] != "") ? $data['ays_poll_bg_image_position'] : 'center center';
            $poll_bg_img_in_finish_page = (isset($data['ays_poll_bg_img_in_finish_page']) && $data['ays_poll_bg_img_in_finish_page'] != "") ? $data['ays_poll_bg_img_in_finish_page'] : 'off';

            // Results bar in RGBA
            $result_in_rgba = (isset($data['ays-poll-res-rgba']) && $data['ays-poll-res-rgba'] == 'on' ) ? 'on' : 'off';

            // Enable View more button
            $enable_view_more_button     = (isset($data['ays_enable_view_more_button']) && $data['ays_enable_view_more_button'] == 'on' ) ? 'on' : 'off';
            $poll_view_more_button_count = (isset($data['ays_poll_view_more_button_count']) && $data['ays_poll_view_more_button_count'] != '' ) ? absint(intval($data['ays_poll_view_more_button_count'])) : 0;

            // Poll Min Height
			$poll_min_height = (isset($data['ays_poll_min_height']) && $data['ays_poll_min_height'] != '') ? absint(intval($data['ays_poll_min_height'])) : '';
			
			// Poll answer ordering
			$poll_answer_ordering   = (isset($data['ays_answers_sort_select']) && $data['ays_answers_sort_select'] != '') ? sanitize_text_field($data['ays_answers_sort_select']) : '';

			// Poll answers font size
			$poll_answer_font_size  = (isset($data['ays_answer_font_size']) && $data['ays_answer_font_size'] != '') ? sanitize_text_field($data['ays_answer_font_size']) : '16';
			
			// Poll answers font size on mobile
			$poll_answer_font_size_mobile  = (isset($data['ays_poll_answer_font_size_mobile']) && $data['ays_poll_answer_font_size_mobile'] != '') ? sanitize_text_field($data['ays_poll_answer_font_size_mobile']) : '16';
			
			// Poll show passed users in result page
			$poll_show_passed_users = (isset($data['ays_poll_show_users']) && sanitize_text_field($data['ays_poll_show_users']) == 'on') ? 'on' : 'off';
			$poll_show_passed_users_count = (isset($data['ays_poll_show_users_count']) && $data['ays_poll_show_users_count'] != '') ? intval(sanitize_text_field($data['ays_poll_show_users_count'])) : 3;

			// Poll Logo image
			$poll_logo_image = (isset($data['ays_poll_logo_image']) && $data['ays_poll_logo_image'] != '') ? sanitize_url($data['ays_poll_logo_image']) : '';
			// if ($poll_logo_image != '') {
			// 	if ( !(filter_var($poll_logo_image, FILTER_VALIDATE_URL) && wp_http_validate_url($poll_logo_image)) ) {
			// 		// invalid URL, handle accordingly
			// 		$poll_logo_image = '';
			// 	}
			// }
			if( $poll_logo_image != "" ){
				$check_if_current_image_exists = Poll_Maker_Ays_Admin::ays_poll_check_if_current_image_exists($poll_logo_image);

				if( !$check_if_current_image_exists ){
					$poll_logo_image = "";
				}
			}

			// Poll schedule container on/off
			$poll_show_container = (isset($data['ays_show_poll_container']) && $data['ays_show_poll_container'] == 'on') ? 'on' : 'off';

			// Poll see results button in limitations
			$poll_see_result_button = (isset($data['ays_see_result_show']) && $data['ays_see_result_show'] == 'on') ? 'on' : 'off';
			$poll_see_result_radio  = (isset($data['ays_poll_see_result_show']) && $data['ays_poll_see_result_show'] != '') ? sanitize_text_field($data['ays_poll_see_result_show']) : '';

			// loader font size 
			$poll_loader_font_size = (isset($data['ays_loader_font_size']) && $data['ays_loader_font_size'] != '') ? sanitize_text_field($data['ays_loader_font_size']) : '64';
			if(intval($poll_loader_font_size) <= 0){
				$poll_loader_font_size = '';
			}

			// Show answers numbering
			$show_answers_numbering = (isset($_POST['ays_poll_show_answers_numbering']) && sanitize_text_field( $_POST['ays_poll_show_answers_numbering']) != '') ? sanitize_text_field( $_POST['ays_poll_show_answers_numbering'] ) : 'none';

			// Poll border color
			$poll_border_color = (isset($data['ays_poll_border_color']) && $data['ays_poll_border_color'] != '') ? sanitize_text_field($data['ays_poll_border_color']) : '';

			// Poll load effect message
			$poll_effect_message = (isset($data['ays_poll_load_effect_message']) && $data['ays_poll_load_effect_message'] != '') ? sanitize_text_field($data['ays_poll_load_effect_message']) : '';

			// Box Shadow X offset
			$poll_box_shadow_x_offset = (isset($data['ays_poll_box_shadow_x_offset']) && $data['ays_poll_box_shadow_x_offset'] != '' ) ? intval( sanitize_text_field( $data['ays_poll_box_shadow_x_offset'] ) ) : 0;

			// Box Shadow Y offset
			$poll_box_shadow_y_offset = (isset($data['ays_poll_box_shadow_y_offset']) && $data['ays_poll_box_shadow_y_offset'] != '' ) ? intval( sanitize_text_field( $data['ays_poll_box_shadow_y_offset'] ) ) : 0;

			// Box Shadow Z offset
			$poll_box_shadow_z_offset = (isset($data['ays_poll_box_shadow_z_offset']) && $data['ays_poll_box_shadow_z_offset'] != '' ) ? intval( sanitize_text_field( $data['ays_poll_box_shadow_z_offset'] ) ) : 15;

			// Vote Reason
			$poll_vote_reason = (isset($data['ays-poll-reason']) && $data['ays-poll-reason'] == 'on' ) ? "on" : "off";

			// Allow multivote
			$poll_allow_multivote   = isset($data['ays_poll_allow_multivote']) && $data['ays_poll_allow_multivote'] == 'on' ? "on" : "off";
			$multivote_answer_min_count = (isset($data['ays_poll_multivote_min_count']) && $data['ays_poll_multivote_min_count'] != '') ? sanitize_text_field($data['ays_poll_multivote_min_count']) : '';
			$poll_multivote_answer_count = (isset($data['ays_poll_multivote_count']) && $data['ays_poll_multivote_count'] != '') ? sanitize_text_field($data['ays_poll_multivote_count']) : '';

			// Allow collect user info
			$poll_allow_collecting_users_data = (isset($data['ays_allow_collecting_logged_in_users_data']) && $data['ays_allow_collecting_logged_in_users_data'] == 'on') ? 'on' : 'off';

			// Answer Icon
			$poll_answer_icon_check = isset($data['ays_poll_show_answers_icon']) && $data['ays_poll_show_answers_icon'] == "on" ? "on" : "off"; 
			$poll_answer_icon       = isset($data['ays_poll_answer_icon']) && $data['ays_poll_answer_icon'] != "" ? sanitize_text_field($data['ays_poll_answer_icon']) : "radio"; 
			
			// Every Answer redirect delay
			$poll_every_answer_redirect_delay = isset($data['ays_poll_answer_redirect_delay']) && $data['ays_poll_answer_redirect_delay'] != "" ? sanitize_text_field($data['ays_poll_answer_redirect_delay']) : ""; 
			$poll_enable_every_answer_redirect_delay = isset($data['ays_poll_enable_answer_redirect_delay']) && $data['ays_poll_enable_answer_redirect_delay'] == "on" ? "on" : "off"; 
			
			// Show Answers image after voting
			if(isset($data['ays-poll-answers-images']) && empty(array_filter($data['ays-poll-answers-images']))){
				$data['ays_poll_enable_answer_image_after_voting'] = 'off';
			}			
			$poll_enable_answer_image_after_voting = isset($data['ays_poll_enable_answer_image_after_voting']) && $data['ays_poll_enable_answer_image_after_voting'] == "on" ? "on" : "off"; 

			// Poll logo image url
			$poll_logo_image_url       = isset($data['ays_poll_logo_image_url']) && $data['ays_poll_logo_image_url'] != "" ? sanitize_text_field($data['ays_poll_logo_image_url']) : ""; 
			$poll_logo_image_url_check = isset($data['ays_poll_logo_enable_image_url']) && $data['ays_poll_logo_enable_image_url'] == "on" ? "on" : "off"; 

			// Poll Logo title
            $poll_logo_title = (isset( $data['ays_poll_logo_title'] ) && $data['ays_poll_logo_title'] != '') ? sanitize_text_field( $data['ays_poll_logo_title'] ) : '';

			// Poll question font size
			$poll_question_font_size_pc     = isset($data['ays_poll_answers_font_size_pc']) && $data['ays_poll_answers_font_size_pc'] != "" ? sanitize_text_field($data['ays_poll_answers_font_size_pc']) : "16"; 
			$poll_question_font_size_mobile = isset($data['ays_poll_answers_font_size_mobile']) && $data['ays_poll_answers_font_size_mobile'] != "" ? sanitize_text_field($data['ays_poll_answers_font_size_mobile']) : "16"; 

			// Poll question image height
			$poll_question_image_height     = isset($data['ays_poll_question_image_height']) && $data['ays_poll_question_image_height'] != "" ? abs(sanitize_text_field($data['ays_poll_question_image_height'])) : ""; 

			// Poll question object fit
			$poll_question_image_object_fit = isset($data['ays_poll_question_image_object_fit']) && $data['ays_poll_question_image_object_fit'] != "" ? sanitize_text_field($data['ays_poll_question_image_object_fit']) : "cover"; 

			// Poll container max-width for mobile
			$poll_mobile_max_width = (isset($data['ays_poll_mobile_max_width']) && $data['ays_poll_mobile_max_width'] != "") ? abs(sanitize_text_field($data['ays_poll_mobile_max_width'])) : '';

			// ==== Button Styles =====
			// Buttons size
			$poll_buttons_size = (isset($data['ays_poll_buttons_size']) && $data['ays_poll_buttons_size'] != "") ? sanitize_text_field($data['ays_poll_buttons_size']) : 'medium';

			// Buttons font size
			$poll_buttons_font_size = (isset($data['ays_poll_buttons_font_size']) && $data['ays_poll_buttons_font_size'] != "") ? sanitize_text_field($data['ays_poll_buttons_font_size']) : '17';

			// Buttons mobile font size
			$poll_buttons_mobile_font_size = (isset($data['ays_poll_buttons_mobile_font_size']) && $data['ays_poll_buttons_mobile_font_size'] != "") ? sanitize_text_field($data['ays_poll_buttons_mobile_font_size']) : '17';

			// Buttons Left / Right padding
			$poll_buttons_left_right_padding = (isset($data['ays_poll_buttons_left_right_padding']) && $data['ays_poll_buttons_left_right_padding'] != "") ? sanitize_text_field($data['ays_poll_buttons_left_right_padding']) : '20';

			// Buttons Top / Bottom padding
			$poll_buttons_top_bottom_padding = (isset($data['ays_poll_buttons_top_bottom_padding']) && $data['ays_poll_buttons_top_bottom_padding'] != "") ? sanitize_text_field($data['ays_poll_buttons_top_bottom_padding']) : '10';

			// Buttons padding
			$poll_buttons_border_radius = (isset($data['ays_poll_buttons_border_radius']) && $data['ays_poll_buttons_border_radius'] != "") ? sanitize_text_field($data['ays_poll_buttons_border_radius']) : '3';

			// Buttons width
            $poll_buttons_width = (isset($data['ays_poll_buttons_width']) && sanitize_text_field( $data['ays_poll_buttons_width'] ) != "") ? sanitize_text_field( $data['ays_poll_buttons_width'] ) : '';
			
			// Buttons mobile width
            $poll_buttons_mobile_width = (isset($data['ays_poll_buttons_mobile_width']) && sanitize_text_field( $data['ays_poll_buttons_mobile_width'] ) != "") ? sanitize_text_field( $data['ays_poll_buttons_mobile_width'] ) : $poll_buttons_width;
            // ==== ====
			
			// ==== Allow Answer options ====
			// Allow custom answer
			$poll_allow_answer = (isset($data['ays_poll_allow_add_answers']) && $data['ays_poll_allow_add_answers'] == "on") ? "on" : "off";
			// Require admin approval

			$poll_allow_answer_require = (isset($data['ays_poll_allow_answer_require']) && $data['ays_poll_allow_answer_require'] == "on") ? "on" : "off";

			// ==== ====

			// Poll answer view type
			$poll_answer_view_type = (isset($data['ays_poll_choose_answer_type']) && $data['ays_poll_choose_answer_type'] != "") ? sanitize_text_field($data['ays_poll_choose_answer_type']) : "list";

			// Poll answer box shadow
			// Poll answer box shadow enable
			$poll_answer_enable_box_shadow = (isset($data['ays_poll_answers_box_shadow_enable']) && $data['ays_poll_answers_box_shadow_enable'] == "on") ? "on" : "off";
			// Poll answer box shadow color
			$poll_answer_box_shadow_color  = (isset($data['ays_poll_answers_box_shadow_color']) && $data['ays_poll_answers_box_shadow_color'] != "") ? sanitize_text_field($data['ays_poll_answers_box_shadow_color']) : "#000000";

			$poll_answer_box_shadow_x_offset  = (isset($data['ays_poll_answer_box_shadow_x_offset']) && $data['ays_poll_answer_box_shadow_x_offset'] != "") ? intval($data['ays_poll_answer_box_shadow_x_offset']) : 0;

			$poll_answer_box_shadow_y_offset  = (isset($data['ays_poll_answer_box_shadow_y_offset']) && $data['ays_poll_answer_box_shadow_y_offset'] != "") ? intval($data['ays_poll_answer_box_shadow_y_offset']) : 0;

			$poll_answer_box_shadow_z_offset  = (isset($data['ays_poll_answer_box_shadow_z_offset']) && $data['ays_poll_answer_box_shadow_z_offset'] != "") ? intval($data['ays_poll_answer_box_shadow_z_offset']) : 10;
			//

			// Poll answer image height
			$poll_answer_image_height = (isset($data['ays_poll_answer_img_height']) && $data['ays_poll_answer_img_height'] != "") ? sanitize_text_field($data['ays_poll_answer_img_height']) : "150";
			if($poll_answer_image_height == '0'){
				$poll_answer_image_height = "150";
			}

			// Poll answer image height for mobile
			$poll_answer_image_height_for_mobile = (isset($data['ays_poll_answer_image_height_for_mobile']) && $data['ays_poll_answer_image_height_for_mobile'] != "") ? sanitize_text_field($data['ays_poll_answer_image_height_for_mobile']) : "150";
			if($poll_answer_image_height_for_mobile == '0'){
				$poll_answer_image_height_for_mobile = "150";
			}

			// Poll answer image border radius
			$poll_answer_image_border_radius = (isset($data['ays_poll_answer_image_border_radius']) && $data['ays_poll_answer_image_border_radius'] != "") ? sanitize_text_field($data['ays_poll_answer_image_border_radius']) : 0;

			// Poll answer image object fit
			$poll_answer_object_fit   = (isset($data['ays_poll_image_background_size']) && $data['ays_poll_image_background_size'] != "") ? sanitize_text_field($data['ays_poll_image_background_size']) : "cover";

			// Poll answer padding
			$poll_answer_padding      = (isset($data['ays_poll_answers_padding']) && $data['ays_poll_answers_padding'] != "") ? sanitize_text_field($data['ays_poll_answers_padding']) : "10";

			// Poll answer gap
			$poll_answer_margin      = (isset($data['ays_poll_answers_margin']) && $data['ays_poll_answers_margin'] != "") ? sanitize_text_field($data['ays_poll_answers_margin']) : "10";
			
			// Poll answer border radius
			$poll_answer_border_radius = (isset($data['ays_poll_answer_border_radius']) && $data['ays_poll_answer_border_radius'] != "") ? absint(intval($data['ays_poll_answer_border_radius'])) : 0;

			// Poll title font size 
			$poll_title_font_size    = (isset($data['ays_poll_title_font_size']) && $data['ays_poll_title_font_size'] != "" && $data['ays_poll_title_font_size'] > 0) ? sanitize_text_field($data['ays_poll_title_font_size']) : "20";

			// Poll title font size for mobile
			$poll_title_font_size_mobile    = (isset($data['ays_poll_title_font_size_mobile']) && $data['ays_poll_title_font_size_mobile'] != "" && $data['ays_poll_title_font_size_mobile'] > 0) ? sanitize_text_field($data['ays_poll_title_font_size_mobile']) : "20";

			// Poll title alignment
			$poll_title_alignment    = ( isset($data['ays_poll_title_alignment']) && $data['ays_poll_title_alignment'] != "" ) ? sanitize_text_field($data['ays_poll_title_alignment']) : "center";

			// Poll title alignment mobile
			$poll_title_alignment_mobile = ( isset($data['ays_poll_title_alignment_mobile']) && $data['ays_poll_title_alignment_mobile'] != "" ) ? sanitize_text_field($data['ays_poll_title_alignment_mobile']) : "center";

			// ===== Poll text type options start =====
			$poll_text_type_length_enable = ( isset($data['ays_poll_enable_question_length']) && $data['ays_poll_enable_question_length'] != "" ) ? sanitize_text_field($data['ays_poll_enable_question_length']) : "off";
			$poll_text_type_limit_type    = ( isset($data['ays_poll_question_limit_text_type']) && $data['ays_poll_question_limit_text_type'] != "" ) ? sanitize_text_field($data['ays_poll_question_limit_text_type']) : "characters";
			$poll_text_type_limit_length  = ( isset($data['ays_poll_question_text_max_length']) && $data['ays_poll_question_text_max_length'] != "" ) ? sanitize_text_field($data['ays_poll_question_text_max_length']) : "";
			$poll_text_type_limit_message = ( isset($data['ays_poll_question_enable_text_message']) && $data['ays_poll_question_enable_text_message'] != "" ) ? sanitize_text_field($data['ays_poll_question_enable_text_message']) : "off";
			$poll_text_type_placeholder   = ( isset($data['ays_poll_text_type_placeholder']) && $data['ays_poll_text_type_placeholder'] != "" ) ? sanitize_text_field($data['ays_poll_text_type_placeholder']) : "";			
			$poll_text_type_width         = ( isset($data['ays_poll_text_type_width']) && $data['ays_poll_text_type_width'] != "" ) ? absint(intval(sanitize_text_field($data['ays_poll_text_type_width']))) : "";			
			$poll_text_type_width_type    = ( isset($data['ays_poll_text_type_width_type']) && $data['ays_poll_text_type_width_type'] != "" ) ? sanitize_text_field($data['ays_poll_text_type_width_type']) : "percent";			
			// ===== Poll text type options end =====
			
			$poll_enable_password = ( isset($data['ays_poll_enable_password']) && $data['ays_poll_enable_password'] == "on" ) ? "on" : "off";
			$poll_password = ( isset($data['ays_poll_password']) && $data['ays_poll_password'] != "" ) ? sanitize_text_field($data['ays_poll_password']) : "";
			// Enable toggle password visibility
			$poll_enable_password_visibility = (isset($data['ays_poll_enable_password_visibility']) && $data['ays_poll_enable_password_visibility'] == 'on') ? 'on' : 'off';
			$poll_password_message = ( isset($data['ays_poll_password_message']) && $data['ays_poll_password_message'] != "" ) ? wp_kses_post($data['ays_poll_password_message']) : "Please enter password";

			// Add post for poll
			$ays_add_post_for_poll = isset($data['ays_add_post_for_poll']) && $data['ays_add_post_for_poll'] == 'on' ? 'on' : 'off';

			//Title Text Shadow
			$enable_poll_title_text_shadow = ( isset( $data['ays_poll_enable_title_text_shadow'] ) && $data['ays_poll_enable_title_text_shadow'] != '' ) ? 'on' : 'off';

			// Enable/disable grid column layout for answers on mobile devices
			$answers_grid_column_mobile = (isset($data['ays_answers_grid_column_mobile']) && $data['ays_answers_grid_column_mobile'] == 'on') ? 'on' :'off';


			$poll_title_text_shadow = ( isset($data['ays_poll_title_text_shadow_color'] ) && $data['ays_poll_title_text_shadow_color'] != '' ) ? sanitize_text_field( $data['ays_poll_title_text_shadow_color'] ) : 'rgba(255,255,255,0)';
			$poll_title_text_shadow_x_offset = (isset($data['ays_poll_title_text_shadow_x_offset']) && $data['ays_poll_title_text_shadow_x_offset'] != '') ? intval( $data['ays_poll_title_text_shadow_x_offset'] )  : 2;

			$poll_title_text_shadow_y_offset = (isset($data['ays_poll_title_text_shadow_y_offset']) && $data['ays_poll_title_text_shadow_y_offset'] != '') ? intval( $data['ays_poll_title_text_shadow_y_offset'] ) : 2;
	
			$poll_title_text_shadow_z_offset = (isset($data['ays_poll_title_text_shadow_z_offset']) && $data['ays_poll_title_text_shadow_z_offset'] != '') ? intval( $data['ays_poll_title_text_shadow_z_offset'] ) : 0;
	
			//Display form fields labels
            $display_fields_labels = ( isset($data['ays_poll_display_fields_labels']) && sanitize_text_field( $data['ays_poll_display_fields_labels'] ) == 'on' ) ? 'on' : 'off';

			// Autofill users data
            $autofill_user_data = ( isset($data['ays_poll_autofill_user_data']) && sanitize_text_field( $data['ays_poll_autofill_user_data'] ) == 'on' ) ? 'on' : 'off';

			//Open Logo Url in new tab
			$poll_logo_url_new_tab = (isset( $data[ 'ays_poll_logo_enable_image_url_new_tab' ] ) && $data[ 'ays_poll_logo_enable_image_url_new_tab' ] == 'on') ? "on" : 'off';

			// Social Media links
            $enable_social_links = (isset($data['ays_poll_enable_social_links']) && sanitize_text_field( $data['ays_poll_enable_social_links'] ) == "on") ? 'on' : 'off';
            $poll_social_links_heading = ( isset( $data[ 'ays_poll_social_links_heading' ] ) && $data[ 'ays_poll_social_links_heading' ] != '' ) ? stripslashes(wp_kses_post($data[ 'ays_poll_social_links_heading' ])) : '';
            $ays_social_links = (isset($data['ays_poll_social_links'])) ? array_map( 'sanitize_text_field', $data['ays_poll_social_links'] ) : array(
                'linkedin_link' => '',
                'facebook_link' => '',
                'twitter_link' => '',
                'vkontakte_link' => '',
                'youtube_link' => '',
            );
            
            $linkedin_link = isset($ays_social_links['ays_linkedin_link']) && sanitize_text_field( $ays_social_links['ays_linkedin_link'] ) != '' ? sanitize_text_field( $ays_social_links['ays_linkedin_link'] ) : '';
            $facebook_link = isset($ays_social_links['ays_facebook_link']) && sanitize_text_field( $ays_social_links['ays_facebook_link'] ) != '' ? sanitize_text_field( $ays_social_links['ays_facebook_link'] ) : '';
            $twitter_link = isset($ays_social_links['ays_twitter_link']) && sanitize_text_field( $ays_social_links['ays_twitter_link'] ) != '' ? sanitize_text_field( $ays_social_links['ays_twitter_link'] ) : '';
            $vkontakte_link = isset($ays_social_links['ays_vkontakte_link']) && sanitize_text_field( $ays_social_links['ays_vkontakte_link'] ) != '' ? sanitize_text_field( $ays_social_links['ays_vkontakte_link'] ) : '';
            $youtube_link = isset($ays_social_links['ays_youtube_link']) && sanitize_text_field( $ays_social_links['ays_youtube_link'] ) != '' ? sanitize_text_field( $ays_social_links['ays_youtube_link'] ) : '';
            
            $social_links = array(
                'linkedin_link'     => $linkedin_link,
                'facebook_link'     => $facebook_link,
                'twitter_link'      => $twitter_link,
                'vkontakte_link'    => $vkontakte_link,
                'youtube_link'    	=> $youtube_link,
            );

            $show_chart_type = (isset($_POST['ays_poll_show_result_chart']) && sanitize_text_field( $_POST['ays_poll_show_result_chart'] ) != "") ? sanitize_text_field( $_POST['ays_poll_show_result_chart'] ) : 'default_bar_chart';
			$show_chart_type_google_height = (isset($_POST['ays_poll_show_result_chart_google_height']) && $_POST['ays_poll_show_result_chart_google_height'] != "" && $_POST['ays_poll_show_result_chart_google_height'] != 0) ? absint( intval($_POST['ays_poll_show_result_chart_google_height']) ) : 400;

			$styles = json_encode(array(
				// Style tab start
				'main_color'                        => $main_color,
				'text_color'                        => $text_color,
				'button_text_color'                 => $button_text_color,
				'button_bg_color'                   => $button_bg_color,
				'icon_color'                        => $icon_color,
				'bg_color'                          => $bg_color,
				'bg_image'                          => $bg_image,
				'enable_answer_style'               => $enable_answer_style,
				'answer_bg_color'                   => $answer_bg_color,
				'answer_hover_color'                => $answer_hover_color,
				'answer_border_side'                => $answer_border_side,
				"answer_font_size"			        => $poll_answer_font_size,
				"poll_answer_font_size_mobile"		=> $poll_answer_font_size_mobile,
				'poll_answer_object_fit'            => $poll_answer_object_fit,
				'poll_answer_padding'               => $poll_answer_padding,
				'poll_answer_margin'                => $poll_answer_margin,
				'poll_answer_border_radius'		    => $poll_answer_border_radius,
				'poll_answer_icon_check' 		    => $poll_answer_icon_check,
				'poll_answer_icon' 		  		    => $poll_answer_icon,
				'poll_answer_view_type'             => $poll_answer_view_type,
				'poll_answer_enable_box_shadow'		=> $poll_answer_enable_box_shadow,
				'poll_answer_box_shadow_color'		=> $poll_answer_box_shadow_color,
				'poll_answer_box_shadow_x_offset'	=> $poll_answer_box_shadow_x_offset,
				'poll_answer_box_shadow_y_offset'	=> $poll_answer_box_shadow_y_offset,
				'poll_answer_box_shadow_z_offset'	=> $poll_answer_box_shadow_z_offset,
				'title_bg_color'                    => $title_bg_color, //aray
				'poll_title_font_size'              => $poll_title_font_size,
				'poll_title_font_size_mobile'       => $poll_title_font_size_mobile,
				'icon_size'                         => $icon_size,
				'width'                             => $width,
				'width_for_mobile'                  => $width_for_mobile,
                "poll_min_height"			        => $poll_min_height,
				'border_style'                      => $border_style,
				'border_radius'                     => $border_radius,
				'border_width'                      => $border_width,
				"border_color"                      => $poll_border_color,
				'enable_box_shadow'                 => $enable_box_shadow,
				'box_shadow_color'                  => $box_shadow_color,
				"poll_box_shadow_x_offset"          => $poll_box_shadow_x_offset,
				"poll_box_shadow_y_offset"          => $poll_box_shadow_y_offset,
				"poll_box_shadow_z_offset"          => $poll_box_shadow_z_offset,
                'enable_background_gradient'        => $enable_background_gradient,
                'background_gradient_color_1'       => $background_gradient_color_1,
                'background_gradient_color_2'       => $background_gradient_color_2,
                'poll_gradient_direction'           => $poll_gradient_direction,
    			'poll_question_size_pc'  			=> $poll_question_font_size_pc,
    			'poll_question_size_mobile'			=> $poll_question_font_size_mobile,
				'poll_question_image_height'	    => $poll_question_image_height,
    			'poll_question_image_object_fit'	=> $poll_question_image_object_fit,
    			'poll_mobile_max_width'	            => $poll_mobile_max_width,
				'poll_buttons_size'                 => $poll_buttons_size,
                'poll_buttons_font_size'            => $poll_buttons_font_size,
                'poll_buttons_mobile_font_size'     => $poll_buttons_mobile_font_size,
                'poll_buttons_left_right_padding'   => $poll_buttons_left_right_padding,
                'poll_buttons_top_bottom_padding'   => $poll_buttons_top_bottom_padding,
				'poll_buttons_border_radius'        => $poll_buttons_border_radius,
				'poll_buttons_width'         		=> $poll_buttons_width,
				'poll_buttons_mobile_width'         => $poll_buttons_mobile_width,
                "disable_answer_hover"              => $disable_answer_hover,
                "logo_image"       			        => $poll_logo_image,
    			'poll_enable_logo_url'              => $poll_logo_image_url_check,
    			'poll_logo_title'              		=> $poll_logo_title,
    			'poll_logo_url'    					=> $poll_logo_image_url,
                "custom_class"        		        => $custom_class,
                "enable_poll_title_text_shadow"     => $enable_poll_title_text_shadow,
                "poll_title_text_shadow"        	=> $poll_title_text_shadow,
                "poll_title_text_shadow_x_offset"   => $poll_title_text_shadow_x_offset,
                "poll_title_text_shadow_y_offset"   => $poll_title_text_shadow_y_offset,
                "poll_title_text_shadow_z_offset"   => $poll_title_text_shadow_z_offset,

				// Settings tab start
                "poll_allow_multivote"		        => $poll_allow_multivote,
				"multivote_answer_min_count"        => $multivote_answer_min_count,
				"poll_allow_multivote_count"        => $poll_multivote_answer_count,
				'poll_direction'                    => $poll_direction,
				'show_create_date'                  => $show_create_date,
				'show_author'            	        => $show_author,
				'active_date_check'                 => $active_date_check,
				'activeInterval'                    => $activeInterval,
				'activeIntervalSec'                 => $activeIntervalSec,
				'deactiveInterval'         	        => $deactiveInterval,
				'deactiveIntervalSec'               => $deactiveIntervalSec,
				'ays_poll_show_timer'    	        => $ays_poll_show_timer,
				'show_bottom_timer'    	            => $show_bottom_timer,
				'ays_show_timer_type'    	        => $ays_show_timer_type,
				'show_result_btn_see_schedule'    	=> $ays_show_result_btn_see,
				'active_date_message_soon'          => $active_date_message_soon,
				'active_date_message'               => $active_date_message,
				'show_result_btn_schedule'          => $show_result_btn_schedule,
                "dont_show_poll_cont"     	        => $poll_show_container,
				'allow_not_vote'                    => $allow_not_vote,
				'see_res_btn_text'                  => $see_res_btn_text,
				'enable_pass_count'                 => $enable_pass_count,
				'redirect_users'                    => $redirect_users,
				'redirect_after_vote_url'           => $redirect_after_vote_url,
				'redirection_delay'                 => $limit_users_delay,
				'randomize_answers'                 => $randomize_answers,
				'enable_asnwers_sound'              => $enable_asnwers_sound,
                "poll_vote_reason"   		        => $poll_vote_reason,
				'enable_vote_btn'                   => $enable_vote_btn,
				'btn_text'                          => $btn_text,
                "enable_view_more_button"	        => $enable_view_more_button,
                "poll_view_more_button_count"       => $poll_view_more_button_count,
                "answer_sort_type"			        => $poll_answer_ordering,
                "show_answers_numbering"            => $show_answers_numbering,
				// Settings tab end
				'hide_results'                      => $hide_results,
				'hide_result_message'               => $hide_result_message,
				'hide_results_text'                 => $hide_results_text,
				'result_message'          	        => $ays_result_message,
				'show_social'                       => $show_social,
				'poll_social_buttons_heading'		=> $poll_social_buttons_heading,
				'poll_show_social_ln'               => $poll_show_social_ln,
				'poll_show_social_fb'				=> $poll_show_social_fb,
				'poll_show_social_tr'				=> $poll_show_social_tr,
				'poll_show_social_vk'				=> $poll_show_social_vk,
				'load_effect'                       => $load_effect,
				'load_gif'                          => $load_gif,
				'limit_users'                       => $limit_users,
				'limit_users_method'                => $limit_users_method,
				'limitation_message'                => $limit_users_msg,
				'redirect_url'                      => $limit_users_url,
				'user_role'                         => $limit_users_role,
				'enable_restriction_pass'           => $limit_users_role_enable,
				'restriction_pass_message'          => $limit_users_role_msg,
				'enable_logged_users'               => $limit_users_logged_enable,
				'enable_logged_users_message'       => $limit_users_logged_msg,
				'notify_email_on'                   => $notify_on,
				'notify_email'                      => $notify_email,
				'result_sort_type'                  => $result_sort_type,
				'create_date'                       => $changed_creation_date,
				'author'                            => $author,
				'poll_main_url'                     => $poll_main_url,
				'redirect_after_vote_delay'         => $redirect_after_vote_delay,
				'published'                         => $published,
				'enable_restart_button'             => $enable_restart_button,
				'show_votes_count'                  => $show_votes_count,
				'show_res_percent'                  => $show_res_percent,
				'show_login_form'                   => $show_login_form,
				'info_form'                         => $info_form,
				'fields'                            => $form_fields,
				"answers_grid_column_mobile"  		=> $answers_grid_column_mobile,
				'required_fields'                   => $form_required_fields,
				'info_form_title'                   => $info_form_title,
                'enable_mailchimp'                  => $enable_mailchimp,
                'redirect_after_submit'             => $redirect_after_submit,
                'mailchimp_list'                    => $mailchimp_list,
                "users_role"                        => json_encode($limit_users_role),
                "poll_bg_image_position"	        => $poll_bg_image_position,
                "poll_bg_img_in_finish_page"        => $poll_bg_img_in_finish_page,
				'ays_add_post_for_poll'       	    => $ays_add_post_for_poll,
                "result_in_rgba"			        => $result_in_rgba,
                "show_passed_users"			        => $poll_show_passed_users,
                "see_result_button"     	        => $poll_see_result_button,
                "see_result_radio"     	            => $poll_see_result_radio,
                "loader_font_size"     	            => $poll_loader_font_size,
                "effect_message"                    => $poll_effect_message,
                
                "poll_allow_collecting_users_data"  => $poll_allow_collecting_users_data,
    			'poll_every_answer_redirect_delay'  => $poll_every_answer_redirect_delay,
    			'poll_enable_answer_image_after_voting' => $poll_enable_answer_image_after_voting,
    			'poll_enable_answer_redirect_delay' => $poll_enable_every_answer_redirect_delay,
    			'poll_show_passed_users_count'      => $poll_show_passed_users_count,
				'poll_allow_answer'                 => $poll_allow_answer,
				'poll_allow_answer_require'         => $poll_allow_answer_require,
				'poll_answer_image_height'          => $poll_answer_image_height,
				'poll_answer_image_height_for_mobile' => $poll_answer_image_height_for_mobile,
				'poll_answer_image_border_radius'   => $poll_answer_image_border_radius,
				'poll_title_alignment'              => $poll_title_alignment,
				'poll_title_alignment_mobile'       => $poll_title_alignment_mobile,
				// Text type options
				'poll_text_type_length_enable'      => $poll_text_type_length_enable,
				'poll_text_type_limit_type'         => $poll_text_type_limit_type,
				'poll_text_type_limit_length'       => $poll_text_type_limit_length,
				'poll_text_type_limit_message'      => $poll_text_type_limit_message,
				'poll_text_type_placeholder'        => $poll_text_type_placeholder,
				'poll_text_type_width'              => $poll_text_type_width,
				'poll_text_type_width_type'         => $poll_text_type_width_type,

				'poll_enable_password'    		    => $poll_enable_password,
				'poll_password'         			=> $poll_password,
				'poll_enable_password_visibility'   => $poll_enable_password_visibility,
				'poll_password_message'             => $poll_password_message,
				'display_fields_labels'         	=> $display_fields_labels,
				'autofill_user_data'         		=> $autofill_user_data,
				'poll_create_author'         		=> $poll_create_author,
				'poll_logo_url_new_tab'         	=> $poll_logo_url_new_tab,
				'enable_social_links'               => $enable_social_links,
				'poll_social_links_heading'			=> $poll_social_links_heading,
                'social_links'                      => $social_links,
                'show_chart_type'                   => $show_chart_type,
                'show_chart_type_google_height'     => $show_chart_type_google_height,
			));
			
			$poll_answers_img = array();
			$answers = array();
			switch ( $type ) {
				case 'choosing':
					$view_type        = isset( $data['ays_poll_choose_answer_type'] ) ? sanitize_text_field($data['ays_poll_choose_answer_type'] ) : "list";
					$poll_answers_img = isset($data['ays-poll-answers-images']) && !empty($data['ays-poll-answers-images']) ? $data['ays-poll-answers-images'] : $poll_answers_img;
					$answers          = isset($data['ays-poll-answers']) && !empty($data['ays-poll-answers']) ? $data['ays-poll-answers'] : array();
					$answers_ids      = $data['ays-poll-answers-ids'];
					$answers_url      = $data['ays_submit_redirect_url'];
					$answers_show_added = isset($data['ays_poll_show_user_added']) && !empty( $data['ays_poll_show_user_added']) ? $data['ays_poll_show_user_added'] : array(1,1);
					break;
				case 'voting':
					$view_type = isset($data['ays-poll-vote-type']) ? sanitize_text_field($data['ays-poll-vote-type']) : "";
					$answers   = array(1, -1);
					$answers_show_added = array(1,1);
					break;
				case 'rating':
					$view_type  = isset($data['ays-poll-rate-type']) ? sanitize_text_field($data['ays-poll-rate-type']) : "";
					$rate_value = $data['ays-poll-rate-value'];
					$answers    = range(1, $rate_value);
					$answers_show_added = array(1,1);
					break;
				case 'text':
					$view_type  = isset($data['ays_poll_text_type']) ? sanitize_text_field($data['ays_poll_text_type']) : "";
					break;
				default:
					# code...
					break;
			}
			if (0 == $id) {
				$poll_result = $wpdb->insert(
					$poll_table,
					array(
						'title'       => $title,
						'description' => $description,
						'type'        => $type,
						'question'    => $question,
						'view_type'   => $view_type,
						'categories'  => $categories,
						'image'       => $image,
						'show_title'  => $show_title,
						'styles'      => $styles,
						'custom_css'  => $css,
						'theme_id'    => $theme_id,
					),
					array(
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
						'%s',
						'%d',
					)
				);
				$last_id     = $wpdb->insert_id;
				$answer_count = isset($answers) && is_array($answers) ? count($answers) : 0;
				$empty_answers_count = 0;
				$not_empty_answers_count = 0;
				foreach ( $answers as $k => $answer ) {
					if (empty($answer)) {
						$empty_answers_count++;
						continue;
					}
					else{
						$not_empty_answers_count++;
					}

					$wpdb->insert(
						$answer_table,
						array(
							'poll_id'   		=> $last_id,
							'answer'    		=> wp_filter_kses($answer),
							'redirect'  		=> isset($answers_url[$k]) && $answers_url[$k] != "" ? sanitize_url($answers_url[$k]) : "",
							'ordering'  		=> ($k + 1),
							'show_user_added' 	=> isset($answers_show_added[$k]) ? $answers_show_added[$k] : 1,
							'answer_img' 		=> ($type == 'choosing' && isset($poll_answers_img[$k])) ? wp_http_validate_url($poll_answers_img[$k]) : "",
						),
						array(
							'%d', // poll_id
							'%s', // answer
							'%s', // redirect
							'%d', // ordering
							'%d', // show_user_added
							'%s', // Answer Image
						)
					);
				}
				if($empty_answers_count == $answer_count && $type != "text"){
					for($i = 0 ; $i < 2 ; $i++){
						$wpdb->insert(
							$answer_table,
							array(
								'poll_id'   		=> $last_id,
								'answer'    		=> "Answer ".($i+1),
								'redirect'  		=> isset($answers_url[$i]) && $answers_url[$i] != "" ? sanitize_url($answers_url[$i]) : "",
								'ordering'  		=> ($i + 1),
								'show_user_added' 	=> isset($answers_show_added[$i]) ? $answers_show_added[$i] : 1,
								'answer_img' 		=> ($type == 'choosing' && isset($poll_answers_img[$i])) ? wp_http_validate_url($poll_answers_img[$i]) : "",
							),
							array(
								'%d', // poll_id
								'%s', // answer
								'%s', // redirect
								'%d', // ordering
								'%d', // show_user_added
								'%s', // Answer Image
							)
						);
					}
				}
				if($not_empty_answers_count == 1){
					$wpdb->insert(
						$answer_table,
						array(
							'poll_id'   		=> $last_id,
							'answer'    		=> "Answer 1",
							'redirect'  		=> isset($answers_url[$i]) && $answers_url[$i] != "" ? sanitize_url($answers_url[$i]) : "",
							'ordering'  		=> ($i + 1),
							'show_user_added' 	=> isset($answers_show_added[$i]) ? $answers_show_added[$i] : 1,
							'answer_img' 		=> ($type == 'choosing' && isset($poll_answers_img[$i])) ? wp_http_validate_url($poll_answers_img[$i]) : "",
						),
						array(
							'%d', // poll_id
							'%s', // answer
							'%s', // redirect
							'%d', // ordering
							'%d', // show_user_added
							'%s', // Answer Image
						)
					);
				}
				$message = 'created';
			} else {
				$poll        = $this->get_poll_by_id($id);
				$poll_result = $wpdb->update(
					$poll_table,
					array(
						'title'       => $title,
						'description' => $description,
						'type'        => $type,
						'question'    => $question,
						'view_type'   => $view_type,
						'categories'  => $categories,
						'image'       => $image,
						'show_title'  => $show_title,
						'styles'      => $styles,
						'custom_css'  => $css,
						'theme_id'    => $theme_id,
					),
					array('id' => $id),
					array(
						'%s', // title
						'%s', // description
						'%s', // type
						'%s', // question
						'%s', // view_type
						'%s', // categories
						'%s', // image
						'%d', // show_title
						'%s', // styles
						'%s', // custom_css
						'%d', // theme_id
					),
					array('%d')
				);
				if ($type != $poll['type']) {					
				    $reports_table  = esc_sql($wpdb->prefix."ayspoll_reports");
				    $poll_id = absint(intval($id));
				 	$sql = "DELETE r FROM ".$reports_table." as r JOIN ".$answer_table." ON ".$answer_table.".id = r.answer_id JOIN ".$poll_table." ON ".$poll_table.".id = ".$answer_table.".poll_id WHERE ".$poll_table.".id = %d";

					$wpdb->query(
						$wpdb->prepare($sql,$poll_id)
					);

					$wpdb->delete(
						$answer_table,
						array('poll_id' => $id),
						array('%d')
					);

					foreach ( $answers as $k => $answer ) {
						if (empty($answer)) {
							continue;
						}
						
						$wpdb->insert(
							$answer_table,
							array(
								'poll_id'   		=> $id,
								'answer'    		=> wp_filter_kses($answer),
								'redirect'  		=> isset($answers_url[$k]) && $answers_url[$k] != "" ? sanitize_url($answers_url[$k]) : "",
								'ordering'  		=> ($k + 1),
								'show_user_added' 	=> isset($answers_show_added[$k]) ? $answers_show_added[$k] : 1,
								'answer_img' 		=> ($type == 'choosing' && isset($poll_answers_img[$k])) ? wp_http_validate_url($poll_answers_img[$k]) : "",
							),
							array(
								'%d', // poll_id
								'%s', // answer
								'%s', // redirect
								'%d', // ordering
								'%d', // show_user_added
								'%s', // Answer Image
							)
						);
					}
				}
				if ('choosing' == $type && $type == $poll['type']) {
					foreach ( $poll['answers'] as $answer ) {
						$old_id = $answer['id'];
						$index  = array_search($old_id, $answers_ids);
						if ($index !== false && !empty($answers[$index])) {
							$new_answer = isset($answers[$index]) && $answers[$index] != "" ? $answers[$index] : "";
							$new_url = sanitize_url($answers_url[$index]);
							
							if (empty($new_url)) {
								$new_url = null;
							}

							$wpdb->update(
								$answer_table,
								array(
									'answer'    		=> wp_filter_kses($new_answer),
									'redirect'  		=> $new_url,
									'ordering'  		=> ($index + 1),
									'show_user_added' 	=> isset($answers_show_added[$index]) ? $answers_show_added[$index] : 1,
									'answer_img' 	    => ($type == 'choosing' && isset($poll_answers_img[$index])) ? wp_http_validate_url($poll_answers_img[$index]) : "",
								),
								array('id' => $old_id),
								array(
									'%s', // answer
									'%s', // redirect
									'%d', // ordering
									'%d', // show_user_added
									'%s', // Answer Image
								),
								array('%d')
							);
						} else {
							$report_table  = esc_sql($wpdb->prefix."ayspoll_reports");
							$wpdb->delete(
								$report_table,
								array('answer_id' => $old_id),
								array('%d')
							);
							$wpdb->delete(
								$answer_table,
								array('id' => $old_id),
								array('%d')
							);
						}
					}
					$e_answer_count = 0;
					$full_answer_count = 0;
					$answer_ids_count = count($answers);
					foreach ( $answers_ids as $index => $value ) {
						if (empty($answers[$index])) {
							$e_answer_count++;
						}
						else{
							$full_answer_count++;
						}
						if (0 == $value) {
							if (empty($answers[$index])) {
								continue;
							}
							
							$wpdb->insert(
								$answer_table,
								array(
									'poll_id'   => $id,
									'answer'    => wp_filter_kses($answers[$index]),
									'redirect'  => isset($answers_url[$index]) && $answers_url[$index] != "" ? sanitize_url($answers_url[$index]) : null,
									'ordering'  => ($index + 1),
									'show_user_added' 	=> isset($answers_show_added[$index]) ? $answers_show_added[$index] : 1,
									'answer_img' 	=> ($type == 'choosing' && isset($poll_answers_img[$index])) ? wp_http_validate_url($poll_answers_img[$index]) : "",
								),
								array(
									'%d', // poll_id
									'%s', // answer
									'%s', // redirect
									'%d', // ordering
									'%d', // show_user_added
									'%s', // Answer Image
								)
							);
						}
						
					}
					if($e_answer_count == $answer_ids_count){
						for($j = 0 ; $j < 2 ; $j++){
							$wpdb->insert(
								$answer_table,
								array(
									'poll_id'   => $id,
									'answer'    => "Answer ".($j+1),
									'redirect'  => isset($answers_url[$j]) && isset($answers_url[$j]) != "" ? sanitize_url($answers_url[$j]) : null,
									'ordering'  => ($j + 1),
									'show_user_added' 	=> isset($answers_show_added[$index]) ? $answers_show_added[$index] : 1,
									'answer_img' 	=> ($type == 'choosing' && isset($poll_answers_img[$index])) ? wp_http_validate_url($poll_answers_img[$index]) : "",
								),
								array(
									'%d', // poll_id
									'%s', // answer
									'%s', // redirect
									'%d', // ordering
									'%d', // show_user_added
									'%s', // Answer Image
								)
							);
						}
					}
					if($full_answer_count == 1){
						$wpdb->insert(
							$answer_table,
							array(
								'poll_id'   => $id,
								'answer'    => "Answer 1",
								'redirect'  => isset($answers_url[$j]) && isset($answers_url[$j]) != "" ? sanitize_url($answers_url[$j]) : null,
								'ordering'  => 1,
								'show_user_added' 	=> isset($answers_show_added[$j]) ? $answers_show_added[$j] : 1,
								'answer_img' 	=> ($type == 'choosing' && isset($poll_answers_img[$index])) ? wp_http_validate_url($poll_answers_img[$index]) : "",
							),
							array(
								'%d', // poll_id
								'%s', // answer
								'%s', // redirect
								'%d', // ordering
								'%d', // show_user_added
								'%s', // Answer Image
							)
						);
					}
				}
				if ($type == 'rating' && $poll['type'] == 'rating' && count($poll['answers']) != $rate_value) {
					if (count($poll['answers']) > $rate_value) {
						for ( $i = intval($rate_value); $i < count($poll['answers']); $i++ ) {
							$wpdb->delete(
								"{$wpdb->prefix}ayspoll_reports",
								array('answer_id' => $poll['answers'][$i]['id']),
								array('%d')
							);
							$wpdb->delete(
								$answer_table,
								array('id' => $poll['answers'][$i]['id']),
								array('%d')
							);
						}
					} else {
						for ( $i = count($poll['answers']); $i < $rate_value; $i++ ) {
							$wpdb->insert(
								$answer_table,
								array(
									'poll_id' => $id,
									'ordering'=> wp_filter_kses($answers[$i]),
									'answer'  => wp_filter_kses($answers[$i]),
								),
								array('%d', '%s')
							);
						}
					}
				}
				$message = 'updated';
			}

			$add_post_for_poll = (isset($data['ays_add_post_for_poll']) && $data['ays_add_post_for_poll'] == 'on') ? 'on' : 'off';
			$add_postcat_for_poll =  isset($data['ays_add_postcat_for_poll']) ? $data['ays_add_postcat_for_poll'] : array();
			$poll_post_id =  ($id == 0) ? $last_id : $id;

			if($add_post_for_poll == "on"){
				global $user_ID;
				$post_content = '[ays_poll id="'.$poll_post_id.'"]';

				if ( Poll_Maker_Ays_Admin::is_active_gutenberg() ) {
					$post_content = '<!-- wp:poll-maker/poll {"metaFieldValue":'.$poll_post_id.',"shortcode":"[ays_poll id='.$poll_post_id.']"} -->
					<div class="wp-block-poll-maker-poll">[ays_poll id="'.$poll_post_id.'"]</div>
					<!-- /wp:poll-maker/poll -->';
                }

				$new_post = array(
                    'post_title' => $title,
                    'post_content' => $post_content,
                    'post_status' => 'publish',
                    'post_date' => current_time( 'mysql' ),
                    'post_author' => $user_ID,
                    'post_type' => 'post',
                    'post_category' => $add_postcat_for_poll
				);
				$post_id = wp_insert_post($new_post);
                if(! empty($image)){
                    $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'attachment' AND guid = '".$image."'";
                    $attachment_id = intval($wpdb->get_var($sql));
                    if($attachment_id !== 0){
						$featured_image = set_post_thumbnail($post_id, $attachment_id);
                    }
                }
                $poll_post_result = $wpdb->update(
                    $poll_table,
                    array( 'post_id' => $post_id ),
                    array( 'id' => $poll_post_id ),
                    array( '%d' ),
                    array( '%d' )
                );
			}

			$active_tabs = !empty($data['ays_poll_active_tab']) ? sanitize_text_field($data['ays_poll_active_tab']) : "General";
			$active_tab = urlencode($active_tabs);

			if ($poll_result >= 0) {
				if($message == 'created'){
					setcookie('ays_poll_created_new', $last_id, time() + 3600, '/');
				}
			}
			
			if ($poll_result >= 0) {
				if ('' != $ays_change_type) {
					if($id == 0){
                       $url = esc_url_raw( add_query_arg( array(
                           "action"    	 => "edit",
                           "poll"      	 => $last_id,
                           "active-tab"  => $active_tab,
                           "status"    	 => $message
                       ) ) );
                   	}else{
                       $url = esc_url_raw( remove_query_arg(false) ) . '&active-tab='.$active_tab.'&status=' . $message;
                   	}					
					wp_redirect($url);
				} else {
					$url = esc_url_raw(remove_query_arg(['action'])) . "&active-tab=".$active_tab."&status=" . $message;
					wp_redirect($url);
				}
			}
		}
		$allowedtags = $old_allowedtags;
	}

	public function duplicate_poll( $id ) {
		global $wpdb;

		// Nonce verification for CSRF protection
		if (!isset($_GET['_wpnonce']) && !wp_verify_nonce($_GET['_wpnonce'], $this->plugin_name . '-duplicate-poll')) {
			wp_die( esc_html__('Security check error. Try again.', "poll-maker"));
		}

	 	$poll_table  = esc_sql($wpdb->prefix."ayspoll_polls");
	 	$answers_table  = esc_sql($wpdb->prefix."ayspoll_answers");

		$duplicate = $this->get_poll_by_id($id, false);
		if (empty($duplicate)) {
			$url = esc_url_raw(remove_query_arg(array('action', 'poll'))) . '&status=failed';
			wp_redirect($url);
			exit;
		}

		$user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $author = array(
            'id' => $user->ID,
            'name' => $user->data->display_name
        );
		$options = json_decode($duplicate['styles'], true);
        
        $options['create_date'] = current_time( 'mysql' );
        $options['author'] = $author;

		$options['ays_add_post_for_poll'] = 'off';

		$answers = $duplicate['answers'];
		unset($duplicate['answers']);
		unset($duplicate['id']);
		$duplicate['title']  = "Copy - " . $duplicate['title'];
		$duplicate['styles'] = json_encode($options);
		$result              = $wpdb->insert(
			$poll_table,
			$duplicate,
			array(
				'%d', // post_id
				'%s', // title
				'%s', // description
				'%s', // question
				'%s', // type
				'%s', // view_type
				'%s', // categories
				'%s', // image
				'%s', // show_title
				'%s', // styles
				'%s', // custom_css
				'%d', // theme_id
			)
		);

		$poll_id         = $wpdb->insert_id;
		$answers_results = array();
		$flag            = true;

		foreach ( $answers as $answer ) {
			$answer['poll_id'] = $poll_id;
			unset($answer['id']);
			unset($answer['votes']);
			$answers_results[] = $wpdb->insert(
				$answers_table,
				$answer,
				array(
					'%s',
					'%s',
					'%s',
				)
			);
		}

		foreach ( $answers_results as $answers_result ) {
			if ($answers_result >= 0) {
				$flag = true;
			} else {
				$flag = false;
				break;
			}
		}
		$message = 'duplicated';
		if ($result >= 0 && $flag == true) {
			$url = esc_url_raw(remove_query_arg(array('action', 'poll'))) . '&status=' . $message;
			wp_redirect($url);
			exit;
		}

	}

	public static function get_poll_pass_count($id) {
        global $wpdb;
        $poll_id = absint(sanitize_text_field($id));
        $args_id = esc_sql($poll_id);
        $answ_table = esc_sql($wpdb->prefix."ayspoll_answers");
        $sql = "SELECT SUM(votes) FROM ".$answ_table." WHERE poll_id=%d";
        $result = $wpdb->get_var(
			   	  	$wpdb->prepare( $sql, $args_id)
				  );

        return $result;
    }

	public function get_poll_by_id( $id, $decode = true ) {
		global $wpdb;
		$poll_id 	= absint(sanitize_text_field($id));
		$poll_table = esc_sql($wpdb->prefix."ayspoll_polls");
		$answ_table = esc_sql($wpdb->prefix."ayspoll_answers");
		$sql  = "SELECT * FROM ".$poll_table." WHERE id=%d";
		$poll = $wpdb->get_row(
			   	  	$wpdb->prepare( $sql, $poll_id),
			   	  	'ARRAY_A'
				  );

		if (empty($poll)) {
			return array();
		}		

		$sql_answ = "SELECT * FROM ".$answ_table." WHERE poll_id=%d ORDER BY ordering ASC, id ASC";
		$poll['answers'] = $wpdb->get_results(
			   	  	$wpdb->prepare( $sql_answ, $poll_id),
			   	  	'ARRAY_A'
				  );

		if ($decode) {
			$cats               = explode(',', $poll['categories']);
			$poll['categories'] = !empty($cats) ? $cats : array();
			$json               = $poll['styles'];
			$poll['styles']     = json_decode($json, true);
		}

		return $poll;
	}

	/** Text displayed when no customer data is available */
	public function no_items() {
		_e('There are no polls yet.', "poll-maker");
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title':
			case 'poll_image':
			case 'categories':
			case 'type':
			case 'shortcode':
			case 'code_include':
			case 'results':
			case 'create_date':
			case 'completed_count':
			case 'id':
			case 'publish':
				return $item[$column_name];
				break;
			default:
				return print_r($item, true); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_title( $item ) {
		$delete_nonce = wp_create_nonce($this->plugin_name . '-delete-poll');
		$duplicate_nonce = wp_create_nonce($this->plugin_name . '-duplicate-poll');

		$poll_title = stripcslashes($item['title']);

        $p = esc_attr($poll_title);
        $polls_title_length = intval( $this->title_length );

        $restitle = Poll_Maker_Ays_Admin::ays_restriction_string("word", $poll_title, $polls_title_length);

		$title   = sprintf('<a href="?page=%s&action=%s&poll=%d" title="%s">%s</a>', esc_attr($_REQUEST['page']), 'edit', absint($item['id']), $p, $restitle);
		$actions = [
			'edit'      => sprintf('<a href="?page=%s&action=%s&poll=%d">' .esc_html__('Edit', "poll-maker") . '</a>', esc_attr($_REQUEST['page']), 'edit', absint($item['id'])),
			'duplicate' => sprintf('<a href="?page=%s&action=%s&poll=%d&_wpnonce=%s">' .esc_html__('Duplicate', "poll-maker") . '</a>', esc_attr($_REQUEST['page']), 'duplicate', absint($item['id']),$duplicate_nonce),
			'results'   => sprintf('<a href="?page=%s&poll=%d&title=%s">'.esc_html__('View Results', "poll-maker") .'</a>', esc_attr( $_REQUEST['page'] ) . '-results-each', absint( $item['id'] ) , $poll_title ),
			'delete'    => sprintf('<a href="?page=%s&action=%s&poll=%d&_wpnonce=%s">' .esc_html__('Delete', "poll-maker") . '</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), $delete_nonce),
		];

		return $title . $this->row_actions($actions);
	}

	function column_shortcode( $item ) {
		$shortcode_content = '<div class="ays-poll-shortcode-container">
								<div class="ays-poll-copy-image" data-toggle="tooltip" aria-label="Click to copy" data-original-title="Click to copy">
									<img src="' . esc_url(POLL_MAKER_AYS_ADMIN_URL) . '/images/icons/edit-shortcode.svg">
								</div>';

		$shortcode_input =  sprintf('<input type="text" class="ays-poll-shortcode-input" onclick="this.setSelectionRange(0, this.value.length)" readonly value="[ays_poll id=%s]" />', $item["id"]);

		$shortcode_content .= $shortcode_input;
		$shortcode_content .= '</div>';

		return $shortcode_content;
	}

	function column_code_include( $item ) {
		return sprintf('<input type="text" onClick="this.setSelectionRange(0, this.value.length)" readonly value="<?php echo do_shortcode(\'[ays_poll id=%s]\'); ?>" />', $item["id"]);
	}

	function column_results($item) {
		return sprintf('<a href="?page=%s&poll=%d&title=%s"><img src="'. esc_url(POLL_MAKER_AYS_ADMIN_URL) .'/images/icons/view-poll-results.svg"></a>', esc_attr( $_REQUEST['page'] ) . '-results-each', absint( $item['id']), $item['title']);
	}

	function column_categories( $item ) {
		if ($item['categories'] == '') {
			return '';
		}
		global $wpdb;
		$cats_ids     = explode(',', $item['categories']);
		$cat_table = esc_sql($wpdb->prefix."ayspoll_categories");
		$category_location = "#";
		foreach ( $cats_ids as $id ) {
			if (empty($id)) {
				continue;
			}
			$args_id = absint( esc_sql($id) );
			$sql = "SELECT * FROM ".$cat_table." WHERE id=%d";

			$result = $wpdb->get_row(
					   	  		$wpdb->prepare( $sql, $args_id),
						   	  	'ARRAY_A'
						  	);
			if (empty($result)) {
				continue;
			}
			
			if ( isset($result) && $result > 0 ) {
				$category_location = sprintf( '<span>%s</span>', $result['title']);
			}

			$cats_content = $category_location;
		}
		
		return $cats_content;
	}

	function column_create_date( $item ) {
        $options = json_decode($item['styles'], true);
        $date = isset($options['create_date']) && $options['create_date'] != '' ? sanitize_text_field($options['create_date']) : current_time( 'mysql' );
        if(isset($options['author'])){
            if(is_array($options['author'])){
                $author = $options['author'];
            }else{
                $author = json_decode($options['author'], true);
            }
        }else{
            $author = array("name"=>"Unknown");
        }
        $text = "";
        if(Poll_Maker_Ays_Admin::validateDate($date)){
            $text .= "<p><b>Date:</b> ".$date."</p>";
        }
        if(isset($author['name']) && $author['name'] !== "Unknown"){
            $text .= "<p><b>Author:</b> ".$author['name']."</p>";
        }
        return $text;
    }

	function column_completed_count( $item ) {
        $id = $item['id'];
        $passed_count = $this->get_poll_pass_count($id);
        $text = "<p style='text-align:center;font-size:14px;'>".$passed_count."</p>";
        return $text;
    }

	function column_publish( $item ) {
		global $wpdb;
		$id = absint(sanitize_text_field($item['id']));
		$poll_table = esc_sql($wpdb->prefix."ayspoll_polls");
		$sql     = "SELECT * FROM ".$poll_table." WHERE id=%d";

		$res = $wpdb->get_row(
			   	  		$wpdb->prepare( $sql, $id),
				   	  	'ARRAY_A'
				  	  );
		$options = json_decode($res['styles'], true);
		$status  = isset($options['published']) ? $options['published'] : 1;

		return $status ? "<i class=\"ays_poll_far ays_poll_fa-check-square\"></i> " .esc_html__("Published", "poll-maker") : "<i class=\"ays_poll_far ays_poll_fa-square\"></i> " .esc_html__("Unpublished", "poll-maker");
	}

	function column_poll_image( $item ) {
        global $wpdb;
        $poll_image = (isset( $item['image'] ) && $item['image'] != '') ? esc_url( $item['image'] ) : '';

        $image_html     = array();
        $edit_page_url  = '';

        if($poll_image != ''){

            if ( isset( $item['id'] ) && absint( $item['id'] ) > 0 ) {
                $edit_page_url = sprintf( 'href="?page=%s&action=%s&poll=%d"', esc_attr( $_REQUEST['page'] ), 'edit', absint( $item['id'] ) );
            }

            $poll_image_url = $poll_image;
            $this_site_path = trim( get_site_url(), "https:" );
            if( strpos( trim( $poll_image_url, "https:" ), $this_site_path ) !== false ){
                $query = "SELECT * FROM `" . $wpdb->prefix . "posts` WHERE `post_type` = 'attachment' AND `guid` = '" . $poll_image_url . "'";
                $result_img =  $wpdb->get_results( $query, "ARRAY_A" );

                if( ! empty( $result_img ) ){
                    $url_img = wp_get_attachment_image_src( $result_img[0]['ID'], 'thumbnail' );
                    if( $url_img !== false ){
                        $poll_image_url = $url_img[0];
                    }
                }
            }

            $image_html[] = '<div class="ays-poll-image-list-table-column">';
                $image_html[] = '<a '. $edit_page_url .' class="ays-poll-image-list-table-link-column">';
                    $image_html[] = '<img src="'. $poll_image_url .'" class="ays-poll-image-list-table-img-column">';
                $image_html[] = '</a>';
            $image_html[] = '</div>';
        }

        $image_html = implode('', $image_html);

        return $image_html;
    }

	/**
	 * Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'				=> '<input type="checkbox" />',
			'title'        		=>esc_html__('Title', "poll-maker"),
			'poll_image'        =>esc_html__('Question image', "poll-maker"),
			'type'         		=>esc_html__('Type', "poll-maker"),
			'shortcode'   		=>esc_html__('Shortcode', "poll-maker"),
			'code_include' 		=>esc_html__('Code Include', "poll-maker"),
			'results'      		=>esc_html__('Results', "poll-maker"),
			'categories'   		=>esc_html__('Categories', "poll-maker"),
			'create_date'   	=>esc_html__('Created', "poll-maker" ),
			'completed_count'   =>esc_html__('Completed count', "poll-maker" ),
			'publish'      		=>esc_html__('Status', "poll-maker"),
			'id'           		=>esc_html__('ID', "poll-maker"),
		);

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'title' => array('title', true),
			'type'  => array('type', true),
			'id'    => array('id', true),
		);

		return $sortable_columns;
	}

	/**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_hidden_columns() {
        $hidden_culumns = array(
			'poll_image',
            'code_include',
        );
        return $hidden_culumns;
    }

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' =>esc_html__('Delete', "poll-maker"),
		);

		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		global $wpdb;

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page('polls_per_page', 20);
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args(array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page, //WE have to determine how many items to show on a page
		));

        $search = ( isset( $_REQUEST['s'] ) ) ? esc_sql( sanitize_text_field( $_REQUEST['s'] ) ) : false;
        $do_search = ( $search ) ? sprintf(" title LIKE '%%%s%%' ", esc_sql( $wpdb->esc_like( $search ) ) ) : '';

		$this->items = self::get_polls($per_page, $current_page, $do_search);
	}

	public function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		$message = 'deleted';
		if ('delete' === $this->current_action()) {

			// In our file that handles the request, verify the nonce.
			$nonce = esc_attr($_REQUEST['_wpnonce']);

			if (!wp_verify_nonce($nonce, $this->plugin_name . '-delete-poll')) {
				die('Go get a life script kiddies');
			} else {
				self::delete_polls(absint($_GET['poll']));

				// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
				// add_query_arg() return the current url

				$url = esc_url_raw(remove_query_arg(['action', 'poll', '_wpnonce'])) . '&status=' . $message;
				wp_redirect($url);
			}
		}

		// If the delete bulk action is triggered
		if ((isset($_POST['action']) && 'bulk-delete' == $_POST['action'])
		    || (isset($_POST['action2']) && 'bulk-delete' == $_POST['action2'])
		) {

			$delete_ids = ( isset( $_POST['bulk-delete'] ) && ! empty( $_POST['bulk-delete'] ) ) ? esc_sql( $_POST['bulk-delete'] ) : array();

			// loop over the array of record IDs and delete them
			$deleted_polls_count = 0;
			foreach ( $delete_ids as $id ) {
				$deleted_polls_count++;
				self::delete_polls($id);
			}

			// esc_url_raw() is used to prevent converting ampersand in url to "#038;"
			// add_query_arg() return the current url

			$url = esc_url_raw(remove_query_arg(['action', 'poll', '_wpnonce'])) . '&status=' . $message . '&d_count=' .$deleted_polls_count;
			wp_redirect($url);
		}
	}

	/**
	 * Delete a customer record.
	 *
	 * @param int $id customer ID
	 */
	public static function delete_polls( $id ) {
		global $wpdb;
		$poll_table = esc_sql($wpdb->prefix . "ayspoll_polls");
		$answ_table = esc_sql($wpdb->prefix . "ayspoll_answers");
		$reports_table = esc_sql($wpdb->prefix . "ayspoll_reports");
		$wpdb->delete(
			$poll_table,
			array('id' => $id),
			array('%d')
		);
		$wpdb->delete(
			$answ_table,
			array('poll_id' => $id),
			array('%d')
		);
		$sql = "DELETE FROM ".$reports_table."
        WHERE answer_id NOT IN (SELECT id FROM ".$answ_table.")";
		$wpdb->query($sql);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
		$poll_table = esc_sql($wpdb->prefix . "ayspoll_polls");
		$sql = "SELECT COUNT(*) FROM ".$poll_table;
		$sql_count = self::get_where_condition();
		if(isset($sql_count) && !empty($sql_count)){
			$sql .= $sql_count;
		}
		return $wpdb->get_var($sql);
	}

	public static function all_record_count() {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ayspoll_polls";

        if (isset($_GET['filterby']) && absint(intval($_GET['filterby'])) > 0) {
            $cat_id = absint(intval($_GET['filterby']));
            if (isset($_SERVER['HTTP_HOST']) && sanitize_text_field($_SERVER['HTTP_HOST']) == "playground.wordpress.net") {
                $sql .= " WHERE categories LIKE '%," . $cat_id . ",%' OR categories LIKE '" . $cat_id . ",%' OR categories LIKE '%," . $cat_id . "' OR categories = '" . $cat_id . "'";
            } else {
                $sql .= " WHERE FIND_IN_SET('{$cat_id}', categories) > 0";
            }
        }

        return $wpdb->get_var( $sql );
    }

	public static function published_polls_count() {
        global $wpdb;

		// Counters
		$publishedCount = 0;

		$poll_table = esc_sql($wpdb->prefix . "ayspoll_polls");
		$sql = "SELECT * FROM " . $poll_table;

		if (isset($_GET['filterby']) && absint(intval($_GET['filterby'])) > 0) {
			$cat_id = absint(intval($_GET['filterby']));
			if (isset($_SERVER['HTTP_HOST']) && sanitize_text_field($_SERVER['HTTP_HOST']) == "playground.wordpress.net") {
				$sql .= " WHERE categories LIKE '%," . $cat_id . ",%' OR categories LIKE '" . $cat_id . ",%' OR categories LIKE '%," . $cat_id . "' OR categories = '" . $cat_id . "'";
			} else {
				$sql .= " WHERE FIND_IN_SET('{$cat_id}', categories) > 0";
			}
		}

		$results = $wpdb->get_results($sql, ARRAY_A);

		foreach ($results as $poll) {
			$options = json_decode($poll['styles'], true);
			$status = isset($options['published']) ? $options['published'] : 1;
		
			if ($status == 1) {
				$publishedCount++;
			}
		}

		return $publishedCount;
    }
    
    public static function unpublished_polls_count() {
        global $wpdb;

		// Counters
		$unpublishedCount = 0;

		$poll_table = esc_sql($wpdb->prefix . "ayspoll_polls");
		$sql = "SELECT * FROM " . $poll_table;

		if (isset($_GET['filterby']) && absint(intval($_GET['filterby'])) > 0) {
			$cat_id = absint(intval($_GET['filterby']));
			if (isset($_SERVER['HTTP_HOST']) && sanitize_text_field($_SERVER['HTTP_HOST']) == "playground.wordpress.net") {
				$sql .= " WHERE categories LIKE '%," . $cat_id . ",%' OR categories LIKE '" . $cat_id . ",%' OR categories LIKE '%," . $cat_id . "' OR categories = '" . $cat_id . "'";
			} else {
				$sql .= " WHERE FIND_IN_SET('{$cat_id}', categories) > 0";
			}
		}

		$results = $wpdb->get_results($sql, ARRAY_A);

		foreach ($results as $poll) {
			$options = json_decode($poll['styles'], true);
			$status = isset($options['published']) ? $options['published'] : 1;
		
			if ($status == 0) {
				$unpublishedCount++;
			}
		}

		return $unpublishedCount;
    }

	public static function get_where_condition(){
		global $wpdb;

        $where = array();
        $sql = '';

		$search = ( isset( $_REQUEST['s'] ) ) ? esc_sql( sanitize_text_field( $_REQUEST['s'] ) ) : false;
        if( $search ){
            $where[] = sprintf(" title LIKE '%%%s%%' ", esc_sql( $wpdb->esc_like( $search ) ) );
        }

		if(isset( $_REQUEST['filterby'] ) && $_REQUEST['filterby'] > 0 ){
			$cat_id = esc_sql( sanitize_text_field( $_REQUEST['filterby'] ) );

            $where[] = sprintf(" categories LIKE('%%,%s,%%') ", esc_sql( $wpdb->esc_like( $cat_id ) ) );
		}

		if( isset( $_REQUEST['fstatus'] ) && is_numeric( $_REQUEST['fstatus'] ) && ! is_null( sanitize_text_field( $_REQUEST['fstatus'] ) ) ){
            if( esc_sql( $_REQUEST['fstatus'] ) != '' ){
                $fstatus  = absint( esc_sql( $_REQUEST['fstatus'] ) );
                $where[] = " JSON_EXTRACT(styles, '$.published') = " . ($fstatus == 1 ? '1' : '0');
            }
        }

		if(isset( $_REQUEST['filterbyauthor'] ) && $_REQUEST['filterbyauthor'] > 0 ){
			$author_id = esc_sql( sanitize_text_field( $_REQUEST['filterbyauthor'] ) );

            $where[] = sprintf(" categories LIKE('%%,%s,%%') ", esc_sql( $wpdb->esc_like( $author_id ) ) );
		}
		if(isset( $_REQUEST['filterbytype'] ) && $_REQUEST['filterbytype'] != "" ){
			$type = esc_sql( sanitize_text_field( $_REQUEST['filterbytype'] ) );
			if (isset($type) && $type != '') {
				$where[] = " type = '" . $type . "'";
			}
		}
			
		if( !empty($where) ){
			$sql = " WHERE " . implode( " AND ", $where );
		}
		return $sql;
    }

	/**
	 * Retrieve customers data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_polls( $per_page = 20, $page_number = 1 , $search = '') {

		global $wpdb;
		$poll_table = esc_sql($wpdb->prefix . "ayspoll_polls");
		$sql = "SELECT * FROM ".$poll_table;
		$args = array();

		$where = array();

        if( $search != '' ){
            $where[] = $search;
        }
		
		if(isset( $_REQUEST['filterby'] ) && $_REQUEST['filterby'] > 0 ){
			$cat_id = esc_sql( sanitize_text_field( $_REQUEST['filterby'] ) );

            $where[] = sprintf(" categories LIKE('%%,%s,%%') ", esc_sql( $wpdb->esc_like( $cat_id ) ) );
		}

		if ( isset($_REQUEST['filterbyauthor'] ) && $_REQUEST['filterbyauthor'] > 0) {
			$poll_author = esc_sql( sanitize_text_field( $_REQUEST['filterbyauthor'] ) );
			
			$where[] = " JSON_EXTRACT(styles, '$.poll_create_author') = " . ($poll_author);
        }

		if(isset( $_REQUEST['filterbytype'] ) && $_REQUEST['filterbytype'] != "" ){
			$type = esc_sql( sanitize_text_field( $_REQUEST['filterbytype'] ) );
			if (isset($type) && $type != '') {
				$where[] = " type = '" . $type . "'";
			}
		}

		if( isset( $_REQUEST['fstatus'] ) && is_numeric( $_REQUEST['fstatus'] ) && ! is_null( sanitize_text_field( $_REQUEST['fstatus'] ) ) ){
            if( esc_sql( $_REQUEST['fstatus'] ) != '' ){
                $fstatus  = absint( esc_sql( $_REQUEST['fstatus'] ) );
                $where[] = " JSON_EXTRACT(styles, '$.published') = " . ($fstatus == 1 ? '1' : '0');
            }
        }

		if( ! empty($where) ){
            $sql .= " WHERE " . implode( " AND ", $where );
        }

		if ( ! empty( $_REQUEST['orderby'] ) ) {

            $order_by  = ( isset( $_REQUEST['orderby'] ) && sanitize_text_field( $_REQUEST['orderby'] ) != '' ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
            $order_by .= ( ! empty( $_REQUEST['order'] ) && strtolower( $_REQUEST['order'] ) == 'asc' ) ? ' ASC' : ' DESC';

            $sql_orderby = sanitize_sql_orderby($order_by);

            if ( $sql_orderby ) {
                $sql .= ' ORDER BY ' . $sql_orderby;
            } else {
                $sql .= ' ORDER BY id DESC';
            }
        }else{
            $sql .= ' ORDER BY id DESC';
        }

		$sql .= " LIMIT %d";
		$args[] = $per_page;
		$offset = ($page_number - 1) * $per_page;
		$sql .= " OFFSET %d";
		$args[] = $offset;
		$result = $wpdb->get_results(
			   	  	$wpdb->prepare( $sql, $args),
			   	  	'ARRAY_A'
				  );

		return $result;
	}

	public function poll_notices() {
		$status = (isset($_REQUEST['status'])) ? sanitize_text_field($_REQUEST['status']) : '';

		if (empty($status)) {
			return;
		}

		if ('created' == $status) {
			$updated_message = esc_html( esc_html__('Poll created.', "poll-maker"));
		} elseif ('updated' == $status) {
			$updated_message = esc_html( esc_html__('Poll saved.', "poll-maker"));
		} elseif ('duplicated' == $status) {
			$updated_message = esc_html( esc_html__('Poll duplicated.', "poll-maker"));
		} elseif ('deleted' == $status) {
			$deleted_count = isset($_GET['d_count']) && $_GET['d_count'] != "" ? intval(esc_attr($_GET['d_count'])) : 0;
			$deleted_count_message =esc_html__('Poll deleted.', "poll-maker");
			if($deleted_count > 1){
				$deleted_count_message = $deleted_count .esc_html__(' Polls are deleted.', "poll-maker");
			} 
			$updated_message = $deleted_count_message;
		}

		if (empty($updated_message)) {
			return;
		}

		?>
        <div class="notice notice-success is-dismissible">
            <p>
				<?php echo $updated_message; ?>
            </p>
        </div>
		<?php
	}
}
