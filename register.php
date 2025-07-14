<?php include("header.php"); ?>
<?php
// Start session & load config
if (session_status() === PHP_SESSION_NONE)
    session_start();
require 'config.php';
require 'schools.php';

// Reset back to role-selection
if (isset($_GET['reset']) || isset($_POST['reset'])) {
    unset($_SESSION['last_step'], $_SESSION['tmp_profile_image']);
    $step = null;
}

// Figure out which step we’re on
$step = $_POST['profile_type']
    ?? $_POST['profile_type_final']
    ?? $_SESSION['last_step']
    ?? null;

$errors = [];
$successMessage = '';

// ────────────────────────────────────────────────────────────────
// Handle student registration (Maturant / Vysokoškolák / Absolvent)
// ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_register'])) {
    $_SESSION['last_step'] = $_POST['profile_type_final'];

    // (1) temporarily store uploaded JPG
    if (!empty($_FILES['profile_image']['tmp_name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpg') {
            $tmp = __DIR__ . "/profile-images/tmp_" . session_id() . ".jpg";
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $tmp);
            $_SESSION['tmp_profile_image'] = $tmp;
        } else {
            $errors[] = "❌ Profilový obrázok musí byť vo formáte .jpg";
        }
    }

    // (2) collect & validate
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['password_confirm'];
    $profileType = $_POST['profile_type_final'];
    $school = $_POST['school'] ?? null;
    $faculty = $_POST['faculty'] ?? null;
    $program = $_POST['program'] ?? null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "❌ Neplatná emailová adresa.";
    }
    if ($password !== $confirm) {
        $errors[] = "❌ Heslá sa nezhodujú.";
    }
    if (
        strlen($password) < 7
        || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[0-9]/', $password)
        || !preg_match('/[.,;_\-#@]/', $password)
    ) {
        $errors[] = "❌ Heslo musí mať aspoň 7 znakov, obsahovať 1 veľké písmeno, 1 číslicu a 1 špeciálny znak.";
    }
    // email uniqueness
    $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE email=:e");
    $chk->execute([':e' => $email]);
    if ($chk->fetchColumn() > 0) {
        $errors[] = "❌ Táto emailová adresa je už zaregistrovaná.";
    }

    // (3) if all good, generate a **unique** nickname and insert
    if (empty($errors)) {
        // base = first 3 letters of name + first 3 of surname, lowercase
        $base = mb_strtolower(substr($name, 0, 3) . substr($surname, 0, 3), 'UTF-8');
        $nick = $base;
        $i = 2;
        $chk = $pdo->prepare("SELECT COUNT(*) FROM student WHERE nickname=:n");
        $chk->execute([':n' => $nick]);
        while ($chk->fetchColumn() > 0) {
            $nick = $base . $i;
            $chk->execute([':n' => $nick]);
            $i++;
        }

        // hash pw & insert
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO student
                    (nickname,name,surname,email,password,registrated,profile_type,school,faculty,program)
                VALUES
                    (:nick,:n,:s,:e,:p,NOW(),:t,:sch,:fac,:pro)";
        $ins = $pdo->prepare($sql);
        $ins->execute([
            ':nick' => $nick,
            ':n' => $name,
            ':s' => $surname,
            ':e' => $email,
            ':p' => $hash,
            ':t' => $profileType,
            ':sch' => $school,
            ':fac' => $faculty,
            ':pro' => $program
        ]);
        $studentId = $pdo->lastInsertId();

        // move the temp image into final
        if (!empty($_SESSION['tmp_profile_image'])) {
            rename(
                $_SESSION['tmp_profile_image'],
                __DIR__ . "/profile-images/{$studentId}-profile-image.jpg"
            );
            unset($_SESSION['tmp_profile_image']);
        }

        $successMessage = "✅ Registrácia prebehla úspešne.";
        unset($_SESSION['last_step']);
    }
}

// ────────────────────────────────────────────────────────────────
// Handle učiteľ / recruiter “contact” form
// ────────────────────────────────────────────────────────────────
elseif (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['submit_teacher']) || isset($_POST['submit_recruiter']))
) {
    $_SESSION['last_step'] = $_POST['profile_type'];
    $role = $_POST['profile_type'];
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $org = trim($_POST['organization']);
    $email = trim($_POST['email']);
    $msg = trim($_POST['message']);

    $to = 'majko.dc@gmail.com';
    $subj = "[$role] Kontaktný formulár";
    $body = "Meno: $name\nPriezvisko: $surname\nOrganizácia: $org\nEmail: $email\n\nSpráva:\n$msg";
    $hdrs = "From: $email";
    mail($to, $subj, $body, $hdrs);
    $successMessage = "✅ Správa bola odoslaná.";
    unset($_SESSION['last_step']);
}
?>

<div class="container">

    <?php if ($successMessage && !isset($_SESSION['last_step'])): ?>
        <p class="text-success"><?= $successMessage ?></p>
        <p><a href="login.php">Teraz sa prosím prihlás</a></p>

    <?php else: ?>

        <!-- 1) Role selection -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$step): ?>
            <h2 class="mt-4">Som:</h2>
            <form method="POST" class="profile-selection">
                <div class="profile-options-container">
                    <?php
                    $opts = [
                        'Maturant' => ['icon' => '📚', 'desc' => 'Rád by som získal informácie o vysokých školách prípadne sa spýtal na niečo ostatných vysokoškolákov'],
                        'Vysokoškolák' => ['icon' => '👨‍🎓', 'desc' => 'Chcem sa pridať do komunity vysokoškolákov, navzájom si pomáhať, zdieľať svoje skúsenosti a ukázať recruiterom, že som šikovný'],
                        'Absolvent' => ['icon' => '🎓', 'desc' => 'Poskytnem rady pre ostatných vysokoškolákov, podelím sa o svoje skúsenosti príp. využijem možnosť sa ukázať recruiterom'],
                        'Učiteľ' => ['icon' => '🧑‍🏫', 'desc' => 'Pomôžem študentom, ktorí majú otázky ohľadom vysokých škôl, predmetov alebo iných tém'],
                        'Recruiter' => ['icon' => '🕵️‍♂️', 'desc' => 'Hľadám šikovných študentov a absolventov, ktorých by som mohol osloviť ohľadom zamestnania alebo stáže'],
                    ];
                    foreach ($opts as $val => $i): ?>
                        <button type="submit" name="profile_type" value="<?= $val ?>"
                            class="profile-option" >
                            <p class="profile-title"><?= $i['icon'] ?>             <?= $val ?></p>
                            <p><?= $i['desc'] ?></p>
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
            <p class="mt-3">Máš už účet? <a href="login.php">Prihlás sa</a></p>
        <?php endif; ?>


        <!-- 2) Teacher / Recruiter form -->
        <?php if ($step === 'Učiteľ' || $step === 'Recruiter'): ?>
            <h3><?= $step ?> – Kontaktný formulár</h3>
            <form method="POST">
                <input type="hidden" name="profile_type" value="<?= $step ?>">
                <div class="form-group">
                    <label>Meno</label>
                    <input class="form-control" type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Priezvisko</label>
                    <input class="form-control" type="text" name="surname" required>
                </div>
                <div class="form-group">
                    <label><?= $step === 'Učiteľ' ? 'Škola' : 'Firma' ?></label>
                    <input class="form-control" type="text" name="organization" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input class="form-control" type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Správa</label>
                    <textarea class="form-control" name="message" required></textarea>
                </div>
                <button type="submit" name="<?= strtolower($step) === 'učiteľ' ? 'submit_teacher' : 'submit_recruiter' ?>"
                    class="btn btn-primary">
                    Odoslať
                </button>
            </form>
            <p class="mt-3"><a href="?reset=1">⬅ Späť</a></p>
            <p>Máš už účet? <a href="login.php">Prihlás sa</a></p>


            <!-- 3) Student registration form -->
        <?php elseif (in_array($step, ['Maturant', 'Vysokoškolák', 'Absolvent'])): ?>
            <h3 class="mt-3">Registrácia – <?= $step ?></h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="profile_type_final" value="<?= $step ?>">

                <div class="form-group">
                    <label>Prezývka</label>
                    <input id="nickname" class="form-control" type="text" disabled>
                </div>
                <div class="form-group">
                    <label>Meno</label>
                    <input id="name" class="form-control" type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Priezvisko</label>
                    <input id="surname" class="form-control" type="text" name="surname" required>
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
                    <p><strong>Už nahratý obrázok:</strong></p>
                    <img src="profile-images/tmp_<?= session_id() ?>.jpg" style="max-width:120px"><br>
                    <p>Pre zmenu nahraj nový:</p>
                    <input type="file" name="profile_image" accept=".jpg"><br><br>
                <?php else: ?>
                    <div class="form-group">
                        <label>Profilový obrázok (.jpg)</label>
                        <input class="form-control-file" type="file" name="profile_image" accept=".jpg" required>
                    </div>
                <?php endif; ?>

                <?php if (in_array($step, ['Vysokoškolák', 'Absolvent'])): ?>
                    <div class="form-group">
                        <label>Škola</label>
                        <select id="school" class="form-control" name="school" required>
                            <option value="">– Vyber školu –</option>
                            <?php foreach (array_keys($schoolsAndFaculties) as $s): ?>
                                <option><?= htmlspecialchars($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fakulta</label>
                        <select id="faculty" class="form-control" name="faculty" disabled required>
                            <option>– Najprv vyber školu –</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Odbor</label>
                        <select id="program" class="form-control" name="program" disabled required>
                            <option>– Najprv vyber fakultu –</option>
                        </select>
                    </div>
                <?php endif; ?>

                <button type="submit" name="submit_register" class="btn btn-success">
                    Registrovať
                </button>
            </form>
            <p class="mt-3"><a href="?reset=1">⬅ Späť</a></p>
            <p>Máš už účet? <a href="login.php">Prihlás sa</a></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-3">
                <?php foreach ($errors as $e): ?>
                    <div><?= $e ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php include("footer.php"); ?>

<script>
    // client-side nickname generation
    document.querySelectorAll('#name, #surname').forEach(el => {
        el.addEventListener('input', () => {
            const n = document.getElementById('name').value.trim().toLowerCase();
            const s = document.getElementById('surname').value.trim().toLowerCase();
            document.getElementById('nickname').value =
                n.substr(0, 3) + s.substr(0, 3);
        });
    });

    // school ⇒ faculty ⇒ program chaining
    const data = <?= json_encode($schoolsAndFaculties, JSON_UNESCAPED_UNICODE) ?>;
    const selS = document.getElementById('school'),
        selF = document.getElementById('faculty'),
        selP = document.getElementById('program');
    if (selS) {
        selS.onchange = () => {
            const s = selS.value;
            selF.innerHTML = '<option>– Vyber fakultu –</option>';
            selP.innerHTML = '<option>– Najprv vyber fakultu –</option>';
            selP.disabled = true;
            if (!data[s]) return selF.disabled = true;
            Object.keys(data[s]).forEach(f => selF.add(new Option(f, f)));
            selF.disabled = false;
        };
        selF.onchange = () => {
            const s = selS.value, f = selF.value;
            selP.innerHTML = '<option>– Vyber odbor –</option>';
            if (!data[s] || !data[s][f]) return selP.disabled = true;
            data[s][f].forEach(p => selP.add(new Option(p, p)));
            selP.disabled = false;
        };
    }
</script>