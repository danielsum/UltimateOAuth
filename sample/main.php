<?php

// ライブラリ読み込み
// (同梱されていないのでダウンロードしておいてください)
require_once('UltimateOAuth.php');
// 設定読み込み
require_once('config.php');
// セッションスタート
session_start();


// SESSIONが正しいかチェック
if (!isset($_SESSION['uo']))
	exit('Error: Session Timeout');

// 名前取得
$res = $_SESSION['uo']->get('account/verify_credentials.json');
if (!isset($res->errors)) {
	$title = sprintf('<h1>Hello! %s(@%s)!</h1>'.PHP_EOL,
		$res->name,
		$res->screen_name
	);
} else {
	// エラーメッセージ
	$title = "<h1>{$res->errors[0]->code}: {$res->errors[0]->message}</h1>".PHP_EOL;
}

// 変数初期化
$message = '';
$text = '';
$home = '';

// ツイート送信ボタンが押されたらツイート
if (isset($_POST['tweet_submit'],$_POST['tweet_text'])) {

	$text = $_POST['tweet_text'];
	
	// ツイートリクエスト
	$res = $_SESSION['uo']->post('statuses/update.json',array(
		'status' => $_POST['tweet_text'] // パラメータとして「status」を設定
	));
	
	// 結果を確認
	if (isset($res->errors)) {
	
		// 再表示のためにHTML特殊文字をエスケープ
		$text = htmlspecialchars($text,ENT_QUOTES);
		
		// エラーメッセージ
		$message = "<div>{$res->errors[0]->code}: {$res->errors[0]->message}</div>".PHP_EOL;
		
	} else {
	
		// テキストボックスをリセット
		$text = '';
		// ツイッターからのレスポンスはエスケープ済みなのでそのまま表示
		$message = "<div>Tweeted: {$res->text}</div>".PHP_EOL;
		
	}
}

// ホームタイムライン取得
$statuses = $_SESSION['uo']->get('statuses/home_timeline.json');

if (is_array($statuses)) {

	$htmls = array();
	
	// 1ツイートずつHTMLを整形していく
	foreach ($statuses as $i => $status) {
		
		$htmls[$i] = '<div>'.PHP_EOL;
		
		//公式RTかどうかチェック
		if (isset($status->retweeted_status)) {
		
			//$retweetに「公式RT」を代入
			$retweet = $status;
			
			//$statusに「公式RT先のオリジナルのツイート」を代入
			$status = $status->retweeted_status;
			
		} else {
		
			$retweet = null;
			
		}
		
		//東京にタイムゾーンを合わせてDateTimeオブジェクトを作る
		$date = new DateTime($status->created_at);
		$date->setTimezone(new DateTimeZone('Asia/Tokyo'));
		
		//アイコン
		$htmls[$i] .= sprintf('<img src="%s" class="prof" />'.PHP_EOL,
			$status->user->profile_image_url
		);
		
		//名前・スクリーンネーム
		$htmls[$i] .= sprintf('%s(@%s)<br />'.PHP_EOL,
			$status->user->name,
			$status->user->screen_name
		);
		
		//本文(改行を<br />に変換)
		$htmls[$i] .= nl2br($status->text).'<br />'.PHP_EOL;
		
		//日時表示
		$htmls[$i] .= $date->format('Y.n.j G:i:s').'<br />'.PHP_EOL;
		
		//「公式RT先のオリジナルのツイート」の場合は「公式RT」主の名前・スクリーンネームを表示
		if ($retweet!==null)
			$htmls[$i] .= sprintf('Retweeted by %s(@%s)<br />'.PHP_EOL,
				$retweet->user->name,
				$retweet->user->screen_name
			);
			
		$htmls[$i] .= '</div>';
		
	}
	
	// タイムラインが空でなければ1つに結合
	if ($htmls)
		$home = 
			'<div>'.PHP_EOL.
			implode(
				'<hr />'.PHP_EOL, // 区切り線
				$htmls
			).PHP_EOL.
			'</div>'.PHP_EOL
		;

} else {

	// エラーメッセージ
	$home = "<div>{$statuses->errors[0]->code}: {$statuses->errors[0]->message}</div>".PHP_EOL;

}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>認証テスト</title>
<style>
img.prof { vertical-align: middle; }
</style>
</head>
<body>
<?php echo $title; ?>
<?php echo $message; ?>
<form method="post" action="<?php echo basename(__FILE__); ?>">
<div>
今どうしてる？<br />
<textarea name="tweet_text"><?php echo $text; ?></textarea><br />
<input type="submit" name="tweet_submit" value="ツイート！" />
</div>
</form>
<h1>ホームタイムライン</h1>
<?php echo $home; ?>
</body>
</html>