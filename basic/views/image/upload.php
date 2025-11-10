<?php
use yii\helpers\Url;
use yii\helpers\Html;

/** @var $image app\models\Image */

$this->title = 'Upload image';
$uploadUrl = Url::to(['image/upload', 'type' => $image->type, 'related_id' => $image->related_id, 'element' => $image->element]);
$viewUrl = Url::to(['image/view', 'type' => $image->type, 'related_id' => $image->related_id, 'element' => $image->element]);
?>

<div class="image-upload">
    <h2><?= Html::encode($this->title) ?></h2>

    <div style="width: 400px; max-width: 100%; margin: 0 auto;">
        <img id="image-to-crop" src="<?= Html::encode($viewUrl) ?>" alt="Actual image" style="max-width:100%; display:block; margin:0 auto;">
    </div>

    <div style="display:flex; flex-direction:column; align-items:center; margin-top:1rem;">
        <input type="file" id="imageInput" accept="image/*" class="form-control mb-2" style="max-width:250px;">
        <button id="uploadBtn" class="btn btn-primary">Upload image</button>
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

    input.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            image.src = e.target.result;
            if (cropper) cropper.destroy();

            cropper = new Cropper(image, {
                aspectRatio: getAspectRatio('<?= $image->type ?>'),
                viewMode: 1,
                dragMode: 'move',
                background: false,
                autoCropArea: 1
            });
        };
        reader.readAsDataURL(file);
    });

    button.addEventListener('click', async () => {
        if (!cropper) {
            alert('You must select an image.');
            return;
        }

        cropper.getCroppedCanvas().toBlob(async (blob) => {
            const formData = new FormData();
            formData.append('croppedImage', blob, 'crop.png');
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
        }, 'image/png');
    });

    function getAspectRatio(type) {
        switch (type) {
            case 'rank_icon': return 87 / 38;
            case 'pilot_profile': return 3 / 4;
            case 'tour_image': return 16 / 9;
            case 'country_icon': return 2 / 1;
            case 'aircraftType_image': return 4 / 3;
            default: return NaN;
        }
    }
});
</script>
