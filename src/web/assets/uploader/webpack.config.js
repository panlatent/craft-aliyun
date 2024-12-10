const { getConfig } = require("@craftcms/webpack");
const CopyWebpackPlugin = require("copy-webpack-plugin");

module.exports = getConfig({
  context: __dirname,
  config: {
    entry: {
      Uploader: "./Uploader.js",
    },
  },
});
