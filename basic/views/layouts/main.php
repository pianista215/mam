<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header id="header">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
    ]);

    $items =
    [
            ['label' => 'Home', 'url' => ['/site/index']],
            [
                'label' => 'About',
                'items' => [
                    ['label' => 'Staff', 'url' => ['page/view', 'code' => 'staff']],
                    ['label' => 'Rules', 'url' => ['page/view', 'code' => 'rules']],
                    ['label' => 'Ranks', 'url' => ['page/view', 'code' => 'ranks']],
                    ['label' => 'School', 'url' => ['page/view', 'code' => 'school']],
                ],
            ],
            ['label' => 'Pilots', 'url' => ['/pilot/index']],
            ['label' => 'Flights', 'url' => ['/flight/index']],
            [
                'label' => 'Operations',
                'items' => [
                    ['label' => 'Aircraft Types', 'url' => ['/aircraft-type/index']],
                    ['label' => 'Aircrafts', 'url' => ['/aircraft/index']],
                    ['label' => 'Countries', 'url' => ['/country/index']],
                    ['label' => 'Airports', 'url' => ['/airport/index']],
                    ['label' => 'Routes', 'url' => ['/route/index']],
                ],
            ],
        ];

    if (Yii::$app->user->can('submitFpl')) {
        $items[] = [
            'label' => 'Actions',
            'items' => [
                ['label' => 'Submit FPL', 'url' => ['/submitted-flight-plan/my-fpl']],
                ['label' => 'Move Pilot', 'url' => ['/pilot/move']],
            ],
        ];
    }

    // TODO: REFINE THE PERMISSIONS HERE. Not checking the role
    if (Yii::$app->authManager->getAssignment('admin', Yii::$app->user->id) !== null) {
        $items[] = [
            'label' => 'Admin',
            'items' => [
                ['label' => 'List FPL', 'url' => ['/submitted-flight-plan/index']],
                ['label' => 'Validate Flight', 'url' => ['/TODOOOOOO/validate']],
            ],
        ];
    }

    $items[] = Yii::$app->user->isGuest
        ? ['label' => 'Login', 'url' => ['/site/login']]
        : '<li class="nav-item">'
            . Html::beginForm(['/site/logout'])
            . Html::submitButton(
                'Logout (' . Yii::$app->user->identity->license . ')',
                ['class' => 'nav-link btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav'],
        'items' => $items,
    ]);
    NavBar::end();
    ?>
</header>

<main id="main" class="flex-shrink-0" role="main">
    <div class="container">
        <?php if (!empty($this->params['breadcrumbs'])): ?>
            <?= Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]) ?>
        <?php endif ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer id="footer" class="mt-auto py-3 bg-light">
    <div class="container">
        <div class="row text-muted">
            <div class="col-md-6 text-center text-md-start">&copy; My Company <?= date('Y') ?></div>
            <div class="col-md-6 text-center text-md-end"><?= Yii::powered() ?></div>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
