<?php
include "config.php";

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE notifikasi SET is_read=1 WHERE id=$id");
}
