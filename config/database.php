<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_inventory"; // Nanti sesuaikan dengan nama database di phpMyAdmin kamu

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>