/*const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: {
  	vendor:['./jquery-3.3.2.js','./semantic/dist/semantic.js']
  },
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: 'pack.js'
  },
   plugins: [
     new webpack.ProvidePlugin({
       $: 'jquery'
     })
   ]
};*/
const path = require('path');
const MergeIntoSingleFilePlugin = require('webpack-merge-and-include-globally');

module.exports = {
  entry: './x.js',
  output: {
    filename: 'o.js',
    path: path.resolve(__dirname, 'dist'),
  },
  plugins: [
    new MergeIntoSingleFilePlugin({files:{
      "pack.js": [
        path.resolve(__dirname, 'jquery-3.3.2.js'),
        path.resolve(__dirname, 'semantic/dist/semantic.js')
      ],
    },
  transform: {
    'pack.js': code => require("uglify-js").minify(code).code
  }})
  ]
};