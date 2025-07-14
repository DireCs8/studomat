<?php

$stmt = $pdo->prepare("SELECT school, faculty, program FROM student WHERE id = :id");
$stmt->execute([':id' => $currentUserId]);
$stu = $stmt->fetch(PDO::FETCH_ASSOC);

// Ak by náhodou nemal nič vyplnené, fallback na prázdny string
$school = $stu['school'] ?? '';
$faculty = $stu['faculty'] ?? '';
$program = $stu['program'] ?? '';
?>

<h3>Pridať novú otázku</h3>
<form method="POST" action="">
    <label>Nadpis:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Otázka:</label><br>
    <textarea name="content" rows="4" required></textarea><br><br>

    <!-- Zobrazenie a skryté odoslanie školy -->
    <input type="hidden" name="school" value="<?= htmlspecialchars($school) ?>">
    <input type="hidden" name="faculty" value="<?= htmlspecialchars($faculty) ?>">
    <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">

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