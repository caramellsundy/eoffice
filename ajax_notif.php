<?php
include "config.php";

$result = mysqli_query($conn, "
    SELECT id, type, title, info, date_created AS date
    FROM notifikasi
    WHERE is_read = 0
    ORDER BY date_created DESC
");

$notif = [];
while($row = mysqli_fetch_assoc($result)){
    $notif[] = $row;
}

header('Content-Type: application/json');
echo json_encode($notif);
