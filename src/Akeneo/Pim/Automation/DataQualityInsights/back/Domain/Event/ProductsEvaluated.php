<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\DataQualityInsights\Domain\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class ProductsEvaluated extends Event
{
    public function __construct(
        private array $productIds
    ) {
    }

    public function getProductIds(): array
    {
        return $this->productIds;
    }
}
