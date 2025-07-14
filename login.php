<?php include("header.php") ?>

<?php
$errors = [];
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM student WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['student_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['surname'] = $user['surname'];


        date_default_timezone_set('Europe/Bratislava');
        $now = date('Y-m-d H:i:s');

        $updateStmt = $pdo->prepare("UPDATE student SET logged_in = :now WHERE id = :id");
        $updateStmt->execute([':now' => $now, ':id' => $user['id']]);

        header("Location: index.php");
        exit;
    } else {
        $errors[] = "❌ Nesprávna emailová adresa alebo heslo.";
    }
}
?>

<body>
    <div class="container">
        <h2>Prihlásenie</h2>

        <form method="POST" action="">
            <label>Email:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>

            <label>Heslo:</label><br>
            <input type="password" name="password" id="password" required><br><br>

            <?php if (!empty($errors)): ?>
                <div style="color: red;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p>Nemáš u nás účet? <a href="register" class="underline">Zaregistrovať sa</a></p>
            <input type="submit" value="Prihlásiť sa">
        </form>
    </div>
</body>

<?php include("footer.php") ?>

<script>
    function toggleEyeClass(field) {
        if ($(field).val().length > 0) {
            $(field).addClass("show-password");
        } else {
            $(field).removeClass("show-password");
        }
    }

    $(document).ready(function () {
        const fields = $("#password, #password_confirm");

        fields.on("input", function () {
            toggleEyeClass(this);
        });

        fields.hover(
            function () {
                if ($(this).val().length > 0) {
                    $(this).attr("type", "text");
                }
            },
            function () {
                $(this).attr("type", "password");
            }
        );
    });
</script>

