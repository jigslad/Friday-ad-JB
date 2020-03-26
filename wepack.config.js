var Encore = require('@symfony/webpack-encore');

Encore
    // directory where compiled assets will be stored
    .setOutputPath('web/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addStyleEntry('css/general', './assets/scss/general.scss')
    .addStyleEntry('css/basic', './assets/scss/basic.scss')
    .addStyleEntry('css/footer', './assets/scss/footer.scss')
    .addStyleEntry('css/header', './assets/scss/header.scss')
    .addStyleEntry('css/homeContent', './assets/scss/homecontent.scss')
    .addStyleEntry('css/readFridayAdOnline', './assets/scss/readFridayAdOnline.scss')
    .addStyleEntry('css/paa', './assets/scss/paa.scss')
    .addStyleEntry('css/login-register', './assets/scss/login-register.scss')

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use Sass/SCSS files
    .enableSassLoader()

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();