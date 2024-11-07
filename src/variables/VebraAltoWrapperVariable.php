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
            'features,accessibility_requirements,accessibility' => 'accessibility',
            'features,broadband,supply' => 'broadband,supply',
            'features,broadband,speed' => 'broadband,speed',
            'features,building_safety,issue' => 'building_safety,issue',
            'features,construction,material' => 'construction,material',
            'features,coastal_erosion' => 'coastal_erosion',
            'features,electricity,supply' => 'electricity,supply',
            'features,flooding_risks,flooded_within_last_5_years' => 'flooding_risks,flooded_within_last_5_years',
            'features,flooding_risks,flood_defenses_present' => 'flooding_risks,flood_defenses_present',
            'features,flooding_risks,sources_of_flooding' => 'flooding_risks,sources_of_flooding',
            'features,heating,source' => 'heating,source',
            'features,known_planning_considerations' => 'known_planning_considerations',
            'features,mining_risks,coalfields' => 'mining_risks,coalfields',
            'features,mining_risks,other_mining_activities' => 'mining_risks,other_mining_activities',
            'features,mobile_coverage' => 'mobile_coverage',
            'features,parking,parking_type' => 'parking,parking_type',
            'features,restrictions,conservation_area' => 'restrictions,conservation_area',
            'features,restrictions,lease_restrictions' => 'restrictions,lease_restrictions',
            'features,restrictions,listed_building' => 'restrictions,listed_building',
            'features,restrictions,permitted_development' => 'restrictions,permitted_development',
            'features,restrictions,real_burdens' => 'restrictions,real_burdens',
            'features,restrictions,holiday_home_rental' => 'restrictions,holiday_home_rental',
            'features,restrictions,restrictive_covenant' => 'restrictions,restrictive_covenant',
            'features,restrictions,business_from_property' => 'restrictions,business_from_property',
            'features,restrictions,property_subletting' => 'restrictions,property_subletting',
            'features,restrictions,tree_preservation_order' => 'restrictions,tree_preservation_order',
            'features,restrictions,other' => 'restrictions,other',
            'features,rights_and_easements,right_of_way_public' => 'rights_and_easements,right_of_way_public',
            'features,rights_and_easements,right_of_way_private' => 'rights_and_easements,right_of_way_private',
            'features,rights_and_easements,registered_easements_hmlr' => 'rights_and_easements,registered_easements_hmlr',
            'features,rights_and_easements,servitudes' => 'rights_and_easements,servitudes',
            'features,rights_and_easements,shared_driveway' => 'rights_and_easements,shared_driveway',
            'features,rights_and_easements,loft_access' => 'rights_and_easements,loft_access',
            'features,rights_and_easements,drain_access' => 'rights_and_easements,drain_access',
            'features,rights_and_easements,other' => 'rights_and_easements,other',
            'features,sewerage,supply' => 'sewerage,supply',
            'features,water,supply' => 'water,supply',
        );
    }
    public function getAllBranches()
    {
        $branches = VebraAltoWrapper::getInstance()->vebraAlto->getBranch();
        $options = [];

        if (in_array(gettype($branches), ['array', 'object'])) {
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
