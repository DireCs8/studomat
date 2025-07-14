<?php include("header.php") ?>

<?php

$errors = [];
$successMessage = '';
$name = '';
$surname = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require 'config.php';

    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $passwordConfirm = $_POST['password_confirm'];
    date_default_timezone_set('Europe/Bratislava');
    $registrated = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $emailExists = $stmt->fetchColumn();

    if ($emailExists) {
        $errors[] = "❌ Táto emailová adresa už je zaregistrovaná.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "❌ Zadaná emailová adresa nie je platná!";
    }

    if ($password !== $passwordConfirm) {
        $errors[] = "❌ Heslá sa nezhodujú.";
    }

    if (
        strlen($password) < 7 ||
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[.,;_\-#@]/', $password)
    ) {
        $errors[] = "❌ Heslo musí mať aspoň 7 znakov, obsahovať 1 veľké písmeno, 1 číslicu a 1 špeciálny znak.";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
            INSERT INTO student (name, surname, email, password, registrated)
            VALUES (:name, :surname, :email, :password, :registrated)
        ");

            $stmt->execute([
                ':name' => $name,
                ':surname' => $surname,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':registrated' => $registrated
            ]);

            $successMessage = "✅ Registrácia prebehla úspešne!";
        } catch (PDOException $e) {
            $errors[] = "❌ Chyba pri registrácii: " . $e->getMessage();
        }
    }
}
?>

<body>
    <div class="container">
        <h2>Registrácia</h2>

        <form method="POST" action="">

            <label>Meno:</label><br>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required><br><br>

            <label>Priezvisko:</label><br>
            <input type="text" name="surname" value="<?php echo htmlspecialchars($surname); ?>" required><br><br>

            <label>Heslo:</label><br>
            <input type="password" name="password" id="password" required><br><br>

            <label>Potvrdenie hesla:</label><br>
            <input type="password" name="password_confirm" id="password_confirm" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required><br><br>


            <?php if (!empty($errors)): ?>
                <div style="color: red;">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($successMessage)): ?>
                <p style="color: green;"><?php echo $successMessage; ?></p>
            <?php endif; ?>


            <p>Už máš u nás účet? <a href="login" class="underline">Prihlásiť sa</a></p>
            <input type="submit" value="Registrovať">
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