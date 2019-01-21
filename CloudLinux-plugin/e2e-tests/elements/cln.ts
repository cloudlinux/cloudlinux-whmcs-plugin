import { LicenseType } from 'models/structure';
import axios from 'axios';
const sha1 = require('sha1');


export const secretKey = process.env.CLN_SECRET;
export const login = process.env.CLN_LOGIN;

type ClnParams = {
    type: LicenseType;
    key?: string;
    maxUsers?: number | string;
    ip?: string;
};

export const isLicenseExists = async ({type, key, maxUsers, ip}: ClnParams) => {
    const licenseTypeToClnQueryString = () => {
        if (!ip) {
            return {
                [LicenseType.Imunify360]: 'im',
                [LicenseType.KernelCare]: 'kcare',
            }[type] + '/key';
        } else {
            return 'ipl';
        }
    };
    const timestamp = (Date.now() / 1000).toFixed();
    const token = `${login}|${timestamp}|${sha1(secretKey + timestamp)}`;
    const url = `https://cln.cloudlinux.com/api/${licenseTypeToClnQueryString()}/list.json?token=${token}`;
    const res = await axios.get(url);
    if (ip) {
        const licenseTypeToClnType = () => {
            if (maxUsers === void 0) {
                const licenseIds = {
                    [LicenseType.KernelCare]: 16,
                    [LicenseType.CloudLinux]: 1,
                };
                return licenseIds[type];
            } else {
                return maxUsers;
            }
        };
        return res.data.data.some(item => {
            return item.ip === ip && item.type === licenseTypeToClnType();
        });
    } else {
        return res.data.data.some(item => {
            return item.key === key
                && (maxUsers !== void 0 || item.code === maxUsers);
        });
    }
};
