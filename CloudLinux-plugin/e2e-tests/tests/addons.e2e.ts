import { LicenseType, ReadableMaxUsers } from 'models/structure';
import { createProducts, Product } from 'models/product';
import { createAddons, Addon } from 'models/addon';
import * as whmcs from 'elements/whmcs';
import * as navigation from 'utils/navigation';
import { clickWhenClickable } from 'utils/utils';


// NOTE: tests works only for version 7.5+
// there are no 'module create' checkbox for provisioning addons during order as admin
describe('Addons.', () => {
    beforeAll(async () => {
        await navigation.auth.withWhmcsUser(async () => {
            await whmcs.cleaner.cleanAll();
        });
    });

    const products = createProducts([
        new Product({
            licenseType: LicenseType.KernelCare,
            byKey: true,
        }),
        new Product({
            licenseType: LicenseType.Imunify360,
            byKey: true,
            maxUsers: ReadableMaxUsers.Single,
        }),
        new Product({
            licenseType: LicenseType.CloudLinux,
            byKey: false,
        }),
        new Product({
            licenseType: LicenseType.KernelCare,
            byKey: true,
        }),
    ]);

    const addons = createAddons([
        new Addon(),
        new Addon({
            licenseType: LicenseType.CloudLinux,
            byKey: false,
        }),
        new Addon({
            licenseType: LicenseType.Imunify360,
            byKey: true,
            maxUsers: ReadableMaxUsers.Single,
        }),
        new Addon({
            licenseType: LicenseType.CloudLinux,
            byKey: false,
        }),
    ]);

    afterEach(async () => {
        await navigation.auth.withWhmcsUser(async () => {
            await whmcs.cleaner.cleanAll();
        });
    });

    describe('As admin.', () => {
        it('Without provisioning options - related:CL-ip', async () => {
            const [mainProduct, relatedIm1KeyProduct, relatedClIpProduct] = products;
            const [addonWithoutModule] = addons;

            await addonWithoutModule.assignProduct(mainProduct);
            await addonWithoutModule.addRelation(relatedClIpProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    addons: [addonWithoutModule],
                });
                await relatedClIpProduct.checkLicenseExistence();

                await navigation.auth.withWhmcsUser(async () => {
                    await clickWhenClickable(mainProduct.getLinkToProduct());
                    await mainProduct.terminate();
                });
                await relatedClIpProduct.checkLicenseAbsence();
            });
            await addonWithoutModule.deleteRelations();
        });

        it('Without provisioning options - related:Im1-key', async () => {
            const [mainProduct, relatedIm1KeyProduct] = products;
            const [addonWithoutModule] = addons;

            await addonWithoutModule.assignProduct(mainProduct);
            await addonWithoutModule.addRelation(relatedIm1KeyProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    addons: [addonWithoutModule],
                });
                await relatedIm1KeyProduct.checkLicenseExistence();

                await navigation.auth.withWhmcsUser(async () => {
                    await clickWhenClickable(mainProduct.getLinkToProduct());
                    await mainProduct.terminate();
                });
                await relatedIm1KeyProduct.checkLicenseAbsence();
            });
            await addonWithoutModule.deleteRelations();
        });

        it('With provisioning options: CL-ip', async () => {
            const [mainProduct] = products;
            const [addonWithoutModule, addonClIp] = addons;

            await addonClIp.assignProduct(mainProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    addons: [addonClIp],
                });
                await addonClIp.checkLicenseExistence();
                await addonClIp.checkLicensesListExistence();
            });
        });

        it('With provisioning options: Im1-key', async () => {
            const [mainProduct] = products;
            const [addonWithoutModule, addonClIp, addonIm1Key] = addons;

            await addonIm1Key.assignProduct(mainProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    addons: [addonIm1Key],
                });
                await addonIm1Key.checkLicenseExistence();
                await addonIm1Key.checkLicensesListExistence();
            });
        });
    });

    describe('As user.', () => {
        it('Without provisioning options - related:CL-ip', async () => {
            const [mainProduct, relatedIm1KeyProduct, relatedClIpProduct] = products;
            const [addonWithoutModule] = addons;

            await addonWithoutModule.assignProduct(mainProduct);
            await addonWithoutModule.addRelation(relatedClIpProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    addons: [addonWithoutModule],
                });
                await relatedClIpProduct.checkLicenseExistence(navigation.Role.user);
            }, navigation.Role.user);
            await addonWithoutModule.deleteRelations();
        });

        it('Without provisioning options - related:Im1-key', async () => {
            const [mainProduct, relatedIm1KeyProduct] = products;
            const [addonWithoutModule] = addons;

            await addonWithoutModule.assignProduct(mainProduct);
            await addonWithoutModule.addRelation(relatedIm1KeyProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    addons: [addonWithoutModule],
                });
                await relatedIm1KeyProduct.checkLicenseExistence(navigation.Role.user);
            }, navigation.Role.user);
            await addonWithoutModule.deleteRelations();
        });

        it('With provisioning options: CL-ip', async () => {
            const [mainProduct] = products;
            const [addonWithoutModule, addonClIp] = addons;

            await addonClIp.assignProduct(mainProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    addons: [addonClIp],
                });
                await addonClIp.checkLicenseExistence(navigation.Role.user);
            }, navigation.Role.user);
        });

        it('With provisioning options: CL-ip & changing IP', async () => {
            const [mainProduct1, im1KeyProduct, clIpProduct, mainProduct2] = products;
            const [addonWithoutModule, addonClIp1, addonIm1Key, addonClIp2] = addons;
            const newIp = '33.33.33.1';

            await addonClIp1.assignProduct(mainProduct1);
            await addonClIp2.assignProduct(mainProduct2);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct1.fullName, {
                    addons: [addonClIp1],
                });
                const oldIp = await addonClIp1.checkLicenseExistence(navigation.Role.user);
                await addonClIp1.changeLicenseItem(newIp, navigation.Role.user);
                await addonClIp1.checkInCln(oldIp, false,
                    'Old IP license should not exist in CLN');
                await addonClIp1.checkInCln(newIp, true, 'New IP license should exist in CLN');

                await navigation.auth.withWhmcsUser(async () => {
                    await whmcs.getOrderer().create(mainProduct2.fullName, {
                        addons: [addonClIp2],
                    });
                    await addonClIp2.changeLicenseItem(newIp, navigation.Role.user, true);
                }, navigation.Role.user);
            }, navigation.Role.user);
        });

        it('With provisioning options: Im1-key & changing Key', async () => {
            const [mainProduct] = products;
            const [addonWithoutModule, addonClIp, addonIm1Key] = addons;

            await addonIm1Key.assignProduct(mainProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    addons: [addonIm1Key],
                });
                const oldKey = await addonIm1Key.checkLicenseExistence(navigation.Role.user);
                await addonIm1Key.changeLicenseItem('unknown key', navigation.Role.user, true);
                await addonIm1Key.checkInCln(oldKey, true,
                    'Old Key license should exist in CLN');
            }, navigation.Role.user);
        });
    });
});
