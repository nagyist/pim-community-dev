import React, {FC, useEffect, useState} from 'react';
import {PageContent, PageHeader, useRoute, useTranslate, PimView} from '@akeneo-pim-community/shared';
import {AttributeGroupsCreateButton, AttributeGroupsDataGrid, MassDeleteAttributeGroups} from '../components';
import {useAttributeGroupsIndexState} from '../hooks';
import {Breadcrumb, Checkbox, Toolbar, useSelection} from 'akeneo-design-system';
import {AttributeGroup} from '../models';

const AttributeGroupsIndex: FC = () => {
  const {attributeGroups, load, isPending} = useAttributeGroupsIndexState();
  const translate = useTranslate();
  const settingsHomePageRoute = `#${useRoute('pim_settings_index')}`;

  const [groupCount, setGroupCount] = useState<number>(attributeGroups.length);

  useEffect(() => {
    (async () => {
      await load();
    })();
  }, [load]);

  return (
    <>
      <PageHeader showPlaceholder={isPending}>
        <PageHeader.Breadcrumb>
          <Breadcrumb>
            <Breadcrumb.Step href={`#${settingsHomePageRoute}`}>{translate('pim_menu.tab.settings')}</Breadcrumb.Step>
            <Breadcrumb.Step>{translate('pim_enrich.entity.attribute_group.plural_label')}</Breadcrumb.Step>
          </Breadcrumb>
        </PageHeader.Breadcrumb>
        <PageHeader.UserActions>
          <PimView
            viewName="pim-menu-user-navigation"
            className="AknTitleContainer-userMenuContainer AknTitleContainer-userMenu"
          />
        </PageHeader.UserActions>
        <PageHeader.Actions>
          <AttributeGroupsCreateButton />
        </PageHeader.Actions>
        <PageHeader.Title>
          {translate('pim_enrich.entity.attribute_group.result_count', {count: groupCount.toString()}, groupCount)}
        </PageHeader.Title>
      </PageHeader>
      <PageContent>
        <AttributeGroupsDataGrid attributeGroups={attributeGroups} onGroupCountChange={setGroupCount} />
      </PageContent>
    </>
  );
};

export {AttributeGroupsIndex};
