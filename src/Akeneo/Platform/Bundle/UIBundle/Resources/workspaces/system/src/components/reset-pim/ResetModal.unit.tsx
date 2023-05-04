import React from 'react';
import userEvent from '@testing-library/user-event';
import {act, screen} from '@testing-library/react';
import {renderWithProviders} from '@akeneo-pim-community/shared';
import {ResetModal} from './ResetModal';

jest.mock('@akeneo-pim-community/system/src/hooks/useResetInstance', () => ({
  useResetInstance: () => [false, jest.fn()],
}));

test('it can be cancelled', () => {
  const handleCancel = jest.fn();

  renderWithProviders(<ResetModal onConfirm={jest.fn()} onCancel={handleCancel} />);

  userEvent.click(screen.getByTitle('pim_common.close'));

  expect(handleCancel).toHaveBeenCalled();
});

test('it can be confirmed after going through every steps and confirming', async () => {
  const handleConfirm = jest.fn();
  const handleRedirect = jest.fn();

  // @ts-ignore
  delete window.location;
  window.location = {
    ...window.location,
    assign: handleRedirect,
  };

  renderWithProviders(<ResetModal onCancel={jest.fn()} onConfirm={handleConfirm} />);

  userEvent.click(screen.getByText('pim_common.next'));
  userEvent.type(
    screen.getByLabelText('pim_system.reset_pim.modal.confirmation_phrase'),
    'pim_system.reset_pim.modal.confirmation_word'
  );

  await act(async () => {
    await userEvent.click(screen.getByText('pim_system.reset_pim.button.confirm'));
  });

  expect(handleConfirm).toHaveBeenCalled();
  expect(handleRedirect).toHaveBeenCalled();
});

test('it cannot be confirmed if the confirmation word is incorrect', () => {
  const handleConfirm = jest.fn();

  renderWithProviders(<ResetModal onCancel={jest.fn()} onConfirm={handleConfirm} />);

  userEvent.click(screen.getByText('pim_common.next'));
  userEvent.type(screen.getByLabelText('pim_system.reset_pim.modal.confirmation_phrase'), 'jambon');
  userEvent.type(screen.getByLabelText('pim_system.reset_pim.modal.confirmation_phrase'), '{enter}');

  expect(screen.getByText('pim_system.reset_pim.button.confirm')).toBeDisabled();
  expect(handleConfirm).not.toHaveBeenCalled();
});
