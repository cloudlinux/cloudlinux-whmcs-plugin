import { $, browser, by, element, ElementFinder }  from 'protractor';
import * as whmcs from 'elements/whmcs';
import { sqlExec, sqlFind } from 'utils/ssh';
import { clickWhenClickable, HTMLSelect, scrollToBottom, wait } from 'utils/utils';
import * as navigation from 'utils/navigation';
import { ConfigurableOption, ConfigurableOptionGroup } from './configurable-option';
import * as plugin from 'elements/plugin';
import { LicenseType, CustomKeyField, ProductType, ServiceStatus } from './structure';


export class ExistingProducts {
    static products: Product[] = [];
}

export class Product extends whmcs.Creator {
    productType: ProductType = ProductType.product;

    get fullName(): string {
        return `${this.name} - ${this.type ? this.type : 'no-module'}`;
    }

    get name(): string {
        return `Product ${this.id}`;
    }

    async add(): Promise<Product> {
        await this.getDbId();
        if (this.dbId ) {
            await this.update();
        } else {
            await this.create();
        }
        return this;
    }

    async create() {
        await browser.get(`admin/configproducts.php?action=create`);
        await this.productGroup.choose('e2e');
        await this.productName.sendKeys(this.fullName);
        await this.submitForm();
        await clickWhenClickable(this.requireDomainCheckbox);
        await clickWhenClickable(this.moduleSettingsTab);
        await this.setup();
        await this.submitForm();
        await this.getDbId(true);
    }

    async update(): Promise<Product> {
        await this.getDbId();
        await browser.get(`admin/configproducts.php?action=edit&id=${this.dbId}`);
        await this.inputName.clear();
        await this.inputName.sendKeys(this.fullName);
        await clickWhenClickable(this.moduleSettingsTab);
        await this.setup();
        await this.submitForm();
        return this;
    }

    async getDbId(renew: boolean = false): Promise<number> {
        if (this.dbId && !renew) {
            return this.dbId;
        }
        this.dbId = await sqlFind('tblproducts', 'name', `${this.name} - %`);
        return this.dbId;
    }

    async checkLicenseExistence(role: navigation.Role = navigation.Role.admin,
                                related: Product = null): Promise<string> {
        let licenseItem: string;

        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            const product = related ? related : this;

            if (product.byKey) {
                const keyField = this.getKeyField(related);
                await scrollToBottom();
                expect(await keyField.isPresent()).toBe(true, 'Key license should exist');
                licenseItem = (related && role === navigation.Role.admin)
                    ? await keyField.getAttribute('value') : await keyField.getText();
                await product.checkInCln(licenseItem);
            } else {
                const ipField = this.getIpField(related);
                await scrollToBottom();
                expect(await ipField.isPresent()).toBe(true, 'IP license should exist');
                licenseItem = (related && role === navigation.Role.admin)
                    ? await ipField.getAttribute('value') : await ipField.getText();
                await product.checkInCln(licenseItem);
            }
        }, role);

        return licenseItem;
    }

    async checkLicenseAbsence() {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            const row = element(by.xpath(
                '//td[contains(text(),"License Details")]/..//table//tbody//tr[1]'));
            expect(await row.isPresent()).toBe(false, 'License should not exist');
        });
    }

    async checkAbsence(role: navigation.Role = navigation.Role.admin, related: Product = null) {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            await scrollToBottom();
            if (related) {
                const customField = related.byKey ?
                    this.getKeyField(related) : this.getIpField(related);
                if (await customField.isPresent()) {
                    expect(await customField.getAttribute('value'))
                        .toEqual('', 'Custom license field should not exist');
                }
            } else {
                expect(await this.getLinkToProduct().isPresent())
                    .toBe(false, 'Product should not exist');
            }
        }, role);
    }

    async terminate() {
        await clickWhenClickable($('#btnTerminate'));
        await browser.sleep(500);
        await clickWhenClickable(this.terminateButton);
        await wait.forElementToBeInvisible(this.terminateButton);
    }

    async acceptOrder() {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            await clickWhenClickable(element(by.xpath('//a[contains(text(), "View Order")]')));
            await clickWhenClickable(element(by.xpath('//button[contains(., "Accept Order")]')));
        });
    }

    async upgradeOption(group: ConfigurableOptionGroup, option: ConfigurableOption | null) {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            await clickWhenClickable(this.upgradeButton);
            const openedWindows = await browser.getAllWindowHandles();
            await browser.switchTo().window(openedWindows[1]);

            const chooseOptions = element(by.xpath(
                `//label[contains(text(),"Configurable Options")]` +
                `/preceding-sibling::input[@type="radio"][1]`,
            ));
            await clickWhenClickable(chooseOptions);
            const orderer = new whmcs.ProductOrderer();
            await orderer.chooseConfigurableOptions(group, option);
            await browser.sleep(500);
            await clickWhenClickable(this.upgradeOrderButton);
            await browser.switchTo().window(openedWindows[0]);
            await wait.forElement(orderer.acceptOrderButton);
        });
    }

    async upgradeServiceOption(options: Array<{
        group: ConfigurableOptionGroup,
        option: ConfigurableOption | null,
    }>) {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            const orderer = new whmcs.ProductOrderer();
            for (let i of options) {
                await orderer.chooseConfigurableOptions(i.group, i.option);
            }
            await clickWhenClickable(this.serviceSaveButton);
            await wait.forElement(this.serviceSuccessMessage);
        });
    }

    async changeDedicatedIP(ip: string) {
        const el = element(by.xpath(`//td[contains(text(),'Dedicated IP')]/..//td[2]/input`));
        await el.clear();
        await el.sendKeys(ip);
        await clickWhenClickable(this.serviceSaveButton);
        await wait.forElement(this.serviceSuccessMessage);
    }

    async changeStatus(status: ServiceStatus) {
        const statusField = new HTMLSelect($(`select[name="domainstatus"]`));
        await statusField.choose(status);
        await clickWhenClickable(this.serviceSaveButton);
        await wait.forElement(this.serviceSuccessMessage);
    }

    async upgradeProduct(product: Product) {
        await navigation.auth.withWhmcsUser(async () => {
            await clickWhenClickable(this.getLinkToProduct());
            await clickWhenClickable(this.upgradeButton);
            const openedWindows = await browser.getAllWindowHandles();
            await browser.switchTo().window(openedWindows[1]);

            const newProduct = new HTMLSelect($(`#newpid`));
            const orderer = new whmcs.ProductOrderer();
            await newProduct.choose(product.fullName);
            await browser.sleep(500);
            await clickWhenClickable(this.upgradeOrderButton);
            await browser.switchTo().window(openedWindows[0]);
            await wait.forElement(orderer.acceptOrderButton);
        });
    }

    async addRelation(product: Product) {
        await plugin.productRelationsEditor.add(this.fullName, product.fullName);
    }

    async deleteRelations() {
        await sqlExec(`DELETE FROM CloudLinux_FreeProductRelations
            WHERE freeProductID='${this.dbId}'`);
    }

    async addUpgradePackages(products: Product[]) {
        for (let product of products) {
            const result = await sqlExec(`SELECT id FROM tblproduct_upgrade_products
                WHERE product_id=${this.dbId} AND upgrade_product_id=${product.dbId}`);
            if (!result) {
                await sqlExec(`INSERT INTO tblproduct_upgrade_products
                    (product_id, upgrade_product_id) VALUES (${this.dbId}, ${product.dbId})`);
            }
        }
    }

    getLinkToProduct(): ElementFinder {
        if (navigation.auth.isAdmin()) {
            return element(by.xpath(`//td[contains(text(),'${this.fullName}')]/..//td[2]/a`));
        } else {
            return element(by.xpath(`//td/strong[contains(text(),'${this.fullName}')]`));
        }
    }

    getIpField(product: Product = null): ElementFinder {
        if (product) {
            if (navigation.auth.isAdmin()) {
                return element(by.xpath(`//td[contains(text(), 'Dedicated IP')]/../td[2]/input`));
            } else {
                return element(by.xpath(
                    `//div[contains(text(), '${product.getCustomFieldName()}')]/` +
                    `../../div[1]/div[2]`));
            }
        } else {
            if (navigation.auth.isAdmin()) {
                return element(by.xpath(
                    `//td[contains(text(),'License Details')]/..//table//tbody//tr[1]//td[1]`));
            } else {
                return element(by.xpath(
                    `//h2[contains(text(),'License Details')]` +
                    `/..//td[contains(text(),'IP Address')]/../td[2]/span`));
            }
        }
    }

    getKeyField(product: Product = null): ElementFinder {
        if (product) {
            if (navigation.auth.isAdmin()) {
                return element(by.xpath(
                    `//td[contains(text(), '${product.getCustomFieldName()}')]/../td[2]/input`));
            } else {
                return element(by.xpath(
                    `//div[contains(text(), '${product.getCustomFieldName()}')]` +
                    `/../../div[1]/div[2]`));
            }
        } else {
            if (navigation.auth.isAdmin()) {
                return element(by.xpath(
                    `//td[contains(text(),'License Details')]/..//table//tbody//tr[1]//td[2]`));
            } else {
                return element(by.xpath(
                    `//h2[contains(text(),'License Details')]/` +
                    `..//td[contains(text(),'License Key')]/../td[2]/span`));
            }
        }
    }

    getCustomFieldName(): string {
        if (this.type === LicenseType.CloudLinux) {
            return CustomKeyField.cloudlinux_IP;
        } else if (this.type === LicenseType.KernelCare) {
            return this.byKey ? CustomKeyField.kernelcare : CustomKeyField.kernelcare_IP;
        } else {
            return this.byKey ?
                CustomKeyField[`imunify360_${this.maxUsers.value}`] : CustomKeyField.imunify360_IP;
        }
    }
}


export const createProducts = (products: Product[]) => {
    beforeAll(async () => {
        for (let i = 0; i < products.length; i++) {
            let product = products[i];
            product.id = i;
            if (ExistingProducts.products[i] !== void 0) {
                ExistingProducts.products[i] = await product.update();
            } else {
                ExistingProducts.products.push(await product.add());
            }
        }
    });

    return ExistingProducts.products;
};
