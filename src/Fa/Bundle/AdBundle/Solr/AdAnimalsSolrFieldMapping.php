<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\AdBundle\Solr;

/**
 * This interface is used to define constant for ad solr fields for animals.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version v1.0
 */

interface AdAnimalsSolrFieldMapping extends AdSolrFieldMapping
{
    // Animal fields
    const BREED_ID = 'a_a_breed_id_i';

    const GENDER_ID = 'a_a_gender_id_ag';

    const COLOUR_ID = 'a_a_colour_id_i';

    const SPECIES_ID = 'a_a_species_id_i';

    const AGE_ID = 'a_a_age_id_i';

    const CONDITION_ID = 'a_a_condition_id_i';

    const HEIGHT_ID = 'a_a_height_id_i';

    const META_DATA = 'a_f_meta_data_desc';
}
