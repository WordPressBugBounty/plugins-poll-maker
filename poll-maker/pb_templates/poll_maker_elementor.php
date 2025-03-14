<?php
namespace Elementor;
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class Widget_Poll_Maker_Elementor extends Widget_Base {
/*
    public function init() {

        // Register Widget Styles
        add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'widget_styles' ) );

    }*/

    public function get_name() {
        return 'poll-maker';
    }
    public function get_title() {
        return esc_html__( 'Poll Maker', 'poll-maker' );
    }
    public function get_icon() {
        // Icon name from the Elementor font file, as per http://dtbaker.net/web-development/creating-your-own-custom-elementor-widgets/
        // return 'fas fa-poll ays_fa_poll';
        return 'ays_fa_power_off_poll';
    }
	public function get_categories() {
		return array( 'general' );
	}

    protected function _register_controls() {
        $this->start_controls_section(
            'section_poll_maker',
            array(
                'label' => esc_html__( 'Poll Maker', 'poll-maker' ),
            )
        );

        $this->add_control(
            'poll_title',
            array(
                'label' =>esc_html__( 'Poll Title', 'poll-maker' ),
                'type' => Controls_Manager::TEXT,
                'default' => '',
                'title' =>esc_html__( 'Enter the poll title', 'poll-maker' ),
                'placeholder' =>esc_html__( 'Enter the poll title', 'poll-maker' ),
            )
        );
        $this->add_control(
            'poll_title_alignment',
            array(
                'label' =>esc_html__( 'Title Alignment', 'poll-maker' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => array(
                    'left'      => 'Left',
                    'right'     => 'Right',
                    'center'    => 'Center'
                )
            )
        );
        $this->add_control(
            'poll_selector',
            array(
                'label' =>esc_html__( 'Select Poll', 'poll-maker' ),
                'type' => Controls_Manager::SELECT,
                'default' => $this->get_default_poll(),
                'options' => $this->get_active_polls()
            )
        );

        $this->end_controls_section();
    }
    protected function render( $instance = array() ) {
        $settings = $this->get_settings_for_display();
        echo ( isset( $settings['poll_title'] ) && ! empty( $settings['poll_title'] ) ) ? "<h2 style='text-align: {$settings['poll_title_alignment']}'>{$settings['poll_title']}</h2>" : "";
        echo do_shortcode("[ays_poll id={$settings['poll_selector']}]");
    }

    public function get_active_polls(){
        global $wpdb;
        $polls_table = esc_sql($wpdb->prefix . 'ayspoll_polls');
        $sql = "SELECT id,title FROM ".$polls_table;
        $results = $wpdb->get_results( $sql, ARRAY_A );
        $options = array();
        foreach ( $results as $result ){
            $options[$result['id']] = $result['title'];
        }
        return $options;
    }

    public function get_default_poll(){
        global $wpdb;
        $polls_table = esc_sql($wpdb->prefix . 'ayspoll_polls');
        $sql = "SELECT id FROM ".$polls_table;
        $id = $wpdb->get_var( $sql );

        return intval($id);
    }

    protected function content_template() {}
    public function render_plain_content( $instance = array() ) {}
}