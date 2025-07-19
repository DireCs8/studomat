<?php include("header.php");

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Upload obrázek
    if (!empty($_FILES['profile_image']['tmp_name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'jpg') {
            $errors[] = "❌ Profilový obrázok musí byť vo formáte .jpg";
        } else {
            $tmpPath = __DIR__ . "/profile-images/tmp_" . session_id() . ".jpg";
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $tmpPath);
            $_SESSION['tmp_profile_image'] = $tmpPath;
        }
    }

    // 2) Validace a sběr dat
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['password_confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "❌ Neplatná emailová adresa.";
    }
    if ($password !== $confirm) {
        $errors[] = "❌ Heslá sa nezhodujú.";
    }
    if (strlen($password) < 7
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[.,;_\-#@]/', $password)
    ) {
        $errors[] = "❌ Heslo musí mať aspoň 7 znakov, obsahovať 1 veľké písmeno, 1 číslicu a 1 špeciálny znak.";
    }
    $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE email=:e");
    $chk->execute([':e'=>$email]);
    if ($chk->fetchColumn()>0) {
        $errors[] = "❌ Email je už zaregistrovaný.";
    }

    // 3) Vytvoření unikátní přezdívky a zápis
    if (empty($errors)) {
        $base = mb_strtolower(substr($name,0,3).substr($surname,0,3),'UTF-8');
        $nick = $base; $i=2;
        $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE nickname=:n");
        $chk->execute([':n'=>$nick]);
        while ($chk->fetchColumn()>0) {
            $nick = $base.$i++;
            $chk->execute([':n'=>$nick]);
        }

        $hash = password_hash($password,PASSWORD_DEFAULT);
        $ins = $pdo->prepare("
            INSERT INTO student
              (nickname,name,surname,email,password,registrated,profile_type)
            VALUES
              (:nick,:n,:s,:e,:p,NOW(),'Maturant')
        ");
        $ins->execute([
            ':nick'=>$nick,
            ':n'=>$name,
            ':s'=>$surname,
            ':e'=>$email,
            ':p'=>$hash
        ]);
        $id = $pdo->lastInsertId();

        if (!empty($_SESSION['tmp_profile_image'])) {
            rename($_SESSION['tmp_profile_image'],
                   __DIR__."/profile-images/{$id}-profile-image.jpg");
            unset($_SESSION['tmp_profile_image']);
        }

        $successMessage = "✅ Registrácia prebehla úspešne.";
    }
}
?>

<div class="container">
  <?php if ($successMessage): ?>
    <div class="alert alert-success"><?= $successMessage ?></div>
    <p><a href="login.php">Teraz sa prosím prihlás</a></p>
  <?php else: ?>
    <h3>Maturant – Registrácia</h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Prezývka</label>
        <input id="nickname" class="form-control" disabled>
      </div>
      <div class="form-group">
        <label>Meno</label>
        <input id="name" class="form-control" name="name" required>
      </div>
      <div class="form-group">
        <label>Priezvisko</label>
        <input id="surname" class="form-control" name="surname" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input class="form-control" type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Heslo</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <div class="form-group">
        <label>Potvrdiť heslo</label>
        <input class="form-control" type="password" name="password_confirm" required>
      </div>
      <?php if (!empty($_SESSION['tmp_profile_image'])): ?>
        <p>Už nahratý obrázok:</p>
        <img src="profile-images/tmp_<?= session_id() ?>.jpg" style="max-width:120px"><br>
        <p>Pre zmenu nahraj nový:</p>
      <?php endif; ?>
      <div class="form-group">
        <label>Profilový obrázok (.jpg)</label>
        <input class="form-control-file" type="file" name="profile_image" accept=".jpg" <?= empty($_SESSION['tmp_profile_image'])?'required':'' ?>>
      </div>
      <button class="btn btn-success" type="submit">Registrovať</button>
    </form>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger mt-3">
        <?php foreach($errors as $e): ?><div><?=$e?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="mt-3"><a href="register.php?reset=1">⬅ Späť</a> | Máš účet? <a href="login.php">Prihlás sa</a></p>
  <?php endif; ?>
</div>

<?php include("footer.php"); ?>

<script>
  document.querySelectorAll('#name,#surname').forEach(el=>el.oninput=()=>{
    const n=document.getElementById('name').value.toLowerCase().substr(0,3),
          s=document.getElementById('surname').value.toLowerCase().substr(0,3);
    document.getElementById('nickname').value=n+s;
  });
</script>
