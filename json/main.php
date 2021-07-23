<?php
namespace Sejoli_Jne_Official;

Class JSON
{

    /**
     * Construction
     */
    public function __construct() {

    }

    /**
     * Set filter args
     * @since  1.0.0
     * @param  array $args
     * @return array
     */
    protected function set_filter_args($args) {

        $filter = [];

        if(is_array($args) && 0 < count($args)) :
            foreach($args as $_filter) :
                if(
                    !empty($_filter['val']) &&
                    'sejoli-nonce' != $_filter['name'] &&
                    '_wp_http_referer' != $_filter['name']
                ) :
                    if('ID' == $_filter['name']) :
                        $filter[$_filter['name']] = explode(',', $_filter['val']);
                    else :
                        $filter[$_filter['name']] = $_filter['val'];
                    endif;
                endif;
            endforeach;
        endif;

        return $filter;

    }

    /**
     * Set table args
     * @since   1.0.0
     * @param   array $args
     * @return  array
     */
    protected function set_table_args(array $args) {

        $filter = NULL;
        $args   = wp_parse_args($args,[
			'start'  => 0,
			'length' => 10,
			'draw'	 => 1,
            'filter' => [],
            'search' => []
        ]);

        $search = [[
            'name' => 'users',
            'val'  => isset($args['search']['value']) ? $args['search']['value'] : NULL,
        ]];

        $order = array(
            0 => [
            'column'=> 'ID',
            'sort'	=> 'desc'
        ]);

        $columns = [];

        if(isset($args['columns'])) :
            foreach( $args['columns'] as $i => $_column ) :
                $columns[$i] = $_column['data'];
            endforeach;
        else :

            $columns['ID'] = 'desc';

        endif;

        if ( isset( $args['order'] ) && 0 < count( $args['order'] ) ) :
			$i = 0;
			foreach( $args['order'] as $_order ) :
				$order[$i]['sort']   = $_order['dir'];
				$order[$i]['column'] = $columns[$_order['column']];
				$i++;
			endforeach;
		endif;

        $filter = $this->set_filter_args($args['filter']);

        return [
            'start'  => $args['start'],
            'length' => $args['length'],
			'draw'	 => $args['draw'],
            'search' => $search,
            'order'  => $order,
            'filter' => $filter
        ];

    }

    /**
     * Set user options
     * @since   1.0.0
     * @return  json
     */
    public function set_for_options() {

    }

    /**
     * Set table data
     * @since   1.0.0
     * @return  json
     */
    public function set_for_table() {

    }

}
