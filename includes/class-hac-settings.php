<?php
/**
 * Settings class
 */
namespace HideAdminColumns;

class HAC_Settings {

    /**
     * Options page callback.
     */
    public function options_page() {
        ?>
        <div class="hac-wrap wrap">
            <h2><?php esc_html_e( 'Hide Admin Columns', 'hide-admin-columns' ); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'hide_admin_columns_group' );
                do_settings_sections( 'hide_admin_columns' );

                echo '<div id="columns_container"></div>'; // Placeholder for dynamically loaded columns.
                wp_nonce_field( 'hac_nonce_action', 'hac_nonce_name' );
                echo '<button id="save_columns_button" class="button button-primary" style="display:none;">' . esc_html__( 'Save Selection', 'hide-admin-columns' ) . '</button>';
                echo '<div id="hacLoader" style="display: inline-block;"></div>';
                ?>
            </form>
        </div>
        <div class="hac-footer-note">
            <small><?php esc_html_e( 'Developed with &#x1F499; by', 'hide-admin-columns' ); ?> <a href="https://www.bishoy.me" target="_blank"><?php esc_html_e( 'Bishoy A', 'hide-admin-columns' ); ?></a>.</small><br />
            <small><?php esc_html_e( 'If my work has helped you, consider buying me a coffee. Every cup makes a big difference!', 'hide-admin-columns' ); ?></small><br /> 
            <a href="https://buymeacoffee.com/bishoy" class="button button-hac-donate" target="_blank"><?php esc_html_e( 'Buy me a coffee', 'hide-admin-columns' ); ?></a>
        </div>
        <?php
    }

    /**
     * Initialize the settings page by adding sections and fields.
     */
    public function init_settings_page() {
        add_settings_section(
            'hac_settings',
            __( 'Show/hide admin columns', 'hide-admin-columns' ),
            array( $this, 'print_section_info' ),
            'hide_admin_columns'
        );

        add_settings_field(
            'post_type',
            __( 'Select Post Type', 'hide-admin-columns' ),
            array( $this, 'create_post_type_dropdown' ),
            'hide_admin_columns',
            'hac_settings'
        );
    }

    /**
     * Print the section text.
     */
    public function print_section_info() {
        esc_html_e( 'Select the post type and then choose columns to hide:', 'hide-admin-columns' );
    }

    /**
     * Create a dropdown for selecting post types.
     */
    public function create_post_type_dropdown() {
        $post_types = get_post_types( array( 'public' => true, 'show_ui' => true ), 'objects' );

        echo '<select id="post_type_selector" name="hide_admin_columns[post_type]">';
        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type->name ) {
                continue;
            }

            printf(
                '<option value="%1$s">%2$s</option>',
                esc_attr( $post_type->name ),
                esc_html( $post_type->labels->name )
            );
        }
        echo '</select>';
    }
}
