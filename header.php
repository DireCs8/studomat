<!doctype html>
<html lang="sk">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css"
        integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <link rel="stylesheet" href="/studomat/style.css">
    <title>Studomat.sk</title>
</head>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$currentUserId = $_SESSION['student_id'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);

$user = null;
if ($currentUserId) {
    $stmt = $pdo->prepare("SELECT name, surname FROM student WHERE id = :id");
    $stmt->execute([':id' => $currentUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<body>
<div class="container">
    <?php if (!in_array($currentPage, ['login.php', 'register.php'])): ?>
        <div class="d-flex justify-content-between align-content-center mb-4">
            <h1><a href="/studomat/index.php" style="text-decoration:none;color:black;">STUDOMAT.SK</a></h1>
            <?php if ($user): ?>
                <div class="text-right">
                    <p>Prihlásený ako: <strong><?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?></strong></p>
                    <form method="POST" action="/studomat/logout.php"><input type="submit" value="Odhlásiť sa"></form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
