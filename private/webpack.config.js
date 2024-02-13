const path = require('path');
const webpack = require('webpack');
const ESLintPlugin = require('eslint-webpack-plugin');

// Project paths.
const SRC_PATH = './src';
const OUT_PATH = '../assets/';

const config = (env, argv) => ({
  entry: {
    main: path.resolve(__dirname, `${SRC_PATH}/main.js`),
    admin: path.resolve(__dirname, `${SRC_PATH}/admin.js`),
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, OUT_PATH),
  },
  watchOptions: {
    ignored: /node_modules/,
  },
  stats: {
    builtAt: true,
    children: false,
  },
  plugins: [
    // Adds banner to bundles.
    new webpack.BannerPlugin(
      `Copyright (c) ${new Date().getFullYear()} Big Bite® | bigbite.net | @bigbite`,
    ),

    new ESLintPlugin(),

    // Sets mode so we can access it in `postcss.config.js`.
    new webpack.LoaderOptionsPlugin({
      options: {
        mode: argv.mode,
      },
    }),
  ].filter(Boolean),
  devtool: 'source-map',
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: ['babel-loader'],
      },
    ],
  },
});

module.exports = (env, argv) => config(env, argv);
