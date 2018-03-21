<?php
/**
 * Created by PhpStorm.
 * User: Hunagyw23
 * Date: 2018/2/25
 * Time: 11:32
 */

namespace app\index\controller;


use app\admin\model\Crowdfunding;
use app\admin\model\UserPurchase;
use app\comm\comm;
use think\Controller;
use think\Log;
use think\Request;
use WxJsApi\JsApi;
use WxJsApi\lib\WxPayApi;
use WxJsApi\lib\WxPayConfig;
use WxJsApi\lib\WxPayUnifiedOrder;
use WxJsApi\Notify;


class Weixinpay extends Controller {

	/**
	 * 微信支付验证结果
	 */
	public function notify() {
		Log::init( [ 'type' => 'File', 'path' => APP_PATH . 'logs/' ] );
		trace( '进入notify', 'notify' );
		$notify = new Notify();
		$notify->Handle( true );
	}

	/**
	 * 微信支付页面
	 * @param null $fee
	 * @param null $cycle_id
	 * @param null $openid
	 *
	 * @return mixed|void
	 * @throws \WxJsApi\WxPayException
	 * @throws \WxJsApi\lib\WxPayException
	 */
	public function pay($fee = null, $cycle_id = null, $openid = null){
		if($cycle_id && $openid && $fee){

			$input = new WxPayUnifiedOrder();
			$input->SetBody("第".$cycle_id."期合买");
			$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
			$input->SetTotal_fee($fee*100);
			$input->SetAttach($cycle_id);
			$input->SetTime_start(date("YmdHis"));
			$input->SetTime_expire(date("YmdHis", time() + 600));
			$input->SetNotify_url("http://1n510107x3.51mypc.cn/index/Weixinpay/notify");
			$input->SetTrade_type("JSAPI");
			$input->SetOpenid($openid);

			//预留额度
			$up = new UserPurchase( [
				'cycle_id'       => $cycle_id,
				'openid'         => $openid,
				'money'          => $fee,
				'recv_time'      => time(),
				'out_trade_no'   => $input->GetOut_trade_no(),
				'cancel_tag'     => '0',
				'status'         => '0'
			] );
			$up->save();

			$order = WxPayApi::unifiedOrder($input);

			$tool = new JsApi();
			$jsApiParameters = $tool->GetJsApiParameters($order);

			return $this->fetch('pay',[
				'param' => $jsApiParameters,
				'out_trade_no' => $input->GetOut_trade_no()
			]);
		}else{
			return $this->error("参数错误，请重新下单");
		}
	}

	/**
	 * 微信支付结果查询
	 */
	public function orderQuery() {

		$out_trade_no = input('out_trade_no');

		$result = UserPurchase::get(['out_trade_no' => $out_trade_no, 'status' => '9' ,'cancel_tag' => '0']);
		if ($result){
			return true;
		}else{
			return false;
	}
	}
	/**
	 * 微信支付失败
	 */
	public function orderCancel() {

		$a = $_POST['out_trade_no'];
		$result = UserPurchase::get(['out_trade_no' => $a, 'status' => '0']);
		if ($result){
			$result->status = '1';
			$result->save();
			return true;
		}else{
			return false;
		}

	}

	/**
	 * 微信支付预下单
	 */
	public function orderAdvance() {

		$cycle_id = input( 'cycle_id' );
		$fee      = input( 'fee' );

		$total = Crowdfunding::get( [ 'cycle_id' => $cycle_id ] );
		$up    = new UserPurchase();
		$cur   = $up->where( [ 'cycle_id' => $cycle_id, 'cancel_tag' => '0' ] )
		            ->where( 'status', '<>', '1' )
		            ->field( 'ifnull(sum(money),0) as cur_money' )
		            ->find();
		if ( $fee <= $total->money - $cur->cur_money ) {
			return true;
		} else {
			return false;
		}
	}

	public function test() {
		$day    = 365*3;
		$i      = 0;
		$big    = 1219.59;
		$day_big = 0;
		$little = 1112780.41;
		$money = 0;
		$tmoney = 54000;
		for ( $j = 0; $j < $day; $j ++ ) {
//			if ( $day_big < 1000 ) {
//				$tmoney += 1000;
//				$little = $little + 1000 * 21;
//			}
			$day_big = $little * 0.0005;
			$big    = $big + $day_big;
			$little = $little - $little * 0.0005;
			$money  = ( $big - 1400 ) * 0.87;
			echo $j+2 . '.tmoney:'.$tmoney.';little:' . round( $little, 2 ) . ';big:' . round( $big, 2 ) . ';money:' . round( $money, 2 ) . ';day_big:'.round($day_big,2).'<br/>';
		}
		echo '投资' . $i . '天，回收' . $day . '天，收入' . round( $money, 2 ) . '元';
	}
	public function test2() {
		echo phpinfo();
	}
}