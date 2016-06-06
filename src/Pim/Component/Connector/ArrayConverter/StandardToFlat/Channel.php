<?php

namespace Pim\Component\Connector\ArrayConverter\StandardToFlat;

use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;

/**
 * Standard to flat array converter for channel
 *
 * @author    Adrien Pétremann <adrien.petremann@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class Channel implements ArrayConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(array $item, array $options = [])
    {
        $convertedItem = [];

        foreach ($item as $field => $data) {
            $convertedItem = $this->convertFields($field, $data, $convertedItem);
        }

        return $convertedItem;
    }

    /**
     * @param string $field
     * @param mixed  $data
     * @param array  $convertedItem
     *
     * @return array
     */
    protected function convertFields($field, $data, array $convertedItem)
    {
        switch ($field) {
            case 'locales':
            case 'currencies':
                $convertedItem[$field] = implode(',', $data);
                break;
            default:
                $convertedItem[$field] = (string) $data;
        }

        return $convertedItem;
    }
}
