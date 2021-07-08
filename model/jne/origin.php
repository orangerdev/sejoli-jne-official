<?php
namespace Sejoli_Jne_Official\Model\JNE;

use Sejoli_Jne_Official\Model\Main as Eloquent;

class Origin extends Eloquent
{
    /**
     * The table associated with the model without prefix.
     *
     * @var string
     */
    protected $table = 'sejoli_jne_shipping_jne_origin';

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
	   'code', 'name'
	];

}
