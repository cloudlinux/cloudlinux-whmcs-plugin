import { createProducts, Product } from 'models/product';
import { LicenseType, MaxUsers, ReadableMaxUsers } from 'models/structure';
import * as whmcs from 'elements/whmcs';
import * as navigation from 'utils/navigation';


describe('Simple order.', () => {
    beforeAll(async () => {
        await navigation.auth.withWhmcsUser(async () => {
            await whmcs.cleaner.cleanAll();
        });
    });

    const products = createProducts([
        new Product({
            licenseType: LicenseType.CloudLinux,
            byKey: false,
        }),
        new Product({
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
        it('CloudLinux IP license & changing IP', async () => {
            const [ipClProduct1, ipClProduct2] = products;
            const newIp = '33.33.33.1';

            ipClProduct1.type = LicenseType.CloudLinux;
            ipClProduct1.byKey = false;
            await ipClProduct1.update();
            ipClProduct2.type = LicenseType.CloudLinux;
            ipClProduct2.byKey = false;
            await ipClProduct2.update();

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(ipClProduct1.fullName, {updateIP: false});
                await ipClProduct1.checkLicenseExistence();
                const oldIp = await ipClProduct1.getIpField().getText();
                await ipClProduct1.changeLicenseItem(newIp);
                await ipClProduct1.checkInCln(oldIp, false,
                    'Old IP license should not exist in CLN');
                await ipClProduct1.checkInCln(newIp, true, 'New IP license should exist in CLN');

                await navigation.auth.withWhmcsUser(async () => {
                    await whmcs.getOrderer().create(ipClProduct2.fullName, {updateIP: false});
                    await ipClProduct2.changeLicenseItem(newIp, navigation.Role.admin, true);
                });
            });
        });

        it('CloudLinux Key license should saved as IP', async () => {
            const [product] = products;
            product.type = LicenseType.CloudLinux;
            product.byKey = true;
            await product.update();
            product.byKey = false;
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(product.fullName);
                await product.checkLicenseExistence();
            });
        });

        it('KernelCare IP license', async () => {
            const [product] = products;
            product.type = LicenseType.KernelCare;
            product.byKey = false;
            await product.update();
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(product.fullName);
                await product.checkLicenseExistence();
            });
        });

        it('KernelCare Key license & changing Key', async () => {
            const [keyKcProduct1, keyKcProduct2] = products;

            keyKcProduct1.type = LicenseType.KernelCare;
            keyKcProduct1.byKey = true;
            await keyKcProduct1.update();
            keyKcProduct2.type = LicenseType.KernelCare;
            keyKcProduct2.byKey = true;
            await keyKcProduct2.update();

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(keyKcProduct1.fullName);
                await keyKcProduct1.checkLicenseExistence();
                const oldKey = await keyKcProduct1.getKeyField().getText();
                await keyKcProduct1.changeLicenseItem('unknown key', navigation.Role.admin, true);
                await keyKcProduct1.checkInCln(oldKey, true,
                    'Old Key license should exist in CLN');
            });
        });

        for (const users of Object.values(ReadableMaxUsers)) {
            it(`Imunify360 Key license with ${users} users`, async () => {
                const [product] = products;
                product.type = LicenseType.Imunify360;
                product.byKey = true;
                product.maxUsers = new MaxUsers(users);
                await product.update();
                await navigation.auth.withWhmcsUser(async () => {
                    await whmcs.orderEditor.orderer.create(product.fullName);
                    await product.checkLicenseExistence();
                });
            });

            it(`Imunify360 IP license with ${users} users`, async () => {
                const [product] = products;
                product.type = LicenseType.Imunify360;
                product.byKey = false;
                product.maxUsers = new MaxUsers(users);
                await product.update();
                await navigation.auth.withWhmcsUser(async () => {
                    await whmcs.orderEditor.orderer.create(product.fullName);
                    await product.checkLicenseExistence();
                });
            });
        }

        it('IP license should be deleted on module terminate', async () => {
            const [product] = products;
            product.type = LicenseType.KernelCare;
            product.byKey = false;
            await product.update();
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(product.fullName);
                await product.checkLicenseExistence();
                await product.terminate();
                await product.checkLicenseAbsence();
            });
        });

        it('Key license should be deleted on module terminate', async () => {
            const [product] = products;
            product.type = LicenseType.KernelCare;
            product.byKey = true;
            await product.update();
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(product.fullName);
                await product.checkLicenseExistence();
                await product.terminate();
                await product.checkLicenseAbsence();
            });
        });

        it('IP license upgrade to non-CloudLinux product', async () => {
            const [mainProduct, nonClProduct] = products;
            mainProduct.type = LicenseType.CloudLinux;
            mainProduct.byKey = false;
            await mainProduct.update();
            nonClProduct.type = null;
            await nonClProduct.update();
            await mainProduct.addUpgradePackages([nonClProduct]);
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName);
                const oldLicense = await mainProduct.checkLicenseExistence();
                await mainProduct.upgradeProduct(nonClProduct);

                await navigation.auth.withWhmcsUser(async () => {
                    await mainProduct.checkInCln(oldLicense, false, 'License IP should not exist');
                });
            });
        });

        // TODO: after upgrade to non-cl product old `custom field` not exist
        // find the way to fix it
        xit('Key license upgrade to non-CloudLinux product', async () => {
            const [mainProduct, nonClProduct] = products;
            mainProduct.type = LicenseType.Imunify360;
            mainProduct.byKey = true;
            await mainProduct.update();
            nonClProduct.type = null;
            await nonClProduct.update();
            await mainProduct.addUpgradePackages([nonClProduct]);
            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName);
                const oldLicense = await mainProduct.checkLicenseExistence();
                await mainProduct.upgradeProduct(nonClProduct);

                await navigation.auth.withWhmcsUser(async () => {
                    await mainProduct.checkInCln(oldLicense, false, 'License Key should not exist');
                });
            });
        });

        xit('Key license upgrade to IP license', async () => {
            const [keyProduct, ipProduct] = products;
            keyProduct.type = LicenseType.KernelCare;
            keyProduct.byKey = true;
            await keyProduct.update();
            ipProduct.type = LicenseType.KernelCare;
            ipProduct.byKey = false;
            await ipProduct.update();
            await keyProduct.addUpgradePackages([ipProduct]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(keyProduct.fullName);
                const oldLicense = await keyProduct.checkLicenseExistence();
                await keyProduct.upgradeProduct(ipProduct);

                await navigation.auth.withWhmcsUser(async () => {
                    await keyProduct.checkInCln(oldLicense, false, 'License Key should not exist');
                    await ipProduct.checkLicenseExistence();
                });
            });
        });
    });

    describe('As user.', () => {
        it('IP license & changing IP', async () => {
            const [ipClProduct1, ipClProduct2] = products;
            const newIp = '33.33.33.1';

            ipClProduct1.type = LicenseType.CloudLinux;
            ipClProduct1.byKey = false;
            await ipClProduct1.update();
            ipClProduct2.type = LicenseType.CloudLinux;
            ipClProduct2.byKey = false;
            await ipClProduct2.update();

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(ipClProduct1.fullName, {updateIP: false});
                const oldIp = await ipClProduct1.checkLicenseExistence(navigation.Role.user);
                await ipClProduct1.changeLicenseItem(newIp, navigation.Role.user);
                await ipClProduct1.checkInCln(oldIp, false,
                    'Old IP license should not exist in CLN');
                await ipClProduct1.checkInCln(newIp, true, 'New IP license should exist in CLN');

                await whmcs.getOrderer().create(ipClProduct2.fullName, {updateIP: false});
                await ipClProduct2.changeLicenseItem(newIp, navigation.Role.user, true);
            }, navigation.Role.user);
        });

        it('Key license & changing Key', async () => {
            const [keyKcProduct1, keyKcProduct2] = products;

            keyKcProduct1.type = LicenseType.KernelCare;
            keyKcProduct1.byKey = true;
            await keyKcProduct1.update();
            keyKcProduct2.type = LicenseType.KernelCare;
            keyKcProduct2.byKey = true;
            await keyKcProduct2.update();

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(keyKcProduct1.fullName);
                const oldKey = await keyKcProduct1.checkLicenseExistence(navigation.Role.user);
                await keyKcProduct1.changeLicenseItem('unknown key', navigation.Role.user, true);
                await keyKcProduct1.checkInCln(oldKey, true,
                    'Old Key license should exist in CLN');
            }, navigation.Role.user);
        });
    });
});
