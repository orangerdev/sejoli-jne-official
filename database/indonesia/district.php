<?php
namespace Sejoli_Jne_Official\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class that responsible to database-functions for District data
 * @since   1.0.0
 */
Class District extends \Sejoli_Jne_Official\Database
{
    /**
     * Table name
     * @since   1.0.0
     */
    static protected $table = 'sejoli_jne_shipping_district';

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
                $table->integer     ('city_id');

            });

        endif;
    }

}
