<?php

/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace madebyfield\vebraaltowrapper\variables;

use Craft;
use madebyfield\vebraaltowrapper\VebraAltoWrapper;

/**
 * Entry Count Variable
 */
class VebraAltoWrapperVariable
{
    public function getAllLinkModels()
    {
        return VebraAltoWrapper::getInstance()->vebraAlto->getAllLinkModels();
    }
    public function getLinkByField($sectionId, $fieldHandle)
    {
        $linkModel = VebraAltoWrapper::getInstance()->vebraAlto->getFieldMapping($sectionId);
        $fieldMapping = (array) json_decode($linkModel->fieldMapping);
        if (array_key_exists($fieldHandle, $fieldMapping)) {
            return $fieldMapping[$fieldHandle];
        } else {
            return '';
        }
    }
    public function getSchema()
    {
        return array(
            '' => 'Don’t import',
            'address,county' => 'address,county',
            'address,custom_location' => 'address,custom_location',
            'address,display' => 'address,display',
            'address,locality' => 'address,locality',
            'address,name' => 'address,name',
            'address,postcode' => 'address,postcode',
            'address,street' => 'address,street',
            'address,town' => 'address,town',
            'available' => 'available',
            'bathrooms' => 'bathrooms',
            'bedrooms' => 'bedrooms',
            'brochures' => 'brochures',
            'bullets,bullet' => 'bullets,bullet',
            'comm_rent' => 'comm_rent',
            'commission' => 'commission',
            'custom_status' => 'custom_status',
            'description' => 'description',
            'easting' => 'easting',
            'energy_ratings' => 'energy_ratings',
            'floorplans' => 'floorplans',
            'furnished' => 'furnished',
            'garden' => 'garden',
            'groundrent' => 'groundrent',
            'images' => 'images',
            'instructed' => 'instructed',
            'latitude' => 'latitude',
            'leaseend' => 'leaseend',
            'let_bond' => 'let_bond',
            'LetOrSale(category)' => 'LetOrSale(category)',
            'longitude' => 'longitude',
            'measurements' => 'measurements',
            'newbuild' => 'newbuild',
            'northing' => 'northing',
            'paragraphs' => 'paragraphs',
            'parish' => 'parish',
            'parking' => 'parking',
            'premium' => 'premium',
            'price' => 'price',
            'receptions' => 'receptions',
            'reference,agents' => 'reference,agents',
            'reference,software' => 'reference,software',
            'rentalfees' => 'rentalfees',
            'rm_let_type_id' => 'rm_let_type_id',
            'rm_qualifier' => 'rm_qualifier',
            'rm_type' => 'rm_type',
            'service_charge' => 'service_charge',
            'solddate' => 'solddate',
            'soldprice' => 'soldprice',
            'tenure' => 'tenure',
            'type,0' => 'type,0',
            'type,1' => 'type,1',
            'uploaded' => 'uploaded',
            'userfield1' => 'userfield1',
            'userfield2' => 'userfield2',
            'web_status' => 'web_status',
        );
    }
    public function getAllBranches()
    {
        $branches = VebraAltoWrapper::getInstance()->vebraAlto->getBranch();
        $options = [];

        if (gettype($branches) !== 'NULL') {
            foreach ($branches as $branch) {
                if ((string) $branch->name == '') {
                    $options[(int) $branch->branchid . '-noname'] = $branch->branchid;
                } else {
                    $options[(string) $branch->name] = $branch->name;
                }
            }
        } else {
            echo '<script>window.location = "/admin";</script>';
            Craft::$app->session->setFlash('error', "Cannot connect to Vebra please try again.");
        }

        return $options;
    }
}
