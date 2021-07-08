<?php
namespace Sejoli_Jne_Official;

Class Database
{
    static protected $table = NULL;

    /**
     * Table define
     * @return [type] [description]
     */
    static protected function table()
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        return $prefix.self::$table;
    }

}
