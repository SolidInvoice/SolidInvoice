const routing = require('imports-loader?window=>{}!exports-loader?router=window.Routing,setData=fos.Router.setData!!../../../../../../web/bundles/fosjsrouting/js/router.js');
const routerConfig = require('../../../../../../web/js/fos_js_routes.js');

routing.setData(routerConfig);

module.exports = routing.router;