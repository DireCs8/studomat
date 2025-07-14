<?php
require 'config.php';
$q = trim(mb_strtolower($_GET['q'] ?? '', 'UTF-8'));
$stmt = $pdo->prepare("
  SELECT DISTINCT q.*, s.name, s.surname,
    (SELECT COUNT(*) FROM question_views WHERE question_id=q.id) AS views,
    (SELECT COUNT(*) FROM comments WHERE question_id=q.id) AS comment_count
  FROM questions q
  JOIN student s ON q.student_id=s.id
  LEFT JOIN comments c ON c.question_id=q.id
  WHERE LOWER(q.title) LIKE :q OR LOWER(q.content) LIKE :q OR LOWER(c.content) LIKE :q
  ORDER BY q.created_at DESC
");
$stmt->execute([':q' => "%" . $q . "%"]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$questions) {
    echo "<p>Žiadne výsledky.</p>";
    exit;
}

foreach ($questions as $q):
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $q['title']));
    ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:20px;">
        <h4><?php echo htmlspecialchars($q['title']); ?></h4>
        <p><?php echo nl2br(htmlspecialchars($q['content'])); ?></p>
        <p>
            <strong><?php echo htmlspecialchars($q['school']); ?></strong>
            <?php if ($q['faculty']): ?> – <?php echo htmlspecialchars($q['faculty']); ?><?php endif; ?>
            <?php if ($q['program']): ?> – <?php echo htmlspecialchars($q['program']); ?><?php endif; ?>
            | <em><?php echo htmlspecialchars($q['category']); ?></em>
        </p>
        <small>Pridal: <?php echo htmlspecialchars($q['name'] . ' ' . $q['surname']); ?> |
            <?php echo $q['created_at']; ?></small><br>
        <small>Zobrazenia: <?php echo $q['views']; ?> | Odpovedí: <?php echo $q['comment_count']; ?></small><br>
        <strong>Hodnotenie: <?php echo $q['score'] ?? 0; ?></strong><br><br>
        <a href="/studomat/otazka/<?php echo $q['id'] . '-' . $slug; ?>">
            <button>Zobraziť otázku</button>
        </a>
    </div>
<?php endforeach; ?>