<?php

// 設定
define('CONSUMER_KEY','');
define('CONSUMER_SECRET','');

/*

https://dev.twitter.com/apps/new
にてアプリケーションキーを発行。

Name:
アプリケーション名。

Description:
簡単な説明。

WebSite:
クリックしたときに飛ぶURL。

Callback URL:
認証後に戻るURL。
このサンプルの場合は「callback.php」に対してのURL。
省略するとPIN入力方式になる。

一度アプリケーションキーを発行したあと、再度
https://dev.twitter.com/apps
から自分のアプリケーション詳細ページに飛び、
「Settings」で「Application Type」を「Read, Write and Access direct messages」に設定しておく。

consumer_key,consumer_secretは「Details」のページで確認できます。

*/
