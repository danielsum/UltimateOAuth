<?php

//***************************************************************
//***************** UltimateOAuth Version 3.42 ******************
//***************************************************************
//
//                                           作者: @To_aru_User
//
//
// ******  概　要  ******
// 
// TwitterAPIエンドポイントとの通信に関することならこれ1つで全てこなせるライブラリです。
// PHP5.2以上で動作します。UltimateOAuthMultiクラス以外はcURLに依存しません。
//
// https://dev.twitter.com/docs/api/1.1 に記載されている順番に則っています(2013/1/5現在)。
// Streaming以外の全てのエンドポイントに加え、アクティビティにも対応しています。
// 「for Official」と記載のあるものに関しては公式キーのみから使用が可能です。
// 独自にバックグラウンドOAuth認証(疑似xAuth)でアクセストークンを取得するメソッド、
// KeitaiWeb経由で非公開アカウントのフォローリクエストの承認・拒否をするメソッドも独自に実装しています。
// バックグラウンドOAuth認証の仕様が前提ですが、UltimateOAuthRotateクラスで自動的に
// GETリクエストのAPI規制を回避する機能も実装しています。
// エンドポイントにアクセスするメソッドは全て、パラメータの渡し方を
// 第1引数に「キーと値を対応させた連想配列」を渡す、というデザインで統一しています。
// 
// 
// 
// ****** 使用方法 ******
//
// 
// ・用語
// 　【エラーオブジェクト】
// 　　エラーコード: $response->errors[0]->code    = エラーコード;
// 　　メッセージ　: $response->errors[0]->message = 'メッセージ';
// 
// 
// 　+++ UltimateOAuth の定数設定(右オペランドにデフォルト値を記載) +++
// 
// 　　　(bool) JSON_DECODE_DEFAULT_ASSOC  = false
// 　　　　内部的に使われているjson_decode関数の第2引数の値。trueを指定するとあらゆる
// 　　　　「*(stdClass)」「*(mixed)」表記のものが全て連想配列に変換されて返されます。
// 
// 　　　(bool) DEFAULT_STRINGIFY_IDS      = true
// 　　　　パラメータにstringify_idsを渡せるものに関して、それらのデフォルト値を設定します。
// 　　　　trueにするとidがIntegerではなくStringで返されます。
// 
// 　　　(string) URL_HEADER                 = 'https://api.twitter.com/1.1/'
// 　　　(string) AUTHENING_URL_HEADER       = 'https://api.twitter.com/'    
// 　　　(string) OAUTH_URL_HEADER           = 'https://api.twitter.com/'    
// 　　　(string) ACTIVITY_URL_HEADER        = 'https://api.twitter.com/i/'  
// 　　　　このあたりは基本的に編集不要です。httpsではなくhttpを利用したい場合などに変更してください。
// 
// 
// 　+++ UltimateOAuth のPublicメソッド +++
// 
// 　　- クラス変数と同名のメソッド
// 　　　　(string) consumer_key          設定されているconsumer_keyの値を返します。
// 　　　　(string) consumer_secret       設定されているconsumer_secretの値を返します。
// 　　　　(string) access_token          設定されているaccess_tokenの値を返します。
// 　　　　(string) access_token_secret   設定されているaccess_token_secretの値を返します。
// 　　　　(string) request_token         設定されているrequest_tokenの値を返します。
// 　　　　(string) request_token_secret  設定されているrequest_token_secretの値を返します。
// 　　　　(string) oauth_verifier        設定されているoauth_verifierの値を返します。
// 　　　　(string) authenticity_token_k  設定されているKeitaiWeb用のauthenticity_tokenの値を返します。
// 　　　　(array)  cookie                設定されているクッキーを連想配列で返します。
// 　　　　(array)  cookie_k              設定されているKeitaiWeb用のクッキーを連想配列で返します。
//
// 　　- (void) __construct
// 　　　　$consumer_keyと$consumer_secretは（事実上）必須です。
// 　　　　$access_token,$access_token_secret,$request_token,$request_token_secret,$oauth_verifierは必要に応じて渡してください。
// 　　　　それ以降の引数はライブラリ側が使用する用途に限ります。
// 
// 　　- (string) save
// 　　- static (UltimateOAuth) load
// 　　　　saveメソッドの動的コールでUltimateOAuthオブジェクト復元に必要な情報を簡易的に暗号化したシリアルで返します。
// 　　　　loadメソッドの静的コールで第1引数にシリアルを渡すと、(可能な限り)復元した状態でUltimateOAuthオブジェクトを返します。
// 
// 　　- *(stdClass) BgOAuthGetToken
// 　　　　疑似的にxAuth認証と同等にアクセストークンを取得可能できます。
// 　　　　　$params = array(
// 　　　　　　'username'=>'スクリーンネーム又はメールアドレス',
// 　　　　　　'password'=>'パスワード'
// 　　　　　);
// 　　　　の形で第1引数にパラメータを渡すと、成功時に
// 　　　　　$response->access_token        = 'アクセストークン';
// 　　　　　$response->access_token_secret = 'アクセストークンシークレット';
// 　　　　の形でレスポンスを返します。
// 　　　　このとき、使用したUltimateOAuthオブジェクトの中に取得したトークンが自動的に設定されます。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 
// 　　- *(stdClass) getAuthenticateURL
// 　　- *(stdClass) getAuthorizeURL
// 　　　　成功時に
// 　　　　　$response->url = 'URL';
// 　　　　の形でレスポンスを返します。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 
// 　　- *(stdClass) POST_oauth_request_token
// 　　　　成功時に
// 　　　　　$response->request_token        = 'リクエストトークン';
// 　　　　　$response->request_token_secret = 'リクエストトークンシークレット';
// 　　　　の形でレスポンスを返します。
// 　　　　このとき、使用したUltimateOAuthオブジェクトの中に取得したトークンが自動的に設定されます。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 
// 　　- *(stdClass) POST_oauth_access_token
// 　　　　成功時に
// 　　　　　$response->access_token        = 'アクセストークン';
// 　　　　　$response->access_token_secret = 'アクセストークンシークレット';
// 　　　　の形でレスポンスを返します。
// 　　　　このとき、使用したUltimateOAuthオブジェクトの中に取得したトークンが自動的に設定されます。
// 　　　　なおこちらは、再度オブジェクトを作り直さなくても
// 　　　　　$params = array(
// 　　　　　　'oauth_verifier'=>'ベリファイア'
// 　　　　　);
// 　　　　の形でパラメータを渡してベリファイアを設定することができます。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 
// 　　- *(stdClass) kWeb_login
// 　　　　KeitaiWebにログインします。
// 　　　　　$params = array(
// 　　　　　　'username'=>'スクリーンネーム又はメールアドレス',
// 　　　　　　'password'=>'パスワード'
// 　　　　　);
// 　　　　の形で第1引数にパラメータを渡してください。
// 　　　　成功時に
// 　　　　　$response->result                = 'リザルトメッセージ';
// 　　　　　$response->cookie_k              = KeitaiWeb用のクッキーの配列,
// 　　　　　$response->authenticity_token_k' = 'KeitaiWeb用のauthenticity_token'
// 　　　　の形でレスポンスを返します。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 　　　　ログイン状態は保存されますが、authenticity_tokenの有効期限にはご注意ください。
// 
// 　　- *(stdClass) kWeb_incoming
// 　　　　先頭カーソル(-1)で表示しきれる分の簡易ユーザーオブジェクトを配列で取得できます。
// 　　　　成功時に
// 　　　　　$response->penders               = 簡易ユーザーオブジェクトの配列;
// 　　　　　$response->cookie_k              = KeitaiWeb用のクッキーの配列,
// 　　　　　$response->authenticity_token_k' = 'KeitaiWeb用のauthenticity_token'
// 　　　　の形でレスポンスを返します。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 　　　　これで得られる簡易ユーザーオブジェクトは、
// 　　　　　$obj->id, $obj->id_str, $obj->screen_name, $obj->name, $obj->profile_image_url
// 　　　　から構成されています。
// 　　　　プロフィール画像はKeitaiWebサイズのため、通常より小さめになっています。
// 
// 　　　　※ 全てのフォローリクエストを正しく取得したい場合はGET_friendships_incomingメソッドをご利用ください。
// 　　　　
// 　　- *(stdClass) kWeb_accept
// 　　- *(stdClass) kWeb_deny
// 　　　　(数字のみの)ユーザーIDを対象に、フォローリクエストを承認・拒否します。
// 　　　　　$params = array(
// 　　　　　　'user_id'=>'ユーザーID'
// 　　　　　);
// 　　　　の形で第1引数にパラメータを渡してこのメソッドをコールすると、
// 　　　　成功時に
// 　　　　　$response->result                = 'リザルトメッセージ';
// 　　　　　$response->cookie_k              = KeitaiWeb用のクッキーの配列,
// 　　　　　$response->authenticity_token_k' = 'KeitaiWeb用のauthenticity_token'
// 　　　　の形でレスポンスを返します。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 　　　　第2引数にレスポンスを待機するかどうかをブール値で渡すことが出来ます。
// 　　　　デフォルトではtrueです。レスポンス非待機時にはnullが返されます。
//
// 　　- *(stdClass) kWeb_accept_all
// 　　　　全てのフォローリクエストを承認します。
// 　　　　成功時に
// 　　　　　$response->result                = 'リザルトメッセージ';
// 　　　　　$response->cookie_k              = KeitaiWeb用のクッキーの配列,
// 　　　　　$response->authenticity_token_k' = 'KeitaiWeb用のauthenticity_token'
// 　　　　の形でレスポンスを返します。
// 　　　　失敗時にはエラーオブジェクトを返します。
// 　　　　第2引数にレスポンスを待機するかどうかをブール値で渡すことが出来ます。
// 　　　　デフォルトではtrueです。レスポンス非待機時にはnullが返されます。
// 
// 　　- *(mixed) その他の名前が「GET_」「POST_」で始まる多くのメソッド
// 　　　　　$params = array(
// 　　　　　　'パラメータ名1'=>'値1',
// 　　　　　　'パラメータ名2'=>'値2',
// 　　　　　　'パラメータ名3'=>'値3',
// 　　　　　　…
// 　　　　　);
// 　　　　の形でTwitterデベロッパサイトの公式ドキュメントの通りに、第1引数に連想配列でパラメータを渡します。
// 　　　　「POST_」のものに関しては、第2引数にレスポンスを待機するかどうかをブール値で渡すことが出来ます。
// 　　　　デフォルトではtrueです。
// 　　　　レスポンス待機時且つ成功時にはレスポンス例として示されているJSONがデコードされた状態で返されます。
// 　　　　レスポンス待機時且つ失敗時にはエラーオブジェクトを返します。
// 　　　　レスポンス非待機時にはnullが返されます。
// 
// 
// 　+++ UltimateOAuthMulti のPublicメソッド +++
// 　このクラスはcURLが使えないと利用できません
// 
// 　　- (void) __construct
// 　　　　引数は不要です。
// 
// 　　- (void) addjob
// 　　　　マルチリクエストのジョブを追加します。
// 　　　　　第1引数 - UltimateOAuthオブジェクト
// 　　　　　第2引数 - UltimateOAuthクラス内のPublicメソッド名
// 　　　　　　　　　　　（返り値がオブジェクトのものに限る）
// 　　　　　第3引数 - 連想配列形式のパラメータ
// 　　　　　　※ 第1引数は参照渡しです。（変数でなく値を渡すと例外が発生します）
// 　　　　　　※ 「アクセストークン取得→ツイート」など、明確に順番が決まっているものに関しては
// 　　　　　　　 同時に並列実行することは避けてください。（そうした場合の挙動は未定義です）
// 
// 　　- (array) exec
// 　　　　追加されたマルチリクエストのジョブを実行します。引数は不要です。
// 　　　　成功時に、追加された順番でレスポンスを配列で返します。
// 　　　　一度execするとaddjobしたものは消去され、まっさらな状態に戻ります。
// 　　　　無料レンタルサーバーなどではこのメソッドがIPブロックにより、
// 　　　　エラーオブジェクトしか返ってこない状態に陥ることがあります。
// 　　　　自前のサーバーでの使用を推奨します。
// 
// 
// 　+++ UltimateOAuthRotate のPublicメソッド +++
// 
// 　　- (stdClass) user
// 　　　　　$userをクローンで返します。
// 　　　　　$user->main                         = UltimateOAuthオブジェクト;
// 　　　　　$user->sub->{'Twitter for iPhone'}  = UltimateOAuthオブジェクト;
// 　　　　　$user->sub->{'Twitter for Android'} = UltimateOAuthオブジェクト;
// 　　　　　…
// 　　　　　$user->i                            = 現在選択中の公式キーの番目を表す値
// 　　　　という構成になっています。
// 
// 　　- (bool) registered
// 　　　　　$registeredの値を返します。既にloadかregisterが行われている場合はtrueとなります。
// 
// 　　- (void) __construct
// 　　　　引数は不要です。コンストラクトした後はregisterメソッドを必ず実行する必要があります。
// 
// 　　- (string) save
// 　　- static (UltimateOAuthRotate) load
// 　　　　saveメソッドの動的コールでUltimateOAuthRotateオブジェクト復元に必要な情報を簡易的に暗号化したシリアルで返します。
// 　　　　loadメソッドの静的コールで第1引数にシリアルを渡すと、(可能な限り)復元した状態でUltimateOAuthRotateオブジェクトを返します。
// 
// 　　- (bool) register
// 　　　　新規にバックグラウンドOAuthで「任意のキー＋公式キー複数」からの認証を実行します。
// 　　　　全ての認証が成功した場合はTrue、それ以外はFalseを返します。
// 　　　　cURLがインストールされている環境では並列実行を試みます。
// 　　　　　$consumer_key, $consumer_secret, $username, $password
// 　　　　は必須です。
// 　　　　第5引数にfalseを指定すると、意図的に逐次実行にすることができます。
// 
// 　　- (mixed) __call [マジックメソッド]
// 　　　　未定義のメソッドが実行されたとき、UltimateOAuthクラス内から実行可能なメソッドを探して、一致した場合はそれを実行します。
// 　　　　エンドポイントへのGETリクエスト/公式キー必須のPOSTリクエストが絡むメソッドに関しては公式キーを1回ごとにローテーションさせて、
// 　　　　API規制回避を自動で行います。
// 　　　　それ以外のメソッドに関してはメインのキーが使われます。
// 　　　　適切なメソッドが見つからなかった場合や、register・loadメソッドのどちらかが実行済みでない場合はエラーオブジェクトを返します。
// 
// 　　- (void) __load
// 　　　　内部的に使用します。
// 
// 
// 
// ****** 注意点 ******
// 
// ★バイナリを扱うパラメータのうち、それがバイナリでなく
// 　ファイルパスを表すものの場合、 "キーの頭" に「@」を付けてください。(例：@media[] @image)
// 
// ・レスポンスのルートがオブジェクトで返されるメソッドと配列で返されるメソッドがあります。
// 　JSON_DECODE_DEFAULT_ASSOCがfalseに設定されているときには、例外処理の方法が異なるので注意が必要です。
// 　成功時に配列・失敗時にオブジェクト、といった形のレスポンス場合、if文の条件式を
// 　　empty($response->errors)
// 　ではなく
// 　　is_array($response)
// 　としなければ、NOTICEエラーを引き起こす原因となります。
// 

/***** UltimateOAuth基本クラス *****/

class UltimateOAuth {
	
	/*************************************/
	/*********** OutSider Area ***********/
	/*************************************/
	
	//***** General *****//
	
	# 設定
	const JSON_DECODE_DEFAULT_ASSOC  = false                         ;
	const DEFAULT_STRINGIFY_IDS      = true                          ;
	const URL_HEADER                 = 'https://api.twitter.com/1.1/';
	const AUTHENING_URL_HEADER       = 'https://api.twitter.com/'    ;
	const OAUTH_URL_HEADER           = 'https://api.twitter.com/'    ;
	const ACTIVITY_URL_HEADER        = 'https://api.twitter.com/i/'  ;
	
	# クラス変数読み取り用
	public function consumer_key()            { return $this->consumer_key;         }
	public function consumer_secret()         { return $this->consumer_secret;      }
	public function access_token()            { return $this->access_token;         }
	public function access_token_secret()     { return $this->access_token_secret;  }
	public function request_token()           { return $this->request_token;        }
	public function request_token_secret()    { return $this->request_token_secret; }
	public function oauth_verifier()          { return $this->oauth_verifier;       }
	public function authenticity_token_k()    { return $this->authenticity_token_k; }
	public function cookie()                  { return $this->cookie;               }
	public function cookie_k()                { return $this->cookie_k;             }
	
	# コンストラクタ
	public function __construct(
		$consumer_key='',$consumer_secret='',$access_token='',$access_token_secret='',
		$request_token='',$request_token_secret='',$oauth_verifier='',
		$authenticity_token_k='',$cookie=array(),$cookie_k=array()
	){
		$this->consumer_key =
			(!empty($consumer_key)         && self::castable($consumer_key)        )
			? $consumer_key                : '' ;
		$this->consumer_secret =
			(!empty($consumer_secret)      && self::castable($consumer_secret)     )
			? $consumer_secret             : '' ;
		$this->access_token =
			(!empty($access_token)         && self::castable($access_token)        )
			? $access_token                : '' ;
		$this->access_token_secret =
			(!empty($access_token_secret)  && self::castable($access_token_secret) )
			? $access_token_secret         : '' ;
		$this->request_token =
			(!empty($request_token)        && self::castable($request_token)       )
			? $request_token               : '' ;
		$this->request_token_secret =
			(!empty($request_token_secret) && self::castable($request_token_secret))
			? $request_token_secret        : '' ;
		$this->oauth_verifier =
			(!empty($oauth_verifier)       && self::castable($oauth_verifier)      )
			? $oauth_verifier              : '' ;
		$this->authenticity_token_k =
			(!empty($authenticity_token_k) && self::castable($authenticity_token_k))
			? $authenticity_token_k        : ''      ;
		$this->cookie = array();
		if (is_array($cookie) || is_object($cookie))
		foreach ($cookie as $k => $v) {
			if (self::castable($v))
				$this->cookie[$k] = $v;
		}
		$this->cookie_k = array();
		if (is_array($cookie_k) || is_object($cookie_k))
		foreach ($cookie_k as $k => $v) {
			if (self::castable($v))
				$this->cookie_k[$k] = $v;
		}
	}
	
	# データから復元
	public static function load($data) {
		if (self::castable($data))
			$obj = json_decode(str_rot13(strrev(base64_decode(urldecode(str_rot13(strrev((string)$data)))))));
		if (!isset($obj))
			$obj = new stdClass;
		$consumer_key =
			(!empty($obj->consumer_key)         && self::castable($obj->consumer_key)        )
			? $obj->consumer_key                : '' ;
		$consumer_secret =
			(!empty($obj->consumer_secret)      && self::castable($obj->consumer_secret)     )
			? $obj->consumer_secret             : '' ;
		$access_token =
			(!empty($obj->access_token)         && self::castable($obj->access_token)        )
			? $obj->access_token                : '' ;
		$access_token_secret =
			(!empty($obj->access_token_secret)  && self::castable($obj->access_token_secret) )
			? $obj->access_token_secret         : '' ;
		$request_token =
			(!empty($obj->request_token)        && self::castable($obj->request_token)       )
			? $obj->request_token               : '' ;
		$request_token_secret =
			(!empty($obj->request_token_secret) && self::castable($obj->request_token_secret))
			? $obj->request_token_secret        : '' ;
		$oauth_verifier =
			(!empty($obj->oauth_verifier)       && self::castable($obj->oauth_verifier)      )
			? $obj->oauth_verifier              : '' ;
		$authenticity_token_k =
			(!empty($obj->authenticity_token_k) && self::castable($obj->authenticity_token_k))
			? $obj->authenticity_token_k        : '' ;
		$cookie = array();
		if (!empty($obj->cookie)   && (is_array($obj->cookie)   || is_object($obj->cookie))  )
		foreach ($obj->cookie as $k => $v) {
			if (self::castable($v) && strlen($k)>0)
				$cookie[$k] = $v;
		}
		$cookie_k = array();
		if (!empty($obj->cookie_k) && (is_array($obj->cookie_k) || is_object($obj->cookie_k)))
		foreach ($obj->cookie_k as $k => $v) {
			if (self::castable($v) && strlen($k)>0)
				$cookie_k[$k] = $v;
		}
		$className = __CLASS__;
		return new $className(
			$consumer_key,$consumer_secret,$access_token,$access_token_secret,
			$request_token,$request_token_secret,$oauth_verifier,
			$authenticity_token_k,$cookie,$cookie_k
		);
	}
	
	# データに出力
	public function save() { 
		return strrev(str_rot13(urlencode(base64_encode(strrev(str_rot13(json_encode(get_object_vars($this))))))));
	}
		
	//***** Timelines *****//
	
	# GET statuses/mentions_timeline
	public function GET_statuses_mentions_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/mentions_timeline.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/user_timeline
	# ユーザーオブジェクトをデフォルトではidのみに簡略化
	public function GET_statuses_user_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/user_timeline.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/home_timeline
	public function GET_statuses_home_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/home_timeline.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/retweets_of_me
	public function GET_statuses_retweets_of_me($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/retweets_of_me.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/retweets/{$id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/show/{$id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/destroy/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/update
	public function POST_statuses_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/update.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/retweet/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST statuses/update_with_media
	public function POST_statuses_update_with_media($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage(self::URL_HEADER.'statuses/update_with_media.json',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/oembed
	public function GET_statuses_oembed($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/retweet/{$id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/:id/activity/summary
	public function GET_statuses_activity_summary($params=array()) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest(self::URL_HEADER."statuses/{$id}/activity/summary.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET statuses/media_timeline (for Official)
	public function GET_statuses_media_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'statuses/media_timeline.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET search/tweets
	public function GET_search_tweets($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'search/tweets.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET search/universal (for Official)
	public function GET_search_universal($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'search/universal.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET conversation/show/:id (for Official)
	public function GET_conversation_show($params=array()) {
		self::modParameters($params);
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		$res = $this->OAuthRequest(self::URL_HEADER."conversation/show/{$id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Dicrect Messages *****//
	
	# GET direct_messages
	public function GET_direct_messages($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'direct_messages.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET direct_messages/sent
	public function GET_direct_messages_sent($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'direct_messages/sent.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET direct_messages_show
	public function GET_direct_messages_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'direct_messages/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST direct_messages_destroy
	public function POST_direct_messages_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'direct_messages/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST direct_messages_new
	public function POST_direct_messages_new($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'direct_messages/new.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Friends & Followers *****//
	
	# GET friends/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friends_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && self::DEFAULT_STRINGIFY_IDS)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest(self::URL_HEADER.'friends/ids.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET followers/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_followers_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && self::DEFAULT_STRINGIFY_IDS)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest(self::URL_HEADER.'followers/ids.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/lookup
	public function GET_friendships_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/lookup.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/incoming
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_incoming($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && self::DEFAULT_STRINGIFY_IDS)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/incoming.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/outgoing
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_outgoing($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && self::DEFAULT_STRINGIFY_IDS)
			$params['stringify_ids'] = '1';
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/outgoing.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/create
	public function POST_friendships_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/destroy
	public function POST_friendships_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/update
	public function POST_friendships_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/update.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/accept (for Official)
	public function POST_friendships_accept($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/accept.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/deny (for Official)
	public function POST_friendships_deny($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/deny.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST friendships/accept_all (for Official)
	public function POST_friendships_accept_all($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/accept_all.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friendships/show
	public function GET_friendships_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'friendships/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET friends/list
	# デフォルトではcursorに-1を設定
	public function GET_friends_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'friends/list.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET followers/list
	# デフォルトではcursorに-1を設定
	public function GET_followers_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'followers/list.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Users *****//
	
	# GET account/settings
	public function GET_account_settings() {
		$res = $this->OAuthRequest(self::URL_HEADER.'account/settings.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET account/verify_credentials
	public function GET_account_verify_credentials($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/verify_credentials.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/settings
	public function POST_account_settings($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/settings.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_delivery_device
	public function POST_account_update_delivery_device($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/update_delivery_device.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile
	public function POST_account_update_profile($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/update_profile.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_background_image
	public function POST_account_update_profile_background_image($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage(self::URL_HEADER.'account/update_profile_background_image.json',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_colors
	public function POST_account_update_profile_colors($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/update_profile_colors.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_image
	public function POST_account_update_profile_image($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage(self::URL_HEADER.'account/update_profile_image.json',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET blocks/list
	# デフォルトではcursorに-1を設定
	public function GET_blocks_list($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'blocks/list.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET blocks/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_blocks_ids($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		if (!isset($params['stringify_ids']) && self::DEFAULT_STRINGIFY_IDS)
			$params['stringify_ids'] = '1';
		$res = $this->OAuthRequest(self::URL_HEADER.'blocks/ids.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST blocks/create
	public function POST_blocks_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'blocks/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST blocks/destroy
	public function POST_blocks_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'blocks/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/lookup
	public function GET_users_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/lookup.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/show
	public function GET_users_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/search
	public function GET_users_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/search.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/contributees
	public function GET_users_contributees($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/contributees.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/contributors
	public function GET_users_contributors($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/contributors.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/remove_profile_banner
	public function POST_account_remove_profile_banner($dummy=array(),$waitResponse=true) {
		$res = $this->OAuthRequest(self::URL_HEADER.'account/remove_profile_banner.json','POST',array(),$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST account/update_profile_banner
	public function POST_account_update_profile_banner($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'account/update_profile_banner.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/profile_banner
	public function GET_users_profile_banner($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/profile_banner.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Suggested Users *****//
	
	# GET users/suggestions/:slug
	# GET users/suggestions
	public function GET_users_suggestions($params=array()) {
		self::modParameters($params);
		if (isset($params['slug']))
			$res = $this->OAuthRequest(self::URL_HEADER.'users/suggestions/'.$params['slug'].'.json','GET',$params);
		else
			$res = $this->OAuthRequest(self::URL_HEADER.'users/suggestions.json','GET',array());
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."users/suggestions/{$slug}/members.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET users/recommendations (for Official)
	public function GET_users_recommendations($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/recommendations.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Favorites *****//
	
	# GET favorites/list
	public function GET_favorites_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'favorites/list.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST favorites/destroy
	public function POST_favorites_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'favorites/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST favorites/create
	public function POST_favorites_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'favorites/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Lists *****//
	
	# GET lists/list
	public function GET_lists_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/list.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/statuses
	public function GET_lists_statuses($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/statuses.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/destroy
	public function POST_lists_members_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/memberships
	# デフォルトではcursorに-1を設定
	public function GET_lists_memberships($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/memberships.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscribers
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscribers($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/subscribers.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/subscribers/create
	public function POST_lists_subscribers_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/subscribers/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscribers/show
	public function GET_lists_subscribers_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/subscribers/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/subscribers/destroy
	public function POST_lists_subscribers_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/subscribers/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/create_all
	public function POST_lists_members_create_all($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members/create_all.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/members/show
	public function GET_lists_members_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/members
	# デフォルトではcursorに-1を設定
	public function GET_lists_members($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/create
	public function POST_lists_members_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/destroy
	public function POST_lists_destroy($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/destroy.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/update
	public function POST_lists_update($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/update.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/create
	public function POST_lists_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/show
	public function GET_lists_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/show.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET lists/subscriptions
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscriptions($params=array()) {
		self::modParameters($params);
		if (!isset($params['cursor']))
			$params['cursor'] = '-1';
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/subscriptions.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST lists/members/destroy_all
	public function POST_lists_members_destroy_all($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'lists/members/destroy_all.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Saved Searches *****//
	
	# GET saved_searches/list
	public function GET_saved_searches_list() {
		$res = $this->OAuthRequest(self::URL_HEADER.'saved_searches/list.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."saved_searches/show/{$id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST saved_searches/create
	public function POST_saved_searches_create($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'saved_searches/create.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."saved_searches/destroy/{$id}.json",'POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
		$res = $this->OAuthRequest(self::URL_HEADER."geo/id/{$place_id}.json",'GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/id/reverse_geocode
	public function GET_geo_id_reverse_geocode($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'geo/id/reverse_geocode.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/search
	public function GET_geo_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'geo/search.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET geo/similar_places
	public function GET_geo_similar_places($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'geo/similar_places.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# POST geo/place
	public function POST_geo_place($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'geo/place.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Trends *****//
	
	# GET trends/place
	public function GET_trends_place($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'trends/place.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET trends/available
	public function GET_trends_available() {
		$res = $this->OAuthRequest(self::URL_HEADER.'trends/available.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET trends/closest
	public function GET_trends_closest($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'trends/closest.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Spam Reporting *****//
	
	# POST users/report_spam
	public function POST_users_report_spam($params=array(),$waitResponse=true) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'users/report_spam.json','POST',$params,$waitResponse);
		if (!$waitResponse) 
			return;
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** OAuth *****//
	
	# GET oauth/authenticate
	public function getAuthenticateURL() {
		if (empty($this->request_token))
			return json_decode('{"errors":[{"message":"No request_token","code":-1}]}',self::JSON_DECODE_DEFAULT_ASSOC);
		return json_decode(
			'{"url":'.
				json_encode(self::AUTHENING_URL_HEADER.'oauth/authenticate?oauth_token='.$this->request_token).
			'}',
			self::JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# GET oauth/authorize
	public function getAuthorizeURL() {
		if (empty($this->request_token))
			return json_decode('{"errors":[{"message":"No request_token","code":-1}]}',self::JSON_DECODE_DEFAULT_ASSOC);
		return json_decode(
			'{"url":'.
				json_encode(self::AUTHENING_URL_HEADER.'oauth/authorize?oauth_token='.$this->request_token).
			'}',
			self::JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# POST oauth/access_token
	public function POST_oauth_access_token($params=array()) {
		self::modParameters($params);
		if (isset($params['oauth_verifier']))
			$this->oauth_verifier = $params['oauth_verifier'];
		$this->OAuthRequest(self::OAUTH_URL_HEADER.'oauth/access_token','POST');
		return json_decode(
			(!empty($this->access_token) && !empty($this->access_token_secret)) ?
			'{"access_token":"'.$this->access_token.'","access_token_secret":"'.$this->access_token_secret.'"}':
			'{"errors":[{"message":"Couldn\'t get access_token","code":-1}]}',
			self::JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# POST oauth/request_token
	public function POST_oauth_request_token() {
		$this->OAuthRequest(self::OAUTH_URL_HEADER.'oauth/request_token','POST');
		return json_decode(
			(!empty($this->request_token) && !empty($this->request_token_secret)) ?
			'{"request_token":"'.$this->request_token.'","request_token_secret":"'.$this->request_token_secret.'"}':
			'{"errors":[{"message":"Couldn\'t get request_token","code":-1}]}',
			self::JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	# BgOAuthGetToken
	public function BgOAuthGetToken($params=array()) {
		self::modParameters($params);
		if (!isset($params['username'])) {
			$res = '{"errors":[{"message":"No username","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!isset($params['password'])) {
			$res = '{"errors":[{"message":"No password","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!self::castable($params['username'])) {
			$res = '{"errors":[{"message":"Invalid username format","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!self::castable($params['password'])) {
			$res = '{"errors":[{"message":"Invalid password format","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/request_token','POST');
		if ($res===false || empty($this->request_token) || empty($this->request_token_secret)) {
			$res = '{"errors":[{"message":"Failed to get request_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$pattern = '@<input name="authenticity_token" type="hidden" value="(.+?)" />@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Failed to get authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
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
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$pattern = '@oauth_verifier=(.+?)"|<code>(.+?)</code>@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Wrong username or password","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$this->oauth_verifier = (!empty($matches[1])) ? $matches[1] : $matches[2];
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/access_token','POST');
		if ($res===false || empty($this->access_token) || empty($this->access_token_secret)) {
			$res = '{"errors":[{"message":"Failed to get access_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		return json_decode(
			'{"access_token":"'.$this->access_token.'","access_token_secret":"'.$this->access_token_secret.'"}',
			self::JSON_DECODE_DEFAULT_ASSOC
		);
	}
	
	//***** Activity *****//
	
	# GET activity/by_friends
	public function GET_activity_by_friends($params=array()) {
		$res = $this->OAuthRequest(self::ACTIVITY_URL_HEADER.'activity/by_friends.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET activity/about_me
	public function GET_activity_about_me($params=array()) {
		$res = $this->OAuthRequest(self::ACTIVITY_URL_HEADER.'activity/about_me.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** Help *****//
	
	# GET help/configuration
	public function GET_help_configuration() {
		$res = $this->OAuthRequest(self::URL_HEADER.'help/configuration.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/languages
	public function GET_help_languages() {
		$res = $this->OAuthRequest(self::URL_HEADER.'help/languages.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/privacy
	public function GET_help_privacy() {
		$res = $this->OAuthRequest(self::URL_HEADER.'help/privacy.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET help/tos
	public function GET_help_tos() {
		$res = $this->OAuthRequest(self::URL_HEADER.'help/tos.json');
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	# GET application/rate_limit_status
	public function GET_application_rate_limit_status($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest(self::URL_HEADER.'application/rate_limit_status.json','GET',$params);
		return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
	}
	
	//***** KeitaiWeb *****//
	
	# kWeb_login
	public function kWeb_login($params=array()) {
		$this->cookie_k = array();
		$this->authenticity_token_k = '';
		self::modParameters($params);
		if (!isset($params['username'])) {
			$res = '{"errors":[{"message":"No username","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!isset($params['password'])) {
			$res = '{"errors":[{"message":"No password","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$request  = '';
		$request .= 'GET /login HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when fetching authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!preg_match('@<input.*?name="authenticity_token".*?value="(.+?)"@',$res,$matches)) {
			$res = '{"errors":[{"message":"Failed to get authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$authenticity_token = $matches[1];
		$q = http_build_query(
			array(
				'login'              => $params['username'],
				'password'           => $params['password'],
				'authenticity_token' => $authenticity_token
			),'','&'
		);
		$request  = '';
		$request .= 'POST /login HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when logining","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!preg_match('@twtr\.jp/home@',$res,$matches)) {
			$res = '{"errors":[{"message":"Wrong username or password","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$this->authenticity_token_k = $authenticity_token;
		$res = array(
			'result'               => 'Login successful',
			'cookie_k'             => $this->cookie_k,
			'authenticity_token_k' => $this->authenticity_token_k
		);
		return self::JSON_DECODE_DEFAULT_ASSOC ? $res : (object)$res ;
	}
	
	# kWeb_incoming
	public function kWeb_incoming() {
		if (empty($this->authenticity_token_k)) {
			$res = '{"errors":[{"message":"You haven\'t logined yet","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$q = http_build_query(
			array(
				'authenticity_token' => $this->authenticity_token_k
			),'','&'
		);
		$request  = '';
		$request .= 'POST /follower_request HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$dom = new DOMDocument('1.0','UTF-8');
		$dom->preserveWhiteSpace = false;
		if (!@$dom->loadHTML($res)) {
			$res = '{"errors":[{"message":"DOM parse error","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$xml = simplexml_import_dom($dom);
		$blocks = $xml->xpath("//*[@class='first' or @class='separated']");
		$users = $bases = array();
		foreach ($blocks as $i => $block) {
			$users[$i] = new stdClass;
			$id                           = substr((string)@$block->div->form->attributes()->action,33) ;
			$users[$i]->id                = (int)$id                                                    ;
			$users[$i]->id_str            = $id                                                         ;
			$users[$i]->screen_name       = (string)@$block->img->attributes()->alt                     ;
			$users[$i]->name              = mb_substr((string)@$block->span,1,-2,'UTF-8')               ;
			$users[$i]->profile_image_url = (string)@$block->img->attributes()->src                     ;
			$bases[$i]                    = strtolower((string)@$users[$i]->screen_name)                ;
		}
		array_multisort($bases,$users);
		if (self::JSON_DECODE_DEFAULT_ASSOC)
		foreach ($users as &$user)
			$user = (array)$user;
		unset($user);
		$res = array(
			'penders'              => $users,
			'cookie_k'             => $this->cookie_k,
			'authenticity_token_k' => $this->authenticity_token_k
		);
		return self::JSON_DECODE_DEFAULT_ASSOC ? $res : (object)$res ;
	}
	
	# kWeb_accept
	public function kWeb_accept($params=array(),$waitResponse=true) {
		if (empty($this->authenticity_token_k)) {
			$res = '{"errors":[{"message":"You haven\'t logined into KeitaiWeb yet","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		self::modParameters($params);
		if (!isset($params['user_id'])) {
			$res = '{"errors":[{"message":"No user_id","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!ctype_digit($params['user_id'])) {
			$res = '{"errors":[{"message":"Invalid user_id","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$q = http_build_query(
			array(
				'authenticity_token' => $this->authenticity_token_k,
				'cursor'             => '-1',
				'commit'             => "\xe8\xa8\xb1\xe5\x8f\xaf"
			),'','&'
		);
		$request  = '';
		$request .= 'POST /follower_request/'.$params['user_id'].' HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if (!$waitResponse)
			return;
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!preg_match('@redirected\.?</a>@i',$res)) {
			$res = '{"errors":[{"message":"Invalid authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$request  = '';
		$request .= 'GET /follower_request?processing_id='.$params['user_id'].' HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (strpos($res,"\xe8\xa8\xb1\xe5\x8f\xaf\xe3\x81\x97\xe3\x81\xbe\xe3\x81\x97\xe3\x81\x9f</div>")===false) {
			$res = '{"errors":[{"message":"Failed to accept '.$params['user_id'].'","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$res = array(
			'result'               => 'Acception of '.$params['user_id'].' successful',
			'cookie_k'             => $this->cookie_k,
			'authenticity_token_k' => $this->authenticity_token_k
		);
		return self::JSON_DECODE_DEFAULT_ASSOC ? $res : (object)$res ;
	}
	
	# kWeb_deny
	public function kWeb_deny($params=array(),$waitResponse=true) {
		if (empty($this->authenticity_token_k)) {
			$res = '{"errors":[{"message":"You haven\'t logined into KeitaiWeb yet","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		self::modParameters($params);
		if (!isset($params['user_id'])) {
			$res = '{"errors":[{"message":"No user_id","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!ctype_digit($params['user_id'])) {
			$res = '{"errors":[{"message":"Invalid user_id","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$params['user_id'] = (int)$params['user_id'];
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$q = http_build_query(
			array(
				'authenticity_token' => $this->authenticity_token_k,
				'cursor'             => '-1',
				'commit'             => "\xe6\x8b\x92\xe5\x90\xa6"
			),'','&'
		);
		$request  = '';
		$request .= 'POST /follower_request/'.$params['user_id'].' HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if (!$waitResponse)
			return;
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!preg_match('@redirected\.?</a>@i',$res)) {
			$res = '{"errors":[{"message":"Invalid authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$request  = '';
		$request .= 'GET /follower_request?processing_id='.$params['user_id'].' HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (strpos($res,"\xe6\x8b\x92\xe5\x90\xa6\xe3\x81\x97\xe3\x81\xbe\xe3\x81\x97\xe3\x81\x9f</div>")===false) {
			$res = '{"errors":[{"message":"Failed to deny '.$params['user_id'].'","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$res = array(
			'result'               => 'Denial of '.$params['user_id'].' successful',
			'cookie_k'             => $this->cookie_k,
			'authenticity_token_k' => $this->authenticity_token_k
		);
		return self::JSON_DECODE_DEFAULT_ASSOC ? $res : (object)$res ;
	}
	
	# kWeb_accept_all
	public function kWeb_accept_all($dummy=array(),$waitResponse=true) {
		if (empty($this->authenticity_token_k)) {
			$res = '{"errors":[{"message":"You haven\'t logined into KeitaiWeb yet","code":-1}]}';
			return (!$waitResponse) ? null : json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$q = http_build_query(
			array(
				'authenticity_token' => $this->authenticity_token_k,
				'cursor'             => '-1',
				'commit'             => "\xe5\x85\xa8\xe3\x81\xa6\xe8\xa8\xb1\xe5\x8f\xaf"
			),'','&'
		);
		$request  = '';
		$request .= 'POST /follower_request/accept_all HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('twtr.jp','https',$request,true,true);
		if (!$waitResponse)
			return;
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (!preg_match('@redirected\.?</a>@i',$res)) {
			$res = '{"errors":[{"message":"Invalid authenticity_token","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$request  = '';
		$request .= 'GET /home HTTP/1.1'."\r\n";
		$request .= 'Host: twtr.jp'."\r\n";
		$request .= 'User-Agent: Mozilla/0 (iPhone;)'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie_k))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('twtr.jp','https',$request,true,true);
		$pattern  = '@';
		$pattern .= '<form action="https://twtr\.jp/follower_request/(\d+).*?';
		$pattern .= '<img alt="(.+?)".*?src="(.+?)".*?';
		$pattern .= '<span class="small">(.*?)</span>';
		$pattern .= '@s';
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		if (strpos($res,"\xe8\xa8\xb1\xe5\x8f\xaf\xe3\x81\x97\xe3\x81\xbe\xe3\x81\x97\xe3\x81\x9f</div>")===false) {
			$res = '{"errors":[{"message":"Failed to accept all","code":-1}]}';
			return json_decode($res,self::JSON_DECODE_DEFAULT_ASSOC);
		}
		$res = array(
			'result'               => 'Acception of all successful',
			'cookie_k'             => $this->cookie_k,
			'authenticity_token_k' => $this->authenticity_token_k
		);
		return self::JSON_DECODE_DEFAULT_ASSOC ? $res : (object)$res ;
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
	private $cookie_k;
	private $authenticity_token_k;
	
	// ソケット接続
	private function connect($host,$scheme,$request,$waitResponse=true,$keitaiWeb=false) {
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
		if (isset($res[1]) && preg_match_all('/^Set-Cookie:(.+?)$/mi',$res[0],$matches,PREG_SET_ORDER)>0) {
			foreach ($matches as $match) {
				$parts = explode(';',$match[1]);
				foreach ($parts as $part) {
					$part = trim($part);
					if (strpos($part,'=')<1 || substr_count($part,'=')!==1)
						continue;
					list($key,$value) = explode('=',$part,2);
					if (in_array($key,array('expires','path','domain','secure')))
						continue;
					if ($keitaiWeb)
						$this->cookie_k[$key] = $value;
					else
						$this->cookie[$key]   = $value;
				}
			}
		}
		return $ret;
	}
	
	// OAuthリクエスト
	private function OAuthRequest($url,$method='GET',$params=array(),$waitResponse=true) {
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
		if (!is_array($array))
			return false;
		foreach ($array as $var)
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	
}

/**** UltimateOAuthマルチリクエスト用クラス *****/

class UltimateOAuthMulti {
	
	/*************************************/
	/*********** OutSider Area ***********/
	/*************************************/
	
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
	public function addjob(&$to,$method,$params=array()) {
		$className = self::baseClassName;
		$i = count($this->chs);
		$this->obj[$i] =& $to;
		if (!is_callable('curl_init') || !is_object($to) || !($to instanceof $className)) {
			$this->chs[$i] = null;
			$this->vars[$i] = null;
			return;
		}
		$this->vars[$i] = (object)array(
			'consumer_key'         => $to->consumer_key()        ,
			'consumer_secret'      => $to->consumer_secret()     ,
			'access_token'         => $to->access_token()        ,
			'access_token_secret'  => $to->access_token_secret() ,
			'request_token'        => $to->request_token()       ,
			'request_token_secret' => $to->request_token_secret(),
			'oauth_verifier'       => $to->oauth_verifier()      ,
			'cookie'               => $to->cookie()              ,
			'cookie_k'             => $to->cookie_k()            ,
			'authenticity_token_k' => $to->authenticity_token_k()
		);
		$obj = new stdClass;
		$obj->vars   = $this->vars[$i];
		$obj->method = $method;
		$obj->params = (is_array($params)||is_object($params)) ? (object)$params : (object)array() ;
		$query = http_build_query(array(self::specialKeyName=>rawurlencode(base64_encode(json_encode($obj)))),'','&');
		$this->chs[$i] = curl_init();
		curl_setopt($this->chs[$i],CURLOPT_URL,$this->url);
		curl_setopt($this->chs[$i],CURLOPT_POST,true);
		curl_setopt($this->chs[$i],CURLOPT_POSTFIELDS,$query);
		curl_setopt($this->chs[$i],CURLOPT_RETURNTRANSFER,true);
	}
	
	# マルチリクエスト実行（結果は追加した順番に配列で返ります）
	public function exec() {
		$className = self::baseClassName;
		$assoc = constant($className.'::JSON_DECODE_DEFAULT_ASSOC');
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
				$res[$i] = json_decode('{"errors":[{"message":"Failed to get URL to this file itself","code":-1}]}',$assoc);
			} elseif (!is_callable('curl_multi_init')) {
				$res[$i] = json_decode('{"errors":[{"message":"cURL functions are not installed on this server","code":-1}]}',$assoc);
			} elseif (is_null($ch)) {
				$res[$i] = json_decode('{"errors":[{"message":"No '.$className.' Object","code":-1}]}',$assoc);
			} elseif ($error=curl_error($ch)) {
				$res[$i] = json_decode('{"errors":[{"message":"cURL error occurred (message: '.$error.')","code":-1}]}',$assoc);
			} elseif (($r=json_decode(curl_multi_getcontent($ch)))===null) {
				$res[$i] = json_decode('{"errors":[{"message":"Failed to get valid cURL content (Requests from this server to itself may be blocked)","code":-1}]}',$assoc);
			} else {
				if (!$assoc && !empty($r->cookie))
					$r->cookie   = (array)$r->cookie;
				if (!$assoc && !empty($r->cookie_k))
					$r->cookie_k = (array)$r->cookie_k;
				$res[$i] = $r;
				$r = (object)$r;
				$this->obj[$i] = new $className(
					$this->vars[$i]->consumer_key                                                                     ,
					$this->vars[$i]->consumer_secret                                                                  ,
					empty($r->access_token)         ? $this->vars[$i]->access_token         : $r->access_token        ,
					empty($r->access_token_secret)  ? $this->vars[$i]->access_token_secret  : $r->access_token_secret ,
					empty($r->request_token)        ? $this->vars[$i]->request_token        : $r->request_token       ,
					empty($r->request_token_secret) ? $this->vars[$i]->request_token_secret : $r->request_token_secret,
					$this->vars[$i]->oauth_verifier                                                                   ,
					empty($r->authenticity_token_k) ? $this->vars[$i]->authenticity_token_k : $r->authenticity_token_k,
					empty($r->cookie)               ? $this->vars[$i]->cookie               : $r->cookie              ,
					empty($r->cookie_k)             ? $this->vars[$i]->cookie_k             : $r->cookie_k
				);
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
	
	/************************************/
	/*********** Insider Area ***********/
	/************************************/
	
	private $obj;
	private $chs;
	private $vars;
	private $url;
	
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
		$document_root_url = $protocol.'://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$document_root_url;
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
			if (self::castable($_POST[self::specialKeyName]))
				$obj = json_decode(base64_decode(rawurldecode((string)$_POST[self::specialKeyName])));
			if (!is_object($obj))
				$obj = new stdClass;
			if (!isset($obj->vars) || !is_object($obj->vars))
				$obj->vars = new stdClass;
			$consumer_key =
				(!empty($obj->vars->consumer_key)         && self::castable($obj->vars->consumer_key)        )
				? $obj->vars->consumer_key                : '' ;
			$consumer_secret =
				(!empty($obj->vars->consumer_secret)      && self::castable($obj->vars->consumer_secret)     )
				? $obj->vars->consumer_secret             : '' ;
			$access_token =
				(!empty($obj->vars->access_token)         && self::castable($obj->vars->access_token)        )
				? $obj->vars->access_token                : '' ;
			$access_token_secret =
				(!empty($obj->vars->access_token_secret)  && self::castable($obj->vars->access_token_secret) )
				? $obj->vars->access_token_secret         : '' ;
			$request_token =
				(!empty($obj->vars->request_token)        && self::castable($obj->vars->request_token)       )
				? $obj->vars->request_token               : '' ;
			$request_token_secret =
				(!empty($obj->vars->request_token_secret) && self::castable($obj->vars->request_token_secret))
				? $obj->vars->request_token_secret        : '' ;
			$oauth_verifier =
				(!empty($obj->vars->oauth_verifier)       && self::castable($obj->vars->oauth_verifier)      )
				? $obj->vars->oauth_verifier              : '' ;
			$authenticity_token_k =
				(!empty($obj->vars->authenticity_token_k) && self::castable($obj->vars->authenticity_token_k))
				? $obj->vars->authenticity_token_k        : '' ;
			$cookie = array();
			if (!empty($obj->vars->cookie)   && (is_array($obj->vars->cookie)   || is_object($obj->vars->cookie))  )
			foreach ($obj->vars->cookie as $k => $v) {
				if (self::castable($v) && strlen($k)>0)
					$cookie[$k] = $v;
			}
			$cookie_k = array();
			if (!empty($obj->vars->cookie_k) && (is_array($obj->vars->cookie_k) || is_object($obj->vars->cookie_k)))
			foreach ($obj->vars->cookie_k as $k => $v) {
				if (self::castable($v) && strlen($k)>0)
					$cookie_k[$k] = $v;
			}
			$params = array();
			if (!empty($obj->params)         && (is_array($obj->params)         || is_object($obj->params))        )
			foreach ($obj->params as $k => $v) {
				if (self::castable($v) && strlen($k)>0)
					$params[$k] = $v;
			}	
			$method = (isset($obj->method) && self::castable($obj->method)) ? $obj->method : '';
			$to = new $className(
				$consumer_key,$consumer_secret,$access_token,$access_token_secret,
				$request_token,$request_token_secret,$oauth_verifier,
				$authenticity_token_k,$cookie,$cookie_k
			);
			if (!preg_match('/^(?:GET|POST|kWeb|BgOAuth)/i',$method) || !is_callable(array($to,$method)))
				echo '{"errors":[{"message":"Can\'t call \"'.$method.'\"","code":-1}]}';
			else
				echo json_encode(call_user_func(array($to,$method),$params));
			exit();
		}
	}
	
	// スカラー型か判断
	private static function castable($var) {
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	private static function castable_array($array) {
		if (!is_array($array))
			return false;
		foreach ($array as $var)
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	
	// serialize防止
	public function __sleep() {
		throw new BadMethodCallException('You cannot serialize this object.');
	}
	
}

/**** UltimateOAuthローテーション用クラス *****/

class UltimateOAuthRotate {
	
	const baseClassName  = 'UltimateOAuth';
	const multiClassName = 'UltimateOAuthMulti';
	
	/*************************************/
	/*********** OutSider Area ***********/
	/*************************************/
	
	# クラス変数読み取り用
	public function user()       { return clone $this->user; }
	public function registered() { return $this->registered; }
	
	# コンストラクタ
	public function __construct() {
		$this->registered = false;
		$this->user = new stdClass;
		$this->user->main = new stdClass;
		$this->user->sub  = new stdClass;
		$this->user->method_count = new stdClass;
	}
	
	# UltimateOAuthRotateオブジェクトを実際に生成
	public function register($base_consumer_key,$base_consumer_secret,$username,$password,$multiRequest=true) {
		$this->error = false;
		$this->user = new stdClass;
		$this->user->main = new stdClass;
		$this->user->sub  = new stdClass;
		$this->user->method_count = new stdClass;
		if (!$multiRequest || !is_callable('curl_multi_init'))
			$this->registerSingle($base_consumer_key,$base_consumer_secret,$username,$password);
		else
			$this->registerMulti($base_consumer_key,$base_consumer_secret,$username,$password);
		$this->registered = true;
		return !$this->error;
	}
	
	# データから復元
	public static function load($data) {
		$className = __CLASS__;
		$obj = new $className();
		$obj->__load($data);
		return $obj;
	}
	
	# データに出力
	public function save() {
		if (!$this->registered)
			return;
		$copy = new stdClass;
		$copy->user = new stdClass;
		$copy->user->main = $this->user()->main->save();
		$copy->user->sub  = new stdClass;
		$copy->user->method_count = $this->user()->method_count;
		$keys = array_keys((array)$this->user()->sub);
		$count = count($keys);
		for ($i=0;$i<$count;$i++)
			$copy->user->sub->{$keys[$i]} = $this->user()->sub->{$keys[$i]}->save();
		return strrev(str_rot13(urlencode(base64_encode(strrev(str_rot13(json_encode(get_object_vars($copy))))))));
	}
	
	/************************************/
	/*********** Insider Area ***********/
	/************************************/
	
	private $user;
	private $registered;
	private $error;
	
	// マジックメソッドを用いて間接的にUltimateOAuthクラスのメソッドをコール
	public function __call($name,$args) {
		$assoc = constant(self::baseClassName.'::JSON_DECODE_DEFAULT_ASSOC');
		if (!$this->registered)
			return json_decode('{"errors":[{"message":"You haven\'t registered yet","code":-1}]}',$assoc);
		$keys = array_keys((array)$this->user->sub);
		if (!is_callable(array($this->user->main,$name)) || !preg_match('/^(?:POST|GET|kWeb|BgOAuth)/i',$name))
			return json_decode('{"errors":[{"message":"Can\'t call \"'.$name.'\"","code":-1}]}',$assoc);
		if (!empty($this->user->sub) && preg_match('/^POST_friendships_accept$|^POST_friendships_deny$|^POST_friendships_accept_all$|^GET_/i',$name)===1) {
			$lower = strtolower($name);
			if (!isset($this->user->method_count->$lower))
				$this->user->method_count->$lower = 0;
			$res = call_user_func_array(array($this->user->sub->{$keys[$this->user->method_count->$lower]},$name),$args);
			$this->user->method_count->$lower = ($this->user->method_count->$lower<count($keys)-1) ? $this->user->method_count->$lower+1 : 0 ;
			return $res;
		} else {
			return call_user_func_array(array($this->user->main,$name),$args);
		}
	}
	
	// 静的コールからのロードリクエストを受けて実際に動的ロード
	public function __load($data) {
		$baseClassName = self::baseClassName;
		if (self::castable($data))
			$obj = json_decode(str_rot13(strrev(base64_decode(urldecode(str_rot13(strrev((string)$data)))))));
		if (!isset($obj))
			$obj = new stdClass;
		if (!isset($obj->user) || !is_object($obj->user))
			$obj->user = new stdClass;
		$json = isset($obj->user->main) ? $obj->user->main : '';
		$this->user->method_count = (isset($obj->user->method_count) && is_object($obj->user->method_count)) ? $obj->user->method_count : new stdClass;
		$this->user->main = $baseClassName::load($json);
		if (!isset($obj->user->sub) || !is_object($obj->user->sub))
			$obj->user->sub = new stdClass;
		foreach ($obj->user->sub as $key => $json)
			$this->user->sub->$key = $baseClassName::load($json);
		$this->registered = true;
	}
	
	// cURLが使えないとき用
	private function registerSingle($base_consumer_key,$base_consumer_secret,$username,$password) {
		$baseClassName = self::baseClassName ;
		$assoc         = constant($baseClassName.'::JSON_DECODE_DEFAULT_ASSOC');
		$method        = 'BgOAuthGetToken';
		$params        = array('username'=>$username,'password'=>$password);
		$res = array();
		$this->user->main = new $baseClassName($base_consumer_key,$base_consumer_secret);
		$func_res = call_user_func(array($this->user->main,$method),$params);
		if (!empty($func_res->errors))
			$this->error = true;
		foreach (self::getOfficialKeys() as $key => $app) {
			$this->user->sub->$key = new $baseClassName($app->consumer_key,$app->consumer_secret);
			$func_res = call_user_func(array($this->user->sub->$key,$method),$params);
			if (!empty($func_res->errors))
				$this->error = true;
		}
	}
	
	// cURLが使えるとき用
	private function registerMulti($base_consumer_key,$base_consumer_secret,$username,$password) {
		$baseClassName  = self::baseClassName ;
		$multiClassName = self::multiClassName;
		$assoc          = constant($baseClassName.'::JSON_DECODE_DEFAULT_ASSOC');
		$method         = 'BgOAuthGetToken';
		$params         = array('username'=>$username,'password'=>$password);
		$multi = new $multiClassName();
		$this->user->main = new $baseClassName($base_consumer_key,$base_consumer_secret);
		$multi->addjob($this->user->main,$method,$params);
		$keys = array();
		foreach (self::getOfficialKeys() as $key => $app) {
			$this->user->sub->$key = new $baseClassName($app->consumer_key,$app->consumer_secret);
			$multi->addjob($this->user->sub->$key,$method,$params);
			$keys[] = $key;
		}
		$res = $multi->exec();
		foreach ($res as $i => &$r) {
			if (!empty($r->errors))
				$this->error = true;
			if (!$assoc && !empty($r->cookie))
				$r->cookie   = (array)$r->cookie;
			if (!$assoc && !empty($r->cookie_k))
				$r->cookie_k = (array)$r->cookie_k;
			$r = (object)$r;
			if ($i===0) {
				$this->user->main = new $baseClassName(
					$this->user->main->consumer_key()                                                                     ,
					$this->user->main->consumer_secret()                                                                  ,
					empty($r->access_token)         ? $this->user->main->access_token()         : $r->access_token        ,
					empty($r->access_token_secret)  ? $this->user->main->access_token_secret()  : $r->access_token_secret ,
					empty($r->request_token)        ? $this->user->main->request_token()        : $r->request_token       ,
					empty($r->request_token_secret) ? $this->user->main->request_token_secret() : $r->request_token_secret,
					$this->user->main->oauth_verifier()                                                                   ,
					empty($r->authenticity_token_k) ? $this->user->main->authenticity_token_k() : $r->authenticity_token_k,
					empty($r->cookie)               ? $this->user->main->cookie()               : $r->cookie              ,
					empty($r->cookie_k)             ? $this->user->main->cookie_k()             : $r->cookie_k
					);
			} else {
				$key = $keys[$i-1];
				$this->user->sub->$key = new $baseClassName(
					$this->user->sub->$key->consumer_key()                                                                     ,
					$this->user->sub->$key->consumer_secret()                                                                  ,
					empty($r->access_token)         ? $this->user->sub->$key->access_token()         : $r->access_token        ,
					empty($r->access_token_secret)  ? $this->user->sub->$key->access_token_secret()  : $r->access_token_secret ,
					empty($r->request_token)        ? $this->user->sub->$key->request_token()        : $r->request_token       ,
					empty($r->request_token_secret) ? $this->user->sub->$key->request_token_secret() : $r->request_token_secret,
					$this->user->sub->$key->oauth_verifier()                                                                   ,
					empty($r->authenticity_token_k) ? $this->user->sub->$key->authenticity_token_k() : $r->authenticity_token_k,
					empty($r->cookie)               ? $this->user->sub->$key->cookie()               : $r->cookie              ,
					empty($r->cookie_k)             ? $this->user->sub->$key->cookie_k()             : $r->cookie_k
					);
			}
		}
	}
	
	// 公式キー取得
	private static function getOfficialKeys() {
		$res = new stdClass;
		$res->{'Twitter for iPhone'         } = 
			(object)array('consumer_key'=>'IQKbtAYlXLripLGPWd0HUA','consumer_secret'=>'GgDYlkSvaPxGxC4X8liwpUoqKwwr3lCADbz8A7ADU'  );
		$res->{'Twitter for Android'        } = 
			(object)array('consumer_key'=>'3nVuSoBZnx6U4vzUxf5w'  ,'consumer_secret'=>'Bcs59EFbbsdF6Sl9Ng71smgStWEGwXXKSjYvPVt7qys');
		$res->{'Twitter for Android Sign-Up'} = 
			(object)array('consumer_key'=>'RwYLhxGZpMqsWZENFVw'   ,'consumer_secret'=>'Jk80YVGqc7Iz1IDEjCI6x3ExMSBnGjzBAH6qHcWJlo' );
		$res->{'Twitter for iPad'           } = 
			(object)array('consumer_key'=>'CjulERsDeqhhjSme66ECg' ,'consumer_secret'=>'IQWdVyqFxghAtURHGeGiWAsmCAGmdW3WmbEx6Hck'   );
		$res->{'Twitter for Mac'            } = 
			(object)array('consumer_key'=>'3rJOl1ODzm9yZy63FACdg' ,'consumer_secret'=>'5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8' );
		$res->{'Twitter for Windows Phone'  } = 
			(object)array('consumer_key'=>'yN3DUNVO0Me63IAQdhTfCA','consumer_secret'=>'c768oTKdzAjIYCmpSNIdZbGaG0t6rOhSFQP0S5uC79g');
		$res->{'TweetDeck'                  } = 
			(object)array('consumer_key'=>'yT577ApRtZw51q4NPMPPOQ','consumer_secret'=>'3neq3XqN5fO3obqwZoajavGFCUrC42ZfbrLXy5sCv8' );
		return $res;
	}
	
	// スカラー型か判断
	private static function castable($var) {
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	private static function castable_array($array) {
		if (!is_array($array))
			return false;
		foreach ($array as $var)
		if (is_object($var) || is_array($var) || is_resource($var))
			return false;
		return true;
	}
	
}

// UltimateOAuthMultiのジョブをチェック
UltimateOAuthMulti::call();