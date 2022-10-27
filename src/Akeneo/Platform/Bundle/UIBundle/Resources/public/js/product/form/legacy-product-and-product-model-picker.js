'use strict';

/**
 # @TODO CPM-739: Remove this file
 */
define([
  'jquery',
  'oro/translator',
  'pim/common/item-picker',
  'pim/fetcher-registry',
  'pim/media-url-generator',
], function ($, __, ItemPicker, FetcherRegistry, MediaUrlGenerator) {
  return ItemPicker.extend({
    /**
     * {@inheritdoc}
     */
    selectModel: function (model) {
      const item =
        model.attributes.document_type === 'product_model'
          ? `product_model;${model.get('identifier')}`
          : `product;${model.get(this.config.columnName)}`;

      this.addItem(item);
    },

    /**
     * {@inheritdoc}
     */
    unselectModel: function (model) {
      this.removeItem(`${model.attributes.document_type};${model.get(this.config.columnName)}`);
    },

    /**
     * {@inheritdoc}
     */
    updateBasket: function () {
      let productIds = [];
      let productModelIds = [];
      this.getItems().forEach(item => {
        const matchProductModel = item.match(/^product_model;(.*)$/);
        if (matchProductModel) {
          productModelIds.push(matchProductModel[1]);
        } else {
          const matchProduct = item.match(/^product;(.*)$/);
          productIds.push(matchProduct[1]);
        }
      });

      $.when(
        FetcherRegistry.getFetcher('product-model').fetchByIdentifiers(productModelIds),
        FetcherRegistry.getFetcher('product').fetchByIdentifiers(productIds)
      ).then(
        function (productModels, products) {
          this.renderBasket(products.concat(productModels));
          this.delegateEvents();
        }.bind(this)
      );
    },

    /**
     * {@inheritdoc}
     */
    imagePathMethod: function (item) {
      let filePath = null;
      if (item.meta.image !== null) {
        filePath = item.meta.image.filePath;
      }

      return MediaUrlGenerator.getMediaShowUrl(filePath, 'thumbnail_small');
    },

    /**
     * {@inheritdoc}
     */
    labelMethod: function (item) {
      return item.meta.label[this.getLocale()];
    },

    /**
     * {@inheritdoc}
     */
    itemCodeMethod: function (item) {
      if (item.code) {
        return `product_model;${item.code}`;
      } else {
        return `product;${item[this.config.columnName]}`;
      }
    },
  });
});