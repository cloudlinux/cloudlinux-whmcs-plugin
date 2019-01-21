export enum LicenseType {
    CloudLinux = 'CloudLinux',
    Imunify360 = 'Imunify360',
    KernelCare = 'KernelCare',
}

export enum ProductType {
    product = 'product',
    addon = 'addon',
}

export enum ServiceStatus {
    active = 'Active',
    pending = 'Pending',
    suspended = 'Suspended',
    terminated = 'Terminated',
    cancelled = 'Cancelled',
    fraud = 'Fraud',
    completed = 'Completed',
}

export enum AutoSetupOptions {
    payment = 'payment',
    order = 'order',
    manually = 'on',
    disabled = '',
}

export enum CustomKeyField {
    cloudlinux_IP = 'CloudLinux - IP based',
    kernelcare = 'KernelCare - Key based',
    kernelcare_IP = 'KernelCare - IP based',
    imunify360_IP = 'Imunify360 - IP based',
    imunify360_Single = 'Imunify360 - Key based (Single user per server)',
    imunify360_30 = 'Imunify360 - Key based (Up to 30 users per server)',
    imunify360_250 = 'Imunify360 - Key based (Up to 250 users per server)',
    imunify360_Unlimited = 'Imunify360 - Key based (Unlimited users per server)',
    // TODO: imunify360_Clean = 'Imunify360 - Key based (Imunify360 Clean)',
}

export class MaxUsers {
    readonly clnKeyValues = {
        [ReadableMaxUsers.Unlimited]: '360_UN',
        [ReadableMaxUsers.Single]: '360_1',
        [ReadableMaxUsers.To30]: '360_30',
        [ReadableMaxUsers.To250]: '360_250',
        // TODO: [ReadableMaxUsers.Clean]: 'CLEAN',
    };
    readonly clnIPValues = {
        [ReadableMaxUsers.Unlimited]: 49,
        [ReadableMaxUsers.Single]: 41,
        [ReadableMaxUsers.To30]: 42,
        [ReadableMaxUsers.To250]: 43,
        // TODO: [ReadableMaxUsers.Clean]: 40,
    };
    readonly dbValues = {
        [ReadableMaxUsers.Unlimited]: 0,
        [ReadableMaxUsers.Single]: 1,
        [ReadableMaxUsers.To30]: 2,
        [ReadableMaxUsers.To250]: 3,
    };

    value: ReadableMaxUsers;

    constructor(value: ReadableMaxUsers) {
        this.value = value;
    }

    toCLN(isKeyBased: boolean = true): string | number {
        return isKeyBased ?
            this.clnKeyValues[this.value] : this.clnIPValues[this.value];
    }

    toSQL(): number {
        return this.dbValues[this.value];
    }
}

export enum ReadableMaxUsers {
    Unlimited = 'Unlimited',
    Single = 'Single',
    To30 = '30',
    To250 = '250',
    // TODO: Clean = 'Clean',
}
