import { $, browser, by, element } from 'protractor';
import { clickWhenClickable, CLSelect, Form, wait } from 'utils/utils';


class AddonRelationsEditor extends Form {
    startButton = element(by.cssContainingText('button', 'Add relation'));
    addonSelect = new CLSelect($('input#addon_id'));
    productSelect = new CLSelect($('input#product_id'));
    confirmButton = element(by.cssContainingText('.md-button-content', 'OK, Remove item'));

    constructor() {
        super($('.cl-whmcs'));
    }

    goToTab() {
        return browser.get(`/admin/addonmodules.php?module=CloudLinuxAddon#/addon-relations`);
    }

    async add(addon: string, product: string) {
        await this.goToTab();
        await clickWhenClickable(this.startButton);
        await this.addonSelect.choose(addon);
        await this.productSelect.choose(product);
        await this.submitForm();
        await wait.forElementToBeAbsent(this.submitButton);
        await wait.forElement(this.getDeleteIcon(addon, product));
    }

    async remove(addon: string, product: string) {
        await this.goToTab();
        const deleteIcon = this.getDeleteIcon(addon, product);
        await clickWhenClickable(deleteIcon);
        await clickWhenClickable(this.confirmButton);
        await wait.forElementToBeAbsent(deleteIcon);
    }

    getDeleteIcon(addon: string, product: string) {
        return element(by.xpath(
            `//tr` +
            `[td[2]//*[contains(text(), '${addon}')]]` +
            `[td[3]//*[contains(text(), '${product}')]]` +
            `//i[contains(text(), 'delete')]`,
        ));
    }
}

export const addonRelationsEditor = new AddonRelationsEditor();

class ConfigurableOptionRelationEditor extends Form {
    startButton = element(by.cssContainingText('button', 'Add relation'));
    productSelect = new CLSelect($('input#product_id'));
    optionGroupSelect = new CLSelect($('input#option_group_id'));
    optionSelect = new CLSelect($('input#option_id'));
    confirmButton = element(by.cssContainingText('.md-button-content', 'OK, Remove item'));

    constructor() {
        super($('.cl-whmcs'));
    }

    goToTab() {
        return browser.get('admin/addonmodules.php?module=CloudLinuxAddon#/option-relations');
    }
    async add(params: ConfigurableOptionEditorParams) {
        const {product, optionGroup, option} = params;

        await this.goToTab();
        await clickWhenClickable(this.startButton);
        await this.productSelect.choose(product);
        await this.optionGroupSelect.choose(optionGroup);
        await this.optionSelect.choose(option);

        await this.submitForm();
        await wait.forElement(this.getDeleteIcon(params));
    }
    async remove(params: ConfigurableOptionEditorParams) {
        await this.goToTab();
        const deleteIcon = this.getDeleteIcon(params);
        await clickWhenClickable(deleteIcon);
        await clickWhenClickable(this.confirmButton);
        await wait.forElementToBeAbsent(deleteIcon);
    }

    getDeleteIcon({product, option, optionGroup}: ConfigurableOptionEditorParams) {
        return element(by.xpath(
            `//tr` +
            `[td[2]//*[contains(text(), '${product}')]]` +
            `[td[3]//*[contains(text(), '${optionGroup}')]]` +
            `[td[4]//*[contains(text(), '${option}')]]` +
            `//i[contains(text(), 'delete')]`,
        ));
    }
}
export type ConfigurableOptionEditorParams = {
    product: string,
    optionGroup: string,
    option: string,
};

export const configurableOptionEditor = new ConfigurableOptionRelationEditor();

class ProductRelationsEditor extends Form {
    startButton = element(by.cssContainingText('button', 'Add relation'));
    mainProductSelect = new CLSelect($('input#non_cl_product_id'));
    relatedProductSelect = new CLSelect($('input#product_id'));

    constructor() {
        super($('.cl-whmcs'));
    }

    goToTab() {
        return browser.get(`/admin/addonmodules.php?module=CloudLinuxAddon#/product-relations`);
    }

    async add(mainProduct: string, relatedProduct: string) {
        await this.goToTab();
        await clickWhenClickable(this.startButton);
        await this.mainProductSelect.choose(mainProduct);
        await this.relatedProductSelect.choose(relatedProduct);
        await this.submitForm();
        await wait.forElementToBeAbsent(this.submitButton);
        await wait.forElement(this.getDeleteIcon(mainProduct, relatedProduct));
    }

    getDeleteIcon(mainProduct: string, relatedProduct: string) {
        return element(by.xpath(
            `//tr` +
            `[td[2]//*[contains(text(), '${mainProduct}')]]` +
            `[td[3]//*[contains(text(), '${relatedProduct}')]]` +
            `//i[contains(text(), 'delete')]`,
        ));
    }
}

export const productRelationsEditor = new ProductRelationsEditor();
