<?php
namespace Sejoli_Jne_Official;

use Illuminate\Database\Capsule\Manager as Capsule;

Class DBIntegration
{

  /**
   * Connection to wordpress database
   * @var [type]
   */
  public static function connection()
  {

      global $wpdb;

      if(!class_exists('Capsule')):

          $capsule   = new Capsule;
          $host_data = explode(':', DB_HOST);

          $args = [
              'driver'   => 'mysql',
              'host'     => $host_data,
              'database' => DB_NAME,
              'username' => DB_USER,
              'password' => DB_PASSWORD,
              // 'charset'   => $wpdb->charset, // will cause problem in several server
              // 'collation' => $wpdb->collate,
              'prefix'   => '',
              'strict'   => false
          ];

          if(isset($host_data[1])) :
              $args['port'] = $host_data[1];
          endif;

          $capsule->addConnection($args);

          $capsule->setAsGlobal();
          $capsule->bootEloquent();

      endif;

  }
  
}
