# License
__CC0__ (No Rights Reserved)

***
***
***

# [UltimateOAuth]

バージョン4.0で大幅に仕様変更しました。
詳しくはライブラリのコメント欄・以下の使用例をご覧ください。

初心者の方はまず [サンプル] をダウンロードして試用してみてください。

***

# 通常の認証ステップを経てテストツイート

以前ここにあったものは [サンプル] に移動しました。

***

# このライブラリの特長を生かした使い方

## 画像を添付してツイート
同一ディレクトリにあるtest.pngを添付してツイート。  
エラーチェックは省略。

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    // パラメータ「media」の頭に「@」をつけると値がファイルパスの扱いになる
    $uo->OAuthRequestMultipart('statuses/update_with_media.json',array('status'=>'test','@media[]'=>'test.png'));
 
## 高速非同期リクエスト(いわゆる爆撃)
「Bomb!」「Bomb!!」「Bomb!!!」…とツイートを10回リクエスト。

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    for ($i=1;$i<=10;$i++)
      // POSTに関しては、$wait_responseにfalseを渡すことで、非同期リクエストが可能
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
    $uor->register('識別子(アプリケーション名)','コンシューマーキー','コンシューマーシークレット');
    
    // マルチスレッドであなたのキーに加えて公式キー複数個を認証する
    $res = $uor->login('スクリーンネーム','パスワード',false,true);
    
    # $resがTrueの場合、この段階で認証完了
    
    /*
    この後は$uorは通常の$uoのように扱え、
    GETリクエスト毎に内部で公式キーのローテーションが行われ、
    自動的にAPI規制を回避できる。
    POSTリクエストに関しては、setCurrentメソッドで指定されたキーが使われる。
    未指定の場合はライブラリ側が自動で最適な選択を行う。
    */

[UltimateOAuth]: https://github.com/Certainist/UltimateOAuth/blob/master/UltimateOAuth.php
[サンプル]: https://github.com/Certainist/UltimateOAuth/tree/master/sample