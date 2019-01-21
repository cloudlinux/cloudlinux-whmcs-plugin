import { browser, by, element } from 'protractor';
import { clickWhenClickable, Form } from 'utils/utils';


const whmcsHttpPassword = encodeURIComponent(process.env.WHMCS_HTTP_PASSWORD);
const whmcsLogin = process.env.WHMCS_LOGIN;
const whmcsPassword = process.env.WHMCS_PASSWORD;

export enum Role {
    admin = 'admin',
    user = 'user',
}

class LoginForm extends Form {
    username = this.elByName('username');
    password = this.elByName('password');
    async login(login: string, password: string) {
        await this.username.sendKeys(login);
        await this.password.sendKeys(password);
        await this.submitForm();
    }
}

class Auth {
    currentRole: Role = Role.admin;

    async loginAsAdmin(baseUrl: string) {
        const form = new LoginForm();
        const whmcsUrl = `https://${whmcsLogin}:${whmcsHttpPassword}@${baseUrl}/admin`;
        await browser.get(whmcsUrl);
        await form.login(whmcsLogin, whmcsPassword);
    }

    async withWhmcsUser<E>(fn: () => E, role: Role = Role.admin, name = 'e2e') {
        this.currentRole = role;

        if (this.isAdmin()) {
            await browser.get(`admin/clients.php`);
            await clickWhenClickable(
                element(by.cssContainingText('.tablebg td:nth-child(3) a', name)));
        } else {
            await browser.get(`dologin.php?username=e2e@email.com`);
            await browser.get(`clientarea.php?action=services`);
        }
        return fn();
    }

    isAdmin() {
        return this.currentRole === Role.admin;
    }

    isUser() {
        return this.currentRole === Role.user;
    }
}
export const auth = new Auth();
