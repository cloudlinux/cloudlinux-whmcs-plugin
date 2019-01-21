import { $, browser, by, element, ElementFinder, protractor } from 'protractor';
import { range } from 'lodash';
import { BaseFragment } from 'protractor-element-extend';

const REPEAT_UNTIL_RETRIES = 15;
const DEFAULT_WAIT_TIME = 10;
export const repeatUntil = (check) => {
    return (target, propertyKey, descriptor: TypedPropertyDescriptor<any>) => {
        const originalMethod = descriptor.value;
        const newMethod = async function (...args: any[]) {
            const errorMessage = args[args.length - 1].errorMessage || '';
            const element = args[0];
            for (let i of range(REPEAT_UNTIL_RETRIES)) {
                try {
                    await originalMethod.apply(this, args);
                } catch (e) {}
                try {
                    if (!await check(...args)) {
                        throw new Error('check failed');
                    }
                    return;
                } catch (e) {
                    console.log(`check ${propertyKey} failed because of `, e.message);
                }
            }
            fail(`${propertyKey} failed execute for ${element.locator()}, ${errorMessage}`);
        };
        descriptor.value = newMethod;
    };
};
export class Waiter {
    @repeatUntil(element => element.isPresent())
    async forElement(element, {errorMessage = '', waitTime = DEFAULT_WAIT_TIME} = {}) {
        await browser.wait(
            protractor.ExpectedConditions.presenceOf(element),
            waitTime * 1000 / REPEAT_UNTIL_RETRIES,
            errorMessage,
        );
    }
    @repeatUntil(async (element, text) => {
        return await element.getText() === text;
    })
    async forElementToHaveText(element, text, {waitTime = DEFAULT_WAIT_TIME, errorMessage = ''} = {}) {
        await this.forElement(element, {
            errorMessage: `forElementToHaveText failed for text=${text}`,
        });
        await browser.wait(
            protractor.ExpectedConditions.textToBePresentInElement(element, text),
            waitTime * 1000 / REPEAT_UNTIL_RETRIES,
            errorMessage,
        );
    }
    @repeatUntil(async element => !await element.isPresent())
    async forElementToBeAbsent(element, {waitTime = DEFAULT_WAIT_TIME, errorMessage = ''} = {}) {
        await browser.wait(
            protractor.ExpectedConditions.not(protractor.ExpectedConditions.presenceOf(element)),
            waitTime * 1000 / REPEAT_UNTIL_RETRIES,
            errorMessage,
        );
    }
    @repeatUntil(async element => await element.isPresent() && await !!element.isDisplayed())
    async forElementToBeInvisible(element, {waitTime = DEFAULT_WAIT_TIME} = {}) {
        await browser.wait(
            protractor.ExpectedConditions.invisibilityOf(element),
            waitTime * 1000 / REPEAT_UNTIL_RETRIES,
        );
    }
    @repeatUntil(async element => await element.isPresent() && await element.isDisplayed())
    async forElementToBeVisible(element, {waitTime = DEFAULT_WAIT_TIME, errorMessage = ''} = {}) {
        await browser.wait(
            protractor.ExpectedConditions.visibilityOf(element),
            waitTime * 1000 / REPEAT_UNTIL_RETRIES,
            errorMessage,
        );
    }
}
export const wait = new Waiter();

export const clickWhenClickable = async (el: ElementFinder, timeout = 15000) => {
    let stopClickingAndFail = false;
    const timeoutRef = setTimeout(() => stopClickingAndFail = true, timeout);
    while (true) {
        try {
            await scrollToElement(el);
            await wait.forElementToBeVisible(el);
            await browser.wait(protractor.ExpectedConditions.elementToBeClickable(el), 500);
        } catch (e) { }
        try {
            console.warn(`Try to click on ${el.locator()}!`);
            await el.click();
            break;
        } catch (e) {
            await browser.sleep(100);
            if (stopClickingAndFail) {
                fail(new Error(`Can not click on ${el.locator()}!`));
                break;
            }
        }
    }
    clearTimeout(timeoutRef);
};

export class Form extends BaseFragment {
    submitButton = this.$('[type=submit]');

    constructor(el: ElementFinder = $('body')) {
        super(el);
    }
    elByName(name: string) {
        return this.$$(`[name="${name}"]`).last();
    }
    submitForm() {
        return clickWhenClickable(this.submitButton);
    }
}
export class HTMLSelect extends BaseFragment {
    async choose(text: string) {
        await clickWhenClickable(this.element(by.cssContainingText('option', text)));
    }
}

export class CLSelect extends BaseFragment {
    async choose(text: string) {
        await clickWhenClickable(this);
        await clickWhenClickable(element(by.cssContainingText('.md-list-item-text', text)));
    }
}

export class Checkbox extends BaseFragment {
    async switch(checked: boolean) {
        await wait.forElement(this);
        const selected = await this.isSelected();
        if (selected !== checked) {
            await clickWhenClickable(this);
        }
    }
}

export const scrollToBottom = () => browser.executeScript('window.scrollTo(0,document.body.scrollHeight)');
export const scrollToElement = (el: ElementFinder) =>
    browser.executeScript("arguments[0].scrollIntoView();", el.getWebElement());
export async function repeatWhile<T>({action, delay = 2, attempts = 10, test = (a) => !!a}: {
    delay?: number,
    attempts?: number,
    action?(): T | Promise<T>,
    test?(result: T): boolean | Promise<boolean>,
}) {
    let attemptsLeft = attempts;
    while (attemptsLeft--) {
        let result = await action();
        if (!(await test(result))) {
            return result;
        }
        console.log(`${attemptsLeft} attempts left...`);
        await browser.sleep(Math.round(delay * 1000));
    }
    throw new Error(`was not successful in ${attempts} attempts`);
}
