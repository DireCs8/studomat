<?php
include "header.php";
require "schools.php";

$errors = [];
$success = "";

// ── Vygenerovanie CAPTCHA na GET ──────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $a = rand(1, 10);
    $b = rand(1, 10);
    $_SESSION["captcha_answer"] = $a + $b;
    $_SESSION["captcha_question"] = "Koľko je $a + $b?";
}

$name_display = $_POST['name_display'] ?? 'name_yes';
$work_interest = $_POST['work_interest'] ?? 'work_no';

// ── Spracovanie POST ─────F─────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1) Načítanie a otrimovanie
    $name = trim($_POST["name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["password_confirm"] ?? "";
    $birth_date = $_POST["birth_date"] ?? "";
    $school = $_POST["school"] ?? "";
    $faculty = $_POST["faculty"] ?? "";
    $program = $_POST["program"] ?? "";
    $start_date = $_POST["start_date"] ?? "";
    $captcha_input = intval($_POST["captcha"] ?? 0);
    $showName = ($_POST['name_display'] ?? '') === 'name_yes' ? 1 : 0;
    $invalid = [];


    $uni_domains = [
        'uniba.sk',
        'stuba.sk',
        'euba.sk',
        'vsvu.sk',
        'vsmu.sk',
        'aubi.sk',
        'umb.sk',
        'tuke.sk',
        'upjs.sk',
        'uvlf.sk',
        'ukf.sk',
        'uniag.sk',
        'unipo.sk',
        'ku.sk',
        'uniza.sk',
        'akademiapolicajna.edu.sk',
        'szu.sk',
    ];


    // 2) Validácie
    if ($password !== $confirm) {
        $errors[] = "❌ Heslá sa nezhodujú.";
        $invalid['password_confirm'] = true;
    }
    if (empty($name)) {
        $errors[] = "❌ Meno je povinné pole.";
        $invalid['name'] = true;
    }
    if (empty($surname)) {
        $errors[] = "❌ Priezvisko je povinné pole.";
        $invalid['surname'] = true;
    }
    if (empty($birth_date)) {
        $errors[] = "❌ Rok narodenia je povinné pole.";
        $invalid['birth_date'] = true;
    }
    if (
        strlen($password) < 7 ||
        !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[0-9]/", $password) ||
        !preg_match("/[.,;_\-#@]/", $password)
    ) {
        $errors[] =
            "❌ Heslo musí mať aspoň 7 znakov, veľké písmeno, číslicu a špeciálny znak.";
        $invalid['password'] = true;
    }
    if (
        !isset($_POST["captcha"]) ||
        $captcha_input !== ($_SESSION["captcha_answer"] ?? -1)
    ) {
        $errors[] = "❌ Nesprávna odpoveď na CAPTCHA kontrolný matematický príklad.";
        $invalid['captcha'] = true;
    }
    // birth_date nesmie byť v budúcnosti, a ≥18 rokov
    if ($birth_date) {
        $ts = strtotime($birth_date);
        if ($ts > time()) {
            $errors[] = "❌ Rok narodenia nemôže byť v budúcnosti.";
            $invalid['birth_date'] = true;
        } else {
            $age = (time() - $ts) / (365.25 * 24 * 3600);
            if ($age < 18) {
                $errors[] = "❌ Musíte mať aspoň 18 rokov.";
                $invalid['birth_date'] = true;
            }
        }
    } else {
        $errors[] = "❌ Rok narodenia je povinné pole.";
        $invalid['birth_date'] = true;
    }
    // start_date nesmie byť v budúcnosti
    if (!$start_date || strtotime($start_date) > time()) {
        $errors[] = "❌ Začiatok štúdia nemôže byť v budúcnosti.";
        $invalid['start_date'] = true;
    }
    // povinné selecty
    foreach (
        ["school" => "Škola", "faculty" => "Fakulta", "program" => "Odbor"]
        as $k => $label
    ) {
        if (empty($_POST[$k])) {
            $errors[] = "❌ $label je povinné pole.";
            $invalid[$k] = true;
        }
    }
    // jedinečný email
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student WHERE email = :e");
    $stmt->execute([":e" => $email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "❌ Emailová adresa už existuje.";
        $invalid['email'] = true;
    }

    // extrahujeme doménu z e‑mailu
    $domain = strtolower(substr(strrchr($email, '@'), 1));

    $valid_uni_email = false;
    foreach ($uni_domains as $ud) {
        // umožní skontrolovať aj prípady ako "student.uniba.sk"
        if (substr($domain, -strlen($ud)) === $ud) {
            $valid_uni_email = true;
            break;
        }
    }

    if (!$valid_uni_email) {
        $errors[] = "❌ Použite prosím študentský e‑mail vašej univerzity.";
        $invalid['email'] = true;
    }

    // po extrahovaní ostatných POST premenných
    $languages = json_decode($_POST['languages_json'] ?? '[]', true) ?: [];
    $skills = json_decode($_POST['skills_json'] ?? '[]', true) ?: [];
    $interests = json_decode($_POST['interests_json'] ?? '[]', true) ?: [];

    // ak používateľ označil záujem o prácu, musí mať aspoň jeden z týchto
    if (($_POST['work_interest'] ?? '') === 'work_yes') {
        if (count($languages) === 0) {
            $errors[] = "❌ Pridajte prosím aspoň jeden jazyk.";
            $invalid['language-input'] = true;
        }
        if (count($skills) === 0) {
            $errors[] = "❌ Pridajte prosím aspoň jednu zručnosť.";
            $invalid['skills-input'] = true;
        }
        if (count($interests) === 0) {
            $errors[] = "❌ Pridajte prosím aspoň jeden koníček.";
            $invalid['interests-input'] = true;
        }
        if (empty($_POST['job_preference'])) {
            $errors[] = "❌ Typ úväzku je povinné pole.";
            $invalid['job_preference'] = true;
        }
        if (empty($_POST['phone'])) {
            $errors[] = "❌ Telefónne číslo je povinné pole.";
            $invalid['phone'] = true;
        }
        $phone = trim($_POST['phone'] ?? '');
        $digits = preg_replace('/\D+/', '', $phone);
        if ($phone === '' || strlen($digits) !== 12) {
            $errors[] = "❌ Telefónne číslo musí obsahovať presne 12 číslic.";
            $invalid['phone'] = true;
        }
        if (empty($_POST['work_type'])) {
            $errors[] = "❌ Preferovaný typ práce je povinné pole.";
            $invalid['work_type'] = true;
        }
        if (empty($_POST['work_location'])) {
            $errors[] = "❌ Preferované miesto práce je povinné pole.";
            $invalid['work_location'] = true;
        }
    }


    // 3) Vloženie do DB, ak žiadne chyby
    if (empty($errors)) {
        // generovanie unikátnej prezývky
        $base = mb_strtolower(
            substr($name, 0, 3) . substr($surname, 0, 3),
            "UTF-8"
        );
        $nick = $base;
        $i = 2;
        $chk = $pdo->prepare(
            "SELECT COUNT(*) FROM student WHERE nickname = :n"
        );
        $chk->execute([":n" => $nick]);
        while ($chk->fetchColumn() > 0) {
            $nick = $base . $i++;
            $chk->execute([":n" => $nick]);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $ins = $pdo->prepare("
        INSERT INTO student
            (nickname, name, surname, email, password, registrated, profile_type,
            birth_date, school, faculty, program, start_date, about, degree,
            work_interest, job_preference, phone, work_type, work_location,
            linkedin, github, languages, skills, interests,
            show_name)
        VALUES
            (:nick, :n, :s, :e, :p, NOW(), 'Vysokoškolák',
            :bd,  :sch, :fac, :pro, :sd,        :abt,  :deg,
            :wi,  :jp,  :ph,   :wt,   :wl,
            :li,   :gh,
            :langs, :skills, :ints,
            :sn)
        ");

        $ins->execute([
            ':nick' => $nick,
            ':n' => $name,
            ':s' => $surname,
            ':e' => $email,
            ':p' => $hash,
            ':bd' => $birth_date,
            ':sch' => $school,
            ':fac' => $faculty,
            ':pro' => $program,
            ':sd' => $start_date,
            ':abt' => $_POST['about'] ?? null,
            ':deg' => $_POST['degree'] ?? null,
            ':wi' => $_POST['work_interest'] ?? 'work_no',
            ':jp' => $_POST['job_preference'] ?? null,
            ':ph' => $_POST['phone'] ?? null,
            ':wt' => $_POST['work_type'] ?? null,
            ':wl' => $_POST['work_location'] ?? null,
            ':li' => $_POST['linkedin'] ?? null,
            ':gh' => $_POST['github'] ?? null,
            ':langs' => $_POST['languages_json'] ?? '[]',
            ':skills' => $_POST['skills_json'] ?? '[]',
            ':ints' => $_POST['interests_json'] ?? '[]',
            ':sn' => $showName,
        ]);


        $success = "✅ Registrácia prebehla úspešne.";
    }
}
?>

<div class="container">
    <h2>Registrácia – Vysokoškolák</h2>
    <p>Vyplň svoje údaje. Čím viac údajov vyplníš, tým kvalitnejší tvôj profil bude.</p>
    <?php if ($errors): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data" novalidate>
        <h3 class="mt-4">Základné údaje:</h3>
        <div class="form-border-box p-4">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Meno*</label>
                        <input id="name" name="name"
                            class="form-control <?= isset($invalid['name']) ? 'is-invalid' : '' ?>" autocomplete="name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>Priezvisko*</label>
                        <input id="surname" name="surname"
                            class="form-control <?= isset($invalid['surname']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="name_display" id="name_yes" value="name_yes"
                        <?= $name_display === 'name_yes' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="name_yes">
                        Nevadí mi, keď bude na platforme vidieť moje celé meno
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="name_display" id="name_no" value="name_no"
                        <?= $name_display === 'name_no' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="name_no">
                        Preferujem, aby môj profil pôsobil pod prezývkou a nebolo vidieť moje celé meno
                    </label>
                </div>
                <small class="form-text text-muted mb-2">
                    Voľbu je možné neskôr zmeniť v tvojom profile
                </small>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group mb-0">
                        <label>Prezývka</label>
                        <input id="nickname" class="form-control" disabled>
                    </div>
                    <small class="form-text text-muted mb-2">
                        Prezývka je generovaná automaticky
                    </small>
                </div>
                <div class="col-6">
                    <div class="form-group mb-0">
                        <label>Email*</label>
                        <input name="email" type="email"
                            class="form-control <?= isset($invalid['email']) ? 'is-invalid' : '' ?>"
                            autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '@') ?>">
                    </div>
                    <small class="form-text text-muted mb-2">
                        Je potrebné zadať školskú emailovú adresu
                    </small>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group mb-0">
                        <label>Heslo*</label>
                        <div class="input-group">
                            <input name="password" type="password" id="password"
                                class="form-control <?= isset($invalid['password']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button"
                                    data-target="#password" aria-label="Ukáž/skry heslo">👁️</button>
                            </div>
                        </div>
                        <small class="form-text text-muted mb-2">
                            Heslo musí mať aspoň 7 znakov, veľké písmeno, číslicu a špeciálny znak.
                        </small>
                    </div>
                </div>

                <div class="col-6">
                    <div class="form-group">
                        <label>Potvrdiť heslo*</label>
                        <div class="input-group">
                            <input name="password_confirm" type="password" id="password_confirm"
                                class="form-control <?= isset($invalid['password_confirm']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['password_confirm'] ?? '') ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary toggle-password" type="button"
                                    data-target="#password_confirm" aria-label="Ukáž/skry heslo">👁️</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group mb-0">
                        <label>Rok narodenia*</label>
                        <input type="date" name="birth_date"
                            class="form-control <?= isset($invalid['birth_date']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <small class="form-text text-muted mb-2">
                        Je potrebné mať aspoň 18 rokov
                    </small>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group mb-0">
                        <label>Profilový obrázok (.jpg)</label>
                        <input name="profile_image" type="file" accept=".jpg" class="form-control-file"
                            <?= empty($_SESSION['profile_image']) ?: '' ?>>
                    </div>
                    <small class="form-text text-muted mb-2">
                        Profilový obrázok musí byť vo formáte .jpg a bude automaticky prispôsobený na veľkosť 50x100px
                    </small>
                </div>
                <div class="col-6">
                    <label>Nahratý obrázok:</label>
                    <div class="profile-image-register"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>O tebe</label>
                        <textarea id="about" name="about" class="form-control mt-0" rows="3"
                            placeholder="Napíš krátke info o sebe, ktoré ťa vystihuje, v čom vynikáš..."
                            style="resize:none"
                            maxlength="200"><?= htmlspecialchars($_POST['about'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-4">Štúdium:</h3>
        <div class="form-border-box p-4">
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Škola*</label>
                        <select id="school" name="school"
        class="form-control <?= isset($invalid['school']) ? 'is-invalid' : '' ?>">
    <option value="">-- Vyber školu na ktorej študuješ --</option>
    <?php foreach (array_keys($schoolsAndFaculties) as $s): ?>
        <option
            value="<?= htmlspecialchars($s) ?>"
            <?= (($_POST['school'] ?? '') === $s) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s) ?>
        </option>
    <?php endforeach; ?>
</select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>Fakulta*</label>
                        <select id="faculty" name="faculty"
        class="form-control <?= isset($invalid['faculty']) ? 'is-invalid' : '' ?>"
        <?= empty($_POST['school']) ? 'disabled' : '' ?>>
    <option value="">-- Vyber fakultu --</option>
    <?php if (!empty($_POST['school'])): ?>
        <?php foreach (array_keys($schoolsAndFaculties[$_POST['school']]) as $f): ?>
            <option
                value="<?= htmlspecialchars($f) ?>"
                <?= (($_POST['faculty'] ?? '') === $f) ? 'selected' : '' ?>>
                <?= htmlspecialchars($f) ?>
            </option>
        <?php endforeach; ?>
    <?php endif; ?>
</select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Odbor*</label>
                        <select id="program" name="program"
        class="form-control <?= isset($invalid['program']) ? 'is-invalid' : '' ?>"
        <?= (empty($_POST['school']) || empty($_POST['faculty'])) ? 'disabled' : '' ?>>
    <option value="">-- Vyber odbor --</option>
    <?php if (!empty($_POST['school']) && !empty($_POST['faculty'])): ?>
        <?php foreach ($schoolsAndFaculties[$_POST['school']][$_POST['faculty']] as $p): ?>
            <option
                value="<?= htmlspecialchars($p) ?>"
                <?= (($_POST['program'] ?? '') === $p) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p) ?>
            </option>
        <?php endforeach; ?>
    <?php endif; ?>
</select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>Titul (ak máš)</label>
                        <select name="degree" class="form-control">
                            <option value="">– Vyber titul –</option>
                            <?php foreach (['Bc.', 'Mgr.', 'Ing.', 'PhD.'] as $d): ?>
                                <option value="<?= $d ?>" <?= (($_POST['degree'] ?? '') === $d) ? 'selected' : '' ?>>
                                    <?= $d ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label>Začiatok štúdia*</label>
                        <input type="date" name="start_date"
                            class="form-control <?= isset($invalid['start_date']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
        </div>

        <h3 class="mt-4">Záujem o prácu:</h3>
        <div class="form-border-box p-4">
            <div class="form-group">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="work_interest" id="work_yes" value="work_yes"
                        <?= $work_interest === 'work_yes' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="work_yes">
                        Mám seriózny záujem o prácu a chcem byť kontaktovaný recruitermi, vyplním dodatočné informácie
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="work_interest" id="work_no" value="work_no"
                        <?= $work_interest === 'work_no' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="work_no">
                        Nehľadám si prácu a neželám si byť oslovovaný recruitermi, chcem sa sústrediť na obohacovanie
                        študentskej komunity.
                    </label>
                </div>
                <small class="form-text text-muted mb-2">
                    Voľbu je možné neskôr zmeniť v tvojom profile
                </small>
            </div>

            <div id="additional-info" style="display:none;">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Hľadám*</label>
                            <select name="job_preference"
                                class="form-control <?= isset($invalid['job_preference']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Vyber typ úväzku --</option>
                                <?php foreach ([
                                    'brigadu' => 'Brigádu',
                                    'skrateny_uvazok' => 'Skrátený úväzok',
                                    'plny_uvazok' => 'Plný úväzok'
                                ] as $v => $label): ?>
                                    <option value="<?= $v ?>" <?= (($_POST['job_preference'] ?? '') === $v) ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Telefónne číslo*</label>
                            <input type="tel" name="phone"
                                class="form-control mb-0 <?= isset($invalid['phone']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '+421') ?>">
                            <small class="form-text text-muted mb-2">Nebudeme ti posielať žiadne SMS. Je to iba možnosť
                                navyše, akým ťa môže recruiter skontaktovať.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>Preferovaný typ práce*</label>
                            <select name="work_type"
                                class="form-control <?= isset($invalid['work_type']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Vyber typ práce --</option>
                                <?php foreach ([
                                    'homeoffice' => 'Home Office',
                                    'office' => 'Office',
                                    'hybrid' => 'Hybrid'
                                ] as $v => $label): ?>
                                    <option value="<?= $v ?>" <?= (($_POST['work_type'] ?? '') === $v) ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>Preferované miesto práce*</label>
                            <select name="work_location"
                                class="form-control <?= isset($invalid['work_location']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Vyber mesto --</option>
                                <?php
                                $cities = [
                                    'Bratislava',
                                    'Košice',
                                    'Prešov',
                                    'Žilina',
                                    'Nitra',
                                    'Banská Bystrica',
                                    'Trnava',
                                    'Trenčín',
                                    'Martin',
                                    'Poprad',
                                    'Prievidza',
                                    'Zvolen',
                                    'Nové Zámky',
                                    'Michalovce',
                                    'Liptovský Mikuláš',
                                    'Levice',
                                    'Ružomberok'
                                ];
                                foreach ($cities as $c) {
                                    $sel = (($_POST['work_location'] ?? '') === $c) ? ' selected' : '';
                                    echo '<option value="' . htmlspecialchars($c) . '"' . $sel . '>' . htmlspecialchars($c) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                    </div>
                </div>
                <div class="form-group">
                    <label>Jazykové znalosti*</label>
                    <div id="languages-container" class="tags-container mb-2"></div>
                    <div class="form-inline mb-2">
                        <input type="text" id="language-input"
                            class="form-control primary-select <?= isset($invalid['language-input']) ? 'is-invalid' : '' ?>"
                            placeholder="Napíš jazyk a stlač Enter">
                        <select id="language-level" class="form-control secondary-select">
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="B1">B1</option>
                            <option value="B2">B2</option>
                            <option value="C1">C1</option>
                            <option value="C2">C2</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Zručnosti*</label>
                    <div id="skills-container" class="tags-container mb-2"></div>
                    <div class="form-inline mb-2">
                        <input type="text" id="skills-input"
                            class="form-control primary-select <?= isset($invalid['skills-input']) ? 'is-invalid' : '' ?>"
                            placeholder="Napíš skill a stlač Enter">
                        <select id="skills-level" class="form-control secondary-select">
                            <option value="úplné základy">úplné základy</option>
                            <option value="základy">základy</option>
                            <option value="mierne pokročilý">mierne pokročilý</option>
                            <option value="pokročilý">pokročilý</option>
                            <option value="expert">expert</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Záujmy, záľuby, koníčky*</label>
                    <div id="interests-container" class="tags-container mb-2"></div>
                    <input type="text" id="interests-input"
                        class="form-control <?= isset($invalid['interests-input']) ? 'is-invalid' : '' ?>"
                        placeholder="Napíš záujem a stlač Enter">
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label>Odkaz na LinkedIn</label>
                            <input type="url" name="linkedin" class="form-control"
                                placeholder="https://www.linkedin.com/in/uzivatel"
                                value="<?= htmlspecialchars($_POST['linkedin'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label>Odkaz na GitHub</label>
                            <input type="url" name="github" class="form-control"
                                placeholder="https://github.com/uzivatel"
                                value="<?= htmlspecialchars($_POST['github'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="medium-bold mt-2 small">* Polia označené hviezdičkou sú povinné</p>


        <div class="row">
            <div class="col-lg-6">
                <div class="form-group mt-4">
                    <label class="medium-bold"><?= $_SESSION['captcha_question'] ?>*</label>
                    <input type="number" name="captcha"
                        class="form-control" id="captcha" <?= isset($invalid['captcha']) ? 'is-invalid' : '' ?>
                        data-answer="<?= $_SESSION['captcha_answer'] ?>" value="<?= htmlspecialchars($_POST['captcha'] ?? '') ?>">
                </div>
            </div>
        </div>

        <button class="btn btn-success btn-rounded mt-3 px-4 medium-bold">Zaregistrovať sa</button>
        <small class="form-text text-muted mb-2">
            Registráciou potvrdzujem, že údaje, ktoré som vyplnil/a sú pravdivé a uvedomujem si, že v opačnom prípade si
            Studomat vyhradzuje právo zablokovať tvôj účet.
        </small>

        <!-- hidden fields for your tags -->
        <input type="hidden" name="languages_json" id="languages_json"
            value='<?= htmlspecialchars($_POST['languages_json'] ?? '[]', ENT_QUOTES) ?>'>
        <input type="hidden" name="skills_json" id="skills_json"
            value='<?= htmlspecialchars($_POST['skills_json'] ?? '[]', ENT_QUOTES) ?>'>
        <input type="hidden" name="interests_json" id="interests_json"
            value='<?= htmlspecialchars($_POST['interests_json'] ?? '[]', ENT_QUOTES) ?>'>
    </form>

    <p class="mt-3"><a href="register.php">⬅ Späť na výber</a></p>
</div>

<?php include "footer.php"; ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        function loadTags(containerId, hiddenId) {
            const cont = document.getElementById(containerId);
            JSON.parse(document.getElementById(hiddenId).value || '[]')
                .forEach(val => {
                    const tag = document.createElement('span');
                    tag.className = 'badge badge-secondary mr-1';
                    tag.textContent = val + ' ×';
                    tag.style.cursor = 'pointer';
                    tag.addEventListener('click', () => {
                        cont.removeChild(tag);
                        updateHidden(hiddenId, containerId);
                    });
                    cont.appendChild(tag);
                });
        }
        loadTags('languages-container', 'languages_json');
        loadTags('skills-container', 'skills_json');
        loadTags('interests-container', 'interests_json');
        // ——— Generovanie prezývky —————————————————————————————
        document.querySelectorAll('#name, #surname').forEach(el =>
            el.addEventListener('input', () => {
                const n = document.getElementById('name').value.toLowerCase().substr(0, 3);
                const s = document.getElementById('surname').value.toLowerCase().substr(0, 3);
                document.getElementById('nickname').value = n + s;
            })
        );

        // ——— Škola → Fakulta → Odbor —————————————————————————————
        const data = <?= json_encode(
            $schoolsAndFaculties,
            JSON_UNESCAPED_UNICODE
        ) ?>;
        const schoolEl = document.getElementById('school'),
            facultyEl = document.getElementById('faculty'),
            programEl = document.getElementById('program');
        schoolEl.addEventListener('change', () => {
            facultyEl.innerHTML = '<option value="" disabled selected>– Vyber fakultu –</option>';
programEl.innerHTML  = '<option value="" disabled selected>– Najprv vyber fakultu –</option>';
            programEl.disabled = true;
            if (!data[schoolEl.value]) {
                facultyEl.disabled = true;
                return;
            }
            Object.keys(data[schoolEl.value]).forEach(f =>
                facultyEl.add(new Option(f, f))
            );
            facultyEl.disabled = false;
        });
        
        facultyEl.addEventListener('change', () => {
            programEl.innerHTML = '<option value="" disabled selected>– Vyber odbor –</option>';
            if (!data[schoolEl.value] || !data[schoolEl.value][facultyEl.value]) {
                programEl.disabled = true;
                return;
            }
            data[schoolEl.value][facultyEl.value]
                .forEach(p => programEl.add(new Option(p, p)));
            programEl.disabled = false;
        });
        
        // ——— Preview nahratého obrázka —————————————————————————————
        document.querySelector('input[name="profile_image"]')
            .addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = evt => {
                    document.querySelector('.profile-image-register').innerHTML =
                        '<img src="' + evt.target.result + '" style="max-width:120px">';
                };
                reader.readAsDataURL(file);
            });

        // ——— Zobraziť / skryť dodatočné info —————————————————————————
        const workYes = document.getElementById('work_yes'),
            workNo = document.getElementById('work_no'),
            addInfo = document.getElementById('additional-info');
        [workYes, workNo].forEach(radio =>
            radio.addEventListener('change', () => {
                addInfo.style.display = workYes.checked ? 'block' : 'none';
                // pri skrytí vyčisti valid/invalid
                if (!workYes.checked) {
                    ['phone', 'work_preference', 'work_type', 'work_location']
                        .forEach(name => {
                            const el = document.querySelector([name="${name}"]);
                            el && el.classList.remove('is-valid', 'is-invalid');
                        });
                    ['languages-container', 'skills-container', 'interests-container']
                        .forEach(id => {
                            const c = document.getElementById(id);
                            c.classList.remove('is-valid', 'is-invalid');
                        });
                }
            })
        );

        // ——— Tag‑like vstupy ————————————————————————————————————
        function setupTagsWithLevel(inputId, selectId, containerId, hiddenId) {
            const input = document.getElementById(inputId),
                sel = document.getElementById(selectId),
                cont = document.getElementById(containerId);
            input.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const val = input.value.trim();
                if (!val) return;
                const lvl = sel.value;
                const tag = document.createElement('span');
                tag.className = 'badge badge-secondary mr-1';
                tag.textContent = `${val} (${lvl}) ×`;
                tag.style.cursor = 'pointer';
                tag.addEventListener('click', () => {
                    cont.removeChild(tag);
                    cont.classList.toggle('is-valid', cont.children.length > 0);
                    cont.classList.toggle('is-invalid', cont.children.length === 0);
                    updateHidden(hiddenId, containerId);
                });
                cont.appendChild(tag);
                updateHidden(hiddenId, containerId);

                // odstrániť červený obrys, pridať zelený valid
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                input.value = '';
            });
        }

        function setupTags(inputId, containerId, hiddenId) {
            const input = document.getElementById(inputId),
                cont = document.getElementById(containerId);
            input.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                const val = input.value.trim();
                if (!val) return;
                const tag = document.createElement('span');
                tag.className = 'badge badge-secondary mr-1';
                tag.textContent = `${val} ×`;
                tag.style.cursor = 'pointer';
                tag.addEventListener('click', () => {
                    cont.removeChild(tag);
                    cont.classList.toggle('is-valid', cont.children.length > 0);
                    cont.classList.toggle('is-invalid', cont.children.length === 0);
                    updateHidden(hiddenId, containerId);
                });
                cont.appendChild(tag);
                updateHidden(hiddenId, containerId);

                // odstrániť červený obrys, pridať zelený valid
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                input.value = '';
            });
        }

        setupTagsWithLevel('language-input', 'language-level', 'languages-container', 'languages_json');
        setupTagsWithLevel('skills-input', 'skills-level', 'skills-container', 'skills_json');
        setupTags('interests-input', 'interests-container', 'interests_json');

        // ——— Živé overovanie formulára ——————————————————————————
        const mark = (el, ok) => {
            el.classList.toggle('is-valid', ok);
            el.classList.toggle('is-invalid', !ok);
        };
        const markContainer = c => {
            const ok = c.children.length > 0;
            c.classList.toggle('is-valid', ok);
            c.classList.toggle('is-invalid', !ok);
        };

        // validátory
        const notEmpty = el => el.value.trim() !== '';
        const validEmail = el => /\S+@\S+\.\S+/.test(el.value);
        const validDOB = el => {
            if (!el.value) return false;
            const d = new Date(el.value);
            return d <= new Date() && ((Date.now() - d) / (365.25 * 24 * 3600 * 1000)) >= 18;
        };
        const validDate = el => {
            if (!el.value) return false;
            return new Date(el.value) <= new Date();
        };
        const validPass = el => {
            const v = el.value;
            return v.length >= 7 && /[A-Z]/.test(v) && /\d/.test(v) && /[.,;_\-#@]/.test(v);
        };
        const validPhone = el => {
            const digits = el.value.replace(/\D+/g, '');
            return digits.length === 12;
        };

        // polia na live-check
        const nameEl = document.getElementById('name');
        const surEl = document.getElementById('surname');
        const emailEl = document.querySelector('input[name="email"]');
        const dobEl = document.querySelector('input[name="birth_date"]');
        const passEl = document.querySelector('input[name="password"]');
        const confEl = document.querySelector('input[name="password_confirm"]');
        const startEl = document.querySelector('input[name="start_date"]');
        const phoneEl = document.querySelector('input[name="phone"]');
        const jobEl = document.querySelector('select[name="job_preference"]');
        const typeEl = document.querySelector('select[name="work_type"]');
        const locEl = document.querySelector('select[name="work_location"]');
        const langCont = document.getElementById('languages-container');
        const skillCont = document.getElementById('skills-container');
        const intCont = document.getElementById('interests-container');
        const degreeEl = document.querySelector('select[name="degree"]');
        const captchaEl = document.getElementById('captcha');

        schoolEl.addEventListener('change', () => mark(schoolEl, notEmpty(schoolEl)));
        facultyEl.addEventListener('change', () => mark(facultyEl, notEmpty(facultyEl)));
        programEl.addEventListener('change', () => mark(programEl, notEmpty(programEl)));
        degreeEl.addEventListener('change', () => mark(degreeEl, notEmpty(degreeEl)));
        nameEl.addEventListener('input', () => mark(nameEl, notEmpty(nameEl)));
        surEl.addEventListener('input', () => mark(surEl, notEmpty(surEl)));
        emailEl.addEventListener('input', () => mark(emailEl, validEmail(emailEl)));
        dobEl.addEventListener('change', () => mark(dobEl, validDOB(dobEl)));
        passEl.addEventListener('input', () => {
            const ok = validPass(passEl);
            mark(passEl, ok);
            mark(confEl, ok && (confEl.value === passEl.value));
        });
        confEl.addEventListener('input', () =>
            mark(confEl, validPass(passEl) && confEl.value === passEl.value)
        );
        startEl.addEventListener('change', () => mark(startEl, validDate(startEl)));

        // iba ak záujem o prácu
        function liveWorkCheck() {
            if (!workYes.checked) return;
            mark(phoneEl, validPhone(phoneEl));
            mark(jobEl, notEmpty(jobEl));
            mark(typeEl, notEmpty(typeEl));
            mark(locEl, notEmpty(locEl));
            markContainer(langCont);
            markContainer(skillCont);
            markContainer(intCont);
        }
        [phoneEl, jobEl, typeEl, locEl].forEach(el =>
            el.addEventListener('input', liveWorkCheck)
        );
        const observer = new MutationObserver((mutationsList) => {
            liveWorkCheck();
        });
        const config = { childList: true };

        observer.observe(langCont, config);
        observer.observe(skillCont, config);
        observer.observe(intCont, config);

        // ——— Kontrola mena pri submit + chybová hláška ————————————
        const form = document.querySelector('form');
        const nameYes = document.getElementById('name_yes'),
            nameNo = document.getElementById('name_no');
        // vložíme invalid-feedback div
        const grp = nameYes.closest('.form-group');
        const fb = document.createElement('div');
        fb.className = 'invalid-feedback';
        fb.textContent = 'Ak máte záujem o prácu, musíte povoliť zobrazenie mena.';
        grp.appendChild(fb);

        form.addEventListener('submit', e => {
            // ak chce prácu, ale nechce ukázať meno
            if (workYes.checked && !nameYes.checked) {
                e.preventDefault();
                [nameYes, nameNo].forEach(r => {
                    r.classList.add('is-invalid');
                    r.classList.remove('is-valid');
                });
                fb.style.display = 'block';
                grp.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        [nameYes, nameNo].forEach(r =>
            r.addEventListener('change', () => {
                [nameYes, nameNo].forEach(x => x.classList.remove('is-invalid'));
                fb.style.display = 'none';
            })
        );

        function updateHidden(hiddenId, containerId) {
            const arr = [];
            document.getElementById(containerId)
                .querySelectorAll('span')
                .forEach(tag => arr.push(tag.textContent.replace(/ ×$/, '')));
            document.getElementById(hiddenId).value = JSON.stringify(arr);
        }


        if (captchaEl) {
            captchaEl.addEventListener('input', e => {
                const answer = e.target.dataset.answer;
                const current = e.target.value.trim();
                const ok = current !== '' && current === answer;
                e.target.classList.toggle('is-valid', ok);
                e.target.classList.toggle('is-invalid', !ok);
            });
        }


        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.querySelector(btn.dataset.target);
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.textContent = '🙈';
                } else {
                    input.type = 'password';
                    btn.textContent = '👁️';
                }
            });
        });

        addInfo.style.display = workYes.checked ? 'block' : 'none';

    });
</script>