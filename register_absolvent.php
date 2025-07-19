<?php 
include("header.php");
require 'schools.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // dočasné uložení nahraného JPG
    if (!empty($_FILES['profile_image']['tmp_name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpg') {
            $tmp = __DIR__ . "/profile-images/tmp_" . session_id() . ".jpg";
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $tmp);
            $_SESSION['tmp_profile_image'] = $tmp;
        } else {
            $errors[] = "Profilový obrázok musí byť vo formáte .jpg";
        }
    }
    $name     = trim($_POST['name']    ?? '');
    $surname  = trim($_POST['surname'] ?? '');
    $email    = trim($_POST['email']   ?? '');
    $password = $_POST['password']     ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $school   = $_POST['school']       ?? '';
    $faculty  = $_POST['faculty']      ?? '';
    $program  = $_POST['program']      ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Neplatná emailová adresa.";
    }
    if ($password !== $confirm) {
        $errors[] = "Heslá sa nezhodujú.";
    }
    if (strlen($password) < 7
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[.,;_\-#@]/', $password)
    ) {
        $errors[] = "Heslo musí mať aspoň 7 znakov, obsahovať veľké písmeno, číslicu a špeciálny znak.";
    }
    $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE email = :e");
    $chk->execute([':e' => $email]);
    if ($chk->fetchColumn() > 0) {
        $errors[] = "Emailová adresa už existuje.";
    }

    if (empty($errors)) {
        $base = mb_strtolower(substr($name, 0, 3) . substr($surname, 0, 3), 'UTF-8');
        $nick = $base;
        $i = 2;
        $chk2 = $pdo->prepare("SELECT COUNT(*) FROM student WHERE nickname = :n");
        $chk2->execute([':n' => $nick]);
        while ($chk2->fetchColumn() > 0) {
            $nick = $base . $i++;
            $chk2->execute([':n' => $nick]);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("
            INSERT INTO student
              (nickname,name,surname,email,password,registrated,profile_type,school,faculty,program)
            VALUES
              (:nick,:n,:s,:e,:p,NOW(),'Absolvent',:sch,:fac,:pro)
        ");
        $ins->execute([
            ':nick' => $nick,
            ':n'    => $name,
            ':s'    => $surname,
            ':e'    => $email,
            ':p'    => $hash,
            ':sch'  => $school,
            ':fac'  => $faculty,
            ':pro'  => $program,
        ]);
        $id = $pdo->lastInsertId();
        if (!empty($_SESSION['tmp_profile_image'])) {
            rename(
                $_SESSION['tmp_profile_image'],
                __DIR__ . "/profile-images/{$id}-profile-image.jpg"
            );
            unset($_SESSION['tmp_profile_image']);
        }
        $success = "Registrácia prebehla úspešne.";
    }
}
?>
<div class="container">
  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <p><a href="login.php">Teraz sa prosím prihlás</a></p>
  <?php else: ?>
    <h3>Registrácia – Absolvent</h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label>Prezývka</label>
        <input id="nickname" class="form-control" disabled>
      </div>
      <div class="form-group">
        <label>Meno</label>
        <input id="name" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Priezvisko</label>
        <input id="surname" name="surname" class="form-control" required value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input name="email" type="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Heslo</label>
        <input name="password" type="password" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Potvrdiť heslo</label>
        <input name="password_confirm" type="password" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Profilový obrázok (.jpg)</label>
        <input name="profile_image" type="file" accept=".jpg" class="form-control-file" <?= empty($_SESSION['tmp_profile_image']) ? 'required' : '' ?>>
      </div>
      <div class="form-group">
        <label>Škola</label>
        <select id="school" name="school" class="form-control" required>
          <option value="">-- Vyber školu --</option>
          <?php foreach (array_keys($schoolsAndFaculties) as $s): ?>
            <option <?= (($_POST['school'] ?? '') === $s) ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Fakulta</label>
        <select id="faculty" name="faculty" class="form-control" disabled required>
          <option>-- Najprv vyber školu --</option>
        </select>
      </div>
      <div class="form-group">
        <label>Odbor</label>
        <select id="program" name="program" class="form-control" disabled required>
          <option>-- Najprv vyber fakultu --</option>
        </select>
      </div>
      <button class="btn btn-success">Registrovať</button>
    </form>
    <?php if ($errors): ?>
      <div class="alert alert-danger mt-3">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p class="mt-3"><a href="register.php">⬅ Späť na výber</a></p>
  <?php endif; ?>
</div>
<?php include("footer.php"); ?>
<script>
  document.querySelectorAll('#name,#surname').forEach(el =>
    el.addEventListener('input', () => {
      const n = document.getElementById('name').value.toLowerCase().substr(0,3),
            s = document.getElementById('surname').value.toLowerCase().substr(0,3);
      document.getElementById('nickname').value = n + s;
    })
  );
  const data = <?= json_encode($schoolsAndFaculties, JSON_UNESCAPED_UNICODE) ?>;
  const selS = document.getElementById('school'),
        selF = document.getElementById('faculty'),
        selP = document.getElementById('program');
  selS.onchange = () => {
    const s = selS.value;
    selF.innerHTML = '<option>-- Vyber fakultu --</option>';
    selP.innerHTML = '<option>-- Najprv vyber fakultu --</option>';
    selP.disabled = true;
    if (!data[s]) return selF.disabled = true;
    Object.keys(data[s]).forEach(f => selF.add(new Option(f, f)));
    selF.disabled = false;
  };
  selF.onchange = () => {
    const s = selS.value, f = selF.value;
    selP.innerHTML = '<option>-- Vyber odbor --</option>';
    if (!data[s] || !data[s][f]) return selP.disabled = true;
    data[s][f].forEach(p => selP.add(new Option(p, p)));
    selP.disabled = false;
  };
</script>
