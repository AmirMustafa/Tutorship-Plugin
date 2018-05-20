<?php
// This page handels AJAX request of reserve and unreserve requests
session_start();
if(isset($_POST['reserveLink'])) {
    echo $_POST['reserveLink'];
}
?>
