<?php
/**
 * Hide Admin Columns
 * 
 * This class is responsible for loading all plugin's core dependencies
 * and applying the column changes per the settings used.
 */

namespace HideAdminColumns;

class Hide_Admin_Columns {
    
    /**
    * The name of the plugin.
    *
    * @var string
    */
   private $plugin_name;

   /**
    * The version of the plugin.
    *
    * @var string
    */
   private $version;

   /**
    * Constructor for the class. Initializes the plugin.
    */
    public function __construct() {
        $this->plugin_name = 'hide_admin_columns';
        $this->version = '1.0';
        $this->load_dependencies();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'class-hac-settings.php';
    }

    /**
     * Initialize the plugin by setting localization, hooks, and filters.
     */
    public function run() {
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_my_settings' ) );
        add_action( 'wp_ajax_get_columns_for_post_type', array( $this, 'ajax_get_columns_for_post_type' ) );
        add_action( 'wp_ajax_save_column_visibility', array( $this, 'ajax_save_column_visibility' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/hide-admin-columns.php' ), array( $this, 'add_settings_link_to_plugin_list' ), 10, 2 );
        add_action( 'admin_init', array( $this, 'add_columns_filters' ) );
    }
    
    /**
     * Add columns filters for all public post types except 'media'.
     */
    public function add_columns_filters() {
        $post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), 'names' );

        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type ) {
                continue;
            }
            add_filter( "manage_{$post_type}_posts_columns", array( $this, 'filter_posts_columns' ) );
        }
    }
    
    /**
    * Handle AJAX request to get columns for a specific post type.
    */
    public function ajax_get_columns_for_post_type() {
        // Verify nonce
        check_ajax_referer( 'hac_nonce_action', 'nonce' );

        // Check for required nonce and user capabilities.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'hide-admin-columns' ) );
        }

        // Sanitize the post type input.
        $post_type = sanitize_text_field( $_POST['post_type'] );

        $option_key   = "{$post_type}_admin_columns";
        $saved_columns = get_option( $option_key, array() );

        $columns = $this->get_post_type_columns( $post_type );

        ob_start();

        foreach ( $columns as $key => $label ) {
            $checked = isset( $saved_columns[ $key ] ) && $saved_columns[ $key ] ? 'checked' : '';
            printf(
                '<label><input type="checkbox" name="%1$s" %2$s> %3$s</label><br>',
                esc_attr( $key ),
                esc_attr( $checked ),
                esc_html( $label )
            );
        }

        $output = ob_get_clean();

        // Define allowed HTML tags and attributes
        $allowed_html = array(
            'label' => array(),
            'input' => array(
                'type' => array(),
                'name' => array(),
                'checked' => array()
            ),
            'br' => array()
        );

        echo wp_kses( $output, $allowed_html );

        // Properly terminate AJAX request.
        wp_die();
    }

    /**
     * Retrieve columns for a specific post type.
     *
     * @param string $post_type The post type to retrieve columns for.
     * @return array Array of columns.
     */
    private function get_post_type_columns( $post_type ) {
        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            return array();
        }

        // Set up the correct admin screen context.
        set_current_screen( "edit-{$post_type}" );
        $GLOBALS['pagenow'] = 'edit.php';
        $_GET['post_type'] = sanitize_key( $post_type );

        // Yoast support.
        if ( class_exists( '\WPSEO_Meta_Columns' ) && \function_exists( 'wpseo_admin_init' ) ) {
            add_filter( 'wpseo_always_register_metaboxes_on_admin', '__return_true' );
            wpseo_admin_init();
            $wpseo_meta_columns = new \WPSEO_Meta_Columns();
            $wpseo_meta_columns->setup_hooks();
        }

        // Include necessary WordPress admin files.
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        require_once ABSPATH . 'wp-admin/includes/screen.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';

        // Temporarily remove the filter that hides columns.
        remove_filter( "manage_{$post_type}_posts_columns", array( $this, 'filter_posts_columns' ) );

        $hidden_columns = apply_filters( 'default_hidden_columns', array(), 999 );

        // Simulate getting columns.
        $list_table = new \WP_Posts_List_Table( array( 'screen' => get_current_screen() ) );
        $columns = $list_table->get_columns();

        // Reapply the filter after fetching the columns.
        add_filter( "manage_{$post_type}_posts_columns", array( $this, 'filter_posts_columns' ) );

        // Exclude the checkbox column for bulk actions.
        unset( $columns['cb'] );

        // Remove hidden columns.
        foreach ( $hidden_columns as $hidden_column ) {
            if ( isset( $columns[ $hidden_column ] ) ) {
                unset( $columns[ $hidden_column ] );
            }
        }

        // Clean up labels to remove any HTML.
        foreach ( $columns as $key => &$label ) {
            $label = wp_strip_all_tags( $label );
        }

        return $columns;
    }

    /**
     * Adds a settings link to the plugin's actions links in the plugins table.
     *
     * @param array  $links An array of plugin action links.
     * @param string $file  The plugin file path relative to the plugins directory.
     * @return array Updated array of plugin action links.
     */
    public function add_settings_link_to_plugin_list( $links, $file ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $plugin_base_file = plugin_basename( dirname( __DIR__ ) . '/hide-admin-columns.php' );
        if ( $file === $plugin_base_file ) {
            $settings_link = '<a href="' . admin_url( 'options-general.php?page=hide_admin_columns' ) . '">Settings</a>';
            array_unshift( $links, $settings_link );
        }

        return $links;
    }
    
    /**
     * Handles the AJAX request to save column visibility settings.
     *
     * This function checks user capabilities, verifies the nonce,
     * retrieves the post type and columns from the request, updates the
     * corresponding option in the database, and sends a success message.
     *
     * @return void
     */
    public function ajax_save_column_visibility() {
        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized user', 'hide-admin-columns' ), 403 );
        }

        // Verify nonce for security.
        check_ajax_referer( 'hac_nonce_action', 'nonce' );

        // Retrieve post type and columns from the request.
        $post_type  = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';
        $columns    = isset( $_POST['columns'] ) ? array_map( 'sanitize_text_field', (array) $_POST['columns'] ) : [];
        $option_key = "{$post_type}_admin_columns";

        // Update the option with the new column visibility settings.
        update_option( $option_key, $columns );

        // Send a success message.
        wp_die( esc_html__( 'Columns Updated Successfully', 'hide-admin-columns' ) );
    }

    /**
     * Enqueue JavaScript and CSS for admin pages.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        if ( 'settings_page_hide_admin_columns' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/hac-scripts.js', array( 'jquery' ), $this->version, true );
        wp_localize_script( $this->plugin_name, 'hacAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'assets/hac-styles.css', array(), $this->version, 'all' );
    }

    /**
     * Register and add settings
     */
    public function register_my_settings() {
        register_setting( 'hide_admin_columns_group', $this->plugin_name, array( $this, 'sanitize_settings' ) );
        $settings_page = new HAC_Settings();
        $settings_page->init_settings_page();
    }

    /**
     * Add plugin menu in settings
     */
    public function add_plugin_admin_menu() {
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }

        add_options_page( 'Hide Admin Columns Settings', 'Hide Admin Columns', 'manage_options', $this->plugin_name, array( $this, 'display_plugin_setup_page' ) );
    }

    /**
     * Display the plugin setup page
     */
    public function display_plugin_setup_page() {
        $settings_page = new HAC_Settings();
        $settings_page->options_page();
    }

    /**
     * Filter the columns displayed in the Posts screen based on options saved per post type.
     *
     * @param array $columns An array of columns.
     * @return array The filtered array of columns.
     */
    public function filter_posts_columns( $columns ) {
        $screen = get_current_screen(); // Get the current screen object.
        
        if ( $screen ) {
            $post_type  = $screen->post_type;
            $option_key = "{$post_type}_admin_columns";
            $options    = get_option( $option_key, array() );

            foreach ( $columns as $key => $value ) {
                if ( isset( $options[ $key ] ) && $options[ $key ] === '1' ) {
                    unset( $columns[ $key ] );
                }
            }
        }
        
        return $columns;
    }

    /**
     * Sanitize the plugin settings.
     *
     * @param array $input Contains all settings fields as array keys.
     * @return array Sanitized settings.
     */
    public function sanitize_settings( $input ) {
        $new_input = array();
        
        foreach ( $input as $key => $val ) {
            $new_input[ $key ] = sanitize_text_field( $val );
        }
        
        return $new_input;
    }
}
