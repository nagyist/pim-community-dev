<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\IdentifierGenerator\Infrastructure\Repository;

use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Model\NomenclatureDefinition;
use Akeneo\Pim\Automation\IdentifierGenerator\Domain\Repository\NomenclatureRepository;
use Doctrine\DBAL\Connection;
use Webmozart\Assert\Assert;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SqlNomenclatureRepository implements NomenclatureRepository
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function get(string $propertyCode): ?NomenclatureDefinition
    {
        $sql = <<<SQL
SELECT definition
FROM pim_catalog_identifier_generator_nomenclature_definition
WHERE property_code=:property_code
SQL;
        $definition = $this->connection->fetchOne($sql, [
            'property_code' => $propertyCode,
        ]);

        if (false === $definition) {
            return null;
        }
        Assert::string($definition);

        $jsonResult = \json_decode($definition, true);
        Assert::isArray($jsonResult, sprintf('Invalid JSON: "%s"', $definition));

        return $this->fromNormalized($jsonResult);
    }

    public function update(string $propertyCode, NomenclatureDefinition $nomenclatureDefinition): void
    {
        $this->updateDefinition($propertyCode, $nomenclatureDefinition);

        $this->updateValues($nomenclatureDefinition);
    }

    /**
     * @param array{
     *     operator?: string,
     *     value?: int,
     *     generate_if_empty?: bool
     * } $jsonResult
     */
    private function fromNormalized(array $jsonResult): NomenclatureDefinition
    {
        return new NomenclatureDefinition(
            $jsonResult['operator'] ?? null,
            $jsonResult['value'] ?? null,
            $jsonResult['generate_if_empty'] ?? null,
        );
    }

    /**
     * This method should not exist. It will be removed in CPM-943. It is only for testing for now.
     * @deprecated @TODO
     */
    public function getValue(string $familyCode): ?string
    {
        $sql = <<<SQL
SELECT value FROM pim_catalog_identifier_generator_family_nomenclature n
INNER JOIN pim_catalog_family f ON f.id = n.family_id
WHERE f.code = :family_code
SQL;
        $result = $this->connection->fetchOne($sql, [
            'family_code' => $familyCode,
        ]);

        if (false === $result) {
            return null;
        }

        Assert::stringNotEmpty($result);

        return $result;
    }

    /**
     * @param string[] $familyCodes
     * @return array<string, int>
     */
    private function getExistingFamilyIdsFromFamilyCodes(array $familyCodes): array
    {
        $sql = <<<SQL
SELECT code, id
FROM pim_catalog_family f
WHERE f.code IN (:family_codes)
SQL;

        $result = $this->connection->fetchAllKeyValue($sql, [
            'family_codes' => $familyCodes,
        ], [
            'family_codes' => Connection::PARAM_STR_ARRAY,
        ]);

        $familyIds = [];
        foreach ($result as $familyCode => $familyId) {
            $familyIds[(string) $familyCode] = \intval($familyId);
        }

        return $familyIds;
    }

    /**
     * @param int[] $familyIdsToDelete
     */
    private function deleteNomenclatureValues(array $familyIdsToDelete): void
    {
        $deleteSql = <<<SQL
DELETE FROM pim_catalog_identifier_generator_family_nomenclature 
WHERE family_id IN (:family_ids);
SQL;
        $this->connection->executeStatement($deleteSql, [
            'family_ids' => $familyIdsToDelete,
        ], [
            'family_ids' => Connection::PARAM_INT_ARRAY,
        ]);
    }

    /**
     * @param array{familyId: int, value: string}[] $valuesToUpdateOrInsert
     */
    private function insertOrUpdateNomenclatureValues(array $valuesToUpdateOrInsert): void
    {
        $insertOrUpdateSql = <<<SQL
INSERT INTO pim_catalog_identifier_generator_family_nomenclature (family_id, value)
VALUES {{ values }}
ON DUPLICATE KEY UPDATE value = VALUES(value)
SQL;
        $valuesArray = [];
        for ($i = 0; $i < \count($valuesToUpdateOrInsert); $i++) {
            $valuesArray[] = \sprintf('(:familyId%d, :value%d)', $i, $i);
        }
        $statement = $this->connection->prepare(\strtr(
            $insertOrUpdateSql,
            ['{{ values }}' => \join(',', $valuesArray)]
        ));

        foreach ($valuesToUpdateOrInsert as $i => $valueToUpdateOrInsert) {
            $statement->bindParam(\sprintf('familyId%d', $i), $valueToUpdateOrInsert['familyId']);
            $statement->bindParam(\sprintf('value%d', $i), $valueToUpdateOrInsert['value']);
        }

        $statement->executeStatement();
    }

    private function updateDefinition(string $propertyCode, NomenclatureDefinition $nomenclatureDefinition): void
    {
        $sql = <<<SQL
INSERT INTO pim_catalog_identifier_generator_nomenclature_definition (property_code, definition)
VALUES(:property_code, :definition)
ON DUPLICATE KEY UPDATE definition = :definition
SQL;

        $this->connection->executeStatement($sql, [
            'property_code' => $propertyCode,
            'definition' => \json_encode($nomenclatureDefinition->normalizeForDatabase()),
        ]);
    }

    private function updateValues(NomenclatureDefinition $nomenclatureDefinition): void
    {
        $familyIds = $this->getExistingFamilyIdsFromFamilyCodes(\array_unique(\array_keys($nomenclatureDefinition->values())));

        $valuesToUpdateOrInsert = [];
        $familyIdsToDelete = [];
        foreach ($nomenclatureDefinition->values() as $familyCode => $value) {
            $familyId = $familyIds[$familyCode] ?? null;
            if ($familyId) {
                if (null === $value || '' === $value) {
                    $familyIdsToDelete[] = $familyIds[$familyCode];
                } else {
                    $valuesToUpdateOrInsert[] = [
                        'familyId' => $familyIds[$familyCode],
                        'value' => $value,
                    ];
                }
            }
        }

        if (\count($familyIdsToDelete)) {
            $this->deleteNomenclatureValues($familyIdsToDelete);
        }

        if (\count($valuesToUpdateOrInsert)) {
            $this->insertOrUpdateNomenclatureValues($valuesToUpdateOrInsert);
        }
    }
}