<?php

namespace Akeneo\Pim\Automation\DataQualityInsights\Infrastructure\Connector\Tasklet\MarkEvaluation;

use Akeneo\Pim\Automation\DataQualityInsights\Application\ProductEvaluation\CreateCriteriaEvaluations;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\Query\ProductEvaluation\GetEntityIdsImpactedByAttributeGroupActivationQueryInterface;
use Psr\Log\LoggerInterface;

/**
 * @copyright 2023 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MarkProductModelsEvaluationImpactedByUpdatedAttributeGroupActivationTasklet extends AbstractMarkEvaluationTasklet
{
    public function __construct(
        private readonly CreateCriteriaEvaluations $createCriteriaEvaluations,
        private readonly GetEntityIdsImpactedByAttributeGroupActivationQueryInterface $getProductModelIdsImpactedByAttributeGroupActivationQuery,
        private readonly LoggerInterface $logger,
        private readonly int $bulkSize
    ) {
    }

    public function execute(): void
    {
        $countMarkedProductModels = 0;

        try {
            foreach ($this->getProductModelIdsImpactedByAttributeGroupActivationQuery->updatedSince($this->updatedSince(), $this->bulkSize) as $productModelIdCollection) {
                $this->createCriteriaEvaluations->createAll($productModelIdCollection);
                $countMarkedProductModels += $productModelIdCollection->count();
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to mark product models evaluation impacted by updated attribute group activation',
                [
                    'error_code' => 'failed_to_mark_product_model_evaluation',
                    'error_message' => $exception->getMessage(),
                ]
            );
        }

        $this->stepExecution->setWriteCount($countMarkedProductModels);
    }
}
