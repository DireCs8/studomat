<?php require 'schools.php'; ?>

<h3>Pridať novú otázku</h3>
<form method="POST">
    <label>Nadpis:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Otázka:</label><br>
    <textarea name="content" rows="4" required></textarea><br><br>

    <label>Vysoká škola:</label><br>
    <select name="school" id="school" required>
        <option value="">-- Vyber školu --</option>
        <?php foreach ($schools as $school): ?>
            <option value="<?= htmlspecialchars($school) ?>"><?= htmlspecialchars($school) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Fakulta:</label><br>
    <select name="faculty" id="faculty" required disabled>
        <option value="">-- Najprv vyber školu --</option>
    </select><br><br>

    <label>Študijný odbor:</label><br>
    <select name="program" id="program" required disabled>
        <option value="">-- Najprv vyber fakultu --</option>
    </select><br><br>

    <label>Kategória otázky:</label><br>
    <select name="category" required>
        <option value="">-- Vyber kategóriu --</option>
        <option value="Pomoc so zadaním">Pomoc so zadaním</option>
        <option value="Internát">Internát</option>
        <option value="Škola">Škola</option>
        <option value="Projekt">Projekt</option>
        <option value="Predmety">Predmety</option>
        <option value="Učitelia">Učitelia</option>
        <option value="Rozvrh">Rozvrh</option>
        <option value="AIS">AIS</option>
    </select><br><br>

    <input type="submit" name="submit_question" value="Pridať otázku">
</form>

<script>
    const data = <?= json_encode($schoolsAndFaculties, JSON_UNESCAPED_UNICODE) ?>;
    const schoolSelect = document.getElementById('school');
    const facultySelect = document.getElementById('faculty');
    const programSelect = document.getElementById('program');

    schoolSelect.addEventListener('change', () => {
        const school = schoolSelect.value;
        facultySelect.innerHTML = '<option value="">-- Vyber fakultu --</option>';
        programSelect.innerHTML = '<option value="">-- Najprv vyber fakultu --</option>';
        programSelect.disabled = true;

        if (!school || !data[school]) {
            facultySelect.disabled = true;
            return;
        }

        Object.keys(data[school]).forEach(faculty => {
            const option = document.createElement('option');
            option.value = faculty;
            option.textContent = faculty;
            facultySelect.appendChild(option);
        });

        facultySelect.disabled = false;
    });

    facultySelect.addEventListener('change', () => {
        const school = schoolSelect.value;
        const faculty = facultySelect.value;
        programSelect.innerHTML = '<option value="">-- Vyber študijný odbor --</option>';

        if (!school || !faculty || !data[school][faculty]) {
            programSelect.disabled = true;
            return;
        }

        data[school][faculty].forEach(program => {
            const option = document.createElement('option');
            option.value = program;
            option.textContent = program;
            programSelect.appendChild(option);
        });

        programSelect.disabled = false;
    });
</script>
