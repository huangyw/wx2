<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/3/5
 * Time: 20:46
 */

namespace app\comm;


class comm {

	public static function TimestrToInt( $str ) {

		return mktime( (int) substr( $str, 8, 2 ), (int) substr( $str, 10, 2 ), (int) substr( $str, 12, 2 ), (int) substr( $str, 4, 2 ), (int) substr( $str, 6, 2 ), (int) substr( $str, 0, 4 ) );
	}
}