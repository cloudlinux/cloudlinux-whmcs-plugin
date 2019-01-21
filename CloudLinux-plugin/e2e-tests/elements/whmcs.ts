import { $, browser, by, element, ElementFinder } from 'protractor';
import { Checkbox, clickWhenClickable, Form, HTMLSelect, repeatWhile, scrollToBottom, wait } from 'utils/utils';
import * as cln from 'elements/cln';
import * as options from 'models/configurable-option';
import * as navigation from 'utils/navigation';
import { auth } from 'utils/navigation';
import { Addon } from 'models/addon';
import { AutoSetupOptions, LicenseType, MaxUsers, ProductType, ReadableMaxUsers } from 'models/structure';
import { sqlExec, sqlFind } from 'utils/ssh';


export abstract class Creator extends Form {
    abstract productType: ProductType;

    id: number;
    dbId: number;
    type: LicenseType;
    byKey: boolean;
    keyLimit: number;
    autoSetup: AutoSetupOptions;
    maxUsers: MaxUsers;

    productGroup = new HTMLSelect(this.elByName('gid'));
    productName = this.elByName('productname');
    inputName = this.elByName('name');

    requireDomainCheckbox = this.elByName('showdomainops');
    showOnOrderCheckbox = this.elByName('showorder');

    moduleSettingsTab = this.$('#tabLink3');
    moduleName = new HTMLSelect(this.elByName('servertype'));
    resellerUsername = this.elByName('packageconfigoption[1]');
    licenseType = new HTMLSelect(this.elByName('packageconfigoption[3]'));
    i360MaxUsers = new HTMLSelect(this.elByName('packageconfigoption[7]'));
    autoSetupCheckbox = this.$('#autosetup_order');
    ipRegistrationToken = this.elByName('packageconfigoption[2]');
    useKeyCheckbox = new Checkbox(this.elByName('packageconfigoption[5]'));

    terminateButton = $('#ModuleTerminate-Yes');
    upgradeButton = element(by.xpath('//button[contains(text(),"Upgrade")]'));
    upgradeOrderButton = $('[type=submit]');

    changeButton = $('#clChangeValue');
    changeField = $('#clNewValueInput');
    changeSaveButton = element(by.xpath(`//span[@class='ui-button-text' and contains(text(),'Save')]`));
    userChangeButton = element(by.xpath(`(//a[contains(@class,'cl-btn-change')])[last()]`));
    userChangeField = element(by.xpath(`(//input[contains(@class,'cl-new-value')])[last()]`));
    userChangeSaveButton = element(by.xpath(`(//div[@class='cl-new']/input[@type='submit'])[last()]`));

    serviceSaveButton = $(`input[value='Save Changes']`);
    serviceSuccessMessage = $('div.successbox');

    constructor(item?: {
        licenseType: LicenseType;
        byKey: boolean;
        keyLimit?: number;
        maxUsers?: ReadableMaxUsers;
        autoSetup?: AutoSetupOptions;
    }) {
        super($('#contentarea form'));
        if (item) {
            this.type = item.licenseType;
            this.byKey = item.byKey;
            this.maxUsers = new MaxUsers(item.maxUsers || ReadableMaxUsers.Single);
            this.autoSetup = item.autoSetup || AutoSetupOptions.order;
            this.keyLimit = item.keyLimit || 1;
        }
    }

    abstract async create();
    abstract async update();

    async setup() {
        if (this.type) {
            await this.moduleName.choose('CloudLinuxLicenses');
            await wait.forElement(this.resellerUsername);
            await this.resellerUsername.clear();
            await this.resellerUsername.sendKeys(cln.login);
            await this.licenseType.choose(this.type);
            if (this.type === LicenseType.Imunify360) {
                await this.i360MaxUsers.choose(this.maxUsers.value);
            }
            await this.useKeyCheckbox.switch(this.byKey);
            await this.ipRegistrationToken.clear();
            await this.ipRegistrationToken.sendKeys(cln.secretKey);
        } else {
            await this.moduleName.choose('');
        }
        await clickWhenClickable(this.autoSetupCheckbox);
    }

    async changeLicenseItem(
        item: string, role: navigation.Role = navigation.Role.admin, checkError: boolean = false,
    ) {
        await navigation.auth.withWhmcsUser(async () => {
            let messageEl: ElementFinder;
            let errorMessage: string;

            if (checkError) {
                const searchMessage = this.byKey ?
                    `Key "${item}" not found` : `IP is already used by another product or addon`;
                messageEl = element(by.xpath(`//div[@class='box-error' ` +
                    `and contains(text(), '${searchMessage}')]` +
                    `|//div[contains(@class,'errorbox') ` +
                    `and contains(text(),'${searchMessage}')]`));
                errorMessage = 'Error message should be present and item not changed';
            } else {
                messageEl = element(by.xpath(`//div[@class='box-success' ` +
                    `and contains(text(), 'has been successfully changed')]` +
                    `|//div[@class='successbox' ` +
                    `and contains(text(), 'Your changes have been saved')]`));
                errorMessage = 'Success message should be present and item changed';
            }

            if (this.productType === ProductType.product) {
                await clickWhenClickable(this.getLinkToProduct());
            } else {
                await this.goToAddonPage();
            }

            await clickWhenClickable(this.getChangeButton());
            await this.getChangeField().clear();
            await this.getChangeField().sendKeys(item);
            await this.getChangeSaveButton().click();
            await wait.forElement(messageEl, {errorMessage});
        }, role);
    }

    async checkInCln(item, existence: boolean = true, errorMessage = '') {
        const verb = existence ? 'should' : 'should not';

        if (this.byKey) {
            errorMessage = errorMessage ? errorMessage : `License Key ${verb} exist in CLN`;
            expect(await cln.isLicenseExists({
                type: this.type,
                key: item,
                ...(this.type === LicenseType.Imunify360 ?
                    {maxUsers: this.maxUsers.toCLN(true)} : {}),
            })).toBe(existence, errorMessage);
        } else {
            errorMessage = errorMessage ? errorMessage : `License IP ${verb} exist in CLN`;
            expect(await cln.isLicenseExists({
                type: this.type,
                ip: item,
                ...(this.type === LicenseType.Imunify360 ?
                    {maxUsers: this.maxUsers.toCLN(false)} : {}),
            })).toBe(existence, errorMessage);
        }
    }

    getChangeButton(): ElementFinder {
        return navigation.auth.isAdmin() ? this.changeButton : this.userChangeButton;
    }

    getChangeField(): ElementFinder {
        return navigation.auth.isAdmin() ? this.changeField : this.userChangeField;
    }

    getChangeSaveButton(): ElementFinder {
        return navigation.auth.isAdmin() ? this.changeSaveButton : this.userChangeSaveButton;
    }

}

export const removeProduct = async (name: string) => {
    await browser.get(`admin/configproducts.php`);
    await scrollToBottom();
    await clickWhenClickable(element(by.xpath(
        `//td[contains(text(),'${name}')]/..//img[@alt='Delete']/..`,
    )));
    await browser.sleep(300); // wait for alert
    await browser.switchTo().alert().accept();
};

export class ProductOrderer extends Form {
    addNewOrderButton = element(by.cssContainingText('.clientssummarybox a', 'Add New Order'));
    product = new HTMLSelect(this.elByName('pid[]'));
    acceptOrderButton = element(by.cssContainingText('button', 'Accept Order'));

    constructor() {
        super($('#orderfrm'));
    }

    async create(productName: string, params: {
        configurableOptions?: Array<{
            group: options.ConfigurableOptionGroup,
            option: options.ConfigurableOption | null,
        }>,
        addons?: Addon[],
        updateIP?: boolean,
    } = {updateIP: true}) {
        await this.chooseProduct(productName);
        await browser.sleep(1000);

        if (params.configurableOptions) {
            for (let i of params.configurableOptions) {
                await this.chooseConfigurableOptions(i.group, i.option);
            }
        }
        if (params.addons) {
            for (let i of params.addons) {
                await this.chooseAddon(i);
            }
        }

        await this.order(params.updateIP);
    }

    async order(updateIP: boolean = true) {
        await repeatWhile({
            action: () => this.submitForm(),
            test: async () => await this.submitButton.isPresent(),
        });

        if (updateIP) {
            await this.updateIP();
        }
        await clickWhenClickable(this.acceptOrderButton);
    }

    async chooseProduct(productName: string) {
        await clickWhenClickable(this.addNewOrderButton);
        await this.product.choose(productName);
    }

    async chooseConfigurableOptions(
        group: options.ConfigurableOptionGroup,
        option: options.ConfigurableOption | null,
    ) {
        if (group.optionType === options.OptionTypes.dropdown) {
            const select = this.getDropdownOption(group.fullName);
            await select.choose(option.name);
        }

        if (group.optionType === options.OptionTypes.checkbox) {
            const checkbox = this.getCheckboxOption(group.fullName);
            await checkbox.switch(!!option);
        }

        if (group.optionType === options.OptionTypes.radio) {
            await clickWhenClickable(this.getRadioOption(group.fullName, option.name));
        }
    }

    async chooseAddon(addon: Addon) {
        await clickWhenClickable(this.getAddonCheckbox(addon.fullName));
    }

    getDropdownOption(name: string): HTMLSelect {
        return new HTMLSelect(element(by.xpath(
            `//td[contains(text(),'${name}')]/..//td[2]/select`,
        )));
    }

    getCheckboxOption(name: string): Checkbox {
        return new Checkbox(element(by.xpath(
            `//td[contains(text(),'${name}')]/..//td[2]//input[@type="checkbox"]`,
        )));
    }

    getRadioOption(groupName: string, optionName: string): ElementFinder {
        return element(by.xpath(
            `//td[contains(text(),'${groupName}')]/..//td[2]` +
            `//text()[contains(.,"${optionName}")]/preceding-sibling::input[1]`,
        ));
    }

    getAddonCheckbox(name: string) {
        return element(by.xpath(
            `//td[contains(text(),'Addons')]/..//td[2]/label/` +
            `text()[contains(.,"${name}")]/preceding-sibling::input[1]`,
        ));
    }

    async updateIP() {
        await sqlExec(`UPDATE tblhosting h` +
            ` LEFT JOIN tblclients c ON c.id=h.userid` +
            ` SET h.dedicatedip='77.79.198.35' WHERE c.firstname='e2e'`);
    }
}

export class UserProductOrderer extends ProductOrderer {
    productGroupDbId: number;

    orderContinueButton = $('#btnCompleteProductConfig');
    orderCheckoutButton = $('#checkout');
    orderCompleteButton = $('#btnCompleteOrder');

    constructor() {
        super();
    }

    async order(updateIP: boolean = true) {
        if (await this.orderContinueButton.isPresent()) {
            await clickWhenClickable(this.orderContinueButton);
        }
        await clickWhenClickable(this.orderCheckoutButton);
        if (updateIP) {
            await this.updateIP();
        }
        await clickWhenClickable(this.orderCompleteButton);
    }

    async chooseProduct(productName: string) {
        await this.setProductGroupId();
        await browser.get(`cart.php?gid=${this.productGroupDbId}`);
        await clickWhenClickable(element(
            by.xpath(`//span[contains(text(),'${productName}')]/../../footer/a`)));
    }

    getDropdownOption(name: string): HTMLSelect {
        return new HTMLSelect(element(by.xpath(
            `//label[contains(text(),'${name}')]/following-sibling::select`,
        )));
    }

    getCheckboxOption(name: string): Checkbox {
        return new Checkbox(element(by.xpath(
            `//label[contains(text(),'${name}')]/../label[2]`,
        )));
    }

    getRadioOption(groupName: string, optionName: string): ElementFinder {
        return element(by.xpath(
            `//label[contains(text(),'${groupName}')]/../label[normalize-space(.)='${optionName}']`,
        ));
    }

    getAddonCheckbox(name: string) {
        return element(by.xpath(`//label[normalize-space(.)='${name}']`));
    }

    async setProductGroupId() {
        if (this.productGroupDbId) {
            return;
        }
        this.productGroupDbId = await sqlFind('tblproductgroups', 'name', `e2e`);
    }
}

export const getOrderer = () => auth.isAdmin() ? new ProductOrderer() : new UserProductOrderer();

class OrderEditor {
    orderer = new ProductOrderer();
    terminateButton = $('#ModuleTerminate-Yes');
    async delete(productName: string) {
        await scrollToBottom();
        await clickWhenClickable(this.getLinkToProduct(productName));
        await clickWhenClickable($('#btnTerminate'));
        await browser.sleep(500);
        await clickWhenClickable(this.terminateButton);
        await scrollToBottom();
        await wait.forElementToBeInvisible(this.terminateButton);
        await browser.sleep(500);
        await clickWhenClickable(element(by.cssContainingText('a strong', 'Delete')));
        await clickWhenClickable($('#Delete-Yes'));
    }

    async getLicenseKey(productName: string, rowOffset = 0) {
        await scrollToBottom();
        await clickWhenClickable(this.getLinkToProduct(productName));
        const inputWithKey = $(`tr:nth-child(${13 + rowOffset}) input`);
        return inputWithKey.getAttribute('value');
    }

    private getLinkToProduct(productName: string) {
        return element(by.xpath(
            `//td[contains(text(),'${productName}')]/..//td[2]/a`,
        ));
    }
}
export const orderEditor = new OrderEditor();

class Cleaner {
    allProductsCheckbox = $('input#prodsall');
    addAddonsCheckbox = $('input#addonsall');
    deleteButton = $('.button[name=del]');
    terminateButton = $('.button[name=massterminate]');

    async terminateProducts() {
        await clickWhenClickable(this.allProductsCheckbox);
        await clickWhenClickable(this.addAddonsCheckbox);
        await clickWhenClickable($('#massUpdateItems'));
        await wait.forElementToBeVisible(this.terminateButton);
        await clickWhenClickable(this.terminateButton);
    }

    async deleteProducts() {
        await clickWhenClickable(this.allProductsCheckbox);
        await clickWhenClickable(this.addAddonsCheckbox);
        await clickWhenClickable(this.deleteButton);
    }

    async cleanAll() {
        const productRows = await element.all(by.xpath(
            `//input[@id='prodsall']/ancestor::table[1]/` +
            `descendant::tr[not(contains(.,'No Records Found'))]`)).count();
        const addonsRows = await element.all(by.xpath(
            `//input[@id='addonsall']/ancestor::table[1]/` +
            `descendant::tr[not(contains(.,'No Records Found'))]`)).count();
        if (await productRows <= 1 && await addonsRows <= 1) {
            return;
        }

        await this.terminateProducts();
        await this.deleteProducts();
    }
}

export const cleaner = new Cleaner();
