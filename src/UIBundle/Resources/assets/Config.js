import {extend, has} from 'lodash';

class Config {
    _config = {};

    load(metaName = 'app_config') {
        let meta = window.document.querySelectorAll(`meta[name="${metaName}"]`);

        meta.forEach((node) => {
            extend(this._config, JSON.parse(node.content));
        });
    }

    get(key) {
        return has(this._config, key) ? this._config[key] : {};
    }
}

export let config = new Config();