<?php
echo "<script src='http://res.wx.qq.com/open/js/jweixin-1.0.0.js'></script>";
echo "<script src='/js/jquery.min.js'></script>";
$noncestr = 'fefadsadaewe';
$timestamp = '1487251584';
$jsapi_ticket = 'kgt8ON7yVITDhtdwci0qebxdFmgiRdeowI9XfHHkvO44ipK1nPPcQMNiJJBXqlwkHlhTa5vwuLyqJfnhILEmlQ';
$url = 'http://123.184.16.57/demo.php';
$encode = $jsapi_ticket.$noncestr.$timestamp.$url;
 echo sha1($encode);
?>