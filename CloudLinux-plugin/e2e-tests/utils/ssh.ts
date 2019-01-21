import * as SSH from 'sequest';
import * as env from 'environment';

export class SSHResult {
    public error: Error;
    public stdout: string;
    public cmd: string;
    public code: number;
    public signal: string;
    public stderr: string;

    constructor(result: SSHResult) {
        Object.assign(this, result);
    }

    public get json(): any {
        return JSON.parse(this.stdout);
    }
}

export const promiseTimeout = (timeout) => new Promise(
    (resolve, reject) => setTimeout(resolve, timeout));

export const exec = (command: string, host?: string, password?: string) => {
    let start = new Date().getTime();
    let params = {command, password: password || env.WHMCS_HOST_PASSWORD, debug: console.log};
    return new Promise<SSHResult>((resolve, reject) => {
        SSH(`${env.WHMCS_HOST_USER}@${host || env.WHMCS_HOST}`, params,
            (error: Error, stdout: string, other) => {
            const result = new SSHResult({error, stdout, ...other});
            let finish = new Date().getTime();
            console.log(`Took ${finish - start} ms# ${stdout || ''}`);
            if (error) {
                console.error(error.message + '\n');
                if (/Timed out while waiting for handshake/.test(error.message) ||
                    ['EHOSTUNREACH', 'ECONNREFUSED'].includes(error['code'])) {
                    console.log('Connection issues, retry...');
                    return promiseTimeout(1000)
                        .then(() => exec(command, host, password).then(resolve, reject));
                }
            }
            resolve(result);
        });
    });
};

export const sqlExec = (sql): Promise<SSHResult> => {
    console.log(`sql# ${sql}`);
    return exec(
        `mysql -u${env.WHMCS_MYSQL_USER} -p${env.WHMCS_MYSQL_PASSWORD} ${env.WHMCS_MYSQL_DB}` +
        ` -e "${sql}\\G"`);
};

export const sqlFind = async (table: string, field: string, value: string): Promise<number> => {
    const response = await sqlExec(
        `SELECT id FROM ${table} WHERE ${field} LIKE '${value}'`);
    if (!response.stdout) {
        return 0;
    }
    const [match, id] = /id:\s(\d+)/.exec(response.stdout);
    return +id;
};
