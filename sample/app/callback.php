<?php

// ライブラリ読み込み
// (同梱されていないのでダウンロードしておいてください)
require_once('UltimateOAuth.php');
// 設定読み込み
require_once('config.php');
// セッションスタート
session_start();


// GET,SESSIONが正しいかチェック
if (!isset($_SESSION['uo']))
	exit('Error: Session Timeout');
if (!isset($_GET['oauth_token'],$_GET['oauth_verifier']))
	exit('Error: GET parameters are not enough');
if ($_GET['oauth_token']!==$_SESSION['uo']->request_token())
	exit('Error: Invalid oauth_token');

// アクセストークン取得
$res = $_SESSION['uo']->post('oauth/access_token',array('oauth_verifier'=>$_GET['oauth_verifier']));

// エラーチェック
if (isset($res->errors))
	exit("{$res->errors[0]->code}: {$res->errors[0]->message}");
	
// ページ遷移
header('Location: main.php');