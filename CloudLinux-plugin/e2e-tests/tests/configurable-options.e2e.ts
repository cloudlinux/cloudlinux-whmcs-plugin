import * as whmcs from 'elements/whmcs';
import * as navigation from 'utils/navigation';
import {createProducts, Product} from 'models/product';
import {LicenseType, ReadableMaxUsers, ServiceStatus} from 'models/structure';
import {
    ConfigurableOption,
    ConfigurableOptionGroup,
    createConfigurableOptions,
    OptionTypes,
} from 'models/configurable-option';


describe('Configurable options.', () => {
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
            licenseType: LicenseType.Imunify360,
            byKey: true,
            maxUsers: ReadableMaxUsers.To30,
        }),
        new Product({
            licenseType: LicenseType.CloudLinux,
            byKey: false,
        }),
        new Product({
            licenseType: LicenseType.KernelCare,
            byKey: false,
        }),
    ]);

    afterEach(async () => {
        await navigation.auth.withWhmcsUser(async () => {
            await whmcs.cleaner.cleanAll();
            await ConfigurableOptionGroup.deleteRelationsByName();
        });
    });

    describe('As admin.', () => {
        it('Dropdown main:kc + related:im1 - buy related => upgrade to unrelated', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedProduct),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: optionGroup.options[1]},
                    ],
                });
                const license = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, optionGroup.options[0]);
                await relatedProduct.checkInCln(license, false);
            });
        });

        it('Dropdown main:kc + related:im1 - buy unrelated => upgrade to related', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedProduct),
                        new ConfigurableOption(),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: optionGroup.options[1]},
                    ],
                });
                await mainProduct.checkAbsence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, optionGroup.options[0]);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedProduct);
            });
        });

        it('Dropdown main:kc + related:im1 + related:im30 - buy related', async () => {
            const [mainProduct, relatedProduct1, relatedProduct2] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedProduct1),
                        new ConfigurableOption(),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedProduct2),
                        new ConfigurableOption(),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[0]},
                        {group: optionGroup2, option: optionGroup2.options[0]},
                    ],
                });
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedProduct1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedProduct2);
            });
        });

        it('Checkbox main:kc + related:im1 - buy unrelated => upgrade to related', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.checkbox,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedProduct),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: null},
                    ],
                });
                await mainProduct.checkAbsence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, optionGroup.options[0]);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedProduct);
            });
        });

        it('Checkbox main:kc + related:im1 - buy related => upgrade to unrelated', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.checkbox,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedProduct),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: optionGroup.options[0]},
                    ],
                });
                const license = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, null);
                await relatedProduct.checkInCln(license, false);
            });
        });

        it('Radiobutton main:kc + related:im1 - buy unrelated => upgrade to related', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.radio,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedProduct),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: optionGroup.options[0]},
                    ],
                });
                await mainProduct.checkAbsence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, optionGroup.options[1]);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedProduct);
            });
        });

        it('Radiobutton main:kc + related:im1 - buy related => upgrade to unrelated', async () => {
            const [mainProduct, relatedProduct] = products;
            const [optionGroup] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.radio,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedProduct),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup, option: optionGroup.options[1]},
                    ],
                });
                const license = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedProduct);
                await mainProduct.upgradeOption(optionGroup, optionGroup.options[0]);
                await relatedProduct.checkInCln(license, false);
            });
        });

        it('Dropdown main:kc + related:im1 + related:im30 - update service', async () => {
            const [mainProduct, relatedIm1, relatedIm30] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedIm1),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedIm30),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[1]},
                        {group: optionGroup2, option: optionGroup2.options[1]},
                    ],
                });

                let license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                let license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: optionGroup1.options[0],
                }, {
                    group: optionGroup2,
                    option: optionGroup2.options[0],
                }]);

                await relatedIm1.checkInCln(license1, false);
                await relatedIm30.checkInCln(license30, false);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: optionGroup1.options[1],
                }, {
                    group: optionGroup2,
                    option: optionGroup2.options[1],
                }]);

                // Status change
                license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.changeStatus(ServiceStatus.pending);
                await relatedIm1.checkInCln(license1, false,
                    `License shouldn't exist after status changed`);
                await relatedIm30.checkInCln(license30, false,
                    `License shouldn't exist after status changed`);

                await mainProduct.changeStatus(ServiceStatus.active);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm30);
            });
        });

        it('Checkbox main:kc + related:im1 + related:im30 - update service', async () => {
            const [mainProduct, relatedIm1, relatedIm30] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.checkbox,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedIm1),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.checkbox,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedIm30),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[0]},
                        {group: optionGroup2, option: optionGroup2.options[0]},
                    ],
                });

                let license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                let license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: null,
                }, {
                    group: optionGroup2,
                    option: null,
                }]);

                await relatedIm1.checkInCln(license1, false);
                await relatedIm30.checkInCln(license30, false);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: optionGroup1.options[0],
                }, {
                    group: optionGroup2,
                    option: optionGroup2.options[0],
                }]);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm30);

                // Status change
                license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.changeStatus(ServiceStatus.pending);
                await relatedIm1.checkInCln(license1, false,
                    `License shouldn't exist after status changed`);
                await relatedIm30.checkInCln(license30, false,
                    `License shouldn't exist after status changed`);

                await mainProduct.changeStatus(ServiceStatus.active);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm30);
            });
        });

        it('Radiobutton main:kc + related:im1 + related:im30 - update service', async () => {
            const [mainProduct, relatedIm1, relatedIm30] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.radio,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedIm1),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.radio,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedIm30),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[1]},
                        {group: optionGroup2, option: optionGroup2.options[1]},
                    ],
                });

                let license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                let license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: optionGroup1.options[0],
                }, {
                    group: optionGroup2,
                    option: optionGroup2.options[0],
                }]);

                await relatedIm1.checkInCln(license1, false);
                await relatedIm30.checkInCln(license30, false);

                await mainProduct.upgradeServiceOption([{
                    group: optionGroup1,
                    option: optionGroup1.options[1],
                }, {
                    group: optionGroup2,
                    option: optionGroup2.options[1],
                }]);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm30);

                // Status change
                license1 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm1);
                license30 = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedIm30);

                await mainProduct.changeStatus(ServiceStatus.pending);
                await relatedIm1.checkInCln(license1, false,
                    `License shouldn't exist after status changed`);
                await relatedIm30.checkInCln(license30, false,
                    `License shouldn't exist after status changed`);

                await mainProduct.changeStatus(ServiceStatus.active);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm1);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedIm30);
            });
        });

        it('Changing dedicated IP should change license IP', async () => {
            const [mainProduct, related1, related2, relatedClIP, relatedKcIP] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedClIP),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedKcIP),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[0]},
                        {group: optionGroup2, option: optionGroup2.options[0]},
                    ],
                });
                const newIP = '62.62.62.62';
                const oldIP = await mainProduct
                    .checkLicenseExistence(navigation.Role.admin, relatedClIP);
                await mainProduct.checkLicenseExistence(navigation.Role.admin, relatedKcIP);
                await mainProduct.changeDedicatedIP(newIP);
                await relatedClIP.checkInCln(oldIP, false);
                await relatedKcIP.checkInCln(oldIP, false);
                await relatedClIP.checkInCln(newIP);
                await relatedKcIP.checkInCln(newIP);

                // Status change
                await mainProduct.changeStatus(ServiceStatus.pending);
                await relatedClIP.checkInCln(newIP, false,
                    `License shouldn't exist after status changed`);
                await relatedKcIP.checkInCln(newIP, false,
                    `License shouldn't exist after status changed`);

                await mainProduct.changeStatus(ServiceStatus.active);
                await relatedClIP.checkInCln(newIP);
                await relatedKcIP.checkInCln(newIP);
            });
        });
    });

    describe('As user.', () => {
        it('Dropdown main:kc + related:im1 + related:clip - buy related', async () => {
            const [mainProduct, relatedImSingle, relatedIm30, relatedClIP] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedImSingle),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(),
                        new ConfigurableOption(relatedClIP),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[1]},
                        {group: optionGroup2, option: optionGroup2.options[1]},
                    ],
                });

                await mainProduct.checkAbsence(navigation.Role.user, relatedImSingle);
                await mainProduct.checkAbsence(navigation.Role.user, relatedClIP);

                await mainProduct.acceptOrder();
                await mainProduct.checkLicenseExistence(navigation.Role.user, relatedImSingle);
                await mainProduct.checkLicenseExistence(navigation.Role.user, relatedClIP);
            }, navigation.Role.user);
        });

        it('Dropdown main:kc + related:im1 + related:clip - buy unrelated', async () => {
            const [mainProduct, relatedImSingle, relatedIm30, relatedClIP] = products;
            const [optionGroup1, optionGroup2] = await createConfigurableOptions([
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedImSingle),
                        new ConfigurableOption(),
                    ],
                }),
                new ConfigurableOptionGroup({
                    optionType: OptionTypes.dropdown,
                    mainProduct,
                    options: [
                        new ConfigurableOption(relatedClIP),
                        new ConfigurableOption(),
                    ],
                }),
            ]);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.getOrderer().create(mainProduct.fullName, {
                    configurableOptions: [
                        {group: optionGroup1, option: optionGroup1.options[1]},
                        {group: optionGroup2, option: optionGroup1.options[1]},
                    ],
                });
                await mainProduct.checkAbsence(navigation.Role.user, relatedImSingle);
                await mainProduct.checkAbsence(navigation.Role.user, relatedClIP);

                await mainProduct.acceptOrder();
                await mainProduct.checkAbsence(navigation.Role.user, relatedImSingle);
                await mainProduct.checkAbsence(navigation.Role.user, relatedClIP);
            }, navigation.Role.user);
        });
    });
});
