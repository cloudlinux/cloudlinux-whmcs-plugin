import { $, browser } from 'protractor';
import { sqlExec, sqlFind } from 'utils/ssh';
import { Product } from 'models/product';
import * as plugin from 'elements/plugin';
import { clickWhenClickable } from 'utils/utils';

export class ExistingOptionGroups {
    static groups: ConfigurableOptionGroup[] = [];
}

export enum OptionTypes {
    dropdown = 1,
    radio = 2,
    checkbox = 3,
    quantity = 4,
}

export class ConfigurableOptionGroup {
    id: number;
    dbId: number;
    dbGroupId: number;
    optionType: OptionTypes;
    mainProduct: Product;
    options: ConfigurableOption[];

    static async deleteRelationsByName() {
        await sqlExec(`DELETE r.* FROM CloudLinux_ConfigurableOptionsRelations r` +
            ` LEFT JOIN tblproductconfigoptions og ON og.id=r.option_group_id` +
            ` WHERE og.optionname LIKE 'Options group%';`);
    }

    constructor(item: {
        optionType: OptionTypes,
        optionRelations?: Product[],
        options: ConfigurableOption[],
        mainProduct: Product,
    }) {
        this.optionType = item.optionType;
        this.mainProduct = item.mainProduct;
        this.options = item.options;
    }

    get fullName(): string {
        return `${this.name} - ${this.getOptionTypeName()}`;
    }

    get name(): string {
        return `Options group ${this.id}`;
    }

    getOptionTypeName(): string {
        return OptionTypes[this.optionType];
    }

    async add(): Promise<ConfigurableOptionGroup> {
        await this.getDbId();
        const gid = await this.getGroup();
        if (this.dbId) {
            await this.update();
        } else {
            await sqlExec(`INSERT INTO tblproductconfigoptions (gid, optionname, optiontype)
                VALUES ('${gid}', '${this.fullName}', '${this.optionType}')`);
            await this.getDbId(true);
            await this.addOptions();
        }

        await sqlExec(`DELETE FROM tblproductconfiglinks WHERE gid='${gid}'`);
        await sqlExec(`INSERT INTO tblproductconfiglinks (gid, pid)
            VALUES (${gid}, ${this.mainProduct.dbId})`);

        return this;
    }

    async update(): Promise<ConfigurableOptionGroup> {
        const gid = await this.getGroup();
        await this.getDbId();
        await sqlExec(
            `UPDATE tblproductconfigoptions SET gid='${gid}',
             optionname='${this.fullName}', optiontype='${this.optionType}'
             WHERE optionname LIKE '${this.name} - %'`);
        await this.addOptions();
        return this;
    }

    async getDbId(renew: boolean = false): Promise<number> {
        if (this.dbId && !renew) {
            return this.dbId;
        }

        this.dbId = await sqlFind('tblproductconfigoptions', 'optionname', `${this.name} - %`);
        return this.dbId;
    }

    async getGroup() {
        if (this.dbGroupId) {
            return this.dbGroupId;
        }

        const response = await sqlExec(`SELECT id FROM tblproductconfiggroups WHERE name='e2e'`);
        if (!response.stdout) {
            await sqlExec(`INSERT INTO tblproductconfiggroups (name) VALUES ('e2e')`);
            return this.getGroup();
        }
        const [match, id] = /id:\s(\d+)/.exec(response.stdout);
        this.dbGroupId = +id;
        return this.dbGroupId;
    }

    async addOptions() {
        await this.deleteRelations();
        await sqlExec(`DELETE FROM tblproductconfigoptionssub WHERE configid='${this.dbId}'`);

        for (let i = 0; i < this.options.length; i++) {
            let option = this.options[i];
            option.id = i;
            option.parentId = this.dbId;
            await option.add();

            if (option.relatedProduct) {
                await plugin.configurableOptionEditor.add({
                    product: option.relatedProduct.fullName,
                    optionGroup: this.fullName,
                    option: option.name,
                });
            }
        }

        // otherwise option is not displayed  on order
        await browser.get(`/admin/configproductoptions.php?manageoptions=true&cid=${this.dbId}`);
        await clickWhenClickable($('.btn.btn-primary'));
    }

    async deleteRelations() {
        await sqlExec(`DELETE FROM CloudLinux_ConfigurableOptionsRelations
            WHERE option_group_id='${this.dbId}'`);
    }
}

export class ConfigurableOption  {
    id: number;
    parentId: number;
    relatedProduct: undefined | Product;

    get name(): string {
        return `Option ${this.id}`;
    }

    constructor(related?: Product) {
        this.relatedProduct = related;
    }

    async add(): Promise<ConfigurableOption> {
        await sqlExec(`INSERT INTO tblproductconfigoptionssub (configid, optionname)
                VALUES ('${this.parentId}', '${this.name}')`);
        return this;
    }
}

export const createConfigurableOptions = async (optionGroups: ConfigurableOptionGroup[]) => {
    for (let i = 0; i < optionGroups.length; i++) {
        optionGroups[i].id = i;
        if (ExistingOptionGroups.groups[i] !== void 0) {
            ExistingOptionGroups.groups[i] = await optionGroups[i].update();
        } else {
            ExistingOptionGroups.groups.push(await optionGroups[i].add());
        }
    }

    return ExistingOptionGroups.groups;
};
