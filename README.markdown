# License
__CC0__ (No Rights Reserved)

***
***
***

# [UltimateOAuth]

初心者向けにサンプルとか。  
わかる人はUltimateOAuthのコメントだけ読んどけば大丈夫なはず。

***

# 通常の認証ステップを経てテストツイート

### index.html
ユーザーにクリックさせるリンクがあるページ。

    <a href="req.php">認証</a>


### req.php
リクエストトークン取得。

    <?php
    
    //session_start()の前に必ずUltimateOAuth.phpを読み込む
    require_once('UltimateOAuth.php');
    session_start();
    
    //セッションにUltimateOAuthオブジェクトをセット
    $_SESSION['uo'] = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット');
    
    //リクエストトークン取得
    $res = $_SESSION['uo']->POST_oauth_request_token();
    //エラーチェック
    if (isset($res->errors))
      exit("{$res->errors[0]->code}: {$res->errors[0]->message}\n");
      
    //Twitterに飛ばす
    header('Location: '.$_SESSION['uo']->getAuthorizeURL()->url);
    exit();

### callback.php
アクセストークン取得。  
アプリ設定でこれにコールバックするように設定しておく。

    <?php
    
    //session_start()の前に必ずUltimateOAuth.phpを読み込む
    require_once('UltimateOAuth.php');
    session_start();
    
    //セッションチェック
    if (!isset($_SESSION['uo']))
      exit("認証済みのセッションが存在しません\n");
    
    //oauth_verifierを設定
    if (isset($_GET['oauth_verifier'])) {
      $res = $_SESSION['uo']->POST_oauth_access_token(array('oauth_verifier'=>$_GET['oauth_verifier']));
      //エラーチェック
      if (isset($res->errors))
        exit("{$res->errors[0]->code}: {$res->errors[0]->message}\n");
    }

    # この段階で認証完了

    //テストツイートするURLに飛ばす
    header('Location: http://～～～.com/tweet.php');
    exit();

### tweet.php
「Test」とツイートしてみる。

    <?php
    
    //session_start()の前に必ずUltimateOAuth.phpを読み込む
    require_once('UltimateOAuth.php');
    session_start();
    
    //セッションチェック
    if (!isset($_SESSION['uo']))
      exit("認証済みのセッションが存在しません\n");
    
    //「Test」とツイート
    $res = $_SESSION['uo']->POST_statuses_update(array('status'=>'Test'));
    //エラーチェック
    if (isset($res->errors))
      echo("{$res->errors[0]->code}: {$res->errors[0]->message}<br />\n");
    else
      echo "ツイート成功しました： {$res->text}<br />\n";

### (備考)
ここではセッションにUltimateOAuthオブジェクトを保存したが、

    $text = $uo->save();

で復元可能な形式でテキストとして出力、

    $uo = UltimateOAuth::load($text);

で復元済みのUltimateOAuthオブジェクトを受け取れる。  
ログイン済みのデータを恒久的に保存したい場合に有用。  
また、アクセストークンからオブジェクトを生成したい場合は、  

    $uo = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット','アクセストークン','アクセストークンシークレット');

とすればOK。

***

# サンプルいろいろ

### ホームタイムラインを取得して表示

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    //タイムライン取得
    $res = $uo->GET_statuses_home_timeline();
    
    //エラーチェック
    /*
    このエンドポイントの正常時の返り値は配列なので、
      empty($res->errors)
    ではなく
      is_array($res)
    を使う必要がある。(配列に対してアロー演算を行うと、その時点でエラーが発生するため)
    */
    if (is_array($res)) {
    
      # 正常に取得できたとき
    
      foreach ($res as $tweet) {
        
        //公式RTかどうかチェック
        if (isset($tweet->retweeted_status)) {
          //$retweetに「公式RT」を代入
          $retweet = $tweet;
          //$tweetに「公式RT先のオリジナルのツイート」を代入
          $tweet = $tweet->retweeted_status;
        } else {
          $retweet = null;
        }
        
        //東京にタイムゾーンを合わせてDateTimeオブジェクトを作る
        $date = new DateTime($tweet->created_at);
        $date->setTimezone(new DateTimeZone('Asia/Tokyo'));
        
        //アイコン表示
        echo "<img src=\"{$tweet->user->profile_image_url}\">\n";
        //名前・スクリーンネーム表示
        echo "{$tweet->user->name}(@{$tweet->user->screen_name})<br />\n";
        //本文表示(改行を<br />に変換)
        echo nl2br($tweet->text)."<br />\n";
        //日時表示
        echo $date->format('Y.n.j G:i:s')."<br />\n";
        //「公式RT先のオリジナルのツイート」の場合は「公式RT」主の名前・スクリーンネームを表示
        if ($retweet!==null)
          echo "Retweeted by {$retweet->user->name}(@{$retweet->user->screen_name})<br />\n";
        //区切り線を表示
        echo "<hr />\n";
      
      }
      
    } else {
      
      # 取得失敗したとき
      
      echo "{$res->errors[0]->code}: {$res->errors[0]->message}<br />\n";
      
    }

### 画像を添付してツイート
同一ディレクトリにあるtest.pngを添付してツイート。  
エラーチェックは省略。

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    //パラメータ「media」の頭に「@」をつけると値がファイルパスの扱いになる
    $uo->POST_statuses_update_with_media(array('status'=>'test','@media[]'=>'test.png'));
 
### 高速非同期リクエスト(いわゆる爆撃)
「Bomb!」「Bomb!!」「Bomb!!!」…とツイートを10回リクエスト。

    <?php
    
    # $uoに認証済みのUltimateOAuthオブジェクトがセットされた状態で
    
    for ($i=1;$i<=10;$i++)
      //(トークン取得系以外の)「POST_」で始まるメソッドに関しては、第2引数にfalseを指定すると非同期リクエストになる
      $uo->POST_statuses_update(array('status'=>'Bomb',str_repeat('!',$i)),false);

***

# バックグラウンドOAuth(疑似xAuth)で一発認証

    <?php
    
    //UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    //UltimateOAuthオブジェクト生成
    $uo = new UltimateOAuth($consumer_key,$consumer_secret);
    
    //BgOAuthGetTokenメソッドをコール
    $res = $uo->BgOAuthGetToken(array('username'=>'スクリーンネーム(又はメールアドレス)','password'=>'パスワード'));
    
    //エラーチェック
    if (isset($res->errors))
      echo("{$res->errors[0]->code}: {$res->errors[0]->message}<br />\n");
    
    # この段階で認証完了

***

# UltimateOAuthMultiクラスを使う
ここでは例として、複数のアカウントで同時にバックグラウンドOAuth認証を行う。  
特にこれは処理が重いメソッドなので、並列化することで高速化が大きく期待できる。

    <?php
    
    //UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    //UltimateOAuthオブジェクト生成
    $uo_1 = new UltimateOAuth('コンシューマーキー','コンシューマーシークレット');
    $uo_2 = clone $uo_1; //$uo_1をコピー
    
    //UltimateOAuthMultiオブジェクト生成
    $uom = new UltimateOAuthMulti();
    
    //ジョブ追加(対象のオブジェクト、メソッド名、パラメータを渡す)
    $uom->addjob($uo_1,'BgOAuthGetToken',array('スクリーンネーム1','パスワード1'));
    $uom->addjob($uo_2,'BgOAuthGetToken',array('スクリーンネーム2','パスワード2'));
    
    //ジョブ実行
    $uom->exec();
    
    # この段階で認証完了
    
    /*
    execメソッドの返り値はそれぞれのレスポンスの配列になっているので、
    そこからエラー処理することもまた可能。
    */

***

# UltimateOAuthRotateクラスを使う
自動的にGETリクエストに関してのAPI規制回避を行えるクラスで、  
UltimateOAuthオブジェクトとほぼ同様に扱える。

    <?php
    
    //UltimateOAuth.php読み込み
    require_once('UltimateOAuth.php');
    
    //UltimateOAuthRotate生成
    $uor = new UltimateOAuthRotate();
    
    /*
    デフォルトのコンシューマーキーに加え、複数の公式クライアントのキーも含めて、バックグラウンドOAuth認証をする。
    可能な場合は並列処理で実行するが、第5引数にfalseを指定すると強制的に逐次処理にさせる。
    */
    $res = $uor->register('コンシューマーキー','コンシューマーシークレット','スクリーンネーム','パスワード');
    
    # $resがTrueの場合、この段階で認証完了
    
    /*
    この後は$uorは通常の$uoのように扱え、GETリクエスト毎に内部でキーローテーションが行われ、
    自動的にAPI規制を回避できる。
    POSTリクエストに関してはデフォルトのコンシューマーキーが常に使われる。
    */

### (備考)

UltimateOAuthクラスと同様に

    $text = $uor->save();

で復元可能な形式でテキストとして出力、

    $uor = UltimateOAuthRotate::load($text);

で復元済みのUltimateOAuthRotateオブジェクトを受け取れる。

[UltimateOAuth]: https://github.com/Certainist/UltimateOAuth/blob/master/UltimateOAuth.php