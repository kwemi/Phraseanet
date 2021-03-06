<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

abstract class AbstractConfigurationPanel implements ConfigurationPanelInterface
{
    /** @var PropertyAccess */
    protected $conf;

    public function __construct(PropertyAccess $conf)
    {
        $this->conf = $conf;
    }

    /**
     * @param \databox[] $databoxes
     * @return array
     */
    public function getAvailableDateFields(array $databoxes)
    {
        $date_fields = [];

        foreach ($databoxes as $databox) {
            /** @var \databox_field $field */
            foreach ($databox->get_meta_structure() as $field) {
                if ($field->get_type() !== \databox_field::TYPE_DATE) {
                    continue;
                }

                $date_fields[] = $field->get_name();
            }
        }

        return $date_fields;
    }

    /**
     * {@inheritdoc}
     */
    public function saveConfiguration(array $configuration)
    {
        $this->conf->set(['main', 'search-engine', 'options'], $configuration);

        return $this;
    }
}
