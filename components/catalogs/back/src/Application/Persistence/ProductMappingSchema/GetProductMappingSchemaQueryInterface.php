<?php

declare(strict_types=1);

namespace Akeneo\Catalogs\Application\Persistence\ProductMappingSchema;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
interface GetProductMappingSchemaQueryInterface
{
    /**
     * @return array{
     *      properties: array<array-key, mixed>,
     *      required?: string[]
     * }
     */
    public function execute(string $catalogId): array;
}
