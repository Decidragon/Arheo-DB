<?php
require_once 'db.php';
$pdo = get_db();
$STARI = ['intreg', 'fragmentar', 'nedeterminat'];
$errors  = [];
$success = false;
$old     = [];

function gasesteSauCreeaza(PDO $pdo, string $tabel, string $col_id, array $criterii, array $extra = []): ?int {
    $criterii = array_filter($criterii, fn($v) => $v !== null && $v !== '');
    if (empty($criterii)) {
        return null;
    }

    $where  = [];
    $params = [];
    foreach ($criterii as $col => $val) {
        $where[]        = "$col = :$col";
        $params[":$col"] = $val;
    }
    $sql = "SELECT $col_id FROM $tabel WHERE " . implode(' AND ', $where) . " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $existing = $stmt->fetchColumn();
    if ($existing !== false) {
        return (int)$existing;
    }

    $toInsert = array_merge($criterii, $extra);
    $cols = array_keys($toInsert);
    $placeholders = array_map(fn($c) => ":$c", $cols);
    $sql = "INSERT INTO $tabel (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ") RETURNING $col_id";
    $stmt = $pdo->prepare($sql);
    $params = [];
    foreach ($toInsert as $c => $v) $params[":$c"] = $v;
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $denumire  = trim($_POST['denumire'] ?? '');
    $stare     = $_POST['stare'] ?? 'nedeterminat';
    $faza      = trim($_POST['faza'] ?? '');
    $nivel     = trim($_POST['nivel'] ?? '');
    $categorie = trim($_POST['categorie'] ?? '');
    $tip_forma = trim($_POST['tip_forma'] ?? '');
    // FIXED: Use correct column name from schema
    $tehn_ornam    = trim($_POST['tehn_ornam'] ?? '');
    $tip_ornament  = trim($_POST['tip_ornament'] ?? '');
    $citare    = trim($_POST['citare_completa'] ?? '');
    $observatii = trim($_POST['observatii'] ?? '');
    $legaturi   = trim($_POST['legaturi_culturale'] ?? '');
    $nr_fragmente = ($_POST['nr_fragmente'] ?? '') ?: null;
    $id_original  = ($_POST['id_original']  ?? '') ?: null;

    $localitate = trim($_POST['localitate'] ?? '');
    $punctul    = trim($_POST['punctul'] ?? '');
    $cod_complex = trim($_POST['cod_complex'] ?? '');
    $epoca      = trim($_POST['epoca'] ?? '');
    $cultura    = trim($_POST['cultura'] ?? '');
    $cod_sursa  = trim($_POST['cod_sursa'] ?? '');
    $id_original_parinte = trim($_POST['id_original_parinte'] ?? '');

    if ($denumire === '')                       $errors[] = 'Denumirea este obligatorie.';
    if (!in_array($stare, $STARI, true))        $errors[] = 'Starea aleasă nu este validă.';
    if ($nr_fragmente !== null && (!is_numeric($nr_fragmente) || $nr_fragmente <= 0)) $errors[] = 'Numărul de fragmente trebuie să fie un întreg pozitiv.';
    if ($id_original !== null && !is_numeric($id_original))                          $errors[] = 'ID original trebuie să fie un număr.';
    if ($cod_complex !== '' && $localitate === '')                                   $errors[] = 'Pentru a adăuga un complex trebuie completată și localitatea.';

    $id_artefact_parinte = null;
    if ($id_original_parinte !== '') {
        if (!is_numeric($id_original_parinte)) {
            $errors[] = 'ID-ul artefactului părinte trebuie să fie un număr.';
        } else {
            $stmt = $pdo->prepare("SELECT id_artefact FROM artefacte WHERE id_original = :id");
            $stmt->execute([':id' => (int)$id_original_parinte]);
            $found = $stmt->fetchColumn();
            if ($found === false) {
                $errors[] = 'Nu există niciun artefact cu ID original ' . htmlspecialchars($id_original_parinte) . '.';
            } else {
                $id_artefact_parinte = (int)$found;
            }
        }
    }

    if (empty($errors)) {
        $id_locatie = null;
        if ($localitate !== '') {
            $idTaraRo = $pdo->query("SELECT id_tara FROM tari WHERE cod_tara = 'R'")->fetchColumn();
            $id_locatie = gasesteSauCreeaza(
                $pdo, 'locatii', 'id_locatie',
                ['localitate' => $localitate, 'punctul' => $punctul !== '' ? $punctul : null],
                ['id_tara' => $idTaraRo]
            );
        }

        $id_complex = null;
        if ($cod_complex !== '' && $id_locatie !== null) {
            $id_complex = gasesteSauCreeaza(
                $pdo, 'complexe', 'id_complex',
                ['id_locatie' => $id_locatie, 'cod_complex' => $cod_complex]
            );
        }

        $id_perioada = null;
        if ($epoca !== '' || $cultura !== '') {
            $id_perioada = gasesteSauCreeaza(
                $pdo, 'perioade_istorice', 'id_perioada',
                ['epoca' => $epoca !== '' ? $epoca : null, 'cultura' => $cultura !== '' ? $cultura : null]
            );
        }

        // Sursa bibliografica
        $id_sursa = null;
        if ($cod_sursa !== '') {
            $id_sursa = gasesteSauCreeaza(
                $pdo, 'surse_bibliografice', 'id_sursa',
                ['cod_sursa' => $cod_sursa]
            );
        }

        $stmt = $pdo->prepare("
            INSERT INTO artefacte
                (id_original, denumire, id_perioada, id_locatie, id_complex, id_artefact_parinte,
                 citare_completa, faza, nivel, categorie, tip_forma,
                 tehn_ornam, tip_ornament, nr_fragmente, stare, observatii, legaturi_culturale)
            VALUES
                (:id_original, :denumire, :id_perioada, :id_locatie, :id_complex, :id_artefact_parinte,
                 :citare_completa, :faza, :nivel, :categorie, :tip_forma,
                 :tehn_ornam, :tip_ornament, :nr_fragmente, :stare, :observatii, :legaturi_culturale)
            RETURNING id_artefact
        ");
        $stmt->execute([
            ':id_original'          => $id_original !== null ? (int)$id_original : null,
            ':denumire'             => $denumire,
            ':id_perioada'          => $id_perioada,
            ':id_locatie'           => $id_locatie,
            ':id_complex'           => $id_complex,
            ':id_artefact_parinte'  => $id_artefact_parinte,
            ':citare_completa'      => $citare ?: null,
            ':faza'                 => $faza ?: null,
            ':nivel'                => $nivel ?: null,
            ':categorie'            => $categorie ?: null,
            ':tip_forma'            => $tip_forma ?: null,
            ':tehn_ornam'           => $tehn_ornam ?: null,
            ':tip_ornament'         => $tip_ornament ?: null,
            ':nr_fragmente'         => $nr_fragmente !== null ? (int)$nr_fragmente : null,
            ':stare'                => $stare,
            ':observatii'           => $observatii ?: null,
            ':legaturi_culturale'   => $legaturi ?: null,
        ]);
        $newId = $stmt->fetchColumn();

        if ($id_sursa) {
            $link = $pdo->prepare("INSERT INTO artefacte_surse (id_artefact, id_sursa) VALUES (:a, :s)");
            $link->execute([':a' => $newId, ':s' => $id_sursa]);
        }

        $success = true;
        $old = [];
    }
}

function old(string $key, string $default = ''): string {
    global $old;
    return htmlspecialchars($old[$key] ?? $default);
}
function sel(string $key, string $val): string {
    global $old;
    return (($old[$key] ?? '') === $val) ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adaugă artefact — Arheo DB</title>
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .form-group.full {
            grid-column: span 2;
        }
        .section-label {
            grid-column: span 2;
            font-weight: 600;
            color: #555;
            margin-top: 16px;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .hint {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<nav>
    <div class="container inner">
        <a class="nav-brand" href="index.php">⚱ Arheo DB</a>
        <ul class="nav-links">
            <li><a href="index.php">Acasă</a></li>
            <li><a href="artefacte.php">Bodrog</a></li>
            <li><a href="artefacte.php?dataset=lumeanou">Lumea Nouă</a></li>
            <li><a href="artefacte.php?dataset=lumeanou1">Lumea Nouă 1</a></li>
            <li><a href="surse.php">Surse</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1>Adaugă artefact</h1>
        <p>Introduceți informațiile despre noul artefact. Câmpurile marcate cu * sunt obligatorii.</p>
    </div>
</div>

<div class="container" style="max-width:900px">

    <?php if ($success): ?>
        <div class="alert alert-success" style="font-size:15px">
            ✓ Artefactul a fost adăugat cu succes!
            <div style="margin-top:10px;display:flex;gap:12px">
                <a href="adauga_artefact.php" class="btn btn-primary">+ Adaugă altul</a>
                <a href="artefacte.php" class="btn btn-secondary">Vezi toate artefactele</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>Corectează erorile:</strong>
            <ul style="margin-top:8px;padding-left:20px">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <div class="card" style="padding:32px 36px">
        <form method="POST" action="">
            <div class="form-grid">

                <div class="section-label">Informații de bază</div>

                <div class="form-group full">
                    <label for="denumire">Denumire *</label>
                    <input type="text" id="denumire" name="denumire"
                           value="<?= old('denumire') ?>"
                           placeholder="Ex: Bodrog, toarte, torti, Decea" required>
                </div>

                <div class="form-group">
                    <label for="stare">Stare *</label>
                    <select id="stare" name="stare" required>
                        <?php foreach ($STARI as $s): ?>
                            <option value="<?= $s ?>" <?= sel('stare', $s) ?: ($s === 'nedeterminat' && empty($old) ? 'selected' : '') ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_original">ID original (lasa gol cu exceptie speciala)</label>
                    <input type="number" id="id_original" name="id_original"
                           value="<?= old('id_original') ?>"
                           placeholder="Ex: 711">
                </div>

                <div class="form-group">
                    <label for="faza">Fază</label>
                    <input type="text" id="faza" name="faza"
                           value="<?= old('faza') ?>" placeholder="Ex: I, II, III, c2, e1a">
                </div>

                <div class="form-group">
                    <label for="nivel">Nivel</label>
                    <input type="text" id="nivel" name="nivel"
                           value="<?= old('nivel') ?>" placeholder="Ex: b, c1, c2e1a">
                </div>

                <div class="form-group">
                    <label for="categorie">Categorie</label>
                    <input type="text" id="categorie" name="categorie"
                           value="<?= old('categorie') ?>" placeholder="Ex: 1, 2">
                </div>

                <div class="form-group">
                    <label for="nr_fragmente">Nr. fragmente</label>
                    <input type="number" id="nr_fragmente" name="nr_fragmente"
                           value="<?= old('nr_fragmente') ?>" min="1" step="1">
                </div>

                <div class="form-group">
                    <label for="tip_forma">Tip formă</label>
                    <input type="text" id="tip_forma" name="tip_forma"
                           value="<?= old('tip_forma') ?>" placeholder="Ex: IA, VG, EB">
                </div>

                <!-- FIXED: Updated label and input name to match schema -->
                <div class="form-group">
                    <label for="tehn_ornam">Tehnică ornament</label>
                    <input type="text" id="tehn_ornam" name="tehn_ornam"
                           value="<?= old('tehn_ornam') ?>" placeholder="Ex: D, F, G, P">
                </div>

                <div class="form-group">
                    <label for="tip_ornament">Tip ornament</label>
                    <input type="text" id="tip_ornament" name="tip_ornament"
                           value="<?= old('tip_ornament') ?>" placeholder="Ex: BD, EE, KC">
                </div>

                <div class="form-group full">
                    <label for="citare_completa">Citare bibliografică</label>
                    <input type="text" id="citare_completa" name="citare_completa"
                           value="<?= old('citare_completa') ?>" placeholder="Ex: Luc99,fig.1/1">
                </div>

                <div class="form-group full">
                    <label for="observatii">Observații</label>
                    <textarea id="observatii" name="observatii" rows="2"
                              placeholder="Ex: vas intreg, cu perforatii..."><?= old('observatii') ?></textarea>
                </div>

                <div class="form-group full">
                    <label for="legaturi_culturale">Legături culturale</label>
                    <input type="text" id="legaturi_culturale" name="legaturi_culturale"
                           value="<?= old('legaturi_culturale') ?>" placeholder="Ex: Import Salcuta, mediu Ariusd">
                </div>

                <div class="section-label">Locație și context</div>

                <div class="form-group">
                    <label for="localitate">Localitate / sit</label>
                    <input type="text" id="localitate" name="localitate"
                           value="<?= old('localitate') ?>" placeholder="Ex: Pecica, Deva, B.Herculane"
                           list="lista_localitati">
                    <p class="hint">Dacă localitatea nu există, va fi creată automat.</p>
                </div>

                <div class="form-group">
                    <label for="punctul">Punct(ul)</label>
                    <input type="text" id="punctul" name="punctul"
                           value="<?= old('punctul') ?>" placeholder="Ex: Forgaci, Ciangai, P. Hotilor">
                </div>

                <div class="form-group">
                    <label for="cod_complex">Complex (mormânt / groapă)</label>
                    <input type="text" id="cod_complex" name="cod_complex"
                           value="<?= old('cod_complex') ?>" placeholder="Ex: M 3, G 6">
                    <p class="hint">Necesită localitatea completată mai sus.</p>
                </div>

                <div class="form-group"></div>

                <div class="form-group">
                    <label for="epoca">Epocă</label>
                    <input type="text" id="epoca" name="epoca"
                           value="<?= old('epoca') ?>" placeholder="Ex: eneo, eneol">
                </div>

                <div class="form-group">
                    <label for="cultura">Cultură</label>
                    <input type="text" id="cultura" name="cultura"
                           value="<?= old('cultura') ?>" placeholder="Ex: Bodrog, toarte, torti, grup De">
                </div>

                <div class="form-group">
                    <label for="cod_sursa">Sursă bibliografică</label>
                    <input type="text" id="cod_sursa" name="cod_sursa"
                           value="<?= old('cod_sursa') ?>" placeholder="Ex: Luc99, Rom71, Nem88">
                </div>

                <div class="form-group">
                    <label for="id_original_parinte">ID artefact părinte <span class="text-muted">(dacă e fragment)</span></label>
                    <input type="number" id="id_original_parinte" name="id_original_parinte"
                           value="<?= old('id_original_parinte') ?>" placeholder="Ex: 65">
                </div>

            </div>

            <div class="btn-row">
                <button type="submit" class="btn btn-primary">💾 Salvează artefactul</button>
                <a href="artefacte.php" class="btn btn-secondary">Anulează</a>
            </div>

        </form>
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
