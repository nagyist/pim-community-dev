<?php

namespace Akeneo\Pim\Enrichment\Bundle\Command\MigrateToUuid;

use Akeneo\Pim\Enrichment\Bundle\Command\MigrateToUuid\Utils\StatusAwareTrait;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MigrateToUuidSetNotNullableUuidColumns implements MigrateToUuidStep
{
    use MigrateToUuidTrait;
    use StatusAwareTrait;

    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger
    ) {
    }

    public function getDescription(): string
    {
        return 'Set not nullable every uuid columns (product_uuid like)';
    }

    public function getName(): string
    {
        return 'set_not_nullable_uuid_columns';
    }

    public function shouldBeExecuted(): bool
    {
        return 0 < $this->getMissingCount();
    }

    public function getMissingCount(): int
    {
        $count = 0;
        foreach (self::TABLES as $tableName => $columnNames) {
            if ($this->tableExists($tableName) && $this->isColumnNullable($tableName, $columnNames[self::UUID_COLUMN_INDEX])) {
                $count++;
            }
        }

        return $count;
    }

    private function isColumnNullable(string $tableName, string $columnName): bool
    {
        $schema = $this->connection->getDatabase();
        $sql = <<<SQL
            SELECT IS_NULLABLE 
            FROM information_schema.columns 
            WHERE table_schema=:schema 
              AND table_name=:tableName
              AND column_name=:columnName;
        SQL;

        $result = $this->connection->fetchOne($sql, [
            'schema' => $schema,
            'tableName' => $tableName,
            'columnName' => $columnName
        ]);

        return $result !== 'NO';
    }

    public function addMissing(Context $context): bool
    {
        $logContext = $context->logContext;

        $updatedItems = 0;
        foreach (self::TABLES as $tableName => $columnNames) {
            $logContext->addContext('substep', $tableName);
            if ($this->tableExists($tableName) && $this->isColumnNullable($tableName, $columnNames[self::UUID_COLUMN_INDEX])) {
                $this->logger->notice(sprintf('Will set uuid column not nullable for %s', $tableName), $logContext->toArray());
                if (!$context->dryRun()) {
                    $this->setUuidColumnNotNullable(
                        $tableName,
                        $columnNames[self::UUID_COLUMN_INDEX]
                    );
                    $this->logger->notice('Substep done', $logContext->toArray(['updated_items_count' => $updatedItems+=1]));
                }
            }
        }

        return true;
    }

    private function setUuidColumnNotNullable(string $tableName, string $uuidColumnName): void
    {
        $sql = <<<SQL
            ALTER TABLE `{table_name}`
            MODIFY {uuid_column_name} BINARY(16) NOT NULL,
            ALGORITHM=INPLACE,
            LOCK=NONE;
        SQL;

        $query = \strtr(
            $sql,
            [
                '{table_name}' => $tableName,
                '{uuid_column_name}' => $uuidColumnName,
            ]
        );

        $this->connection->executeQuery($query);
    }
}
