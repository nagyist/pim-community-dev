import React, {FC} from 'react';
import {Helper, MultiSelectInput} from 'akeneo-design-system';
import {useGetFamilies, usePaginatedFamilies} from '../hooks/useFamilies';
import {getLabel, useTranslate, useUserContext} from '@akeneo-pim-community/shared';
import {Family, FamilyCode} from '../models';
import {Unauthorized} from '../errors';

type FamiliesSelectorProps = {
  familyCodes: FamilyCode[],
  onChange: (familyCodes: FamilyCode[]) => void;
};

const FamiliesSelector: FC<FamiliesSelectorProps> = ({familyCodes, onChange}) => {
  const translate = useTranslate();
  const userContext = useUserContext();
  const catalogLocale = userContext.get('catalogLocale');
  const {families, handleNextPage, handleSearchChange} = usePaginatedFamilies();
  const {data: selectedFamilies, isLoading, error} = useGetFamilies({codes: familyCodes});

  // Avoid blinking of values when selecting a new one
  const [debouncedSelectedFamilies, setDebouncedSelectedFamilies] = React.useState<Family[]>([]);
  React.useEffect(() => {
    if (!isLoading) {
      setDebouncedSelectedFamilies(selectedFamilies as Family[]);
    }
  }, [selectedFamilies, isLoading]);

  const [debouncedInvalidFamilyCodes, setDebouncedInvalidFamilyCodes] = React.useState<FamilyCode[]>([]);
  React.useEffect(() => {
    if (!isLoading && !error) {
      setDebouncedInvalidFamilyCodes(familyCodes
        .filter(code => !(selectedFamilies as Family[]).map(f => f.code).includes(code)));
    }
  }, [selectedFamilies, isLoading, error]);

  const getFamiliesList = [
    ...(families || []),
    ...(debouncedSelectedFamilies || []).filter(family => !(families || []).map(f => f.code).includes(family.code))
  ];

  if (error) {
    if (error instanceof Unauthorized) {
      return <Helper level={'error'}>{translate('pim_error.unauthorized_list_families')}</Helper>;
    }
    return <Helper level={'error'}>{translate('pim_error.general')}</Helper>;
  }

  return <MultiSelectInput
    emptyResultLabel={translate('pim_common.no_result')}
    placeholder="Please select at least one family"
    removeLabel={translate('pim_common.remove')}
    openLabel={translate('pim_common.open')}
    onNextPage={handleNextPage}
    onSearchChange={handleSearchChange}
    onChange={onChange}
    value={familyCodes}
    invalidValue={debouncedInvalidFamilyCodes}
  >
    {getFamiliesList.map(family => (
      <MultiSelectInput.Option value={family.code} key={family.code}>
        {getLabel(family.labels, catalogLocale, family.code)}
    </MultiSelectInput.Option>))}
  </MultiSelectInput>;
};

export {FamiliesSelector};