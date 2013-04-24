# License
__CC0__ (No Rights Reserved)

***
***
***

# [UltimateOAuth]

TwitterAPIに特化した、非常に高機能なOAuthライブラリです。

+ API規制をなんとかしたい・・・
+ xAuth認証使いたいけどDM操作権限無いし第一許可下りない・・・
+ 今使ってるライブラリ画像アップロード対応してない・・・
+ レンタルサーバーなのでcURLとかPEARが使えない・・・
+ エラーコードを分かりやすく表示したい・・・

そんな人にオススメ。
初心者の方はまず [サンプル] をダウンロードして試用してみてください。

***

# 通常の認証ステップを経てテストツイート

[サンプル] 参照

***

# 基本メソッド仕様 (詳細はライブラリ内参照)

## UltimateOAuth

    $uo = new UltimateOAuth( $consumer_key, $consumer_secret, $access_token='', $access_token_secret='' );
    
    (stdClass|Array)      $uo->get                   ( $endpoint,                $params=array()                       );
    (stdClass|Array|NULL) $uo->post                  ( $endpoint,                $params=array(), $wait_response=false );
    (stdClass|Array|NULL) $uo->OAuthRequest          ( $endpoint, $method='GET', $params=array(), $wait_response=false );
    (stdClass|Array|NULL) $uo->OAuthRequestMultipart ( $endpoint,                $params=array(), $wait_response=false );
    
    (stdClass) $uo->BgOAuthGetToken ( $username, $password );
    
    (String) $uo->getAuthorizeURL    ( $force_login=false );
    (String) $uo->getAuthenticateURL ( $force_login=false );
    
    (String) $uo->save();
    (UltimateOAuth) UltimateOAuth::load($data);

## UltimateOAuthMulti

    $uom = new UltimateOAuthMulti;
    
    (NULL)  $uom->addjob ( &$uo, $method, $param0, $param1, $param2, ... );
    (Array) $uom->exec();

## UltimateOAuthRotate

    $uor = new UltimateOAuthRotate;
    
    (Bool)       $uor->register( $app_name, $app_consumer_key, $app_consumer_secret );
    (Bool|Array) $uor->login ( $username, $password, $return_bool=true, $parallel=true );
    (Bool)       $uor->setCurrent( $app_name );
    
    (stdClass|Array)      $uor->get                   ( $endpoint,                $params=array()                       );
    (stdClass|Array|NULL) $uor->post                  ( $endpoint,                $params=array(), $wait_response=false );
    (stdClass|Array|NULL) $uor->OAuthRequest          ( $endpoint, $method='GET', $params=array(), $wait_response=false );
    (stdClass|Array|NULL) $uor->OAuthRequestMultipart ( $endpoint,                $params=array(), $wait_response=false );
    
    (stdClass) $uor->BgOAuthGetToken ( $username, $password );


***

# このライブラリの特長を生かした使い方

## メディアつきリクエスト

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    // 「img」ディレクトリにある「test.png」を添付して「test」とツイート (どちらも同じ結果)
    $uo->OAuthRequestMultipart('statuses/update_with_media.json',array('status'=>'test','@media[]'=>'img/test.png'));
    $uo->OAuthRequestMultipart('statuses/update_with_media.json',array('status'=>'test','media[]'=>file_get_contents('img/test.png')));
    
    // 親ディレクトリにある「avatar.png」をプロフィール画像に設定 (全て同じ結果)
    // ※APIドキュメントに説明は無いがこちらはマルチパートでも可能
    $uo->post('account/update_profile_image.json',array('@image'=>'../avatar.png'));
    $uo->post('account/update_profile_image.json',array('image'=>base64_encode(file_get_contents('../avatar.png'))));
    $uo->OAuthRequestMultipart('account/update_profile_image.json',array('@image'=>'../avatar.png'));
    $uo->OAuthRequestMultipart('account/update_profile_image.json',array('image'=>file_get_contents('../avatar.png')));

※後述のUltimateOAuthMultiクラスを使う場合は、「@」指定におけるカレントディレクトリが __このファイル自身__ になることに注意。

## レスポンスを待たないリクエスト(いわゆる爆撃)
「Bomb!」「Bomb!!」「Bomb!!!」…とツイートを10回リクエスト。

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    for ($i=1;$i<=10;$i++)
      // $wait_responseがfalseのとき、エンドポイントを叩きに行ったらすぐ接続を切って次のリクエストをする
      $uo->post('statuses/update.json',array('status'=>'Bomb',str_repeat('!',$i)),false);

## バックグラウンドOAuth(疑似xAuth)で一発認証

    <?php
    
    // UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    // UltimateOAuthオブジェクト生成
    $uo = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット');
    
    // BgOAuthGetTokenメソッドをコール
    $res = $uo->BgOAuthGetToken('スクリーンネーム(又はメールアドレス)','パスワード');
    
    // レスポンスチェック
    if (isset($res->errors))
      echo "{$res->errors[0]->code}: {$res->errors[0]->message}";
    else
      echo 'Login successfully.';

## UltimateOAuthMultiクラスを使う
ここでは例として、複数のアカウントで同時にバックグラウンドOAuth認証を行う。  
特にこれは処理が重いメソッドなので、並列化することで高速化が大きく期待できる。

    <?php
    
    // UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    // UltimateOAuthオブジェクト生成
    $uo_1 = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット');
    $uo_2 = clone $uo_1; // $uo_1をコピー
    
    // UltimateOAuthMultiオブジェクト生成
    $uom = new UltimateOAuthMulti();
    
    // ジョブ追加(対象のオブジェクト、メソッド名、パラメータ(可変引数)を渡す)
    $uom->addjob($uo_1,'BgOAuthGetToken','スクリーンネーム1','パスワード1');
    $uom->addjob($uo_1,'BgOAuthGetToken','スクリーンネーム2','パスワード2');
    
    // ジョブ実行(値を返した後、$uomのジョブはリセットされる)
    $res = $uom->exec();
    
    // レスポンスチェック
    try {
      foreach ($res as $i => $r) {
        if (isset($r->errors))
          throw new Exception("On job[{$i}]; {$r->errors[0]->code}: {$r->errors[0]->message}");
      }
      echo 'All done.';
    } catch (Exception $e) {
      echo $e->getMessage();
    }

## UltimateOAuthRotateクラスを使う
自動的にGETリクエストに関してのAPI規制回避を行えるクラス。  
公式アプリのキーを含め、複数のキーを同時に管理して、  
適当なローテーションを行い、API規制を回避する。

    <?php
    
    // UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    // UltimateOAuthRotate生成
    $uor = new UltimateOAuthRotate();
    
    // あなたのアプリケーションキーを登録
    $uor->register('識別子(アプリケーション名)1','コンシューマーキー1','コンシューマーシークレット1');
    $uor->register('識別子(アプリケーション名)2','コンシューマーキー2','コンシューマーシークレット2');
    
    // マルチスレッドであなたのキーに加えて公式キー複数個を認証する
    $res = $uor->login('スクリーンネーム','パスワード');
    
    // レスポンスチェック(Falseなら1つ以上エラーが発生している)
    if (!$res)
      die('Login error. Check your username and password again.');
    
    // POSTに使うアプリケーション名を指定
    $uor->setCurrent('識別子(アプリケーション名)');
    
    // 指定したキーからツイート(未指定の場合ライブラリが適当に選択)
    $uor->post('statuses/update.json',array('status'=>'Test Tweet'));
    
    // 公式キーをローテーションしながらアクティビティを50回連続取得
    for($i=0;$i<50;$i++)
      var_dump($uor->get('activity/about_me.json'));

[UltimateOAuth]: https://github.com/Certainist/UltimateOAuth/blob/master/UltimateOAuth.php
[サンプル]: https://github.com/Certainist/UltimateOAuth/tree/master/sample