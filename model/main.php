<?php
namespace Sejoli_Jne_Official\Model;

abstract class Main extends \WeDevs\ORM\Eloquent\Model
{
    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * Make ID guarded -- without this ID doesn't save.
     *
     * @var string
     */
    protected $guarded = [ 'ID' ];

    /**
     * Get model database table
     *
     * @since    1.0.0
     * @return  string
     */
    public function getTable() {

        if ( isset( $this->table ) ){
            $prefix =  $this->getConnection()->db->prefix;
            return $prefix . $this->table;
        }

        return parent::getTable();
    }

}
