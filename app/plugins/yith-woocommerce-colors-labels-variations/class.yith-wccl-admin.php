<?php
/**
 * Admin class
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Colors and Labels Variations
 * @version 1.0.0
 */

if ( !defined( 'YITH_WCCL' ) ) { exit; } // Exit if accessed directly

if( !class_exists( 'YITH_WCCL_Admin' ) ) {
    /**
     * Admin class.
     * The class manage all the admin behaviors.
     *
     * @since 1.0.0
     */
    class YITH_WCCL_Admin {
        /**
         * Plugin version
         *
         * @var string
         * @since 1.0.0
         */
        public $version;

        /**
         * Constructor
         *
         * @access public
         * @since 1.0.0
         */
        public function __construct( $version ) {
            $this->version = $version;

            //new attribute types
            add_action('woocommerce_admin_attribute_types', array($this, 'attribute_types'));

            //product attribute taxonomies
            add_action('init', array($this, 'attribute_taxonomies'));

            //print attribute field type
            add_action('yith_wccl_print_attribute_field', array($this, 'print_attribute_type'), 10, 3);

            //save new term
            add_action('created_term', array($this, 'attribute_save'), 10, 3);
            add_action('edit_term', array($this, 'attribute_save'), 10, 3);

            //choose variations in product page
            add_action('woocommerce_product_option_terms', array($this, 'product_option_terms'), 10, 2);

            //enqueue static content
            add_action('admin_enqueue_scripts', array($this, 'enqueue'));

            // YITH WCCL Loaded
            do_action( 'yith_wccl_loaded' );
        }


        /**
         * Enqueue static content
         */
        public function enqueue() {
            global $pagenow;

            if( 'edit-tags.php' == $pagenow && isset( $_GET['post_type'] ) && 'product' == $_GET['post_type'] ) {
                wp_enqueue_media();
                wp_enqueue_style( 'yith-wccl-admin', YITH_WCCL_URL . '/assets/css/admin.css', array('wp-color-picker'), $this->version );
                wp_enqueue_script( 'yith-wccl-admin', YITH_WCCL_URL . '/assets/js/admin.js', array('jquery', 'wp-color-picker' ), $this->version, true );
            }
        }


        /**
         * Add new attribute types
         *
         * @access public
         * @since 1.0.0
         */
        public function attribute_types() {
            global $wpdb;

            $edit = absint( $_GET['edit'] );
            $attribute_to_edit = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_id = '$edit'");
            $att_type 	= $attribute_to_edit->attribute_type;

            ?>
            <option value="colorpicker" <?php selected( $att_type, 'colorpicker' ); ?>><?php _e( 'Colorpicker', 'yit' ) ?></option>
            <option value="image" <?php selected( $att_type, 'image' ); ?>><?php _e( 'Image', 'yit' ) ?></option>
            <option value="label" <?php selected( $att_type, 'label' ); ?>><?php _e( 'Label', 'yit' ) ?></option>

        <?php
        }


        /**
         * Init product attribute taxonomies
         *
         * @access public
         * @since 1.0.0
         */
        public function attribute_taxonomies() {
            global $woocommerce;

            $attribute_taxonomies = $woocommerce->get_attribute_taxonomies();
            if ($attribute_taxonomies) {
                foreach ($attribute_taxonomies as $tax) {

                    add_action('pa_' . $tax->attribute_name . '_add_form_fields', array($this, 'add_attribute_field'));
                    add_action('pa_' . $tax->attribute_name . '_edit_form_fields', array($this, 'edit_attribute_field'), 10, 2);

                    add_filter('manage_edit-pa_' . $tax->attribute_name . '_columns', array($this, 'product_attribute_columns'));
                    add_filter('manage_pa_' . $tax->attribute_name . '_custom_column', array($this, 'product_attribute_column'), 10, 3);
                }
            }
        }


        /**
         * Add field for each product attribute taxonomy
         *
         * @access public
         * @since 1.0.0
         */
        public function add_attribute_field( $taxonomy ) {
            global $wpdb;

            $attribute = substr($taxonomy, 3);
            $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'");
            $att_type 	= $attribute->attribute_type;

            do_action('yith_wccl_print_attribute_field', $attribute);
        }

        /**
         * Edit field for each product attribute taxonomy
         *
         * @access public
         * @since 1.0.0
         */
        public function edit_attribute_field( $term, $taxonomy ) {
            global $wpdb;

            $attribute = substr($taxonomy, 3);
            $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'");

            $value = get_woocommerce_term_meta($term->term_id, $taxonomy . '_yith_wccl_value');

            do_action('yith_wccl_print_attribute_field', $attribute, $value, 1);
        }


        /**
         * Print Color Picker Type HTML
         *
         * @access public
         * @since 1.0.0
         */
        public function print_attribute_type($attribute, $value = '', $table = 0){
            $type = $attribute->attribute_type;
            $label = ($type == 'colorpicker') ? 'Color' : ( ($type == 'image') ? 'Image' : 'Label' ); ?>

            <?php if( $table ): ?>
             <tr class="form-field">
                <th scope="row" valign="top"><label for="term-value"><?php _e($label, 'yit'); ?></label></th>
                <td>
            <?php else: ?>
            <div class="form-field">
                <label for="term-value"><?php _e($label, 'yit'); ?></label>
            <?php endif ?>

            <input type="text" name="term-value" id="term-value" value="<?php if ($value) echo $value ?>" data-type="<?php echo $type ?>" />

            <?php if( $table ): ?>
                </td>
                </tr>
            <?php else: ?>
                </div>
            <?php endif ?>
        <?php
        }


        /**
         * Save attribute field
         *
         * @access public
         * @since 1.0.0
         */
        public function attribute_save($term_id, $tt_id, $taxonomy) {
            if (isset($_POST['term-value'])) {
                update_woocommerce_term_meta($term_id, $taxonomy . '_yith_wccl_value', $_POST['term-value']);
            }
        }

        /**
         * Create new column for product attributes
         *
         * @access public
         * @since 1.0.0
         */
        public function product_attribute_columns( $columns ) {
            $temp_cols = array();
            $temp_cols['cb'] = $columns['cb'];
            $temp_cols['yith_wccl_value'] = __('Value', 'yit');
            unset($columns['cb']);
            $columns = array_merge($temp_cols, $columns);
            return $columns;
        }

        /**
         * Print the column content
         *
         * @access public
         * @since 1.0.0
         */
        public function product_attribute_column($columns, $column, $id) {
            global $taxonomy, $wpdb;

            if ($column == 'yith_wccl_value') {
                $attribute = substr($taxonomy, 3);
                $attribute = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name = '$attribute'");
                $att_type 	= $attribute->attribute_type;

                $value = get_woocommerce_term_meta($id, $taxonomy . '_yith_wccl_value');
                $columns .= $this->_print_attribute_column( $value, $att_type );
            }

            return $columns;
        }


        /**
         * Print the column content according to attribute type
         *
         * @access public
         * @since 1.0.0
         */
        protected function _print_attribute_column( $value, $type ) {
            $output = '';

            if( $type == 'colorpicker' ) {
                $output = '<span class="yith-wccl-color" style="background-color:'. $value .'"></span>';
            } elseif( $type == 'label' ) {
                $output = '<span class="yith-wccl-label">'. $value .'</span>';
            } elseif( $type == 'image' ) {
                $output = '<img class="yith-wccl-image" src="'. $value .'" alt="" />';
            }

            return $output;
        }


        /**
         * Print select for product variations
         *
         *
         */
        function product_option_terms( $tax, $i ) {
            global $woocommerce, $thepostid;

            if( in_array( $tax->attribute_type, array( 'colorpicker', 'image', 'label' ) ) ) {

                $attribute_taxonomy_name = $woocommerce->attribute_taxonomy_name( $tax->attribute_name );

?>
                <select multiple="multiple" data-placeholder="<?php _e( 'Select terms', 'yit' ); ?>" class="multiselect attribute_values" name="attribute_values[<?php echo $i; ?>][]">
                    <?php
                    $all_terms = get_terms( $attribute_taxonomy_name, 'orderby=name&hide_empty=0' );
                    if ( $all_terms ) {
                        foreach ( $all_terms as $term ) {
                            $has_term = has_term( (int) $term->term_id, $attribute_taxonomy_name, $thepostid ) ? 1 : 0;
                            echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $has_term, 1, false ) . '>' . $term->name . '</option>';
                        }
                    }
                    ?>
                </select>
                <button class="button plus select_all_attributes"><?php _e( 'Select all', 'yit' ); ?></button> <button class="button minus select_no_attributes"><?php _e( 'Select none', 'yit' ); ?></button>
                <button class="button fr plus add_new_attribute" data-attribute="<?php echo $attribute_taxonomy_name; ?>"><?php _e( 'Add new', 'yit' ); ?></button>
<?php
            }
        }
    }
}
