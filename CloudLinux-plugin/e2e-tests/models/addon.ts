import {$, browser, by, element, ElementFinder} from 'protractor';
import * as whmcs from 'elements/whmcs';
import * as plugin from 'elements/plugin';
import { isLicenseExists } from 'elements/cln';
import { sqlExec, sqlFind } from 'utils/ssh';
import { clickWhenClickable, wait } from 'utils/utils';
import * as navigation from 'utils/navigation';
import {LicenseType, ProductType} from './structure';
import { Product } from './product';


export class ExistingAddons {
    static addons: Addon[] = [];
}

export class Addon extends whmcs.Creator {
    productType: ProductType = ProductType.addon;
    assignedProduct: Product;

    get fullName(): string {
        return `${this.name} - ${this.type ? this.type : 'no-module'}`;
    }

    get name(): string {
        return `Addon ${this.id}`;
    }

    async add(): Promise<Addon> {
        await this.getDbId();
        if (this.dbId) {
            await this.update();
        } else {
            await this.create();
        }
        return this;
    }

    async create() {
        await browser.get(`admin/configaddons.php?action=manage`);
        await this.inputName.sendKeys(this.fullName);
        await clickWhenClickable(this.showOnOrderCheckbox);
        await clickWhenClickable(this.moduleSettingsTab);
        await this.setup();
        await this.submitForm();
        await this.getDbId(true);
    }

    async update(): Promise<Addon> {
        await this.getDbId();
        await browser.get(`admin/configaddons.php?action=manage&id=${this.dbId}`);
        await this.inputName.clear();
        await this.inputName.sendKeys(this.fullName);
        await clickWhenClickable(this.moduleSettingsTab);
        await this.setup();
        await this.submitForm();
        return this;
    }

    async assignProduct(product: Product) {
        this.assignedProduct = product;
        await sqlExec(`UPDATE tbladdons SET packages='${product.dbId}' WHERE id=${this.dbId}`);
    }

    async getDbId(renew: boolean = false): Promise<number> {
        if (this.dbId && !renew) {
            return this.dbId;
        }
        this.dbId = await sqlFind('tbladdons', 'name', `${this.name} - %`);
        return this.dbId;
    }

    async checkLicenseExistence(role: navigation.Role = navigation.Role.admin): Promise<string> {
        let licenseItem: string;

        await navigation.auth.withWhmcsUser(async () => {
            await this.goToAddonPage();

            if (this.byKey) {
                const keyField = this.getKeyField();
                expect(await keyField.isPresent()).toBe(true, 'Key license should exist');
                licenseItem = await keyField.getText();
                await this.checkInCln(licenseItem);
            } else {
                const ipField = this.getIpField();
                expect(await ipField.isPresent()).toBe(true, 'IP license should exist');
                licenseItem = await ipField.getText();
                await this.checkInCln(licenseItem);
            }
        }, role);

        return licenseItem;
    }

    async checkLicenseAbsence() {
        await navigation.auth.withWhmcsUser(async () => {
            await this.goToAddonPage();
            const row = element(by.xpath(
                '//td[contains(text(),"License Details")]/..//table//tbody//tr[1]'));
            expect(await row.isPresent()).toBe(false, 'License should not exist');
        });
    }

    async checkLicensesListExistence() {
        await navigation.auth.withWhmcsUser(async () => {
            await browser.get(`/admin/addonmodules.php?module=CloudLinuxAddon#/addons-list`);
            const el = element(by.xpath(`//td/div/a[contains(text(),"${this.fullName}")]`));
            await wait.forElement(el, {
                errorMessage: 'Addon should be present in addons list table',
            });
        });
    }

    async terminate() {
        await clickWhenClickable($('#btnTerminate'));
        await browser.sleep(500);
        await clickWhenClickable(this.terminateButton);
        await wait.forElementToBeInvisible(this.terminateButton);
    }

    async addRelation(product: Product) {
        await plugin.addonRelationsEditor.add(this.fullName, product.fullName);
    }

    async deleteRelations() {
        await sqlExec(`DELETE FROM CloudLinux_AddonRelations
            WHERE addonID='${this.dbId}'`);
    }

    async goToAddonPage() {
        if (navigation.auth.isAdmin()) {
            await clickWhenClickable(this.getLinkToAddon());
        } else {
            await clickWhenClickable(this.assignedProduct.getLinkToProduct());
            await clickWhenClickable(element(by.xpath(`//a[contains(@href, '#tabAddons')]`)));
        }
    }

    getLinkToAddon() {
        return element(by.xpath(
            `//td[contains(text(),'${this.fullName}')]/..//td[2]/a`,
        ));
    }

    getIpField(): ElementFinder {
        if (navigation.auth.isAdmin()) {
            return element(by.xpath(
                '//td[contains(text(),"License Details")]/..//table//tbody//tr[1]//td[1]'));
        } else {
            return element(by.xpath(
                `//h2[contains(text(),'License Details')]/` +
                `..//td[contains(text(),'IP Address')]/../td[2]/span`));
        }
    }

    getKeyField(): ElementFinder {
        if (navigation.auth.isAdmin()) {
            return element(by.xpath(
                '//td[contains(text(),"License Details")]/..//table//tbody//tr[1]//td[2]'));
        } else {
            return element(by.xpath(
                `(//h2[contains(text(),'License Details')]/` +
                `..//td[contains(text(),'License Key')]/../td[2]/span)[last()]`));
        }
    }
}

export const createAddons = (addons: Addon[]) => {
    beforeAll(async () => {
        for (let i = 0; i < addons.length; i++) {
            let addon = addons[i];
            addon.id = i;
            if (ExistingAddons.addons[i] !== void 0) {
                ExistingAddons.addons[i] = await addon.update();
            } else {
                ExistingAddons.addons.push(await addon.add());
            }
        }
    });

    return ExistingAddons.addons;
};
