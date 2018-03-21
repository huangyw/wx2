<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/2/19
 * Time: 14:00
 */

namespace app\admin\model;


use think\Model;

class Crowdfunding extends Model {

	protected $pk = 'cycle_id';

	public static function isExistsCycleId($cycle_id){

		if(!Crowdfunding::get($cycle_id)){
			return false;
		}else{
			return true;
		}
	}

	public function getItems(){

		return $this->hasMany('CrowdfundingItems','cycle_id','cycle_id')
			->field('cycle_id, red, blue, quantity, money')->select();
	}

	public function getPurchaseSum(){

		return $this->hasMany('UserPurchase','cycle_id','cycle_id')
		            ->field('cycle_id, openid, sum(money) as money')
		            ->group('cycle_id, openid')
					->select();
	}
}