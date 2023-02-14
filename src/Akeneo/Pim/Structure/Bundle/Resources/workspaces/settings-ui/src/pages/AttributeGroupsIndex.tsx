import React, {FC, useEffect, useState} from 'react';
import {PageHeader, useRoute, useTranslate, PimView} from '@akeneo-pim-community/shared';
import {AttributeGroupsCreateButton, AttributeGroupsDataGrid, MassDeleteAttributeGroups} from '../components';
import {useAttributeGroupsIndexState} from '../hooks';
import {Breadcrumb, Checkbox, Toolbar, useSelection} from 'akeneo-design-system';
import {AttributeGroup} from '../models';
import styled from 'styled-components';

const Content = styled('div')`
  overflow-y: auto;
  padding: 0 40px;
`;

const Page = styled('div')`
  height: 100%;
  display: flex;
  flex-direction: column;
`;

const AttributeGroupsIndex: FC = () => {
  const {attributeGroups, load, isPending} = useAttributeGroupsIndexState();
  const [selection, selectionState, isItemSelected, onSelectionChange, onSelectAllChange, selectedCount] =
    useSelection<AttributeGroup>(attributeGroups.length);
  const translate = useTranslate();
  const settingsHomePageRoute = `#${useRoute('pim_settings_index')}`;

  const [groupCount, setGroupCount] = useState<number>(attributeGroups.length);

  useEffect(() => {
    (async () => {
      await load();
    })();
  }, [load]);

  return (
    <Page>
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
      <Content>
        <AttributeGroupsDataGrid
          attributeGroups={attributeGroups}
          onGroupCountChange={setGroupCount}
          isItemSelected={isItemSelected}
          selectionState={selectionState}
          onSelectionChange={onSelectionChange}
          selectedCount={selectedCount}
        />
      </Content>
      <Toolbar isVisible={!!selectionState}>
        <Toolbar.SelectionContainer>
          <Checkbox checked={selectionState} onChange={() => {}} />
        </Toolbar.SelectionContainer>
        <Toolbar.LabelContainer>
          {translate('pim_enrich.entity.attribute_group.selected', {count: selectedCount}, selectedCount)}
        </Toolbar.LabelContainer>
        <Toolbar.ActionsContainer>
          <MassDeleteAttributeGroups attributeGroups={selection.collection} />
        </Toolbar.ActionsContainer>
      </Toolbar>
    </Page>
  );
};

export {AttributeGroupsIndex};
