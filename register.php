<?php include("header.php"); ?>
<div class="container">
  <h2 class="mt-4">Som:</h2>
  <?php
    $opts = [
      'Maturant'       => ['icon'=>'📚','desc'=>'Rád by som získal informácie o vysokých školách, maturitách prípadne sa spýtal na niečo ostatných vysokoškolákov'],
      'Vysokoškolák'   => ['icon'=>'👨‍🎓','desc'=>'Chcem sa pridať do komunity vysokoškolákov, navzájom si pomáhať, zdieľať svoje skúsenosti a ukázať recruiterom, že som šikovný'],
      'Absolvent'      => ['icon'=>'🎓','desc'=>'Poskytnem rady pre ostatných vysokoškolákov, podelím sa o svoje skúsenosti príp. využijem možnosť sa ukázať recruiterom'],
      'Učiteľ'         => ['icon'=>'🧑‍🏫','desc'=>'Pomôžem študentom, ktorí majú otázky ohľadom vysokých škôl, predmetov alebo iných tém'],
      'Recruiter'      => ['icon'=>'🕵️‍♂️','desc'=>'Hľadám šikovných študentov a absolventov, ktorých by som mohol osloviť ohľadom zamestnania alebo stáže'],
    ];
  ?>
  <div class="profile-options-container">
    <?php foreach($opts as $role => $info): 
      $file = 'register_'.strtolower(str_replace(['č','š','ť','á','é','ľ'],['c','s','t','a','e','l'],$role)).'.php';
    ?>
      <a href="<?= $file ?>" 
         class="card profile-option">
        <div class="profile-title h5"><?= $info['icon'] ?> <?= $role ?></div>
        <p><?= $info['desc'] ?></p>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="mt-3">Máš už účet? <a href="login.php">Prihlás sa</a></p>
</div>
<?php include("footer.php"); ?>
