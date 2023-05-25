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

namespace madebyfield\vebraaltowrapper\models;

use madebyfield\vebraaltowrapper\VebraAltoWrapper;

use Craft;
use craft\base\Model;

class LinkModel extends Model
{
    // Public Properties
    // =========================================================================
    /**
     * @var int|null ID
     */
    public $id;
    /**
     * @var int|null Entry ID
     */
    public $sectionId;

    public $branch;

    public $fieldMapping;
}