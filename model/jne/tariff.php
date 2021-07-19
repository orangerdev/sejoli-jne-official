<?php
namespace Sejoli_Jne_Official\Model\JNE;

use Sejoli_Jne_Official\Model\Main as Eloquent;

class Tariff extends Eloquent
{
    /**
     * The table associated with the model without prefix.
     *
     * @var string
     */
    protected $table = 'sejoli_jne_shipping_jne_tariff';

    /**
     * The label for this class.
     *
     * @var string
     */
    protected $label = 'JNE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
	protected $fillable = [
	   'jne_origin_id', 'jne_destination_id', 'tariff_data'
	];

    public static function get_available_services() {

        return self::$available_services;

    }

    /**
     * Set tariff data.
     *
     * @param  string  $value
     * @return void
     */
    public function setTariffDataAttribute( $value ) {

        $this->attributes['tariff_data'] = serialize( $value );

    }

    /**
     * Get tariff data.
     *
     * @param  string  $value
     * @return string
     */
    public function getTariffDataAttribute( $value ) {

        return unserialize( $value );

    }

    /**
     * Define relationship with Origin model
     *
     * @since    1.0.0
     * @return  string
     */
    public function origin() {

        return $this->belongsTo( 'Sejoli_Jne_Official\Model\JNE\Origin', 'jne_origin_id' );

    }

    /**
     * Define relationship with Destination model
     *
     * @since    1.0.0
     * @return  string
     */
    public function destination() {
        
        return $this->belongsTo( 'Sejoli_Jne_Official\Model\JNE\Destination', 'jne_destination_id' );
    
    }

    /**
     * Get tariff label.
     *
     * @param   $rate array of service information
     * @return  string
     */
    public function getLabel( $rate ) {

        $label = array();

        if( $this->label ) {
            $label[] = $this->label;
        }

        if( $rate->service_display ) {
            $label[] = $rate->service_display;
        }

        $label = implode( " - ", $label );

        if( $rate->etd_from && $rate->etd_thru ) {

            $label .= ' (';

            if( $rate->etd_from == 1 && $rate->etd_from == $rate->etd_thru ) {
                $label .= $rate->etd_from;

                if( $rate->times == 'D' ) {
                    $label .= ' day';
                }
            } else {
                $label .= $rate->etd_from . '-' . $rate->etd_thru;

                if( $rate->times == 'D' ) {
                    $label .= ' days';
                }
            }

            $label .= ')';
        }

        return $label;
    
    }

    /**
     * Generate Rate ID for shipping methods.
     *
     * @param   $rate rate object
     * @param   $prefix
     * @return  string
     */
    public function getRateID( $prefix, $rate ) {

        $ids       = array( $prefix , $this->label );
        $code      = $rate->service_code;
        $separator = "_";

        $lessthan_pattern       = "/</i";
        $lessthan_placeholder   = "lt";
        $biggerthan_pattern     = "/>/i";
        $biggerthan_placeholder = "bt";

        if( preg_match( $biggerthan_pattern, $code ) ) {
            $code = preg_replace( $biggerthan_pattern, $biggerthan_placeholder, $code );
        } elseif( preg_match( $lessthan_pattern, $code ) ) {
            $code = preg_replace( $lessthan_pattern, $lessthan_placeholder, $code );
        }

        $ids[] = $code;
        return mb_strtolower( \implode( $separator, $ids ) );

    }

}
