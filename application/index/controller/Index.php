<?php

namespace app\index\controller;

use app\admin\model\Crowdfunding;
use app\admin\model\UserPurchase;
use app\comm\JSSDK;
use app\index\model\User;
use think\Controller;
use think\Request;
use think\Session;
use WxJsApi\lib\WxPayConfig;
use WxJsApi\lib\WxWebUserApi;


class Index extends Controller {
	protected $openid;
	protected $cycle_id;

	public function __construct( Request $request = null ) {
		parent::__construct( $request );
		$this->openid = Session::get( 'openid' );
		if ( ! $this->openid ) {
			$tool     = new WxWebUserApi();
			$userInfo = $tool->GetUserinfo();
			$u        = new User();
			$u->UpdateInfo( $userInfo );
			$this->openid = $userInfo['openid'];
			Session::set( 'openid', $userInfo['openid'] );
		}
		$jssdk      = new JSSDK( WxPayConfig::APPID, WxPayConfig::APPSECRET );
		$signPackge = $jssdk->getSignPackage();
		$this->assign( 'signPackage', $signPackge );
	}

	public function index( $cycle_id = null ) {
		try {
			if ( ! $cycle_id ) {
				$cf = Crowdfunding::get( function ( $query ) {
					$query->where( 'status', 'in', '0,1' )
					      ->order( 'start_time', 'desc' );
				} );
			} else {
				$cf = Crowdfunding::get( [ 'cycle_id' => $cycle_id ] );
			}
			$this->cycle_id = $cf['cycle_id'];
			$up             = new UserPurchase();
			$summary        = $up->where( [ 'cycle_id' => $cf['cycle_id'], 'cancel_tag' => '0', 'status' => '9' ] )
			                     ->field( 'ifnull(sum(money),0) as cur_money, ifnull(count(distinct(openid)),0) as cur_cnt' )
			                     ->find();
		} catch ( \Exception $e ) {
			return $this->error( '出现未知错误' );
		}

		return $this->fetch( 'index', [
			'cf'      => $cf,
			'summary' => $summary
		] );
	}

	public function chose( $cycle_id ) {

		//获取合买当期信息
		$cf  = Crowdfunding::get( $cycle_id );
		$cfi = $cf->getItems();

		//已购买情况
		$up  = new UserPurchase();
		$upg = $up->field( 'sum(money) as pmoney' )
		          ->where( [ 'cycle_id' => $cycle_id, 'cancel_tag' => '0' ] )
		          ->where( 'status', '<>', '1' )
		          ->find();

		return $this->fetch( 'chose', [
			'cycle_id' => $cycle_id,
			'items'    => $cfi,
			'openid'   => $this->openid,
			'pmoney'   => $upg->pmoney
		] );
	}

	/**
	 * 显示订单完成页面
	 */
	public function mysuccess() {
		return $this->fetch();
	}

	/**
	 * 查看个人订单
	 */
	public function track() {

		$ups = UserPurchase::all( function ( $query ) {
			$query->where( [ 'openid' => $this->openid, 'cancel_tag' => '0', 'status' => '9' ] )
			      ->order( 'update_time', 'desc' );
		} );

		return $this->fetch( 'track', [ 'ups' => $ups ] );
	}

	public function test() {
		$a = new UserPurchase();
		$up_sum = $a->field('sum(money) as money')
		            ->where(['cycle_id' => '2018025','status' => '9' ,'cancel_tag' => '0'])
		            ->find();
		$b = new Crowdfunding();
		$cf_sum = $b->field('sum(money) as money')
		            ->where(['cycle_id' => '2018025'])
		            ->find();

		dump($cf_sum->money);
		dump($up_sum->money);
		if($cf_sum->money == $up_sum->money){
//			Crowdfunding::update(['status' => '1',
//			                      'end_time' => time()
//			],['cycle_id' => $data['attach']]);
			echo true;
		}
	}
}
