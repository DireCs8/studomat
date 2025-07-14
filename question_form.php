<?php
$stmt = $pdo->prepare("SELECT school, faculty, program FROM student WHERE id = :id");
$stmt->execute([':id' => $currentUserId]);
$stu = $stmt->fetch(PDO::FETCH_ASSOC);
$school  = $stu['school']  ?? '';
$faculty = $stu['faculty'] ?? '';
$program = $stu['program'] ?? '';
?>
<div class="card mb-4">
  <div class="card-body">
    <h3 class="card-title mb-4">Pridať novú otázku</h3>
    <form method="POST" action="">
      <!-- Hidden tags from your profile -->
      <input type="hidden" name="school"  value="<?= htmlspecialchars($school) ?>">
      <input type="hidden" name="faculty" value="<?= htmlspecialchars($faculty) ?>">
      <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">

      <div class="form-group">
        <label for="title">Nadpis</label>
        <input
          type="text"
          id="title"
          name="title"
          class="form-control"
          placeholder="Zadaj nadpis otázky"
          required>
      </div>

      <div class="form-group">
        <label for="content">Otázka</label>
        <textarea
          id="content"
          name="content"
          rows="5"
          class="form-control"
          placeholder="Sem napíš svoj dotaz..."
          required></textarea>
      </div>

      <div class="form-group">
        <label for="category">Kategória otázky</label>
        <select
          id="category"
          name="category"
          class="form-control"
          required>
          <option value="">-- Vyber kategóriu --</option>
          <option>Pomoc so zadaním</option>
          <option>Internát</option>
          <option>Škola</option>
          <option>Projekt</option>
          <option>Predmety</option>
          <option>Učitelia</option>
          <option>Rozvrh</option>
          <option>AIS</option>
        </select>
      </div>

      <button type="submit" name="submit_question" class="btn btn-primary">
        Pridať otázku
      </button>
    </form>
  </div>
</div>
