<?php include("header.php"); ?>
<?php
// Start session & load dependencies
if (session_status() === PHP_SESSION_NONE)
    session_start();
require 'config.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = $pdo->prepare("SELECT * FROM student WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['student_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['surname'] = $user['surname'];

        // Update last login
        date_default_timezone_set('Europe/Bratislava');
        $now = date('Y-m-d H:i:s');
        $upd = $pdo->prepare("UPDATE student SET logged_in = :now WHERE id = :id");
        $upd->execute([':now' => $now, ':id' => $user['id']]);

        header("Location: index.php");
        exit;
    } else {
        $errors[] = "❌ Nesprávna emailová adresa alebo heslo.";
    }
}
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Prihlásenie</h3>

                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?= htmlspecialchars($error) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="form-group position-relative">
                            <label for="password">Heslo</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small class="form-text text-muted">
                                Podržte kurzor nad políčkom pre zobrazenie hesla
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Prihlásiť sa
                        </button>
                    </form>

                    <p class="mt-3 text-center">
                        Nemáš účet? <a href="register.php">Zaregistrovať sa</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>

<script>
    // Show/hide password on hover
    const pwd = document.getElementById('password');
    pwd.addEventListener('mouseenter', () => {
        if (pwd.value.length > 0) pwd.type = 'text';
    });
    pwd.addEventListener('mouseleave', () => {
        pwd.type = 'password';
    });
</script>