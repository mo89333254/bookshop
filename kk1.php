<?php
$mysqli = new mysqli("localhost", "root", "root", "bookshop");

if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
  exit();
}

$s_sid = $_POST['sid'];
$s_addtype = $_POST['addtype'];
$s_userid = $_POST['userid'];
$s_ids = $_POST['arr'];
$booksaleid = $_POST['id'];
$s_none = 0;
if( isset( $_POST['none'] ) ) {
    $s_none = $_POST['none'];
}
$check = "select count from saleinfo where ID = $booksaleid";
$bookinfo = $mysqli->query($check);
   while($row=$bookinfo->fetch_row()){ 
   if($row[0] == 0)
   { 
	   
	   $code = ['count' => 0];	 
		    $bookinfo->close();
		 echo json_encode($code);
return;
	   
   }

        }

$query = "call test($s_sid,$s_userid,$s_addtype,'$s_ids');";

$mysqli->multi_query($query);
$kkk = array();
while($mysqli->more_results())
{
  if ($result = $mysqli->store_result()) {
    while ($row = $result->fetch_row()) {
      //printf("%s\n", $row[0]);
      array_push($kkk,$row[0]);
    }
    $result->close();
  }

  $mysqli->next_result();
}
$code = ['content' => number_format($kkk[1], 2, '.', ''), 'cart_number' => $kkk[0], 'none' => $s_none];

echo json_encode($code);
?>