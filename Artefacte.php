<?php
require_once 'db.php';
$pdo = get_db();

$dataset = $_GET['dataset'] ?? '';
$is_lumeanou  = ($dataset === 'lumeanou');
$is_lumeanou1 = ($dataset === 'lumeanou1');

$lumeanou_cultura_sql = "(p.cultura ILIKE '%Lumea Noua%' OR p.cultura ILIKE '%Iclod%' OR p.cultura ILIKE '%Suplac%')";

$f = [];
$text_filters = [
    'q','cultura','stare','punct','complex','faza','etapa','nivel',
    'sectiunea','caroul','adincimea','tip_forma','var_forma',
    'nr_buza','tip_buza','dim_buza','nr_git','dim_git',
    'nr_umar','tip_umar','dim_umar','nr_burta','tip_burta','dim_burta',
    'nr_toarta','tip_toarta','nr_picior','tip_picior','dim_picior',
    'tehn_ornam','tip_ornament','var_ornam','fond_de_pi',
    'cul_exteri','cul_interi','cul_pictur',
    'amestec','netezire','ardere','factura',
    'categorie','domfunct','sectfunct','folosire',
    'stratigraf','legaturi','raspandire',
    'citare','nr_inventar','observatii',
    'datarea','originea','nr_proba_c',
    'fragm_min','fragm_max',
    'lungime_min','lungime_max',
    'latime_min','latime_max',
    'inaltime_min','inaltime_max',
];
foreach ($text_filters as $key) {
    $f[$key] = trim($_GET[$key] ?? '');
}

$where  = [];
$params = [];

if ($is_lumeanou) {
    $where[] = $lumeanou_cultura_sql;
    $where[] = "(a.dataset IS NULL OR a.dataset != 'lumeanou1')";
} elseif ($is_lumeanou1) {
    $where[] = "a.dataset = 'lumeanou1'";
} else {
    $where[] = "(p.cultura IS NULL OR NOT ($lumeanou_cultura_sql))";
    $where[] = "(a.dataset IS NULL OR a.dataset != 'lumeanou1')";
}

$add = function($col, $key) use (&$where, &$params, $f) {
    if ($f[$key] !== '') {
        $where[]           = "$col ILIKE :$key";
        $params[":$key"]    = '%' . $f[$key] . '%';
    }
};
$add_exact = function($col, $key) use (&$where, &$params, $f) {
    if ($f[$key] !== '') {
        $where[]           = "$col = :$key";
        $params[":$key"]    = $f[$key];
    }
};
$add_min = function($col, $key) use (&$where, &$params, $f) {
    if ($f[$key] !== '') {
        $where[]         = "$col >= :$key";
        $params[":$key"]  = (float)$f[$key];
    }
};
$add_max = function($col, $key) use (&$where, &$params, $f) {
    if ($f[$key] !== '') {
        $where[]         = "$col <= :$key";
        $params[":$key"]  = (float)$f[$key];
    }
};

if ($f['q'] !== '') {
    $where[]      = '(a.denumire ILIKE :q OR l.localitate ILIKE :q OR a.citare_completa ILIKE :q)';
    $params[':q'] = '%' . $f['q'] . '%';
}
$add('p.cultura',      'cultura');
$add_exact('a.stare',  'stare');
$add('l.punctul',      'punct');
$add('c.cod_complex',  'complex');
$add('a.faza',         'faza');
$add('a.etapa',        'etapa');
$add('a.nivel',        'nivel');
$add('a.sectiunea',    'sectiunea');
$add('a.caroul',       'caroul');
$add('a.adincimea',    'adincimea');
$add('a.tip_forma',    'tip_forma');
$add('a.var_forma',    'var_forma');
$add('a.nr_buza::text','nr_buza');
$add('a.tip_buza',     'tip_buza');
$add('a.dim_buza',     'dim_buza');
$add('a.nr_git::text', 'nr_git');
$add('a.dim_git',      'dim_git');
$add('a.nr_umar::text','nr_umar');
$add('a.tip_umar',     'tip_umar');
$add('a.dim_umar',     'dim_umar');
$add('a.nr_burta::text','nr_burta');
$add('a.tip_burta',    'tip_burta');
$add('a.dim_burta',    'dim_burta');
$add('a.nr_toarta::text','nr_toarta');
$add('a.tip_toarta',   'tip_toarta');
$add('a.nr_picior::text','nr_picior');
$add('a.tip_picior',   'tip_picior');
$add('a.dim_picior',   'dim_picior');
$add('a.tehn_ornam',   'tehn_ornam');
$add('a.tip_ornament', 'tip_ornament');
$add('a.var_ornam',    'var_ornam');
$add('a.fond_de_pi',   'fond_de_pi');
$add('a.cul_exteri',   'cul_exteri');
$add('a.cul_interi',   'cul_interi');
$add('a.cul_pictur',   'cul_pictur');
$add('a.amestec',      'amestec');
$add('a.netezire',     'netezire');
$add('a.ardere',       'ardere');
$add('a.factura',      'factura');
$add('a.categorie',    'categorie');
$add('a.domfunct',     'domfunct');
$add('a.sectfunct',    'sectfunct');
$add('a.folosire',     'folosire');
$add('a.stratigraf',   'stratigraf');
$add('a.legaturi',     'legaturi');
$add('a.raspandire',   'raspandire');
$add('a.citare_completa','citare');
$add('a.nr_inventar',  'nr_inventar');
$add('a.observatii',   'observatii');
$add('a.datarea',      'datarea');
$add('a.originea',     'originea');
$add('a.nr_proba_c',   'nr_proba_c');
$add_min('a.nr_fragmente','fragm_min');
$add_max('a.nr_fragmente','fragm_max');
$add_min('a.lungime',  'lungime_min'); $add_max('a.lungime','lungime_max');
$add_min('a.latime',   'latime_min');  $add_max('a.latime', 'latime_max');
$add_min('a.inaltime', 'inaltime_min');$add_max('a.inaltime','inaltime_max');

$sql = "
    SELECT a.*, p.epoca, p.cultura, l.localitate, l.punctul, l.provincia,
           t.nume_tara, c.cod_complex
    FROM artefacte a
    LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
    LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
    LEFT JOIN tari t              ON t.id_tara     = l.id_tara
    LEFT JOIN complexe c          ON c.id_complex  = a.id_complex
    WHERE " . implode(' AND ', $where) . "
    ORDER BY a.id_original NULLS LAST";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$artefacte = $stmt->fetchAll();

$sj = "LEFT JOIN perioade_istorice p ON p.id_perioada = a.id_perioada
       LEFT JOIN locatii l           ON l.id_locatie  = a.id_locatie
       LEFT JOIN complexe c          ON c.id_complex  = a.id_complex";
if ($is_lumeanou) {
    $sw = "WHERE $lumeanou_cultura_sql AND (a.dataset IS NULL OR a.dataset != 'lumeanou1')";
} elseif ($is_lumeanou1) {
    $sw = "WHERE a.dataset = 'lumeanou1'";
} else {
    $sw = "WHERE (p.cultura IS NULL OR NOT ($lumeanou_cultura_sql)) AND (a.dataset IS NULL OR a.dataset != 'lumeanou1')";
}

function opts($pdo, $sj, $sw, $col) {
    return $pdo->query("SELECT DISTINCT $col FROM artefacte a $sj $sw AND $col IS NOT NULL AND $col::text != '' ORDER BY $col")
               ->fetchAll(PDO::FETCH_COLUMN);
}

$opt_cultura    = opts($pdo,$sj,$sw,'p.cultura');
$opt_stare      = opts($pdo,$sj,$sw,'a.stare');
$opt_punct      = opts($pdo,$sj,$sw,'l.punctul');
$opt_complex    = opts($pdo,$sj,$sw,'c.cod_complex');
$opt_faza       = opts($pdo,$sj,$sw,'a.faza');
$opt_etapa      = opts($pdo,$sj,$sw,'a.etapa');
$opt_nivel      = opts($pdo,$sj,$sw,'a.nivel');
$opt_sectiunea  = opts($pdo,$sj,$sw,'a.sectiunea');
$opt_tip_forma  = opts($pdo,$sj,$sw,'a.tip_forma');
$opt_tip_buza   = opts($pdo,$sj,$sw,'a.tip_buza');
$opt_tip_burta  = opts($pdo,$sj,$sw,'a.tip_burta');
$opt_tip_toarta = opts($pdo,$sj,$sw,'a.tip_toarta');
$opt_tip_picior = opts($pdo,$sj,$sw,'a.tip_picior');
$opt_tehn_ornam = opts($pdo,$sj,$sw,'a.tehn_ornam');
$opt_cul_exteri = opts($pdo,$sj,$sw,'a.cul_exteri');
$opt_cul_interi = opts($pdo,$sj,$sw,'a.cul_interi');
$opt_amestec    = opts($pdo,$sj,$sw,'a.amestec');
$opt_netezire   = opts($pdo,$sj,$sw,'a.netezire');
$opt_ardere     = opts($pdo,$sj,$sw,'a.ardere');
$opt_factura    = opts($pdo,$sj,$sw,'a.factura');
$opt_categorie  = opts($pdo,$sj,$sw,'a.categorie');

$reset_url  = $dataset ? "artefacte.php?dataset={$dataset}" : 'artefacte.php';
$page_title = $is_lumeanou  ? 'Artefacte — Lumea Nouă / Iclod / Suplac'
            : ($is_lumeanou1 ? 'Artefacte — Lumea Nouă 1'
            : 'Artefacte — Bodrog');

$any_filter = count(array_filter($f, fn($v) => $v !== '')) > 0;

function sel($opts, $name, $cur) {
    $html = "<select name=\"$name\"><option value=\"\">Toate</option>";
    foreach ($opts as $o) {
        $s = $cur === $o ? ' selected' : '';
        $e = htmlspecialchars($o);
        $html .= "<option value=\"$e\"$s>$e</option>";
    }
    return $html . '</select>';
}
function inp($name, $cur, $ph='') {
    return "<input type=\"text\" name=\"$name\" value=\"".htmlspecialchars($cur)."\" placeholder=\"$ph\">";
}
function num_inp($name, $cur, $ph='') {
    return "<input type=\"number\" name=\"$name\" value=\"".htmlspecialchars($cur)."\" placeholder=\"$ph\" min=\"0\" step=\"any\">";
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — Arheo DB</title>
    <link rel="stylesheet" type="text/css" href="mystyle.css" />
    <style>
        .filter-section { margin-bottom: 10px; }
        .filter-section h3 {
            font-size: 11px; text-transform: uppercase; letter-spacing: .07em;
            color: var(--muted); margin-bottom: 8px; padding-bottom: 4px;
            border-bottom: 1px solid var(--border);
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
        }
        .filter-grid .form-group { margin: 0; }
        .filter-grid label { font-size: 11px; }
        .range-pair { display: flex; gap: 4px; }
        .range-pair input { width: 0; flex: 1; }
        .filter-actions { display: flex; gap: 8px; margin-top: 12px; }
        details > summary {
            cursor: pointer; font-size: 12px; font-weight: 600;
            color: var(--accent); padding: 6px 0; user-select: none;
        }
        details[open] > summary { margin-bottom: 10px; }
        .filter-badge {
            display:inline-block; background:#fee2e2; border:1px solid #fca5a5;
            color:#dc2626; border-radius:20px; font-size:11px; font-weight:600;
            padding:2px 10px; margin-left:10px; vertical-align:middle;
            text-transform:uppercase; letter-spacing:.05em;
        }
        td.num { text-align: right; color: var(--muted); font-size: 13px; }
    </style>
</head>
<body>

<nav>
    <div class="container-wide inner">
        <a class="nav-brand" href="index.php">⚱ Arheo DB</a>
        <ul class="nav-links">
            <li><a href="index.php">Acasă</a></li>
            <li><a href="artefacte.php" class="<?= !$is_lumeanou && !$is_lumeanou1 ? 'active':'' ?>">Bodrog</a></li>
            <li><a href="artefacte.php?dataset=lumeanou" class="<?= $is_lumeanou?'active':'' ?>">Lumea Nouă</a></li>
            <li><a href="artefacte.php?dataset=lumeanou1" class="<?= $is_lumeanou1?'active':'' ?>">Lumea Nouă 1</a></li>
            <li><a href="surse.php">Surse</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container-wide flex-between" style="flex-wrap:wrap;gap:12px">
        <div>
            <h1><?= $page_title ?><?php if($any_filter): ?><span class="filter-badge">Filtrat</span><?php endif; ?></h1>
            <p><?= count($artefacte) ?> înregistrări găsite</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="adauga_artefact.php" class="btn btn-primary">+ Adaugă</a>
            <a href="editeaza_artefact.php<?= $dataset?'?dataset='.$dataset:'' ?>" class="btn btn-secondary">✏ Editează</a>
            <a href="sterge_artefact.php<?= $dataset?'?dataset='.$dataset:'' ?>" class="btn btn-danger">✕ Șterge</a>
        </div>
    </div>
</div>

<div class="container-wide">
<div class="card mb-2">
<form method="GET" action="">
<?php if($dataset): ?><input type="hidden" name="dataset" value="<?= htmlspecialchars($dataset) ?>"><?php endif; ?>

<div class="filter-section">
    <h3>General</h3>
    <div class="filter-grid">
        <div class="form-group" style="grid-column:span 2">
            <label>Caută (denumire / localitate / sursă)</label>
            <?= inp('q',$f['q'],'Ex: Bodrog, Iclod...') ?>
        </div>
        <div class="form-group"><label>Cultură</label><?= sel($opt_cultura,'cultura',$f['cultura']) ?></div>
        <div class="form-group"><label>Stare</label><?= sel($opt_stare,'stare',$f['stare']) ?></div>
        <div class="form-group"><label>Fază</label><?= sel($opt_faza,'faza',$f['faza']) ?></div>
        <div class="form-group"><label>Etapă</label><?= sel($opt_etapa,'etapa',$f['etapa']) ?></div>
        <div class="form-group"><label>Nivel</label><?= sel($opt_nivel,'nivel',$f['nivel']) ?></div>
        <div class="form-group"><label>Punct</label><?= sel($opt_punct,'punct',$f['punct']) ?></div>
        <div class="form-group"><label>Complex</label><?= sel($opt_complex,'complex',$f['complex']) ?></div>
        <div class="form-group"><label>Categorie</label><?= sel($opt_categorie,'categorie',$f['categorie']) ?></div>
    </div>
</div>

<details>
<summary>▸ Filtre avansate</summary>

<div class="filter-section">
    <h3>Locație detaliată</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Secțiunea</label><?= sel($opt_sectiunea,'sectiunea',$f['sectiunea']) ?></div>
        <div class="form-group"><label>Caroul</label><?= inp('caroul',$f['caroul']) ?></div>
        <div class="form-group"><label>Adâncimea</label><?= inp('adincimea',$f['adincimea']) ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Formă</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Tip formă</label><?= sel($opt_tip_forma,'tip_forma',$f['tip_forma']) ?></div>
        <div class="form-group"><label>Variantă formă</label><?= inp('var_forma',$f['var_forma']) ?></div>
        <div class="form-group"><label>Tipologie</label><?= inp('tipologia',$f['tipologia'] ?? '') ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Componente morfologice</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Nr. buză</label><?= inp('nr_buza',$f['nr_buza']) ?></div>
        <div class="form-group"><label>Tip buză</label><?= sel($opt_tip_buza,'tip_buza',$f['tip_buza']) ?></div>
        <div class="form-group"><label>Dim. buză</label><?= inp('dim_buza',$f['dim_buza']) ?></div>
        <div class="form-group"><label>Nr. gît</label><?= inp('nr_git',$f['nr_git']) ?></div>
        <div class="form-group"><label>Dim. gît</label><?= inp('dim_git',$f['dim_git']) ?></div>
        <div class="form-group"><label>Nr. umăr</label><?= inp('nr_umar',$f['nr_umar']) ?></div>
        <div class="form-group"><label>Tip umăr</label><?= inp('tip_umar',$f['tip_umar']) ?></div>
        <div class="form-group"><label>Dim. umăr</label><?= inp('dim_umar',$f['dim_umar']) ?></div>
        <div class="form-group"><label>Nr. burtă</label><?= inp('nr_burta',$f['nr_burta']) ?></div>
        <div class="form-group"><label>Tip burtă</label><?= sel($opt_tip_burta,'tip_burta',$f['tip_burta']) ?></div>
        <div class="form-group"><label>Dim. burtă</label><?= inp('dim_burta',$f['dim_burta']) ?></div>
        <div class="form-group"><label>Nr. toartă</label><?= inp('nr_toarta',$f['nr_toarta']) ?></div>
        <div class="form-group"><label>Tip toartă</label><?= sel($opt_tip_toarta,'tip_toarta',$f['tip_toarta']) ?></div>
        <div class="form-group"><label>Nr. picior</label><?= inp('nr_picior',$f['nr_picior']) ?></div>
        <div class="form-group"><label>Tip picior</label><?= sel($opt_tip_picior,'tip_picior',$f['tip_picior']) ?></div>
        <div class="form-group"><label>Dim. picior</label><?= inp('dim_picior',$f['dim_picior']) ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Ornament</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Tehn. ornam.</label><?= sel($opt_tehn_ornam,'tehn_ornam',$f['tehn_ornam']) ?></div>
        <div class="form-group"><label>Tip ornament</label><?= inp('tip_ornament',$f['tip_ornament']) ?></div>
        <div class="form-group"><label>Var. ornam.</label><?= inp('var_ornam',$f['var_ornam']) ?></div>
        <div class="form-group"><label>Fond pictură</label><?= inp('fond_de_pi',$f['fond_de_pi']) ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Culori &amp; Tehnică</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Culoare ext.</label><?= sel($opt_cul_exteri,'cul_exteri',$f['cul_exteri']) ?></div>
        <div class="form-group"><label>Culoare int.</label><?= sel($opt_cul_interi,'cul_interi',$f['cul_interi']) ?></div>
        <div class="form-group"><label>Culoare pictură</label><?= inp('cul_pictur',$f['cul_pictur']) ?></div>
        <div class="form-group"><label>Amestec</label><?= sel($opt_amestec,'amestec',$f['amestec']) ?></div>
        <div class="form-group"><label>Netezire</label><?= sel($opt_netezire,'netezire',$f['netezire']) ?></div>
        <div class="form-group"><label>Ardere</label><?= sel($opt_ardere,'ardere',$f['ardere']) ?></div>
        <div class="form-group"><label>Factură</label><?= sel($opt_factura,'factura',$f['factura']) ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Dimensiuni &amp; Fragmente</h3>
    <div class="filter-grid">
        <div class="form-group">
            <label>Nr. fragmente</label>
            <div class="range-pair"><?= num_inp('fragm_min',$f['fragm_min'],'Min') ?><?= num_inp('fragm_max',$f['fragm_max'],'Max') ?></div>
        </div>
        <div class="form-group">
            <label>Lungime (cm)</label>
            <div class="range-pair"><?= num_inp('lungime_min',$f['lungime_min'],'Min') ?><?= num_inp('lungime_max',$f['lungime_max'],'Max') ?></div>
        </div>
        <div class="form-group">
            <label>Lățime (cm)</label>
            <div class="range-pair"><?= num_inp('latime_min',$f['latime_min'],'Min') ?><?= num_inp('latime_max',$f['latime_max'],'Max') ?></div>
        </div>
        <div class="form-group">
            <label>Înălțime (cm)</label>
            <div class="range-pair"><?= num_inp('inaltime_min',$f['inaltime_min'],'Min') ?><?= num_inp('inaltime_max',$f['inaltime_max'],'Max') ?></div>
        </div>
    </div>
</div>

<div class="filter-section">
    <h3>Funcționalitate &amp; Provenință</h3>
    <div class="filter-grid">
        <div class="form-group"><label>Dom. funcțional</label><?= inp('domfunct',$f['domfunct']) ?></div>
        <div class="form-group"><label>Sector funcț.</label><?= inp('sectfunct',$f['sectfunct']) ?></div>
        <div class="form-group"><label>Folosire</label><?= inp('folosire',$f['folosire']) ?></div>
        <div class="form-group"><label>Datare</label><?= inp('datarea',$f['datarea']) ?></div>
        <div class="form-group"><label>Originea</label><?= inp('originea',$f['originea']) ?></div>
    </div>
</div>

<div class="filter-section">
    <h3>Bibliografie &amp; Inventar</h3>
    <div class="filter-grid">
        <div class="form-group" style="grid-column:span 2"><label>Sursă / Citare</label><?= inp('citare',$f['citare'],'Ex: BERCIU, Luc99') ?></div>
        <div class="form-group"><label>Nr. inventar</label><?= inp('nr_inventar',$f['nr_inventar']) ?></div>
        <div class="form-group"><label>Nr. probă C14</label><?= inp('nr_proba_c',$f['nr_proba_c']) ?></div>
        <div class="form-group"><label>Stratigrafie</label><?= inp('stratigraf',$f['stratigraf']) ?></div>
        <div class="form-group"><label>Legături</label><?= inp('legaturi',$f['legaturi']) ?></div>
        <div class="form-group"><label>Răspândire</label><?= inp('raspandire',$f['raspandire']) ?></div>
        <div class="form-group"><label>Observații</label><?= inp('observatii',$f['observatii']) ?></div>
    </div>
</div>

</details>

<div class="filter-actions">
    <button type="submit" class="btn btn-primary">Filtrează</button>
    <a href="<?= $reset_url ?>" class="btn btn-secondary">Reset</a>
</div>
</form>
</div>

<div class="scroll-top" id="scrollTop"><div class="scroll-top-inner" id="scrollTopInner"></div></div>
<div class="table-wrap" id="tableWrap">
<table>
    <thead>
        <tr>
            <th>#</th><th>Denumire</th><th>Cultură</th><th>Localitate</th>
            <th>Punct</th><th>Complex</th><th>Secț.</th><th>Carou</th><th>Adânc.</th>
            <th>Fază</th><th>Etapă</th><th>Nivel</th>
            <th>Tip formă</th><th>Var. formă</th>
            <th>Nr.B</th><th>Tip B</th><th>Dim.B</th>
            <th>Nr.G</th><th>Dim.G</th>
            <th>Nr.U</th><th>Tip U</th><th>Dim.U</th>
            <th>Nr.Bu</th><th>Tip Bu</th><th>Dim.Bu</th>
            <th>Nr.T</th><th>Tip T</th>
            <th>Nr.P</th><th>Tip P</th><th>Dim.P</th>
            <th>Tehn.Orn</th><th>Tip Orn</th><th>Var.Orn</th><th>Fond Pi.</th>
            <th>Col.Ext</th><th>Col.Int</th><th>Col.Pict</th>
            <th>Amestec</th><th>Netezire</th><th>Ardere</th><th>Factură</th>
            <th>Categ.</th><th>Dom.F.</th><th>Sect.F.</th>
            <th>Fragm.</th><th>L</th><th>La</th><th>H</th>
            <th>Stare</th><th>Nr.Inv</th><th>Sursă</th><th>Observații</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($artefacte)): ?>
        <tr><td colspan="52" class="text-center text-muted" style="padding:24px">Nicio înregistrare găsită.</td></tr>
    <?php endif; ?>
    <?php foreach ($artefacte as $a):
        $cls = match($a['stare']) { 'intreg'=>'badge-green','fragmentar'=>'badge-red',default=>'badge-muted'};
        $d = fn($v) => $v !== null && $v !== '' ? htmlspecialchars($v) : '—';
    ?>
    <tr>
        <td class="text-muted"><?= $a['id_original'] ?? $a['id_artefact'] ?></td>
        <td><strong><?= $d($a['denumire']) ?></strong></td>
        <td class="text-muted"><?= $a['cultura'] ? $d($a['epoca'].' – '.$a['cultura']) : '—' ?></td>
        <td><?= $d($a['localitate']) ?></td>
        <td class="text-muted"><?= $d($a['punctul']) ?></td>
        <td class="text-muted"><?= $d($a['cod_complex']) ?></td>
        <td class="text-muted"><?= $d($a['sectiunea']) ?></td>
        <td class="text-muted"><?= $d($a['caroul']) ?></td>
        <td class="text-muted"><?= $d($a['adincimea']) ?></td>
        <td class="text-muted"><?= $d($a['faza']) ?></td>
        <td class="text-muted"><?= $d($a['etapa']) ?></td>
        <td class="text-muted"><?= $d($a['nivel']) ?></td>
        <td class="text-muted"><?= $d($a['tip_forma']) ?></td>
        <td class="text-muted"><?= $d($a['var_forma']) ?></td>
        <td class="num"><?= $d($a['nr_buza']) ?></td>
        <td class="text-muted"><?= $d($a['tip_buza']) ?></td>
        <td class="text-muted"><?= $d($a['dim_buza']) ?></td>
        <td class="num"><?= $d($a['nr_git']) ?></td>
        <td class="text-muted"><?= $d($a['dim_git']) ?></td>
        <td class="num"><?= $d($a['nr_umar']) ?></td>
        <td class="text-muted"><?= $d($a['tip_umar']) ?></td>
        <td class="text-muted"><?= $d($a['dim_umar']) ?></td>
        <td class="num"><?= $d($a['nr_burta']) ?></td>
        <td class="text-muted"><?= $d($a['tip_burta']) ?></td>
        <td class="text-muted"><?= $d($a['dim_burta']) ?></td>
        <td class="num"><?= $d($a['nr_toarta']) ?></td>
        <td class="text-muted"><?= $d($a['tip_toarta']) ?></td>
        <td class="num"><?= $d($a['nr_picior']) ?></td>
        <td class="text-muted"><?= $d($a['tip_picior']) ?></td>
        <td class="text-muted"><?= $d($a['dim_picior']) ?></td>
        <td class="text-muted"><?= $d($a['tehn_ornam']) ?></td>
        <td class="text-muted"><?= $d($a['tip_ornament']) ?></td>
        <td class="text-muted"><?= $d($a['var_ornam']) ?></td>
        <td class="text-muted"><?= $d($a['fond_de_pi']) ?></td>
        <td class="text-muted"><?= $d($a['cul_exteri']) ?></td>
        <td class="text-muted"><?= $d($a['cul_interi']) ?></td>
        <td class="text-muted"><?= $d($a['cul_pictur']) ?></td>
        <td class="text-muted"><?= $d($a['amestec']) ?></td>
        <td class="text-muted"><?= $d($a['netezire']) ?></td>
        <td class="text-muted"><?= $d($a['ardere']) ?></td>
        <td class="text-muted"><?= $d($a['factura']) ?></td>
        <td class="text-muted"><?= $d($a['categorie']) ?></td>
        <td class="text-muted"><?= $d($a['domfunct']) ?></td>
        <td class="text-muted"><?= $d($a['sectfunct']) ?></td>
        <td class="num"><?= $d($a['nr_fragmente']) ?></td>
        <td class="num"><?= $d($a['lungime']) ?></td>
        <td class="num"><?= $d($a['latime']) ?></td>
        <td class="num"><?= $d($a['inaltime']) ?></td>
        <td><span class="badge <?= $cls ?>"><?= $d($a['stare']) ?></span></td>
        <td class="text-muted"><?= $d($a['nr_inventar']) ?></td>
        <td class="text-muted" style="font-size:12px"><?= $d($a['citare_completa']) ?></td>
        <td class="text-muted" style="font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $d($a['observatii']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<script>
(function(){
    var tw=document.getElementById('tableWrap'),
        st=document.getElementById('scrollTop'),
        si=document.getElementById('scrollTopInner');
    if(!tw||!st||!si)return;
    function sync(){si.style.width=tw.scrollWidth+'px';}
    sync();window.addEventListener('resize',sync);
    var b=false;
    st.addEventListener('scroll',function(){if(b)return;b=true;tw.scrollLeft=st.scrollLeft;b=false;});
    tw.addEventListener('scroll',function(){if(b)return;b=true;st.scrollLeft=tw.scrollLeft;b=false;});
})();
</script>

<footer><div class="container"><p>Arheo DB &mdash; Bază de date arheologică</p></div></footer>
</body>
</html>
