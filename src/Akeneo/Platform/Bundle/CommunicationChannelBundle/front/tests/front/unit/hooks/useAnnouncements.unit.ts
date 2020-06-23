import '@testing-library/jest-dom/extend-expect';
import {useAnnouncements} from '@akeneo-pim-community/communication-channel/src/hooks/useAnnouncements';
import {renderHookWithProviders, fetchMockResponseOnce} from '@akeneo-pim-community/shared/tests/front/unit/utils';
import {getExpectedAnnouncements} from '../__mocks__/dataProvider';

const expectedAnnouncements = getExpectedAnnouncements();

afterEach(() => {
  fetchMock.resetMocks();
});

test('It can get all the announcements', async () => {
  fetchMockResponseOnce('/rest/announcements', JSON.stringify({items: expectedAnnouncements}));

  const {result, waitForNextUpdate} = renderHookWithProviders(useAnnouncements);

  expect(result.current.data).toEqual([]);

  await waitForNextUpdate();

  expect(result.current.data).toEqual(expectedAnnouncements);
  expect(result.current.hasError).toEqual(false);
  expect(fetchMock).toHaveBeenCalledWith('/rest/announcements');
});

test('It can validate the announcements from the json', async () => {
  fetchMockResponseOnce('/rest/announcements', JSON.stringify({items: [{invalidProperty: 'invalid_property'}]}));
  console.error = jest.fn();

  const {result, waitForNextUpdate} = renderHookWithProviders(useAnnouncements);

  await waitForNextUpdate();

  expect(result.current.data).toEqual([]);
  expect(result.current.hasError).toEqual(true);
  expect(console.error).toHaveBeenCalledTimes(1);
});
