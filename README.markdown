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
    (Bool|Array) $uor->login ( $username, $password );
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
    
    // 親ディレクトリにある「avatar.png」をプロフィール画像に設定 (どちらも同じ結果)
    $uo->post('account/update_profile_image.json',array('@image'=>'../avatar.png'));
    $uo->post('account/update_profile_image.json',array('image'=>base64_encode(file_get_contents('../avatar.png'))));

※後述の非同期リクエストを行う場合は、「@」指定におけるカレントディレクトリが __このファイル自身__ になることに注意。

## 高速非同期リクエスト(いわゆる爆撃)
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
    $uo = new UltimateOAuth($consumer_key,$consumer_secret);
    
    // BgOAuthGetTokenメソッドをコール
    $res = $uo->BgOAuthGetToken('スクリーンネーム(又はメールアドレス)','パスワード');
    
    // エラーチェック
    if (isset($res->errors))
      echo("{$res->errors[0]->code}: {$res->errors[0]->message}<br />\n");
    
    # この段階で認証完了

## UltimateOAuthMultiクラスを使う
ここでは例として、複数のアカウントで同時にバックグラウンドOAuth認証を行う。  
特にこれは処理が重いメソッドなので、並列化することで高速化が大きく期待できる。

    <?php
    
    // UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    // UltimateOAuthオブジェクト生成
    $uo_1 = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット');
    $uo_2 = clone $uo_1; //$uo_1をコピー
    
    // UltimateOAuthMultiオブジェクト生成
    $uom = new UltimateOAuthMulti();
    
    // ジョブ追加(対象のオブジェクト、メソッド名、パラメータ(可変引数)を渡す)
    $uom->addjob($uo_1,'BgOAuthGetToken','スクリーンネーム1','パスワード1');
    $uom->addjob($uo_1,'BgOAuthGetToken','スクリーンネーム2','パスワード2');
    
    // ジョブ実行
    $uom->exec();
    
    # この段階で認証完了
    
    /*
    execメソッドの返り値はそれぞれのレスポンスの配列になっているので、
    そこからエラー処理することもまた可能。
    */

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
    $uor->register('識別子(アプリケーション名1)','コンシューマーキー1','コンシューマーシークレット1');
    $uor->register('識別子(アプリケーション名2)','コンシューマーキー2','コンシューマーシークレット2');
    
    // マルチスレッドであなたのキーに加えて公式キー複数個を認証する
    $res = $uor->login('スクリーンネーム','パスワード');
    
    # $resがTrueの場合全てのキーの認証の成功を意味し、この段階で認証完了
    
    /*
    この後は$uorは通常の$uoのように扱え、
    GETリクエスト毎に内部で公式キーのローテーションが行われ、
    自動的にAPI規制を回避できる。
    POSTリクエストに関しては、setCurrentメソッドで指定されたキーが使われる。
    未指定の場合はライブラリ側が適当に選択する。
    */

[UltimateOAuth]: https://github.com/Certainist/UltimateOAuth/blob/master/UltimateOAuth.php
[サンプル]: https://github.com/Certainist/UltimateOAuth/tree/master/sample