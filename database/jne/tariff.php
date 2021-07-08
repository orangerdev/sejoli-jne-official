<?php
namespace Sejoli_Jne_Official\Database\JNE;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class that responsible to database-functions for City data
 * @since   1.0.0
 */
Class Tariff extends \Sejoli_Jne_Official\Database
{
    /**
     * Table name
     * @since   1.0.0
     */
    static protected $table = 'sejoli_jne_shipping_jne_tariff';

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
                $table->integer     ('jne_origin_id');
                $table->string      ('jne_destination_id');
                $table->text        ('tariff_data');
                $table->datetime    ('created_at');
                $table->datetime    ('updated_at')->default(NULL)->nullable();

            });

        endif;
    }

}
