import React, {FC, useState} from 'react';
import {PageContent, PageHeader, useRoute, useTranslate, PimView} from '@akeneo-pim-community/shared';
import {AttributeGroupsCreateButton, AttributeGroupsDataGrid} from '../components';
import {useGetAttributeGroups} from '../hooks';
import {Breadcrumb} from 'akeneo-design-system';

const AttributeGroupsIndex: FC = () => {
  const {attributeGroups, isPending} = useGetAttributeGroups();
  const translate = useTranslate();
  const settingsHomePageRoute = `#${useRoute('pim_settings_index')}`;

  const [groupCount, setGroupCount] = useState<number>(attributeGroups.length);

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
        <AttributeGroupsDataGrid attributeGroups={attributeGroups} onAttributeGroupCountChange={setGroupCount} />
      </PageContent>
    </>
  );
};

export {AttributeGroupsIndex};
