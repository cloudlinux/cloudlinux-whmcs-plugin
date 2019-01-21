import { LicenseType, ReadableMaxUsers } from 'models/structure';
import { createProducts, Product } from 'models/product';
import * as whmcs from 'elements/whmcs';
import * as navigation from 'utils/navigation';


describe('Products.', () => {
    beforeAll(async () => {
        await navigation.auth.withWhmcsUser(async () => {
            await whmcs.cleaner.cleanAll();
        });
    });

    const products = createProducts([
        new Product(),
        new Product({
            licenseType: LicenseType.Imunify360,
            byKey: true,
            maxUsers: ReadableMaxUsers.Single,
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
        it('Related:CL-ip', async () => {
            const [mainProduct, relatedIm1KeyProduct, relatedClIpProduct] = products;
            await mainProduct.addRelation(relatedClIpProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName);
                await relatedClIpProduct.checkLicenseExistence();
            });
            await mainProduct.deleteRelations();
        });

        it('Related:Im1-key', async () => {
            const [mainProduct, relatedIm1KeyProduct] = products;
            await mainProduct.addRelation(relatedIm1KeyProduct);

            await navigation.auth.withWhmcsUser(async () => {
                await whmcs.orderEditor.orderer.create(mainProduct.fullName);
                await relatedIm1KeyProduct.checkLicenseExistence();
            });
            await mainProduct.deleteRelations();
        });
    });

    // User can't buy products without provisioning module
});
