<?php

//****************************************************************
//****************** UltimateOAuth Version 1.0 *******************
//****************************************************************
//
//                                            作者: @To_aru_User
//
// SimpleOAuth, BgOAuth, BgOAuthMulti の機能を結合させ、
// 更に利便性の向上を図ったライブラリです。
// これ1つで何でも出来ます。
//
// ・トークン取得系エンドポイントにアクセスして取得に成功したとき、
// 　自動的に自身のメンバ変数を更新します。
// 　Publicなものは
// 　consumer_key, consumer_secret, access_token, access_token_secret
// 　の4つで、json_encode等を用いて簡単にシリアライズできます。
//
// ・OAuthRequestImageメソッドまたはそれを利用するメソッド
// 　のパラメータのうち、ファイルパスを表すもののキーの頭に
// 　「@」を付けてください。(例：@media[] @image)
//
// ・レスポンスを待機させたくない場合は、エンドポイント毎のラッパーメソッド
// 　を使用せず、OAuthRequestまたはOAuthRequestImageメソッドを利用し、
// 　$waitResponseにfalseを指定するようにしてください。
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
// ・詳しい仕様はご自分でメソッドをご覧になって確かめてください。（説明放棄）
//
// サンプルコード
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
// ●「Bomb!」「Bomb!!」「Bomb!!!」…と "レスポンスを待機せずに" 10回高速爆撃
//  for ($i=1;$i<=10;$i++)
//    $to->OAuthRequest('https://api.twitter.com/1.1/statuses/update.json','POST',array('status'=>'Bomb',str_repeat('!',$i)),false);
//
// ★バックグラウンドOAuth認証からそのままツイート、結果も表示
//  $consumer_key    = 'xxxxxxxxxx';
//  $consumer_secret = 'yyyyyyyyyy';
//  $username        = 'zzzzzzzzzz';
//  $password        = 'wwwwwwwwww';
//  $to = new UltimateOAuth($consumer_key,$consumer_secret);
//  $res = $to->BgOAuthGetToken($username,$password);
//  $success = false;
//  if (empty($res->errors)) {
//    $res = $to->POST_statuses_update(array('status'=>'Tweeting through BgOAuth'));
//    if (empty($res->errors)) {
//      echo "Tweeting Done: $res->text<br />\n";
//      $success = true;
//    }
//  }
//  if ($success===false) {
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
		$this->consumer_key          = $consumer_key;
		$this->consumer_secret       = $consumer_secret;
		if (strpos($oauth_token,'-')===false) {
			$this->request_token          = $oauth_token;
			$this->request_token_secret   = $oauth_token_secret;
		} else {
			$this->access_token           = $oauth_token;
			$this->access_token_secret    = $oauth_token_secret;
		}
		$this->oauth_verifier = $oauth_verifier;
		$this->cookie = array();
	}
	
	# OAuthリクエスト
	public function OAuthRequest($url,$method='GET',$params=array(),$waitResponse=true) {
		$method = strtoupper($method);
		$element = self::getUriElements($url);
		if ($element===false)
			return false;
		parse_str($element['query'],$temp);
		$params += $temp;
		$content = $this->getParameters($element['scheme'].'://'.$element['host'].$element['path'],$method,$params);
		if ($method==='GET')
			$element['path'] .= '?'.$content;
		$request  = '';
		$request .= $method.' '.$element['path'].' HTTP/1.1'."\r\n";
		$request .= 'Host: '.$element['host']."\r\n";
		$request .= 'User-Agent: UltimateOAuth'."\r\n";
		$request .= 'Connection: Close'."\r\n";
		if ($method==='POST') {
			$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
			$request .= 'Content-Length: '.strlen($content)."\r\n";
		}
		$request .= "\r\n";
		if ($method==='POST')
			$request .= $content;
		$res = $this->connect($element['host'],$element['scheme'],$request,$waitResponse);
		if (preg_match('@oauth/(?:(request)|access)_token@',$element['path'],$matches)) {
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
	
	# マルチパートOAuthリクエスト (ファイルパスを示すものはパラメータ名先頭に「@」を付加)
	public function OAuthRequestImage($url,$params=array(),$waitResponse=true) {
		$element = self::getUriElements($url);
		if ($element===false)
			return false;
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
		$request .= 'User-Agent: UltimateOAuth'."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Authorization: OAuth '.$this->getParameters($url,'POST',array(),true)."\r\n";
		$request .= 'Content-Type: multipart/form-data; boundary='.$boundary."\r\n";
		$request .= 'Content-Length: '.strlen($content)."\r\n";
		$request .= "\r\n";
		$request .= $content;
		$res = $this->connect($element['host'],$element['scheme'],$request,$waitResponse);
		if (preg_match('@oauth/(?:(request)|access)_token@',$element['path'],$matches)) {
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
	
	# BgOAuthリクエスト（アクセストークンを取得・設定）
	public function BgOAuthGetToken($username,$password) {
		$toPairs = create_function('$a','$p=array();foreach($a as $k=>$v)$p[]=$k."=".$v;return $p;');
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/request_token','POST');
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to get request_token","code":-1}]}';
			return json_decode($res);
		}
		$q = 'force_login=true&oauth_token='.$this->request_token;
		$request  = '';
		$request .= 'GET /oauth/authorize?'.$q.' HTTP/1.1'."\r\n";
		$request .= 'Host: api.twitter.com'."\r\n";
		$request .= 'User-Agent: UltimateOAuth'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= "\r\n";
		$res = $this->connect('api.twitter.com','https',$request);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when fetching authenticity_token","code":-1}]}';
			return json_decode($res);
		}
		$pattern = '@<input name="authenticity_token" type="hidden" value="(.+?)" />@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Failed to get authenticity_token","code":-1}]}';
			return json_decode($res);
		}
		$q = http_build_query(
			array(
				'authenticity_token' => $matches[1],
				'oauth_token' => $this->request_token,
				'force_login' => '1',
				'session[username_or_email]' => $username,
				'session[password]' => $password
			),'','&'
		);
		$request  = '';
		$request .= 'POST /oauth/authorize HTTP/1.1'."\r\n";
		$request .= 'Host: api.twitter.com'."\r\n";
		$request .= 'User-Agent: UltimateOAuth'."\r\n";
		$request .= 'Cookie: '.implode('; ',$toPairs($this->cookie))."\r\n";
		$request .= 'Connection: Close'."\r\n";
		$request .= 'Content-Type: application/x-www-form-urlencoded'."\r\n";
		$request .= 'Content-Length: '.strlen($q)."\r\n";
		$request .= "\r\n";
		$request .= $q;
		$res = $this->connect('api.twitter.com','https',$request);
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to open login page when logining","code":-1}]}';
			return json_decode($res);
		}
		$pattern = '@oauth_verifier=(.+?)"|<code>(.+?)</code>@';
		if (!preg_match($pattern,$res,$matches)) {
			$res = '{"errors":[{"message":"Failed to get oauth_verifier","code":-1}]}';
			return json_decode($res);
		}
		$this->oauth_verifier = (!empty($matches[1])) ? $matches[1] : $matches[2];
		$res = $this->OAuthRequest('https://api.twitter.com/oauth/access_token','POST');
		if ($res===false) {
			$res = '{"errors":[{"message":"Failed to get access_token","code":-1}]}';
			return json_decode($res);
		}
		return array(
			'access_token' => $this->access_token,
			'access_token_secret' => $this->access_token_secret,
		);
	}
	
	//***** Timelines *****//
	
	# GET statuses/mentions_timeline
	public function GET_statuses_mentions_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/mentions_timeline.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET statuses/user_timeline
	# ユーザーオブジェクトをデフォルトではidのみに簡略化
	public function GET_statuses_user_timeline($params=array()) {
		if (!isset($params['trim_user']) && $this->trim_user_in_user_timeline===true)
			$params['trim_user'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/user_timeline.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET statuses/home_timeline
	public function GET_statuses_home_timeline($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/home_timeline.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET statuses/retweets_of_me
	public function GET_statuses_retweets_of_me($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/retweets_of_me.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Tweets *****//
	
	# GET statuses/retweets/:id
	public function GET_statuses_retweets($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."statuses/retweets/{$id}.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET statuses/show/:id
	public function GET_statuses_show($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."statuses/show/{$id}.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST statuses/destroy/:id
	public function POST_statuses_destroy($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."statuses/destroy/{$id}.json",'POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST statuses/update
	public function POST_statuses_update($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'statuses/update.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST statuses/retweet/:id
	public function POST_statuses_retweet($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."statuses/retweet/{$id}.json",'POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST statuses/update_with_media
	public function POST_statuses_update_with_media($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'statuses/update_with_media.json',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET statuses/oembed
	public function GET_statuses_oembed($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header."statuses/retweet/{$id}.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET search/tweets
	public function GET_search_tweets($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'search/tweets.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Dicrect Messages *****//
	
	# GET direct_messages
	public function GET_direct_messages($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET direct_messages/sent
	public function GET_direct_messages_sent($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/sent.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET direct_messages_show
	public function GET_direct_messages_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST direct_messages_destroy
	public function POST_direct_messages_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST direct_messages_new
	public function POST_direct_messages_new($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'direct_messages/new.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Friends & Followers *****//
	
	# GET friends/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friends_ids($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		if (!isset($params['stringify_ids']) && $this->stringify_ids===true)
			$params['stringify_ids'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friends/ids.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET followers/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_followers_ids($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		if (!isset($params['stringify_ids']) && $this->stringify_ids===true)
			$params['stringify_ids'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'followers/ids.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET friendships/lookup
	public function GET_friendships_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/lookup.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET friendships/incoming
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_incoming($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		if (!isset($params['stringify_ids']) && $this->stringify_ids===true)
			$params['stringify_ids'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/incoming.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET friendships/outgoing
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_friendships_outgoing($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		if (!isset($params['stringify_ids']) && $this->stringify_ids===true)
			$params['stringify_ids'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/outgoing.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST friendships/create
	public function POST_friendships_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST friendships/destroy
	public function POST_friendships_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST friendships/update
	public function POST_friendships_update($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/update.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET friendships/show
	public function GET_friendships_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friendships/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET friends/list
	# デフォルトではcursorに-1を設定
	public function GET_friends_list($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'friends/list.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET followers/list
	# デフォルトではcursorに-1を設定
	public function GET_followers_list($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'followers/list.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Users *****//
	
	# GET account/settings
	# デフォルトではcursorに-1を設定
	public function GET_account_settings() {
		$res = $this->OAuthRequest($this->url_header.'account/settings.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET account/verify_credentials
	public function GET_account_verify_credentials($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/verify_credentials.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/settings
	public function POST_account_settings($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/settings.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_delivery_device
	public function POST_account_update_delivery_device($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_delivery_device.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_profile
	public function POST_account_update_profile($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_profile_background_image
	public function POST_account_update_profile_background_image($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'account/update_profile_background_image.json',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_profile_colors
	public function POST_account_update_profile_colors($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile_colors.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_profile_image
	public function POST_account_update_profile_image($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequestImage($this->url_header.'account/update_profile_image.json',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET blocks/list
	# デフォルトではcursorに-1を設定
	public function GET_blocks_list($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/list.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET blocks/ids
	# デフォルトではcursorに-1を設定
	# idをデフォルトでは文字列化
	public function GET_blocks_ids($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		if (!isset($params['stringify_ids']) && $this->stringify_ids===true)
			$params['stringify_ids'] = true;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/ids.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST blocks/create
	public function POST_blocks_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST blocks/destroy
	public function POST_blocks_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'blocks/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/lookup
	public function GET_users_lookup($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/lookup.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/show
	public function GET_users_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/search
	public function GET_users_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/search.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/contributees
	public function GET_users_contributees($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/contributees.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/contributors
	public function GET_users_contributors($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/contributors.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/remove_profile_banner
	public function POST_account_remove_profile_banner() {
		$res = $this->OAuthRequest($this->url_header.'account/remove_profile_banner.json','POST',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST account/update_profile_banner
	public function POST_account_update_profile_banner($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'account/update_profile_banner.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/profile_banner
	public function GET_users_profile_banner($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/profile_banner.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Suggested Users *****//
	
	# GET users/suggestions/:slug
	# GET users/suggestions
	public function GET_users_suggestions($params=array()) {
		if (isset($params['slug'])) {
			self::modParameters($params);
			$res = $this->OAuthRequest($this->url_header.'users/suggestions/'.$params['slug'].'.json','GET',$params);
		} else {
			$res = $this->OAuthRequest($this->url_header.'users/suggestions.json','GET',array());
		}
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET users/suggestions/:slug/members
	public function GET_users_suggestions_members($params=array()) {
		if (isset($params['slug'])) {
			$slug = $params['slug'];
			unset($params['slug']);
		} else {
			$slug = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."users/suggestions/{$slug}/members.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Favorites *****//
	
	# GET favorites/list
	public function GET_favorites_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/list.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST favorites/destroy
	public function POST_favorites_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST favorites/create
	public function POST_favorites_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'favorites/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Lists *****//
	
	# GET lists/list
	public function GET_lists_list($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/list.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/statuses
	public function GET_lists_statuses($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/statuses.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/members/destroy
	public function POST_lists_members_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/memberships
	# デフォルトではcursorに-1を設定
	public function GET_lists_memberships($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/memberships.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/subscribers
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscribers($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/subscribers/create
	public function POST_lists_subscribers_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/subscribers/show
	public function GET_lists_subscribers_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/subscribers/destroy
	public function POST_lists_subscribers_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscribers/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/members/create_all
	public function POST_lists_members_create_all($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/create_all.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/members/show
	public function GET_lists_members_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/members
	# デフォルトではcursorに-1を設定
	public function GET_lists_members($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/members/create
	public function POST_lists_members_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/destroy
	public function POST_lists_destroy($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/destroy.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/update
	public function POST_lists_update($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/update.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/create
	public function POST_lists_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/show
	public function GET_lists_show($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/show.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET lists/subscriptions
	# デフォルトではcursorに-1を設定
	public function GET_lists_subscriptions($params=array()) {
		if (!isset($params['cursor']))
			$params['cursor'] = -1;
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/subscriptions.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST lists/members/destroy_all
	public function POST_lists_members_destroy_all($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'lists/members/destroy_all.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Saved Searches *****//
	
	# GET saved_searches/list
	public function GET_saved_searches_list() {
		$res = $this->OAuthRequest($this->url_header.'saved_searches/list.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET saved_searches/show/:id
	public function GET_saved_searches_show($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."saved_searches/show/{$id}.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST saved_searches/create
	public function POST_saved_searches_create($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'saved_searches/create.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST saved_searches/show/:id
	public function POST_saved_searches_destroy($params=array()) {
		if (isset($params['id'])) {
			$id = $params['id'];
			unset($params['id']);
		} else {
			$id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."saved_searches/destroy/{$id}.json",'POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Places & Geo *****//
	
	# GET geo/id/:place_id
	public function GET_geo_id($params=array()) {
		if (isset($params['place_id'])) {
			$place_id = $params['place_id'];
			unset($params['place_id']);
		} else {
			$place_id = '';
		}
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header."geo/id/{$place_id}.json",'GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET geo/id/reverse_geocode
	public function GET_geo_id_reverse_geocode($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/id/reverse_geocode.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET geo/search
	public function GET_geo_search($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/search.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET geo/similar_places
	public function GET_geo_similar_places($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/similar_places.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# POST geo/place
	public function POST_geo_place($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'geo/place.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Trends *****//
	
	# GET trends/place
	public function GET_trends_place($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'trends/place.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET trends/available
	public function GET_trends_available() {
		$res = $this->OAuthRequest($this->url_header.'trends/available.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET trends/closest
	public function GET_trends_closest($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'trends/closest.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Spam Reporting *****//
	
	# POST users/report_spam
	public function POST_users_report_spam($params=array()) {
		self::modParameters($params);
		$res = $this->OAuthRequest($this->url_header.'users/report_spam.json','POST',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** OAuth *****//
	
	# GET oauth/authenticate
	public function getAuthenticateURL() {
		$params = array('oauth_token'=>$this->request_token);
		$q = http_build_query($params,'','&');
		return $this->authening_url_header.'oauth/authenticate?'.$q;
	}
	
	# GET oauth/authorize
	public function getAuthorizeURL() {
		$params = array('oauth_token'=>$this->request_token);
		$q = http_build_query($params,'','&');
		return $this->authening_url_header.'oauth/authorize?'.$q;
	}
	
	# POST oauth/access_token
	public function POST_oauth_access_token() {
		$this->OAuthRequest($this->oauth_url_header.'oauth/access_token','POST',array());
		return array('oauth_token'=>$this->access_token,'oauth_token_secret'=>$this->access_token_secret);
	}
	
	# POST oauth/request_token
	public function POST_oauth_request_token() {
		$this->OAuthRequest($this->oauth_url_header.'oauth/request_token','POST',array());
		return array('oauth_token'=>$this->request_token,'oauth_token_secret'=>$this->request_token_secret);
	}
	
	//***** Activity *****//
	
	# GET activity/by_friends
	public function GET_activity_by_friends() {
		$res = $this->OAuthRequest($this->activity_url_header.'activity/by_friends.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET activity/about_me
	public function GET_activity_about_me() {
		$res = $this->OAuthRequest($this->activity_url_header.'activity/about_me.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	//***** Help *****//
	
	# GET help/configuration
	public function GET_help_configuration() {
		$res = $this->OAuthRequest($this->url_header.'help/configuration.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET help/languages
	public function GET_help_languages() {
		$res = $this->OAuthRequest($this->url_header.'help/languages.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET help/privacy
	public function GET_help_privacy() {
		$res = $this->OAuthRequest($this->url_header.'help/privacy.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET help/tos
	public function GET_help_tos() {
		$res = $this->OAuthRequest($this->url_header.'help/tos.json','GET',array());
		self::modResponse($res);
		return json_decode($res);
	}
	
	# GET application/rate_limit_status
	public function GET_application_rate_limit_status($params=array()) {
		$res = $this->OAuthRequest($this->url_header.'application/rate_limit_status.json','GET',$params);
		self::modResponse($res);
		return json_decode($res);
	}
	
	/************************************/
	/*********** Insider Area ***********/
	/************************************/
	
	public $consumer_key;
	public $consumer_secret;
	public $access_token;
	public $access_token_secret;
	
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
		if (isset($res[1]) && preg_match('/^Set-Cookie:(.+?)$/mi',$res[0],$matches)) {
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
	
	// URLをパースして最適化
	private static function getUriElements($url) {
		$parsed = parse_url($url);
		if (empty($parsed) || !isset($parsed['host']))
			return false;
		$parsed['scheme'] = !isset($parsed['scheme']) ? 'http' : $parsed['scheme'] ;
		$parsed['path']   = !isset($parsed['path'])   ? '/'    : $parsed['path']   ;
		$parsed['query']  = !isset($parsed['query'])  ? ''     : $parsed['query']  ;
		return $parsed;
	}
	
	// パラメータ取得
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
		} elseif (preg_match('@oauth/(?:authorize|authenticate)@',$url)) {
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
	
	// 通信失敗時にもエラーオブジェクトを返すためにJSON文字列を生成
	private static function modResponse(&$res) {
		if ($res===false)
			$res = '{"errors":[{"message":"Failed to connect","code":-1}]}';
	}
	
	// パラメータ最適化
	private static function modParameters(&$params) {
		$_params = array();
		foreach ($params as $key => $value) {
			if ($value===false)
				$_params[$key] = '0';
			elseif ($value!==null)
				$_params[$key] = $value;
		}
		$params = $_params;
	}
	
}

/**** UltimateOAuthマルチリクエスト用クラス *****/

class UltimateOAuthMulti {
	
	private $job;
	private $path_error;
	
	# コンストラクタ
	public function __construct() {
		$this->job = array();
	}
	
	# ジョブ追加
	//
	// 第1引数 - UltimateOAuthオブジェクト
	// 第2引数 - UltimateOAuthクラス内のPublicメソッド名
	// 第3引数 - 引数を配列形式で
	//
	// ※ UltimateOAuthクラスの多くのラッパーメソッドは$paramsを配列で渡すようになっていますが、
	// 　 こちらの$argsに代入するときはさらにもう一度配列にする必要があることに注意してください。
	// 　 $params = array('status'=>'test');
	// 　 　の場合は
	// 　 $args = array(array('status'=>'test'));
	// 　 　のようになります。
	//
	public function addjob($UltimateOAuthObject,$method,$args=array()) {
		$this->job[] = array(array($UltimateOAuthObject,$method),$args);
	}
	
	# マルチリクエスト実行(結果は追加した順番に配列で返ります)
	public function exec() {
		$res = array();
		$this->path_error = false;
		$path = $this->getPath().basename(__FILE__);
		if (empty($this->job))
			return $res;
		$count = count($this->job);
		$chs = array();
		for ($i=0;$i<$count;$i++) {
			if ($this->path_error===false) {
				$serial = rawurlencode(base64_encode(json_encode($this->job[$i])));
				$query = http_build_query(array('__UltimateOAuthMulti_CALL'=>$serial),'','&');
				$chs[$i] = curl_init();
				curl_setopt($chs[$i],CURLOPT_URL,$path);
				curl_setopt($chs[$i],CURLOPT_POST,true);
				curl_setopt($chs[$i],CURLOPT_POSTFIELDS,$query);
				curl_setopt($chs[$i],CURLOPT_RETURNTRANSFER,true);
			} else {
				$res[] = json_decode('{"errors":[{"message":"Request URI is invalid","code":-1}]}');
			}
		}
		if ($this->path_error===true)
			return $res;
		$mh = curl_multi_init();
		foreach ($chs as $ch)
			curl_multi_add_handle($mh,$ch);
		$active = 0;
		do {
			curl_multi_exec($mh,$active);
		} while ($active>0);
		foreach ($chs as $i => $ch) {
			if (!curl_error($ch)) {
				$res[] = @json_decode(curl_multi_getcontent($ch));
			} else {
				$res[] = json_decode('{"errors":[{"message":"Failed to get cURL content","code":-1}]}');
			}
			curl_multi_remove_handle($mh,$ch);
			curl_close($ch);
		}
		curl_multi_close($mh);
		return $res;
	}
	
	# このファイル自身への絶対URL取得
	private function getPath() {
		$head = 'http://'.$_SERVER['HTTP_HOST'];
		if (!isset($_SERVER['REQUEST_URI']) || strlen($_SERVER['REQUEST_URI'])===0)
			return $head.'/';
		$ruri = strrev($_SERVER['REQUEST_URI']);
		if (!preg_match('@^.*?/+(.*)@',$ruri,$matches)) {
			$this->path_error = true;
			return '';
		}
		return $head.strrev($matches[1]).'/';
	}

}

//「__UltimateOAuthMulti_CALL」という固有のキーを使用
if (isset($_POST['__UltimateOAuthMulti_CALL'])) {
	$args = @json_decode(@base64_decode(@rawurldecode($_POST['__UltimateOAuthMulti_CALL'])));
	echo json_encode(@call_user_func_array($args[0],$args[1]));
}