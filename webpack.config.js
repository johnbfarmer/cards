var webpack = require('webpack');
var path = require('path');

var BUILD_DIR = path.resolve(__dirname, 'web/js');
var APP_DIR = path.resolve(__dirname, 'web/js/scripts');

var config = {
  entry: {
    main: APP_DIR + '/index.js',
  },
  output: {
    path: BUILD_DIR,
    filename: '[name].js'
  },
  module: {
    rules: [
      { test: /\.js$/, loader: 'babel-loader', include: APP_DIR },
      { test: /\.jsx$/, loader: 'babel-loader', include: APP_DIR },
      { test: /\.css$/, use: ['style-loader', 'css-loader']}
    ],
  }
};

module.exports = config;
