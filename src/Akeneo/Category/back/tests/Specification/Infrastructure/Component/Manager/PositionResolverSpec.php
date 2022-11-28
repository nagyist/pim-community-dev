<?php
declare(strict_types=1);

namespace Specification\Akeneo\Category\Infrastructure\Component\Manager;

use Akeneo\Category\Domain\Model\Enrichment\Category;
use Akeneo\Category\Domain\ValueObject\CategoryId;
use Akeneo\Category\Domain\ValueObject\Code;
use Akeneo\Category\Infrastructure\Component\Classification\Model\CategoryInterface;
use Akeneo\Category\Infrastructure\Component\Manager\PositionResolver;
use Akeneo\Category\Infrastructure\Component\Manager\PositionResolverInterface;
use Akeneo\Pim\Enrichment\Component\Category\Query\GetDirectChildrenCategoryCodesInterface;
use Akeneo\Platform\Bundle\FeatureFlagBundle\FeatureFlags;
use PhpSpec\ObjectBehavior;

/**
 * @copyright 2022 Akeneo SAS (https://www.akeneo.com)
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class PositionResolverSpec extends ObjectBehavior
{
    function let(
        GetDirectChildrenCategoryCodesInterface $getDirectChildrenCategoryCodes,
        FeatureFlags $featureFlags
    )
    {
        $this->beConstructedWith($getDirectChildrenCategoryCodes, $featureFlags);
    }

    function it_is_initializable()
    {
        $this->shouldImplement(PositionResolverInterface::class);
        $this->shouldHaveType(PositionResolver::class);
    }

    function it_gets_position_when_category_has_no_parent(CategoryInterface $category)
    {
        $category->isRoot()->willReturn(true);

        $this->getPosition($category)->shouldReturn(1);
    }

    function it_gets_position_with_enriched_category_feature_disabled(
        GetDirectChildrenCategoryCodesInterface $getDirectChildrenCategoryCodes,
        FeatureFlags $featureFlags,
        CategoryInterface $category,
        CategoryInterface $categoryParent
    ) {
        $aCategoryCode = 'categoryC';
        $aCategoryParentId = 1;
        $aListOfParentCategoryChildren = [
            'categoryA' => ['row_num' => 1],
            'categoryB' => ['row_num' => 2],
            'categoryC' => ['row_num' => 3],
        ];

        $featureFlags->isEnabled('enriched_category')->willReturn(false);

        $category->getCode()->willReturn($aCategoryCode);
        $category->isRoot()->willReturn(false);
        $category->getParent()->willReturn($categoryParent);
        $categoryParent->getId()->willReturn($aCategoryParentId);

        $getDirectChildrenCategoryCodes->execute($aCategoryParentId)->willReturn($aListOfParentCategoryChildren);

        $this->getPosition($category)->shouldReturn(3);
    }

    function it_gets_position_with_enriched_category_feature_enabled(
        GetDirectChildrenCategoryCodesInterface $getDirectChildrenCategoryCodes,
        FeatureFlags $featureFlags,
        Category $category
    ) {
        $aCategoryCode = new Code('categoryC');
        $aCategoryParentId = new CategoryId(1);
        $aListOfParentCategoryChildren = [
            'categoryA' => ['row_num' => 1],
            'categoryB' => ['row_num' => 2],
            'categoryC' => ['row_num' => 3],
        ];

        $featureFlags->isEnabled('enriched_category')->willReturn(true);

        $category->getCode()->willReturn($aCategoryCode);
        $category->isRoot()->willReturn(false);
        $category->getParentId()->willReturn($aCategoryParentId);

        $getDirectChildrenCategoryCodes->execute($aCategoryParentId->getValue())->willReturn($aListOfParentCategoryChildren);

        $this->getPosition($category)->shouldReturn(3);
    }
}
