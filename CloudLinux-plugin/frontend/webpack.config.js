const path = require('path');
const webpack = require('webpack');
const miniCssExtractPlugin = require('mini-css-extract-plugin');
const vueLoaderPlugin = require('vue-loader/lib/plugin');
const prefixSelector = require('postcss-prefix-selector');

const postCSSLoader = {
    loader: 'postcss-loader',
    options: {
        plugins: () => [
            prefixSelector({
                prefix: '.cl-whmcs',
                exclude: ['body', '.cl-whmcs'],
                transform: (prefix, selector, prefixedSelector) => {
                    return /(^.md-.*)/.test(selector)
                        ? (/(^.md-theme)/.test(selector) ? prefixedSelector : selector)
                        : prefixedSelector;
                }
            }),
            require('autoprefixer'),
        ]
    }
};

module.exports = {
    entry: {
        bundle: './src/index.ts',
        vendor: ['vuelidate'],
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, '../modules/servers/CloudLinuxLicenses/templates/assets/addon'),
    },
    resolve: {
        modules: [
            path.join(__dirname, 'src'),
            path.join(__dirname, 'node_modules')
        ],
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        },
        extensions: ['.ts', '.js', '.vue']
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                enforce: 'pre',
                use: [
                    {
                        loader: 'tslint-loader',
                        options: {
                            configFile: 'tslint.json',
                            tsConfigFile: 'tsconfig.json',
                            fix: true,
                            failOnHint: true,
                        },
                    },
                ],
                include: ['./src'],
            },
            {
                test: /\.ts$/,
                exclude: /node_modules/,
                use: {
                    loader: 'ts-loader',
                    options: {
                        appendTsSuffixTo: [/\.vue$/]
                    }
                }
            },
            {
                test: /\.vue$/,
                use: 'vue-loader'

            },
            {
                test: /\.css$/,
                use: [
                    miniCssExtractPlugin.loader,
                    'css-loader',
                    postCSSLoader,
                ],
            },
            {
                test: /\.scss$/,
                use: [
                    miniCssExtractPlugin.loader,
                    'css-loader',
                    postCSSLoader,
                    'sass-loader',
                ],
            },
            {
                test: /\.(png|svg|jpg|gif)$/,
                use: [{
                    loader: 'url-loader',
                    options: {
                        limit: 8192
                    }
                }]
            },
            {
                test: /\.(woff|woff2|eot|ttf|otf)$/,
                use: [
                    'file-loader'
                ]
            }
        ]
    },
    plugins: [
        new vueLoaderPlugin(),
        new miniCssExtractPlugin({
            filename: 'styles.css'
        }),
    ],
    devtool: 'inline-source-map',
    optimization: {
        minimize: process.env.NODE_ENV === 'production',
    }
};

if (process.env.NODE_ENV === 'production') {
    module.exports.devtool = 'none';
    // http://vue-loader.vuejs.org/en/workflow/production.html
    module.exports.plugins = (module.exports.plugins || []).concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: true
        })
    ]);
}
