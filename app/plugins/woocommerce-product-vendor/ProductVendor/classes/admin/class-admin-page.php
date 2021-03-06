<?php

class PV_Admin_Setup
{
	/**
	 * WC > Referrals menu
	 */


	public function __construct()
	{
		add_filter( 'set-screen-option', array( 'PV_Admin_Setup', 'set_table_option' ), 10, 3 );
		add_action( 'admin_menu', array( 'PV_Admin_Setup', 'menu' ) );
	}


	/**
	 *
	 */
	public static function menu()
	{
		$hook = add_submenu_page(
			'woocommerce',
			__( 'Commission', 'wc_product_vendor' ), __( 'Commission', 'wc_product_vendor' ),
			'manage_woocommerce',
			'pv_admin_commissions',
			array( 'PV_Admin_Setup', 'commissions_page' )
		);

		add_action( "load-$hook", array( 'PV_Admin_Setup', 'add_options' ) );
	}


	/**
	 *
	 *
	 * @param unknown $status
	 * @param unknown $option
	 * @param unknown $value
	 *
	 * @return unknown
	 */
	public function set_table_option( $status, $option, $value )
	{
		if ( $option == 'commission_per_page' ) {
			return $value;
		}
	}


	/**
	 *
	 */
	public function add_options()
	{
		global $PV_Admin_Page;

		$args = array(
			'label'   => 'Rows',
			'default' => 10,
			'option'  => 'commission_per_page'
		);
		add_screen_option( 'per_page', $args );

		$PV_Admin_Page = new PV_Admin_Page();

	}


	/**
	 * HTML setup for the WC > Commission page
	 */
	public static function commissions_page()
	{
		global $woocommerce, $PV_Admin_Page;

		$PV_Admin_Page->prepare_items();

		?>

		<div class="wrap">

			<div id="icon-woocommerce" class="icon32 icon32-woocommerce-reports"><br/></div>
			<h2><?php _e( 'Commission', 'wc_product_vendor' ); ?></h2>

			<form id="posts-filter" method="POST">

				<input type="hidden" name="page" value="pv_admin_commissions"/>
				<?php $PV_Admin_Page->display() ?>

			</form>
			<div id="ajax-response"></div>
			<br class="clear"/>
		</div>
	<?php
	}


}


if ( !class_exists( 'WP_List_Table' ) ) require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * WC_Simple_Referral_Admin class.
 *
 * @extends WP_List_Table
 */
class PV_Admin_Page extends WP_List_Table
{

	public $index;


	/**
	 * __construct function.
	 *
	 * @access public
	 */
	function __construct()
	{
		global $status, $page;

		$this->index = 0;

		//Set parent defaults
		parent::__construct( array(
								  'singular' => 'commission',
								  'plural'   => 'commissions',
								  'ajax'     => false
							 ) );
	}


	/**
	 * column_default function.
	 *
	 * @access public
	 *
	 * @param unknown $item
	 * @param mixed   $column_name
	 *
	 * @return unknown
	 */
	function column_default( $item, $column_name )
	{
		global $wpdb;

		switch ( $column_name ) {
			case 'id' :
				return $item->id;
			case 'vendor_id' :
				$user = get_userdata( $item->vendor_id );

				return '<a href="' . admin_url( 'user-edit.php?user_id=' . $item->vendor_id ) . '">' . PV_Vendors::get_vendor_shop_name( $item->vendor_id ) . '</a>';
			case 'total_due' :
				return woocommerce_price( $item->total_due + $item->total_shipping + $item->tax );
			case 'product_id' :
				return '<a href="' . admin_url( 'post.php?post=' . $item->product_id . '&action=edit' ) . '">' . get_the_title( $item->product_id ) . '</a>';
			case 'order_id' :
				return '<a href="' . admin_url( 'post.php?post=' . $item->order_id . '&action=edit' ) . '">' . $item->order_id . '</a>';
			case 'status' :
				return $item->status;
			case 'time' :
				return date_i18n( get_option( 'date_format' ), strtotime( $item->time ) );
		}
	}


	/**
	 * column_cb function.
	 *
	 * @access public
	 *
	 * @param mixed $item
	 *
	 * @return unknown
	 */
	function column_cb( $item )
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			'id',
			/*$2%s*/
			$item->id
		);
	}


	/**
	 * get_columns function.
	 *
	 * @access public
	 * @return unknown
	 */
	function get_columns()
	{
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'product_id' => __( 'Product', 'wc_product_vendor' ),
			'order_id'   => __( 'Order ID', 'wc_product_vendor' ),
			'vendor_id'  => __( 'Vendor', 'wc_product_vendor' ),
			'total_due'  => __( 'Total', 'wc_product_vendor' ),
			'status'     => __( 'Status', 'wc_product_vendor' ),
			'time'       => __( 'Date', 'wc_product_vendor' ),
		);

		return $columns;
	}


	/**
	 * get_sortable_columns function.
	 *
	 * @access public
	 * @return unknown
	 */
	function get_sortable_columns()
	{
		$sortable_columns = array(
			'time'       => array( 'time', true ),
			'product_id' => array( 'product_id', false ),
			'order_id'   => array( 'order_id', false ),
			'total_due'  => array( 'total_due', false ),
			'status'     => array( 'status', false ),
			'vendor_id'  => array( 'vendor_id', false ),
			'status'     => array( 'status', false ),
		);

		return $sortable_columns;
	}


	/**
	 * Get bulk actions
	 *
	 * @return unknown
	 */
	function get_bulk_actions()
	{
		$actions = array(
			'mark_paid'     => __( 'Mark paid', 'wc_product_vendor' ),
			'mark_due'      => __( 'Mark due', 'wc_product_vendor' ),
			'mark_reversed' => __( 'Mark reversed', 'wc_product_vendor' ),
			// 'delete' => __('Delete', 'wc_product_vendor'),
		);

		return $actions;
	}


	/**
	 *
	 */
	function extra_tablenav( $which )
	{
		if ( $which == 'top' ) {
			?>
			<div class="alignleft actions"><?php
			$this->months_dropdown( 'commission' );
			submit_button( __( 'Filter' ), false, false, false, array( 'id' => "post-query-submit", 'name' => 'do-filter' ) );
			?></div><?php
		}
	}


	/**
	 * Display a monthly dropdown for filtering items
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @param unknown $post_type
	 */
	function months_dropdown( $post_type )
	{
		global $wpdb, $wp_locale;

		$table_name = $wpdb->prefix . "pv_commission";

		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( time ) AS year, MONTH( time ) AS month
			FROM $table_name
			ORDER BY time DESC
		" );

		$month_count = count( $months );

		if ( !$month_count || ( 1 == $month_count && 0 == $months[ 0 ]->month ) )
			return;

		$m = isset( $_POST[ 'm' ] ) ? (int) $_POST[ 'm' ] : 0;
		?>
		<select name="m">
			<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 == $arc_row->year )
					continue;

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
	<?php
	}


	/**
	 * Process bulk actions
	 *
	 * @return unknown
	 */
	function process_bulk_action()
	{
		if ( !isset( $_POST[ 'id' ] ) ) return;

		$items = array_map( 'intval', $_POST[ 'id' ] );
		$ids   = implode( ',', $items );

		switch ( $this->current_action() ) {
			case 'mark_paid':
				$result = $this->mark_paid( $ids );

				if ( $result )
					echo '<div class="updated"><p>' . __( 'Commission marked paid.', 'wc_product_vendor' ) . '</p></div>';
				break;

			case 'mark_due':
				$result = $this->mark_due( $ids );

				if ( $result )
					echo '<div class="updated"><p>' . __( 'Commission marked due.', 'wc_product_vendor' ) . '</p></div>';
				break;

			case 'mark_reversed':
				$result = $this->mark_reversed( $ids );

				if ( $result )
					echo '<div class="updated"><p>' . __( 'Commission marked reversed.', 'wc_product_vendor' ) . '</p></div>';
				break;

			default:
				// code...
				break;
		}

	}


	/**
	 *
	 *
	 * @param unknown $ids (optional)
	 *
	 * @return unknown
	 */
	public function mark_paid( $ids = array() )
	{
		global $wpdb;

		$table_name = $wpdb->prefix . "pv_commission";

		$query = "SELECT sum(`total_due` + `total_shipping` + `tax`) as total, `vendor_id` FROM `{$table_name}` WHERE `status` = 'due' AND `id` IN ($ids) GROUP BY `vendor_id`";
		$dues  = $wpdb->get_results( $query );
		if ( empty( $dues ) ) return false;

		foreach ( $dues as $due ) {
			PV_Vendors::update_total_due( $due->vendor_id, ( $due->total * -1 ) );
		}

		$query  = "UPDATE `{$table_name}` SET `status` = 'paid' WHERE id IN ($ids) AND `status` = 'due'";
		$result = $wpdb->query( $query );

		return $result;
	}


	/**
	 *
	 *
	 * @param unknown $ids (optional)
	 *
	 * @return unknown
	 */
	public function mark_reversed( $ids = array() )
	{
		global $wpdb;

		$table_name = $wpdb->prefix . "pv_commission";

		$query = "SELECT sum(`total_due` + `total_shipping` + `tax`) as total, `vendor_id` FROM `{$table_name}` WHERE `status` = 'due' AND `id` IN ($ids) GROUP BY `vendor_id`";
		$dues  = $wpdb->get_results( $query );
		if ( empty( $dues ) ) return false;

		foreach ( $dues as $due ) {
			PV_Vendors::update_total_due( $due->vendor_id, ( $due->total * -1 ) );
		}

		$query  = "UPDATE `{$table_name}` SET `status` = 'reversed' WHERE id IN ($ids) AND `status` = 'due'";
		$result = $wpdb->query( $query );

		return $result;
	}


	/**
	 *
	 *
	 * @param unknown $ids (optional)
	 *
	 * @return unknown
	 */
	public function mark_due( $ids = array() )
	{
		global $wpdb;

		$table_name = $wpdb->prefix . "pv_commission";

		$query = "SELECT sum(`total_due` + `total_shipping` + `tax`) as total, `vendor_id` FROM `{$table_name}` WHERE `status` != 'due' AND `id` IN ($ids) GROUP BY `vendor_id`";
		$dues  = $wpdb->get_results( $query );
		if ( empty( $dues ) ) return false;

		foreach ( $dues as $due ) {
			PV_Vendors::update_total_due( $due->vendor_id, ( $due->total ) );
		}

		$query  = "UPDATE `{$table_name}` SET `status` = 'due' WHERE id IN ($ids)";
		$result = $wpdb->query( $query );

		return $result;
	}


	/**
	 * prepare_items function.
	 *
	 * @access public
	 */
	function prepare_items()
	{
		global $wpdb;

		$per_page     = $this->get_items_per_page( 'commission_per_page', 10 );
		$current_page = $this->get_pagenum();

		$orderby = !empty( $_REQUEST[ 'orderby' ] ) ? esc_attr( $_REQUEST[ 'orderby' ] ) : 'time';
		$order   = ( !empty( $_REQUEST[ 'order' ] ) && $_REQUEST[ 'order' ] == 'asc' ) ? 'ASC' : 'DESC';

		/**
		 * Init column headers
		 */
		$this->_column_headers = $this->get_column_info();


		/**
		 * Process bulk actions
		 */
		$this->process_bulk_action();

		/**
		 * Get items
		 */
		$sql = "SELECT COUNT(id) FROM {$wpdb->prefix}pv_commission";

		if ( !empty( $_POST[ 'm' ] ) ) {
			$year  = substr( $_POST[ 'm' ], 0, 4 );
			$month = substr( $_POST[ 'm' ], 4, 2 );

			$time_sql = "
				WHERE MONTH(`time`) = '$month'
				AND YEAR(`time`) = '$year'
			";

			$sql .= $time_sql;
		}

		$max = $wpdb->get_var( $sql );

		$sql = "
			SELECT * FROM {$wpdb->prefix}pv_commission
		";

		if ( !empty( $_POST[ 'm' ] ) ) {
			$sql .= $time_sql;
		}

		$sql .= "
			ORDER BY `{$orderby}` {$order}
			LIMIT %d, %d
		";

		$this->items = $wpdb->get_results( $wpdb->prepare( $sql, ( $current_page - 1 ) * $per_page, $per_page ) );

		/**
		 * Pagination
		 */
		$this->set_pagination_args( array(
										 'total_items' => $max,
										 'per_page'    => $per_page,
										 'total_pages' => ceil( $max / $per_page )
									) );
	}


}
