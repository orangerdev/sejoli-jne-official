<?php
namespace Sejoli_Jne_Official\Model;

use Sejoli_Jne_Official\Model\Main as Eloquent;

class City extends Eloquent
{
    /**
     * The table associated with the model without prefix.
     *
     * @var string
     */
    protected $table = 'sejoli_jne_shipping_city';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
	   'name', 'state_id'
	];

    /**
     * Define relationship with State model
     *
     * @since    1.0.0
     * @return  string
     */
	public function state() {

		return $this->belongsTo( 'Sejoli_Jne_Official\Model\State', 'state_id' );

	}

    /**
     * Define relationship with District model
     *
     * @since    1.0.0
     * @return  string
     */
    public function districts() {

        return $this->hasMany( 'Sejoli_Jne_Official\Model\District', 'city_id' );
        
    }

}
