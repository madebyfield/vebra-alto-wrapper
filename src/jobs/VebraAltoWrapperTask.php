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

        $propertyList = VebraAltoWrapper::getInstance()->vebraAlto->connect($branch->url . '/property')['response']['property'];

        $allProps = [];
        foreach ($propertyList as $propertyKey => $property) {
            $this->setProgress($queue, $propertyKey / count($propertyList));

            $property = VebraAltoWrapper::getInstance()->vebraAlto->connect($property->url)['response'];
            $property = json_decode(json_encode($property), TRUE);

            $title = $property['address']['display'];

            if (empty($title)) continue;

            $allProps = array_merge($allProps, [$property]);

            $ref = (string)$property['@attributes']['id'];
            $slug = $title ? StringHelper::toKebabCase($title) . '-' . $ref : $ref;
            $this->vebraLog('Adding property ' . $title);

            $fields = array(
                'title' => $title,
                'reference' => $ref,
                'slug' => $slug,
                'postDate' => DateTimeHelper::toDateTime($property['uploaded']),
            );

            foreach ($fieldMapping as $craftField => $vebraField) {
                switch ($vebraField) {
                    case 'parish':
                        $this->vebraLog('Creating parish categories');
                        $ids = [];
                        $cats = VebraAltoWrapper::getInstance()->vebraAlto->searchCategoriesByTitle((string)$property['address']['town']);
                        foreach ($cats as $cat) {
                            $ids[] = $cat->id;
                        }
                        $fields[$craftField] = $ids;
                        break;
                    case 'LetOrSale(category)':
                        if ((int)$property['web_status'] > 99) {
                            //letting
                            $cat = Category::find()
                                ->title('For Let')
                                ->all();
                        } else {
                            //sales
                            $cat = Category::find()
                                ->title('For Sale')
                                ->all();
                        }
                        if (count($cat) > 0) {
                            $fields[$craftField] = [$cat[0]->id];
                        }
                        break;
                    case 'measurements':
                        $measure = [];

                        if (VebraAltoWrapper::getInstance()->vebraAlto->findKey($property['paragraphs'], 'paragraph')) {
                            $paragraphs = $property['paragraphs'];

                            foreach ($paragraphs as $paragraph) {
                                if (gettype($paragraph) == 'array') {
                                    if (VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'metric') && VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'name') && VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'text')) {
                                        $name = $paragraph['name'];
                                        $dimensions = $paragraph['dimensions']['metric'];
                                        $text = $paragraph['text'];

                                        if (gettype($name) != 'array' && gettype($dimensions) != 'array' && gettype($text) != 'array') {
                                            $measure[] = $paragraph['name'] . ' | ' . $paragraph['dimensions']['metric'] . ' | ' . $paragraph['text'];
                                        }
                                    }
                                }
                            }
                            $fields[$craftField] = join('@', $measure);
                        }

                        break;
                    case 'paragraphs':
                        $html = '';

                        if (VebraAltoWrapper::getInstance()->vebraAlto->findKey($property['paragraphs'], 'paragraph')) {
                            $paragraphs = $property['paragraphs'];

                            foreach ($paragraphs as $paragraph) {

                                if (gettype($paragraph) == 'array') {
                                    if (VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'metric') && VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'name') && VebraAltoWrapper::getInstance()->vebraAlto->findKey($paragraph, 'text')) {
                                        $name = $paragraph['name'];
                                        $text = $paragraph['text'];

                                        /*
                                        if (gettype($name) == 'array') {
                                            $html .= '<h2>' . $name . '</h2>';
                                        }
                                        */

                                        if (gettype($text) != 'array') {
                                            $html .= '<p>' . str_replace('<br/><br/>', '</p><p>', str_replace('<br /><br />', '</p><p>', str_replace('<br><br>', '</p><p>', $paragraph['text']))) . '</p>';
                                        }
                                    }
                                }
                            }
                            $fields[$craftField] = $html;
                        }

                        break;
                    case 'images':
                        $images = VebraAltoWrapper::getInstance()->vebraAlto->getImages($property['files'], $title . '-' . $ref);
                        $fields[$craftField] = $images;
                        break;
                    case 'floorplans':
                        $floorplans = VebraAltoWrapper::getInstance()->vebraAlto->getFloorplans($property['files'], $title . '-' . $ref);
                        $fields[$craftField] = $floorplans;
                        break;
                    case 'energy_ratings':
                        $energyRatings = VebraAltoWrapper::getInstance()->vebraAlto->getEnergyRatings($property['files'], $title . '-' . $ref);
                        $fields[$craftField] = $energyRatings;
                        break;
                    case 'brochures':
                        $brochures = VebraAltoWrapper::getInstance()->vebraAlto->getBrochures($property['files'], $title . '-' . $ref);
                        $fields[$craftField] = $brochures;
                        break;
                    default:
                        if (strlen($vebraField) > 0) {
                            if (strpos($vebraField, ',') !== false) {
                                $value = VebraAltoWrapper::getInstance()->vebraAlto->getArrayValueByCsv($vebraField, $property);
                            } else {
                                $value = $property[$vebraField];
                            }

                            $fields[$craftField] = is_array($value) ? join('|', $value) : $value;
                        }
                }
            }

            $entry = Entry::find()
                ->sectionId($sectionId)
                //->title($title)
                //->reference($ref)
                ->reference(['or', (int)$ref, (string)$ref])
                ->status(null)
                ->all();
            
            if (empty($entry)) {
                // $this->vebraLog('Attempting to save entry ' . json_encode($fields));
                VebraAltoWrapper::getInstance()->vebraAlto->saveNewEntry($sectionId, $fields);
            } else {
                // $this->vebraLog('Attempting to update entry ' . json_encode($fields));
                VebraAltoWrapper::getInstance()->vebraAlto->updateEntry($entry[0], $fields);
            }
        }
        
        $allEntries = Entry::find()
        ->sectionId($sectionId)
        ->limit(null)
        ->status(null)
        ->all();

        foreach ($allEntries as $entry) {
            $isOnVebra = false;
            
            foreach ($allProps as $property) {
                //if ((string)$entry->title == (string)$property['address']['display']) {
                if ((string)$entry->reference == (string)$property['@attributes']['id']) {
                    $isOnVebra = true;
                }
            }

            if (!$isOnVebra) {
                if ((int)VebraAltoWrapper::$plugin->getSettings()->shouldAutoDisable === 1) $entry->enabled = false;
                $entry->webStatus = 2;
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
        return Craft::t('vebra-alto-wrapper', 'Syncing all properties');
    }
}
