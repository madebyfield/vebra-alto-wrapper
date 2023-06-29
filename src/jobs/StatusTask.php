<?php

/**
 * Vebra Alto Wrapper plugin for Craft CMS 4.x
 *
 * Integration with the estate agency altosoftware.co.uk
 *
 * @link      https://github.com/Jegard
 * @copyright Copyright (c) 2018 Luca Jegard
 * 
 * @link      https://github.com/MadeByField
 * @copyright Copyright (c) 2023 Dave Speake / Made by Field Ltd
 */

namespace madebyfield\vebraaltowrapper\jobs;

use madebyfield\vebraaltowrapper\VebraAltoWrapper;

use Craft;
use craft\elements\Entry;
use craft\queue\BaseJob;
use craft\helpers\FileHelper;
use craft\elements\Category;
use craft\helpers\StringHelper;
use craft\helpers\DateTimeHelper;

/**
 * StatusTask job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use madebyfield\vebraaltowrapper\jobs\StatusTask as StatusTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new StatusTaskJob([
 *     'description' => Craft::t('vebra-alto-wrapper', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Luca Jegard
 * @package   VebraAltoWrapper
 * @since     1.0.0
 * 
 * @author    Dave Speake / Made By Field Ltd
 * @package   VebraAltoWrapper
 * @since     1.1.0
 */
class StatusTask extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $criteria;
    public $allProps;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue): void
    {
        @set_time_limit(3600);
        @ini_set('max_execution_time', 3600);
        
        $sectionId = $this->criteria['sectionId'];
        $branch = $this->criteria['branch'];
        $token = VebraAltoWrapper::getInstance()->vebraAlto->getToken();

        $branchName = $branch;
        $linkModel = VebraAltoWrapper::getInstance()->vebraAlto->getLinkModel($sectionId);
        $fieldMapping = json_decode($linkModel->fieldMapping);
        $branches = VebraAltoWrapper::getInstance()->vebraAlto->getBranch();
        
        $allEntries = Entry::find()
        ->sectionId($sectionId)
        ->limit(null)
        ->status(null)
        ->all();

        foreach ($allEntries as $entry) {
            if (!in_array((int)$entry->reference, $allProps)) {
                if ((int)VebraAltoWrapper::$plugin->getSettings()->shouldAutoDisable === 1) $entry->enabled = false;
                //$entry->webStatus = 2;
            } else {
                $entry->enabled = true;
            }

            Craft::$app->elements->saveElement($entry);
        }
    }

    public function vebraLog($message)
    {
        $file = Craft::getAlias('@storage/logs/vebra.log');
        $log = date('Y-m-d H:i:s') . ' ' . $message . "\n";
        FileHelper::writeToFile($file, $log, ['append' => true]);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('vebra-alto-wrapper', 'Updating property statuses');
    }
}
