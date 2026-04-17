<?php

include 'Admin/header.php';

if(isset($_GET["layout"])){
    $file = $_GET["layout"] . ".php";

    if(file_exists("Customer/" . $file)){
        include "Customer/" . $file;
    }
    elseif(file_exists("Admin/" . $file)){
        include "Admin/" . $file;
    }
    else{
        echo "<h3>Trang không tồn tại!!!</h3>";
    }
}
else{
    include 'Customer/mainpage.php';
}

include 'Admin/footer.php';