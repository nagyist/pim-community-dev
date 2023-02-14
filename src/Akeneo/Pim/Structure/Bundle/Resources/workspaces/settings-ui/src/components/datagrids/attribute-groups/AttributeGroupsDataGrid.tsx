import React, {FC, useEffect, useRef, useState} from 'react';
import {AttributeGroup} from '../../../models';
import {NoResults} from '../../shared';
import {Search, useAutoFocus, Table, Badge, Toolbar, Checkbox, useSelection} from 'akeneo-design-system';
import {getLabel} from 'pimui/js/i18n';
import {useAttributeGroupPermissions, useAttributeGroupsIndexState, useFilteredAttributeGroups} from '../../../hooks';
import {useDebounceCallback, useTranslate, useFeatureFlags, useUserContext} from '@akeneo-pim-community/shared';
import {MassDeleteAttributeGroups} from './MassDeleteAttributeGroups';
import styled from 'styled-components';

type Props = {
  attributeGroups: AttributeGroup[];
  onGroupCountChange: (newGroupCount: number) => void;
};

const Wrapper = styled.div`
  display: flex;
  flex-direction: column;
`;

const ToolbarWrapper = styled.div`
  position: absolute;
  bottom: 0;
  margin-left: -40px;
  width: 100%;
`;

const InfoTop = styled.div`
  display: flex;
  gap: 15px;
  padding: 38px 20px;
`;

const AttributeGroupsDataGrid: FC<Props> = ({attributeGroups, onGroupCountChange}) => {
  const [selection, selectionState, isItemSelected, onSelectionChange, onSelectAllChange, selectedCount] =
    useSelection<AttributeGroup>(attributeGroups.length);
  const {refreshOrder, selectAttributeGroup, isSelected} = useAttributeGroupsIndexState();
  const {sortGranted} = useAttributeGroupPermissions();
  const userContext = useUserContext();
  const {filteredGroups, search} = useFilteredAttributeGroups(attributeGroups);
  const translate = useTranslate();
  const [searchString, setSearchString] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const featureFlags = useFeatureFlags();

  useAutoFocus(inputRef);

  const debouncedSearch = useDebounceCallback(search, 300);

  const onSearch = (searchValue: string) => {
    setSearchString(searchValue);
    debouncedSearch(searchValue);
  };

  useEffect(() => {
    onGroupCountChange(filteredGroups.length);
  }, [filteredGroups.length]);

  return (
    <Wrapper>
      <Search
        sticky={0}
        placeholder={translate('pim_common.search')}
        searchValue={searchString}
        onSearchChange={onSearch}
        inputRef={inputRef}
      >
        <Search.ResultCount>
          {translate('pim_common.result_count', {itemsCount: filteredGroups.length}, filteredGroups.length)}
        </Search.ResultCount>
      </Search>
      {searchString !== '' && filteredGroups.length === 0 ? (
        <NoResults
          title={translate('pim_enrich.entity.attribute_group.grid.no_search_result')}
          subtitle={translate('pim_datagrid.no_results_subtitle')}
        />
      ) : (
        <>
          <InfoTop>
            <Checkbox checked={selectionState} onChange={() => {}} />
            <p>{translate('pim_enrich.entity.attribute_group.selected', {count: selectedCount}, selectedCount)}</p>
          </InfoTop>
          <Table
            isDragAndDroppable={sortGranted && 'mixed' !== selectionState && !selectionState}
            isSelectable={false}
            onReorder={order => refreshOrder(order.map(index => attributeGroups[index]))}
          >
            <Table.Header>
              <Table.HeaderCell>{translate('pim_enrich.entity.attribute_group.grid.columns.name')}</Table.HeaderCell>
              {featureFlags.isEnabled('data_quality_insights') && (
                <Table.HeaderCell>
                  {translate('akeneo_data_quality_insights.attribute_group.dqi_status')}
                </Table.HeaderCell>
              )}
            </Table.Header>
            <Table.Body>
              {filteredGroups.map(attributeGroup => (
                <Table.Row
                  key={attributeGroup.code}
                  isSelected={isItemSelected(attributeGroup)}
                  onSelectToggle={(selected: boolean) => onSelectionChange(attributeGroup, selected)}
                >
                  <Table.Cell>
                    {getLabel(attributeGroup.labels, userContext.get('catalogLocale'), attributeGroup.code)}
                  </Table.Cell>
                  {featureFlags.isEnabled('data_quality_insights') && (
                    <Table.Cell>
                      <Badge level={attributeGroup.is_dqi_activated ? 'primary' : 'danger'}>
                        {translate(
                          `akeneo_data_quality_insights.attribute_group.${
                            attributeGroup.is_dqi_activated ? 'activated' : 'disabled'
                          }`
                        )}
                      </Badge>
                    </Table.Cell>
                  )}
                </Table.Row>
              ))}
            </Table.Body>
          </Table>
        </>
      )}
      <ToolbarWrapper style={{visibility: selectionState ? 'visible' : 'hidden'}}>
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
      </ToolbarWrapper>
    </Wrapper>
  );
};

export {AttributeGroupsDataGrid};
