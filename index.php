<?php include("header.php"); ?>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'config.php';
require 'schools.php';

$currentUserId = $_SESSION['student_id'] ?? null;
if (!$currentUserId) {
    header("Location: login.php");
    exit;
}

// --- Pridanie otázky spracovanie ---
if (isset($_POST['submit_question'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $school = $_POST['school'];
    $faculty = $_POST['faculty'] ?: null;
    $program = $_POST['program'] ?: null;
    $category = $_POST['category'];

    if ($title && $content && $school && $category) {
        $stmt = $pdo->prepare("INSERT INTO questions 
            (student_id, title, content, school, faculty, program, category)
            VALUES (:sid, :t, :c, :s, :f, :p, :cat)");
        $stmt->execute([
            ':sid' => $currentUserId,
            ':t' => $title,
            ':c' => $content,
            ':s' => $school,
            ':f' => $faculty,
            ':p' => $program,
            ':cat' => $category
        ]);
    }

    header("Location: /studomat/index.php");
    exit;
}
?>
<body>
<div class="container">

  <?php include("question_form.php"); ?>

  <h3 class="mt-3">Vyhľadávanie</h3>
  <div class="form-group">
  <input class="form-control" type="text" id="searchInput" placeholder="Hľadaj v otázkach..." onkeyup="ajaxSearch()">
  </div>
  <hr>
  <h3>Otázky</h3>
  <div id="questionList"></div>

</div>

<script>
function ajaxSearch() {
  fetch("search.php?q=" + encodeURIComponent(document.getElementById("searchInput").value))
    .then(r => r.text())
    .then(html => document.getElementById("questionList").innerHTML = html);
}

window.addEventListener("DOMContentLoaded", ajaxSearch);
</script>
<?php include("footer.php"); ?>
