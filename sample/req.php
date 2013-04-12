<?php

// ライブラリ読み込み
// (同梱されていないのでダウンロードしておいてください)
require_once('UltimateOAuth.php');
// 設定読み込み
require_once('config.php');
// セッションスタート
session_start();


// セッションにUltimateOAuthオブジェクト作成
$_SESSION['uo'] = new UltimateOAuth(CONSUMER_KEY,CONSUMER_SECRET);

// リクエストトークン取得
$res = $_SESSION['uo']->post('oauth/request_token');

// エラーチェック
if (isset($res->errors))
	exit("{$res->errors[0]->code}: {$res->errors[0]->message}");
	
// ページ遷移
header('Location: '.$_SESSION['uo']->getAuthorizeURL());