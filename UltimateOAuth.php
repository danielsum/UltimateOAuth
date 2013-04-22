<?php

// **************************************************************
// **************** UltimateOAuth Version 4.2 *******************
// **************************************************************
//
//   Author : CertaiN
//   License: Creative Commons CC0
//   GitHub : http://github.com/certainist
//   Twitter: http://twitter.com/mpyw
// 
// 【特長】
//  
//  ★スクレイピング利用で疑似的にxAuth認証のようなことが出来る。
//    (UltimateOAuth::BgOAuthGetToken)
//  
//  ・PHP5.2以上で動作。
//    「PEAR」「cURL」「OAuth.php」などに依存しない。(依存ファイル無し)
//  
//  ・上記の理由から、POSTリクエスト時にオプションで指定して、レスポンスを待機させない非同期リクエストも出来る。
//  
//  ★奇形なエラーレスポンスは標準形に修正される。
//    エラーメッセージを取得する方法に関して、TwitterAPIでは
//      $response->errors[0]->message
//      $respnose->errros
//      $response->error
//    などものによってバラツキがあるが、全てこのライブラリでは
//      $response->errors[0]->message
//    で扱うことができる。
//    またエラーコード
//      $response->errors[0]->code
//    は全て意味の分かりやすい「HTTPステータスコード」に上書きされて返される。
//  
//  ・複数のUltimateOAuthのメソッドを並列で実行させることが出来る。
//    (UltimateOAuthMulti)
//    Version4.1以降、この並列リクエストもcURLに依存しなくなった。
//    このファイル自身にリクエストを送信するため、このファイルがPublicディレクトリに置かれていなければならない。
//  
//  ・UltiamteOAuthオブジェクトを、規制緩和されている公式キーも併せて包括的に管理し、
//    更に自動でローテーションさせてAPIのGETリクエストにおける規制が回避できるクラスを実装。
//    (UltimateOAuthRotate)
//    loginメソッドでUltimateOAuthMultiからUltimateOAuthのBgOAuthGetTokenメソッドをコールし、
//    上記の複数のコンシューマーキーで高速に同時認証可能。
//  
// 【データの保存/復元について】
//  
//  ・UltimateOAuthクラス
//      UltimateOAuth::load (static) (安全)
//      UltimateOAuth::save          (安全)
//      serialize
//      unserialize
//     で可能。保存方法と復元方法を対応させること。
//  
//  ・UltimateOAuthMultiクラス
//     ソケットリソースを用いているため不可。
//  
//  ・UltimateOAuthRotateクラス
//      serialize
//      unserialize
//     で可能。
//  
//  ※サンプルコードはGitHubに置いています。
//  

/**** 設定 ****/
interface UltimateOAuthConfig {

	// 必要に応じて設定
	const USER_AGENT                   = 'UltimateOAuth'            ;
	const MULTI_KEYNAME                = '__UltimateOAuthMulti_key' ;
	const DEFAULT_SCHEME               = 'https'                    ;
	const DEFAULT_HOST                 = 'api.twitter.com'          ;
	const DEFAULT_API_VERSION          = '1.1'                      ;
	const DEFAULT_ACTIVITY_API_VERSION = '1.1'                      ;
	
	// 設定しておくと、UltimateOAuthMultiクラスがgetURLメソッドに依存しなくなる。
	// (UNIX環境でなく、うまくいかない場合は設定推奨)
	const URL_TO_THIS_FILE = '';

}

/**** メインクラス ****/
class UltimateOAuth {
	
	/****************/
	/**** Public ****/
	/****************/
	
	# プロパティ読み取り
	public function consumer_key()         { return $this->consumer_key            ; }
	public function consumer_secret()      { return $this->consumer_secret         ; }
	public function access_token()         { return $this->access_token            ; }
	public function access_token_secret()  { return $this->access_token_secret     ; }
	public function request_token()        { return $this->request_token           ; }
	public function request_token_secret() { return $this->request_token_secret    ; }
	public function oauth_verifier()       { return $this->oauth_verifier          ; }
	public function authenticity_token()   { return $this->authenticity_token      ; }
	public function cookie()               { return $this->cookie                  ; }
	public function lastHTTPStatusCode()   { return $this->last_http_status_code   ; }
	public function lastCalledEndpoint()   { return $this->last_called_endpoint    ; }
	public function getProperties()        { return (object)get_object_vars($this) ; }
	
	# ユーザーにOAuth認証させるためのURLを取得
	public function getAuthorizeURL($force_login=false) {
		return sprintf('%s://%s/oauth/authorize?oauth_token=%s%s',
			UltimateOAuthConfig::DEFAULT_SCHEME  ,
			UltimateOAuthConfig::DEFAULT_HOST    ,
			$this->request_token                 ,
			$force_login ? '&force_login=1' : ''  
		);
	}
	public function getAuthenticateURL($force_login=false) {
		return sprintf('%s://%s/oauth/authenticate?oauth_token=%s%s',
			UltimateOAuthConfig::DEFAULT_SCHEME  ,
			UltimateOAuthConfig::DEFAULT_HOST    ,
			$this->request_token                 ,
			$force_login ? '&force_login=1' : ''  
		);
	}
	
	# コンストラクタ
	public function __construct(
		$consumer_key          = '', // コンシューマーキー(必須)
		$consumer_secret       = '', // コンシューマーシークレット(必須)
		$access_token          = '', // アクセストークン(ユーザーに認証させる場合は不要)
		$access_token_secret   = '', // アクセストークンシークレット(ユーザーに認証させる場合は不要)
		// -- 内部利用引数ここから -- //
		$request_token         = '',
		$request_token_secret  = '',
		$oauth_verifier        = '',
		$authenticity_token    = '',
		$cookie                = array(),
		$last_http_status_code = -1,
		$last_called_endpoint  = ''
		// -- 内部利用引数ここまで -- //
	) {
		// 引数のデータ型チェック・プロパティ初期化
		$this->consumer_key          = UltimateOAuthModule::convertible($consumer_key)          ? (string)$consumer_key         : '' ;
		$this->consumer_secret       = UltimateOAuthModule::convertible($consumer_secret)       ? (string)$consumer_secret      : '' ;
		$this->access_token          = UltimateOAuthModule::convertible($access_token)          ? (string)$access_token         : '' ;
		$this->access_token_secret   = UltimateOAuthModule::convertible($access_token_secret)   ? (string)$access_token_secret  : '' ;
		$this->request_token         = UltimateOAuthModule::convertible($request_token)         ? (string)$request_token        : '' ;
		$this->request_token_secret  = UltimateOAuthModule::convertible($request_token_secret)  ? (string)$request_token_secret : '' ;
		$this->oauth_verifier        = UltimateOAuthModule::convertible($oauth_verifier)        ? (string)$oauth_verifier       : '' ;
		$this->authenticity_token    = UltimateOAuthModule::convertible($authenticity_token)    ? (string)$authenticity_token   : '' ;
		$this->cookie = array();
		if (is_array($cookie) && $cookie!==array())
			foreach ($cookie as $key => $value)
				if (UltimateOAuthModule::convertible($value))
					$this->cookie[$key] = (string)$value;
		$this->last_http_status_code = UltimateOAuthModule::convertible($last_http_status_code) ? (int)$last_http_status_code   : -1 ;
		$this->last_called_endpoint  = UltimateOAuthModule::convertible($last_called_endpoint)  ? (string)$last_called_endpoint : '' ;
		
	}
	
	// 外部からのデータの受け渡しを考慮して、
	// serialize・unserializeより安全に行いたい場合に有用。
	# シリアル復元/シリアル化
	public static function load($data) {
		if (UltimateOAuthModule::convertible($data))
			// stdClassに復元
			$obj = json_decode(base64_decode($data));
		if (!isset($obj))
			// 復元に失敗したら初期化
			$obj = new stdClass;
		// インスタンスを返す
		return new UltimateOAuth(
			isset($obj->consumer_key)          ? $obj->consumer_key          : ''      ,
			isset($obj->consumer_secret)       ? $obj->consumer_secret       : ''      ,
			isset($obj->access_token)          ? $obj->access_token          : ''      ,
			isset($obj->access_token_secret)   ? $obj->access_token_secret   : ''      ,
			isset($obj->request_token)         ? $obj->request_token         : ''      ,
			isset($obj->request_token_secret)  ? $obj->request_token_secret  : ''      ,
			isset($obj->oauth_verifier)        ? $obj->oauth_verifier        : ''      ,
			isset($obj->authenticity_token)    ? $obj->authenticity_token    : ''      ,
			isset($obj->cookie)                ? $obj->cookie                : array() ,
			isset($obj->last_http_status_code) ? $obj->last_http_status_code : -1      ,
			isset($obj->last_called_endpoint)  ? $obj->last_called_endpoint  : ''
		);
	}
	public function save() {
		return base64_encode(json_encode($this));
	}
	
	// 以下のPublicメソッドはレスポンスが全て、
	// 「$wait_response」にFALSEを指定してNULLになる場合や、
	// タイムラインなどの配列になるケースを除き、
	// レスポンス正常取得時は全てオブジェクトで返される。
	// エラーオブジェクトは
	//   HTTPステータスコード: $obj->errors[0]->code;
	//   エラーメッセージ    : $obj->errors[0]->message;
	// の形に全て統一され、エラー処理を容易にする。
	// エラーオブジェクトに含まれるエラーコードは意味が分かりにくいため、
	// 分かりやすい「HTTPステータスコード」で上書きする。
	
	# GETリクエスト用ラッパー
	public function get(
		$endpoint                , // エンドポイント。完全URL、部分URL(「statuses/home_timeline.json」などの表記)に対応。
		$params        = array()   // パラメータ。連想配列で渡す。
	) {
		return $this->OAuthRequest($endpoint,'GET',$params,true);
	}
	
	# POSTリクエスト用ラッパー
	public function post(
		$endpoint                , // エンドポイント。完全URL、部分URL(「statuses/update.json」などの表記)に対応。
		$params        = array() , // パラメータ。連想配列で渡す。
		$wait_response = true      // FALSEにした場合レスポンスを待たずにNULLで返す。
	) {
		return $this->OAuthRequest($endpoint,'POST',$params,$wait_response);
	}
	
	# 通常のOAuthRequest
	public function OAuthRequest(
		$endpoint                , // エンドポイント。完全URL、部分URLに対応。
		$method        = 'GET'   , // メソッド。「GET」「POST」のいずれか。
		$params        = array() , // パラメータ。連想配列で渡す。
		$wait_response = true      // FALSEにした場合レスポンスを待たずにNULLで返す。
	) {
		// パラメータ最適化
		self::modParameters($params);
		return $this->request($endpoint,$method,$params,false,$wait_response,false);
	
	}
	
	# 画像アップロード用のOAuthRequest(メソッドはPOST固定)
	public function OAuthRequestMultipart(
		$endpoint                , // エンドポイント。完全URL、部分URLに対応。
		$params        = array() , // パラメータ。連想配列で渡す。ファイル名を指す場合はキーの頭に「@」を付加する。
		$wait_response = true      // FALSEにした場合レスポンスを待たずにNULLで返す。
	) {
		// パラメータ最適化
		self::modParameters($params);
		return $this->request($endpoint,'POST',$params,true,$wait_response,false);
		
	}
	
	# バックグラウンドOAuth認証(疑似xAuth認証)
	public function BgOAuthGetToken(
		$username, // スクリーンネームまたはEメールアドレス。
		$password  // パスワード。
	) {
	
		try {
			
			// 引数のデータ型チェック
			if (!UltimateOAuthModule::convertible($username))
				throw new Exception('$username must be string.');
			if (!UltimateOAuthModule::convertible($password))
				throw new Exception('$password must be string.');
			
			// リクエストトークン取得
			$res = $this->post('oauth/request_token');
			if (isset($res->errors))
				return UltimateOAuthModule::createErrorObject($res->errors[0]->message,$this->lastHTTPStatusCode());
				
			// 認証ページURL取得
			$url = $this->getAuthorizeURL(true);
			
			// 認証ページからauthenticity_tokenを取得
			$res = $this->request($url,'GET',array(),false,true,true);
			$pattern = '@<input name="authenticity_token" type="hidden" value="(.+?)" />@';
			if ($res===false)
				return UltimateOAuthModule::createErrorObject(
					'Connection failed when fetching authenticity_token.',
					$this->lastHTTPStatusCode()
				);
			if (!preg_match($pattern,$res,$matches))
				return UltimateOAuthModule::createErrorObject(
					'Failed to fetch authenticity_token.',
					$this->lastHTTPStatusCode()
				);
			
			// 認証ページにもう一度飛んでログイン試行、oauth_verifierを取得
			$params = array(
				'authenticity_token'         => $matches[1],
				'oauth_token'                => $this->request_token,
				'force_login'                => '1',
				'session[username_or_email]' => $username,
				'session[password]'          => $password,
			);
			$res = $this->request($url,'POST',$params,false,true,true);
			if ($res===false)
				return UltimateOAuthModule::createErrorObject(
					'Connection failed when fetching oauth_verifier',
					$this->lastHTTPStatusCode()
				);
			$pattern = '@oauth_verifier=(.+?)"|<code>(.+?)</code>@';
			if (!preg_match($pattern,$res,$matches))
				return UltimateOAuthModule::createErrorObject(
					'Wrong username or password.',
					$this->lastHTTPStatusCode()
				);
			$this->oauth_verifier = (!empty($matches[1])) ? $matches[1] : $matches[2];
			
			// アクセストークン取得
			$res = $this->post('oauth/access_token',array(
				'oauth_verifier' => $this->oauth_verifier,
			));
			if (isset($res->errors))
				return UltimateOAuthModule::createErrorObject($res->errors[0]->message,$this->lastHTTPStatusCode());
			
			// アクセストークン、アクセストークンシークレットをオブジェクトプロパティでまとめて返す
			return $res;
		
		} catch (Exception $e) {
		
			return UltimateOAuthModule::createErrorObject($e->getMessage());
			
		}
	
	}
	
	/*****************/
	/**** Private ****/
	/*****************/
	
	# プロパティ宣言
	private $consumer_key;
	private $consumer_secret;
	private $access_token;
	private $access_token_secret;
	private $request_token;
	private $request_token_secret;
	private $authenticity_token;
	private $oauth_verifier;
	private $cookie;
	private $last_http_status_code;
	private $last_called_endpoint;
	
	# パラメータ修正
	private static function modParameters(&$params) {
		
		// 配列以外はスルー
		if (!is_array($params)) {
			$params = array();
			return;
		}
		
		$ret = array();
		foreach ($params as $key => $value) {
			// スカラー値以外のもの・NULL値はスルー
			if (!UltimateOAuthModule::convertible($value) || $value===null)
				continue;
			// 「FALSE」は「0」に変換
			if ($value===false)
				$ret[$key] = '0';
			$ret[$key] = $value;
		}
		$params = $ret;
		
	}
	
	# 接続
	private function connect($host,$scheme,$request,$wait_response) {
		
		// スキームによりポート振り分け
		if ($scheme==='https') {
			$host = 'ssl://'.$host; //HTTPSの場合HOSTに「ssl://」が必要
			$port = 443;
		} else {
			$port = 80;
		}
		
		// ソケットを開く
		$fp = @fsockopen($host,$port);
		if ($fp===false)
			throw new Exception('Failed to open socket.');
		
		// リクエスト送信
		if (fwrite($fp,$request)===false)
			throw new Exception('Failed to send request.');
		
		if ($wait_response) {
			// レスポンス回収
			ob_start();
			fpassthru($fp);
			// $res[0]にレスポンスヘッダー、$res[1]にレスポンスボディが入る
			$res = explode("\r\n\r\n",ob_get_clean(),2);
			if (!isset($res[1]) || !preg_match('@^HTTP/1\\.0 (\\d+)@',$res[0],$matches))
				throw new Exception('Invalid response.');
			// HTTPステータスコード更新
			$this->last_http_status_code = (int)$matches[1];
		}
		
		// ソケットを閉じる
		fclose($fp);
		
		// レスポンスを待たない場合は値を返さない
		if (!$wait_response)
			return;
			
		// Set-Cookieヘッダを調べる
		if (preg_match_all('/^Set-Cookie:(.+?)(?:;|$)/mi',$res[0],$matches)) {
			// Set-Cookieヘッダを1行ずつイテレート
			foreach ($matches[1] as $match) {
				$pair = explode('=',trim($match),2);
				if (!isset($pair[1]))
					continue;
				// Cookieを設定
				$this->cookie[$pair[0]] = $pair[1];
			}
		}
		
		// レスポンスボディを返す
		return $res[1];
		
	}
	
	# リクエスト処理(例外キャッチはここで)
	private function request($uri,$method,$params,$multipart,$wait_response,$scraping) {
		
		try {
			
			// HTTPステータスコード等初期化
			$this->last_http_status_code = -1;
			$this->last_called_endpoint  = '';
			
			if (!UltimateOAuthModule::convertible($uri))
				throw new Exception('$uri must be string.');
			if (!UltimateOAuthModule::convertible($method))
				throw new Exception('$method must be string.');
			
			// メソッドを大文字に統一
			$method = strtoupper($method);
			
			if ($multipart && ($method!=='POST' || $scraping))
				throw new Exception('Multipart requests are supported only on the POST OAuth method in this library.');
			
			// URIをパース
			$elements = self::parse_uri($uri);
			
			// パラメータ配列にいったんURIにあったものを追加
			parse_str($elements['query'],$temp);
			$params += $temp;
			
			// oauth_verifierがパラメータにあった場合、プロパティに直接設定してunset
			if (
				$elements['path']==='/oauth/access_token' &&
				isset($params['oauth_verifier']) &&
				UltimateOAuthModule::convertible($params['oauth_verifier'])
			) {
				$this->oauth_verifier = (string)$params['oauth_verifier'];
				unset($params['oauth_verifier']);
			}
			
			if (!$scraping) {
				
				// OAuth認証のとき
				
				// QueryString取得
				$query = $this->getQueryString(
					$elements['scheme'].'://'.$elements['host'].$elements['path'],
					$elements['path'],
					$method,
					$params,
					$multipart // マルチパートの場合はAuthorizationヘッダーとして取得
				);
				
			} else {
			
				// スクレイピング時、QueryStringはそのままパラメータから組み立てる
				$query = http_build_query($params,'','&');
			
			}
			
			if ($method==='GET' && !$multipart)
				// GETメソッドでマルチパートでないとき、パスにQueryStringを付加
				$path = $elements['path'].'?'.$query;
			else
				$path = $elements['path'];
				
			// ヘッダー行作成
			$lines = array(
				sprintf('%s %s HTTP/1.0',strtoupper($method),$path),
				'Host: '       . $elements['host']               ,
				'User-Agent: ' . UltimateOAuthConfig::USER_AGENT ,
				'Connection: ' . 'Close'                         ,
				"\r\n"                                           ,
			);
			
			// クッキーがあれば追加
			if ($this->cookie) {
				array_splice($lines,-1,0,array(
					'Cookie: '.implode('; ',UltimateOAuthModule::pairize($this->cookie)),
				));
			}
			
			if ($multipart) {
			
				// POSTメソッドでマルチパートのとき
				
				// バウンダリ生成
				$boundary = '--------------'.sha1($_SERVER['REQUEST_TIME']);
				
				// コンテンツ行作成
				$cts_lines = array();
				foreach ($params as $key => $value) {
					$cts_lines[] = '--'.$boundary;
					// キーの頭に「@」がつく場合のみ、それを除去すると同時に、値を「ファイル名」として扱う
					if (strpos($key,'@')===0) {
						if (!is_file($value))
							throw new Exception("File '{$value}' not found.");
						$disposition = sprintf('form-data; name="%s"; filename="%s"',
							substr($key,1),
							basename($value)
						);
						array_push($cts_lines,
							'Content-Disposition: ' .  $disposition              ,
							'Content-Type: '        . 'application/octet-stream' ,
							''                                                   ,
							file_get_contents($value)
						);
					} else {
						$disposition = sprintf('form-data; name="%s"',
							substr($key,1)
						);
						array_push($cts_lines,
							'Content-Disposition: ' . $disposition ,
							''                                     ,
							$value
						);
					}
				}
				$cts_lines[] = '--'.$boundary.'--';
				
				// コンテンツ行を連結
				$contents = implode("\r\n",$cts_lines);
				
				// 追加ヘッダ
				$adds = array(
					'Authorization: '  . 'OAuth '.$query                            ,
					'Content-Type: '   . 'multipart/form-data; boundary='.$boundary ,
					'Content-Length: ' . strlen($contents)                          ,
				);
				array_splice($lines,-1,0,$adds);
				
			} elseif ($method==='POST') {
			
				// POSTメソッドでマルチパートでないとき
				
				// 追加ヘッダ
				$adds = array(
					'Content-Type: '   . 'application/x-www-form-urlencoded'        ,
					'Content-Length: ' . strlen($query)                             ,
				);
				array_splice($lines,-1,0,$adds);
				
			}
			
			// ヘッダ行連結
			$request = implode("\r\n",$lines);
			
			if ($multipart)
				// POSTメソッドでマルチパートのときコンテンツを付加
				$request .= $contents;
			elseif ($method==='POST')
				// POSTメソッドでマルチパートでないときQueryStringを付加
				$request .= $query;
			
			// 接続
			$res = $this->connect(
				$elements['host']   ,
				$elements['scheme'] ,
				$request            ,
				$wait_response
			);
			
			// ラストコールを更新
			$this->last_called_endpoint = $elements['path'];
			
			// レスポンスを待たない場合は値を返さない
			if (!$wait_response)
				return;
			
			if ($scraping) {
			
				// スクレピング時はそのままボディを返す
				return $res;
				
			} elseif (preg_match('@^/oauth/(?:(request)|access)_token$@',$elements['path'],$matches) && strpos($res,'{')!==0) {
				
				// トークンを取得するエンドポイントを叩いたとき
				parse_str($res,$oauth_tokens);
				
				// 失敗したらエラーを返す
				if (!isset($oauth_tokens['oauth_token'],$oauth_tokens['oauth_token_secret']))
					throw new Exception($res);
				
				// トークンを取得した場合、プロパティを上書き
				if (empty($matches[1])) {
					if (isset($oauth_tokens['oauth_token']))
						$this->access_token        = $oauth_tokens['oauth_token'];
					if (isset($oauth_tokens['oauth_token_secret']))
						$this->access_token_secret = $oauth_tokens['oauth_token_secret'];
					$res = (object)array(
						'oauth_token'        => $this->access_token         ,
						'oauth_token_secret' => $this->access_token_secret  ,
					);
				} else {
					if (isset($oauth_tokens['oauth_token']))
						$this->request_token        = $oauth_tokens['oauth_token'];
					if (isset($oauth_tokens['oauth_token_secret']))
						$this->request_token_secret = $oauth_tokens['oauth_token_secret'];
					// オブジェクト形式にする
					$res = (object)array(
						'oauth_token'        => $this->request_token        ,
						'oauth_token_secret' => $this->request_token_secret ,
					);
				}
				
				// user_idとscreen_nameをプロパティに設定
				if (isset($oauth_tokens['user_id'],$oauth_tokens['screen_name'])) {
					$res->user_id     = $oauth_tokens['user_id'];
					$res->screen_name = $oauth_tokens['screen_name'];
				}
				
			} else {
			
				// それ以外の場合はJSONをデコード
				$res = json_decode($res);
				
				// デコードに失敗したらエラー
				if (!$res)
					throw new Exception('Failed to decode as JSON. There may be some errors on the request header.');
				
			}
			
			// 奇形なエラーレスポンスを修正
			if (isset($res->error)) {
				$res = (object)array(
					'errors' => array(
						(object)array(
							'code'    => -1          ,
							'message' => $res->error ,
						),
					),
				);
			} elseif (isset($res->errors) && !is_array($res->errors)) {
				$res = (object)array(
					'errors' => array(
						(object)array(
							'code'    => -1           ,
							'message' => $res->errors ,
						),
					),
				);
			}
			
			// エラーコードをHTTPステータスコードで上書き
			if (isset($res->errors))
				foreach ($res->errors as $error)
					$error->code = $this->lastHTTPStatusCode();
			
			// レスポンスを返す
			return $res;
		
		} catch (Exception $e) {
			
			return UltimateOAuthModule::createErrorObject($e->getMessage(),$this->lastHTTPStatusCode());
		
		}
	
	}
	
	# URIをパース
	private function parse_uri($uri) {
		
		if (!UltimateOAuthModule::convertible($uri))
			throw new Exception('URI isn\'t convertible to string.');
		
		$elements = parse_url($uri);
		if ($elements===false)
			throw new Exception('URI is invalid.');
		
		if (!isset($elements['host'])) {
			
			// 省略形の場合
			
			$elements['host']   = UltimateOAuthConfig::DEFAULT_HOST;
			$elements['scheme'] = UltimateOAuthConfig::DEFAULT_SCHEME;
			
			// 「/」で始まる場合は1文字目を一旦削除
			if (strpos($elements['path'],'/')===0)
				$elements['path'] = substr($elements['path'],1);
			
			if (
				strpos($elements['path'],'1')   !== 0 &&
				strpos($elements['path'],'1.1') !== 0 &&
				strpos($elements['path'],'i')   !== 0
			) {
				//バージョン記述がない場合
				if (strpos($elements['path'],'activity/')!==false)
					//アクティビティ系APIの場合はそのバージョン記述を追加
					$elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_ACTIVITY_API_VERSION.'/'.$elements['path'];
				elseif (strpos($elements['path'],'oauth/')===false)
					//OAuth認証系API以外の場合はバージョン記述を追加
					$elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_API_VERSION.'/'.$elements['path'];
				else
					//OAuth認証系APIの場合は「/」のみを追加
					$elements['path'] = '/'.$elements['path'];
			} else {
				//バージョン記述がある場合、スラッシュのみを追加
				$elements['path'] = '/'.$elements['path'];
			}
			
		} else {
		
			// 完全なURLの場合
			
			if (!isset($elements['path']))
				// パスがない場合は「/」に設定
				$elements['path'] = '/';
		
		}
		
		if (!isset($elements['query']))
			// クエリが無い場合は空文字に設定
			$elements['query']  = '';
			
		return $elements;
	
	}
	
	# パラメータとなるQueryStringを作成
	private function getQueryString($uri,$path,$method,$opt,$as_header) {
		
		// 初期パラメータ
		$parameters = array(
			'oauth_consumer_key'     => $this->consumer_key      ,
			'oauth_signature_method' => 'HMAC-SHA1'              ,
			'oauth_timestamp'        => $_SERVER['REQUEST_TIME'] ,
			'oauth_nonce'            => md5(mt_rand())           ,
			'oauth_version'          => '1.0'                    ,
		);
		
		// エンドポイントで追加パラメータを振り分け
		if ($path==='/oauth/request_token') {
			$oauth_token_secret           = '';
		} elseif ($path==='/oauth/access_token') {
			$parameters['oauth_verifier'] = $this->oauth_verifier;
			$parameters['oauth_token']    = $this->request_token;
			$oauth_token_secret           = $this->request_token_secret;
		} else {
			$parameters['oauth_token']    = $this->access_token;
			$oauth_token_secret           = $this->access_token_secret;
		}
		
		// 認証部分以外のパラメータを追加
		if (!$as_header)
			$parameters += $opt;
		
		// シグネチャ作成用ボディ
		$body = implode(
			'&',
			array_map(
				array(
					'UltimateOAuthModule',
					'enc',
				),
				array(
					$method,
					$uri,
					implode(
						'&',
						UltimateOAuthModule::pairize(
							UltimateOAuthModule::nksort(
								array_map(
									array(
										'UltimateOAuthModule',
										'enc',
									),
									$parameters
								)
							)
						)
					),
				)
			)
		);
		
		// シグネチャ作成用キー
		$key = implode(
			'&',
			array_map(
				array(
					'UltimateOAuthModule',
					'enc',
				),
				array(
					$this->consumer_secret,
					$oauth_token_secret,
				)
			)
		);
		
		// シグネチャを作成してパラメータに追加
		$parameters['oauth_signature'] = base64_encode(hash_hmac('sha1',$body,$key,true));
		
		// QueryStringを返す($as_headerがTrueの場合はAuthorizationヘッダーの値として返す)
		return implode(
			$as_header ?
				', ' :
				'&'
			,
			UltimateOAuthModule::pairize(
				array_map(
					array(
						'UltimateOAuthModule',
						'enc',
					),
					$parameters
				)
			)
		);
	
	}
	
}

/**** マルチリクエスト用クラス ****/
class UltimateOAuthMulti {
	
	/****************/
	/**** Public ****/
	/****************/
	
	# コンストラクタ
	public function __construct() {
	
		// プロパティ初期化
		$this->postfields = array();
		$this->objects    = array();
		$this->properties = array();
		if (UltimateOAuthConfig::URL_TO_THIS_FILE)
			$this->url = UltimateOAuthConfig::URL_TO_THIS_FILE;
		else
			$this->url = self::getURL();
		
	}
	
	# ジョブ追加
	// 第1引数(必須)            … UltimateOAuthオブジェクト。参照渡し。
	// 第2引数(必須)            … 実行するメソッド名。
	// 第3引数以降(任意,可変個) … 実行するメソッドに対して渡す引数。
	public function addjob(&$uo,$method) {
		
		// 割り当てるキー
		$i = count($this->objects);
		
		// 第3引数以降を抽出
		$args = array_slice(func_get_args(),2);
		
		// 参照渡しでUltimateOAuthオブジェクトを代入
		$this->objects[$i] = &$uo;
		
		// またはUltiamteOAuthオブジェクト以外が渡されたときNULLを代入
		if (!($uo instanceof UltimateOAuth)) {
			$this->properties[$i] = null;
			$this->postfields[$i] = null;
			return;
		}
		
		// プロパティ(Private)をstdClassでアクセス可能(Public)にして保存
		$this->properties[$i] = $uo->getProperties();
		
		// このファイル自身にPOSTする内容
		$obj = new stdClass;
		$obj->properties = $this->properties[$i];
		$obj->method     = $method;
		$obj->args       = json_encode($args);
		
		// クエリ作成
		$query = http_build_query(
			array(
				UltimateOAuthConfig::MULTI_KEYNAME => rawurlencode(base64_encode(json_encode($obj))),
			),
			'',
			'&'
		);
		
		// ポストフィールドを配列にセット
		$this->postfields[$i] = $query;
	
	}
	
	# マルチリクエスト実行
	// レスポンスを配列で取得する。
	// 実行後にジョブはリセットされ、空になる。
	public function exec() {
		
		// レスポンスを受け取る配列
		$res = array();
		
		// ソケットの配列
		$fps = array();
		
		// ジョブが無ければ空配列を返す
		if (!$this->postfields)
			return $res;
		
		// 送信先のURI成分を求める
		$uri = parse_url(self::getURL());
		if (!$uri || !isset($uri['host'])) {
			$uri = false;
		} else {
			if (!isset($uri['path']))
				$uri['path'] = '/';
			if (!isset($uri['port']))
				$uri['port'] = $uri['scheme']==='https' ? 443 : 80;
			$host = $uri['scheme']==='https' ? 'ssl://'.$uri['host'] : $uri['host'];
		}
		
		// ソケットリソースを作成し、リクエストを送信
		foreach ($this->postfields as $i => $query) {
			if (!$uri) {
				$fps[$i] = false;
			} else {
				$fps[$i] = @fsockopen($host,$uri['port']);
				if ($fps[$i]) {
					stream_set_blocking($fps[$i],0);
					stream_set_timeout($fps[$i],86400);
					$lines = array(
						"POST {$uri['path']} HTTP/1.0",
						"Host: {$host}",
						"Connection: close",
						"Content-Type: application/x-www-form-urlencoded",
						"Content-Length: ".strlen($query),
						"",
						$query
					);
					fwrite($fps[$i],implode("\r\n",$lines));
				}
			}
		}
		
		// 終わるまでループ
		Do {
			$active = false;
			foreach ($fps as $i => $fp) {
				if (!$fp || feof($fp)) {
					continue;
				} else {
					$active = true;
					if (!isset($res[$i]))
						$res[$i] = '';
					$res[$i] .= fgets($fp,10000);
				}
			}
		} while ($active);
		
		// レスポンス整形
		foreach ($res as $i => &$r) {
		
			if ($this->url===false) {
			
				// このファイルへのパス取得に失敗した場合
				$r = UltimateOAuthModule::createErrorObject('Failed to get URL to this file itself.');
				
			} elseif ($this->postfields[$i]===null) {
			
				// UltimateOAuthオブジェクトではなかった場合
				$r = UltimateOAuthModule::createErrorObject('This is not UltimateOAuth object.');
				
			} elseif ($fps[$i]===false) {
			
				// fsockopenでエラーが発生した場合
				$r = UltimateOAuthModule::createErrorObject('Failed to open socket.');
				
			} elseif (!($r=explode("\r\n\r\n",$r,2)) || !isset($r[1])) {
			
				// レスポンスが異常な場合
				$r = UltimateOAuthModule::createErrorObject('Invalid response.');
				
			} elseif (($r=json_decode($r[1]))===null) {
			
				// JSONデコードに失敗した場合
				$r = UltimateOAuthModule::createErrorObject('Failed to decode as JSON. There may be some errors.');
			
			} else {
			
				// 正常時はレスポンスを代入すると同時に、UltimateOAuthオブジェクトを受け取ったプロパティをもとに再作成
				$p = $this->properties[$i];
				$obj= (isset($r->object) && is_string($r->object)) ? json_decode($r->object) : null ;
				if ($obj===null)
					$obj = new stdClass;
				$this->objects[$i] = new UltimateOAuth(
					isset($obj->consumer_key)          ? $obj->consumer_key          : $p->consumer_key          ,
					isset($obj->consumer_secret)       ? $obj->consumer_secret       : $p->consumer_secret       ,
					isset($obj->access_token)          ? $obj->access_token          : $p->access_token          ,
					isset($obj->access_token_secret)   ? $obj->access_token_secret   : $p->access_token_secret   ,
					isset($obj->request_token)         ? $obj->request_token         : $p->request_token         ,
					isset($obj->request_token_secret)  ? $obj->request_token_secret  : $p->request_token_secret  ,
					isset($obj->oauth_verifier)        ? $obj->oauth_verifier        : $p->oauth_verifier        ,
					isset($obj->authenticity_token)    ? $obj->authenticity_token    : $p->authenticity_token    ,
					isset($obj->cookie)                ? $obj->cookie                : $p->cookie                ,
					isset($obj->last_http_status_code) ? $obj->last_http_status_code : $p->last_http_status_code ,
					isset($obj->last_called_endpoint)  ? $obj->last_called_endpoint  : $p->last_called_endpoint
				);
				$r = (isset($r->response) && is_string($r->response)) ? json_decode($r->response) : null ;
				
			}
			
			// リソースを解放
			if (is_resource($fps[$i]))
				fclose($fps[$i]);
			
		}
			
		// プロパティ初期化
		$this->objects    = array();
		$this->properties = array();
		$this->postfields = array();
		
		// レスポンスを返す
		return $res;
		
	}
	
	# シリアライズ防止
	public function __sleep() {
		throw new BadMethodCallException('You cannot serialize this object.');
	}
	
	/*****************/
	/**** Private ****/
	/*****************/
	
	# プロパティ宣言
	private $objects;
	private $properties;
	private $postfields;
	private $url;

	# 絶対URL取得 (UNIX環境のみ対応)
	private static function getURL() {
	
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
		$url = str_replace($document_root_path,$document_root_url,__FILE__);
		if ($url===__FILE__)
			return false;
		return $url;
		
	}
	
	# マルチリクエストから実行
	public static function call() {
		
		// POSTキーをチェック
		if (
			isset($_POST[UltimateOAuthConfig::MULTI_KEYNAME]) &&
			!is_array($_POST[UltimateOAuthConfig::MULTI_KEYNAME])
		) {
			
			// デコードして復元
			$obj = json_decode(base64_decode(rawurldecode($_POST[UltimateOAuthConfig::MULTI_KEYNAME])));
			if (!is_object($obj))
				$obj = new stdClass;
			if (!isset($obj->properties) || !is_object($obj->properties))
				$obj->properties = new stdClass;
			$p = $obj->properties;
			$method = 
				(
					isset($obj->method) &&
					UltimateOAuthModule::convertible($obj->method)
				) ?
				$obj->method :
				''
			;
			$args = isset($obj->args) ? json_decode($obj->args,true) : array();
			$uo = new UltimateOAuth(
				isset($p->consumer_key)          ? $p->consumer_key          : ''      ,
				isset($p->consumer_secret)       ? $p->consumer_secret       : ''      ,
				isset($p->access_token)          ? $p->access_token          : ''      ,
				isset($p->access_token_secret)   ? $p->access_token_secret   : ''      ,
				isset($p->request_token)         ? $p->request_token         : ''      ,
				isset($p->request_token_secret)  ? $p->request_token_secret  : ''      ,
				isset($p->oauth_verifier)        ? $p->oauth_verifier        : ''      ,
				isset($p->authenticity_token)    ? $p->authenticity_token    : ''      ,
				isset($p->cookie)                ? $p->cookie                : array() ,
				isset($p->last_http_status_code) ? $p->last_http_status_code : -1      ,
				isset($p->last_called_endpoint)  ? $p->last_called_endpoint  : ''      
			);
			
			// メソッドをコール
			if (!is_callable(array($uo,$method)))
				$ret = json_encode(UltimateOAuthModule::createErrorObject("Cant call '{$method}'."));
			else
				$ret = json_encode(call_user_func_array(array($uo,$method),$args));
				
			// 結果をJSONで出力
			echo json_encode(
				array(
					'object'   => json_encode($uo->getProperties()) ,
					'response' => $ret                              ,
				)
			);
			
			exit();
			
		}
	
	}

}

/**** 複数キーローテーション管理用クラス ****/
class UltimateOAuthRotate {
	
	/****************/
	/**** Public ****/
	/****************/
	
	# コンストラクタ
	public function __construct() {
		
		// プロパティ初期化
		$this->current = array(
			'POST' => null,
			'GET'  => array(),
		);
		$this->my       = array();
		$this->official = array();
		foreach (self::getOfficialKeys() as $name => $consumer) {
			$this->official[$name] = new UltimateOAuth(
				$consumer['consumer_key'],
				$consumer['consumer_secret']
			);
			
		}
	
	}
	
	# POSTで使うカレントアプリケーションを設定
	public function setCurrent($name) {
		
		// my,officialの優先順
		foreach ($this->my as $key => $value)
			if ($key===$name) {
				$this->current['POST'] = array('my',$name);
				return true;
			}
		foreach ($this->official as $key => $value)
			if ($key===$name) {
				$this->current['POST'] = array('official',$name);
				return true;
			}
		return false;
		
	}

	
	# 自分のアプリケーションを登録(既にある公式キーの名前と重複してはいけない)
	public function register(
		$name,           // 識別子。自分が分かればいい。
		$consumer_key,   // コンシューマーキー。
		$consumer_secret // コンシューマーシークレット。
	) {
		
		if (isset($this->official[$name]))
			return false;
			
		$this->my[$name] = new UltimateOAuth(
			$consumer_key,
			$consumer_secret
		);
		
		return true;
	
	}
	
	# ログイン(返り値はデフォルトではTrue[全て成功]/False[1つ以上失敗])
	public function login(
		$username,         // スクリーンネームまたはEメールアドレス。
		$password,         // パスワード。
		$return_bool=true, // Falseで返り値を各キーごとのに変更。
		$parallel=true     // Falseで逐次処理にする。
	) {
	
		if ($parallel)
			return $this->login_async($username,$password,$return_bool);
		else
			return $this->login_sync($username,$password,$return_bool);
	
	}
	
	# このクラス内に存在しないメソッドがコールされたとき代わりにこのメソッドが実行される
	// あたかもこのクラスの中に
	public function __call(
		$name, // メソッド名。UltimateOAuthにあるものを呼ぶ。
		$args  // 引数の配列。
	) {
		
		try {
			
			// POSTメソッドだが公式キーを使わせる例外
			$post_ex = array(
				'friendships/accept.json',
				'friendships/deny.json',
				'friendships/accept_all.json',
			);
			
			if (
				!strcasecmp($name,'get') ||
				!strcasecmp($name,'OAuthRequest') && (
					isset($args[1]) && !strcasecmp($args[1],'GET') ||
					count($args) < 2
				)
			) {
				
				// GET
				
				// 最初の引数は必須
				if (!isset($args[0]))
					throw new Exception('First parameter required as URI.');
				if (!UltimateOAuthModule::convertible($args[0]))
					throw new Exception('First parameter isn\'t convertible to string.');
				
				// エンドポイント取得
				$endpoint = UltimateOAuthModule::endpoint($args[0]);
				
				// 番号→名前対応テーブル作成
				$table = array_keys(self::getOfficialKeys());
				
				// カウントアップ
				if (!isset($this->current['GET'][$endpoint]))
					$this->current['GET'][$endpoint] = 0;
				else
					$this->current['GET'][$endpoint]++;
				
				// その値がテーブルに存在しないキーならば0まで減らす
				if (!isset($table[$this->current['GET'][$endpoint]]))
					$this->current['GET'][$endpoint] = 0;
					
				// 現在の番号に対応するアプリケーションに対応したUltimateOAuthオブジェクトを選択
				$obj = $this->official[$table[$this->current['GET'][$endpoint]]];
				
				// メソッドをコールして結果を返す
				return UltimateOAuthModule::call(array(&$obj,$name),$args);
				
			} elseif (
				!strcasecmp($name,'post') ||
				!strcasecmp($name,'OAuthRequest') && isset($args[1]) && !strcasecmp($args[1],'POST') ||
				!strcasecmp($name,'OAuthRequestMultipart')
			) {
				
				// POST
				
				// setCurrentでの設定を済ませていなければmy,officialの優先順で先頭のオブジェクトに設定
				if ($this->current['POST']===null) {
					if ($this->my) {
						$keys = array_keys($this->my);
						$this->current['POST'] = array('my',$keys[0]);
					} else {
						$keys = array_keys($this->official);
						$this->current['POST'] = array('official',$keys[0]);
					}
				}
				
				// UltimateOAuthオブジェクトを選択
				$app_type = $this->current['POST'][0];
				$app_name = $this->current['POST'][1];
				$obj = $this->{$app_type}[$app_name];
				
				// 例外にマッチしたときは先頭の公式キーに変更
				foreach ($post_ex as $ex) {
					if (strpos($args[0],$ex)!==false) {
						$keys = array_keys($this->official);
						$obj = $this->official[$keys[0]];
						break;
					}
				}
				
				// メソッドをコールして結果を返す
				return UltimateOAuthModule::call(array(&$obj,$name),$args);
				
			} else {
				
				// コール失敗
				throw new Exception("Failed to call '{$name}'.");
			
			}
			
		} catch (Exception $e) {
		
			return UltimateOAuthModule::createErrorObject($e->getMessage());
		
		}
	
	}
	
	/*****************/
	/**** Private ****/
	/*****************/
	
	# プロパティ宣言
	private $current;
	private $my;
	private $official;

	# 公式キー取得
	private static function getOfficialKeys($include_signup=false) {
		
		$ret = array(
			'Twitter for iPhone' => array(
				'consumer_key'    => 'IQKbtAYlXLripLGPWd0HUA',
				'consumer_secret' => 'GgDYlkSvaPxGxC4X8liwpUoqKwwr3lCADbz8A7ADU',
			),
			'Twitter for Android' => array(
				'consumer_key'    => '3nVuSoBZnx6U4vzUxf5w',
				'consumer_secret' => 'Bcs59EFbbsdF6Sl9Ng71smgStWEGwXXKSjYvPVt7qys',
			),
			'Twitter for iPad' => array(
				'consumer_key'    => 'CjulERsDeqhhjSme66ECg',
				'consumer_secret' => 'IQWdVyqFxghAtURHGeGiWAsmCAGmdW3WmbEx6Hck',
			),
			'Twitter for Mac' => array(
				'consumer_key'    => '3rJOl1ODzm9yZy63FACdg',
				'consumer_secret' => '5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8',
			),
			'Twitter for Windows Phone' => array(
				'consumer_key'    => 'yN3DUNVO0Me63IAQdhTfCA',
				'consumer_secret' => 'c768oTKdzAjIYCmpSNIdZbGaG0t6rOhSFQP0S5uC79g',
			),
			'TweetDeck' => array(
				'consumer_key'    => 'yT577ApRtZw51q4NPMPPOQ',
				'consumer_secret' => '3neq3XqN5fO3obqwZoajavGFCUrC42ZfbrLXy5sCv8',
			),
		);
		
		// Sign-UpはDM権限が無いため標準では除外
		if ($include_signup)
			$ret += array(
				'Twitter for Android Sign-Up' => array(
					'consumer_key'    => 'RwYLhxGZpMqsWZENFVw',
					'consumer_secret' => 'Jk80YVGqc7Iz1IDEjCI6x3ExMSBnGjzBAH6qHcWJlo',
				)
			);
		
		return $ret;
	
	}
	
	# 非同期ログイン(高速)
	private function login_async($username,$password,$return_bool) {
	
		// UltimateOAuthMultiオブジェクト作成
		$uom = new UltimateOAuthMulti();
		
		// my,officialともに全てのUltimateOAuthオブジェクトの認証ジョブを追加
		foreach ($this->my as $name => &$item)
			$uom->addjob($item,'BgOAuthGetToken',$username,$password);
		foreach ($this->official as $name => &$item)
			$uom->addjob($item,'BgOAuthGetToken',$username,$password);
		
		// 実行して連想配列の結果セットを作る
		$res = array_combine(
			array_keys($this->my + $this->official),
			$uom->exec()
		);
		
		// $return_boolの有無に応じてレスポンスを切り替え
		if ($return_bool) {
			foreach ($res as $r) {
				if (isset($r->errors))
					return false;
			}
			return true;
		} else {
			return $res;
		}
	
	}
	
	# 同期ログイン(低速)
	private function login_sync($username,$password,$return_bool) {
	
		// my,officialともに全てのUltimateOAuthオブジェクトで認証させる
		// $require_all_successがTrueならば失敗した段階でFalseを返す
		$res = array();
		foreach ($this->my as $name => &$item) {
			$r = $item->BgOAuthGetToken($username,$password);
			if ($return_bool && isset($r->errors))
				return false;
			$res[$name] = $r;
		}
		foreach ($this->official as $name => &$item) {
			$r = $item->BgOAuthGetToken($username,$password);
			if ($return_bool && isset($r->errors))
				return false;
			$res[$name] = $r;
		}
		
		// $return_boolの有無に応じてレスポンスを切り替え
		if ($return_bool)
			return true;
		else
			return $res;
	
	}
	

}

/**** 共通モジュール ****/
// 内部的に使うもの
class UltimateOAuthModule {
	
	# 自然順にキーソートした配列を返す
	public static function nksort($arr) {
	
		uksort($arr,'strnatcmp');
		return $arr;
		
	}
	
	# TwitterのOAuth認証の仕様に則ってURLエンコード
	public static function enc($str) {
	
		return str_replace('%7E','~',rawurlencode($str));
		
	}
	
	# 配列のキーと値を「=」でつないでペアにする
	public static function pairize($arr) {
	
		$ret = array();
		foreach ($arr as $key => $value)
			$ret[] = $key.'='.$value;
		return $ret;
		
	}
	
	# 文字列にキャスト可能かどうか判定
	public static function convertible($var) {
		
		return (
			!is_array($var) &&
			!is_resource($var) &&
			(!is_object($var) || method_exists($var,'__toString'))
		);
		
	}
	
	# エラーオブジェクト作成
	public static function createErrorObject($msg,$code=-1) {
	
		return json_decode(
			sprintf('{"errors":[{"message":%s,"code":%d}]}',
				json_encode($msg),
				$code
			)
		);
	
	}
	
	# call_user_func_arrayラッパー
	public static function call($callable,$args) {
		
		$res = @call_user_func_array($callable,$args);
		if ($res===false)
			throw new Exception('Some errors ocurred on executing function.');
		return $res;
		
	}
	
	# URIからエンドポイントのパスのみ抽出
	public static function endpoint($uri) {
	
		$elements = parse_url($uri);
		if ($elements===false)
			throw new Exception('Invalid URI');
		if (!isset($elements['host'])) {
			if (strpos($elements['path']))
				$elements['path'] = substr($elements['path'],1);
			if (
				strpos($elements['path'],'1')   !== 0 &&
				strpos($elements['path'],'1.1') !== 0 &&
				strpos($elements['path'],'i')   !== 0
			) {
				if (strpos($elements['path'],'activity/')!==false)
					$elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_ACTIVITY_API_VERSION.'/'.$elements['path'];
				elseif (strpos($elements['path'],'oauth/')===false)
					$elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_API_VERSION.'/'.$elements['path'];
				else
					$elements['path'] = '/'.$elements['path'];
			} else {
				$elements['path'] = '/'.$elements['path'];
			}
		} else {
			if (!isset($elements['path']))
				$elements['path'] = '/';
		}
		return $elements['path'];
		
	}

}

// マルチリクエストが来ているかどうかチェック
UltimateOAuthMulti::call();