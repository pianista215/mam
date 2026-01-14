<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\config\ConfigHelper as CK;
use app\rbac\constants\Permissions;
use app\rbac\constants\Roles;
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
$this->registerCssFile(
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
    [
        'integrity' => 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==',
        'crossorigin' => 'anonymous',
        'referrerpolicy' => 'no-referrer'
    ]
);
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
        'brandLabel' => CK::getAirlineName(),
        'brandUrl' => Yii::$app->homeUrl,
        'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
    ]);

    $items =
    [
            [
                'label' => Yii::t('app', 'About'),
                'items' => [
                    ['label' => Yii::t('app', 'Staff'), 'url' => ['page/view', 'code' => 'staff']],
                    ['label' => Yii::t('app', 'Rules'), 'url' => ['page/view', 'code' => 'rules']],
                    ['label' => Yii::t('app', 'Ranks'), 'url' => ['page/view', 'code' => 'ranks']],
                    ['label' => Yii::t('app', 'School'), 'url' => ['page/view', 'code' => 'school']],
                ],
            ],
            ['label' => Yii::t('app', 'Pilots'), 'url' => ['/pilot/index']],
    ];

    if (!Yii::$app->user->isGuest) {
        $items[] =
         ['label' => Yii::t('app', 'Flights'), 'url' => ['/flight/index']];
    }
    $items[] =  ['label' => Yii::t('app', 'Tours'), 'url' => ['/tour/index']];
    $items[] =
            [
                'label' => Yii::t('app', 'Operations'),
                'items' => [
                    ['label' => Yii::t('app', 'Aircraft Types'), 'url' => ['/aircraft-type/index']],
                    ['label' => Yii::t('app', 'Aircraft Configurations'), 'url' => ['/aircraft-configuration/index']],
                    ['label' => Yii::t('app', 'Aircrafts'), 'url' => ['/aircraft/index']],
                    ['label' => Yii::t('app', 'Airports'), 'url' => ['/airport/index']],
                    ['label' => Yii::t('app', 'Countries'), 'url' => ['/country/index']],
                    ['label' => Yii::t('app', 'Ranks'), 'url' => ['/rank/index']],
                    ['label' => Yii::t('app', 'Routes'), 'url' => ['/route/index']],
                ],
            ];

    if (Yii::$app->user->can(Permissions::SUBMIT_FPL)) {
        $items[] =
        [
            'label' => Yii::t('app', 'Actions'),
            'items' => [
                ['label' => Yii::t('app', 'Submit FPL'), 'url' => ['/submitted-flight-plan/my-fpl']],
                ['label' => Yii::t('app', 'Move Pilot'), 'url' => ['/pilot/move']],
            ],
        ];
    }

    if (Yii::$app->user->can(Permissions::VALIDATE_VFR_FLIGHT) || Yii::$app->user->can(Permissions::VALIDATE_IFR_FLIGHT)) {
        $items[] =
        [
            'label' => Yii::t('app', 'Validations'),
            'items' => [
                ['label' => Yii::t('app', 'Validate Flights'), 'url' => ['/flight/index-pending']],
                ['label' => Yii::t('app', 'List FPLs'), 'url' => ['/submitted-flight-plan/index']],
            ],
        ];
    }

    if (Yii::$app->authManager->getAssignment(Roles::ADMIN, Yii::$app->user->id) !== null) {
        $items[] = [
            'label' => Yii::t('app', 'Admin'),
            'items' => [
                ['label' => Yii::t('app', 'Activate Pilots'), 'url' => ['/pilot/activate-pilots']],
                ['label' => Yii::t('app', 'Manage Images'), 'url' => ['/image/index']],
                ['label' => Yii::t('app', 'Role Assignment'), 'url' => ['/admin/roles-matrix']],
                ['label' => Yii::t('app', 'Site Settings'), 'url' => ['/admin/site-settings']],
            ],
        ];
    }

    $items[] = Yii::$app->user->isGuest
        ? ['label' => Yii::t('app', 'Login'), 'url' => ['/site/login']]
        : '<li class="nav-item">'
            . Html::beginForm(['/site/logout'])
            . Html::submitButton(
                Yii::t('app', 'Logout'). ' (' . Yii::$app->user->identity->license . ')',
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
            <div class="col-md-6 text-center text-md-start">&copy; <?= CK::getAirlineName() ?> <?= date('Y') ?></div>
            <div class="col-md-6 text-center text-md-end">
                Powered by <a href="https://github.com/pianista215/mam" target="_blank">Mam</a>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-1 fs-4">
            <?php if ($x = CK::getXUrl()): ?>
                <a href="<?= $x ?>" target="_blank"><i class="fab fa-x-twitter"></i></a>
            <?php endif; ?>
            <?php if ($instagram = CK::getInstagramUrl()): ?>
                <a href="<?= $instagram ?>" target="_blank"><i class="fab fa-instagram"></i></a>
            <?php endif; ?>
            <?php if ($facebook = CK::getFacebookUrl()): ?>
                <a href="<?= $facebook ?>" target="_blank"><i class="fab fa-facebook"></i></a>
            <?php endif; ?>
        </div>
        <div class="text-center mt-2">
            <?= \yii\helpers\Html::beginForm(['/site/language'], 'post', ['class' => 'd-inline']) ?>
            <?= \yii\helpers\Html::dropDownList(
                    'language',
                    Yii::$app->language,
                    ['es' => 'Español', 'en' => 'English'],
                    [
                        'class' => 'form-select form-select-sm d-inline w-auto',
                        'onchange' => 'this.form.submit()'
                    ]
                )
            ?>
            <?= \yii\helpers\Html::endForm() ?>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
