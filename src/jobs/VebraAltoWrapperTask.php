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
use madebyfield\vebraaltowrapper\jobs\PropertyTask;
use madebyfield\vebraaltowrapper\jobs\StatusTask;

use Craft;
use craft\elements\Entry;
use craft\queue\BaseJob;
use craft\helpers\FileHelper;
use craft\helpers\Queue;
use craft\elements\Category;
use craft\helpers\StringHelper;
use craft\helpers\DateTimeHelper;

/**
 * VebraAltoWrapperTask job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use madebyfield\vebraaltowrapper\jobs\VebraAltoWrapperTask as VebraAltoWrapperTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new VebraAltoWrapperTaskJob([
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
class VebraAltoWrapperTask extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $criteria;

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

        if (strpos($branchName, '-noname') !== false) {
            foreach ($branches as $_branch) {
                if ((int)$_branch->branchid == explode('-', $branchName)[0]) {
                    $branch = $_branch;
                }
            }
        } else {
            foreach ($branches as $_branch) {
                if ($_branch->name == $branchName) {
                    $branch = $_branch;
                }
            }
        }

        $file = Craft::$app->getPath()->getRuntimePath() . '/vebra-alto-wrapper/token.txt';
        if (!file_exists($file) || $this->criteria['full']) {
            $url = $branch->url . '/property';
            $propertyList = VebraAltoWrapper::getInstance()->vebraAlto->connect($url)['response']['property'];
        } else {
            $url = '/property/' . date('Y/m/d/00/00/00', filemtime($file));
            $propertyList = VebraAltoWrapper::getInstance()->vebraAlto->connect($url, true)['response']['property'];
        }
        
        if (gettype($propertyList) !== 'array') $propertyList = [$propertyList];
        $this->vebraLog('Total properties in update: ' . print_r(count($propertyList), true));
        if (count($propertyList) < 1) return;
        usort($propertyList, fn($a, $b) => (int)$a->prop_id < (int)$b->prop_id);
        
        $allProps = [];
        foreach ($propertyList as $propertyKey => $property) {
            $this->setProgress($queue, $propertyKey / count($propertyList));
            if ((string) $property->action === 'deleted') {
                \craft\helpers\Queue::push(new DeleteTask([
                    'criteria' => [
                        'sectionId' => $this->criteria['sectionId'],
                        'branch' => $this->criteria['branch'],
                    ],
                    'prop_id' => (int)$property->prop_id
                ]), 4);
            } else {
                \craft\helpers\Queue::push(new PropertyTask([
                    'criteria' => [
                        'sectionId' => $this->criteria['sectionId'],
                        'branch' => $this->criteria['branch'],
                    ],
                    'url' => (string)$property->url
                ]), 4);
                array_push($allProps, (int)$property->prop_id);
            }
        }

        if ($this->criteria['full'] && (int)VebraAltoWrapper::$plugin->getSettings()->shouldAutoDisable === 1) {
            \craft\helpers\Queue::push(new StatusTask([
                'criteria' => [
                    'sectionId' => $this->criteria['sectionId'],
                    'branch' => $this->criteria['branch'],
                ],
                'allProps' => $allProps,
            ]), 5);
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
        return Craft::t('vebra-alto-wrapper', 'Syncing all properties');
    }
}
