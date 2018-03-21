<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/2/22
 * Time: 0:29
 */

namespace app\admin\model;


use think\Model;

class UserPurchase extends Model {

	protected $pk = 'out_trade_no';

	public function getUser(){

		return $this->hasOne('User','openid','openid')
		            ->field('openid, nickname, headimgurl')->select();
	}
}