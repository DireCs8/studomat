<?php include("header.php");

$errors = [];
$successMessage = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']);
  $surname=trim($_POST['surname']);
  $org=trim($_POST['organization']);
  $email=trim($_POST['email']);
  $msg=trim($_POST['message']);
  $to='majko.dc@gmail.com';
  mail($to,"[Učiteľ] Kontaktný formulár",
       "Meno: $name\nPriezvisko: $surname\nŠkola: $org\nEmail: $email\n\n$msg",
       "From:$email");
  $successMessage="✅ Správa bola odoslaná.";
}
?>
<div class="container">
  <?php if($successMessage): ?>
    <div class="alert alert-success"><?=$successMessage?></div>
    <p><a href="login.php">Prihlás sa</a></p>
  <?php else: ?>
    <h3>Učiteľ – Kontaktný formulár</h3>
    <form method="POST">
      <div class="form-group">
        <label>Meno</label><input class="form-control" name="name" required>
      </div>
      <div class="form-group">
        <label>Priezvisko</label><input class="form-control" name="surname" required>
      </div>
      <div class="form-group">
        <label>Škola</label><input class="form-control" name="organization" required>
      </div>
      <div class="form-group">
        <label>Email</label><input class="form-control" type="email" name="email" required>
      </div>
      <div class="form-group">
        <label>Správa</label><textarea class="form-control" name="message" required></textarea>
      </div>
      <button class="btn btn-primary" name="submit_teacher">Odoslať</button>
    </form>
    <p class="mt-3"><a href="register.php?reset=1">⬅ Späť</a></p>
  <?php endif; ?>
</div>
<?php include("footer.php"); ?>
