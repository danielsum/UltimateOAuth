<?php

//****************************************************************
//****************** UltimateOAuth Version 2.0 *******************
//****************************************************************
//
//                                            作者: @To_aru_User
//
// ****** 概要 ******
// 
// SimpleOAuth, BgOAuth, BgOAuthMulti の機能を結合させ、
// 更に利便性の向上を図ったライブラリです。
// これ1つで何でも出来ます。
// 
// ・https://dev.twitter.com/docs/api/1.1 に記載されている順番に則っています(2013/1/3現在)。
// 　Streaming以外の全てのエンドポイントに対応しております。
// 　このライブラリの独自メソッド
// 　- BgOAuthGetToken
// 　　　usernameとpasswordから疑似的にxAuth認証と同等にアクセストークンを取得可能です。
// 　- toJSON・fromJSON
// 　　　toJSONメソッドの動的コールで復元に必要な情報をJSON文字列化して返します。
// 　　　formJSONメソッドの静的コールでJSON文字列を渡すと復元されたオブジェクトを返します。
// 
// ・バイナリを扱うパラメータのうち、それがバイナリでなく
// 　ファイルパスを表すものの場合、キーの頭に「@」を付けてください。(例：@media[] @image)
// 
// ・非同期リクエストを行いたい場合、
// 　$waitResponseにfalseを指定するようにしてください。
// 　指定可能なメソッドが限られているので確認しておいてください。
// 
// ・リクエストに失敗した場合は必ず
// 　　エラーコード: $res->errors[0]->code
// 　　メッセージ: $res->errors[0]->message
// 　の形のオブジェクトを返します。
// 　クライアント側のエラーの場合、コードは全て-1です。
// 　$waitResponseがfalseの場合はnullを返します。
// 
// ・定数JSON_DECODE_DEFAULT_ASSOCの値をtrueに設定すると、
// 　返り値がオブジェクトを含まず、全て連想配列形式になります。
// 
// ・トークン取得系エンドポイントにアクセスして取得に成功したとき、
// 　自動的に自身のメンバ変数を更新します。
// 
// ・「OAuth.php」やcURLに依存しません。
// 　但し、BgOAuthMultiクラスを利用する場合にのみcURLが必要です。
// 　json_decodeの実装されているPHP5.2以降のほとんどの環境で
// 　動作すると思われます。
// 　構文はPHP5.2への互換性に考慮して、
// 　- 無名関数を直接利用せずにcreate_function関数を利用
// 　- 配列は全てarray構文で宣言
// 　- 配列を返す関数の返り値に直接添え字を付加しない
// 　という仕様にしています。
// 
// ****** バージョンアップでの変更点 ******
// 
// ★…非常に重要な変更
// ●…重要な変更
//
// -Version2.0
// 
// ・POSTで外部からリクエストを受け取って実行する可能性があるので、例外対策を徹底した。
// 　無駄なエラー制御演算子を削除した。
// 　名前空間を汚さないように構造を最適化した。
// 
// ★OAuthRequestメソッドとOAuthRequestImageメソッドをPrivateにして外部からコールできないようにした。
// 　その代わり、トークン取得を除くPOST系の全Publicメソッドで第2引数に$waitResponseを渡せるようにした。
// 
// ★BgOAuthGetTokenの引数の渡し方を他のメソッド同様に配列で渡すように変更した。
// 　これでUltimateOAuthMultiからコール可能なメソッド（get_object_vars・fromJSON・toJSON以外のPublicメソッド）
// 　の引数が$params1つになるように変更した。
// 　それに伴い、実際にコールに使用する関数をcall_user_func_arrayからcall_user_funcに変更した。
// 
// ●UltimateOAuthMultiのレスポンスをデコードして、access_tokenやrequest_tokenが含まれていた場合に
// 　呼び出し側スクリプトのオブジェクトにも反映させるように変更した。
// 
// ●UltimateOAuthMultiでexecした後にaddjobした内容を全て消去するように変更した。
// 
// ★全Publicメソッドの返り値をオブジェクトに統一した。
// 　（Versin1.0では一部ArrayやStringを含んでいた）
// 
// ●全プロパティをPrivateに変更した。
// 　その代わりfromJSONメソッドとtoJSONメソッドを追加し、シリアル化とオブジェクト復元が容易に行えるようにした。
// 
// ●一部のメソッドの名前を変更した。
// 
// ●JSON_DECODE_DEFAULT_ASSOC定数で、このライブラリで外部的に使用するメソッドの中で使われる、
// 　json_decode関数の第2引数の値を指定できるようにした。
// 
// ・POST_oauth_access_tokenで直接oauth_verifierを渡せるようにした。
// 　（Version1.0ではオブジェクトを作り直す必要があった）
// 
// ・$paramsにオブジェクトを渡した場合、配列に変換するようにした。
// 　（Version1.0では配列しか受け付けなかった）
// 
// ・絶対URL取得メソッドを改良した。
// 　引用 http://www.programming-magic.com/20080628015044/
// 
// ・その他数ヵ所修正
// 
// ****** その他 ******
// 
// ・FollwerRequestライブラリに実装している機能の一部もこちらに移植するかもしれない。
// 
// ****** サンプルコード ******
// 
// ★通常利用共通部分
//  $consumer_key        = 'xxxxxxxxxx';
//  $consumer_secret     = 'yyyyyyyyyy';
//  $access_token        = 'zzzzzzzzzz';
//  $access_token_secret = 'wwwwwwwwww';
//  $to = new UltimateOAuth($consumer_key,$consumer_secret,$access_token,$access_token_secret);
// 
// ●ホームタイムラインを取得して実際に表示してみる
//  $timeline = $to->GET_statuses_home_timeline();
//  if (empty($timeline->errors)) {
//    foreach ($timeline as $tweet) {
//      if (isset($tweet->retweeted_status)) {
//        $retweet = $tweet;
//        $tweet = $tweet->retweeted_status;
//      } else {
//        $retweet = null;
//      }
//      $date = new DateTime($tweet->created_at);
//      $date->setTimezone(new DateTimeZone('Asia/Tokyo'));
//      echo "<img src=\"{$tweet->user->profile_image_url}\">\n";
//      echo "{$tweet->user->name}(@{$tweet->user->screen_name})<br />\n";
//      echo nl2br($tweet->text)."<br />\n";
//      echo $date->format('Y.n.j G:i:s')."<br />\n";
//      if ($retweet!==null)
//         echo "Retweeted by {$retweet->user->name}(@{$retweet->user->screen_name})<br />\n";
//      echo "<hr />\n";
//    }
//  } else {
//    $error = $timeline->errors[0];
//    echo "[{$error->code}]{$error->message}<br />\n";
//  }
// 
// ●$_POST['status']の値をツイート、結果も表示
//  if (isset($_POST['status'])) {
//    $res = $to->POST_statuses_update(array('status'=>$_POST['status']));
//    if (empty($res->errors)) {
//      echo "Tweeting Done: $res->text<br />\n";
//    } else {
//      $error = $res->errors[0];
//      $escaped_status = htmlspecialchars(get_magic_quotes_gpc()?stripslashes($_POST['status']):$_POST['status'],ENT_QUOTES);
//      echo "[{$error->code}]{$error->message}: {$escaped_status}<br />\n";
//    }
//  }
// 
// ●同一ディレクトリにあるtest.pngを添付してツイート
//  $to->POST_statuses_update_with_media(array('status'=>'test','@media[]'=>'./test.png'));
// 
// ●「Bomb!」「Bomb!!」「Bomb!!!」…とツイートを10回非同期リクエスト
//  for ($i=1;$i<=10;$i++)
//    $to->POST_statuses_update(array('status'=>'Bomb',str_repeat('!',$i)),false);
// 
// ★バックグラウンドOAuth認証からそのままツイート、結果も表示
//  $consumer_key    = 'xxxxxxxxxx';
//  $consumer_secret = 'yyyyyyyyyy';
//  $username        = 'zzzzzzzzzz';
//  $password        = 'wwwwwwwwww';
//  $to = new UltimateOAuth($consumer_key,$consumer_secret);
//  $res = $to->BgOAuthGetToken(array('username'=>$username,'password'=>$password));
//  $success = false;
//  if (empty($res->errors)) {
//    $res = $to->POST_statuses_update(array('status'=>'Tweeting through BgOAuth'));
//    if (empty($res->errors)) {
//      echo "Tweeting Done: {$res->text}<br />\n";
//      $success = true;
//    }
//  }
//  if (!$success) {
//    $error = $res->errors[0];
//    $escaped_status = htmlspecialchars(get_magic_quotes_gpc()?stripslashes($_POST['status']):$_POST['status'],ENT_QUOTES);
//    echo "[{$error->code}]{$error->message}: {$escaped_status}<br />\n"; 
//  }
// 
// ★複数のバックグラウンドOAuth認証を並列処理で実行する
//  $consumer_key    = 'xxxxxxxxxx';
//  $consumer_secret = 'yyyyyyyyyy';
//  $to = new UltimateOAuth($consumer_key,$consumer_secret);
//  $to_0 = clone ($to_1 = clone ($to_2 = clone $to));
//  $uom = new UltimateOAuthMulti();
//  $uom->addjob($to_0,'BgOAuthGetToken',array('id_0','pw_0'));
//  $uom->addjob($to_1,'BgOAuthGetToken',array('id_1','pw_1'));
//  $uom->addjob($to_2,'BgOAuthGetToken',array('id_2','pw_2'));
//  $res = $uom->exec();
//  var_dump($res);
// 

# json_decodeの第2パラメータ指定
define('JSON_DECODE_DEFAULT_ASSOC',false);

/**** UltimateOAuth基本クラス *****/

class UltimateOAuth {
	
	/*************************************/
	/*********** OutSider Area ***********/
	/*************************************/
	
	//***** General *****//
	
	# 設定
	private $url_header = 'https://api.twitter.com/1.1/';
	private $authening_url_header = 'https://api.twitter.com/';
	private $oauth_url_header = 'https://api.twitter.com/';
	private $activity_url_header = 'https://api.twitter.com/i/';
	private $default_stringify_ids = true;
	private $trim_user_in_user_timeline = true;
	
	# コンストラクタ
	public function __construct($consumer_key,$consumer_secret,$oauth_token='',$oauth_token_secret='',$oauth_verifier='') {
		$this->consumer_key    = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		if (self::castable_array(array($oauth_token,$oauth_token_secret))) {
			if (strpos($oauth_token,'-')===false) {
				$this->request_token        = $oauth_token;
				$this->request_token_secret = $oauth_token_secret;
				$this->access_token         = '';
				$this->access_token_secret  = '';
			} else {
				$this->access_token         = $oauth_token;
				$this->access_token_secret  = $oauth_token_secret;
				$this->request_token        = '';
				$this->request_token_secret = '';
			}
		}
		$this->oauth_verifier  = $oauth_verifier;
		$this->cookie = array();
	}
	
	# JSONから復元
	public static function fromJSON($json) {
		$obj = null;
		if (self::castable($json))
			$obj = json_decode($json);
		if (!is_object($obj))
			$obj = new stdClass;
		$consumer_key         = isset($obj->consumer_key)         ? $obj->consumer_key         : '' ;
		$consumer_secret      = isset($obj->consumer_secret)      ? $obj->consumer_secret      : '' ;
		$access_token         = isset($obj->access_token)         ? $obj->access_token         : '' ;
		$access_token_secret  = isset($obj->access_token_secret)  ? $obj->access_token_secret  : '' ;
		$request_token        = isset($obj->request_token)        ? $obj->request_token        : '' ;
		$request_token_secret = isset($obj->request_token_secret) ? $obj->request_token_secret : '' ;
		$oauth_verifier       = isset($obj->oauth_verifier)       ? $obj->oauth_verifier       : '' ;
		$className = __CLASS__;
		if (!empty($access_token) && !empty($access_token_secret))
			return new $className($consumer_key,$consumer_secret,$access_token,$access_token_secret);
		else
			return new $className($consumer_key,$consumer_secret,$request_token,$request_token_secret,$oauth_verifier);
	}
	
	# JSONに変換
	public function toJSON() {
		$obj = new stdClass;
		$obj->consumer_key    = $this->consumer_key;
		$obj->consumer_secret = $this->consumer_secret;
		if (!empty($this->access_token) && !empty($this->access_token_secret)) {
			$obj->access_token        = $this->access_token;
			$obj->access_token_secret = $this->access_token_secret;
		} else {
			$obj->request_token        = $this->request_token;
			$obj->request_token_secret = $this->request_token_secret;
			$obj->oauth_verifier       = $this->oauth_verifier;
		}
		return json_encode($obj);
	}
		
	//***** Timelines *****//
	
	# GET statuses/mentions_timeline
	public function GET_statuses_mentions_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/mentions_timeline.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/user_timeline
	# ユーザーオブジェクトをデフォルトではidのみに簡略化
	public function GET_statuses_user_timeline($params=array()) {
		self::modParameters($params);
		if (!isset($params['trim_user']) && $this->trim_user_in_user_timeline)
			$params['trim_user'] = '1';
		$res = $this->OAuthRequest($this->url_header.'statuses/user_timeline.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/home_timeline
	public function GET_statuses_home_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/home_timeline.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/retweets_of_me
	public function GET_statuses_retweets_of_me($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/retweets_of_me.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Tweets *****//
	
	# GET statuses/retweets/:id
	public function GET_statuses_retweets($params=array()) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."statuses/retweets/{$id}.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/show/:id
	public function GET_statuses_show($params=array()) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."statuses/show/{$id}.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/destroy/:id
	public function POST_statuses_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."statuses/destroy/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/update
	public function POST_statuses_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/update.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/retweet/:id
	public function POST_statuses_retweet($params=array(),$waitResponse=true) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."statuses/retweet/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/update_with_media
	public function POST_statuses_update_with_media($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'statuses/update_with_media.json',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/oembed
	public function GET_statuses_oembed($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header."statuses/retweet/{$id}.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET search/tweets
	public function GET_search_tweets($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'search/tweets.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Dicrect Messages *****//
	
	# GET direct_messages
	public function GET_direct_messages($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET direct_messages/sent
	public function GET_direct_messages_sent($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/sent.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET direct_messages_show
	public function GET_direct_messages_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST direct_messages_destroy
	public function POST_direct_messages_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST direct_messages_new
	public function POST_direct_messages_new($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/new.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Friends & Followers *****//
	
	# GET friends/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friends_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && $this->stringify_ids)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest($this->url_header.'friends/ids.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET followers/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_followers_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && $this->stringify_ids)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest($this->url_header.'followers/ids.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/lookup
	public function GET_friendships_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/lookup.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/incoming
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_incoming($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && $this->stringify_ids)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest($this->url_header.'friendships/incoming.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/outgoing
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_outgoing($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && $this->stringify_ids)
			$params['stringify_ids'] = '1';
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/outgoing.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/create
	public function POST_friendships_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/destroy
	public function POST_friendships_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/update
	public function POST_friendships_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/update.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/show
	public function GET_friendships_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friends/list
	# デフォルトではcursorに-1を設定
	public function GET_friends_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'friends/list.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET followers/list
	# デフォルトではcursorに-1を設定
	public function GET_followers_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'followers/list.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Users *****//
	
	# GET account/settings
	public function GET_account_settings() {
		$res = $this->OAuthRequest($this->url_header.'account/settings.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET account/verify_credentials
	public function GET_account_verify_credentials($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/verify_credentials.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/settings
	public function POST_account_settings($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/settings.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_delivery_device
	public function POST_account_update_delivery_device($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_delivery_device.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile
	public function POST_account_update_profile($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_background_image
	public function POST_account_update_profile_background_image($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'account/update_profile_background_image.json',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_colors
	public function POST_account_update_profile_colors($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile_colors.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_image
	public function POST_account_update_profile_image($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'account/update_profile_image.json',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET blocks/list
	# デフォルトではcursorに-1を設定
	public function GET_blocks_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'blocks/list.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET blocks/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_blocks_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && $this->stringify_ids)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest($this->url_header.'blocks/ids.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST blocks/create
	public function POST_blocks_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST blocks/destroy
	public function POST_blocks_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/lookup
	public function GET_users_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/lookup.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/show
	public function GET_users_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/search
	public function GET_users_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/search.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/contributees
	public function GET_users_contributees($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/contributees.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/contributors
	public function GET_users_contributors($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/contributors.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/remove_profile_banner
	public function POST_account_remove_profile_banner($dummy=array(),$waitResponse=true) {
		$res = $this->OAuthRequest($this->url_header.'account/remove_profile_banner.json','POST',array(),$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_banner
	public function POST_account_update_profile_banner($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile_banner.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/profile_banner
	public function GET_users_profile_banner($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/profile_banner.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Suggested Users *****//
	
	# GET users/suggestions/:slug
	# GET users/suggestions
	public function GET_users_suggestions($params=array()) {
		self::modParameters($params);
		if (isset($params['slug']))
			$res = $this->OAuthRequest($this->url_header.'users/suggestions/'.$params['slug'].'.json','GET',$params);
		else
			$res = $this->OAuthRequest($this->url_header.'users/suggestions.json','GET',array());
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/suggestions/:slug/members
	public function GET_users_suggestions_members($params=array()) {
		self::modParameters($params);
		if (isset($params['slug'])) {
			$slug = $params['slug'];
			unset($params['slug']);
		} else {
			$slug = '';
		}
		$res = $this->OAuthRequest($this->url_header."users/suggestions/{$slug}/members.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Favorites *****//
	
	# GET favorites/list
	public function GET_favorites_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/list.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST favorites/destroy
	public function POST_favorites_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST favorites/create
	public function POST_favorites_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Lists *****//
	
	# GET lists/list
	public function GET_lists_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/list.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/statuses
	public function GET_lists_statuses($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/statuses.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/destroy
	public function POST_lists_members_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/memberships
	# デフォルトではcursorに-1を設定
	public function GET_lists_memberships($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'lists/memberships.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscribers
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscribers($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/subscribers/create
	public function POST_lists_subscribers_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscribers/show
	public function GET_lists_subscribers_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/subscribers/destroy
	public function POST_lists_subscribers_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/create_all
	public function POST_lists_members_create_all($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/create_all.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/members/show
	public function GET_lists_members_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/members
	# デフォルトではcursorに-1を設定
	public function GET_lists_members($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'lists/members.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/create
	public function POST_lists_members_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/destroy
	public function POST_lists_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/update
	public function POST_lists_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/update.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/create
	public function POST_lists_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/show
	public function GET_lists_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/show.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscriptions
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscriptions($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest($this->url_header.'lists/subscriptions.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/destroy_all
	public function POST_lists_members_destroy_all($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/destroy_all.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Saved Searches *****//
	
	# GET saved_searches/list
	public function GET_saved_searches_list() {
		$res = $this->OAuthRequest($this->url_header.'saved_searches/list.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET saved_searches/show/:id
	public function GET_saved_searches_show($params=array()) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."saved_searches/show/{$id}.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST saved_searches/create
	public function POST_saved_searches_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'saved_searches/create.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST saved_searches/show/:id
	public function POST_saved_searches_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest($this->url_header."saved_searches/destroy/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Places & Geo *****//
	
	# GET geo/id/:place_id
	public function GET_geo_id($params=array()) {
		self::modParameters($params);
		if (isset($params['place_id'])) {
			$place_id = $params['place_id'];
			unset($params['place_id']);
		} else {
			$place_id = '';
		}
		$res = $this->OAuthRequest($this->url_header."geo/id/{$place_id}.json",'GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/id/reverse_geocode
	public function GET_geo_id_reverse_geocode($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/id/reverse_geocode.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/search
	public function GET_geo_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/search.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/similar_places
	public function GET_geo_similar_places($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/similar_places.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST geo/place
	public function POST_geo_place($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/place.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Trends *****//
	
	# GET trends/place
	public function GET_trends_place($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'trends/place.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET trends/available
	public function GET_trends_available() {
		$res = $this->OAuthRequest($this->url_header.'trends/available.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET trends/closest
	public function GET_trends_closest($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'trends/closest.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Spam Reporting *****//
	
	# POST users/report_spam
	public function POST_users_report_spam($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/report_spam.json','POST',$params,$waitResponse);
		if (!$waitResponse)
			return;
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** OAuth *****//
	
	# GET oauth/authenticate
	public function getAuthenticateURL() {
		if ($this->construct_error())
			return json_decode('{"errors":[{"message":"Invalid parameters for __construct method","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
		if (empty($this->request_token))
			return json_decode('{"errors":[{"message":"No request_token","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
		return json_decode(
			'{"url":'.
				json_encode($this->authening_url_header.'oauth/authenticate?oauth_token='.$this->request_token).
			'}',
			JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# GET oauth/authorize
	public function getAuthorizeURL() {
		if ($this->construct_error())
			return json_decode('{"errors":[{"message":"Invalid parameters for __construct method","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
		if (empty($this->request_token))
			return json_decode('{"errors":[{"message":"No request_token","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
		return json_decode(
			'{"url":'.
				json_encode($this->authening_url_header.'oauth/authorize?oauth_token='.$this->request_token).
			'}',
			JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# POST oauth/access_token
	public function POST_oauth_access_token($params=array()) {
		self::modParameters($params);
		if (isset($params['oauth_verifier']))
			$this->oauth_verifier = $params['oauth_verifier'];
		$this->OAuthRequest($this->oauth_url_header.'oauth/access_token','POST');
		return json_decode(
			(empty($this->access_token) || empty($this->access_token_secret)) ?
			'{"access_token":"'.$this->access_token.'","access_token_secret":"'.$this->access_token_secret.'"}':
			'{"errors":[{"message":"Couldn\'t get access_token","code":-1}]}',
			JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# POST oauth/request_token
	public function POST_oauth_request_token() {
		$this->OAuthRequest($this->oauth_url_header.'oauth/request_token','POST');
		return json_decode(
			(empty($this->request_token) || empty($this->request_token_secret)) ?
			'{"request_token":"'.$this->request_token.'","request_token_secret":"'.$this->request_token_secret.'"}':
			'{"errors":[{"message":"Couldn\'t get request_token","code":-1}]}',
			JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# BgOAuthGetToken（username・passwordからアクセストークンを取得）
	//
	// 必要パラメータ
	//   username - スクリーンネームまたはメールアドレス
	//   password - パスワード
	//
	public function BgOAuthGetToken($params=array()) {
		if ($this->construct_error()) {
			$res = '{"errors":[{"message":"Invalid parameters for __construct method","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		self::modParameters($params);
		if (!isset($params['username'])) {
			$res = '{"errors":[{"message":"No username","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!isset($params['password'])) {
			$res = '{"errors":[{"message":"No password","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!self::castable($params['username'])) {
			$res = '{"errors":[{"message":"Invalid username format","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!self::castable($params['password'])) {
			$res = '{"errors":[{"message":"Invalid password format","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/request_token','POST');
		if ($res===false || empty($this->request_token) || empty($this->request_token_secret)) {
			$res = '{"errors":[{"message":"Failed to get request_token","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$q = 'force_login=true&oauth_token='.$this->request_token;
		$request  = '';
		$request .= 'GET /oauth/authorize?'.$q.' HTTP/1.1'."\r\n";
		$request .= 'Host: api.twitter.com'."\r\n";
		$request .= 'User-Agent: '.__CLASS__."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('api.twitter.com','https',$request);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when fetching authenticity_token","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$pattern = '@<input name="authenticity_token" type="hidden" value="(.+?)" />@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Failed to get authenticity_token","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$q = http_build_query(
			array(
				'authenticity_token' => $matches[1],
				'oauth_token' => $this->request_token,
				'force_login' => '1',
				'session[username_or_email]' => $params['username'],
				'session[password]' => $params['password']
			),'','&'
		);
		$request  = '';
		$request .= 'POST /oauth/authorize HTTP/1.1'."\r\n";
		$request .= 'Host: api.twitter.com'."\r\n";
		$request .= 'User-Agent: '.__CLASS__."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('api.twitter.com','https',$request);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when logining","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$pattern = '@oauth_verifier=(.+?)"|<code>(.+?)</code>@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Wrong username or password","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		$this->oauth_verifier = (!empty($matches[1])) ? $matches[1] : $matches[2];
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/access_token','POST');
		if ($res===false || empty($this->access_token) || empty($this->access_token_secret)) {
			$res = '{"errors":[{"message":"Failed to get access_token","code":-1}]}';
			return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
		}
		return json_decode(
			'{"access_token":"'.$this->access_token.'","access_token_secret":"'.$this->access_token_secret.'"}',
			JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	//***** Activity *****//
	
	# GET activity/by_friends
	public function GET_activity_by_friends() {
		$res = $this->OAuthRequest($this->activity_url_header.'activity/by_friends.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET activity/about_me
	public function GET_activity_about_me() {
		$res = $this->OAuthRequest($this->activity_url_header.'activity/about_me.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Help *****//
	
	# GET help/configuration
	public function GET_help_configuration() {
		$res = $this->OAuthRequest($this->url_header.'help/configuration.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/languages
	public function GET_help_languages() {
		$res = $this->OAuthRequest($this->url_header.'help/languages.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/privacy
	public function GET_help_privacy() {
		$res = $this->OAuthRequest($this->url_header.'help/privacy.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/tos
	public function GET_help_tos() {
		$res = $this->OAuthRequest($this->url_header.'help/tos.json');
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET application/rate_limit_status
	public function GET_application_rate_limit_status($params=array()) {
		$res = $this->OAuthRequest($this->url_header.'application/rate_limit_status.json','GET',$params);
		return json_decode($res,JSON_DECODE_DEFAULT_ASSOC);
	}
	
	/************************************/
	/*********** Insider Area ***********/
	/************************************/
	
	private $consumer_key;
	private $consumer_secret;
	private $access_token;
	private $access_token_secret;
	private $request_token;
	private $request_token_secret;
	private $oauth_verifier;
	private $cookie;
	
	// ソケット接続
	private function connect($host,$scheme,$request,$waitResponse=true) {
		if (strtolower($scheme)==='https') {
			$host = 'ssl://'.$host;
			$port = 443;
		} else {
			$port = 80;
		}
		$fp = @fsockopen($host,$port);
		if ($fp===false)
			return false;
		fwrite($fp,$request);
		$ret = '';
		if ($waitResponse) {
			ob_start();
			fpassthru($fp);
			$res = ob_get_clean();
			$res = explode("\r\n\r\n",$res,2);
			$ret = isset($res[1])?$res[1]:$res[0];
		}
		fclose($fp);
		if (!$waitResponse)
			return;
		if (isset($res[1]) && preg_match('/^Set-Cookie:(.+?)$/mi',$res[0],$matches)===1) {
			$parts = explode(';',$matches[1]);
			foreach ($parts as $part) {
				$part = trim($part);
				if (strpos($part,'=')<1 || substr_count($part,'=')!==1)
					continue;
				list($key,$value) = explode('=',$part,2);
				if (in_array($key,array('expires','path','domain','secure')))
					continue;
				$this->cookie[$key] = $value;
			}
		}
		return $ret;
	}
	
	// OAuthリクエスト
	private function OAuthRequest($url,$method='GET',$params=array(),$waitResponse=true) {
		if ($this->construct_error())
			return '{"errors":[{"message":"Invalid parameters for __construct method","code":-1}]}';
		$element = self::getUriElements($url);
		if ($element===false)
			return '{"errors":[{"message":"Invalid URL","code":-1}]}';
		parse_str($element['query'],$temp);
		$params += $temp;
		$content = $this->getParameters($element['scheme'].'://'.$element['host'].$element['path'],$method,$params);
		if ($method==='GET')
			$element['path'] .= '?'.$content;
		$request  = '';
		$request .= $method.' '.$element['path'].' HTTP/1.1'."\r\n";
		$request .= 'Host: '.$element['host']."\r\n";
		$request .= 'User-Agent: '.__CLASS__."\r\n";
		$request .= 'Connection: Close'."\r\n";
		if ($method==='POST') {
			$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$request .= 'Content-Length: '.strlen($content)."\r\n";
		}
		$request .= "\r\n";
		if ($method==='POST')
			$request .= $content;
		$res = $this->connect($element['host'],$element['scheme'],$request,$waitResponse);
		if (!$waitResponse)
			return;
		if ($res===false)
			return '{"errors":[{"message":"Failed to connect","code":-1}]}';
		if (preg_match('@oauth/(?:(request)|access)_token@',$element['path'],$matches)===1) {
			parse_str($res,$oauth_tokens);
			if (empty($matches[1])) {
				if (isset($oauth_tokens['oauth_token']))
					$this->access_token = $oauth_tokens['oauth_token'];
				if (isset($oauth_tokens['oauth_token_secret']))
					$this->access_token_secret = $oauth_tokens['oauth_token_secret'];
			} else {
				if (isset($oauth_tokens['oauth_token']))
					$this->request_token = $oauth_tokens['oauth_token'];
				if (isset($oauth_tokens['oauth_token_secret']))
					$this->request_token_secret = $oauth_tokens['oauth_token_secret'];
			}
		}
		return $res;
	}
	
	// マルチパートOAuthリクエスト (ファイルパスを示すものはパラメータ名先頭に「@」を付加)
	private function OAuthRequestImage($url,$params=array(),$waitResponse=true) {
		if ($this->construct_error())
			return '{"errors":[{"message":"Invalid parameters for __construct method","code":-1}]}';
		$element = self::getUriElements($url);
		if ($element===false)
			return '{"errors":[{"message":"Invalid URL","code":-1}]}';
		parse_str($element['query'],$temp);
		$params += $temp;
		$boundary = '------------------'.md5(time());
		$content = '';
		foreach ($params as $key => $value) {
			$content .= '--'.$boundary."\r\n";
			if (strpos($key,'@')===0) {
				$binary = @file_get_contents($value);
				$content .= 'Content-Disposition: form-data; name="'.substr($key,1).'"; filename="'.basename($value)."\"\r\n";
				$content .= 'Content-Type: application/octet-stream'."\r\n";
				$content .= "\r\n";
				$content .= $binary."\r\n";
			} else {
				$content .= 'Content-Disposition: form-data; name="'.$key."\"\r\n";
				$content .= "\r\n";
				$content .= $value."\r\n";
			}
		}
		$content .= '--'.$boundary.'--';
		$request  = '';
		$request .= 'POST '.$element['path'].' HTTP/1.1'."\r\n";
		$request .= 'Host: '.$element['host']."\r\n";
		$request .= 'User-Agent: '.__CLASS__."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Authorization: OAuth '.$this->getParameters($url,'POST',array(),true)."\r\n";
		$request .= 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n";
		$request .= 'Content-Length: '.strlen($content)."\r\n";
		$request .= "\r\n";
		$request .= $content;
		$res = $this->connect($element['host'],$element['scheme'],$request,$waitResponse);
		if (!$waitResponse)
			return;
		if ($res===false)
			return '{"errors":[{"message":"Failed to connect","code":-1}]}';
		if (preg_match('@oauth/(?:(request)|access)_token@',$element['path'],$matches)===1) {
			parse_str($res,$oauth_tokens);
			if (empty($matches[1])) {
				if (isset($oauth_tokens['oauth_token']))
					$this->access_token = $oauth_tokens['oauth_token'];
				if (isset($oauth_tokens['oauth_token_secret']))
					$this->access_token_secret = $oauth_tokens['oauth_token_secret'];
			} else {
				if (isset($oauth_tokens['oauth_token']))
					$this->request_token = $oauth_tokens['oauth_token'];
				if (isset($oauth_tokens['oauth_token_secret']))
					$this->request_token_secret = $oauth_tokens['oauth_token_secret'];
			}
		}
		return $res;
	}
	
	// URLをパースして最適化
	private static function getUriElements($url) {
		if (!self::castable($url))
			return false;
		$parsed = parse_url($url);
		if (empty($parsed) || !isset($parsed['host']))
			return false;
		if (!isset($parsed['scheme']))
			$parsed['scheme'] = 'http';
		if (!isset($parsed['path']))
			$parsed['path'] = '/';
		if (!isset($parsed['query']))
			$parsed['query'] = '';
		return $parsed;
	}
	
	// OAuthリクエスト用パラメータ取得
	private function getParameters($url,$method='GET',$opt=array(),$asHeader=false) {
		$method = strtoupper($method);
		$enc = create_function('$s','return str_replace("%7E","~",rawurlencode($s));');
		$nsort = create_function('$a','uksort($a,"strnatcmp");return $a;');
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$parameters = array(
			'oauth_consumer_key' => $this->consumer_key,
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp' => time(),
			'oauth_nonce' => md5(microtime().mt_rand()),
			'oauth_version' => '1.0'
		);
		if (strpos($url,'oauth/request_token')!==false) {
			$oauth_token_secret = '';
		} elseif (preg_match('@oauth/(?:authorize|authenticate)@',$url)===1) {
			$parameters['oauth_token'] = $this->request_token;
			$oauth_token_secret = $this->request_token_secret;
		} elseif (strpos($url,'oauth/access_token')!==false) {
			$parameters['oauth_token'] = $this->request_token;
			$oauth_token_secret = $this->request_token_secret;
			if (!empty($this->oauth_verifier))
				$parameters['oauth_verifier'] = $this->oauth_verifier;
		} else {
			$parameters['oauth_token'] = $this->access_token;
			$oauth_token_secret = $this->access_token_secret;
		}
		$parameters += $opt;
		$body = implode('&',array_map($enc,array($method,$url,implode('&',$toPairs($nsort(array_map($enc,$parameters)))))));
		$key = implode('&',array_map($enc,array($this->consumer_secret,$oauth_token_secret)));
		$parameters['oauth_signature'] = base64_encode(hash_hmac('sha1',$body,$key,true));
		return implode(($asHeader)?', ':'&',$toPairs(array_map($enc,$parameters)));
	}
	
	// ラッパーメソッドのパラメータ最適化
	private static function modParameters(&$params) {
		$_params = array();
		if (is_object($params))
			$params = (array)$params;
		if (is_array($params))
		foreach ($params as $key => $value) {
			if ($value===false)
				$_params[$key] = '0';
			elseif ($value!==null && self::castable($value))
				$_params[$key] = $value;
		}
		$params = $_params;
	}
	
	// スカラー型か判断
	private static function castable($var) {
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	private static function castable_array($array) {
		foreach ($array as $var)
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	
	// エラーチェック
	private function construct_error() {
		return !self::castable_array(array(
			$this->consumer_key   , $this->consumer_secret      ,
			$this->access_token   , $this->access_token_secret  ,
			$this->request_token  , $this->request_token_secret ,
			$this->oauth_verifier
		));
	}
	
	// メンバ変数取得
	public function get_object_vars() {
		return get_object_vars($this);
	}
	
}

/**** UltimateOAuthマルチリクエスト用クラス *****/

class UltimateOAuthMulti {
	
	private $obj;
	private $chs;
	private $vars;
	private $url;
	const baseClassName  = 'UltimateOAuth';
	const specialKeyName = '__UltimateOAuthMulti_CALL';
	
	# コンストラクタ
	public function __construct() {
		$this->chs   = array();
		$this->obj   = array();
		$this->vars  = array();
		$this->url   = $this->getURL();
	}
	
	# ジョブ追加
	// 
	// 第1引数 - UltimateOAuthオブジェクト
	// 第2引数 - UltimateOAuthクラス内のPublicメソッド名（fromJSON・toJSON・get_object_varsを除く）
	// 第3引数 - パラメータ
	// 
	// ※ 第1引数は参照渡しです（NULLなどを渡すと例外が発生します）
	// ※ 「アクセストークン取得→ツイート」など、明確に順番が決まっているものに関しては
	// 　 同時に並列実行することは避けてください（そうした場合の挙動は未定義です）
	//
	public function addjob(&$UltimateOAuthObject,$method,$params=array()) {
		$i = count($this->chs);
		$this->obj[$i] =& $UltimateOAuthObject;
		if (!is_callable('curl_init') || !is_callable(array($UltimateOAuthObject,$method))) {
			$this->chs[$i] = null;
			$this->vars[$i] = null;
			return;
		}
		$obj = new stdClass;
		$this->vars[$i] = $UltimateOAuthObject->get_object_vars();
			$obj->consumer_key       = (!empty($this->vars[$i]['consumer_key']))    ? $this->vars[$i]['consumer_key']    : ''              ;
			$obj->consumer_secret    = (!empty($this->vars[$i]['consumer_secret'])) ? $this->vars[$i]['consumer_secret'] : ''              ;
		if (!empty($this->vars[$i]['access_token']) && !empty($this->vars[$i]['access_token_secret'])) {
			$obj->oauth_token        = $this->vars[$i]['access_token'];
			$obj->oauth_token_secret = $this->vars[$i]['access_token_secret'];
			$obj->oauth_verifier     = '';
		} elseif (!empty($this->vars[$i]['request_token']) && !empty($this->vars[$i]['request_token_secret'])) {
			$obj->oauth_token        = $this->vars[$i]['access_token'];
			$obj->oauth_token_secret = $this->vars[$i]['access_token_secret'];
			$obj->oauth_verifier     = (!empty($this->vars[$i]['oauth_verifier']))  ? $this->vars[$i]['oauth_verifier']  : ''              ;
		} else {
			$obj->oauth_token        = '';
			$obj->oauth_token_secret = '';
			$obj->oauth_verifier     = '';
		}
			$obj->method             = $method;
			$obj->params             = (is_array($params)||is_object($params))      ? (object)$params                    : (object)array() ;
		$this->chs[$i] = curl_init();
		curl_setopt($this->chs[$i],CURLOPT_URL,$this->url);
		curl_setopt($this->chs[$i],CURLOPT_POST,true);
		curl_setopt($this->chs[$i],CURLOPT_POSTFIELDS,http_build_query(array(self::specialKeyName=>rawurlencode(base64_encode(json_encode($obj)))),'','&'));
		curl_setopt($this->chs[$i],CURLOPT_RETURNTRANSFER,true);
	}
	
	# マルチリクエスト実行（結果は追加した順番に配列で返ります）
	public function exec() {
		$className = self::baseClassName;
		$res = array();
		if (empty($this->chs))
			return $res;
		$count = count($this->chs);
		if (is_callable('curl_multi_init'))
			$mh = curl_multi_init();
		foreach ($this->chs as $ch)
			if (is_resource($ch))
				curl_multi_add_handle($mh,$ch);
		$active = 0;
		do {
			curl_multi_exec($mh,$active);
		} while ($active>0);
		foreach ($this->chs as $i => $ch) {
			if ($this->url===false) {
				$res[$i] = json_decode('{"errors":[{"message":"Failed to get URL to this file itself","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
			} elseif (!is_callable('curl_multi_init')) {
				$res[$i] = json_decode('{"errors":[{"message":"cURL functions are not installed on this server","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
			} elseif (is_null($ch)) {
				$res[$i] = json_decode('{"errors":[{"message":"Can\'t construct UltimateOAuth object or use its method","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
			} elseif ($error=curl_error($ch)) {
				$res[$i] = json_decode('{"errors":[{"message":"cURL error occurred (message: '.$error.')","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
			} elseif (($temp=json_decode(curl_multi_getcontent($ch)))===null) {
				$res[$i] = json_decode('{"errors":[{"message":"Failed to get valid cURL content (Requests from this server to itself may be blocked)","code":-1}]}',JSON_DECODE_DEFAULT_ASSOC);
			} else {
				$res[$i] = $temp;
				if (JSON_DECODE_DEFAULT_ASSOC) {
					if (!empty($temp['access_token']) && !empty($temp['access_token_secret']))
						$this->obj[$i] = new $className($this->vars[$i]['consumer_key'],$this->vars[$i]['consumer_secret'],$temp['access_token'],$temp['access_token_secret']);
					elseif (!empty($temp['request_token']) && !empty($temp['request_token_secret']))
						$this->obj[$i] = new $className($this->vars[$i]['consumer_key'],$this->vars[$i]['consumer_secret'],$temp['request_token'],$temp['request_token_secret'],$this->vars[$i]['oauth_verifier']);
				} else {
					if (!empty($temp->access_token) && !empty($temp->access_token_secret))
						$this->obj[$i] = new $className($this->vars[$i]['consumer_key'],$this->vars[$i]['consumer_secret'],$temp->access_token,$temp->access_token_secret);
					elseif (!empty($temp->request_token) && !empty($temp->request_token_secret))
						$this->obj[$i] = new $className($this->vars[$i]['consumer_key'],$this->vars[$i]['consumer_secret'],$temp->request_token,$temp->request_token_secret,$this->vars[$i]['oauth_verifier']);
				}
			}
			if (is_resource($ch)) {
				curl_multi_remove_handle($mh,$ch);
				curl_close($ch);
			}
		}
		if (is_callable('curl_multi_init'))
			curl_multi_close($mh);
		$this->obj  = array();
		$this->chs  = array();
		$this->vars = array();
		return $res;
	}
	
	// 絶対URL取得
	private function getURL() {
		$document_root_url = $_SERVER['SCRIPT_NAME'];
		$document_root_path = $_SERVER['SCRIPT_FILENAME'];
		while (basename($document_root_url)===basename($document_root_path)) {
			$document_root_url = dirname($document_root_url);
			$document_root_path = dirname($document_root_path);
		}
		if ($document_root_path==='/')
			$document_root_path = '';
		if ($document_root_url==='/')
			$document_root_url = '';
		$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https': 'http';
		$document_root_url = $protocol.'://'.$_SERVER['SERVER_NAME'].$document_root_url;
		$absolute_path = realpath(__FILE__);
		if (!$absolute_path)
			return false;
		if (substr($absolute_path,-1)!=='/' && substr(__FILE__,-1)==='/')
			$absolute_path .= '/';
		$url = strtr($absolute_path,array($document_root_path=>$document_root_url));
		if ($absolute_path===$url)
			return false;
		return $url;
	}
	
	// コールから起動
	public static function call() {
		if (isset($_POST[self::specialKeyName])) {
			$className = self::baseClassName;
			$property = json_decode(base64_decode(rawurldecode($_POST[self::specialKeyName])));
			if (!isset($property->consumer_key)       && !call_user_func(array($className,'castable'),$property->consumer_key)      )
				$property->consumer_key       = '';
			if (!isset($property->consumer_secret)    && !call_user_func(array($className,'castable'),$property->consumer_secret)   )
				$property->consumer_secret    = '';
			if (!isset($property->oauth_token)        && !call_user_func(array($className,'castable'),$property->oauth_token)       )
				$property->oauth_token        = '';
			if (!isset($property->oauth_token_secret) && !call_user_func(array($className,'castable'),$property->oauth_token_secret))
				$property->oauth_token_secret = '';
			if (!isset($property->oauth_verifier)     && !call_user_func(array($className,'castable'),$property->oauth_verifier)    )
				$property->oauth_verifier     = '';
			if (!isset($property->method)             && !call_user_func(array($className,'castable'),$property->method)            )
				$property->method             = '';
			if (!isset($property->params)             && !is_object($property->params)                                              )
				$property->params             = new stdClass;
			$property->params = (array)$property->params;
			$obj = new $className($property->consumer_key,$property->consumer_secret,$property->oauth_token,$property->oauth_token_secret,$property->oauth_verifier);
			if ($property->method==='get_object_vars' || $property->method==='fromJSON' || $property->method==='toJSON' || !is_callable(array($obj,$property->method)))
				echo '{"errors":[{"message":"Can\'t call \''.$property->method.'\'","code":-1}]}';
			else
				echo json_encode(call_user_func(array($obj,$property->method),$property->params));
			exit();
		}
	}
	
}

UltimateOAuthMulti::call();