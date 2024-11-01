<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class wpUrlList extends WP_List_Table {

    public $example_data = array();
    public $all_data;
    private $PagesSocialStatsPlugin;
    private $active_services;


    function __construct( $PagesSocialStatsPlugin, array $items) {

        $this->PagesSocialStatsPlugin = $PagesSocialStatsPlugin;
        $this->active_services = $this->process_active_services();

        $this->all_data = $this->_process_current_data_structure($items);

        global $status, $page;

        //Set parent defaults
        parent::__construct(
            array(
                //singular name of the listed records
                'singular'	=> 'url_status',
                //plural name of the listed records
                'plural'	=> 'url_statuses',
                //does this table support ajax?
                'ajax'		=> true
            )
        );


    }


    private function process_active_services(){
        $active_services = (array) $this->PagesSocialStatsPlugin->get_active_services();
        $prepared_active_services = array();
        if(!empty($active_services)){
            foreach($active_services as $active_service){
                $prepared_active_services[$active_service] = $this->PagesSocialStatsPlugin->get_service_label_by_code($active_service);
            }
        }
        return $prepared_active_services;
    }


    private function _process_current_data_structure($items){
        $new_items = array();
        if(!empty($items)){
            $counter = 0;
            foreach($items as $url => $item){
                $new_items[$counter] = $item;
                $new_items[$counter]['url'] = $url;
                $new_items[$counter]['total'] = $this->calculate_total_share($item);
                $counter++;
            }
        }
        return $new_items;
    }


    private function calculate_total_share($items){
        $total = 0;
        foreach($items as $key => $value){
            if(array_key_exists($key, $this->active_services)){
                $total += (int) $value;
            }
        }
        return $total;
    }



    public function column_default( $item, $column_name ) {

        if(array_key_exists($column_name, $this->active_services)){
            return isset($item[$column_name]) ? $item[$column_name] : 0;
        }

        switch ( $column_name ) {
            case 'url':
                return $item[ $column_name ];
            case 'ct':
                return date("Y-m-d H:i:s", $item[ $column_name ]);
            case 'total':
                return $item[ 'total' ];
            default:
                //Show the whole array for troubleshooting purposes
                return print_r( $item, true );
        }
    }



    public function column_url( $item ) {
        //Build row actions
        $actions = array(
            'Refresh Status'		=> sprintf( '<a class="referesh_status" href="'.$item['url'].'">Refresh Status</a>', $_REQUEST['page'], 'referesh_status', $item['url'] ),
            'delete'	=> sprintf( '<a class="delete-item" href="'.$item['url'].'">Delete</a>', $_REQUEST['page'], 'delete', $item['url'] ),
        );

        //Return the title contents
        return sprintf('%1$s %2$s',
             $item['url'],
             $this->row_actions( $actions )
        );
    }


    function column_cb( $item ) {

        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  	//Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['url']			//The value of the checkbox should be the record's id
        );
    }

    function get_columns() {
        $columns = array(
            'cb'		=> '<input type="checkbox" />', //Render a checkbox instead of text
            'url'		=> 'URL',


        ) + $this->active_services ;
        $columns += array('total' => 'Total Shares','ct'		=> 'Last Updated');
        return $columns;

    }


    function get_sortable_columns() {
        $sortable_columns = array();
        if(!empty($this->active_services)){
            foreach($this->active_services as $key=>$active_service){
                $sortable_columns[$key] =array($key, false);
            }
        }

        $sortable_columns['total'] = array('total', false);

        /*return $sortable_columns_or = array(
            'title'	 	=> array( 'title', false ),	//true means it's already sorted
            'rating'	=> array( 'rating', false ),
            'director'	=> array( 'director', false )
        ); */

        return $sortable_columns;
    }

    function get_bulk_actions() {

        return $actions = array(
            'delete'	=> 'Delete',
            'refresh'	=> 'Refresh Status'
        );
    }

    function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if( 'delete'=== $this->current_action() ) {
            wp_die( 'Items deleted (or they would be if we had items to delete)!' );
        }

    }


    private static function sort_data_based_on_columns($data, $comp){
        $orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : '';
        $order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
        if($orderby != '' && $order != ''){
            if($order == 'asc') {
                return $data[$orderby] > $comp[$orderby];
            } else {
                return $data[$orderby] < $comp[$orderby];
            }
        }


    }








    function prepare_items() {

        global $wpdb; //This is used only if making any database queries

        /**
         * First, lets decide how many records per page to show
         */
        $per_page = 10;


        /**
         * REQUIRED. Now we need to define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        /**
         * REQUIRED. Finally, we build an array to be used by the class for column
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);


        /**
         * Optional. You can handle your bulk actions however you see fit. In this
         * case, we'll handle them within our package just to keep things clean.
         */
        $this->process_bulk_action();


        /**
         * Instead of querying a database, we're going to fetch the example data
         * property we created for use in this plugin. This makes this example
         * package slightly different than one you might build on your own. In
         * this example, we'll be using array manipulation to sort and paginate
         * our data. In a real-world implementation, you will probably want to
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */
        $data = $this->all_data;
        if( ! empty( $_REQUEST['orderby'] ) ){
            usort($data, array('wpUrlList','sort_data_based_on_columns'));
        }




        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently
         * looking at. We'll need this later, so you should always include it in
         * your own package classes.
         */
        $current_page = $this->get_pagenum();

        /**
         * REQUIRED for pagination. Let's check how many items are in our data array.
         * In real-world use, this would be the total number of items in your database,
         * without filtering. We'll need this later, so you should always include it
         * in your own package classes.
         */
        $total_items = count($data);


        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);


        $total_pages = ceil( $total_items / $per_page );


        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where
         * it can be used by the rest of the class.
         */
        $this->items = $data;


        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args(
            array(
                //WE have to calculate the total number of items
                'total_items'	=> $total_items,
                //WE have to determine how many items to show on a page
                'per_page'	=> $per_page,
                //WE have to calculate the total number of pages
                'total_pages'	=> $total_pages,
                'current' => $current_page,
                // Set ordering values if needed (useful for AJAX)
                'orderby'	=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
                'order'		=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
            )
        );
    }


    function display() {

        wp_nonce_field( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

        echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
        echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

        parent::display();
    }


    function ajax_response() {

        check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

        $this->prepare_items();



        extract( $this->_pagination_args, EXTR_SKIP );

        ob_start();
        if ( ! empty( $_REQUEST['no_placeholder'] ) )
            $this->display_rows();
        else
            $this->display_rows_or_placeholder();
        $rows = ob_get_clean();

        ob_start();
        $this->print_column_headers();
        $headers = ob_get_clean();

        ob_start();
        $this->pagination('top');
        $pagination_top = ob_get_clean();

        ob_start();
        $this->pagination('bottom');
        $pagination_bottom = ob_get_clean();

        $response = array( 'rows' => $rows );
        $response['pagination']['top'] = $pagination_top;
        $response['pagination']['bottom'] = $pagination_bottom;
        $response['column_headers'] = $headers;

        if ( isset( $total_items ) )
            $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

        if ( isset( $total_pages ) ) {
            $response['total_pages'] = $total_pages;
            $response['total_pages_i18n'] = number_format_i18n( $total_pages );
        }

        $response['total_status'] = $this->PagesSocialStatsPlugin->_get_total_status();

        die( json_encode( $response ) );
    }






}