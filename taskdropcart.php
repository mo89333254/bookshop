<?php
$mysqli = new mysqli("localhost", "root", "root", "bookshop");

if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
  exit();
}





$query = "call dropshopcart();";

$mysqli->multi_query($query);



?>