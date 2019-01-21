///<reference path="node_modules/@types/jasmine/index.d.ts"/>

require('ts-node/register');
require('tsconfig-paths/register');  // resolve baseUrl and paths from tsconfig in runtime

const env = require('environment');
const path = require('path');
const fs = require('fs-extra');
const util = require('util');
const chalk = require('chalk');
const consoleStamp = require('console-stamp');
const navigation = require('utils/navigation');
const REPORTS_DIR = './reports';
const TIMEOUT = 20 * 60 * 1000;  // 10 minutes

let seleniumProcess;

exports.config = {
    // logLevel: 'DEBUG',
    // troubleshoot: true,
    baseUrl: `https://${env.WHMCS_HOST}`,
    SELENIUM_PROMISE_MANAGER: false,
    seleniumAddress: env.USE_LOCAL_SELENIUM ? null : 'http://127.0.0.1:4444/wd/hub',
    webDriverLogDir: process.execArgv.find(arg => /^(--inspect|--debug)(-brk)?(?!=0)/.test(arg)) ? null : REPORTS_DIR,
    // use `npm run e2e`
    specs: [
        path.join(__dirname, 'tests', '*.e2e.ts'),
    ],

    framework: 'jasmine2',

    allScriptsTimeout: TIMEOUT,

    jasmineNodeOpts: {
        showTiming: true,
        showColors: true,
        isVerbose: false,
        includeStackTrace: false,
        defaultTimeoutInterval: TIMEOUT,
        print: function () {
        }
    },
    // directConnect: true,

    capabilities: {
        browserName: 'chrome',
        chromeOptions: {
            args: [
                'disable-web-security', 'no-sandbox',
                ...(env.HEADLESS ? ['headless', 'disable-gpu'] : []),
            ]
        },
        acceptInsecureCerts: true,
    },

    plugins: [{
        package: 'protractor-screenshoter-plugin',
        screenshotOnExpect: 'failure+success',
        screenshotOnSpec: 'none',
        withLogs: true,
        htmlReport: true,
        screenshotPath: path.join(REPORTS_DIR, 'ui_report'),
        writeReportFreq: 'spec',  // 'end',
        verbose: 'info',  // 'debug',
        pauseOn: 'never',
        imageToAscii: 'none',
        clearFoldersBeforeTest: false,
        failTestOnErrorLog: {
            failTestOnErrorLogLevel: 1001,
            excludeKeywords: [
                'news.json',
                'facebook.com',
                '401 (Access Denied)',
                'License expiration exceeded',
                'Conflicting setting',
            ],
        },
    }],

    beforeLaunch: async () => {
        if (env.USE_STANDALONE_SELENIUM) {
            const selenium = require('selenium-standalone');
            const drivers = {chrome: {}};
            console.log('starting selenium-standalone...');
            await util.promisify(selenium.install)({drivers});
            seleniumProcess = await util.promisify(selenium.start)({drivers, port: 4444});
        }

        // remove reports dir
        fs.emptyDirSync(REPORTS_DIR);
    },

    onPrepare: async () => {
        consoleStamp(console, {
            pattern: 'HH:MM:ss.l',
            colors: {stamp: chalk.gray, label: chalk.gray}
        });

        browser.ignoreSynchronization = true;

        let width = 1280;
        let height = 2000;
        await browser.driver.manage().window().setSize(width, height);

        let SpecReporter = require('jasmine-spec-reporter').SpecReporter;
        jasmine.getEnv().addReporter(new SpecReporter({spec: {displayStacktrace: 'all'}}));

        let xmlReporter = require('jasmine-reporters').JUnitXmlReporter;
        jasmine.getEnv().addReporter(
            new xmlReporter({
                savePath: path.join(REPORTS_DIR, 'xml_report/'),
            }),
        );
        await navigation.auth.loginAsAdmin(env.WHMCS_HOST);

        // wait for the reporter config before executing tests
        await browser.getProcessedConfig();

    },
    afterLaunch: () => {
        if (seleniumProcess) {
            seleniumProcess.kill();
        }
    },
};
