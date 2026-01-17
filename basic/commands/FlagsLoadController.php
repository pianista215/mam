<?php
namespace app\commands;

use app\models\Country;
use app\models\Image;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\FileHelper;
use Yii;


class FlagsLoadController extends Controller
{
    /**
     * Load flags for countries from the provided sourceDir
     *
     * Example:
     * yii flags-load/load @console/initial_flags
     */
    public function actionLoad(string $sourceDir)
    {
        $sourceDir = Yii::getAlias($sourceDir);

        if (!is_dir($sourceDir)) {
            $this->stderr("ERROR: Folder $sourceDir doesn't exist\n");
            return ExitCode::DATAERR;
        }

        $pngFiles = glob($sourceDir . DIRECTORY_SEPARATOR . '*.png');

        if (!$pngFiles) {
            $this->stderr("ERROR: There aren't PNGs in $sourceDir\n");
            return ExitCode::DATAERR;
        }

        // Index files per iso2
        $filesByIso = [];
        foreach ($pngFiles as $file) {
            $bn = basename($file, '.png');
            $iso = strtoupper($bn);
            $filesByIso[$iso] = $file;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Get all countries
            $countries = Country::find()->all();

            foreach ($countries as $country) {
                $iso = strtoupper($country->iso2_code);

                if (!isset($filesByIso[$iso])) {
                    throw new \Exception("FALTA PNG para country ISO: $iso");
                }

                $filePath = $filesByIso[$iso];

                // Create Image
                $image = new Image([
                    'type' => Image::TYPE_COUNTRY_ICON,
                    'related_id' => $country->id,
                    'element' => 0,
                ]);

                // Generate random file
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $image->filename = Yii::$app->security->generateRandomString() . '.' . $extension;

                // Destination by getPath
                $targetPath = $image->getPath();

                // Move before save
                if (!copy($filePath, $targetPath)) {
                    throw new \Exception("Can't move $filePath → $targetPath");
                }

                // Save
                if (!$image->save()) {
                    throw new \Exception("Can't save Image for country $iso: " . json_encode($image->errors));
                }

                // Remove from pendings
                unset($filesByIso[$iso]);
            }

            // If there are png not needed abort, we want to ensure all the files are used ---
            if (!empty($filesByIso)) {
                $keys = implode(',', array_keys($filesByIso));
                throw new \Exception("ERROR: PNGs not used: $keys");
            }

            // If there are a country without flag, abort. Ensure all have a flag
            $countryIds = Country::find()->select('id')->column();

            $flaggedCountryIds = Image::find()
                ->select('related_id')
                ->where(['type' => Image::TYPE_COUNTRY_ICON])
                ->column();

            $countryIds = array_map('intval', $countryIds);
            $flaggedCountryIds = array_map('intval', $flaggedCountryIds);

            $missing = array_diff($countryIds, $flaggedCountryIds);

            if (!empty($missing)) {
                throw new \Exception(
                    "ERROR: Some countries don't have a flag. Ids without flag: " . implode(',', $missing)
                );
            }

            $transaction->commit();
            $this->stdout("OK: All flags are loaded correctly.\n");
            return ExitCode::OK;

        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->stderr("ABORTADO: " . $e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
