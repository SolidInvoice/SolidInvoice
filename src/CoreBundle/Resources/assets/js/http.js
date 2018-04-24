import axios from 'axios';
import Router from './router';
import qs from 'qs';

const METHOD_GET = 'get';
const METHOD_POST = 'post';

export default class Http {
    _httpClient;
    _activeCalls = 0;

    constructor(options = {}) {
        this._httpClient = axios.create({
            timeout: options.timeout || 60000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            paramsSerializer: function(params) {
                return qs.stringify(params, {arrayFormat: 'brackets'})
            },
        });
    }

    _performRequest(url, method = METHOD_GET, data = {}) {
        return new Promise((resolve, reject) => {
            this._activeCalls++;

            this._httpClient({url, method, data})
                .then(resolve)
                .catch(reject)
                .finally(() => {
                    this._activeCalls--;
                });
        });
    }

    [METHOD_GET](route, routeParameters = {}) {
        return this._performRequest(Router.generate(route, routeParameters), METHOD_GET);
    }

    [METHOD_POST](route, routeParameters = {}, data = {}) {
        return this._performRequest(Router.generate(route, routeParameters), METHOD_POST, data);
    }

    hasActiveCalls() {
        return this._activeCalls > 0;
    }
}