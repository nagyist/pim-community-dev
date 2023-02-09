import React, {useRef, useState} from 'react';
import {AttributeGroup} from '../../../models';
import {Button, TextInput, useBooleanState, Field, useAutoFocus} from 'akeneo-design-system';
import {DeleteModal, useTranslate} from '@akeneo-pim-community/shared';

type MassDeleteAttributeGroupsProps = {
  attributeGroups: AttributeGroup[];
};
const MassDeleteAttributeGroups = ({attributeGroups}: MassDeleteAttributeGroupsProps) => {
  const translate = useTranslate();
  const [isMassDeleteModalOpen, openMassDeleteModal, closeMassDeleteModal] = useBooleanState(false);
  const [confirmationText, setConfirmationText] = useState<string>('');
  const isValid = false;
  const inputRef = useRef<HTMLInputElement>(null);

  useAutoFocus(inputRef);

  const handleConfirm = async () => {
    if (!isValid) return;

    //onConfirm();
  };

  return (
    <>
      <Button level="danger" onClick={() => openMassDeleteModal()}>
        Delete
      </Button>
      {isMassDeleteModalOpen && null !== attributeGroups && (
        <DeleteModal
          title={translate('pim_enrich.entity.attribute_group.mass_delete.title')}
          onConfirm={handleConfirm}
          onCancel={() => closeMassDeleteModal()}
          canConfirmDelete={isValid}
        >
          <p>
            {translate(
              'pim_enrich.entity.attribute_group.mass_delete.confirm',
              {assetCount: attributeGroups.length},
              attributeGroups.length
            )}
          </p>
          <p>
            {translate('pim_enrich.entity.attribute_group.mass_delete.confirmation_phrase', {
              confirmation_word: translate('pim_enrich.entity.attribute_group.mass_delete.confirmation_word'),
            })}
          </p>
          <Field label={translate('pim_enrich.entity.attribute_group.mass_delete.confirm_label')}>
            <TextInput
              ref={inputRef}
              value={confirmationText}
              onChange={setConfirmationText}
              onSubmit={handleConfirm}
            />
          </Field>
        </DeleteModal>
      )}
    </>
  );
};

export {MassDeleteAttributeGroups};
