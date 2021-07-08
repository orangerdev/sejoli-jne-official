<?php
namespace Sejoli_Jne_Official\Model;

use Sejoli_Jne_Official\Model\Main as Eloquent;

class District extends Eloquent
{
    /**
     * The table associated with the model without prefix.
     *
     * @var string
     */
    protected $table = 'sejoli_jne_shipping_district';

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
	   'name', 'city_id'
	];

    /**
     * Define relationship with City model
     *
     * @since    1.0.0
     * @return  string
     */
    public function city() {
        return $this->belongsTo( 'Sejoli_Jne_Official\Model\City', 'city_id' );
    }


}
