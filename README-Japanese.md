UltimateOAuth
=============
*__非常に高機能な__ PHPのTwitterライブラリです。*

[English](https://github.com/Certainist/UltimateOAuth/blob/master/README.md)

@Version: 5.1.0  
@Author : CertaiN  
@License: FreeBSD  
@GitHub : http://github.com/certainist  


## \[動作環境\]
- **5.2.0** 以降。
- **cURL** に依存しない。
- 他のファイルに依存しない。このファイル1つでOK。
- UNIXとWindowsどちらでもOK。


## \[クラス・メソッド一覧\]

### UltimateOAuth

```php
$uo = new UltimateOAuth( $consumer_key, $consumer_secret, $access_token="", $access_token_secret="" );

(stdClass|Array)      $uo->get                   ( $endpoint,                $params=array()                      );
(stdClass|      Void) $uo->post                  ( $endpoint,                $params=array(), $wait_response=true );
(stdClass|Array|Void) $uo->OAuthRequest          ( $endpoint, $method="GET", $params=array(), $wait_response=true );
(stdClass|      Void) $uo->OAuthRequestMultipart ( $endpoint,                $params=array(), $wait_response=true );

(stdClass) $uo->directGetToken ( $username, $password );

(String) $uo->getAuthorizeURL    ( $force_login=false );
(String) $uo->getAuthenticateURL ( $force_login=false );
```

### UltimateOAuthMulti

```php
$uom = new UltimateOAuthMulti;

(Void)  $uom->enqueue ( &$uo, $method, $arg1, $arg2, $arg3, ... );
(Array) $uom->execute ();
```

### UltimateOAuthRotate

**UltimateOAuthRotate** クラスは **__call()** メソッドを用いることにより、 **UltimateOAuth** クラスのメソッドのうち `get`, `post`, `OAuthRequest`, `OAuthRequestMultipart` には対応しています。

```php
$uor = new UltimateOAuthRotate;

(mixed)              $uor->__call      ( $name, $arguments );

(Bool)               $uor->register    ( $name, $consumer_key, $consumer_secret );
(Bool|Array)         $uor->login       ( $username, $password, $return_array=false );
(Bool)               $uor->setCurrent  ( $name );
(UltimateOAuth|Bool) $uor->getInstance ( $name );
```

------------------------------------------------------------------

0. マルチリクエスト設定
-----------------------

**UltimateOAuthConfig** の定数を編集してください。

- 無料レンタルサーバーなどで `proc_open()` 関数が禁止されている場合
  
  > - `USE_PROC_OPEN`: **FALSE**
  > - `FULL_URL_TO_THIS_FILE`: このファイル自身への **絶対URL** (絶対パスでは無い)
  
- `proc_open()` 関数が問題なく使える場合
  
  > - `USE_PROC_OPEN`: **TRUE** (デフォルト)
  

1. OAuth認証
-----------------------

ユーザーに認証させるためには、次のステップを踏んでください。

**prepare.php** (ログインページに遷移させるスクリプト)

```php
<?php

// ライブラリ読み込み
require_once('UltimateOAuth.php');

// セッション開始
session_start();

// UltimateOAuthオブジェクトを新規作成してセッションに保存
$_SESSION['uo'] = new UltimateOAuth('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET');
$uo = $_SESSION['uo'];

// リクエストトークンを取得
$res = $uo->post('oauth/request_token');
if (isset($res->errors)) {
    die(sprintf('Error[%d]: %s',
        $res->errors[0]->code,
        $res->errors[0]->message
    ));
}

// Authenticateで認証するなら
$url = $uo->getAuthenticateURL();
// Authorizeで認証するなら
// $url = $uo->getAuthorizeURL();

// Twitterのログインページに遷移
header('Location: '.$url);
exit();
```

ユーザーがログインを完了すると、あらかじめ指定した **コールバックURL** に **oauth_verifier** を伴って遷移します。  
このパラメータは [Twitter Developers](https://dev.twitter.com/apps) で事前に編集しておかなければなりません。

**callback.php** (ログイン後に遷移されるスクリプト)

```php
<?php

// ライブラリ読み込み
require_once('UltimateOAuth.php');

// セッション開始
session_start();

// セッションタイムアウトチェック
if (!isset($_SESSION['uo'])) {
    die('Error[-1]: Session timeout.');
}
$uo = $_SESSION['uo'];

// oauth_verifierパラメータが存在するかチェック
if (!isset($_GET['oauth_verifier']) || !is_string($_GET['oauth_verifier'])) {
    die('Error[-1]: No oauth_verifier');
}

// アクセストークン取得
$res = $uo->post('oauth/access_token', array(
    'oauth_verifier' => $_GET['oauth_verifier']
));
if (isset($res->errors)) {
    die(sprintf('Error[%d]: %s',
        $res->errors[0]->code,
        $res->errors[0]->message
    ));
}

// アプリケーションのメインページに遷移
header('Location: main.php');
exit();
```


**main.php** (メインページ)

```php
<?php

// ライブラリ読み込み
require_once('UltimateOAuth.php');

// セッション開始
session_start();

// セッションタイムアウトチェック
if (!isset($_SESSION['uo'])) {
    die('Error[-1]: Session timeout.');
}
$uo = $_SESSION['uo'];

// ツイートしてみましょう
$uo->post('statuses/update', 'status=テストツイート！');
```



**備考1:**  
アクセストークンとアクセストークンシークレットを既に所持している場合は、  
以下のようにするだけでログイン済みのように出来ます。

```php
<?php

require_once('UltimateOAuth.php');

$uo = new UltimateOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secreet);
```

**備考2:**  
認証済みのUltimateOAuthオブジェクトを文字列として保存・復元したい場合は、  `serialize()` 関数と `unserialize()` 関数をご利用ください。


------------------------------------------------------------------

2-1. UltimateOAuthクラス詳細
----------------------------------

### UltimateOAuth::__construct()

```php
<?php
$uo = new UltimateOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
```

#### 引数

- *__$consumer\_key__*, *__$consumer\_secret__*  
  **必須** 。
  
- *__$access\_token__*, *__$access\_token__*  
  あとでユーザーに認証させる場合は不要。

=========================================

### UltimateOAuth::OAuthRequest()

```php
<?php
$uo->OAuthRequest($endpoints, $method, $params, $wait_response);
```

#### 引数

- *__$endpoints__*  
  [API Documentation](https://dev.twitter.com/docs/api/1.1) を参照。  
  例:  
  `statuses/update`, `1.1/statuses/update`, `https://api.twitter.com/1.1/statuses/update.json`
  
- *__$method__*  
  `GET` か `POST`。 大文字小文字区別なし。
  
- *__$params__*  
  **クエリ文字列** (URLエンコードされていない) か **連想配列** 。  
  例:
  ```php
  <?php
  $params = 'status=テストツイート';
  $params = array('status' => 'テストツイート');
  ```
  ファイルをアップロードするには:
  ```php
  <?php
  $params = '@image='.$filename;
  $params = array('@image' => $filename);
  $params = array('image' => base64_encode(file_get_contents($filename)));
  ```
  
  **備考:**  
  このメソッドは `statuses/update_with_media` には適用できません。  
  **UltimateOAuth::OAuthRequestMultipart()** を代わりに使ってください。
  
- *__$wait\_resposne__*  
  デフォルト値 **TRUE** 。
  以下の記述を読んでください。

#### 返り値

- 成功したとき、デコードされたJSONを返す。  
  通常これは **stdClass** となる。  
  一部のエンドポイントは **配列** を返す。  
  例: `statuses/home_timeline`, `users/lookup`  
  一部のエンドポイントは **クエリ文字列** を取得するが、 自動的に **stdClass** に変換される。  
  例: `oauth/request_token`, `oauth/access_token`
  
- 失敗したとき、 **エラーオブジェクト** を返す。 それは下記のような構造を持つ。
  
  > - `(int) $response->errors[0]->code`  
  >   **HTTPステータスコード** 。 エラーコードではない。  
  >   全てのエラーコードはHTTPステータスコードで上書きされる。  
  >   ローカルで起こったエラーに関しては、 **-1** が設定される。
  >   
  > - `(string) $response->errors[0]->message`  
  >   エラーメッセージ。
  
- `$wait_response` がFALSEに設定されると、常に **NULL** を返す。


=========================================


### UltimateOAuth::get()<br />UltimateOAuth::post()

```php
<?php
$uo->get($endpoints, $params);
$uo->post($endpoints, $params, $wait_response);
```

**UltimateOAuth::OAuthRequest()** のラッパーメソッド。


=========================================


### UltimateOAuth::OAuthRequestMultipart()

```php
<?php
$uo->OAuthRequestMultipart($endpoints, $params, $wait_response);
```

主に `statuses/update_with_media` に対して用いる。


#### 引数

- *__$endpoints__*  
  例: `statuses/update_with_media`
  
- *__$params__*  
  例:
  ```php 
  <?php
  $params = '@media[]='.$filename;
  $params = array('@media[]' => $filename);
  $params = array('media[]' => file_get_contents($filename));
  ```
  
- *__$wait\_response__*  
  **UltimateOAuth::OAuthRequest()** のものと同じ。
  デフォルト値 **TRUE** 。
  
#### 返り値

- **UltimateOAuth::OAuthRequest()** のものと同じ。


=========================================

  
### UltimateOAuth::directGetToken()

あたかもOAuth認証を **xAuth** 認証のように行えるメソッドです。  
私はこれを **疑似xAuth** 認証と呼んでいます。

```php
<?php
$uo->directGetToken($username, $password);
```

#### 引数
 
- *__$username__*  
  スクリーンネーム(screen_name)またはEメールアドレス。
  
- *__$password__*  
  パスワード。
  
#### 返り値

- `oauth/access_token` を用いたときの **UltimateOAuth::OAuthRequest()** のものと同じ。


=========================================


### UltimateOAuth::getAuthenticateURL()<br />UltimateOAuth::getAuthorizeURL()

```php
<?php
$uo->getAuthenticateURL($force_login);
$uo->getAuthorizeURL($force_login);
```

#### 引数

- *__$force\_login__*  
  既にログイン済みのユーザーを再認証させるかどうか。  
  デフォルト値 **FALSE** 。
  
#### 返り値

- URL文字列。

#### 備考: *Authenticate* と *Authorize* の違いは？

|                | Authenticate  |  Authorize   |
| -------------: |:---------------:| :-----------:|
| 新規ユーザー       | Twitterに遷移 | Twitterに遷移 |
| ログイン済みユーザー   | Twitterに遷移、但しアプリケーション設定で<br /> **__Allow this application to be used to Sign in with Twitter__**, <br />にチェックを入れた場合は、設定したコールバックにすぐ遷移する  |  Twitterに遷移  |


2-2. UltimateOAuthMultiクラス詳細
--------------------------------------

複数のリクエストを **並列処理** で高速に行えます。


=========================================


### UltimateOAuthMulti::__construct()

```php
<?php
$uom = new UltimateOAuthMulti;
```


=========================================


### UltimateOAuthMulti::enqueue()

新しいジョブを追加。

```php
<?php
$uom->enqueue($uo, $method, $arg1, $arg2, ...);
```


=========================================


#### 引数

- *__$uo__*  
  **UltimateOAuth** オブジェクト。 **参照渡し** 。
  
- *__$method__*  
  例: `post`
  
- *__$arg1__*, *__$arg2__*, *__...__*  
  例: `'statuses/update', 'status=TestTweet'`

=========================================
  
  
### UltimateOAuthMulti::execute()

全てのジョブを実行。  
実行後ジョブはリセットされる。

```php
<?php
$uom->execute($wait_processes);
```

#### 引数

- *__$wait\_processes__*  
  **UltimateOAuth::OAuthRequest()**　の *__$wait\_response__* と同じ。  
  デフォルト値 **TRUE** 。
  
#### 返り値

- 実行結果の **配列** 。


=========================================


2-3. UltimateOAuthRotateクラス詳細
---------------------------------------

簡単に **API規制回避** を行えます。  
さらに下記のような、 非常に役に立つ **隠しエンドポイント** を利用できるようになります。

- `GET activity/about_me`  
  自分に関するアクティビティを取得。
- `GET activity/by_friends`  
  フレンドに関するアクティビティを取得。
- `GET statuses/:id/activity/summary`  
  指定されたツイートに関するアクティビティを取得。
- `GET conversation/show/:id`  
  指定されたツイートに含む会話を取得。

- `POST friendships/accept`  
  指定されたフォローリクエストを受理する。
- `POST friendships/deny`  
  指定されたフォローリクエストを拒否する。
- `POST friendships/accept_all`  
  全てのフォローリクエストを受理する。

=========================================
  
### UltimateOAuthMulti::__construct()

```php
<?php
$uor = new UltimateOAuthRotate;
```

=========================================

### UltimateOAuthMulti::register()

あなた自身がTwitterに登録したアプリケーションをここで登録。

```php
<?php
$uor->register($name, $consumer_key, $consumer_secret);
```

#### 引数

- *__$name__*  
  公式アプリの名前と重複しなければどんな名前でもOK。識別用に使われる。  
  例: `my_app_01`
- *__$consumer\_key__*
- *__$consumer\_secret__*

#### 返り値

- 結果を **TRUE か FALSE** で返します。


=========================================


### UltimateOAuthMulti::login()

登録されたアプリケーション全てでログインする。  
このメソッドは内部的に **UltimateOAuthMulti** クラスを利用している。

```php
<?php
$uor->login($username, $password, $return_array);
```

#### 引数
 
- *__$username__*  
  スクリーンネーム(screen_name)またはEメールアドレス。
  
- *__$password__*  
  パスワード。
  
- *__$return\_array__*  
  デフォルト値 **FALSE** 。
  以下の記述を読んでください。
  
#### 返り値

- `$return_array` がFALSEのとき、結果を **TRUE か FALSE** で返す。

- `$return_array` がTRUEのとき、 実行結果の **配列** を返す。


=========================================

### UltimateOAuthMulti::setCurrent()

**POST** リクエストで使われるアプリケーションを選択。  
GETリクエストは無関係。

```php
<?php
$uor->setCurrent($name);
```

#### 引数

- *__$name__*  
  例: `my_app_01`

#### 返り値

- 結果を **TRUE か FALSE** で返す。


=========================================


### UltimateOAuthMulti::getInstance($name)

指定されたUltimateOAuthインスタンスの **クローン** を取得する。

```php
<?php
$uor->getInstance($name);
```

#### 引数

- *__$name__*  
  例: `my_app_01`
  
#### 返り値

- **UltimateOAuth** インスタンスか **FALSE** を返す。

=========================================


### UltimateOAuthMulti::__call()

**UltimateOAuth** のメソッドをコールする。

例:
```php
<?php
$uor->get('statuses/home_timeline');
```