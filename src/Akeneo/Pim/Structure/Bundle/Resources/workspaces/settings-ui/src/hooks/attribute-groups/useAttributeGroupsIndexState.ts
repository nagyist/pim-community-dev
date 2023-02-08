import {useCallback, useContext, useState} from 'react';
import {useRouter} from '@akeneo-pim-community/shared';
import {useRedirectToAttributeGroup} from './useRedirectToAttributeGroup';
import {saveAttributeGroupsOrder} from '../../infrastructure/savers';
import {AttributeGroupsIndexContext, AttributeGroupsIndexState} from '../../components';
import {AttributeGroup} from '../../models';

const useAttributeGroupsIndexState = (): AttributeGroupsIndexState => {
  const context = useContext(AttributeGroupsIndexContext);

  if (!context) {
    throw new Error("[Context]: You are trying to use 'AttributeGroupsIndex' context outside Provider");
  }

  return context;
};

const ATTRIBUTE_GROUP_INDEX_ROUTE = 'pim_structure_attributegroup_rest_index';

const useInitialAttributeGroupsIndexState = (): AttributeGroupsIndexState => {
  const [attributeGroups, setAttributeGroups] = useState<AttributeGroup[]>([]);
  const [isPending, setIsPending] = useState<boolean>(true);
  const [isSelected, setIsSelected] = useState<boolean>(false);
  const router = useRouter();

  const redirect = useRedirectToAttributeGroup();

  const refresh = useCallback(
    (list: AttributeGroup[]) => {
      setAttributeGroups(list);
    },
    [setAttributeGroups]
  );

  const load = useCallback(async () => {
    setIsPending(true);

    const route = router.generate(ATTRIBUTE_GROUP_INDEX_ROUTE);
    const response = await fetch(route);
    const attributeGroups = await response.json();

    setAttributeGroups(attributeGroups);
    setIsPending(false);
  }, [router, setAttributeGroups, setIsPending]);

  const saveOrder = useCallback(async (reorderedGroups: AttributeGroup[]) => {
    const order: {[code: string]: number} = {};

    reorderedGroups.forEach(attributeGroup => {
      order[attributeGroup.code] = attributeGroup.sort_order;
    });

    await saveAttributeGroupsOrder(order);
  }, []);

  const refreshOrder = useCallback(
    async (list: AttributeGroup[]) => {
      const reorderedGroups = list.map((item, index) => {
        return {
          ...item,
          sort_order: index,
        };
      });

      refresh(reorderedGroups);
      await saveOrder(reorderedGroups);
    },
    [refresh, saveOrder]
  );

  const checkIfSelected = useCallback(() => {
    const selectedAttributeGroups = attributeGroups.filter((attributeGroup: AttributeGroup) => attributeGroup.selected);
    setIsSelected(selectedAttributeGroups.length > 0);
  }, [attributeGroups, setIsSelected]);

  const selectAttributeGroup = useCallback(
    (selectedAttributeGroup: AttributeGroup) => {
      refresh(
        attributeGroups.map((attributeGroup: AttributeGroup) => {
          if (attributeGroup.code === selectedAttributeGroup.code) {
            attributeGroup.selected = !attributeGroup.selected;
          }

          return attributeGroup;
        })
      );
      checkIfSelected();
    },
    [attributeGroups, refresh, checkIfSelected]
  );

  return {
    attributeGroups,
    isSelected,
    load,
    saveOrder,
    redirect,
    refresh,
    refreshOrder,
    isPending,
    selectAttributeGroup,
  };
};

export {useAttributeGroupsIndexState, useInitialAttributeGroupsIndexState, AttributeGroupsIndexState};
