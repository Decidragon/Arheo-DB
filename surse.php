<?php
require_once 'db.php';

$pdo = get_db();

$surse = $pdo->query("
    SELECT s.id_sursa,
           s.cod_sursa,
           COUNT(asu.id_artefact) AS nr_artefacte
    FROM surse_bibliografice s
    LEFT JOIN artefacte_surse asu ON asu.id_sursa = s.id_sursa
    GROUP BY s.id_sursa, s.cod_sursa
    ORDER BY nr_artefacte DESC, s.cod_sursa
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surse bibliografice — Arheo DB</title>
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
</head>
<body>

<nav>
    <div class="container flex-between">
        <a class="nav-brand" href="index.php">⚱ Arheo DB</a>
        <ul class="nav-links">
            <li><a href="index.php">Acasă</a></li>
            <li><a href="artefacte.php">Artefacte</a></li>
            <li><a href="surse.php" class="active">Surse bibliografice</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1>Surse bibliografice</h1>
        <p><?= count($surse) ?> surse citate în baza de date</p>
    </div>
</div>

<div class="container">

    <?php if (empty($surse)): ?>
        <div class="card text-center">
            <p class="text-muted">Nicio sursă bibliografică în baza de date.</p>
        </div>
    <?php else: ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cod sursă</th>
                    <th>Artefacte citate</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($surse as $i => $s): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($s['cod_sursa']) ?></strong></td>
                    <td>
                        <span class="badge <?= $s['nr_artefacte'] > 0 ? 'badge-gold' : 'badge-muted' ?>">
                            <?= $s['nr_artefacte'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="artefacte.php?sursa=<?= urlencode($s['cod_sursa']) ?>" class="text-muted" style="font-size:13px">
                            Vezi artefactele →
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<footer>
    <div class="container">
        <p>Arheo DB &mdash; Bază de date arheologică</p>
    </div>
</footer>

</body>
</html>
