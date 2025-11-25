const path = require('path');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

const blocksDir = path.resolve(__dirname, 'src/blocks');
const blockEntries = {};
if (fs.existsSync(blocksDir)) {
  fs.readdirSync(blocksDir, { withFileTypes: true }).forEach((dirent) => {
    if (!dirent.isDirectory()) {
      return;
    }
    const entry = path.join(blocksDir, dirent.name, 'index.js');
    if (fs.existsSync(entry)) {
      blockEntries[`blocks/${dirent.name}`] = entry;
    }
  });
}

module.exports = {
  entry: {
    style: './src/css/style.css',
    main: './src/js/main.js',
    ...blockEntries,
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js',
    clean: true,
  },
  resolve: {
    extensions: ['.js', '.jsx'],
  },
  module: {
    rules: [
      {
        test: /\.css$/i,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          {
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [require('tailwindcss'), require('autoprefixer')],
              },
            },
          },
        ],
      },
      {
        test: /\.jsx?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            sourceType: 'unambiguous',
            presets: [['@babel/preset-env', { modules: 'commonjs' }]],
            plugins: [
              [
                '@babel/plugin-transform-react-jsx',
                {
                  pragma: 'wp.element.createElement',
                  pragmaFrag: 'wp.element.Fragment',
                },
              ],
            ],
          },
        },
      },
    ],
  },
  externals: {
    '@wordpress/blocks': ['wp', 'blocks'],
    '@wordpress/block-editor': ['wp', 'blockEditor'],
    '@wordpress/components': ['wp', 'components'],
    '@wordpress/element': ['wp', 'element'],
    '@wordpress/i18n': ['wp', 'i18n'],
    '@wordpress/data': ['wp', 'data'],
  },
  plugins: [
    new MiniCssExtractPlugin({ filename: '[name].css' }),
    new BrowserSyncPlugin({
      proxy: 'http://ewenique-blueprint.local',
      files: ['**/*.php', 'dist/**/*.css', 'dist/**/*.js'],
      injectChanges: true,
      open: false,
    }),
  ],
  mode: 'development',
  devtool: 'source-map',
  watch: false,
};
