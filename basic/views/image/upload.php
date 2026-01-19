<?php
use app\models\Image;
use yii\helpers\Url;
use yii\helpers\Html;

/** @var $image app\models\Image */
/** @var $description string */
/** @var $redirect string|null */

$this->title = Yii::t('app', 'Uploading image for') . ' ' . $description;
$uploadParams = [
    'image/upload',
    'type' => $image->type,
    'related_id' => $image->related_id,
    'element' => $image->element,
];
if ($redirect !== null) {
    $uploadParams['redirect'] = $redirect;
}
$uploadUrl = Url::to($uploadParams);
$viewUrl = Url::to([
    'image/view',
    'type' => $image->type,
    'related_id' => $image->related_id,
    'element' => $image->element,
]);

$typeSettings = Image::getAllowedTypes()[$image->type] ?? [];
$displayWidth = $typeSettings['width'] ?? null;
$displayHeight = $typeSettings['height'] ?? null;
$hasFixedDimensions = $displayWidth !== null && $displayHeight !== null;
$imageExists = $image->id !== null;
$showPreview = $imageExists || $image->type !== Image::TYPE_PAGE_IMAGE;

$photoTypes = [
    Image::TYPE_PAGE_IMAGE,
    Image::TYPE_PILOT_PROFILE,
    Image::TYPE_TOUR_IMAGE,
    Image::TYPE_AIRCRAFT_TYPE_IMAGE,
];
$isPhoto = in_array($image->type, $photoTypes);
?>

<div class="image-upload">
    <h2><?= Html::encode($this->title) ?></h2>

    <div style="max-width: 100%; width: 80%; margin: 0 auto;">
        <?php if ($showPreview): ?>
            <img id="image-to-crop" src="<?= Html::encode($viewUrl) ?>"
                 alt="Actual image"
                 style="max-width:100%; height:auto; display:block; margin:0 auto;">
        <?php else: ?>
            <img id="image-to-crop" src=""
                 alt=""
                 style="max-width:100%; height:auto; display:none; margin:0 auto;">
        <?php endif; ?>
    </div>

    <div style="display:flex; flex-direction:column; align-items:center; margin-top:1rem;">
        <input type="file" id="imageInput" accept="image/*" class="form-control mb-2" style="max-width:250px;">
        <div class="btn-group" role="group">
            <button id="uploadBtn" class="btn btn-primary"><?= Yii::t('app', 'Upload image') ?></button>
            <?php if ($imageExists): ?>
                <?php
                $deleteParams = ['image/delete', 'id' => $image->id];
                if ($redirect !== null) {
                    $deleteParams['redirect'] = $redirect;
                }
                ?>
                <?= Html::a(
                    Yii::t('app', 'Delete image'),
                    Url::to($deleteParams),
                    [
                        'class' => 'btn btn-danger',
                        'data-method' => 'post',
                        'data-confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" rel="stylesheet"/>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const image = document.getElementById('image-to-crop');
    const input = document.getElementById('imageInput');
    const button = document.getElementById('uploadBtn');
    let cropper = null;

    const targetWidth = <?= $displayWidth ?? 'null' ?>;
    const targetHeight = <?= $displayHeight ?? 'null' ?>;
    const hasFixedDimensions = targetWidth !== null && targetHeight !== null;
    const aspectRatio = hasFixedDimensions ? targetWidth / targetHeight : NaN;

    let originalFileType = null;

    input.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        originalFileType = file.type;

        const reader = new FileReader();
        reader.onload = (e) => {
            image.src = e.target.result;
            image.style.display = 'block';
            if (cropper) cropper.destroy();

            const cropperOptions = {
                viewMode: 1,
                dragMode: 'move',
                background: false,
                autoCropArea: 1,
            };
            if (hasFixedDimensions) {
                cropperOptions.aspectRatio = aspectRatio;
            }

            cropper = new Cropper(image, cropperOptions);
        };
        reader.readAsDataURL(file);
    });

    button.addEventListener('click', async () => {
        if (!cropper) {
            alert('You must select an image.');
            return;
        }

        const canvasOptions = hasFixedDimensions ? { width: targetWidth, height: targetHeight } : {};
        const isPhoto = <?= $isPhoto ? 'true' : 'false' ?>;
        const isPng = originalFileType === 'image/png';
        const mimeType = (isPhoto && !isPng) ? 'image/jpeg' : 'image/png';
        const fileExtension = (isPhoto && !isPng) ? 'jpg' : 'png';
        const quality = (isPhoto && !isPng) ? 0.92 : undefined;

        cropper.getCroppedCanvas(canvasOptions).toBlob(async (blob) => {
            const maxSize = <?= \yii\helpers\Json::encode(Yii::$app->formatter->asShortSize((int)ini_get('upload_max_filesize') * 1024 * 1024, 0)) ?>;
            const maxBytes = <?= (int)ini_get('upload_max_filesize') * 1024 * 1024 ?>;

            if (blob.size > maxBytes) {
                alert(<?= \yii\helpers\Json::encode(Yii::t('app', 'The image is too large. Maximum size allowed:')) ?> + ' ' + maxSize);
                return;
            }

            const formData = new FormData();
            formData.append('croppedImage', blob, 'crop.' + fileExtension);
            const csrfParam = document.querySelector('meta[name="csrf-param"]').getAttribute('content');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            formData.append(csrfParam, csrfToken);

            const response = await fetch('<?= $uploadUrl ?>', {
                method: 'POST',
                body: formData
            });

            if (response.redirected) {
                window.location.href = response.url;
            } else {
                alert('Error uploading image.');
            }
        }, mimeType, quality);
    });
});
</script>
