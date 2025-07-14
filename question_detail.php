<?php include("header.php"); ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}

$currentUserId = $_SESSION['student_id'];

// Extract question ID from friendly URL like /otazka/123-nazov-otazky
$questionId = 0;
$questionSlug = '';
if (isset($_SERVER['REQUEST_URI'])) {
    if (preg_match('#/otazka/(\d+)(?:-([^/]*))?#', $_SERVER['REQUEST_URI'], $matches)) {
        $questionId = (int) $matches[1];
        $questionSlug = $matches[2] ?? '';
    } else {
        $questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;
    }
} else {
    $questionId = isset($_GET['question_id']) ? (int) $_GET['question_id'] : 0;
}

function buildQuestionUrl($id, $title)
{
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));
    return "/studomat/otazka/" . $id . '-' . $slug;
}

// --- Handle editing question or comment ---
if (isset($_POST['submit_edit'])) {
    $editType = $_POST['edit_type'];
    $editId = (int) $_POST['edit_id'];
    $newContent = trim($_POST['new_content']);

    if ($editType === 'question') {
        $stmt = $pdo->prepare("UPDATE questions SET content = :content WHERE id = :id AND student_id = :sid AND score = 0");
        $stmt->execute([':content' => $newContent, ':id' => $editId, ':sid' => $currentUserId]);
    } elseif ($editType === 'comment') {
        $stmt = $pdo->prepare("UPDATE comments SET content = :content WHERE id = :id AND student_id = :sid AND score = 0");
        $stmt->execute([':content' => $newContent, ':id' => $editId, ':sid' => $currentUserId]);
    }
    $stmt = $pdo->prepare("SELECT title FROM questions WHERE id = :id");
    $stmt->execute([':id' => $questionId]);
    $title = $stmt->fetchColumn();
    header("Location: " . buildQuestionUrl($questionId, $title));
    exit;
}

// --- Log unique view by user ---
$check = $pdo->prepare("SELECT 1 FROM question_views WHERE question_id = :qid AND student_id = :sid");
$check->execute([':qid' => $questionId, ':sid' => $currentUserId]);
if (!$check->fetch()) {
    $insert = $pdo->prepare("INSERT INTO question_views (question_id, student_id) VALUES (:qid, :sid)");
    $insert->execute([':qid' => $questionId, ':sid' => $currentUserId]);
}

// --- Handle voting ---
if (isset($_POST['vote'])) {
    $type = $_POST['type']; // question or comment
    $id = (int) $_POST['id'];
    $value = (int) $_POST['value'];
    $redirectId = isset($_POST['redirect_id']) ? (int) $_POST['redirect_id'] : 0;

    if ($type === 'question') {
        $stmt = $pdo->prepare("SELECT student_id, title FROM questions WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT student_id FROM comments WHERE id = :id");
    }
    $stmt->execute([':id' => $id]);
    $ownerData = $stmt->fetch(PDO::FETCH_ASSOC);
    $ownerId = $ownerData['student_id'] ?? null;

    if ($ownerId != $currentUserId && ($value === 1 || $value === -1)) {
        $stmt = $pdo->prepare("SELECT id, value FROM votes WHERE student_id = :sid AND target_type = :type AND target_id = :tid");
        $stmt->execute([
            ':sid' => $currentUserId,
            ':type' => $type,
            ':tid' => $id
        ]);
        $existingVote = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingVote) {
            if ($existingVote['value'] != $value) {
                $stmt = $pdo->prepare("UPDATE votes SET value = :val WHERE id = :vote_id");
                $stmt->execute([
                    ':val' => $value,
                    ':vote_id' => $existingVote['id']
                ]);

                $delta = $value - $existingVote['value'];
                $table = ($type === 'question') ? 'questions' : 'comments';
                $pdo->prepare("UPDATE $table SET score = score + :delta WHERE id = :id")
                    ->execute([':delta' => $delta, ':id' => $id]);
            }
        } else {
            $pdo->prepare("INSERT INTO votes (student_id, target_type, target_id, value) VALUES (:sid, :type, :tid, :val)")
                ->execute([
                    ':sid' => $currentUserId,
                    ':type' => $type,
                    ':tid' => $id,
                    ':val' => $value
                ]);
            $table = ($type === 'question') ? 'questions' : 'comments';
            $pdo->prepare("UPDATE $table SET score = score + :val WHERE id = :id")
                ->execute([':val' => $value, ':id' => $id]);
        }
    }

    // Získaj názov otázky podľa typu
    $title = '';
    if ($type === 'question') {
        $title = $ownerData['title'] ?? '';
    } else {
        $stmt = $pdo->prepare("SELECT q.title FROM questions q JOIN comments c ON c.question_id = q.id WHERE c.id = :id");
        $stmt->execute([':id' => $id]);
        $title = $stmt->fetchColumn();
    }

    header("Location: " . buildQuestionUrl($redirectId, $title));
    exit;
}

// --- Handle mark helpful ---
if (isset($_POST['mark_helpful'])) {
    $commentId = (int) $_POST['comment_id'];
    $questionId = (int) $_POST['question_id'];
    $pdo->prepare("UPDATE questions SET helpful_comment_id = :cid WHERE id = :qid")
        ->execute([':cid' => $commentId, ':qid' => $questionId]);

    $stmt = $pdo->prepare("SELECT title FROM questions WHERE id = :id");
    $stmt->execute([':id' => $questionId]);
    $title = $stmt->fetchColumn();
    header("Location: " . buildQuestionUrl($questionId, $title));
    exit;
}

// --- Handle delete comment ---
if (isset($_POST['delete_comment_id'])) {
    $deleteId = (int) $_POST['delete_comment_id'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id AND id != :helpful");
    $stmt->execute([':id' => $deleteId, ':helpful' => $q['helpful_comment_id'] ?? 0]);

    $stmt = $pdo->prepare("SELECT title FROM questions WHERE id = :id");
    $stmt->execute([':id' => $questionId]);
    $title = $stmt->fetchColumn();
    header("Location: " . buildQuestionUrl($questionId, $title));
    exit;
}

// --- Handle delete question ---
if (isset($_POST['delete_question'])) {
    $deleteId = (int) $_POST['delete_question_id'];

    // Over, že ide o otázku aktuálneho používateľa a nemá komentáre ani hlasy
    $stmt = $pdo->prepare("SELECT id FROM questions WHERE id = :id AND student_id = :sid AND score = 0 AND 
        (SELECT COUNT(*) FROM comments WHERE question_id = :id) = 0");
    $stmt->execute([':id' => $deleteId, ':sid' => $currentUserId]);

    if ($stmt->fetch()) {
        // Odstráň všetky záznamy spojené s otázkou
        $pdo->prepare("DELETE FROM question_views WHERE question_id = :id")->execute([':id' => $deleteId]);
        $pdo->prepare("DELETE FROM votes WHERE target_type = 'question' AND target_id = :id")->execute([':id' => $deleteId]);
        $pdo->prepare("DELETE FROM questions WHERE id = :id")->execute([':id' => $deleteId]);
    }

    header("Location: /studomat/index.php");
    exit;
}

// --- Handle adding comment ---
if (isset($_POST['submit_comment'])) {
    $content = trim($_POST['comment_content']);
    $questionId = (int) $_POST['question_id'];
    $replyTo = isset($_POST['reply_to']) ? (int) $_POST['reply_to'] : null;

    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO comments (student_id, question_id, content, reply_to) VALUES (:sid, :qid, :content, :reply_to)");
        $stmt->execute([
            ':sid' => $currentUserId,
            ':qid' => $questionId,
            ':content' => $content,
            ':reply_to' => $replyTo
        ]);
    }
    $stmt = $pdo->prepare("SELECT title FROM questions WHERE id = :id");
    $stmt->execute([':id' => $questionId]);
    $title = $stmt->fetchColumn();
    header("Location: " . buildQuestionUrl($questionId, $title));
    exit;
}

$stmt = $pdo->prepare("SELECT q.*, s.name, s.surname, 
    (SELECT COUNT(*) FROM question_views WHERE question_id = q.id) as views,
    (SELECT COUNT(*) FROM votes WHERE target_type = 'question' AND target_id = q.id) as vote_count,
    (SELECT COUNT(*) FROM comments WHERE question_id = q.id) as comment_count
    FROM questions q 
    JOIN student s ON q.student_id = s.id 
    WHERE q.id = :id");
$stmt->execute([':id' => $questionId]);
$q = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<div class='container'>";
if (!$q) {
    echo "<p>Otázka neexistuje.</p>";
    echo '<a href="../index.php"><button>Späť</button></a>';
    exit;
}

// --- Display question ---
echo "<div class='mb-5'>";
echo "<a href='../index.php'><button>Späť</button></a>";
echo "<h3 class='mt-3'>" . htmlspecialchars($q['title']) . "</h3>";
echo "<div id='question-content'>";
echo "<p id='question-text'>" . nl2br(htmlspecialchars($q['content'])) . "</p>";
echo "</div>";
echo "<small>Pridal: " . htmlspecialchars($q['name'] . ' ' . $q['surname']) . " | " . $q['created_at'] . "</small><br>";
echo "<small>Zobrazení: {$q['views']} | Počet odpovedí: {$q['comment_count']}</small><br>";


echo "<div class='d-flex'>";
echo "<strong>Hodnotenie otázky: " . ($q['score'] ?? 0) . "</strong>";

if ($q['student_id'] != $currentUserId) {
    echo "<form method='POST' class='ml-2 mr-1'>
            <input type='hidden' name='vote' value='1'>
            <input type='hidden' name='type' value='question'>
            <input type='hidden' name='id' value='{$q['id']}'>
            <input type='hidden' name='value' value='1'>
            <input type='hidden' name='redirect_id' value='{$q['id']}'>
            <button type='submit' class='vote-button'>👍</button>
        </form>
        <form method='POST'>
            <input type='hidden' name='vote' value='1'>
            <input type='hidden' name='type' value='question'>
            <input type='hidden' name='id' value='{$q['id']}'>
            <input type='hidden' name='value' value='-1'>
            <input type='hidden' name='redirect_id' value='{$q['id']}'>
            <button type='submit' class='vote-button'>👎</button>
        </form><br><br>";
}
echo "</div>";

if ($q['student_id'] == $currentUserId) {
    echo "<form method='POST' onsubmit=\"return confirm('Naozaj chceš zmazať túto otázku?');\" style='display:inline'>
        <input type='hidden' name='delete_question_id' value='{$q['id']}'>
        <input type='submit' name='delete_question' value='Zmazať otázku' class='delete-button'>
    </form>";
}
if ($q['student_id'] == $currentUserId && $q['score'] == 0 && $q['comment_count'] == 0) {
    echo "<button class='edit-button' onclick=\"showEditForm('question')\">Upraviť otázku</button>";
    echo "<form id='edit-question-form' method='POST' style='display:none; margin-top:10px;'>
            <input type='hidden' name='edit_type' value='question'>
            <input type='hidden' name='edit_id' value='{$q['id']}'>
            <textarea name='new_content' rows='4' required>" . htmlspecialchars($q['content']) . "</textarea><br>
            <input type='submit' name='submit_edit' value='Uložiť úpravu otázky'>
        </form>";
}

// --- Comments ---
$cStmt = $pdo->prepare("SELECT c.*, s.name, s.surname, (c.id = :helpful) as is_helpful FROM comments c JOIN student s ON c.student_id = s.id WHERE c.question_id = :qid ORDER BY is_helpful DESC, c.score DESC, c.created_at ASC");
$cStmt->execute([':qid' => $q['id'], ':helpful' => $q['helpful_comment_id']]);
$allComments = $cStmt->fetchAll(PDO::FETCH_ASSOC);

$repliesMap = [];
$topLevelComments = [];
foreach ($allComments as $c) {
    if ($c['reply_to']) {
        $repliesMap[$c['reply_to']][] = $c;
    } else {
        $topLevelComments[] = $c;
    }
}

if ($q['student_id'] != $currentUserId) {
    echo "<form method='POST' action=''>
            <input type='hidden' name='question_id' value='{$q['id']}'>
            <textarea class='mt-0' name='comment_content' rows='2' placeholder='Pridať komentár k otázke...' required></textarea><br>
            <input type='submit' name='submit_comment' value='Pridať komentár'>
        </form>";
}

function renderCommentBlock($comment, $q, $currentUserId, $repliesMap)
{
    $isHelpful = $comment['id'] == $q['helpful_comment_id'];
    $isReply = $comment['reply_to'] !== null;
    $style = $isHelpful ? "background:#d6ffd6" : "";
    $borderStyle = $isReply ? "" : "border:1px solid black; padding:15px;border-radius:8px;";

    echo "<div style='margin-top:15px;{$borderStyle} {$style}'>";
    if ($isHelpful) {
        echo "<p style='color:darkgreen;font-weight:700;' class='mb-2'>✅ <em>Autor otázky označil túto odpoveď za nápomocnú</em></p>";
    }
    echo "<p style='font-weight:500'>" . htmlspecialchars($comment['name'] . ' ' . $comment['surname']) . ": " . nl2br(htmlspecialchars($comment['content'])) . "</p>";
    echo "<small>Pridané: {$comment['created_at']}</small><br>";

    echo "<div class='d-flex align-items-center'>";
    echo "<small>Kvalita odpovede: " . ($comment['score'] ?? 0) . "</small>";

    if ($comment['student_id'] != $currentUserId) {
        echo "<form method='POST' class='ml-2 mr-1'>
                <input type='hidden' name='vote' value='1'>
                <input type='hidden' name='type' value='comment'>
                <input type='hidden' name='id' value='{$comment['id']}'>
                <input type='hidden' name='value' value='1'>
                <input type='hidden' name='redirect_id' value='{$q['id']}'>
                <button type='submit' class='vote-button'>👍</button>
            </form>
            <form method='POST'>
                <input type='hidden' name='vote' value='1'>
                <input type='hidden' name='type' value='comment'>
                <input type='hidden' name='id' value='{$comment['id']}'>
                <input type='hidden' name='value' value='-1'>
                <input type='hidden' name='redirect_id' value='{$q['id']}'>
                <button type='submit' class='vote-button'>👎</button>
            </form>";
    }
    echo "</div>";

    if ($q['student_id'] == $currentUserId && !$isReply && !$isHelpful) {
        echo "<form method='POST' style='display:inline;'>
                <input type='hidden' name='comment_id' value='{$comment['id']}'>
                <input type='hidden' name='question_id' value='{$q['id']}'>
                <input type='submit' name='mark_helpful' value='Označiť ako užitočné' class='helpful-button'>
            </form>";
    }

    if ($comment['student_id'] == $currentUserId && !$isHelpful) {
        echo "<form method='POST' style='display:inline;'>
                <input type='hidden' name='delete_comment_id' value='{$comment['id']}'>
                <input type='submit' name='delete_comment' value='Zmazať komentár' class='delete-button'>
            </form>";
    }

    if ($comment['student_id'] == $currentUserId && !$isHelpful && $comment['score'] == 0 && !isset($repliesMap[$comment['id']])) {
        echo "<button class='edit-button' onclick=\"showEditForm('comment-{$comment['id']}')\">Upraviť komentár</button>";
        echo "<div id='comment-{$comment['id']}-edit-form' style='display:none;margin-top:8px;'>
            <form method='POST'>
                <input type='hidden' name='edit_type' value='comment'>
                <input type='hidden' name='edit_id' value='{$comment['id']}'>
                <textarea name='new_content' rows='2' required>" . htmlspecialchars($comment['content']) . "</textarea><br>
                <input type='submit' name='submit_edit' value='Uložiť úpravu komentára'>
            </form>
        </div>";
    }

    if (!$isReply && ($q['student_id'] != $currentUserId || $comment['student_id'] != $q['student_id'])) {
        echo "<form method='POST' action=''>
                <input type='hidden' name='reply_to' value='{$comment['id']}'>
                <textarea name='comment_content' rows='2' placeholder='Odpovedať na komentár...' required></textarea><br>
                <input type='hidden' name='question_id' value='{$q['id']}'>
                <input type='submit' name='submit_comment' value='Odpovedať'>
            </form>";
    }

    if (isset($repliesMap[$comment['id']])) {
        foreach ($repliesMap[$comment['id']] as $reply) {
            echo "<div class='comment-reply'>";
            renderCommentBlock($reply, $q, $currentUserId, []);
            echo "</div>";
        }
    }

    echo "</div>";
}

foreach ($topLevelComments as $comment) {
    renderCommentBlock($comment, $q, $currentUserId, $repliesMap);
}

echo "</div>";
echo "</div>";
?>
<?php include("footer.php"); ?>


<script>
function showEditForm(id) {
    if (id === 'question') {
        document.getElementById('edit-question-form').style.display = 'block';
    } else {
        const el = document.getElementById(id + '-edit-form');
        if (el) el.style.display = 'block';
    }
}
</script>