<?php
/**
 * Image Optimize plugin for Craft CMS
 *
 * Automatically optimize images after they've been transformed
 *
 * @link      https://nystudio107.com
 * @copyright Copyright (c) 2018 nystudio107
 */

namespace nystudio107\imageoptimize\console\controllers;

use Craft;
use craft\base\Field;
use craft\helpers\App;
use craft\queue\QueueInterface;
use nystudio107\imageoptimize\ImageOptimize;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\queue\redis\Queue as RedisQueue;

/**
 * Optimize Command
 *
 * @author    nystudio107
 * @package   ImageOptimize
 * @since     1.2.0
 */
class OptimizeController extends Controller
{
    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether image variants should be forced to recreated, even if they already exist on disk
     * @since 1.6.18
     */
    public bool $force = false;

    /**
     * @var string|null Only re-save image variants associated with this field handle
     * @since 1.6.18
     */
    public ?string $field = null;

    /**
     * @var bool Should the image generation simply be queued, rather than run immediately?
     */
    public bool $queue = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        return array_merge($options, [
            'force',
            'field',
            'queue',
        ]);
    }

    /**
     * Create all the OptimizedImages Field variants by creating all the responsive image variant transforms
     *
     * @param ?string $volumeHandle
     *
     * @throws InvalidConfigException
     */
    public function actionCreate(?string $volumeHandle = null): void
    {
        echo 'Creating optimized image variants' . PHP_EOL;
        if ($this->force) {
            echo 'Forcing optimized image variants creation via --force' . PHP_EOL;
        }

        $fieldId = null;
        if ($this->field !== null) {
            /** @var Field $field */
            $field = Craft::$app->getFields()->getFieldByHandle($this->field);
            $fieldId = $field?->id;
        }
        if ($volumeHandle === null) {
            // Re-save all the optimized image variants in all volumes
            ImageOptimize::$plugin->optimizedImages->resaveAllVolumesAssets($fieldId, $this->force);
        } else {
            // Re-save all the optimized image variants in a specific volume
            $volumes = Craft::$app->getVolumes();
            $volume = $volumes->getVolumeByHandle($volumeHandle);
            if ($volume) {
                ImageOptimize::$plugin->optimizedImages->resaveVolumeAssets($volume, $fieldId, $this->force);
            } else {
                echo 'Unknown Asset Volume handle: ' . $volumeHandle . PHP_EOL;
            }
        }
        if (!$this->queue) {
            $this->runCraftQueue();
        }
    }

    /**
     * Create a single OptimizedImage for the passed in Asset ID
     *
     * @param ?int $id
     */
    public function actionCreateAsset(?int $id = null): void
    {
        echo 'Creating optimized image variants' . PHP_EOL;

        if ($id === null) {
            echo 'No Asset ID specified' . PHP_EOL;
        } else {
            // Re-save a single Asset ID
            ImageOptimize::$plugin->optimizedImages->resaveAsset($id, $this->force);
        }
        if (!$this->queue) {
            $this->runCraftQueue();
        }
    }

    /**
     *
     */
    private function runCraftQueue(): void
    {
        // This might take a while
        App::maxPowerCaptain();
        $queue = Craft::$app->getQueue();
        if ($queue instanceof QueueInterface) {
            $queue->run();
        } elseif ($queue instanceof RedisQueue) {
            $queue->run(false);
        }
    }
}
