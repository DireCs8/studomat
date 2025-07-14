<?php

    $host = 'localhost';
    $dbname = 'studomat';  
    $username = 'root';                 
    $password = '';                    


    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch (PDOException $e) {
        die("Nepodarilo sa pripojiť k databáze: " . $e->getMessage());
    }
?>
