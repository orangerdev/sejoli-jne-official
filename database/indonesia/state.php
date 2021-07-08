<?php
namespace Sejoli_Jne_Official\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class that responsible to database-functions for State data
 * @since   1.0.0
 */
Class State extends \Sejoli_Jne_Official\Database
{
    /**
     * Table name
     * @since   1.0.0
     */
    static protected $table = 'sejoli_jne_shipping_state';

    /**
     * Class properties
     * @since   1.0.0
     */
    static protected $props  = array(
        'name'      => NULL,
        'code'      => NULL
    );

    /**
     * Class given data
     * @since   1.0.0
     */
    static protected $data = array();

    /**
     * Create table if not exists
     * @return void
     */
    static public function create_table()
    {
        parent::$table = self::$table;

        if( ! Capsule::schema()->hasTable( self::table() ) ):

            Capsule::schema()->create( self::table(), function( $table ){

                $table->increments  ('ID');
                $table->string      ('name');
                $table->string     	('code');

            });

        endif;
    }

}
